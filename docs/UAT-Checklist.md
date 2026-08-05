# User Acceptance Testing (UAT) — Sistem Surat Izin Belajar Mandiri (SI-TEMA CANTIK)

> **Tujuan:** Memastikan seluruh modul aplikasi berfungsi sesuai kebutuhan sebelum digunakan di produksi.
>
> **Cara Pakai:** Jalankan setiap skenario uji, isi kolom **Status** (PASS / FAIL / N/A), **Catatan**, dan **Tanggal**.

**Legenda Status:**
- PASS = Lolos, sesuai harapan
- FAIL = Gagal, ada bug
- N/A = Tidak relevan/tidak diuji

---

## Akun Uji (Demo)

| ID | Role | Email | Password |
|----|------|-------|----------|
| U1 | Pemohon | drajat@disdik.go.id | password |
| U2 | Atasan / Kepala Unit | kadisdik@disdik.go.id | password |
| U3 | Admin BKPSDM | admin@bkpsdm.go.id | password |
| U4 | Kepala BKPSDM | kepala@bkpsdm.go.id | password |

**Lingkungan Uji:**
- URL Frontend: ____________________
- URL API: ____________________
- Browser: ____________________
- Tanggal Mulai UAT: ____________________

---

## MODUL 1 — Autentikasi & Sesi

### 1.1 Login

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 1.1.1 | Login dengan email valid | Login U1 dengan email + password benar | Berhasil login, redirect ke Dashboard | ☐ | |
| 1.1.2 | Login dengan NIP valid | Login U1 menggunakan NIP sebagai identity | Berhasil login | ☐ | |
| 1.1.3 | Login password salah | Masukkan password salah | Muncul pesan error, tidak login | ☐ | |
| 1.1.4 | Login email tidak terdaftar | Masukkan email acak | Muncul pesan "tidak ditemukan" | ☐ | |
| 1.1.5 | Login akun non-aktif (is_active=false) | Login dengan akun yang dinonaktifkan | Muncul pesan "akun tidak aktif" | ☐ | |
| 1.1.6 | Akses halaman tanpa login | Buka `/dashboard` tanpa token | Redirect ke halaman Login | ☐ | |
| 1.1.7 | Validasi field kosong | Submit form login kosong | Muncul validasi "wajib diisi" | ☐ | |

### 1.2 Logout & Sesi

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 1.2.1 | Logout dari menu | Klik dropdown user → Logout | Berhasil logout, redirect Login, token hilang | ☐ | |
| 1.2.2 | Redirect setelah logout | Coba tombol Back browser | Tidak bisa kembali ke halaman auth | ☐ | |
| 1.2.3 | Token kedaluwarsa | Tunggu token expire (atau hapus token manual) | Auto-redirect ke Login | ☐ | |
| 1.2.4 | Session warning | Tunggu hingga sisa waktu < 5 menit | Muncul peringatan sesi | ☐ | |
| 1.2.5 | Extend sesi (aktivitas) | Lakukan aktivitas (klik/ketik) saat sesi hampir habis | Sesi diperpanjang otomatis | ☐ | |

### 1.3 Profil

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 1.3.1 | Lihat profil | Buka menu Profile | Data user tampil lengkap | ☐ | |
| 1.3.2 | Edit profil | Ubah no_hp/alamat → Simpan | Data tersimpan, tampil terupdate | ☐ | |
| 1.3.3 | Ganti password | Buka modal ganti password, isi benar | Password berubah, bisa login baru | ☐ | |
| 1.3.4 | Ganti password lama salah | Masukkan password lama salah | Muncul error, tidak berubah | ☐ | |

---

## MODUL 2 — Pemohon: Dashboard

### 2.1 Tampilan & Statistik Dashboard

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 2.1.1 | Tampilan dashboard awal | Login U1 → lihat Dashboard | Kartu statistik tampil (Draft, Pending, Terverifikasi, dst.) | ☐ | |
| 2.1.2 | Statistik akurat | Bandingkan angka dengan data di DB | Jumlah sesuai data sebenarnya | ☐ | |
| 2.1.3 | Pengajuan Terbaru | Lihat section "Pengajuan Terbaru" | Menampilkan pengajuan terbaru user | ☐ | |
| 2.1.4 | Tombol Surat (jika ada) | Cek pengajuan berstatus signed/selesai | Tombol "Surat" muncul dengan opsi download | ☐ | |
| 2.1.5 | Refresh data | Klik tombol Refresh | Data dashboard ter-update | ☐ | |
| 2.1.6 | Milestone tampil | Buka detail pengajuan | Milestone 4 langkah tampil dengan warna benar | ☐ | |

