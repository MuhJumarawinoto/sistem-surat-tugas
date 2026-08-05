---
title: "Manual Book Sistem Informasi Tugas Belajar Mandiri"
subtitle: "SI-TEMA CANTIK — BKPSDM Kabupaten Sukabumi"
author: "Badan Kepegawaian dan Pengembangan SDM Kabupaten Sukabumi"
date: "2026"
lang: "id"
documentclass: "report"
geometry: "margin=2.5cm"
fontsize: "11pt"
---

\newpage

# BAB 1 — PENDAHULUAN

## 1.1 Tentang Sistem

**Sistem Informasi Tugas Belajar Mandiri dan Pencantuman Gelar Akademik (SI-TEMA CANTIK)** adalah aplikasi berbasis web yang dikelola oleh Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Kabupaten Sukabumi. Sistem ini digunakan untuk mengelola pengajuan izin belajar mandiri bagi Pegawai Negeri Sipil (PNS) di lingkungan Pemerintah Kabupaten Sukabumi secara elektronik, cepat, dan transparan.

## 1.2 Fitur Utama

1. **Pengajuan Izin Belajar Online** — PNS dapat mengajukan izin belajar mandiri secara mandiri.
2. **Unggah Dokumen Digital** — Seluruh persyaratan diunggah dalam bentuk digital.
3. **Verifikasi Elektronik** — Admin BKPSDM memverifikasi kelengkapan dokumen secara online.
4. **Surat Otomatis** — Surat Izin Belajar & Surat Tugas Belajar dibuat sistem dengan penomoran otomatis.
5. **Tanda Tangan Elektronik (TTE)** — Surat ditandatangani secara elektronik oleh Kepala BKPSDM.
6. **Verifikasi QR Code** — Keaslian surat dapat diverifikasi publik melalui QR code.
7. **Integrasi PDDikti** — Data perguruan tinggi & program studi terisi otomatis.
8. **Integrasi SIMPEG** — Data pegawai tersinkron dengan sistem kepegawaian.

## 1.3 Peran Pengguna (Role)

| Role | Pengguna | Tugas Utama |
|------|----------|-------------|
| **Pemohon** | PNS umum | Membuat pengajuan, mengunggah dokumen, mengunduh surat |
| **Atasan / Kepala Dinas** | Kepala OPD | Membuat Surat Tugas Belajar untuk pegawai di unit kerjanya |
| **Kepala Unit** | Penanggung jawab unit | Mengajukan izin belajar **mewakili pegawai** di unit kerjanya (bisa lebih dari satu pegawai) |
| **Admin BKPSDM** | Staf BKPSDM | Verifikasi dokumen, manajemen pegawai, manajemen surat |
| **Kepala BKPSDM** | Kepala Badan | Menandatangani Surat Izin Belajar dengan TTE |

## 1.4 Alur Singkat Proses

```
Pemohon buat & kirim pengajuan
        ↓
Admin BKPSDM verifikasi dokumen
        ↓
Kepala BKPSDM generate Surat Izin Belajar + TTE
        ↓
Admin BKPSDM buat Surat Tugas Belajar
        ↓
Selesai → Pemohon unduh surat
```

## 1.5 Persyaratan Sistem

- Peramban (browser) modern: Google Chrome, Mozilla Firefox, atau Microsoft Edge versi terbaru.
- Koneksi internet stabil.
- Akun pengguna yang telah didaftarkan oleh Admin BKPSDM.

\newpage

# BAB 2 — LOGIN & AUTENTIKASI

## 2.1 Halaman Login

Untuk mengakses sistem, buka alamat aplikasi pada peramban. Halaman login akan tampil sebagai berikut. Pengguna dapat masuk menggunakan **NIP** (sesuai SIMPEG) atau **alamat email** yang terdaftar.

![Halaman Login](screenshots/desktop/00-autentikasi/01-halaman-login.png){ width=90% }

## 2.2 Mengisi Form Login

1. Masukkan **NIP atau Email** pada kolom pertama.
2. Masukkan **Kata Sandi (Password)** pada kolom kedua.
3. Klik tombol **Masuk**.

![Form Login Terisi](screenshots/desktop/00-autentikasi/02-login-terisi.png){ width=90% }

> **Catatan:** Setelah login berhasil, sistem akan mengarahkan pengguna ke halaman utama sesuai peran (role) masing-masing.

\newpage

# BAB 3 — PANDUAN PEMOHON (PNS)

Bab ini menjelaskan penggunaan sistem bagi PNS selaku pemohon izin belajar mandiri.

## 3.1 Dashboard

Halaman utama berisi ringkasan status pengajuan dalam bentuk kartu statistik (Draft, Pending, Terverifikasi, Disetujui, Ditolak, Selesai), daftar pengajuan terbaru, serta progres (milestone) dari setiap pengajuan.

