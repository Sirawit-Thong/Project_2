#Requires -Version 5.1
<#
.SYNOPSIS
    สคริปต์สำรองข้อมูลสำหรับ Windows XAMPP (PowerShell)
    สำรองฐานข้อมูล equipment_db และไฟล์ uploads

.DESCRIPTION
    - ใช้ mysqldump จาก C:\xampp\mysql\bin\mysqldump.exe
    - dump พร้อม options: --default-character-set=utf8mb4 --single-transaction --routines --triggers --set-gtid-purged=OFF
    - บีบอัดเป็น .sql.gz (ใช้ gzip ถ้ามี, fallback เป็น .NET GzipStream หรือ Compress-Archive)
    - zip โฟลเดอร์ uploads (equipment + repairs + .htaccess)
    - Retention: ลบไฟล์เกิน KeepDays วัน (daily), เกิน 30 วันลบแม้ weekly
    - Log ไป backups/backup.log

.PARAMETER DBName
    ชื่อฐานข้อมูล (default: equipment_db)

.PARAMETER DBHost
    Host ฐานข้อมูล (default: localhost)

.PARAMETER DBUser
    User ฐานข้อมูล (default: root)

.PARAMETER DBPass
    รหัสผ่านฐานข้อมูล (default: "")

.PARAMETER BackupDir
    โฟลเดอร์เก็บไฟล์สำรอง (default: backups) รองรับทั้ง relative และ absolute path

.PARAMETER KeepDays
    จำนวนวันเก็บไฟล์ daily (default: 7)

.PARAMETER KeepWeeklyDays
    จำนวนวันเก็บไฟล์ weekly (default: 30)

.PARAMETER MysqldumpPath
    Path ไปยัง mysqldump.exe (default: C:\xampp\mysql\bin\mysqldump.exe)

.EXAMPLE
    .\scripts\backup.ps1
    .\scripts\backup.ps1 -DBName equipment_db -BackupDir backups -KeepDays 7
    .\scripts\backup.ps1 -DBName equipment_db -DBHost localhost -DBUser root -DBPass "secret" -KeepDays 14
#>