---

## MODUL 3 — Pemohon: Pengajuan (CRUD)

### 3.1 Buat Pengajuan Baru

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.1.1 | Buka form pengajuan | Klik "Buat Pengajuan Baru" | Form pengajuan tampil lengkap | ☐ | |
| 3.1.2 | Pilih jenjang pendidikan | Pilih dari dropdown jenjang | Dropdown terisi dari master data | ☐ | |
| 3.1.3 | Cari perguruan tinggi | Ketik keyword di PDDiktiDropdown | Muncul saran PT dari database lokal | ☐ | |
| 3.1.4 | Cari program studi | Pilih PT → muncul prodi | Dropdown prodi terisi | ☐ | |
| 3.1.5 | Auto-fill dari PDDikti | Pilih PT + prodi | Lokasi & akreditasi terisi otomatis | ☐ | |
| 3.1.6 | Input manual PT/prodi | Ketik manual (bukan dari daftar) | Bisa input manual | ☐ | |
| 3.1.7 | Isi tanggal rencana | Pilih rencana_mulai & selesai | Tanggal tersimpan | ☐ | |
| 3.1.8 | Validasi field wajib | Submit form kosong | Muncul validasi field wajib | ☐ | |
| 3.1.9 | Simpan draft | Isi lengkap → Simpan Draft | Status = draft, muncul di daftar | ☐ | |
| 3.1.10 | Nomor pengajuan otomatis | Cek nomor pengajuan setelah simpan | Nomor ter-generate: `IBL/{tahun}/{seq}` | ☐ | |

### 3.2 Upload Dokumen

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.2.1 | Lihat daftar dokumen wajib | Buka form/halaman dokumen | Semua jenis dokumen aktif tampil | ☐ | |
| 3.2.2 | Jumlah dokumen dinamis | Cek jumlah = jumlah jenis_dokumen aktif di DB | Jumlah sesuai konfigurasi admin | ☐ | |
| 3.2.3 | Upload PDF | Upload file .pdf (valid) | File ter-upload, muncul preview | ☐ | |
| 3.2.4 | Upload gambar | Upload file .jpg/.png | File ter-upload, preview gambar | ☐ | |
| 3.2.5 | Upload file > 5MB | Upload file lebih dari 5MB | Ditolak dengan pesan ukuran | ☐ | |
| 3.2.6 | Upload tipe tidak didukung | Upload .docx/.exe | Ditolak dengan pesan tipe | ☐ | |
| 3.2.7 | Ganti dokumen sama jenis | Upload ulang jenis yang sudah ada | Dokumen lama diganti baru | ☐ | |
| 3.2.8 | Progress bar upload | Upload file besar | Progress bar tampil | ☐ | |
| 3.2.9 | Tooltip info dokumen | Hover/klik icon info | Muncul persyaratan dokumen | ☐ | |
| 3.2.10 | Hapus dokumen | Klik hapus dokumen (status draft) | Dokumen terhapus | ☐ | |

### 3.3 Submit Pengajuan

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.3.1 | Submit dokumen lengkap | Upload semua dokumen → Submit | Status → `pending_admin`, notifikasi ke admin | ☐ | |
| 3.3.2 | Submit dokumen belum lengkap | Submit dengan dokumen kurang | Muncul konfirmasi dokumen belum lengkap | ☐ | |
| 3.3.3 | Submit dengan konfirmasi | Klik Submit → konfirmasi modal | Modal konfirmasi muncul | ☐ | |
| 3.3.4 | Notifikasi ke admin | Cek notifikasi admin (U3) | Admin terima notif "pengajuan baru" | ☐ | |

### 3.4 Edit & Hapus Pengajuan

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.4.1 | Edit pengajuan draft | Edit pengajuan status draft | Bisa edit semua field | ☐ | |
| 3.4.2 | Edit pengajuan pending | Coba edit pengajuan pending | Tidak bisa edit (disabled) | ☐ | |
| 3.4.3 | Hapus pengajuan draft | Hapus pengajuan draft | Status → `dicabut`, pindah ke Riwayat | ☐ | |
| 3.4.4 | Hapus pengajuan pending | Coba hapus pengajuan pending | Tidak bisa (harus cabut dulu) | ☐ | |

