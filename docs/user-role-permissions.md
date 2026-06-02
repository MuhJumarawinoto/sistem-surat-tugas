# User Role & Permissions - SIPINTAR

Dokumen ini menjelaskan hak akses setiap role dalam Sistem Surat Izin Belajar Mandiri.

---

## 1. PEMOHON (PNS)

### 🟢 BISA DILAKUKAN

| Fitur | Aksi |
|-------|------|
| **Authentication** | Login, Logout, Lihat Profile |
| **Pengajuan** | • Buat pengajuan baru (draft)<br>• Edit pengajuan (status draft/ditolak)<br>• Submit pengajuan<br>• Lihat daftar pengajuan<br>• Lihat detail pengajuan |
| **Dokumen** | • Upload 9 dokumen persyaratan<br>• Hapus dokumen (status draft)<br>• Lihat dokumen yang sudah diupload |
| **PDDikti** | • Cari perguruan tinggi<br>• Cari program studi |
| **Notifikasi** | • Lihat notifikasi<br>• Tandai sudah dibaca |
| **Surat** | • Download surat tugas (jika sudah signed) |

### 🔴 TIDAK BISA DILAKUKAN

| Fitur | Keterangan |
|-------|------------|
| Approval | Tidak bisa approve/reject pengajuan |
| Verifikasi | Tidak bisa verifikasi dokumen |
| Generate Surat | Tidak bisa generate surat tugas |
| Signing | Tidak bisa TTE surat |
| Admin Data | Tidak bisa akses data pegawai, sync SIMPEG |
| Admin PDDikti | Tidak bisa sync data PDDikti |

### 📄 Halaman yang Bisa Diakses
- `/dashboard` - Dashboard Pemohon
- `/pengajuan` - Daftar Riwayat Pengajuan
- `/pengajuan/baru` - Buat Pengajuan Baru
- `/pengajuan/{id}` - Detail Pengajuan
- `/pengajuan/{id}/edit` - Edit Pengajuan (draft/ditolak)
- `/profile` - Profile Saya
- `/notifications` - Notifikasi

---

## 2. ATASAN LANGSUNG (Kepala OPD)

### 🟢 BISA DILAKUKAN

| Fitur | Aksi |
|-------|------|
| **Authentication** | Login, Logout, Lihat Profile |
| **Pengajuan** | • Lihat daftar pengajuan dari unit kerja<br>• Lihat detail pengajuan<br>• Lihat dokumen persyaratan |
| **Approval** | • Approve pengajuan<br>• Reject pengajuan dengan catatan |
| **Notifikasi** | • Lihat notifikasi<br>• Tandai sudah dibaca |
| **PDDikti** | • Cari perguruan tinggi<br>• Cari program studi |
| **Surat** | • Lihat preview surat (jika ada) |

### 🔴 TIDAK BISA DILAKUKAN

| Fitur | Keterangan |
|-------|------------|
| Buat Pengajuan Lain | ✅ **BOLEH** buat pengajuan untuk DIRI SENDIRI (dengan approval PPK/Bupati) |
| Edit Pengajuan Orang Lain | Tidak bisa edit pengajuan bawahan (hanya approve/reject) |
| Verifikasi Admin | Tidak bisa verifikasi dokumen tahap admin |
| Generate Surat | Tidak bisa generate surat tugas |
| Signing | Tidak bisa TTE surat |
| Admin Data | Tidak bisa akses data pegawai, sync SIMPEG |
| Admin PDDikti | Tidak bisa sync data PDDikti |

### 📝 Catatan Penting
Berdasarkan regulasi yang berlaku, **Atasan BOLEH mengajukan izin belajar** untuk diri sendiri dengan ketentuan:
- Memerlukan rekomendasi/izin dari atasan yang lebih tinggi (PPK/Bupati)
- Tidak berkonflik dengan kebutuhan organisasi
- Ada pegawai pengganti selama menjalani pendidikan
| Verifikasi Admin | Tidak bisa verifikasi dokumen tahap admin |
| Generate Surat | Tidak bisa generate surat tugas |
| Signing | Tidak bisa TTE surat |
| Admin Data | Tidak bisa akses data pegawai, sync SIMPEG |
| Admin PDDikti | Tidak bisa sync data PDDikti |

### 📄 Halaman yang Bisa Diakses
- `/dashboard` - Dashboard Atasan
- `/pengajuan` - Daftar Riwayat Pengajuan (milik sendiri)
- `/pengajuan/baru` - Buat Pengajuan Baru (untuk diri sendiri)
- `/pengajuan/{id}` - Detail Pengajuan
- `/pengajuan/{id}/edit` - Edit Pengajuan (draft/ditolak, milik sendiri)
- `/atasan/persetujuan` - Daftar Pengajuan untuk Disetujui (unit kerja)
- `/profile` - Profile Saya
- `/notifications` - Notifikasi

---

## 3. ADMIN BKPSDM

### 🟢 BISA DILAKUKAN

| Fitur | Aksi |
|-------|------|
| **Authentication** | Login, Logout, Lihat Profile |
| **Pengajuan** | • Lihat SEMUA pengajuan<br>• Lihat detail pengajuan<br>• Lihat dokumen persyaratan |
| **Verifikasi** | • Verifikasi kelengkapan dokumen<br>• Approve setelah verifikasi<br>• Reject jika tidak lengkap |
| **Generate Surat** | • Input rekomendasi atasan<br>• Generate nomor surat otomatis<br>• Generate PDF draft |
| **Pegawai** | • Lihat daftar semua pegawai<br>• Tambah pegawai baru<br>• Edit data pegawai<br>• Hapus pegawai<br>• Sync data dari SIMPEG |
| **PDDikti** | • Sync data universitas<br>• Sync data program studi<br>• Lihat statistik sync<br>• Hapus data sync |
| **Notifikasi** | • Lihat notifikasi<br>• Tandai sudah dibaca |
| **Surat** | • Lihat semua surat<br>• Preview surat<br>• Download surat |