![Dashboard Pemohon](screenshots/desktop/01-pemohon/01-dashboard.png){ width=100% }

## 3.2 Riwayat Pengajuan

Menampilkan seluruh daftar pengajuan yang pernah dibuat oleh pemohon. Tersedia fitur pencarian dan filter berdasarkan status. Pada halaman ini pemohon dapat membuka detail, mengedit draft, mencabut berkas, atau menghapus draft.

![Riwayat Pengajuan](screenshots/desktop/01-pemohon/02-riwayat-pengajuan.png){ width=100% }

## 3.3 Membuat Pengajuan Baru

Untuk membuat pengajuan baru, klik tombol **Buat Pengajuan Baru**. Isi data program studi dan perguruan tinggi (terintegrasi PDDikti untuk auto-fill), lalu unggah seluruh dokumen persyaratan sesuai jenis dokumen yang ditentukan.

![Buat Pengajuan Baru](screenshots/desktop/01-pemohon/03-buat-pengajuan-baru.png){ width=100% }

## 3.4 Detail Pengajuan

Halaman detail menampilkan informasi lengkap pengajuan, status terkini, progres milestone, daftar dokumen beserta status verifikasinya, serta tombol aksi sesuai status (edit, kirim, cabut berkas, atau unduh surat).

![Detail Pengajuan](screenshots/desktop/01-pemohon/04-detail-pengajuan.png){ width=100% }

## 3.5 Edit Pengajuan (Draft)

Pengajuan berstatus **Draft** masih dapat diedit. Pemohon dapat memperbarui data program studi maupun dokumen sebelum dikirim untuk diverifikasi.

![Edit Pengajuan](screenshots/desktop/01-pemohon/05-edit-pengajuan.png){ width=100% }

## 3.6 Profil Pengguna

Menampilkan data diri pemohon (nama, NIP, pangkat/golongan, jabatan, unit kerja). Pemohon dapat memperbarui informasi kontak seperti nomor HP dan alamat.

![Profil](screenshots/desktop/01-pemohon/06-profil.png){ width=100% }

## 3.7 Notifikasi

Seluruh pemberitahuan sistem (status pengajuan, surat terbit, dll) tersedia di halaman notifikasi maupun ikon lonceng pada bagian atas.

![Notifikasi](screenshots/desktop/01-pemohon/07-notifikasi.png){ width=100% }

\newpage

# BAB 4 — PANDUAN ATASAN / KEPALA DINAS

Atasan (Kepala OPD/Dinas) berperan ganda: dapat mengajukan izin belajar untuk diri sendiri, sekaligus membuat **Surat Tugas Belajar** untuk pegawai di unit kerjanya.

## 4.1 Dashboard Atasan

Tampilan dashboard atasan serupa dengan pemohon, menampilkan statistik pengajuan pribadi.

![Dashboard Atasan](screenshots/desktop/02-atasan/01-dashboard.png){ width=100% }

## 4.2 Riwayat Pengajuan

![Riwayat Pengajuan Atasan](screenshots/desktop/02-atasan/02-riwayat-pengajuan.png){ width=100% }

## 4.3 Profil Atasan

![Profil Atasan](screenshots/desktop/02-atasan/03-profil.png){ width=100% }

## 4.4 Surat Tugas Belajar (Kepala Dinas)

Kepala Dinas dapat melihat daftar pengajuan dari pegawai di unit kerjanya yang telah ditandatangani, kemudian membuat **Surat Tugas Belajar** dengan penomoran otomatis. Surat ini menjadi dasar penerbitan Surat Izin Belajar oleh BKPSDM.

![Surat Tugas Dinas](screenshots/desktop/02-atasan/04-surat-tugas-dinas.png){ width=100% }

## 4.5 Kepala Unit Mewakili Pegawai (Fitur Perwakilan)

Selain peran di atas, **Kepala Unit Kerja** (penanggung jawab OPD/Bidang) memiliki kemampuan khusus untuk **mengajukan izin belajar atas nama pegawai** di unit kerjanya. Fitur ini sangat berguna ketika seorang Kepala Unit ingin membantu mengurus pengajuan beberapa pegawai sekaligus secara terpusat, tanpa harus pegawai bersangkutan masing-masing login dan mengajukan sendiri.

**Hal-hal penting tentang fitur perwakilan:**

- Kepala Unit **hanya dapat membuat pengajuan untuk pegawai yang berada di unit kerja yang sama** dengannya. Sistem menolak pembuatan pengajuan untuk pegawai unit lain (alasan keamanan).
- Pegawai yang diajukan **akan menerima notifikasi otomatis** bahwa pengajuannya dibuat oleh Kepala Unitnya.
- Pada halaman detail pengajuan akan tampil keterangan **"dibuat oleh [nama Kepala Unit]"**.
- Satu Kepala Unit **dapat mengajukan untuk lebih dari satu pegawai** dengan mengulangi langkah pembuatan pengajuan (satu pengajuan untuk satu pegawai).

