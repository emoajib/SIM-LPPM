# Observability & Monitoring Strategy

Strategi observabilitas untuk SIM-LPPM ITSNU menggunakan pendekatan Tiga Pilar: Metrics, Logs, dan Traces.

## 1. Metrics (Real-time Health)
- **Tool**: Prometheus & Grafana.
- **Key Metrics**:
    - HTTP Request Rate (RPS).
    - Error Rate (4xx, 5xx).
    - Latency (P95, P99).
    - DB Connection Pool Health.

## 2. Logs (Incident Investigation)
- **Tool**: Laravel Pail & ELK Stack (atau CloudWatch/Datadog).
- **Log Levels**:
    - `EMERGENCY`: Kegagalan infrastruktur total.
    - `CRITICAL`: Masalah keamanan (Zero Trust Breach).
    - `ERROR`: Bug fungsional yang dideteksi oleh aplikasi.

## 3. Tracing (Performance Bottlenecks)
- **Tool**: Laravel Telescope (Staging) & Sentry (Production).
- **Focus**:
    - Lambatnya query database pada laporan ekspor.
    - Eksekusi Queue Job yang tertunda.

## Health Check Endpoints
Semua lingkungan wajib mengekspos endpoint `/api/health` yang divalidasi oleh Pipeline:
- `DB_STATUS`: OK
- `REDIS_STATUS`: OK
- `STORAGE_STATUS`: WRITABLE

---
*Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager*
*Architectural Alignment: TOGAF (Technology Architecture)*