### 3.5 Cabut & Pulihkan

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.5.1 | Cabut berkas pending | Klik "Cabut Berkas" di pending | Status kembali `draft` | ☐ | |
| 3.5.2 | Cabut berkas verified | Klik "Cabut Berkas" di verified | Status kembali `draft` | ☐ | |
| 3.5.3 | Cabut berkas signed | Coba cabut berkas signed | Tidak bisa (disabled) | ☐ | |
| 3.5.4 | Pulihkan dari dicabut | Buka Riwayat → Pulihkan | Status kembali `draft` | ☐ | |
| 3.5.5 | Edit setelah cabut | Edit pengajuan yang sudah dicabut → pulihkan | Bisa edit kembali | ☐ | |

### 3.6 Riwayat Pengajuan

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 3.6.1 | Lihat daftar riwayat | Buka menu Riwayat Pengajuan | Daftar pengajuan tampil dengan pagination | ☐ | |
| 3.6.2 | Filter by status | Pilih filter status | Daftar terfilter | ☐ | |
| 3.6.3 | Search pengajuan | Ketik keyword di search box | Hasil filter real-time | ☐ | |
| 3.6.4 | Lihat detail (modal) | Klik "Lihat" di salah satu item | Modal detail muncul | ☐ | |
| 3.6.5 | Pagination | Klik halaman berikutnya | Halaman berganti | ☐ | |

---

## MODUL 4 — Admin BKPSDM: Verifikasi Dokumen

### 4.1 Halaman Verifikasi

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 4.1.1 | Buka halaman verifikasi | Login U3 → menu Verifikasi | Daftar pengajuan pending tampil | ☐ | |
| 4.1.2 | Statistik verifikasi | Lihat kartu statistik | Angka sesuai data | ☐ | |
| 4.1.3 | Filter status | Filter by status | Daftar terfilter | ☐ | |
| 4.1.4 | Info atasan | Cek badge info atasan | Atasan pemohon tampil (atau warning jika kosong) | ☐ | |

### 4.2 Proses Verifikasi Dokumen

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 4.2.1 | Buka detail verifikasi | Klik "Verifikasi" | Modal/halaman detail tampil | ☐ | |
| 4.2.2 | Lihat data pemohon | Cek info pegawai & pendidikan | Data lengkap tampil | ☐ | |
| 4.2.3 | Preview dokumen | Klik preview dokumen | DocumentPreviewModal muncul | ☐ | |
| 4.2.4 | Tandai dokumen lengkap | Klik "Lengkap" pada dokumen | Status dokumen → lengkap | ☐ | |
| 4.2.5 | Tandai dokumen tidak lengkap | Klik "Tidak Lengkap" + catatan | Status → tidak_lengkap, catatan tersimpan | ☐ | |
| 4.2.6 | Verifikasi semua dokumen | Lengkapi semua dokumen | Tombol Approve aktif | ☐ | |
| 4.2.7 | Approve belum lengkap | Coba approve dengan dokumen belum lengkap | Tombol Approve disabled | ☐ | |

### 4.3 Approve & Reject

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 4.3.1 | Approve pengajuan | Klik "Verifikasi & Lanjutkan" | Status → verified, surat ter-generate, notif ke pemohon | ☐ | |
| 4.3.2 | Cek surat ter-generate | Cek DB/tabel surat | SuratIzinBelajar + SuratTugasMandiri + SuratTugasDinas terbuat | ☐ | |
| 4.3.3 | Reject pengajuan | Klik "Tolak" + isi alasan | Status → ditolak, notif ke pemohon | ☐ | |
| 4.3.4 | Reject tanpa alasan | Tolak tanpa isi catatan | Validasi muncul "catatan wajib" | ☐ | |

### 4.4 Kirim Pesan

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 4.4.1 | Buka modal kirim pesan | Klik "Kirim Pesan" | Modal kirim pesan muncul | ☐ | |
| 4.4.2 | Kirim pesan | Isi pesan → Kirim | Pesan terkirim, pemohon terima notifikasi | ☐ | |

