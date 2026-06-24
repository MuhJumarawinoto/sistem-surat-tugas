# Analisis Sistem SIPINTAR (Sistem Izin Belajar Mandiri)

> Dokumen analisis arsitektur, platform, proses bisnis, dan panduan sistem.
> Secara internal sistem juga di-branding sebagai **"SI-TEMA CANTIK"** (Sistem Informasi Tugas Belajar Mandiri dan Pencantuman Gelar Akademik) untuk BKPSDM Kabupaten Sukabumi.

---

## Daftar Isi

1. [Arsitektur & Platform](#1-arsitektur--platform)
2. [Struktur Database](#2-struktur-database-skema-inti)
3. [Proses Bisnis (Workflow)](#3-proses-bisnis-workflow)
4. [Arsitektur Backend](#4-arsitektur-backend)
5. [Arsitektur Frontend](#5-arsitektur-frontend)
6. [Panduan (How-To)](#6-panduan-how-to)
7. [Temuan & Risiko Penting](#7-temuan--risiko-penting-)
8. [Referensi File Kunci](#8-referensi-file-kunci)

---

## 1. Arsitektur & Platform

### Pola Arsitektur
**Monorepo decoupled** — Frontend SPA + Backend REST API terpisah, berkomunikasi via HTTP/JSON.

```
┌──────────────────────┐        JSON/HTTPS         ┌──────────────────────┐
│  Frontend (Vue 3)    │  ◄──────────────────►     │  Backend (Laravel)   │
│  SPA (Vite)          │   Bearer Token (Sanctum)  │  REST API            │
└──────────────────────┘                           └─────────┬────────────┘
                                                             │
                                                   ┌─────────▼────────────┐
                                                   │  MySQL Database       │
                                                   │  Storage (PDF/QR)     │
                                                   └──────────────────────┘
```

### Tech Stack (terverifikasi dari `package.json` & `composer.json`)

| Lapisan | Teknologi | Versi |
|---------|-----------|-------|
| **Frontend** | Vue 3 (Composition API, `<script setup>`) | ^3.5.34 |
| Build tool | Vite | ^8.0.12 |
| Styling | Tailwind CSS (palet "Kuning Emas" BKPSDM) | ^3.4.19 |
| State Mgmt | Pinia | ^3.0.4 |
| Routing | Vue Router 4 | ^4.6.4 |
| HTTP | Axios (3 instance: api 30s, apiQuick 5s, apiLong 60s) | ^1.16.1 |
| Ikon | RemixIcon (via CDN, bukan paket npm) | — |
| Font | Inter (Google Fonts) | — |
| **Backend** | Laravel | 13.8 (framework) |
| Bahasa | PHP | 8.3 |
| Auth | Laravel Sanctum (token bearer) | ^4.3 |
| PDF | `barryvdh/laravel-dompdf` | ^3.1 |
| DB | MySQL (ada fallback SQLite di repo) | — |
| Testing | Pest / PHPUnit | ^4.7 / ^12.5 |

**Catatan:** `barryvdh/laravel-snappy` tercantum tapi tidak dipakai aktif. PDF di-generate via DOMPDF.

---

## 2. Struktur Database (Skema Inti)

Database memiliki ~15 tabel utama. Relasi inti:

```
roles ──< users (role_id, is_kepala_unit) ──< pengajuan (user_id, jenjang_id)
                                                    │
                    ┌───────────────────────────────┼──────────────────────────┐
                    │                               │                          │
            dokumen_pengajuan              surat_tugas_dinas          surat_izin_belajar
            (9 jenis dokumen,              (kepala dinas)             (kepala BKPSDM)
             status_verifikasi)                 │                            │
                                                 └──────< surat_tugas_mandiri ───┘
unit_kerja ──< users              approval_history, notifications, verification_rules
jenjang_pendidikan                perguruan_tinggi ──< prodis (PDDikti lokal)
```

### Tabel `pengajuan`
(`backend/database/migrations/2026_05_16_175638_create_pengajuan_table.php`)

Field utama: `nomor_pengajuan`, `user_id`, `jenjang_id`, `nama_prodi`, `perguruan_tinggi`, `akreditasi_prodi`, `lokasi_pt`, `rencana_mulai/selesai`, `status` (enum), `catatan_tolak`, timestamps approval.

### Enum Status Pengajuan
Diperluas via migration berikutnya menjadi:
`draft, pending_atasan, pending_admin, verified, disetujui, signed, ditolak, selesai, completed, dicabut, surat_dinas, surat_izin`

### Tabel `users` (modifikasi)
Field tambahan: `nip` (unique), `role_id`, `unit_kerja_id`, `pangkat_gol`, `jabatan`, `tanggal_lahir`, `no_hp`, `alamat`, `is_active`, `atasan_id`, `jabatan_kategori`, `is_kepala_unit`.

### Tabel Surat
| Tabel | Penerbit | Field Kunci |
|-------|----------|-------------|
| `surat_tugas_dinas` | Kepala Dinas | nomor_surat, bulan, tahun, tanggal_mulai/selesai/ttd, kepala_dinas_id |
| `surat_izin_belajar` | Kepala BKPSDM | nomor_surat, qr_code, tte_path, signed_at/by/by_nip |
| `surat_tugas_mandiri` | Kepala BKPSDM | surat_izin_belajar_id, surat_tugas_dinas_id, qr_code |

---

## 3. Proses Bisnis (Workflow)

### Alur Utama (sesuai kode aktual)

```
draft → pending_admin → verified → signed → selesai
  │         (submit)    (admin    (Kepala    (Admin buat
  │                      verify)  BKPSDM     Surat Tugas
  │                               TTE)       Dinas)
  ↓
dicabut ←─ (hapus draft / soft delete)
```

### Detail Tiap Transisi

| Transisi | Trigger | Aktor | Lokasi Kode |
|----------|---------|-------|-------------|
| `→ draft` | Buat pengajuan baru | Pemohon/Atasan | `PengajuanController.php:100` |
| `draft → pending_admin` | Submit (tanpa cek kelengkapan dokumen) | Pemohon | `submit():265` |
| `pending_admin → verified` | Approve (approveAdmin) | Admin BKPSDM | `ApprovalController.php:62` |
| `verified → signed` | Generate & TTE Surat Izin | Kepala BKPSDM | `SuratIzinBelajarController.php:187` |
| `signed → selesai` | Buat Surat Tugas Dinas | Admin BKPSDM | `SuratTugasDinasController.php:169` |
| `pending_admin/verified → draft` | Cabut berkas | Pemohon | `cancel():206` |
| `draft/dicabut → dicabut` | Hapus (soft delete) | Pemohon | `destroy():181` |
| `dicabut → draft` | Pulihkan | Pemohon | `restore():233` |
| `pending_* → ditolak` | Reject + catatan | Admin/Atasan | `reject():125` |

### Aktor & Hak Akses (4 Role)

| Role | Kode | Tugas Utama |
|------|------|-------------|
| **Pemohon (PNS)** | `pemohon` | Buat/edit/hapus draft, upload 9 dokumen, submit, cabut, download surat |
| **Atasan** | `atasan` | Bisa juga buat pengajuan sendiri (regulasi membolehkan) |
| **Admin BKPSDM** | `admin_bkpsdm` | Verifikasi dokumen, approve/reject, buat Surat Tugas Dinas, kelola pegawai, sync SIMPEG |
| **Kepala BKPSDM** | `kepala_bkpsdm` | Generate & TTE Surat Izin Belajar |
| **Kepala Dinas/OPD** | flag `is_kepala_unit=true` | (Sub-fungsi) — notifikasi menyuruh buat surat, tapi kode aktual menolak selain admin |

### 9 Dokumen Wajib
(`Pengajuan.php:183`): sk_pangkat, sk_cpns, skp, surat_lulus, jadwal, akreditasi, surat_mandiri, surat_ijazah, surat_sehat.

### Sistem 2-Tahap Surat
1. **Surat Izin Belajar** — dikeluarkan Kepala BKPSDM dengan TTE + QR code
2. **Surat Tugas Dinas** — dikeluarkan Admin BKPSDM setelah Surat Izin signed
3. **Surat Tugas Mandiri** — langkah opsional terpisah (status `selesai`)

---

## 4. Arsitektur Backend

```
backend/app/
├── Http/Controllers/   17 controller (Auth, Pengajuan, Approval, Dokumen,
│                       3× Surat, Pegawai, PDDikti, Verification, Master, Notification)
├── Models/             15 model (Eloquent)
├── Services/           4 service:
│   ├── QrCodeService   → panggil API eksternal api.qrserver.com
│   ├── BarcodeService  → panggil API eksternal barcodeapi.org
│   ├── PDDiktiService  → lookup data perguruan tinggi
│   └── SimpegService   → scrape portal SIMPEG via Guzzle (verify=false!)
└── Http/Middleware/    AdminMiddleware (alias 'admin') → cek isAdminBkpsdm()
```

### Pola yang Dipakai
- Controller RESTful dengan response JSON terstandar
- **Tidak** memakai API Resource / Form Request (validasi inline di controller)
- **Tidak** memakai Service Layer untuk business logic (logic ada di controller)
- Role check sebagian via middleware `admin`, sebagian inline di tiap method
- `routes/api.php` (221 baris) — grup: public routes, auth routes, download routes ber-token

### Bagaimana "TTE" Sebenarnya Bekerja ⚠️ PENTING
**Bukan tanda tangan elektronik kriptografis (BSrE/PAdES).** Hanya metadata stamp:
1. Set 4 kolom: `status='signed'`, `signed_at`, `signed_by` (nama), `signed_by_nip` (NIP)
2. `tte_path` = sama dengan `file_path` PDF (bukan artefak tanda tangan terpisah)
3. PDF hanya mencetak **teks nama + NIP** + QR/barcode (dari API eksternal)
4. Verifikasi publik via scan QR → endpoint `/surat-izin/verify/{qr}`

Meskipun CLAUDE.md menyebut "TTE BSrE BSSN", **tidak ada integrasi BSrE, tidak ada private key, PDF tidak ditandatangani secara kriptografis.**

### Cara Kerja PDF Generation
- Library: `barryvdh/laravel-dompdf ^3.1` via facade `Pdf::loadView(...)`
- Template: Blade di `backend/resources/views/pdf/` (surat-izin-belajar, surat-tugas-dinas, surat-tugas-mandiri, surat-tugas, salinan-surat-tugas)
- Pola: eager-load surat + relasi → generate QR via QrCodeService → generate barcode via BarcodeService → base64-encode → render Blade → simpan ke `Storage::disk('public')`
- Surat Izin di-regenerate on-the-fly saat download (preview = download)

---

## 5. Arsitektur Frontend

```
frontend/src/
├── views/         ~25 view terorganisir per role:
│   ├── auth/      LoginView (split-screen gold gradient)
│   ├── pemohon/   Dashboard, RiwayatPengajuan, PengajuanBaru, Detail, Edit, Profile
│   ├── atasan/    PersetujuanView
│   └── admin/     Verifikasi, RiwayatVerifikasi, SuratIzin, Signing,
│                  SuratTugasDinas, Pegawai, PDDiktiSync, PdfEditor, dst.
├── components/    layout/ (MainLayout, TopNavbar, Sidebar)
│                  + PageHeader, Breadcrumb, Toast*, VerificationDetailModal,
│                  DocumentPreviewModal, PengajuanMilestone, FileUpload, PDDiktiDropdown
├── stores/        auth, pengajuan, master, notification, toast (5 Pinia store)
├── services/      api.js (3 instance Axios + interceptor 401/403/419)
└── router/        index.js (role guard + redirect berbasis role)
```

### Pola Arsitektur
- **Role-based access** di router (`meta.roles`) + sidebar + redirect pasca-login
- **Modal Pattern** (Teleport) untuk detail — bukan halaman terpisah
- **Token expiry** di-manage store: expiry 3 jam + polling cek tiap 30s + warning ≤5 menit
- **Caching notifikasi** (TTL 30s/60s) untuk kurangi beban polling
- **Detail view reuse**: `/pengajuan/:id` bisa diakses semua role (permission server-side)

### Redirect Setelah Login
| Role | Tujuan |
|------|--------|
| admin_bkpsdm | `/admin/verifikasi` |
| kepala_bkpsdm | `/kepala/signing` |
| kepala unit (is_kepala_unit) | `/kepala/surat-tugas` |
| pemohon / atasan | `/dashboard` |

---

## 6. Panduan (How-To)

### Menjalankan (Development)

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Set DB credentials di .env, lalu:
php artisan migrate
php artisan db:seed
php artisan serve        # http://localhost:8000

# Frontend
cd frontend
npm install
npm run dev              # http://localhost:5173
```

### Akun Demo (password: `password`)

| Role | Email |
|------|-------|
| Pemohon | `drajat@disdik.go.id` |
| Atasan (+ kepala unit) | `kadisdik@disdik.go.id` |
| Admin BKPSDM | `admin@bkpsdm.go.id` |
| Kepala BKPSDM | `kepala@bkpsdm.go.id` |

### Deploy (cPanel) — lihat `DEPLOYMENT.md`

1. `npm run build` → upload `frontend/dist/` ke `public_html/`
2. Upload `backend/` ke `public_html/api/` (exclude vendor/node_modules/storage)
3. Set `.env` production, lalu:
   ```bash
   composer install --no-dev
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   ```

### Struktur Folder cPanel
```
public_html/
├── api/                    # Backend Laravel
│   ├── app/ bootstrap/ config/ database/
│   ├── public/             # → document root
│   ├── resources/ routes/ storage/ vendor/
│   ├── .env artisan composer.json
└── (isi frontend dist/)    # Frontend Vue
    ├── index.html
    └── assets/
```

---

## 7. Temuan & Risiko Penting ⚠️

### Dokumentasi vs Kode
1. **Dokumentasi usang (drift).** CLAUDE.md menyebut alur lama (atasan approval, SuratTugasMandiri auto-create, Kepala Dinas buat Surat Tugas). Kode aktual: alur disederhanakan tanpa approval atasan, SuratTugasDinas dibuat **Admin** setelah Surat Izin signed, SuratTugasMandiri adalah langkah terpisah.

### Bug & Issue Kode
2. **TTE palsu** — bukan tanda tangan kriptografis (lihat §4). Jika butuh legalitas BSrE asli, perlu integrasi nyata.
3. **Notifikasi misinformasi** — `ApprovalController.php:88` mengirim notif ke kepala unit "buat Surat Tugas", tapi `SuratTugasDinasController::store():103` menolak selain admin. Kepala unit terima tugas yang tak bisa dikerjakan.
4. **Bug format nomor surat** — `nomor_surat` disimpan full `800.1.3.1/N/BKPSDM/Thn`, lalu accessor `getFullNomorSuratAttribute()` menambah `/DK/bulan/tahun` lagi → tampil dobel di template Surat Izin.
5. **Race condition** pembuatan nomor (pengajuan & surat) tanpa locking → bisa duplikat saat concurrent.
6. **Dead code signing path** — `SuratIzinBelajarController::store()` set `status='signed'` langsung, jadi method `sign()` terpisah (gated `canBeSigned()` butuh `'draft'`) tidak akan pernah berhasil di record yang dibuat via `store()`.
7. **Inconsistent path resolution** — `SuratTugasMandiriController::sign()` pakai `public_path('images/...')` sementara `generatePdf()` di kelas sama pakai `Storage::disk('public')->path(...)`.

### Keamanan
8. **Keamanan jaringan** — `withoutVerifying()`/`verify=false` di QR/Barcode/SIMPEG calls + kredensial SIMPEG hard-coded (`admin/Admin123`) → rentan MITM.
9. **Token di URL** — endpoint download/preview publik pakai `?token=` → bocor ke log server/Referer.
10. **Tidak ada validasi server-side kelengkapan dokumen** saat approve — aturan "lengkap dulu baru approve" hanya ada di UI. API langsung bisa approve tanpa dokumen.

### Reliability
11. **Ketergantungan API eksternal** — bila `api.qrserver.com`/`barcodeapi.org` down, signing/preview/download gagal 500 (fallback mengembalikan URL, bukan file).
12. **Debug `console.log`** masih ada di kode produksi (router, auth, interceptor).

---

## 8. Referensi File Kunci

| Aspek | File |
|-------|------|
| API Routes | `backend/routes/api.php` |
| Pengajuan Workflow | `backend/app/Http/Controllers/PengajuanController.php` |
| Verifikasi Dokumen | `backend/app/Http/Controllers/ApprovalController.php` |
| Surat Izin + TTE | `backend/app/Http/Controllers/SuratIzinBelajarController.php` |
| Surat Tugas Dinas | `backend/app/Http/Controllers/SuratTugasDinasController.php` |
| Auth | `backend/app/Http/Controllers/AuthController.php` |
| Model Pengajuan | `backend/app/Models/Pengajuan.php` |
| Services | `backend/app/Services/` (QrCode, Barcode, PDDikti, Simpeg) |
| Frontend Router | `frontend/src/router/index.js` |
| API Service | `frontend/src/services/api.js` |
| Auth Store | `frontend/src/stores/auth.js` |
| Layout | `frontend/src/components/layout/` (MainLayout, TopNavbar, Sidebar) |
| PDF Templates | `backend/resources/views/pdf/` |
| Dokumentasi Bisnis | `docs/business-flow.md`, `docs/user-role-permissions.md` |
| Deploy | `DEPLOYMENT.md` |

---

*Dokumen ini dihasilkan dari analisis kode aktual per Juni 2026. Beberapa bagian CLAUDE.md di repo sudah usang — rujuk dokumen ini untuk keadaan terkini, atau verifikasi ulang ke kode sumber.*
