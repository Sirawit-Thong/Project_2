#!/usr/bin/env bash
# สคริปต์สำรองข้อมูลสำหรับ Linux / Production (Bash)
# ใช้ mysqldump + tar + gzip + find -mtime
#
# Usage:
#   ./scripts/backup.sh [DB_NAME] [BACKUP_DIR] [KEEP_DAYS]
#   ./scripts/backup.sh equipment_db ./backups 7
#   DB_NAME=equipment_db BACKUP_DIR=./backups KEEP_DAYS=7 ./scripts/backup.sh
#
# Options mysqldump:
#   --default-character-set=utf8mb4 --single-transaction --routines --triggers --set-gtid-purged=OFF
#
# Retention:
#   - ลบไฟล์ daily เกิน KEEP_DAYS (default 7 วัน)
#   - เก็บ weekly (Monday) ไว้จนถึง KEEP_WEEKLY_DAYS (default 30 วัน) แล้วค่อยลบ
#   - Log ไป backups/backup.log

set -euo pipefail

# --- Parameters (support both positional and env) ---
DB_NAME="${1:-${DB_NAME:-equipment_db}}"
BACKUP_DIR="${2:-${BACKUP_DIR:-backups}}"
KEEP_DAYS="${3:-${KEEP_DAYS:-7}}"
KEEP_WEEKLY_DAYS="${KEEP_WEEKLY_DAYS:-30}"

# Optional env overrides for DB connection
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
MYSQLDUMP_PATH="${MYSQLDUMP_PATH:-mysqldump}"

