#Requires -Version 5.1
<#
.SYNOPSIS
    สคริปต์กู้คืนข้อมูลสำหรับ Windows XAMPP (PowerShell)
    รับไฟล์ .sql หรือ .sql.gz แล้ว restore ไปยังฐานข้อมูล

.DESCRIPTION
    - ใช้ mysql.exe --default-character-set=utf8mb4
    - รองรับไฟล์ .sql (plain) และ .sql.gz (gzip compressed)
    - มี confirmation prompt และ --force flag เพื่อข้ามการยืนยัน
    - มี error handling และ exit code

.PARAMETER BackupFile
    Path ไปยังไฟล์สำรอง (.sql หรือ .sql.gz) - required

.PARAMETER DBName
    ชื่อฐานข้อมูลปลายทาง (default: equipment_db)

.PARAMETER DBHost
    Host ฐานข้อมูล (default: localhost)

.PARAMETER DBUser
    User ฐานข้อมูล (default: root)

.PARAMETER DBPass
    รหัสผ่านฐานข้อมูล (default: "")

.PARAMETER MysqlPath
    Path ไปยัง mysql.exe (default: C:\xampp\mysql\bin\mysql.exe)

.PARAMETER Force
    ข้าม confirmation prompt (switch)

.EXAMPLE
    .\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz
    .\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql -Force
    .\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz -DBName equipment_db -Force
    .\scripts\restore.ps1 backups\equipment_db_2026-09-03_120000.sql.gz --force
#>

