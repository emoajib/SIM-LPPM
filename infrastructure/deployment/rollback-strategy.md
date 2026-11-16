# Secure Rollback & Deployment Strategy

Strategi ini memastikan ketersediaan tinggi (High Availability) dan integritas data selama proses rilis.

## 1. Blue-Green Deployment
- **Green Environment**: Versi baru yang sedang dideploy.
- **Blue Environment**: Versi stabil yang sedang melayani traffic.
- **Switching**: Lalu lintas dialihkan ke Green hanya setelah Health Check 100% berhasil.

## 2. Automatic Rollback (Pipeline Level)
Jika tahap `Post-Deploy Verification` gagal di Pipeline GitHub Actions:
1. Pipeline secara otomatis memicu script rollback.
2. Tag Docker `:latest` dikembalikan ke Image SHA yang stabil sebelumnya.
3. Notifikasi dikirim melalui Slack/Team channel Direksi.

## 3. Persistent Data Safety
- **Migrations**: Gunakan migrasi yang *backward-compatible*. Hindari penghapusan kolom secara langsung (drop column) sebelum rilis N+1.
- **Backup**: Snapshot database dilakukan otomatis tepat sebelum rilis ke Production dilakukan (`Pre-release Snapshot`).

## 4. Environment Parity
Untuk meminimalkan kejutan:
- Gambar Docker yang sama (`SHA`) digunakan di Staging dan Production.
- Konfigurasi infrastruktur (PHP-FPM, Nginx, Redis) dikelola melalui file Dockerfile yang seragam.

---
*Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager*
*Architectural Alignment: Zero Trust & TOGAF Principles*