### 4.5 Riwayat Verifikasi

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 4.5.1 | Buka Riwayat Verifikasi | Login U3 → menu Riwayat Verifikasi | Pengajuan verified/signed/selesai tampil | ☐ | |
| 4.5.2 | Download Surat Izin | Klik download surat izin | PDF terdownload | ☐ | |
| 4.5.3 | Download Surat Tugas | Klik download surat tugas | PDF terdownload | ☐ | |

---

## MODUL 5 — Kepala BKPSDM: Signing (TTE)

### 5.1 Halaman Signing

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 5.1.1 | Buka halaman signing | Login U4 → menu Signing | Daftar pengajuan verified (perlu TTE) tampil | ☐ | |
| 5.1.2 | Daftar kosong jika tidak ada | Jika tidak ada verified | Tampil pesan "tidak ada" | ☐ | |

### 5.2 Generate & TTE Surat Izin

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 5.2.1 | Buka detail signing | Klik pengajuan | Halaman detail signing tampil | ☐ | |
| 5.2.2 | Generate & TTE | Klik "Generate & TTE" | Surat ter-generate + signed, QR code dibuat | ☐ | |
| 5.2.3 | Nomor surat otomatis | Cek nomor surat | Format `800.1.3.1/{seq}/BKPSDM/{tahun}` | ☐ | |
| 5.2.4 | Tampilkan QR Code | Klik "Tampilkan QR Code" | QR code tampil untuk verifikasi | ☐ | |
| 5.2.5 | Preview surat | Klik preview | Surat tampil di tab baru | ☐ | |
| 5.2.6 | Download surat | Klik download | PDF terdownload dengan QR + barcode | ☐ | |
| 5.2.7 | Status pengajuan update | Cek status pengajuan setelah TTE | Status → signed | ☐ | |
| 5.2.8 | Notifikasi ke pemohon | Cek notifikasi pemohon | Pemohon terima notif surat siap | ☐ | |

### 5.3 Riwayat Signing

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 5.3.1 | Buka riwayat signing | Menu Riwayat | Surat yang sudah signed tampil | ☐ | |
| 5.3.2 | Search riwayat | Ketik keyword | Hasil terfilter | ☐ | |
| 5.3.3 | Download dari riwayat | Klik download | PDF terdownload | ☐ | |

---

## MODUL 6 — Admin BKPSDM: Surat Tugas Dinas

### 6.1 Surat Tugas Dinas (Admin)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 6.1.1 | Buka daftar surat tugas | Login U3 → menu Surat Tugas | Daftar surat tugas tampil | ☐ | |
| 6.1.2 | Tab pending | Klik tab pending | Pengajuan signed tanpa surat tampil | ☐ | |
| 6.1.3 | Buat surat tugas | Klik buat → isi form → simpan | Surat terbuat, status → selesai | ☐ | |
| 6.1.4 | Format nomor surat | Cek nomor | Format `{seq}/DK/{bulan}/{tahun}` | ☐ | |
| 6.1.5 | Fallback kepala unit | Buat surat untuk unit tanpa kepala | Otomatis pakai Kepala BKPSDM | ☐ | |
| 6.1.6 | Download PDF surat tugas | Klik download | PDF terdownload | ☐ | |

---

## MODUL 7 — Kepala Unit: Surat Tugas (Role Atasan)

### 7.1 Pengajuan untuk Staf

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 7.1.1 | Lihat pengajuan staf | Login U2 → lihat pengajuan unit kerja | Pengajuan staf di unit terlihat | ☐ | |
| 7.1.2 | Buat pengajuan untuk staf | Kepala unit buat pengajuan untuk pegawai | created_by tercatat, staf terima notif | ☐ | |

### 7.2 Surat Tugas Dinas

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 7.2.1 | Buka Surat Tugas (kepala) | Login U2 → menu Surat Tugas | Hanya surat unit kerja sendiri | ☐ | |
| 7.2.2 | Buat surat tugas dinas | Isi form → simpan | Surat terbuat | ☐ | |
| 7.2.3 | Preview PDF | Klik preview | Preview HTML tampil | ☐ | |
| 7.2.4 | Download PDF | Klik download | PDF terdownload | ☐ | |

---

## MODUL 8 — Notifikasi

