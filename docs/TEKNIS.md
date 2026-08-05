# Dokumen Teknis — Sistem Surat Izin Belajar Mandiri (SI-TEMA CANTIK)

> **Sistem Informasi Pengelolaan Izin Belajar Mandiri bagi PNS di Lingkungan Pemerintah Kabupaten Sukabumi**

Dokumen ini merupakan spesifikasi teknis lengkap yang mencakup arsitektur sistem, struktur database, API, frontend, alur bisnis, keamanan, serta panduan deployment dan pengembangan.

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tech Stack & Dependensi](#2-tech-stack--dependensi)
3. [Arsitektur Sistem](#3-arsitektur-sistem)
4. [Struktur Direktori](#4-struktur-direktori)
5. [Skema Database](#5-skema-database)
6. [Model Data (Backend)](#6-model-data-backend)
7. [REST API Specification](#7-rest-api-specification)
8. [Logika Bisnis & Alur Proses](#8-logika-bisnis--alur-proses)
9. [Role-Based Access Control (RBAC)](#9-role-based-access-control-rbac)
10. [Frontend Architecture](#10-frontend-architecture)
11. [Komponen Frontend](#11-komponen-frontend)
12. [Sistem Autentikasi & Keamanan](#12-sistem-autentikasi--keamanan)
13. [Sistem Notifikasi](#13-sistem-notifikasi)
14. [PDF, QR Code & TTE](#14-pdf-qr-code--tte)
15. [Integrasi Eksternal](#15-integrasi-eksternal)
16. [Optimasi Performa](#16-optimasi-performa)
17. [Deployment](#17-deployment)
18. [Akun Demo](#18-akun-demo)
19. [Catatan Pengembangan](#19-catatan-pengembangan)

---

## 1. Gambaran Umum

### 1.1 Deskripsi

**SI-TEMA CANTIK** (Sistem Surat Izin Belajar Mandiri) adalah aplikasi web untuk mengelola pengajuan izin belajar mandiri bagi Pegawai Negeri Sipil (PNS) di lingkungan Pemerintah Kabupaten Sukabumi. Sistem mengotomatisasi seluruh siklus pengajuan mulai dari pengajuan oleh pemohon, verifikasi dokumen oleh Admin BKPSDM, hingga penerbitan Surat Izin Belajar dan Surat Tugas dengan Tanda Tangan Elektronik (TTE).

### 1.2 Tujuan

- Menggantikan proses manual pengajuan izin belajar menjadi digital (paperless).
- Menjamin integritas dokumen melalui QR Code verifikasi dan TTE.
- Menyediakan pelacakan status pengajuan secara real-time (milestone).
- Mendukung multi-role: Pemohon, Atasan, Admin BKPSDM, Kepala BKPSDM, Kepala Unit/Dinas.

### 1.3 Dasar Hukum

- **Perbup Sukabumi No. 2 Tahun 2022** — Pedoman Tugas Belajar.
- UU No. 20/2003, UU No. 20/2023, PP No. 17/2020, Perda Kab. Sukabumi No. 3/2024.

### 1.4 Karakteristik Aplikasi

| Aspek | Keterangan |
|-------|------------|
| Tipe aplikasi | SPA (Single Page Application) + RESTful API |
| Arsitektur | Client-Server (decoupled frontend/backend) |
| Skala | Single-tenant, instansi pemerintah daerah |
| Bahasa | Bahasa Indonesia |

---

## 2. Tech Stack & Dependensi

### 2.1 Backend (Laravel)

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| PHP | ^8.3 | Menggunakan PHP 8 attributes & constructor promotion |
| Laravel Framework | ^13.8 | Core framework |
| Laravel Sanctum | ^4.3 | API token authentication |
| barryvdh/laravel-dompdf | ^3.1 | Generator PDF (utama) |
| barryvdh/laravel-snappy | ^1.0 | Generator PDF alternatif (wkhtmltopdf) |
| picqer/php-barcode-generator | — | Generator barcode |
| guzzlehttp/guzzle | — | HTTP client untuk integrasi eksternal |

**Dev dependencies:** Pest ^4.7, PHPUnit ^12, Laravel Pint ^1.27, Faker ^1.23, Mockery ^1.6.

### 2.2 Frontend (Vue 3)

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| Vue | ^3.5.34 | Composition API (`<script setup>`) |
| Vue Router | ^4.6.4 | SPA routing |
| Pinia | ^3.0.4 | State management |
| Axios | ^1.16.1 | HTTP client |
| Vite | ^8.0.12 | Build tool & dev server |
| Tailwind CSS | ^3.4.19 | Utility-first CSS |
| RemixIcon | 3.5.0 (CDN) | Icon set |
| Inter Font | (Google Fonts) | Tipografi utama |

### 2.3 Database & Infrastruktur

- **Database:** MySQL (MariaDB compatible).
- **Cache:** Laravel Cache (file/database driver) untuk master data & PDDikti.
- **Storage:** Laravel Filesystem (local disk, public symlink untuk cPanel).
- **Web Server:** Apache/Nginx (cPanel shared hosting friendly).

---

## 3. Arsitektur Sistem

### 3.1 Diagram Arsitektur Tingkat Tinggi

```
┌──────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                        │
│  Vue 3 SPA  (Vite build → dist/)                               │
│  ├─ Pinia Stores (auth, pengajuan, master, notification, toast)│
│  ├─ Vue Router (role-based guards)                             │
│  └─ Axios (api / apiQuick / apiLong)  ── Bearer Token ──┐     │
└──────────────────────────────────────────────────────────┼─────┘
                                                           │ HTTPS
┌──────────────────────────────────────────────────────────▼─────┐
│                LARAVEL API  (/api/*)                            │
│  Middleware: sanctum.auth + admin (RBAC)                        │
│  ├─ Controllers (19)    ├─ Services (4)                         │
│  ├─ Form Validation     ├─ API Resources                        │
│  └─ Blade Templates (PDF surat)                                 │
└─────────────┬───────────────────────┬──────────────────────────┘
              │                       │
   ┌──────────▼──────────┐  ┌─────────▼──────────┐
   │      MySQL DB       │  │   File Storage     │
   │  (22 tabel)         │  │  (dokumen, PDF,    │
   │                     │  │   QR, barcode)     │
   └─────────────────────┘  └────────────────────┘
              │
   ┌──────────▼──────────────────────────────────┐
   │   Integrasi Eksternal (cached/fallback)     │
   │  ├─ PDDikti API (pddikti.fastapicloud.dev)  │
   │  ├─ QR Server (api.qrserver.com)            │
   │  ├─ Barcode API (barcodeapi.org)            │
   │  └─ SIMPEG (simpeg.bkpsdmcloud.com)         │
   └─────────────────────────────────────────────┘
```

### 3.2 Pola Arsitektur

| Pola | Implementasi |
|------|-------------|
| **Decoupled API** | Frontend & backend terpisah, komunikasi via REST + JSON |
| **Service Layer** | Logika bisnis kompleks dipisah ke `app/Services/` (QrCodeService, BarcodeService, PDDiktiService, SimpegService) |
| **Repository-ish** | Model Eloquent sebagai data access + business method (mis. `Pengajuan::canBeEditedBy()`) |
| **API Resources** | Response JSON terstandardisasi `{ data, message }` |
| **Pinia Stores** | State terpusat di frontend dengan caching & TTL |
| **Middleware RBAC** | `auth:sanctum` + custom `admin` middleware |

### 3.3 Alur Request

```
Browser → Axios (inject Bearer token) → Laravel Route
  → Middleware (auth:sanctum [+ admin]) → Controller
  → Service (jika perlu) → Model/Eloquent → DB
  → Response JSON → Axios Response Interceptor → Pinia Store → Vue Component
```

---

## 4. Struktur Direktori

```
Sistem-Surat-Belajar-Mandiri/
├── backend/                         # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/         # 19 controller
│   │   │   └── Middleware/          # AdminMiddleware, EnsureFrontendRequestsAreStateful
│   │   ├── Models/                  # 16 model
│   │   └── Services/                # 4 service (QrCode, Barcode, PDDikti, Simpeg)
│   ├── config/                      # 13 file konfigurasi
│   ├── database/
│   │   ├── migrations/              # 32 migration
│   │   └── seeders/                 # 11 seeder
│   ├── resources/views/pdf/         # Blade template untuk surat PDF
│   ├── routes/api.php               # Definisi API routes
│   └── public/                      # Document root API
├── frontend/                        # Vue 3 SPA
│   ├── src/
│   │   ├── components/              # 20+ komponen (layout & reusable)
│   │   ├── views/                   # ~25 view (auth, pemohon, atasan, admin)
│   │   ├── stores/                  # 5 Pinia store
│   │   ├── router/index.js          # Route config + guards
│   │   ├── services/api.js          # Axios instance + interceptor
│   │   └── style.css                # Tailwind + custom classes
│   ├── dist/                        # Build produksi
│   └── tailwind.config.js
├── docs/                            # Dokumentasi
├── DEPLOYMENT.md
└── CLAUDE.md
```

---

## 5. Skema Database

### 5.1 ERD Overview

```
roles 1──∞ users ∞──1 unit_kerja
                 │
                 ├──∞ pengajuan ∞──1 jenjang_pendidikan
                 │       │
                 │       ├──∞ dokumen_pengajuan ∞──1 jenis_dokumen
                 │       ├──∞ approval_history
                 │       ├──1 surat_tugas_dinas
                 │       ├──1 surat_izin_belajar
                 │       └──∞ surat_tugas_mandiri
                 │
                 └──∞ notifications

perguruan_tinggi 1──∞ prodis
verification_rules (standalone config)
```

### 5.2 Daftar Tabel (22 tabel)

#### Tabel Master

**`roles`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR | Nama role |
| slug | VARCHAR UNIQUE | `pemohon`, `atasan`, `admin_bkpsdm`, `kepala_bkpsdm` |
| description | VARCHAR | |

**`unit_kerja`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| kode | VARCHAR UNIQUE | Kode OPD |
| nama | VARCHAR | Nama dinas/opd |
| singkatan | VARCHAR | |
| eselon | VARCHAR | |
| alamat, telepon, email | VARCHAR | Kontak |
| is_active | BOOLEAN | |

**`jenjang_pendidikan`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| nama | VARCHAR | D1–D4, S1, S2, S3, Profesi |
| kode | VARCHAR UNIQUE | |
| urutan | INT | Untuk sorting |
| is_active | BOOLEAN | |

**`jenis_dokumen`** (dinamis, dapat dikonfigurasi admin)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| kode | VARCHAR UNIQUE | `sk_pangkat`, `sk_cpns`, dll |
| nama | VARCHAR | Nama dokumen |
| deskripsi | TEXT | |
| is_wajib | BOOLEAN | Apakah wajib |
| urutan | INT | Urutan tampil |
| persyaratan | JSON | Detail persyaratan |
| catatan | TEXT | Catatan tambahan |
| is_active | BOOLEAN | Soft-delete |

**`verification_rules`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| kode | VARCHAR UNIQUE | staf, kasi, kabid, kadis, sekda, bupati |
| nama_jabatan | VARCHAR | |
| atasan_level | VARCHAR | Level atasan yang dibutuhkan |
| signer_s1 | VARCHAR | Default: Kepala BKPSDM |
| signer_s2 | VARCHAR | Default: Sekretaris Daerah |
| signer_s3 | VARCHAR | Default: Bupati |
| urutan | INT | 1=terendah |
| is_active | BOOLEAN | |

#### Tabel Utama

**`users`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR | Nama lengkap |
| email | VARCHAR UNIQUE | |
| password | VARCHAR | Hashed |
| nip | VARCHAR UNIQUE | Nomor Induk Pegawai |
| role_id | FK→roles | |
| unit_kerja_id | FK→unit_kerja | |
| atasan_id | FK→users (self) | Atasan langsung |
| jabatan_kategori | VARCHAR(50) | staf/kasi/kabid/kadis/sekda/bupati |
| pangkat_gol | VARCHAR | |
| jabatan | VARCHAR | |
| tanggal_lahir | DATE | |
| no_hp, alamat | VARCHAR/TEXT | |
| is_active | BOOLEAN | |
| is_kepala_unit | BOOLEAN | Flag kepala dinas/opd |

**`pengajuan`** (tabel sentral)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| nomor_pengajuan | VARCHAR UNIQUE | Format: `IBL/{tahun}/{seq}` |
| user_id | FK→users | Pemohon |
| created_by | FK→users | Pembuat (jika diwakilkan kepala unit) |
| jenjang_id | FK→jenjang_pendidikan | |
| nama_prodi | VARCHAR | |
| perguruan_tinggi | VARCHAR | |
| akreditasi_prodi | VARCHAR | |
| lokasi_pt | VARCHAR | |
| rencana_mulai / rencana_selesai | DATE | |
| status | ENUM | Lihat §8.2 |
| catatan_tolak | TEXT | |
| approval_level | ENUM(biasa/atasan) | |
| approved_by_atasan | FK→users | |
| approved_at_atasan | DATETIME | |
| tanggal_submit_atasan / approve_atasan / approve_admin | DATETIME | |
| timestamps | | |

**`dokumen_pengajuan`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| pengajuan_id | FK→pengajuan | |
| jenis_dokumen | VARCHAR(100) | Kode jenis (dinamis, relasi ke jenis_dokumen.kode) |
| file_path | VARCHAR | Path storage |
| file_name, file_type | VARCHAR | |
| file_size | BIGINT | Bytes |
| status_verifikasi | ENUM(pending/lengkap/tidak_lengkap) | |
| catatan | TEXT | Catatan verifikator |
| verified_by | FK→users | |
| verified_at | DATETIME | |

**`approval_history`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| pengajuan_id | FK→pengajuan | |
| approver_id | FK→users | |
| role_approval | ENUM(atasan/admin_bkpsdm/kepala_bkpsdm) | |
| status | ENUM(setuju/tolak) | |
| catatan | TEXT | |

#### Tabel Surat (3 tahap)

**`surat_tugas_dinas`** — Surat dari Kepala Dinas
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| pengajuan_id | FK→pengajuan | |
| unit_kerja_id | FK→unit_kerja | |
| kepala_dinas_id | FK→users | |
| nomor_surat | VARCHAR(50) | |
| bulan, tahun | VARCHAR | |
| tanggal_mulai/selesai/ttd | DATE | |
| tempat_ttd | VARCHAR | Default: Sukabumi |
| file_path | VARCHAR | |
| status | ENUM(draft/signed/completed) | |
| signed_at | DATETIME | |
| **UNIQUE** | (unit_kerja_id, nomor_surat, tahun) | |

**`surat_izin_belajar`** — Surat dari Kepala BKPSDM (TTE)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| pengajuan_id | FK→pengajuan | |
| surat_tugas_dinas_id | FK→surat_tugas_dinas (nullable) | |
| nomor_surat | VARCHAR(100) UNIQUE | `800.1.3.1/{seq}/BKPSDM/{tahun}` |
| tahun | VARCHAR(4) | |
| file_path, tte_path | VARCHAR | |
| qr_code | VARCHAR | Data QR untuk verifikasi |
| status | ENUM(draft/signed/completed) | |
| signed_at, signed_by, signed_by_nip | | |

**`surat_tugas_mandiri`** — Surat Tugas Belajar Mandiri
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| pengajuan_id | FK→pengajuan | |
| surat_izin_belajar_id | FK→surat_izin_belajar | |
| surat_tugas_dinas_id | FK→surat_tugas_dinas (nullable) | |
| nomor_surat | VARCHAR UNIQUE | `800.1.3.2/{seq}/BKPSDM/{tahun}` |
| tanggal_surat | DATE | |
| tempat_ttd | VARCHAR | |
| file_path, tte_path, qr_code | | |
| status, signed_at, signed_by, signed_by_nip | | |

#### Tabel Pendukung

**`notifications`**
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| user_id | FK→users | Penerima |
| pengajuan_id | FK→pengajuan (nullable) | |
| type | ENUM(info/warning/success/error) | |
| title, message | VARCHAR/TEXT | |
| is_read | BOOLEAN | |
| read_at | DATETIME | |
| **Index** | (user_id, is_read) | Optimasi polling |

**`perguruan_tinggi`** — Data PDDikti lokal
| Kolom | Tipe |
|-------|------|
| id, kode_pt(UNIQUE), nama_pt, nama_singkat |
| jenis_perguruan_tinggi, alamat, provinsi, kab_kota, kecamatan, kode_pos |
| website, telepon, email, akreditasi, status_pt |
| metadata(JSON), synced_at |

**`prodis`** — Program studi lokal
| Kolom | Tipe |
|-------|------|
| id, perguruan_tinggi_id(FK), kode_prodi, nama_prodi |
| jenjang, akreditasi, akreditasi_internasional, status_prodi, bidang_ilmu |
| id_prodi_external, metadata(JSON), synced_at |

**Tabel framework:** `personal_access_tokens` (Sanctum), `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### 5.3 Index Performa

| Tabel | Index | Tujuan |
|-------|-------|--------|
| notifications | (user_id, is_read), created_at | Polling unread cepat |
| pengajuan | (user_id, status), status, created_at, jenjang_id | Filter list |
| users | unit_kerja_id, jabatan_kategori, atasan_id | Lookup struktur |
| dokumen_pengajuan | (pengajuan_id, jenis_dokumen), status_verifikasi | Cek kelengkapan |
| unit_kerja | is_active, nama | Filter |

---

## 6. Model Data (Backend)

### 6.1 Daftar Model (16)

| Model | Tabel | Relasi Utama | Method Bisnis Penting |
|-------|-------|--------------|----------------------|
| `User` | users | role, unitKerja, atasan(self), bawahan, pengajuan | `hasRole()`, `isPemohon()`, `isAtasan()`, `isAdminBkpsdm()`, `isKepalaBkpsdm()`, `isKepalaUnit()` |
| `Role` | roles | users | — |
| `UnitKerja` | unit_kerja | users | accessor `nama_unit_kerja` |
| `JenjangPendidikan` | jenjang_pendidikan | pengajuan | accessor `nama_jenjang` |
| `Pengajuan` | pengajuan | user, jenjang, dokumen, surat* | `isDraft()`, `isVerified()`, `canBeEditedBy()`, `getAllDocumentsUploaded()`, `needsSuratIzinBelajar()` |
| `DokumenPengajuan` | dokumen_pengajuan | pengajuan, verifiedBy, jenisDokumen | accessor `jenis_dokumen_label`, `file_size_in_mb` |
| `ApprovalHistory` | approval_history | pengajuan, approver | — |
| `SuratTugas` | surat_tugas (legacy) | pengajuan, signedBy | `isSigned()` |
| `SuratTugasDinas` | surat_tugas_dinas | pengajuan, unitKerja, kepalaDinas, suratIzinBelajar | `getFullNomorSuratAttribute()`, `canBeEdited()`, scope `forUnitKerja()` |
| `SuratIzinBelajar` | surat_izin_belajar | pengajuan, suratTugasDinas | `canBeSigned()`, `markAsSigned()`, scope `pending/signed` |
| `SuratTugasMandiri` | surat_tugas_mandiri | pengajuan, suratIzinBelajar, suratTugasDinas | `canBeSigned()`, scope `signed/draft/byPengajuan` |
| `Notification` | notifications | user, pengajuan | `markAsRead()`, static `createForUser()`, scope `unread/forUser` |
| `VerificationRule` | verification_rules | — | `getSignerForJenjang()`, static `getByKode/findByJabatan` |
| `JenisDokumen` | jenis_dokumen | — | scope `active/allWithInactive` |
| `PerguruanTinggi` | perguruan_tinggi | prodis | scope `search` |
| `Prodi` | prodis | perguruanTinggi | scope `search/byPerguruanTinggi` |

### 6.2 Konfigurasi Model

Model baru menggunakan **PHP 8 attributes** (`#[Fillable]`, `#[Hidden]`), sedangkan model lama menggunakan properti tradisional `$fillable`/`$casts`. Contoh:

```php
// User.php (PHP 8 attribute style)
#[Fillable(['name', 'email', 'password', 'nip', 'role_id', ...])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable { ... }

// SuratIzinBelajar.php (traditional style)
protected $fillable = ['pengajuan_id', 'nomor_surat', ...];
protected $casts = ['signed_at' => 'datetime'];
```

---

## 7. REST API Specification

**Base URL:** `/api` (default: `http://localhost:8000/api`)

**Response Format:**
```json
// Success
{ "data": {...}, "message": "Success message" }

// Error
{ "message": "Error message", "errors": {...} }
```

**Authentication:** `Authorization: Bearer {token}` ( Sanctum)

### 7.1 Authentication

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/login` | — | Login by NIP/email (`identity`, `password`) |
| POST | `/logout` | ✓ | Hapus token aktif |
| GET | `/me` | ✓ | Data user + role + unitKerja |

### 7.2 Master Data (Public)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/master/jenjang` | Jenjang pendidikan aktif (cached 1j) |
| GET | `/master/unit-kerja` | Unit kerja aktif (cached 1j) |
| GET | `/master/status-pengajuan` | Daftar status |
| GET | `/master/jenis-dokumen` | Jenis dokumen aktif (cached) |
| GET | `/master/akreditasi` | Distinct akreditasi |
| GET | `/master/perguruan-tinggi?keyword=` | Search PT lokal (limit 100) |
| GET | `/master/prodi?perguruan_tinggi_id=&keyword=` | Search prodi lokal |

### 7.3 Pengajuan

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/pengajuan` | ✓ | List (filter by role, `?status=`, `?include_deleted=1`, paginated 10) |
| POST | `/pengajuan` | ✓ | Buat pengajuan baru |
| GET | `/pengajuan/nomor` | ✓ | Nomor pengajuan berikutnya |
| GET | `/pengajuan/{id}` | ✓ | Detail (role-based authorization) |
| PUT | `/pengajuan/{id}` | ✓ | Update (draft/ditolak only) |
| DELETE | `/pengajuan/{id}` | ✓ | Soft-delete → `dicabut` |
| POST | `/pengajuan/{id}/submit` | ✓ | Submit draft → `pending_admin` |
| POST | `/pengajuan/{id}/cancel` | ✓ | Tarik kembali → `draft` (owner only) |
| POST | `/pengajuan/{id}/restore` | ✓ | Pulihkan `dicabut` → `draft` |

### 7.4 Dokumen

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/pengajuan/{pengajuanId}/dokumen` | ✓ | List dokumen pengajuan |
| POST | `/pengajuan/{pengajuanId}/dokumen` | ✓ | Upload dokumen (max 5MB, pdf/jpg/png, ganti jika sama jenis) |
| DELETE | `/dokumen/{id}` | ✓ | Hapus dokumen |
| PUT | `/dokumen/{id}/verify` | admin | Verifikasi dokumen (lengkap/tidak_lengkap) |

### 7.5 Approval & Verification

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/pengajuan/{id}/approve` | admin | Approve + auto-generate 3 surat (TTE) |
| POST | `/pengajuan/{id}/reject` | ✓ | Tolak dengan catatan |
| POST | `/pengajuan/{id}/send-notification` | ✓ | Kirim pesan ke pemohon |
| GET | `/verification/rules` | ✓ | Aturan verifikasi |
| GET | `/verification/categories` | ✓ | Kategori jabatan |
| GET | `/verification/pengajuan/{id}` | ✓ | Chain verifikasi + signer |

### 7.6 Surat Izin Belajar

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/admin/surat-izin` | ✓ | List surat izin |
| GET | `/admin/surat-izin/pending` | ✓ | Pengajuan verified butuh surat |
| POST | `/admin/surat-izin` | kepala bkpsdm | Generate + TTE |
| GET | `/admin/surat-izin/{id}` | ✓ | Detail |
| GET | `/admin/surat-izin/{id}/preview` | token/auth | Preview HTML |
| GET | `/admin/surat-izin/{id}/download` | token/auth | Download PDF |
| POST | `/admin/surat-izin/{id}/sign` | kepala bkpsdm | TTE sign |
| GET | `/pengajuan/{id}/surat-izin` | ✓ | Get by pengajuan (pemohon) |
| GET | `/surat-izin/verify/{qrCode}` | — | Verifikasi publik via QR |

### 7.7 Surat Tugas Dinas (Kepala Unit)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/kepala/surat-tugas` | ✓ | List (scoped by unit kerja) |
| GET | `/kepala/surat-tugas/pending` | ✓ | Pengajuan signed butuh surat |
| POST | `/kepala/surat-tugas` | ✓ | Buat surat tugas |
| GET | `/kepala/surat-tugas/{id}/pdf` | token/auth | Download PDF |
| GET | `/surat-tugas/{pengajuanId}` | ✓ | Get by pengajuan |
| GET | `/surat-tugas/verify/{qrCode}` | — | Verifikasi publik |

### 7.8 Surat Tugas Mandiri

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/admin/surat-tugas-mandiri` | ✓ | List |
| POST | `/admin/surat-tugas-mandiri` | ✓ | Buat |
| GET | `/admin/surat-tugas-mandiri/{id}/preview` | token/auth | Preview |
| GET | `/admin/surat-tugas-mandiri/{id}/download` | token/auth | Download |
| POST | `/admin/surat-tugas-mandiri/{id}/sign` | kepala bkpsdm | TTE |
| GET | `/pengajuan/{id}/surat-tugas-mandiri` | ✓ | Get by pengajuan |
| GET | `/surat-tugas-mandiri/verify/{qrCode}` | — | Verifikasi publik |

### 7.9 Notifications

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/notifications` | List + unread count |
| GET | `/notifications/all-messages` | Feed terpadu (notif + approval + dokumen) |
| GET | `/notifications/unread-count` | Count only (fast poll) |
| POST | `/notifications/mark-all-read` | Tandai semua dibaca |
| PATCH | `/notifications/{id}/read` | Tandai satu dibaca |
| DELETE | `/notifications/{id}` | Hapus |

### 7.10 PDDikti & Sync (Admin)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/pddikti/universitas?keyword=` | — | Search PT (lokal dulu, API fallback) |
| GET | `/pddikti/prodi?keyword=` | — | Search prodi |
| POST | `/admin/pddikti-sync/universitas` | admin | Sync PT dari API |
| POST | `/admin/pddikti-sync/prodi` | admin | Sync prodi |
| GET | `/admin/pddikti-sync/stats` | admin | Statistik sync |

### 7.11 Pegawai (Admin)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/pegawai` | ✓ | List pegawai (search/filter/paginate) |
| GET | `/pegawai/{id}` | ✓ | Detail |
| PUT | `/pegawai/{id}` | ✓ | Update (termasuk atasan) |
| DELETE | `/pegawai/{id}` | ✓ | Hapus |
| GET | `/pegawai/{id}/structure` | ✓ | Rantai atasan + bawahan |
| POST | `/admin/pegawai-sync/import` | admin | Import JSON |
| GET | `/admin/pegawai-sync/stats` | admin | Statistik |
| GET | `/admin/pegawai-sync/template` | admin | Download template |

### 7.12 Admin Lainnya

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET/POST/PUT/DELETE | `/admin/jenis-dokumen/*` | admin | CRUD jenis dokumen dinamis |
| DELETE | `/admin/pengajuan/{id}` | admin | Hapus permanen |
| POST | `/admin/pengajuan/delete-multiple` | admin | Hapus bulk |
| POST | `/admin/cache/clear` | admin | Clear cache master data |
| POST | `/system/migrate` | secret | Run migration (deployment tanpa SSH) |

---

## 8. Logika Bisnis & Alur Proses

### 8.1 Alur Utama (Simplified Flow)

```
┌────────────┐     ┌─────────────────┐     ┌──────────────────┐
│  PEMOHON   │     │  ADMIN BKPSDM   │     │ KEPALA BKPSDM    │
│            │     │                 │     │                  │
│ Buat       │────▶│ Verifikasi      │────▶│ Generate & TTE   │
│ Pengajuan  │     │ Dokumen         │     │ Surat Izin +     │
│ + Upload   │     │                 │     │ Surat Tugas      │
│ Dokumen    │     │ Approve/Reject  │     │ Mandiri          │
└────────────┘     └─────────────────┘     └──────────────────┘
                                                │
                                                ▼
                                     ┌──────────────────┐
                                     │  ADMIN BKPSDM    │
                                     │                  │
                                     │ Buat Surat Tugas │
                                     │ Dinas (TTE sama) │
                                     └──────────────────┘
                                                │
                                                ▼
                                           SELESAI
```

> **Catatan:** ApprovalController::approveAdmin() saat ini **auto-generate ketiga surat** dalam satu transaksi (Surat Izin Belajar + Surat Tugas Mandiri + Surat Tugas Dinas) saat admin menyetujui pengajuan.

### 8.2 Status Pengajuan

| Status | Penjelasan | Bisa Edit | Bisa Cabut |
|--------|------------|-----------|------------|
| `draft` | Belum dikirim | ✓ | — |
| `pending_admin` | Menunggu verifikasi admin | ✗ | ✓ → draft |
| `verified` | Dokumen lengkap | ✗ | ✓ → draft |
| `surat_dinas` | Surat tugas dinas dibuat | ✗ | ✗ |
| `surat_izin` | Surat izin dibuat | ✗ | ✗ |
| `signed` | Surat sudah ditandatangani TTE | ✗ | ✗ |
| `selesai` | Proses lengkap | ✗ | ✗ |
| `completed` | Final | ✗ | ✗ |
| `ditolak` | Ditolak admin | ✓ (edit ulang) | ✗ |
| `dicabut` | Dihapus/ditarik permanen | ✗ | restore → draft |

### 8.3 State Diagram

```
                         ┌─────────┐
              restore    │ dicabut │◀── delete (draft)
          ┌─────────────▶│         │
          │              └─────────┘
          │                    │
          │ restore            ▲
          │                    │ cancel
     ┌────▼───┐  submit   ┌────┴──────────┐  approve  ┌──────────┐
     │ draft  │──────────▶│ pending_admin │──────────▶│ verified │
     │        │◀──────────│               │           └────┬─────┘
     └────────┘  cancel   └───────────────┘                │
          │                    │                           │ auto-gen
          │                    │ reject                    │ surat
          │                    ▼                           ▼
          │              ┌──────────┐              ┌──────────────┐
          └──────────────│ ditolak  │              │ signed       │
              edit       └──────────┘              └──────┬───────┘
                                                   surat tugas
                                                          │
                                                          ▼
                                                    ┌──────────┐
                                                    │ selesai  │
                                                    └──────────┘
```

### 8.4 Format Penomoran Surat

| Surat | Format | Contoh |
|-------|--------|--------|
| Pengajuan | `IBL/{tahun}/{seq:04d}` | `IBL/2026/0001` |
| Surat Izin Belajar | `800.1.3.1/{seq:03d}/BKPSDM/{tahun}` | `800.1.3.1/001/BKPSDM/2026` |
| Surat Tugas Mandiri | `800.1.3.2/{seq:03d}/BKPSDM/{tahun}` | `800.1.3.2/001/BKPSDM/2026` |
| Surat Tugas Dinas | `{seq:03d}/DK/{bulan}/{tahun}` | `001/DK/Mei/2026` |

**Concurrency safety:** Nomor urut di-generate menggunakan `lockForUpdate()` + DB transaction + retry logic untuk mencegah race condition.

### 8.5 Matriks Verifikasi (Berdasarkan Jabatan)

| Jabatan Pemohon | Atasan Langsung | Signer S1 | Signer S2 | Signer S3 |
|-----------------|-----------------|-----------|-----------|-----------|
| Staf/Pelaksana | Kabid/Kasi | Kepala BKPSDM | Sekda | Bupati |
| Kepala Seksi | Kabid | Kepala BKPSDM | Sekda | Bupati |
| Kepala Bidang | Kepala Dinas | Kepala BKPSDM | Sekda | Bupati |
| Kepala Dinas | Sekda | Kepala BKPSDM | Sekda | Bupati |
| Sekda | Bupati | Kepala BKPSDM | Sekda | Bupati |

Penandatangan ditentukan berdasarkan jenjang: D1-D3/S1 → signer_s1, S2/Profesi → signer_s2, S3 → signer_s3.

### 8.6 Milestone Tracking (4 Langkah)

| Langkah | Status Trigger | Warna |
|---------|---------------|-------|
| 1. Dikirim | `pending_admin`+ | Biru (current) → Hijau (done) |
| 2. Verifikasi | `verified`+ | Biru/Hijau |
| 3. TTE | `signed` | Biru/Hijau |
| 4. Selesai | `selesai/completed` | Hijau (done) |

Progress: 25% → 50% → 75% → 100%.

---

## 9. Role-Based Access Control (RBAC)

### 9.1 Role & Permission Matrix

| Role | Slug | Hak Akses |
|------|------|-----------|
| **Pemohon (PNS)** | `pemohon` | CRUD pengajuan sendiri, upload dokumen, download surat, lihat status |
| **Atasan Langsung** | `atasan` | Semua hak pemohon + melihat pengajuan dari unit kerja |
| **Admin BKPSDM** | `admin_bkpsdm` | Verifikasi dokumen, approve/reject, generate surat, kelola pegawai & master data |
| **Kepala BKPSDM** | `kepala_bkpsdm` | TTE surat izin belajar & surat tugas mandiri |

### 9.2 Kepala Unit (Flag `is_kepala_unit`)

Role `atasan` dengan flag `is_kepala_unit=true` memiliki fungsi ganda:
1. **Kepala Dinas (OPD)** — Membuat Surat Tugas Belajar untuk pegawai di unit kerjanya.
2. Dapat membuat pengajuan untuk stafnya (`created_by` field).

### 9.3 Implementasi Middleware

- `auth:sanctum` — Validasi token Sanctum.
- `admin` (custom `AdminMiddleware`) — Cek `isAdminBkpsdm()`, return 403 jika bukan admin.
- Permission check di controller level untuk role kompleks (mis. Kepala BKPSDM check di SuratIzinBelajarController::store()).

### 9.4 Frontend Route Guard

```javascript
// router/index.js — beforeEach guard
if (to.meta.requiresAuth && !isAuthenticated) → redirect /login
if (to.meta.roles && !roles.includes(userRole)) → redirect /dashboard
```

---

## 10. Frontend Architecture

### 10.1 Bootstrap Flow

```
main.js
  ├─ createPinia()
  ├─ createRouter()
  ├─ directive: v-click-outside
  ├─ authStore.initializeFromStorage()  // hydrate dari localStorage
  └─ app.mount('#app')

App.vue
  ├─ <router-view />
  ├─ <ToastContainer />               // global toast
  └─ onMounted: if authenticated → fetchUser() + startTokenCheck()
```

### 10.2 API Service Layer (`services/api.js`)

Tiga instance Axios dengan timeout berbeda:

| Instance | Timeout | Penggunaan |
|----------|---------|------------|
| `api` (default) | 30s | Request umum |
| `apiQuick` | 5s | Polling notifikasi (fast-fail) |
| `apiLong` | 60s | Upload file |

**Request interceptor:** Inject `Authorization: Bearer {token}` dari localStorage.

**Response interceptor:**
- 401 → simpan redirect URL, clear token, redirect `/login` (debounced).
- 403/419/500/network → error handling konsisten.

### 10.3 State Management (Pinia Stores)

| Store | Style | State Utama | Actions |
|-------|-------|-------------|---------|
| `auth` | Options | user, token, tokenExpiryTime | login, logout, fetchUser, startTokenCheck, extendToken |
| `pengajuan` | Options | pengajuanList, currentPengajuan | fetch, create, update, delete, submit, cancel, restore |
| `master` | Options | jenjang, unitKerja, jenisDokumen, perguruanTinggi, prodi | fetch* (dengan caching) |
| `notification` | Options | notifications, allMessages, unreadCount | fetch* (dengan TTL cache 30-60s), markAsRead |
| `toast` | Composition | toasts | show, success, error, warning, info, remove |

**localStorage keys:** `token`, `user`, `tokenExpiryTime`.

### 10.4 Routing & Views

Lihat §7 untuk endpoint. Frontend routes menggunakan lazy-loading dan meta `roles`:

```
/ → redirect by role
/login                          (public)
/verify                         (public - QR verification)
/dashboard                      (pemohon, atasan)
/pengajuan, /pengajuan/baru,
/pengajuan/:id, /pengajuan/:id/edit  (pemohon, atasan)
/admin/verifikasi               (admin_bkpsdm)
/admin/riwayat-verifikasi       (admin_bkpsdm)
/admin/surat-izin, /admin/surat-tugas, ...
/kepala/signing, /kepala/riwayat      (kepala_bkpsdm)
/kepala/surat-tugas             (kepala, atasan)
/admin/pegawai, /admin/jenis-dokumen, /admin/pddikti-sync  (admin_bkpsdm)
```

---

## 11. Komponen Frontend

### 11.1 Layout Components

| Komponen | Fungsi |
|----------|--------|
| `MainLayout.vue` | Shell: TopNavbar + Sidebar + content + toast/notifier/session warning |
| `TopNavbar.vue` | Navbar gradient (primary-700→accent), logo "SI-TEMA CANTIK", NotificationBell, user dropdown |
| `Sidebar.vue` | Menu role-based (pemohon/atasan, admin, kepala), off-canvas mobile, isActive() exact match |

### 11.2 Reusable Components

| Komponen | Props | Emit | Fungsi |
|----------|-------|------|--------|
| `PageHeader.vue` | title, subtitle, actions[], showBack, backTo | — | Header halaman konsisten dengan action buttons |
| `Breadcrumb.vue` | — | — | Auto dari route, skip `baru`/`edit` |
| `LoadingSpinner.vue` | size, color, text, type, progress | — | 4 varian: spin/progress/dots/pulse |
| `Toast.vue` | show, message, type, duration, action | close | Single toast, Teleport to body |
| `ToastContainer.vue` | — | — | Render semua toast dari store |
| `ToastAutoNotifier.vue` | — | — | Poll 120s, toast untuk notif penting |
| `SessionWarning.vue` | — | — | Tracking aktivitas, extend token, warning expiry |
| `NotificationBell.vue` | — | — | Dropdown panel, badge unread, poll 30s |
| `SendMessageModal.vue` | show, pengajuanId, pemohonName | close, sent | Kirim pesan ke pemohon |
| `FileUpload.vue` | modelValue, label, accept, maxSize, preview | update:modelValue, fileSelected, preview | Drag-drop + click upload |
| `DocumentPreviewModal.vue` | show, document, src, alt, fileType | close | Preview PDF/image, zoom-pan, shortcut keyboard |
| `PDDiktiDropdown.vue` | modelValue, type, idPt, required | update:modelValue | Searchable dropdown PT/prodi + manual input |
| `PengajuanMilestone.vue` | pengajuanId, compact | — | Timeline 3-4 langkah dengan progress |
| `DocumentInfoTooltip.vue` | title, requirements[], notes | — | Tooltip info persyaratan dokumen |
| `VerificationDetailModal.vue` | show, pengajuanId | close, verified | Modal verifikasi admin lengkap |

### 11.3 Design System (Tailwind)

**Custom Colors:**
- `primary` (BKPSDM Gold): 50 `#fefce8` → 900 `#423d00` (500 `#eab308`)
- `secondary` (Stone): 50 `#f5f5f4` → 900 `#0c0a09`
- `accent`: `#E8D800`
- Status: `success #22c55e`, `warning #f59e0b`, `danger #ef4444`, `info #3b82f6`

**Custom Classes (style.css):**
- Buttons: `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`, `.btn-outline`, `.btn-sm/lg/icon`
- Cards: `.card`, `.card-header/body/footer/title`
- Forms: `.input-field`, `.input-label`, `.select-field`, `.textarea-field`
- Badges: `.badge`, `.badge-primary/success/warning/danger/info/purple`
- Tables: `.data-table`
- Animations: `.animate-fade-in`, `.animate-slide-up`
- Skeleton loading, alert, avatar, tooltip, dropdown, accordion, toast

---

## 12. Sistem Autentikasi & Keamanan

### 12.1 Autentikasi

- **Mechanism:** Laravel Sanctum (token-based, bukan session untuk API).
- **Login:** By NIP atau email (`identity` field) + password.
- **Token:** Disimpan di `personal_access_tokens`, dikirim via `Authorization: Bearer`.
- **Frontend storage:** `localStorage` (token, user, tokenExpiryTime).

### 12.2 Token Lifecycle (Frontend)

```
login() → set token + expiry (3 jam sliding)
  ↓
startTokenCheck() → interval 30 detik
  ├─ sisa ≤ 5 menit → tampilkan SessionWarning
  ├─ extendToken() jika ada aktivitas (< 1 jam tersisa)
  └─ expiry 0 → auto logout → /login
```

**SessionWarning.vue** melacak aktivitas user (mousedown/keydown/scroll/touch) untuk sliding session.

### 12.3 Public Token Access (PDF)

Preview & download PDF surat dapat diakses publik via query param `?token={token}`:
- Controller resolve token via `PersonalAccessToken::findToken()`.
- Memungkinkan pemohon membuka PDF di tab baru tanpa re-auth.
- Token di URL **harus di-encode** (`encodeURIComponent`) karena mengandung karakter `|`.

### 12.4 Keamanan Tambahan

- Password di-hash (Laravel default bcrypt).
- Role-based filtering di query (pemohon hanya lihat data sendiri).
- File upload validation: tipe (pdf/jpg/jpeg/png), ukuran (max 5MB).
- Authorization di controller level (bukan hanya frontend).
- System migration endpoint diproteksi secret key.

---

## 13. Sistem Notifikasi

### 13.1 Mekanisme

- **Database-backed:** Tabel `notifications`, bukan real-time WebSocket.
- **Polling:** Frontend poll `/notifications/unread-count` setiap 30-120 detik.
- **Smart polling:** Fetch count dulu (apiQuick, 5s timeout), fetch list hanya jika count meningkat.

### 13.2 Cache TTL (Frontend)

| Data | TTL |
|------|-----|
| Unread count | 30 detik |
| Unread list | 60 detik |
| All messages | 60 detik |

### 13.3 Trigger Notifikasi

| Event | Penerima | Type |
|-------|----------|------|
| Submit pengajuan | Semua admin_bkpsdm | pengajuan_baru |
| Approve pengajuan | Pemohon | success |
| Reject pengajuan | Pemohon | error |
| Verifikasi dokumen (catatan) | Pemohon | info/warning |
| Kirim pesan | Pemohon | info |

### 13.4 Unified Message Feed

`/notifications/all-messages` menggabungkan:
1. Notifications table
2. Approval history (catatan approve/reject)
3. Document verification notes

---

## 14. PDF, QR Code & TTE

### 14.1 PDF Generation

- **Engine:** DOMPDF (`barryvdh/laravel-dompdf`) — utama.
- **Alternatif:** wkhtmltopdf (`barryvdh/laravel-snappy`) — jika terinstall.
- **Template:** Blade views di `resources/views/pdf/`:
  - `surat-izin-belajar.blade.php`
  - `surat-tugas-dinas.blade.php`
  - `surat-tugas-mandiri.blade.php`
- **Paper:** A4 Portrait, margin 0, font Arial 11pt body / 15pt header.
- **On-the-fly generation:** PDF di-generate setiap request download (selalu sinkron dengan template terbaru).

### 14.2 QR Code

**Service:** `QrCodeService` (backend/app/Services/)

- Generator: external API `api.qrserver.com` (dengan fallback GD library).
- Storage: `storage/app/public/qr-codes/`.
- Data format (JSON):
```json
{
  "type": "surat_izin_belajar",
  "id": 123,
  "nomor": "800.1.3.1/001/BKPSDM/2026",
  "signed_at": "2026-07-01T10:00:00Z"
}
```
- **Verifikasi publik:** Endpoint `/surat-izin/verify/{qrCode}` (no auth).

### 14.3 Barcode

**Service:** `BarcodeService`

- Generator: external `barcodeapi.org` (Code128) + fallback GD.
- Berisi nomor surat untuk identifikasi cepat.

### 14.4 TTE (Tanda Tangan Elektronik)

- Bukan BSrE asli; simulasi TTE dengan QR code sebagai bukti keaslian.
- `signed_at`, `signed_by`, `signed_by_nip` dicatat di tabel surat.
- Status berubah: `draft` → `signed`.

---

## 15. Integrasi Eksternal

### 15.1 PDDikti

| Aspek | Detail |
|-------|--------|
| API | `https://pddikti.fastapicloud.dev/api` |
| Cache TTL | 24 jam |
| Timeout | 30 detik |
| SSL verify | Disabled |
| Strategi | **Local DB first**, API fallback. Data di-sync & disimpan di `perguruan_tinggi` + `prodis`. |

**Config:** `config/services.php` → `pddikti`.

### 15.2 SIMPEG

| Aspek | Detail |
|-------|--------|
| Base URL | `https://simpeg.bkpsdmcloud.com` |
| Mechanism | Web scraping (CSRF + form login + HTML table parse) |
| Service | `SimpegService` (Guzzle) |

### 15.3 QR & Barcode API

- QR: `https://api.qrserver.com/v1/create-qr-code/`
- Barcode: `https://barcodeapi.org/api/`
- Fallback: GD library / text representation.

---

## 16. Optimasi Performa

### 16.1 Database

- Composite index pada tabel high-traffic (notifications, pengajuan, users, dokumen).
- Selective eager loading (specific columns, bukan `*`).
- Pagination 10 item/halaman.

### 16.2 Caching

| Data | TTL | Invalidate |
|------|-----|------------|
| Master data (jenjang, unit kerja) | 1 jam | Manual `POST /admin/cache/clear` atau auto setelah update |
| PDDikti API response | 24 jam | — |
| Notification count (frontend) | 30 detik | — |

### 16.3 Frontend

- Route-based code splitting (lazy-loaded components).
- Smart notification polling (count-first strategy).
- Skeleton loading states.
- Debounced redirect pada 401.

---

## 17. Deployment

### 17.1 Environment Variables

**Backend (.env):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_DATABASE=sipintar_db
DB_USERNAME=***
DB_PASSWORD=***
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
WKHTML_PDF_BINARY=/usr/local/bin/wkhtmltopdf  # opsional
```

**Frontend (.env.production):**
```env
VITE_API_URL=https://yourdomain.com/api
```

### 17.2 Deployment Steps (cPanel)

1. **Build frontend:**
   ```bash
   cd frontend && npm run build
   ```
2. Upload `frontend/dist/` → `public_html/`
3. Upload `backend/` → `public_html/api/` (exclude `node_modules`, `vendor`)
4. `composer install --no-dev` (di server)
5. Buat database MySQL di cPanel
6. Update `.env` production
7. Jalankan migration (via `/api/system/migrate` jika tanpa SSH, atau `php artisan migrate --force`)
8. `php artisan storage:link` (atau PHP script symlink workaround)
9. `php artisan config:cache`

### 17.3 Storage Symlink (Shared Hosting)

Jika `storage:link` gagal di shared hosting, gunakan PHP script:
```php
<?php
$target = '../storage/app/public';
$link = __DIR__ . '/storage';
symlink($target, $link) or die('Hosting tidak support symlink');
```

Atau manual copy `storage/app/public/*` → `public/storage/`.

### 17.4 Artisan Commands Khusus

```bash
# Import PDDikti
php artisan pddikti:import --file=scrape_progress.json

# Import Pegawai
php artisan pegawai:import data.json --mode=sync

# Seeder
php artisan db:seed --class=DatabaseSeeder
```

---

## 18. Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Pemohon | drajat@disdik.go.id | password |
| Atasan | kadisdik@disdik.go.id | password |
| Admin BKPSDM | admin@bkpsdm.go.id | password |
| Kepala BKPSDM | kepala@bkpsdm.go.id | password |

---

## 19. Catatan Pengembangan

### 19.1 Testing

```bash
# Backend (Pest)
cd backend && php artisan test --compact

# Frontend
cd frontend && npm run test
```

### 19.2 Running Development

```bash
# Backend
cd backend && php artisan serve    # http://localhost:8000

# Frontend
cd frontend && npm run dev         # http://localhost:5173
```

> **Windows + Bun.js issue:** Bun bermasalah dengan Git Bash. Gunakan PowerShell atau `node node_modules/vite/bin/vite.js` langsung.

### 19.3 Known Issues

- PDF generation butuh `wkhtmltopdf` terinstall untuk snappy (opsional).
- File upload max 5MB/file.
- PHP `mysqli` extension di Laragon bisa duplikat di php.ini — hapus entri duplikat.
- `pdo_firebird` & `pdo_oci` tidak ada di Windows — comment out di php.ini.
- QR & Barcode bergantung pada external API (butuh internet), dengan fallback GD.

### 19.4 Tech Debt

- `SuratTugas` model & `SuratView.vue` adalah **legacy** (deprecated), digantikan sistem 3 surat baru.
- `HelloWorld.vue` & empty `common/` dir tersisa dari scaffold Vite.
- Beberapa dokumen type masih hardcoded di `VerificationDetailModal.vue` (seharusnya dinamis dari `jenis_dokumen`).

### 19.5 Statistik Kode

| Kategori | Jumlah |
|----------|--------|
| Models | 16 |
| Controllers | 19 |
| Migrations | 32 |
| Services | 4 |
| Seeders | 11 |
| Middleware | 2 |
| Frontend Views | ~25 |
| Frontend Components | ~20 |
| Pinia Stores | 5 |
| Database Tables | 22 |

---

*Dokumen ini dibuat berdasarkan analisis menyeluruh kode sumber per Juli 2026.*