[CmdletBinding(PositionalBinding=$true)]
param(
    [Parameter(Mandatory=$true, Position=0)]
    [string]$BackupFile,

    [string]$DBName = "equipment_db",
    [string]$DBHost = "localhost",
    [string]$DBUser = "root",
    [string]$DBPass = "",
    [string]$MysqlPath = "C:\xampp\mysql\bin\mysql.exe",
    [switch]$Force
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Support --force as alias (for bash-style invocation)
# PowerShell will parse --force as Force switch if defined with alias, but to be safe handle extra args
# Also allow positional file with --force after

$ProjectRoot = Split-Path $PSScriptRoot -Parent
if (-not $ProjectRoot) { $ProjectRoot = (Get-Location).Path }

# Resolve BackupFile path (relative to project root or absolute)
if (-not [System.IO.Path]::IsPathRooted($BackupFile)) {
    $candidate1 = Join-Path $ProjectRoot $BackupFile
    $candidate2 = Join-Path (Get-Location).Path $BackupFile
    if (Test-Path -LiteralPath $candidate1) {
        $BackupFileFull = $candidate1
    } elseif (Test-Path -LiteralPath $candidate2) {
        $BackupFileFull = $candidate2
    } else {
        $BackupFileFull = $candidate1  # for error message
    }
} else {
    $BackupFileFull = $BackupFile
}

$BackupDirFull = Split-Path $BackupFileFull -Parent
if (-not $BackupDirFull) { $BackupDirFull = $ProjectRoot }
$LogFile = Join-Path (Join-Path $ProjectRoot "backups") "restore.log"
$TimestampLog = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $line = "[$TimestampLog] [$Level] $Message"
    try {
        $logDir = Split-Path $LogFile -Parent
        if (-not (Test-Path -LiteralPath $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        Add-Content -LiteralPath $LogFile -Value $line -Encoding UTF8
    } catch {}
    switch ($Level) {
        "ERROR" { Write-Host $line -ForegroundColor Red }
        "WARN"  { Write-Host $line -ForegroundColor Yellow }
        default { Write-Host $line }
    }
}

function Test-CommandExists {
    param([string]$Command)
    return $null -ne (Get-Command $Command -ErrorAction SilentlyContinue)
}

$ExitCode = 0

try {
    Write-Log "=== Restore requested: File=$BackupFileFull DB=$DBName Host=$DBHost ==="

    # Validate file exists
    if (-not (Test-Path -LiteralPath $BackupFileFull)) {
        throw "Backup file not found: $BackupFileFull"
    }

    $fileInfo = Get-Item -LiteralPath $BackupFileFull
    if ($fileInfo.Length -eq 0) {
        throw "Backup file is empty: $BackupFileFull"
    }

    # Validate extension
    if ($BackupFileFull -notmatch '\.sql(\.gz)?$') {
        throw "Unsupported file type: $BackupFileFull (expected .sql or .sql.gz)"
    }

    $isGz = $BackupFileFull -like "*.gz"

    # Validate mysql
    if (-not (Test-Path -LiteralPath $MysqlPath)) {
        if (Test-CommandExists "mysql") {
            $MysqlPath = (Get-Command mysql).Source
            Write-Log "mysql not found at default path, using PATH: $MysqlPath" "WARN"
        } else {
            throw "mysql not found at '$MysqlPath' and not in PATH. Please set -MysqlPath"
        }
    }

    # Confirmation prompt (unless -Force)
    if (-not $Force) {
        Write-Host ""
        Write-Host "⚠️  คำเตือน: การกู้คืนจะเขียนทับข้อมูลในฐานข้อมูล '$DBName' ทั้งหมด!" -ForegroundColor Yellow
        Write-Host "   ไฟล์: $BackupFileFull" -ForegroundColor Cyan
        Write-Host "   ขนาด: $($fileInfo.Length) bytes" -ForegroundColor Cyan
        Write-Host "   DB: $DBName @ $DBHost (user: $DBUser)" -ForegroundColor Cyan
        Write-Host ""
        $confirm = Read-Host "พิมพ์ 'YES' เพื่อยืนยันการกู้คืน หรือกด Enter เพื่อยกเลิก"
        if ($confirm -ne "YES") {
            Write-Log "Restore cancelled by user (input: '$confirm')" "WARN"
            Write-Host "ยกเลิกการกู้คืน" -ForegroundColor Yellow
            exit 0
        }
    } else {
        Write-Log "Force mode: skipping confirmation"
    }

    # Prepare temp decompressed file if needed
    $tempSqlFile = $null
    $sqlSource = $BackupFileFull

    if ($isGz) {
        Write-Log "Decompressing gzip file: $BackupFileFull"
        $tempSqlFile = Join-Path ([System.IO.Path]::GetTempPath()) "restore_$(Get-Date -Format 'yyyyMMdd_HHmmss')_$([System.IO.Path]::GetFileNameWithoutExtension($BackupFileFull)).sql"
        # Try gzip command first
        $decompressed = $false
        if (Test-CommandExists "gzip") {
            try {
                $gzipExe = (Get-Command gzip).Source
                # gzip -dc file > temp
                $psi = New-Object System.Diagnostics.ProcessStartInfo
                $psi.FileName = $gzipExe
                $psi.Arguments = "-dc `"$BackupFileFull`""
                $psi.UseShellExecute = $false
                $psi.RedirectStandardOutput = $true
                $psi.RedirectStandardError = $true
                $proc = [System.Diagnostics.Process]::Start($psi)
                $outputStream = [System.IO.File]::Create($tempSqlFile)
                $proc.StandardOutput.BaseStream.CopyTo($outputStream)
                $outputStream.Close()
                $proc.WaitForExit()
                $stderr = $proc.StandardError.ReadToEnd()
                if ($proc.ExitCode -eq 0 -and (Test-Path -LiteralPath $tempSqlFile) -and (Get-Item -LiteralPath $tempSqlFile).Length -gt 0) {
                    $decompressed = $true
                    Write-Log "Decompressed with gzip to temp file"
                } else {
                    Write-Log "gzip decompression failed (exit $($proc.ExitCode)): $stderr" "WARN"
                    if (Test-Path -LiteralPath $tempSqlFile) { Remove-Item -LiteralPath $tempSqlFile -Force -ErrorAction SilentlyContinue }
                }
            } catch {
                Write-Log "gzip decompression exception: $($_.Exception.Message)" "WARN"
            }
        }

        if (-not $decompressed) {
            # Fallback to .NET GzipStream
            try {
                Write-Log "Decompressing with .NET GzipStream..."
                $inputStream = [System.IO.File]::OpenRead($BackupFileFull)
                $outputStream = [System.IO.File]::Create($tempSqlFile)
                $gzipStream = New-Object System.IO.Compression.GzipStream($inputStream, [System.IO.Compression.CompressionMode]::Decompress)
                $gzipStream.CopyTo($outputStream)
                $gzipStream.Close()
                $outputStream.Close()
                $inputStream.Close()
                if ((Test-Path -LiteralPath $tempSqlFile) -and (Get-Item -LiteralPath $tempSqlFile).Length -gt 0) {
                    $decompressed = $true
                    Write-Log "Decompressed with GzipStream"
                } else {
                    throw "GzipStream produced empty file"
                }
            } catch {
                if (Test-Path -LiteralPath $tempSqlFile) { Remove-Item -LiteralPath $tempSqlFile -Force -ErrorAction SilentlyContinue }
                throw "Failed to decompress .sql.gz file: $($_.Exception.Message)"
            }
        }

        if (-not $decompressed) {
            throw "Failed to decompress gzip file with all methods"
        }
        $sqlSource = $tempSqlFile
    }

    # Build mysql arguments
    $mysqlArgs = @(
        "--host=$DBHost",
        "--user=$DBUser",
        "--default-character-set=utf8mb4",
        $DBName
    )
    if ($DBPass -ne "") {
        $mysqlArgs = @("--password=$DBPass") + $mysqlArgs
        # Alternative: use MYSQL_PWD env to avoid showing password in process list
        # But we already use --password; set env as well for safety
        $env:MYSQL_PWD = $DBPass
    }

    Write-Log "Restoring to database '$DBName' from '$sqlSource'..."
    Write-Log "Command: $MysqlPath --host=$DBHost --user=$DBUser --default-character-set=utf8mb4 $DBName < `"$sqlSource`""

    # Execute mysql with input redirection
    # Use ProcessStartInfo to pipe file content to stdin
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $MysqlPath
    $psi.Arguments = ($mysqlArgs | ForEach-Object { if ($_ -match '\s') { "`"$_`"" } else { $_ } }) -join " "
    $psi.UseShellExecute = $false
    $psi.RedirectStandardInput = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.StandardOutputEncoding = [System.Text.Encoding]::UTF8
    $psi.StandardErrorEncoding = [System.Text.Encoding]::UTF8

    $proc = New-Object System.Diagnostics.Process
    $proc.StartInfo = $psi
    $null = $proc.Start()

    # Stream file content to stdin (handle large files by chunk)
    $reader = [System.IO.File]::OpenText($sqlSource)
    # Use async write to avoid deadlock; simplest: ReadToEnd and Write
    # For large files, stream line by line
    try {
        $bufferSize = 81920
        $buffer = New-Object char[] $bufferSize
        while (($read = $reader.Read($buffer, 0, $bufferSize)) -gt 0) {
            $proc.StandardInput.Write($buffer, 0, $read)
        }
    } finally {
        $reader.Close()
        $proc.StandardInput.Close()
    }

    $stdout = $proc.StandardOutput.ReadToEnd()
    $stderr = $proc.StandardError.ReadToEnd()
    $proc.WaitForExit()

    # Clear MYSQL_PWD from env
    if ($env:MYSQL_PWD) { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue }

    if ($proc.ExitCode -ne 0) {
        throw "mysql restore failed with exit code $($proc.ExitCode): $stderr $stdout"
    }

    if ($stderr -and $stderr.Trim() -ne "") {
        Write-Log "mysql stderr: $stderr" "WARN"
    }

    Write-Log "=== Restore completed successfully to DB '$DBName' ==="
    Write-Host "✅ กู้คืนข้อมูลสำเร็จ: $BackupFileFull -> $DBName" -ForegroundColor Green
    $ExitCode = 0

} catch {
    $err = $_.Exception.Message
    if ($_.ScriptStackTrace) { $err += " | Stack: $($_.ScriptStackTrace)" }
    Write-Log "Restore failed: $err" "ERROR"
    Write-Host "❌ กู้คืนล้มเหลว: $err" -ForegroundColor Red
    $ExitCode = 1
    # Clear MYSQL_PWD
    if ($env:MYSQL_PWD) { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue }
} finally {
    # Cleanup temp file
    if ($tempSqlFile -and (Test-Path -LiteralPath $tempSqlFile)) {
        try { Remove-Item -LiteralPath $tempSqlFile -Force -ErrorAction SilentlyContinue; Write-Log "Cleaned temp file: $tempSqlFile" } catch {}
    }
}

exit $ExitCode