### 8.1 Bell & Badge

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 8.1.1 | Badge unread count | Trigger event (mis. submit pengajuan) | Badge angka bertambah | ☐ | |
| 8.1.2 | Buka panel notifikasi | Klik bell | Dropdown panel tampil dengan list | ☐ | |
| 8.1.3 | Klik notifikasi | Klik item notifikasi | Navigate ke pengajuan terkait | ☐ | |
| 8.1.4 | Mark as read (satu) | Klik notifikasi | Status read, badge berkurang | ☐ | |
| 8.1.5 | Mark all as read | Klik "Tandai semua dibaca" | Semua read, badge = 0 | ☐ | |
| 8.1.6 | Hapus notifikasi | Klik hapus | Notifikasi terhapus | ☐ | |

### 8.2 Auto Notifier (Toast)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 8.2.1 | Toast otomatis | Trigger event penting | Toast muncul otomatis | ☐ | |
| 8.2.2 | Toast hilang otomatis | Tunggu beberapa detik | Toast hilang sendiri | ☐ | |
| 8.2.3 | Polling notifikasi | Tunggu 120 detik | Notifikasi baru terdeteksi | ☐ | |

### 8.3 Halaman Notifikasi Lengkap

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 8.3.1 | Buka halaman notifikasi | Buka menu Notifikasi | Feed pesan terpadu tampil | ☐ | |
| 8.3.2 | Filter (semua/belum dibaca) | Klik filter | Daftar terfilter | ☐ | |
| 8.3.3 | Pesan dari approval | Cek pesan reject/approve | Pesan dari admin tampil | ☐ | |

---

## MODUL 9 — Admin: Manajemen Pegawai

### 9.1 Daftar Pegawai

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 9.1.1 | Buka daftar pegawai | Login U3 → menu Pegawai | Daftar pegawai tampil dengan pagination | ☐ | |
| 9.1.2 | Search pegawai | Ketik nama/NIP | Hasil terfilter | ☐ | |
| 9.1.3 | Filter role/unit | Pilih filter | Daftar terfilter | ☐ | |
| 9.1.4 | Statistik pegawai | Lihat kartu statistik | Angka sesuai data | ☐ | |

### 9.2 CRUD Pegawai

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 9.2.1 | Edit pegawai | Klik edit → ubah data → simpan | Data tersimpan | ☐ | |
| 9.2.2 | Set kategori jabatan | Ubah jabatan_kategori dropdown | Tersimpan | ☐ | |
| 9.2.3 | Set atasan langsung | Pilih atasan_id → simpan | Relasi tersimpan | ☐ | |
| 9.2.4 | Hapus pegawai | Klik hapus → konfirmasi | Pegawai terhapus | ☐ | |
| 9.2.5 | Lihat struktur | Klik "Struktur" | Rantai atasan + bawahan tampil | ☐ | |

### 9.3 Import Pegawai

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 9.3.1 | Download template | Klik "Download Template" | File JSON terdownload | ☐ | |
| 9.3.2 | Import JSON (create) | Upload JSON → mode create | Pegawai baru terimport | ☐ | |
| 9.3.3 | Import JSON (sync) | Upload JSON → mode sync | Create + update | ☐ | |
| 9.3.4 | Import format SIMPEG | Upload file SIMPEG format | Format terdeteksi otomatis | ☐ | |
| 9.3.5 | Auto-create unit kerja | Import dengan unit kerja baru | Unit kerja terbuat otomatis | ☐ | |
| 9.3.6 | Mapping golongan→kategori | Import dengan golongan IV/a | jabatan_kategori = kabid | ☐ | |
| 9.3.7 | Hasil import | Lihat ringkasan hasil | Jumlah sukses/gagal tampil | ☐ | |

---

## MODUL 10 — Admin: Jenis Dokumen (Dinamis)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 10.1 | Buka manajemen jenis dokumen | Login U3 → menu Jenis Dokumen | Daftar jenis dokumen tampil | ☐ | |
| 10.2 | Tambah jenis dokumen | Klik tambah → isi → simpan | Jenis baru tersimpan | ☐ | |
| 10.3 | Edit jenis dokumen | Edit → ubah nama → simpan | Data terupdate | ☐ | |
| 10.4 | Nonaktifkan jenis | Set is_active = false | Jenis hilang dari form pemohon | ☐ | |
| 10.5 | Aktifkan kembali | Set is_active = true | Jenis muncul kembali di form | ☐ | |
| 10.6 | Urutan dokumen | Ubah urutan → cek form pemohon | Urutan sesuai konfigurasi | ☐ | |
| 10.7 | Hapus jenis dokumen | Klik hapus | Jenis terhapus | ☐ | |
| 10.8 | Modal close (X button) | Klik X di modal | Modal tertutup | ☐ | |
| 10.9 | Modal backdrop tidak close | Klik di luar modal | Modal TIDAK tertutup | ☐ | |

