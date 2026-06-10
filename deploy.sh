#!/usr/bin/env bash
# Secure deploy script for SIM-LPPM Cloud Run
# Vetted by AI - Manual Review Required by Senior Engineer/Manager

set -uo pipefail

COMMIT_MSG=${1:-"Update deploy"}
SEED_FLAG=${2:-""}

echo "🧹 Menyiapkan kode untuk deployment..."

# 1. Commit lokal
git add .
git commit -m "$COMMIT_MSG" || echo "No changes to commit."

# 2. Push ke GitHub (Tidak fatal jika gagal karena masalah token)
echo "🚀 Pushing to remote (GitHub)..."
git push origin HEAD || echo "⚠️ Warning: Git push failed. Proceeding with Google Cloud Run deployment anyway..."

# 3. Konfigurasi Deployment
REGION="asia-southeast2"
SERVICE_NAME="sim-lppm"
CLOUDSQL_CONN="gen-lang-client-0704185463:asia-southeast2:sim-lppm-db"

# Tentukan flag seeding
if [[ "$SEED_FLAG" == "--seed" ]]; then
  DB_SEED=true
else
  DB_SEED=false
fi

echo "🚢 Mendeploy ke Google Cloud Run (Region: $REGION)..."

# Menggunakan --set-secrets untuk keamanan tinggi
SECRETS="APP_KEY=SIM_LPPM_APP_KEY:latest"
SECRETS+=",DB_PASSWORD=SIM_LPPM_DB_PASSWORD:latest"
SECRETS+=",INITIAL_ADMIN_PASSWORD=SIM_LPPM_INITIAL_ADMIN_PASSWORD:latest"

# Variabel non-sensitif di Env Vars
ENV_VARS="APP_DEBUG=false"
ENV_VARS+=",APP_ENV=production"
ENV_VARS+=",APP_URL=https://sim-lppm.itsnupekalongan.ac.id"
ENV_VARS+=",DB_CONNECTION=mysql"
ENV_VARS+=",DB_SOCKET=/cloudsql/$CLOUDSQL_CONN"
ENV_VARS+=",DB_DATABASE=sim_lppm"
ENV_VARS+=",DB_USERNAME=root"
ENV_VARS+=",DB_SEED=$DB_SEED"

# Jalankan Deployment
gcloud run deploy $SERVICE_NAME \
  --source . \
  --region $REGION \
  --set-env-vars "$ENV_VARS" \
  --set-secrets "$SECRETS" \
  --add-cloudsql-instances "$CLOUDSQL_CONN" \
  --allow-unauthenticated \
  --quiet

echo "✅ Deployment selesai! Silakan cek dashboard Cloud Run Anda."