![Dashboard Kepala Unit](screenshots/desktop/06-kepala-unit-wakil/01-dashboard-kepala-unit.png){ width=100% }

## 4.6 Membuat Pengajuan Mewakili Pegawai

1. Login sebagai Kepala Unit, lalu buka menu **Buat Pengajuan Baru**.
2. Pada bagian atas form terdapat kolom **"Pegawai yang akan diajukan izin belajar"** berisi daftar pegawai aktif di unit kerja Anda.
3. Pilih pegawai yang akan diwakili dari daftar dropdown.

![Form Pilih Pegawai](screenshots/desktop/06-kepala-unit-wakil/02-form-pilih-pegawai.png){ width=100% }

Daftar dropdown memperlihatkan seluruh pegawai di unit kerja Anda beserta NIP-nya. Jika daftar belum termuat, klik ikon **refresh** di sebelah label untuk memuat ulang.

![Daftar Pegawai di Unit Kerja](screenshots/desktop/06-kepala-unit-wakil/03-dropdown-daftar-pegawai.png){ width=100% }

4. Isi data program studi dan perguruan tinggi (terintegrasi PDDikti).
5. Unggah dokumen persyaratan untuk pegawai yang dipilih.
6. Klik **Simpan Draft** atau **Kirim Pengajuan**.

> Dokumen lampiran yang diunggah akan dikaitkan dengan pegawai yang dipilih, bukan dengan akun Kepala Unit.

## 4.7 Mengajukan untuk Lebih dari Satu Pegawai

Untuk mengajukan izin belajar bagi **banyak pegawai** sekaligus, Kepala Unit cukup **mengulangi proses pembuatan pengajuan** — setiap pegawai memiliki pengajuan tersendiri. Contohnya, pada gambar di bawah terdapat dua pengajuan untuk dua pegawai berbeda (ARIE dan ARIES), keduanya dibuat oleh Kepala Unit yang sama.

![Riwayat Pengajuan Mewakili](screenshots/desktop/06-kepala-unit-wakil/04-riwayat-mewakili-pegawai.png){ width=100% }

## 4.8 Detail Pengajuan Hasil Perwakilan

Pada halaman detail pengajuan yang dibuat oleh Kepala Unit, akan tampil kotak keterangan berwarna yang menunjukkan **siapa pembuat pengajuan**. Hal ini memungkinkan Admin BKPSDM maupun pegawai bersangkutan mengetahui bahwa pengajuan diajukan melalui perwakilan Kepala Unit.

![Detail Pengajuan Mewakili](screenshots/desktop/06-kepala-unit-wakil/05-detail-mewakili-pegawai.png){ width=100% }

\newpage

# BAB 5 — PANDUAN ADMIN BKPSDM

Admin BKPSDM adalah pengelola utama sistem: verifikasi dokumen, manajemen pegawai, serta pengelolaan surat.

## 5.1 Verifikasi Dokumen

Halaman utama admin. Menampilkan daftar pengajuan yang berstatus menunggu verifikasi lengkap dengan daftar dokumen lampiran dan status verifikasinya. Admin dapat membuka detail untuk memverifikasi tiap dokumen.

![Verifikasi Dokumen](screenshots/desktop/03-admin-bkpsdm/01-verifikasi-dokumen.png){ width=100% }

## 5.2 Detail Verifikasi

Pada halaman detail, admin dapat menandai setiap dokumen sebagai **Lengkap** atau **Tidak Lengkap** beserta catatan, melihat rantai verifikasi, pratinjau dokumen, lalu **menyetujui** atau **menolak** pengajuan.

![Detail Verifikasi](screenshots/desktop/03-admin-bkpsdm/02-detail-verifikasi.png){ width=100% }

## 5.3 Riwayat Verifikasi

Menampilkan pengajuan yang sudah selesai diverifikasi (status verified, signed, atau selesai), lengkap dengan tombol unduh Surat Izin Belajar dan Surat Tugas Belajar.

![Riwayat Verifikasi](screenshots/desktop/03-admin-bkpsdm/03-riwayat-verifikasi.png){ width=100% }

## 5.4 Surat Izin Belajar

Daftar Surat Izin Belajar yang telah diterbitkan dan ditandatangani. Admin dapat melihat pratinjau dan mengunduh surat.

![Surat Izin Belajar](screenshots/desktop/03-admin-bkpsdm/04-surat-izin-belajar.png){ width=100% }

## 5.5 Surat Tugas Belajar