---

## MODUL 11 — Admin: PDDikti Sync

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 11.1 | Buka PDDikti Sync | Login U3 → menu PDDikti Sync | Statistik sync tampil | ☐ | |
| 11.2 | Sync universitas | Masukkan keyword → sync | PT ter-sync ke DB lokal | ☐ | |
| 11.3 | Sync prodi | Pilih PT → sync prodi | Prodi ter-sync | ☐ | |
| 11.4 | Cek di form pemohon | Cari PT di form pengajuan | PT hasil sync muncul | ☐ | |
| 11.5 | Hapus data sync | Hapus PT tertentu | PT + prodis terhapus | ☐ | |

---

## MODUL 12 — Verifikasi QR Code (Publik)

### 12.1 Halaman Verifikasi (Tanpa Login)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 12.1.1 | Buka halaman verify | Akses `/verify` tanpa login | Halaman verifikasi tampil | ☐ | |
| 12.1.2 | Input QR surat izin | Masukkan QR code surat izin valid | Muncul detail surat + badge "SAH" | ☐ | |
| 12.1.3 | Input QR surat tugas | Masukkan QR code surat tugas valid | Detail surat tugas tampil | ☐ | |
| 12.1.4 | Input QR invalid | Masukkan QR code salah/acak | Muncul pesan "tidak ditemukan" | ☐ | |
| 12.1.5 | Input nomor surat | Cari via nomor surat | Detail tampil jika valid | ☐ | |

---

## MODUL 13 — PDF & Dokumen Surat

### 13.1 Surat Izin Belajar

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 13.1.1 | Preview surat izin | Klik preview | Surat tampil di tab baru | ☐ | |
| 13.1.2 | Konten surat benar | Cek isi: nama, NIP, prodi, PT | Semua data sesuai pengajuan | ☐ | |
| 13.1.3 | QR code di PDF | Cek QR di PDF | QR code terlihat | ☐ | |
| 13.1.4 | Barcode di PDF | Cek barcode di PDF | Barcode terlihat | ☐ | |
| 13.1.5 | Dasar hukum lengkap | Cek 6 dasar hukum | Semua 6 poin tampil | ☐ | |
| 13.1.6 | TTD & nama penandatangan | Cek bagian TTD | Nama Kepala BKPSDM + NIP | ☐ | |
| 13.1.7 | Download surat izin | Klik download | PDF terdownload | ☐ | |
| 13.1.8 | URL token encode | Cek URL download | Token ter-encode (tidak ada error 500) | ☐ | |

### 13.2 Surat Tugas Mandiri

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 13.2.1 | Preview surat tugas mandiri | Klik preview | Surat tampil | ☐ | |
| 13.2.2 | Konten benar | Cek data pegawai & pendidikan | Sesuai pengajuan | ☐ | |
| 13.2.3 | Ketentuan (5 poin) | Cek ketentuan | 5 poin tampil | ☐ | |
| 13.2.4 | Download PDF | Klik download | PDF terdownload | ☐ | |

### 13.3 Surat Tugas Dinas

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 13.3.1 | Preview surat tugas dinas | Klik preview | Preview tampil (bg abu-abu) | ☐ | |
| 13.3.2 | Kop surat dinamis | Cek kop sesuai unit kerja | Nama dinas & alamat benar | ☐ | |
| 13.3.3 | Tidak ada halaman kosong | Download PDF | Tidak ada halaman 2 kosong | ☐ | |
| 13.3.4 | Font Arial | Cek font PDF | Menggunakan Arial | ☐ | |
| 13.3.5 | Download PDF | Klik download | PDF terdownload, bg putih | ☐ | |

---

## MODUL 14 — Pemohon: Download Surat

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 14.1 | Tombol Surat di Dashboard | Login U1 → lihat pengajuan signed/selesai | Tombol "Surat" muncul | ☐ | |
| 14.2 | Menu download Surat Izin | Klik Surat → Surat Izin Belajar | PDF terdownload | ☐ | |
| 14.3 | Menu download Surat Tugas | Klik Surat → Surat Tugas Mandiri | PDF terdownload | ☐ | |
| 14.4 | Surat belum ready | Pengajuan belum signed | Tombol Surat tidak muncul | ☐ | |
| 14.5 | Download di detail pengajuan | Buka detail → download surat | PDF terdownload | ☐ | |