### 🔴 TIDAK BISA DILAKUKAN

| Fitur | Keterangan |
|-------|------------|
| Buat Pengajuan | Tidak bisa buat pengajuan sebagai pemohon |
| Edit Pengajuan | Tidak bisa edit pengajuan pemohon |
| Approval Atasan | Tidak approve di level atasan (ini job atasan) |
| **Signing TTE** | TIDAK BISA TTE surat (hanya Kepala BKPSDM) |

### 📄 Halaman yang Bisa Diakses
- `/dashboard` - Dashboard Admin
- `/admin/verifikasi` - Verifikasi Pengajuan
- `/admin/pegawai` - Manajemen Pegawai
- `/admin/pddikti-sync` - Sync Data PDDikti
- `/admin/surat/{id}` - Preview & Generate Surat
- `/pengajuan/{id}` - Detail Pengajuan (view only)
- `/profile` - Profile Saya
- `/notifications` - Notifikasi

---

## 4. KEPALA BKPSDM

### 🟢 BISA DILAKUKAN

| Fitur | Aksi |
|-------|------|
| **Authentication** | Login, Logout, Lihat Profile |
| **Pengajuan** | • Lihat SEMUA pengajuan<br>• Lihat detail pengajuan<br>• Lihat dokumen persyaratan |
| **Signing (TTE)** | • Lihat daftar surat yang perlu ditandatangani<br>• Preview surat sebelum TTE<br>• **TTE surat dengan BSrE BSSN**<br>• Lihat riwayat surat yang sudah ditandatangani |
| **Notifikasi** | • Lihat notifikasi<br>• Tandai sudah dibaca |
| **Surat** | • Lihat semua surat<br>• Download surat |

### 🔴 TIDAK BISA DILAKUKAN

| Fitur | Keterangan |
|-------|------------|
| Buat Pengajuan | Tidak bisa buat pengajuan |
| Edit Pengajuan | Tidak bisa edit pengajuan |
| Approval Atasan | Tidak approve di level atasan |
| Verifikasi Admin | Tidak verifikasi dokumen (ini job Admin) |
| Generate Surat | Tidak generate surat (ini job Admin) |
| Admin Data | Tidak bisa akses manajemen pegawai |
| Admin PDDikti | Tidak bisa sync PDDikti |

### 📄 Halaman yang Bisa Diakses
- `/dashboard` - Dashboard Kepala
- `/kepala/signing` - Daftar Surat untuk TTE
- `/admin/surat/{id}` - Preview Surat & TTE
- `/pengajuan/{id}` - Detail Pengajuan (view only)
- `/profile` - Profile Saya
- `/notifications` - Notifikasi

---

## 5. TAMU (Belum Login)

### 🟢 BISA DILAKUKAN

| Fitur | Aksi |
|-------|------|
| **Authentication** | Login |

### 🔴 TIDAK BISA DILAKUKAN

Semua fitur sistem dikunci kecuali halaman login.

---

## Matrix Permissions

| Fitur | Tamu | Pemohon | Atasan | Admin | Kepala |
|-------|------|---------|--------|-------|--------|
| **Login** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Dashboard** | ❌ | ✅ Pemohon | ✅ Atasan | ✅ Admin | ✅ Kepala |
| **Buat Pengajuan** | ❌ | ✅ | ✅ Diri Sendiri | ❌ | ❌ |
| **Edit Pengajuan** | ❌ | ✅ Draft/Ditolak | ✅ Draft/Ditolak (Sendiri) | ❌ | ❌ |
| **Lihat Pengajuan Sendiri** | ❌ | ✅ | ✅ Unit Kerja + Sendiri | ✅ Semua | ✅ Semua |
| **Upload Dokumen** | ❌ | ✅ | ✅ (Sendiri) | ❌ | ❌ |
| **Approve Atasan** | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Verify Admin** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Generate Surat** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **TTE Surat** | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Download Surat** | ❌ | ✅ Milik Sendiri | ❌ | ✅ Semua | ✅ Semua |
| **Manajemen Pegawai** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Sync SIMPEG** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Sync PDDikti** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Lihat Profile** | ❌ | ✅ Sendiri | ✅ Sendiri | ✅ Sendiri | ✅ Sendiri |
| **Edit Profile** | ❌ | ✅ Sendiri | ✅ Sendiri | ✅ Sendiri | ✅ Sendiri |

---

## Status Transisi per Role

```
DRAFT → SUBMITTED → APPROVED_ATASAN → VERIFIED → SIGNED
         ↓              ↓                  ↓
      [Pemohon]    [Atasan]          [Admin]
                                         ↓
                                      SIGNED
                                         ↓
                                     [Kepala]
```

| Role | Bisa Ubah Status ke |
|------|---------------------|
| **Pemohon** | draft → submitted |
| **Atasan** | submitted → approved_atasan / rejected |
| **Admin** | approved_atasan → verified / rejected |
| **Kepala** | verified → signed (via TTE) |

---

*Document Version: 1.0*
*Last Updated: 2026-05-20*