Daftar pengajuan berstatus *signed* yang siap dibuatkan Surat Tugas Belajar. Tanggal TTE otomatis diambil dari Surat Izin Belajar.

![Surat Tugas Belajar](screenshots/desktop/03-admin-bkpsdm/05-surat-tugas-belajar.png){ width=100% }

## 5.6 Surat Tugas Mandiri

Daftar Surat Tugas Mandiri yang dibuat otomatis bersamaan dengan Surat Izin Belajar.

![Surat Tugas Mandiri](screenshots/desktop/03-admin-bkpsdm/06-surat-tugas-mandiri.png){ width=100% }

## 5.7 Detail Surat Tugas Mandiri

Halaman detail menampilkan pratinjau surat, informasi penandatanganan (TTE), serta QR code untuk verifikasi keaslian.

![Detail Surat Tugas Mandiri](screenshots/desktop/03-admin-bkpsdm/07-detail-surat-tugas-mandiri.png){ width=100% }

## 5.8 Data Pegawai

Manajemen data seluruh pegawai: menambah, mengedit, menghapus, serta fitur impor massal dari file JSON (format SIMPEG atau standar). Tersedia juga penugasan atasan langsung dan kategori jabatan.

![Data Pegawai](screenshots/desktop/03-admin-bkpsdm/08-data-pegawai.png){ width=100% }

## 5.9 Jenis Dokumen

Konfigurasi dinamis jenis dan jumlah dokumen persyaratan pengajuan. Admin dapat menambah, mengubah urutan, atau menonaktifkan jenis dokumen.

![Jenis Dokumen](screenshots/desktop/03-admin-bkpsdm/09-jenis-dokumen.png){ width=100% }

## 5.10 Sinkronisasi PDDikti

Panel untuk mensinkronkan data perguruan tinggi dan program studi dari PDDikti ke basis data lokal, sehingga mempercepat pengisian form pengajuan.

![Sinkronisasi PDDikti](screenshots/desktop/03-admin-bkpsdm/10-pddikti-sync.png){ width=100% }

## 5.11 Editor PDF

Utilitas pratinjau dan penyusunan tata letak surat dalam format PDF sebelum dicetak.

![Editor PDF](screenshots/desktop/03-admin-bkpsdm/11-pdf-editor.png){ width=100% }

\newpage

# BAB 6 — PANDUAN KEPALA BKPSDM

Kepala BKPSDM berwenang menerbitkan dan menandatangani Surat Izin Belajar menggunakan Tanda Tangan Elektronik (TTE).

## 6.1 Surat Perlu TTE

Daftar pengajuan berstatus *verified* yang siap dibuatkan Surat Izin Belajar. Kepala BKPSDM cukup menekan tombol **Generate & TTE** dan surat langsung terbit dengan status *signed* beserta QR code verifikasi.

![Surat Perlu TTE](screenshots/desktop/04-kepala-bkpsdm/01-surat-perlu-tte.png){ width=100% }

## 6.2 Detail TTE

Halaman detail menampilkan pratinjau surat yang telah ditandatangani, informasi penandatangan, QR code, serta tombol unduh.

![Detail TTE](screenshots/desktop/04-kepala-bkpsdm/02-detail-signing.png){ width=100% }

## 6.3 Riwayat TTE

Riwayat seluruh surat yang telah ditandatangani, beserta tanggal penandatanganan.

![Riwayat TTE](screenshots/desktop/04-kepala-bkpsdm/03-riwayat-tte.png){ width=100% }

\newpage

# BAB 7 — VERIFIKASI PUBLIK

## 7.1 Verifikasi Keaslian Surat

Masyarakat umum dapat memverifikasi keaslian surat yang diterbitkan dengan memindai QR code pada surat atau memasukkan kode verifikasi secara manual pada halaman publik. Sistem akan menampilkan informasi surat beserta status keabsahannya.

![Halaman Verifikasi QR](screenshots/desktop/05-publik/01-verifikasi-qr.png){ width=90% }

\newpage

# LAMPIRAN — Akun Demo

Berikut akun demo yang dapat digunakan untuk uji coba sistem:

| Role | Email / NIP | Kata Sandi |
|------|-------------|------------|
| Pemohon | `drajat@disdik.go.id` | `password` |
| Atasan / Kepala Dinas | `bkpsdm@sipintar.go.id` | `password` |
| Kepala Unit (mewakili pegawai) | `bidang-kdh@sipintar.go.id` | `password` |
| Admin BKPSDM | `admin@bkpsdm.go.id` | `password` |
| Kepala BKPSDM | `kepala@bkpsdm.go.id` | `password` |

---

*Manual Book ini disusun untuk Panduan Pengguna Sistem Informasi Tugas Belajar Mandiri — BKPSDM Kabupaten Sukabumi.*
