#!/bin/bash
# sync-from-prod.sh — Tarik data dari server produksi ke localhost
# Usage: bash sync-from-prod.sh [db|files|all]
#
# Konfigurasi via environment variable (diset di .env atau export):
#   SYNC_SSH_HOST, SYNC_SSH_USER, SYNC_SSH_PORT, SYNC_SSH_KEY_PATH
#   SYNC_REMOTE_PATH, SYNC_REMOTE_DB, SYNC_REMOTE_DB_USER, SYNC_REMOTE_DB_PASSWORD
#   SYNC_REMOTE_DB_CONNECTION (mysql|pgsql)
#   DB_DATABASE, DB_USERNAME, DB_PASSWORD (local database)

set -e

SYNC_TYPE="${1:-all}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SYNC_DIR="${SCRIPT_DIR}/storage/app/sync"

echo "============================================"
echo "  Sinkronisasi Data dari Produksi"
echo "  Tipe: ${SYNC_TYPE}"
echo "  Waktu: $(date)"
echo "============================================"
echo ""

# Validasi konfigurasi SSH
if [[ -z "${SYNC_SSH_HOST}" || -z "${SYNC_SSH_USER}" ]]; then
    echo "❌ ERROR: Konfigurasi SSH tidak lengkap."
    echo "   Pastikan SYNC_SSH_HOST dan SYNC_SSH_USER sudah diisi di .env"
    exit 1
fi

SSH_CMD="ssh -p ${SYNC_SSH_PORT:-22}"
if [[ -n "${SYNC_SSH_KEY_PATH}" ]]; then
    SSH_CMD="${SSH_CMD} -i ${SYNC_SSH_KEY_PATH}"
fi
SSH_DEST="${SYNC_SSH_USER}@${SYNC_SSH_HOST}"
SSH_FULL="${SSH_CMD} ${SSH_DEST}"

echo "🔌 Menguji koneksi SSH ke ${SSH_DEST}..."
if ! ${SSH_FULL} "echo OK" 2>/dev/null; then
    echo "❌ Gagal terkoneksi ke server produksi."
    echo "   Periksa: host, user, port, dan SSH key."
    exit 1
fi
echo "✅ Koneksi SSH berhasil!"
echo ""

mkdir -p "${SYNC_DIR}"

# ──────────────────────────────────────────────
# 1. Sinkronisasi Database
# ──────────────────────────────────────────────
sync_db() {
    echo "📦 Sinkronisasi Database..."
    REMOTE_DB_CONN="${SYNC_REMOTE_DB_CONNECTION:-mysql}"
    REMOTE_DB_NAME="${SYNC_REMOTE_DB}"
    REMOTE_DB_USER="${SYNC_REMOTE_DB_USER}"
    REMOTE_DB_PASS="${SYNC_REMOTE_DB_PASSWORD}"

    if [[ -z "${REMOTE_DB_NAME}" || -z "${REMOTE_DB_USER}" ]]; then
        echo "⚠️  Konfigurasi database remote tidak lengkap. Lewati sinkronisasi DB."
        return 1
    fi

    DUMP_FILE="${SYNC_DIR}/${REMOTE_DB_NAME}_${TIMESTAMP}.sql"
    COMPRESSED_FILE="${DUMP_FILE}.gz"

    echo "   Mengekspor database ${REMOTE_DB_NAME} dari produksi..."

    if [[ "${REMOTE_DB_CONN}" == "pgsql" ]]; then
        DUMP_CMD="PGPASSWORD='${REMOTE_DB_PASS}' pg_dump -U ${REMOTE_DB_USER} -h localhost ${REMOTE_DB_NAME} --no-owner"
    else
        if [[ -n "${REMOTE_DB_PASS}" ]]; then
            DUMP_CMD="mysqldump -u ${REMOTE_DB_USER} -p'${REMOTE_DB_PASS}' ${REMOTE_DB_NAME}"
        else
            DUMP_CMD="mysqldump -u ${REMOTE_DB_USER} ${REMOTE_DB_NAME}"
        fi
    fi

    echo "   Mendownload dump file..."
    ${SSH_FULL} "${DUMP_CMD}" | gzip > "${COMPRESSED_FILE}"

    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        echo "✅ Database berhasil diunduh: ${COMPRESSED_FILE}"
        ls -lh "${COMPRESSED_FILE}"

        # Coba import ke database lokal jika tipe DB sama
        LOCAL_DB_CONN="${DB_CONNECTION:-pgsql}"
        if [[ "${LOCAL_DB_CONN}" == "${REMOTE_DB_CONN}" ]]; then
            echo "   Mengimpor ke database lokal (${LOCAL_DB_CONN})..."
            LOCAL_DB_NAME="${DB_DATABASE:-sim_lppm}"
            LOCAL_DB_USER="${DB_USERNAME:-postgres}"
            LOCAL_DB_PASS="${DB_PASSWORD:-}"

            if [[ "${LOCAL_DB_CONN}" == "pgsql" ]]; then
                gunzip -c "${COMPRESSED_FILE}" | PGPASSWORD="${LOCAL_DB_PASS}" psql -U "${LOCAL_DB_USER}" -h localhost "${LOCAL_DB_NAME}" 2>&1
            else
                gunzip -c "${COMPRESSED_FILE}" | mysql -u "${LOCAL_DB_USER}" "${LOCAL_DB_NAME}" 2>&1
            fi

            if [[ $? -eq 0 ]]; then
                echo "✅ Database lokal berhasil diperbarui!"
            else
                echo "⚠️  Gagal mengimpor ke database lokal. Dump tersimpan di: ${COMPRESSED_FILE}"
            fi
        else
            echo "⚠️  Tipe DB lokal (${LOCAL_DB_CONN}) ≠ remote (${REMOTE_DB_CONN})."
            echo "   Dump tersimpan di: ${COMPRESSED_FILE}. Import manual diperlukan."
        fi
    else
        echo "❌ Gagal mengekspor database dari produksi."
        return 1
    fi
}

