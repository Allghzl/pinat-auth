# 🐳 Docker Setup - PinatAuth

Dokumentasi untuk menjalankan PinatAuth menggunakan Docker.

---

## 🚀 Quick Start

### 1. Setup Environment

```bash
cd C:\web\pinat-auth
cp .env.example .env
```

Edit `.env` dan sesuaikan dengan setup kamu:

```env
# Generate APP_KEY (lihat step 2)
APP_KEY=base64:xxxxx

# PostgreSQL - sesuaikan dengan setup kamu
DB_HOST=host.docker.internal
DB_PORT=5432
DB_DATABASE=pinat_auth
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 2. Generate Application Key

```bash
docker-compose run --rm pinat-auth php artisan key:generate --show
```

Copy hasilnya ke `.env`:
```env
APP_KEY=base64:hasil_dari_command_diatas
```

### 3. Buat Database

Buat database `pinat_auth` di PostgreSQL:

```sql
CREATE DATABASE pinat_auth;
```

### 4. Build & Run

```bash
# Build image
docker-compose build

# Jalankan container
docker-compose up -d

# Lihat logs
docker-compose logs -f
```

### 5. Run Migrations

```bash
docker-compose exec pinat-auth php artisan migrate
```

### 6. Generate JWT Keys

```bash
docker-compose exec pinat-auth php artisan jwt:keys
```

Keys akan disimpan di `storage/jwt/`:
- `private.key` - untuk signing JWT
- `public.key` - untuk verifikasi JWT

### 7. Buat Service Credentials

```bash
docker-compose exec pinat-auth php artisan service:create
```

Pilih:
- **Service Name**: nama service yang akan menggunakan PinatAuth
- **Bucket**: pilih bucket (drive, photos, music, avatars, temp)
- **Scopes**: pilih scopes yang dibutuhkan

Simpan **client_id** dan **client_secret** yang dihasilkan.

---

## 🔧 Konfigurasi PostgreSQL

### Windows / Mac

Gunakan `host.docker.internal`:
```env
DB_HOST=host.docker.internal
```

### Linux

Gunakan IP gateway Docker:
```env
DB_HOST=172.17.0.1
```

Atau cari IP gateway:
```bash
docker network inspect bridge | grep Gateway
```

### PostgreSQL di Container Lain

Gunakan nama container:
```env
DB_HOST=postgres-container-name
```

### PostgreSQL di Server Lain

Gunakan IP atau hostname:
```env
DB_HOST=192.168.1.100
```

---

## 📦 Docker Commands

### Container Management

```bash
# Start service
docker-compose up -d

# Stop service
docker-compose down

# Restart service
docker-compose restart

# Lihat logs
docker-compose logs -f

# Lihat status
docker-compose ps
```

### Rebuild Image

```bash
# Rebuild image
docker-compose build

# Rebuild tanpa cache
docker-compose build --no-cache
```

### Artisan Commands

```bash
# Run artisan command
docker-compose exec pinat-auth php artisan <command>

# Contoh:
docker-compose exec pinat-auth php artisan migrate:status
docker-compose exec pinat-auth php artisan route:list
docker-compose exec pinat-auth php artisan service:list
```

### Shell Access

```bash
# Masuk ke container
docker-compose exec pinat-auth sh

# Atau bash (jika tersedia)
docker-compose exec pinat-auth bash
```

---

## 🌐 Akses Service

- **URL**: http://localhost:5000
- **Health Check**: http://localhost:5000/api/health
- **JWKS Endpoint**: http://localhost:5000/.well-known/jwks.json

---

## 🐛 Troubleshooting

### Container Tidak Bisa Connect ke PostgreSQL

1. **Pastikan PostgreSQL listen ke semua interface**:
   ```conf
   # postgresql.conf
   listen_addresses = '*'
   ```

2. **Tambahkan rule di pg_hba.conf**:
   ```conf
   host    all    all    172.17.0.0/16    md5
   ```

3. **Restart PostgreSQL**

### Error "Permission Denied" di Storage

```bash
docker-compose exec pinat-auth chown -R www-data:www-data /var/www/html/storage
```

### JWT Keys Tidak Ter-generate

Pastikan folder storage/jwt ada:
```bash
docker-compose exec pinat-auth mkdir -p storage/jwt
docker-compose exec pinat-auth php artisan jwt:keys
```

### Container Crash/Restart Terus

Check logs:
```bash
docker-compose logs pinat-auth
```

---

## 📁 Volume Mapping

Container memiliki volume mapping:
- `./storage` → `/var/www/html/storage` (JWT keys, logs, cache)
- `./bootstrap/cache` → `/var/www/html/bootstrap/cache` (Laravel cache)

Data akan persist meskipun container di-restart atau di-rebuild.

---

## 🔐 Security Notes

- **JANGAN commit file `.env`**
- Simpan `client_secret` dengan aman
- JWT private key harus tetap rahasia
- Gunakan strong password untuk database

---

## ✅ Checklist

- [ ] PostgreSQL sudah running
- [ ] Database `pinat_auth` sudah dibuat
- [ ] File `.env` sudah dikonfigurasi
- [ ] APP_KEY sudah di-generate
- [ ] Image sudah di-build
- [ ] Container sudah running
- [ ] Migrations sudah dijalankan
- [ ] JWT keys sudah di-generate
- [ ] Service credentials sudah dibuat
- [ ] Health check endpoint bisa diakses

---

Jika ada masalah, check logs atau lihat troubleshooting section.
