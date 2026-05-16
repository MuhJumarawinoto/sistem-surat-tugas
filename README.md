# Sistem Surat Izin Belajar Mandiri (SIPINTAR)

Sistem informasi pengelolaan izin belajar mandiri bagi PNS di lingkungan Pemerintah Kabupaten Sukabumi.

## Tech Stack

- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **Backend**: Laravel 11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL
- wkhtmltopdf (for PDF generation)

## Installation

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy .env and configure
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=sipintar
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed database (optional, for demo data)
php artisan db:seed

# Start development server
php artisan serve
```

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

## Demo Accounts

Setelah menjalankan seeder, gunakan akun berikut untuk login:

| Role | Email | Password |
|------|-------|----------|
| Pemohon | drajat@disdik.go.id | password |
| Atasan | kadisdik@disdik.go.id | password |
| Admin BKPSDM | admin@bkpsdm.go.id | password |
| Kepala BKPSDM | kepala@bkpsdm.go.id | password |

## Features

### Pemohon (PNS)
- Dashboard pengajuan
- Buat pengajuan izin belajar baru
- Upload dokumen persyaratan
- Tracking status pengajuan
- Download surat tugas

### Atasan Langsung
- Review dan approval pengajuan dari bawahan
- Lihat detail pengajuan

### Admin BKPSDM
- Verifikasi kelengkapan dokumen
- Approval pengajuan
- Generate surat tugas (PDF)

### Kepala BKPSDM
- Tanda tangan elektronik surat tugas
- Review dan approve surat

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Pengajuan
- `GET /api/pengajuan` - List pengajuan
- `POST /api/pengajuan` - Create pengajuan
- `GET /api/pengajuan/{id}` - Detail pengajuan
- `PUT /api/pengajuan/{id}` - Update pengajuan
- `DELETE /api/pengajuan/{id}` - Delete pengajuan
- `POST /api/pengajuan/{id}/submit` - Submit ke atasan

### Dokumen
- `POST /api/pengajuan/{id}/dokumen` - Upload dokumen
- `GET /api/pengajuan/{id}/dokumen` - List dokumen
- `DELETE /api/dokumen/{id}` - Delete dokumen

### Approval
- `POST /api/pengajuan/{id}/approve-atasan` - Approval atasan
- `POST /api/pengajuan/{id}/approve-admin` - Approval admin
- `POST /api/pengajuan/{id}/reject` - Tolak pengajuan
- `POST /api/pengajuan/{id}/verify-documents` - Verifikasi dokumen

### Surat Tugas
- `POST /api/pengajuan/{id}/generate-surat` - Generate surat
- `GET /api/surat/{id}` - Detail surat
- `GET /api/surat/{id}/download` - Download PDF
- `POST /api/surat/{id}/sign-tte` - TTE signing

### Master Data
- `GET /api/master/jenjang` - List jenjang pendidikan
- `GET /api/master/unit-kerja` - List OPD
- `GET /api/master/status-pengajuan` - List status
- `GET /api/master/jenis-dokumen` - List jenis dokumen
- `GET /api/master/akreditasi` - List akreditasi

## Project Structure

```
sistem-surat-belajar-mandiri/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── ...
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
├── frontend/                # Vue 3
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── router/
│   │   ├── stores/
│   │   └── services/
│   └── ...
└── README.md
```

## License

MIT