# Resolve project root (one level above scripts/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# If BACKUP_DIR is relative, resolve against PROJECT_ROOT
if [[ "${BACKUP_DIR}" != /* ]]; then
    BACKUP_DIR_FULL="${PROJECT_ROOT}/${BACKUP_DIR}"
else
    BACKUP_DIR_FULL="${BACKUP_DIR}"
fi

TIMESTAMP="$(date +"%Y-%m-%d_%H%M%S")"
TIMESTAMP_LOG="$(date +"%Y-%m-%d %H:%M:%S")"
SQL_FILE="${BACKUP_DIR_FULL}/${DB_NAME}_${TIMESTAMP}.sql"
GZ_FILE="${SQL_FILE}.gz"
ZIP_FILE="${BACKUP_DIR_FULL}/uploads_${TIMESTAMP}.zip"
TAR_FILE="${BACKUP_DIR_FULL}/uploads_${TIMESTAMP}.tar.gz"
LOG_FILE="${BACKUP_DIR_FULL}/backup.log"

EXIT_CODE=0

log() {
    local level="${2:-INFO}"
    local msg="[$TIMESTAMP_LOG] [$level] $1"
    mkdir -p "$(dirname "${LOG_FILE}")" 2>/dev/null || true
    echo "${msg}" | tee -a "${LOG_FILE}" 2>/dev/null || echo "${msg}"
    # also color to stderr for errors
    if [[ "${level}" == "ERROR" ]]; then
        echo "${msg}" >&2
    fi
}

log "=== Backup started: DB=${DB_NAME} Host=${DB_HOST} BackupDir=${BACKUP_DIR_FULL} KeepDays=${KEEP_DAYS} ==="

# Ensure backup directory exists
mkdir -p "${BACKUP_DIR_FULL}"

# Validate mysqldump
if ! command -v "${MYSQLDUMP_PATH}" >/dev/null 2>&1; then
    if ! command -v mysqldump >/dev/null 2>&1; then
        log "mysqldump not found at '${MYSQLDUMP_PATH}' and not in PATH" "ERROR"
        exit 1
    else
        MYSQLDUMP_PATH="mysqldump"
    fi
fi

# Build mysqldump command
# Use MYSQL_PWD to avoid password in process list if DB_PASS provided
DUMP_CMD=(
    "${MYSQLDUMP_PATH}"
    "--host=${DB_HOST}"
    "--user=${DB_USER}"
    "--default-character-set=utf8mb4"
    "--single-transaction"
    "--routines"
    "--triggers"
    "--set-gtid-purged=OFF"
    "${DB_NAME}"
)

if [[ -n "${DB_PASS}" ]]; then
    export MYSQL_PWD="${DB_PASS}"
fi

# --- Dump database ---
log "Running mysqldump for database '${DB_NAME}'..."
set +e
"${DUMP_CMD[@]}" > "${SQL_FILE}" 2> "${BACKUP_DIR_FULL}/.mysqldump.err"
DUMP_EXIT=$?
set -e

if [[ ${DUMP_EXIT} -ne 0 ]]; then
    ERR_MSG="$(cat "${BACKUP_DIR_FULL}/.mysqldump.err" 2>/dev/null || echo "unknown error")"
    log "mysqldump failed with exit code ${DUMP_EXIT}: ${ERR_MSG}" "ERROR"
    rm -f "${SQL_FILE}" "${BACKUP_DIR_FULL}/.mysqldump.err"
    exit 1
fi
rm -f "${BACKUP_DIR_FULL}/.mysqldump.err"

if [[ ! -s "${SQL_FILE}" ]]; then
    log "mysqldump produced empty file: ${SQL_FILE}" "ERROR"
    rm -f "${SQL_FILE}"
    exit 1
fi

SQL_SIZE=$(stat -c%s "${SQL_FILE}" 2>/dev/null || stat -f%z "${SQL_FILE}" 2>/dev/null || wc -c < "${SQL_FILE}")
log "Database dump created: $(basename "${SQL_FILE}") (${SQL_SIZE} bytes)"

# --- Compress to .sql.gz ---
COMPRESSED=false
if command -v gzip >/dev/null 2>&1; then
    log "Compressing with gzip..."
    if gzip -c "${SQL_FILE}" > "${GZ_FILE}"; then
        COMPRESSED=true
        log "Compressed with gzip: $(basename "${GZ_FILE}")"
        rm -f "${SQL_FILE}"
    else
        log "gzip compression failed" "WARN"
        rm -f "${GZ_FILE}"
    fi
fi

if [[ "${COMPRESSED}" != "true" ]]; then
    # Fallback: try tar gzip or python
    log "gzip not available or failed, trying alternative compression..." "WARN"
    if command -v tar >/dev/null 2>&1; then
        if tar -czf "${GZ_FILE}" -C "$(dirname "${SQL_FILE}")" "$(basename "${SQL_FILE}")" 2>/dev/null; then
            # tar creates tar.gz; we want plain gz - extract logic fallback
            # For compatibility, keep tar.gz as valid backup
            log "Compressed with tar: $(basename "${GZ_FILE}")" "WARN"
            rm -f "${SQL_FILE}"
            COMPRESSED=true
        fi
    fi
fi

if [[ "${COMPRESSED}" != "true" ]]; then
    log "All compression methods failed" "ERROR"
    exit 1
fi

if [[ ! -s "${GZ_FILE}" ]]; then
    log "Compressed file is empty: ${GZ_FILE}" "ERROR"
    exit 1
fi

GZ_SIZE=$(stat -c%s "${GZ_FILE}" 2>/dev/null || stat -f%z "${GZ_FILE}" 2>/dev/null || wc -c < "${GZ_FILE}")
log "Compression complete: $(basename "${GZ_FILE}") (${GZ_SIZE} bytes)"

# --- Zip uploads folder ---
UPLOADS_PATH="${PROJECT_ROOT}/uploads"
if [[ -d "${UPLOADS_PATH}" ]]; then
    log "Archiving uploads..."
    # Prefer zip, fallback to tar.gz
    if command -v zip >/dev/null 2>&1; then
        # zip uploads/equipment + uploads/repairs + uploads/.htaccess
        # -r recursive, handle missing files gracefully
        FILES_TO_ZIP=()
        [[ -d "${UPLOADS_PATH}/equipment" ]] && FILES_TO_ZIP+=("uploads/equipment")
        [[ -d "${UPLOADS_PATH}/repairs" ]] && FILES_TO_ZIP+=("uploads/repairs")
        [[ -f "${UPLOADS_PATH}/.htaccess" ]] && FILES_TO_ZIP+=("uploads/.htaccess")

        if [[ ${#FILES_TO_ZIP[@]} -gt 0 ]]; then
            (cd "${PROJECT_ROOT}" && zip -r "${ZIP_FILE}" "${FILES_TO_ZIP[@]}" >> "${LOG_FILE}" 2>&1) || {
                log "Failed to zip uploads with zip command" "WARN"
                rm -f "${ZIP_FILE}"
            }
            if [[ -s "${ZIP_FILE}" ]]; then
                ZIP_SIZE=$(stat -c%s "${ZIP_FILE}" 2>/dev/null || stat -f%z "${ZIP_FILE}" 2>/dev/null || wc -c < "${ZIP_FILE}")
                log "Uploads zipped: $(basename "${ZIP_FILE}") (${ZIP_SIZE} bytes)"
            fi
        else
            log "No uploads files found to zip" "WARN"
        fi
    elif command -v tar >/dev/null 2>&1; then
        log "zip not found, using tar..." "WARN"
        FILES_TO_TAR=()
        [[ -d "${UPLOADS_PATH}/equipment" ]] && FILES_TO_TAR+=("uploads/equipment")
        [[ -d "${UPLOADS_PATH}/repairs" ]] && FILES_TO_TAR+=("uploads/repairs")
        [[ -f "${UPLOADS_PATH}/.htaccess" ]] && FILES_TO_TAR+=("uploads/.htaccess")
        if [[ ${#FILES_TO_TAR[@]} -gt 0 ]]; then
            (cd "${PROJECT_ROOT}" && tar -czf "${TAR_FILE}" "${FILES_TO_TAR[@]}" 2>> "${LOG_FILE}") || {
                log "Failed to tar uploads" "WARN"
                rm -f "${TAR_FILE}"
            }
            if [[ -s "${TAR_FILE}" ]]; then
                TAR_SIZE=$(stat -c%s "${TAR_FILE}" 2>/dev/null || stat -f%z "${TAR_FILE}" 2>/dev/null || wc -c < "${TAR_FILE}")
                log "Uploads archived with tar: $(basename "${TAR_FILE}") (${TAR_SIZE} bytes)"
            fi
        fi
    else
        log "Neither zip nor tar found, skipping uploads archive" "WARN"
    fi
else
    log "Uploads directory not found at ${UPLOADS_PATH}, skipping" "WARN"
fi

# --- Retention Policy ---
log "Applying retention policy: KeepDays=${KEEP_DAYS}, KeepWeeklyDays=${KEEP_WEEKLY_DAYS}"

# 1) Delete daily files older than KEEP_DAYS, but keep Monday (weekly) files up to KEEP_WEEKLY_DAYS
# Use find for base deletion, then refine

# Find files older than KEEP_DAYS
# We list candidates and decide per file
if [[ -d "${BACKUP_DIR_FULL}" ]]; then
    # For each backup file older than KEEP_DAYS
    while IFS= read -r -d '' file; do
        # Get file mtime day-of-week (1=Monday ... 7=Sunday)
        if date -d "@$(stat -c %Y "$file" 2>/dev/null || stat -f %m "$file" 2>/dev/null)" +"%u" >/dev/null 2>&1; then
            # GNU date
            DOW=$(date -d "@$(stat -c %Y "$file" 2>/dev/null || stat -f %m "$file" 2>/dev/null)" +"%u")
        else
            # BSD fallback - use stat + date -r
            DOW=$(date -r "$(stat -f %m "$file" 2>/dev/null || stat -c %Y "$file")" +"%u" 2>/dev/null || echo "0")
        fi

        # Calculate age in days
        MTIME=$(stat -c %Y "$file" 2>/dev/null || stat -f %m "$file" 2>/dev/null || echo "0")
        NOW=$(date +%s)
        AGE_DAYS=$(( (NOW - MTIME) / 86400 ))

        IS_WEEKLY=false
        # Monday = 1 is weekly
        if [[ "${DOW}" == "1" ]]; then
            IS_WEEKLY=true
        fi

        if [[ ${AGE_DAYS} -gt ${KEEP_WEEKLY_DAYS} ]]; then
            log "Deleting (exceeds KeepWeeklyDays=${KEEP_WEEKLY_DAYS}d, age=${AGE_DAYS}d): $(basename "$file")" "WARN"
            rm -f "$file"
        elif [[ ${AGE_DAYS} -gt ${KEEP_DAYS} ]]; then
            if [[ "${IS_WEEKLY}" == "true" ]]; then
                log "Keeping weekly backup (age=${AGE_DAYS}d, weekly): $(basename "$file")"
            else
                log "Deleting (exceeds KeepDays=${KEEP_DAYS}d, age=${AGE_DAYS}d): $(basename "$file")" "WARN"
                rm -f "$file"
            fi
        fi
    done < <(find "${BACKUP_DIR_FULL}" -maxdepth 1 -type f \( -name "*.sql.gz" -o -name "*.sql" -o -name "*.zip" -o -name "uploads_*.tar.gz" \) -mtime +${KEEP_DAYS} -print0 2>/dev/null || true)

    # 2) Strict: delete anything older than KEEP_WEEKLY_DAYS regardless of weekly status (already handled) + also catch files that find missed due to edge
    while IFS= read -r -d '' file; do
        MTIME=$(stat -c %Y "$file" 2>/dev/null || stat -f %m "$file" 2>/dev/null || echo "0")
        NOW=$(date +%s)
        AGE_DAYS=$(( (NOW - MTIME) / 86400 ))
        if [[ ${AGE_DAYS} -gt ${KEEP_WEEKLY_DAYS} ]]; then
            log "Deleting (strict weekly expiry, age=${AGE_DAYS}d): $(basename "$file")" "WARN"
            rm -f "$file"
        fi
    done < <(find "${BACKUP_DIR_FULL}" -maxdepth 1 -type f \( -name "*.sql.gz" -o -name "*.sql" -o -name "*.zip" -o -name "uploads_*.tar.gz" \) -mtime +${KEEP_WEEKLY_DAYS} -print0 2>/dev/null || true)
fi

log "=== Backup completed successfully: $(basename "${GZ_FILE}") (${GZ_SIZE} bytes) ==="
exit 0
