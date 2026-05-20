# Panduan Deploy ke cPanel

## Prasyarat
- cPanel hosting dengan PHP 8.1+
- MySQL database
- Akses SSH atau File Manager

---

## Langkah 1: Persiapkan Frontend (Sudah Selesai)
✅ Frontend sudah di-build ke folder `frontend/dist/`

File yang perlu diupload:
- Semua file di `frontend/dist/`

---

## Langkah 2: Persiapkan Backend

### 2.1. Update .env untuk Production
Buat file `.env` baru di cPanel dengan konfigurasi:

```env
APP_NAME="SIPINTAR"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainanda.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_db
DB_USERNAME=username_db
DB_PASSWORD=password_db

SESSION_DRIVER=file
SESSION_LIFETIME=120

FILESYSTEM_DISK=local
```

### 2.2. File Backend yang Perlu Diupload
Upload SEMUA file backend KECUALI:
- `node_modules/`
- `.git/`
- `storage/` (buat baru di server)
- `vendor/` (akan di-install di server)

### 2.3. Set Permission Storage
Di cPanel terminal atau file manager:
```bash
cd storage
mkdir -p framework/cache framework/sessions framework/views
mkdir -p app/public
chmod -R 775 storage bootstrap/cache
```

---

## Langkah 3: Struktur Folder di cPanel

```
public_html/
├── api/                    # Backend Laravel
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/             # → ini jadi document root
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   └── composer.json
└── (isi frontend dist/)    # Frontend Vue
    ├── index.html
    └── assets/
```

---

## Langkah 4: Setup Database

### 4.1. Buat Database di cPanel
1. Buka MySQL® Databases
2. Buat database baru
3. Buat user database
4. Hubungkan user ke database dengan ALL PRIVILEGES

### 4.2. Import Database
```bash
# Export dari local
mysqldump -u root -p sipintar > sipintar.sql

# Import di cPanel (via SSH)
mysql -u username_db -p nama_database_db < sipintar.sql
```

Atau gunakan phpMyAdmin untuk import.

---

## Langkah 5: Install Dependencies & Setup

### 5.1. Install Composer Dependencies
Via SSH:
```bash
cd public_html/api
composer install --optimize-autoloader --no-dev
```

### 5.2. Generate Application Key
```bash
php artisan key:generate
```

### 5.3. Run Migration
```bash
php artisan migrate --force
```

### 5.4. Storage Link
```bash
php artisan storage:link
```

### 5.5. Cache Config
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Langkah 6: Konfigurasi Apache/Litespeed

### 6.1. Point Subdomain ke Backend
Buat subdomain `api.domainanda.com` yang point ke:
`public_html/api/public`

### 6.2. Update Frontend API URL
Edit `dist/assets/index-*.js` (cari dan ganti localhost dengan domain asli):

**Lebih baik:** Build ulang dengan URL production:
```bash
cd frontend
echo "VITE_API_URL=https://api.domainanda.com/api" > .env.production
npm run build
```

---

## Langkah 7: Testing

1. Buka `https://domainanda.com` → Frontend harus muncul
2. Buka `https://api.domainanda.com/api/health` → API harus merespon
3. Coba login → Harus berhasil

---

## Troubleshooting

### Error 500
- Cek `storage/logs/laravel.log`
- Pastikan permission storage benar (775)
- Pastikan `.env` sudah terisi dengan benar

### CORS Error
Tambahkan di `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['https://domainanda.com'],
```

### File Upload Gagal
- Pastikan folder `storage/app/public` ada dan writable
- Cek PHP upload_max_filesize di cPanel

---

## Tips Keamanan

1. **Jangan upload** `.env.local` atau file development
2. **Set APP_DEBUG=false** di production
3. **Gunakan HTTPS** (SSL certificate)
4. **Protect folder** `storage/` dan `bootstrap/cache/`
5. **Rotate APP_KEY** secara berkala