---

## MODUL 15 — Keamanan & Akses (RBAC)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 15.1 | Pemohon akses halaman admin | U1 buka `/admin/verifikasi` | Redirect ke dashboard (tidak boleh) | ☐ | |
| 15.2 | Admin akses signing | U3 buka `/kepala/signing` | Redirect ke dashboard | ☐ | |
| 15.3 | Lihat pengajuan orang lain | U1 akses `/pengajuan/{id lain}` via API | Ditolak (403) | ☐ | |
| 15.4 | Edit pengajuan orang lain | U1 PUT pengajuan user lain | Ditolak | ☐ | |
| 15.5 | Hapus pengajuan orang lain | U1 DELETE pengajuan lain | Ditolak | ☐ | |
| 15.6 | API tanpa token | Curl API tanpa Authorization | 401 Unauthorized | ☐ | |
| 15.7 | Akses endpoint admin non-admin | Pemohon POST `/admin/*` | 403 Forbidden | ☐ | |
| 15.8 | Manipulasi role di frontend | Ubah role di localStorage | Backend tetap tolak (server-side check) | ☐ | |
| 15.9 | Kepala unit lihat unit lain | U2 akses surat unit kerja lain | Hanya unit sendiri yang tampil | ☐ | |

---

## MODUL 16 — Concurrency & Edge Cases

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 16.1 | Nomor surat unik | Generate beberapa surat bersamaan | Tidak ada nomor duplikat | ☐ | |
| 16.2 | Nomor pengajuan unik | Submit beberapa pengajuan bersamaan | Nomor berurutan, tidak duplikat | ☐ | |
| 16.3 | Upload file corrupt | Upload PDF corrupt | Error handling baik | ☐ | |
| 16.4 | Koneksi terputus saat upload | Putuskan internet saat upload | Error handling (tidak hang) | ☐ | |
| 16.5 | Session timeout saat isi form | Tunggu expire saat isi form | Redirect login, data tidak tersimpan hilang wajar | ☐ | |
| 16.6 | Karakter khusus di nama | Input nama dengan tanda kutik | Tersimpan dengan benar (escaped) | ☐ | |

---

## MODUL 17 — Responsif & Cross-Browser

### 17.1 Responsif (Mobile)

| No | Skenario Uji | Langkah | Hasil yang Diharapkan | Status | Catatan |
|----|-------------|---------|----------------------|--------|---------|
| 17.1.1 | Tampilan mobile (< 640px) | Buka di HP / DevTools mobile | Layout menyesuaikan | ☐ | |
| 17.1.2 | Sidebar mobile | Klik hamburger | Sidebar off-canvas tampil | ☐ | |
| 17.1.3 | Tabel scroll horizontal | Lihat tabel di mobile | Bisa scroll horizontal | ☐ | |
| 17.1.4 | Menu dots (mobile) | Klik 3 titik di list | Dropdown menu muncul | ☐ | |
| 17.1.5 | Form mobile | Isi form pengajuan di HP | Form bisa diisi dengan nyaman | ☐ | |
| 17.1.6 | Modal mobile | Buka modal di HP | Modal fit & scroll baik | ☐ | |

### 17.2 Cross-Browser

| No | Browser | Status | Catatan |
|----|---------|--------|---------|
| 17.2.1 | Google Chrome (latest) | ☐ | |
| 17.2.2 | Mozilla Firefox (latest) | ☐ | |
| 17.2.3 | Microsoft Edge (latest) | ☐ | |
| 17.2.4 | Safari (iOS) | ☐ | |

---

## MODUL 18 — Alur End-to-End (E2E)

### 18.1 Skenario Lengkap (Happy Path)