[CmdletBinding()]
param(
    [string]$DBName = "equipment_db",
    [string]$DBHost = "localhost",
    [string]$DBUser = "root",
    [string]$DBPass = "",
    [string]$BackupDir = "backups",
    [int]$KeepDays = 7,
    [int]$KeepWeeklyDays = 30,
    [string]$MysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Resolve paths ---
$ProjectRoot = Split-Path $PSScriptRoot -Parent
if (-not $ProjectRoot) { $ProjectRoot = (Get-Location).Path }

if ([System.IO.Path]::IsPathRooted($BackupDir)) {
    $BackupDirFull = $BackupDir
} else {
    $BackupDirFull = Join-Path $ProjectRoot $BackupDir
}

$Timestamp = Get-Date -Format "yyyy-MM-dd_HHmmss"
$TimestampLog = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$SqlFileName = "${DBName}_${Timestamp}.sql"
$GzFileName = "${SqlFileName}.gz"
$ZipFileName = "uploads_${Timestamp}.zip"
$LogFile = Join-Path $BackupDirFull "backup.log"

$SqlFile = Join-Path $BackupDirFull $SqlFileName
$GzFile = Join-Path $BackupDirFull $GzFileName
$ZipFile = Join-Path $BackupDirFull $ZipFileName

$ExitCode = 0

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $line = "[$TimestampLog] [$Level] $Message"
    # Ensure backup dir exists before logging
    try {
        $logDir = Split-Path $LogFile -Parent
        if (-not (Test-Path -LiteralPath $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        Add-Content -LiteralPath $LogFile -Value $line -Encoding UTF8
    } catch {
        # fallback to console if log write fails
    }
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

try {
    Write-Log "=== Backup started: DB=$DBName Host=$DBHost BackupDir=$BackupDirFull KeepDays=$KeepDays ==="

    # Ensure backup directory exists
    if (-not (Test-Path -LiteralPath $BackupDirFull)) {
        New-Item -ItemType Directory -Path $BackupDirFull -Force | Out-Null
        Write-Log "Created backup directory: $BackupDirFull"
    }

    # Validate mysqldump
    if (-not (Test-Path -LiteralPath $MysqldumpPath)) {
        # try fallback to PATH
        if (Test-CommandExists "mysqldump") {
            $MysqldumpPath = (Get-Command mysqldump).Source
            Write-Log "Mysqldump not found at default path, using PATH: $MysqldumpPath" "WARN"
        } else {
            throw "mysqldump not found at '$MysqldumpPath' and not in PATH. Please install MySQL client or set -MysqldumpPath"
        }
    }

    # Build mysqldump arguments
    $dumpArgs = @(
        "--host=$DBHost",
        "--user=$DBUser",
        "--default-character-set=utf8mb4",
        "--single-transaction",
        "--routines",
        "--triggers",
        "--set-gtid-purged=OFF"
    )
    if ($DBPass -ne "") {
        $dumpArgs += "--password=$DBPass"
    }
    $dumpArgs += $DBName

    Write-Log "Running mysqldump for database '$DBName'..."
    # Execute mysqldump and redirect output to file
    # Use Start-Process or call operator with redirect
    $dumpOutput = & $MysqldumpPath @dumpArgs 2>&1
    # Check exit code
    if ($LASTEXITCODE -ne 0) {
        $errMsg = $dumpOutput | Out-String
        throw "mysqldump failed with exit code $LASTEXITCODE : $errMsg"
    }

    # $dumpOutput is array of lines; need to write to file with utf8mb4
    # However & operator above already captures output; we need to re-run with redirection to file properly
    # Alternative: run again with Out-File if captured string is valid, or use redirection
    # We have $dumpOutput as strings; write them to $SqlFile
    # To preserve original behavior (avoid double run), write captured output to file
    # But mysqldump output may contain binary; better to use redirection operator
    
    # If $dumpOutput is already captured, write it; otherwise if we want to ensure correct file, re-execute with redirection
    # Approach: if file not yet written, write captured output
    # Detect if $SqlFile exists and has content; if not, write $dumpOutput
    if ($dumpOutput -is [array] -or $dumpOutput -is [string]) {
        # Write captured output to file
        # Use UTF8 without BOM
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllLines($SqlFile, @($dumpOutput), $utf8NoBom)
    }

    # Fallback: if file still empty or not exists, try direct redirection method
    if (-not (Test-Path -LiteralPath $SqlFile) -or (Get-Item -LiteralPath $SqlFile).Length -eq 0) {
        Write-Log "Captured output empty, retrying mysqldump with direct file redirection..." "WARN"
        # Build command string for direct redirection
        $argString = ($dumpArgs | ForEach-Object { "`"$_`"" }) -join " "
        $psi = New-Object System.Diagnostics.ProcessStartInfo
        $psi.FileName = $MysqldumpPath
        $psi.Arguments = $argString
        $psi.UseShellExecute = $false
        $psi.RedirectStandardOutput = $true
        $psi.RedirectStandardError = $true
        $psi.StandardOutputEncoding = [System.Text.Encoding]::UTF8
        $psi.StandardErrorEncoding = [System.Text.Encoding]::UTF8
        $proc = [System.Diagnostics.Process]::Start($psi)
        $stdout = $proc.StandardOutput.ReadToEnd()
        $stderr = $proc.StandardError.ReadToEnd()
        $proc.WaitForExit()
        if ($proc.ExitCode -ne 0) {
            throw "mysqldump (redirect mode) failed with exit code $($proc.ExitCode): $stderr"
        }
        [System.IO.File]::WriteAllText($SqlFile, $stdout, (New-Object System.Text.UTF8Encoding $false))
    }

    if (-not (Test-Path -LiteralPath $SqlFile) -or (Get-Item -LiteralPath $SqlFile).Length -eq 0) {
        throw "mysqldump produced empty file: $SqlFile"
    }

    $sqlSize = (Get-Item -LiteralPath $SqlFile).Length
    Write-Log "Database dump created: $SqlFileName ($sqlSize bytes)"

    # --- Compress to .sql.gz ---
    $compressed = $false
    # 1) Try gzip command if available
    if (Test-CommandExists "gzip") {
        try {
            Write-Log "Compressing with gzip..."
            # gzip -c input > output
            $gzipExe = (Get-Command gzip).Source
            $psi = New-Object System.Diagnostics.ProcessStartInfo
            $psi.FileName = $gzipExe
            $psi.Arguments = "-c `"$SqlFile`""
            $psi.UseShellExecute = $false
            $psi.RedirectStandardOutput = $true
            $psi.RedirectStandardError = $true
            $proc = [System.Diagnostics.Process]::Start($psi)
            # Read raw bytes
            $ms = New-Object System.IO.MemoryStream
            $proc.StandardOutput.BaseStream.CopyTo($ms)
            $proc.WaitForExit()
            $stderr = $proc.StandardError.ReadToEnd()
            if ($proc.ExitCode -eq 0) {
                [System.IO.File]::WriteAllBytes($GzFile, $ms.ToArray())
                $compressed = $true
                Write-Log "Compressed with gzip: $GzFileName"
            } else {
                Write-Log "gzip failed (exit $($proc.ExitCode)): $stderr" "WARN"
            }
        } catch {
            Write-Log "gzip compression failed: $($_.Exception.Message)" "WARN"
        }
    }

    # 2) Fallback to .NET GzipStream if not compressed
    if (-not $compressed) {
        try {
            Write-Log "Compressing with .NET GzipStream..."
            $inputStream = [System.IO.File]::OpenRead($SqlFile)
            $outputStream = [System.IO.File]::Create($GzFile)
            $gzipStream = New-Object System.IO.Compression.GzipStream($outputStream, [System.IO.Compression.CompressionMode]::Compress)
            $inputStream.CopyTo($gzipStream)
            $gzipStream.Close()
            $outputStream.Close()
            $inputStream.Close()
            $compressed = $true
            Write-Log "Compressed with GzipStream: $GzFileName"
        } catch {
            Write-Log "GzipStream compression failed: $($_.Exception.Message)" "WARN"
            # Cleanup partial gz file
            if (Test-Path -LiteralPath $GzFile) { Remove-Item -LiteralPath $GzFile -Force -ErrorAction SilentlyContinue }
        }
    }

    # 3) Fallback to Compress-Archive (creates .zip containing sql) if still not compressed
    if (-not $compressed) {
        try {
            Write-Log "Falling back to Compress-Archive..." "WARN"
            $zipFallback = Join-Path $BackupDirFull "${DBName}_${Timestamp}.zip"
            Compress-Archive -LiteralPath $SqlFile -DestinationPath $zipFallback -Force
            Write-Log "Compressed with Compress-Archive: $zipFallback (contains .sql)" "WARN"
            $compressed = $true
            # Remove sql file after zip fallback as well
            $GzFile = $zipFallback
        } catch {
            throw "All compression methods failed: $($_.Exception.Message)"
        }
    }

    # Remove original .sql if gz succeeded and file exists
    if ($compressed -and (Test-Path -LiteralPath $SqlFile) -and (Test-Path -LiteralPath $GzFile) -and $GzFile -ne $SqlFile) {
        # Only remove if gz is proper gz file (not zip fallback containing sql)
        if ($GzFile -like "*.gz") {
            $gzSize = (Get-Item -LiteralPath $GzFile).Length
            if ($gzSize -gt 0) {
                Remove-Item -LiteralPath $SqlFile -Force
                Write-Log "Removed uncompressed SQL file: $SqlFileName"
            } else {
                throw "Compressed file is empty: $GzFile"
            }
        } else {
            # zip fallback case - also remove sql? keep zip, remove sql
            Remove-Item -LiteralPath $SqlFile -Force -ErrorAction SilentlyContinue
        }
    }

    # --- Zip uploads folder ---
    $uploadsPath = Join-Path $ProjectRoot "uploads"
    if (Test-Path -LiteralPath $uploadsPath) {
        $equipmentPath = Join-Path $uploadsPath "equipment"
        $repairsPath = Join-Path $uploadsPath "repairs"
        $htaccessPath = Join-Path $uploadsPath ".htaccess"

        $filesToZip = @()
        if (Test-Path -LiteralPath $equipmentPath) { $filesToZip += $equipmentPath }
        if (Test-Path -LiteralPath $repairsPath) { $filesToZip += $repairsPath }
        if (Test-Path -LiteralPath $htaccessPath) { $filesToZip += $htaccessPath }

        if ($filesToZip.Count -gt 0) {
            Write-Log "Zipping uploads: $($filesToZip -join ', ') -> $ZipFileName"
            # Use Compress-Archive with update; handle existing
            if (Test-Path -LiteralPath $ZipFile) { Remove-Item -LiteralPath $ZipFile -Force }
            # Need to stage files to temp to preserve structure uploads/...
            # Simplest: Compress-Archive handles full paths; we want relative structure inside zip
            # We'll use .NET ZipArchive for better control or just Compress-Archive with -Path and handle
            try {
                Compress-Archive -Path $filesToZip -DestinationPath $ZipFile -Force
                $zipSize = (Get-Item -LiteralPath $ZipFile).Length
                Write-Log "Uploads zipped: $ZipFileName ($zipSize bytes)"
            } catch {
                Write-Log "Failed to zip uploads: $($_.Exception.Message)" "WARN"
            }
        } else {
            Write-Log "No uploads files found to zip (equipment/repairs/.htaccess missing)" "WARN"
        }
    } else {
        Write-Log "Uploads directory not found at $uploadsPath, skipping zip" "WARN"
    }

    # --- Retention Policy ---
    Write-Log "Applying retention policy: KeepDays=$KeepDays, KeepWeeklyDays=$KeepWeeklyDays"
    try {
        $now = Get-Date
        $allBackups = Get-ChildItem -LiteralPath $BackupDirFull -File | Where-Object {
            $_.Name -match '\.(sql\.gz|zip|sql)$' -and $_.Name -ne "backup.log" -and $_.Extension -ne ".log"
        }

        foreach ($file in $allBackups) {
            $ageDays = ($now - $file.LastWriteTime).TotalDays
            $isWeekly = $file.LastWriteTime.DayOfWeek -eq 'Monday'  # weekly keeps Monday backups
            # Alternative: also consider Sunday as weekly; treat Monday as weekly
            # If file older than KeepWeeklyDays, delete regardless
            if ($ageDays -gt $KeepWeeklyDays) {
                Write-Log "Deleting (exceeds KeepWeeklyDays=${KeepWeeklyDays}d, age=$([math]::Floor($ageDays))d): $($file.Name)" "WARN"
                Remove-Item -LiteralPath $file.FullName -Force
            } elseif ($ageDays -gt $KeepDays) {
                if ($isWeekly) {
                    Write-Log "Keeping weekly backup (age=$([math]::Floor($ageDays))d, weekly): $($file.Name)"
                } else {
                    Write-Log "Deleting (exceeds KeepDays=${KeepDays}d, age=$([math]::Floor($ageDays))d): $($file.Name)" "WARN"
                    Remove-Item -LiteralPath $file.FullName -Force
                }
            }
        }
    } catch {
        Write-Log "Retention cleanup failed: $($_.Exception.Message)" "WARN"
    }

    # Final success
    $gzExists = Test-Path -LiteralPath $GzFile
    $gzSizeFinal = if ($gzExists) { (Get-Item -LiteralPath $GzFile).Length } else { 0 }
    Write-Log "=== Backup completed successfully: $GzFileName ($gzSizeFinal bytes) ==="
    $ExitCode = 0

} catch {
    $err = $_.Exception.Message
    if ($_.ScriptStackTrace) { $err += " | Stack: $($_.ScriptStackTrace)" }
    Write-Log "Backup failed: $err" "ERROR"
    $ExitCode = 1
    # Cleanup partial files on failure
    if (Test-Path -LiteralPath $SqlFile) {
        # Keep sql if gz not created for debugging? But remove if empty
        try {
            if ((Get-Item -LiteralPath $SqlFile).Length -eq 0) { Remove-Item -LiteralPath $SqlFile -Force -ErrorAction SilentlyContinue }
        } catch {}
    }
}

exit $ExitCode