# ──────────────────────────────────────────────
# 2. Sinkronisasi Storage (File Upload)
# ──────────────────────────────────────────────
sync_files() {
    echo ""
    echo "📁 Sinkronisasi File Storage..."

    if [[ -z "${REMOTE_PATH}" ]]; then
        echo "⚠️  SYNC_REMOTE_PATH tidak dikonfigurasi. Lewati sinkronisasi file."
        return 1
    fi

    LOCAL_STORAGE="${SCRIPT_DIR}/storage/app"
    REMOTE_STORAGE="${REMOTE_PATH}/storage/app"

    RSYNC_ARGS=(-avz --progress)
    SSH_ARGS=(-p "${SYNC_SSH_PORT:-22}")
    if [[ -n "${SYNC_SSH_KEY_PATH}" ]]; then
        SSH_ARGS+=(-i "${SYNC_SSH_KEY_PATH}")
    fi
    RSYNC_ARGS+=(-e "ssh ${SSH_ARGS[*]}")
    RSYNC_ARGS+=("${SYNC_SSH_USER}@${SYNC_SSH_HOST}:${REMOTE_STORAGE}/" "${LOCAL_STORAGE}/")

    echo "   Menyalin file dari produksi..."
    rsync "${RSYNC_ARGS[@]}"

    if [[ $? -eq 0 ]]; then
        echo "✅ File storage berhasil disinkronkan!"
    else
        echo "❌ Gagal sinkronisasi file storage."
        return 1
    fi
}

# ──────────────────────────────────────────────
# 3. Bersihkan Cache Lokal
# ──────────────────────────────────────────────
clear_cache() {
    echo ""
    echo "🧹 Membersihkan cache lokal..."
    php artisan optimize:clear 2>/dev/null || true
    echo "✅ Cache lokal dibersihkan."
}

# ──────────────────────────────────────────────
# Eksekusi berdasarkan tipe
# ──────────────────────────────────────────────
case "${SYNC_TYPE}" in
    db)
        sync_db
        ;;
    files)
        sync_files
        ;;
    all)
        sync_db || true
        sync_files || true
        clear_cache
        ;;
    *)
        echo "Penggunaan: bash sync-from-prod.sh [db|files|all]"
        exit 1
        ;;
esac

echo ""
echo "============================================"
echo "  ✅ Sinkronisasi selesai!"
echo "  Waktu: $(date)"
echo "============================================"