| No | Langkah | Oleh | Hasil yang Diharapkan | Status | Catatan |
|----|---------|------|----------------------|--------|---------|
| 18.1.1 | Buat pengajuan + upload dokumen | Pemohon | Status: draft | ☐ | |
| 18.1.2 | Submit pengajuan | Pemohon | Status: pending_admin + notif admin | ☐ | |
| 18.1.3 | Verifikasi dokumen | Admin | Semua dokumen: lengkap | ☐ | |
| 18.1.4 | Approve pengajuan | Admin | Status: verified + surat ter-generate | ☐ | |
| 18.1.5 | Cek milestone | Pemohon | Milestone: Verifikasi selesai | ☐ | |
| 18.1.6 | (Auto) Generate & TTE | Sistem/Kepala | Status: signed + QR dibuat | ☐ | |
| 18.1.7 | Cek notifikasi pemohon | Pemohon | Terima notif surat siap | ☐ | |
| 18.1.8 | Download surat izin | Pemohon | PDF valid dengan QR | ☐ | |
| 18.1.9 | Download surat tugas mandiri | Pemohon | PDF valid | ☐ | |
| 18.1.10 | Verifikasi QR publik | Umum | Badge "SAH" di halaman verify | ☐ | |
| 18.1.11 | Cek final status | Pemohon | Status: selesai/completed | ☐ | |

### 18.2 Skenario Reject

| No | Langkah | Oleh | Hasil yang Diharapkan | Status | Catatan |
|----|---------|------|----------------------|--------|---------|
| 18.2.1 | Submit pengajuan | Pemohon | pending_admin | ☐ | |
| 18.2.2 | Verifikasi dokumen tidak lengkap | Admin | Dokumen: tidak_lengkap + catatan | ☐ | |
| 18.2.3 | Reject pengajuan | Admin | Status: ditolak + notif pemohon | ☐ | |
| 18.2.4 | Pemohon lihat alasan | Pemohon | Catatan tolak terlihat | ☐ | |
| 18.2.5 | Edit & resubmit | Pemohon | Bisa edit (draft), upload ulang, submit | ☐ | |

### 18.3 Skenario Cabut Berkas

| No | Langkah | Oleh | Hasil yang Diharapkan | Status | Catatan |
|----|---------|------|----------------------|--------|---------|
| 18.3.1 | Submit pengajuan | Pemohon | pending_admin | ☐ | |
| 18.3.2 | Cabut berkas | Pemohon | Status kembali: draft | ☐ | |
| 18.3.3 | Edit & resubmit | Pemohon | Bisa edit ulang & submit | ☐ | |

---

## RINGKASAN HASIL UAT

| Modul | Total Uji | PASS | FAIL | N/A | % Lolos |
|-------|-----------|------|------|-----|---------|
| 1. Autentikasi & Sesi | 16 | | | | |
| 2. Dashboard | 6 | | | | |
| 3. Pengajuan CRUD | 31 | | | | |
| 4. Verifikasi Admin | 16 | | | | |
| 5. Signing (TTE) | 11 | | | | |
| 6. Surat Tugas Dinas | 6 | | | | |
| 7. Kepala Unit | 6 | | | | |
| 8. Notifikasi | 9 | | | | |
| 9. Manajemen Pegawai | 16 | | | | |
| 10. Jenis Dokumen | 9 | | | | |
| 11. PDDikti Sync | 5 | | | | |
| 12. Verifikasi QR | 5 | | | | |
| 13. PDF & Surat | 13 | | | | |
| 14. Download Surat | 5 | | | | |
| 15. Keamanan (RBAC) | 9 | | | | |
| 16. Concurrency & Edge | 6 | | | | |
| 17. Responsif & Browser | 10 | | | | |
| 18. Alur E2E | 19 | | | | |
| **TOTAL** | **198** | | | | |

### Kriteria Penerimaan

- **SIAP PRODUKSI:** Total PASS ≥ 95% dan semua modul **E2E (Modul 18)** PASS
- **BERSYARAT:** Total PASS 80-94%, bug minor perlu perbaikan
- **TIDAK SIAP:** Total PASS < 80% atau ada bug kritis di alur utama

### Bug yang Ditemukan

| No | Modul | Deskripsi Bug | Severity (Kritis/Sedang/Rendah) | Status | PIC |
|----|-------|--------------|--------------------------------|--------|-----|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |

### Sign-off

| Peran | Nama | Tanda Tangan | Tanggal |
|-------|------|-------------|---------|
| QA Tester | | | |
| Developer | | | |
| Admin BKPSDM (User) | | | |
| Kepala BKPSDM (PIC) | | | |

---

*Checklist UAT ini mencakup 198 skenario uji dari 18 modul. Sesuaikan dengan kebutuhan dan lingkungan aktual.*
