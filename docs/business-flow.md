# Alur Bisnis Sistem Surat Izin Belajar Mandiri (SIPINTAR)

## 1. Alur Utama Pengajuan (End-to-End)

```mermaid
flowchart TD
    Start([Mulai]) --> Login[PNS Login ke Sistem]
    Login --> Role{Peran User?}

    Role -->|Pemohon/Atasan| Create[ Buat Pengajuan Baru ]
    Role -->|Admin| AdminFlow[Masuk ke Halaman Admin]
    Role -->|Kepala| KepalaFlow[Masuk ke Halaman TTE]

    Create --> Fill[Isi Data Pendidikan]
    Fill --> Upload[Upload 9 Dokumen Persyaratan]
    Upload --> Submit{Kirim Pengajuan?}

    Submit -->|Belum| Draft[Simpan sebagai Draft]
    Submit -->|Ya| CheckAtasan{Atasan Langsung Ditugaskan?}

    CheckAtasan -->|Tidak| AssignAtasan[Admin Assign Atasan]
    CheckAtasan -->|Ya| WaitAtasan[Menunggu Approval Atasan]

    AssignAtasan --> WaitAtasan

    WaitAtasan --> AtasanAction{Atasan Memproses}
    AtasanAction -->|Tolak| RejectAtasan[Pengajuan Ditolak]
    AtasanAction -->|Setuju| WaitAdmin[Menunggu Verifikasi Admin]

    RejectAtasan --> NotifReject1[Notifikasi ke Pemohon]
    NotifReject1 --> EditPemohon[Pemohon Edit & Resubmit]
    EditPemohon --> WaitAtasan

    WaitAdmin --> Verify[Admin Verifikasi Dokumen]
    Verify --> DocCheck{Dokumen Lengkap?}

    DocCheck -->|Tidak| RequestDoc[Admin Request Dokumen Tambahan]
    DocCheck -->|Ya| ApproveAdmin[Admin Setujui Pengajuan]

    RequestDoc --> NotifDoc[Notifikasi ke Pemohon]
    NotifDoc --> UploadDoc[Pemohon Upload Dokumen]
    UploadDoc --> WaitAdmin

    ApproveAdmin --> DetermineSigner{Tentukan Penandatangan}

    DetermineSigner -->|D1-D3/S1| SignKepala[Kepala BKPSDM]
    DetermineSigner -->|S2/Profesi| SignSekda[Sekretaris Daerah]
    DetermineSigner -->|S3| SignBupati[Bupati]

    SignKepala --> ReadyTTE[Surat Siap Ditandatangani]
    SignSekda --> ReadyTTE
    SignBupati --> ReadyTTE

    ReadyTTE --> TTE[Kepala BKPSDM TTE Surat]
    TTE --> SuratTerbit[Surat Terbit]
    SuratTerbit --> NotifPemohon[Notifikasi ke Pemohon]
    NotifPemohon --> Download[Pemohon Download Surat]
    Download --> End([Selesai])

    style Start fill:#e1f5e1
    style End fill:#ffe1e1
    style CheckAtasan fill:#fff4e1
    style DocCheck fill:#fff4e1
    style DetermineSigner fill:#fff4e1
    style ApproveAdmin fill:#d4edda
    style TTE fill:#d4edda
    style RejectAtasan fill:#f8d7da
    style RequestDoc fill:#fff3cd
```

## 2. Alur Verifikasi Dokumen Admin

```mermaid
flowchart LR
    Start([Admin Buka Halaman Verifikasi]) --> List[Daftar Pengajuan pending_admin]
    List --> Select[Pilih Pengajuan]
    Select --> OpenModal[Buka Modal Verifikasi]

    OpenModal --> Review[Review Data Pegawai]
    Review --> ReviewPend[Review Data Pendidikan]
    ReviewPend --> ShowChain[Tampilkan Alur Verifikasi]

    ShowChain --> Docs{Dokumen Tersedia?}

    Docs -->|Tidak Ada| NoDocs[Mark: Tidak Ada Dokumen]
    Docs -->|Ada| LoopDoc[Loop 9 Jenis Dokumen]

    LoopDoc --> Preview[Preview Dokumen]
    Preview --> VerifyCheck{Dokumen Valid?}

    VerifyCheck -->|Ya| MarkComplete[Check: Lengkap]
    VerifyCheck -->|Tidak| MarkIncomplete[Check: Tidak Lengkap]
    VerifyCheck -->|Perlu Catatan| AddNote[Tambah Catatan]

    MarkComplete --> NextDoc{Masih Ada Dokumen?}
    MarkIncomplete --> NextDoc
    AddNote --> NextDoc

    NextDoc -->|Ya| LoopDoc
    NextDoc -->|Tidak| AllDone{Semua Lengkap?}

    AllDone -->|Ya| EnableBtn[Enable: Setujui Button]
    AllDone -->|Tidak| ShowWarning[Tampilkan Warning]

    EnableBtn --> Decision{Admin Aksi?}
    ShowWarning --> Decision

    Decision -->|Tolak| RejectModal[Modal Reject]
    Decision -->|Setuju| Approve[Approve Pengajuan]
    Decision -->|Tutup| CloseModal[Tutup Modal]

    RejectModal --> InputReason[Input Alasan Penolakan]
    InputReason --> ConfirmReject[Konfirmasi Reject]
    ConfirmReject --> UpdateStatus[Update Status: Ditolak]
    UpdateStatus --> NotifyUser[Notifikasi ke Pemohon]
    NotifyUser --> End([Selesai])

    Approve --> UpdateApprove[Update Status: Disetujui]
    UpdateApprove --> NotifyApprove[Notifikasi ke Pemohon]
    NotifyApprove --> DetermineSigner
    CloseModal --> End

    DetermineSigner --> SignFlow[Siapkan untuk Penandatangan]
    SignFlow --> End

    style Start fill:#e1f5e1
    style End fill:#ffe1e1
    style VerifyCheck fill:#fff4e1
    style Decision fill:#fff4e1
    style MarkComplete fill:#d4edda
    style MarkIncomplete fill:#f8d7da
    style EnableBtn fill:#d4edda
```

## 3. Matriks Verifikasi Berdasarkan Jabatan

```mermaid
flowchart TB
    subgraph Pemohon["Pemohon (PNS)"]
        Staf[Staf/Pelaksana]
        Kasi[Kepala Seksi/Kasubbag]
        Kabid[Kepala Bidang]
        Kadis[Kepala Dinas/Badan]
        KepalaBK[Kepala BKPSDM]
        Sekda[Sekretaris Daerah]
    end

    subgraph Atasan["Atasan Langsung (Eselon IV ke atas)"]
        AtasanStaf[Kepala Seksi/Kasubbag - Eselon IV]
        AtasanKasi[Kepala Bidang - Eselon III]
        AtasanKabid[Kepala Dinas - Eselon II]
        AtasanKadis[Sekretaris Daerah]
        AtasanKepalaBK[Sekretaris Daerah]
        AtasanSekda[Bupati]
    end

    subgraph Admin["Admin BKPSDM"]
        Verify[Verifikasi Dokumen Lengkap]
    end

    subgraph Signer["Penandatangan Surat"]
        SignS1[S1/D1-D3 → Kepala BKPSDM]
        SignS2[S2/Profesi → Sekda]
        SignS3[S3 → Bupati]
    end

    Staf -->|1. Approval| AtasanStaf
    Kasi -->|1. Approval| AtasanKasi
    Kabid -->|1. Approval| AtasanKabid
    Kadis -->|1. Approval| AtasanKadis
    KepalaBK -->|1. Approval| AtasanKepalaBK
    Sekda -->|1. Approval| AtasanSekda

    AtasanStaf -->|2. Verifikasi| Verify
    AtasanKasi -->|2. Verifikasi| Verify
    AtasanKabid -->|2. Verifikasi| Verify
    AtasanKadis -->|2. Verifikasi| Verify
    AtasanKepalaBK -->|2. Verifikasi| Verify
    AtasanSekda -->|2. Verifikasi| Verify

    Verify --> SignS1
    Verify --> SignS2
    Verify --> SignS3

    style Staf fill:#e3f2fd
    style Kasi fill:#e3f2fd
    style Kabid fill:#e3f2fd
    style Kadis fill:#e3f2fd
    style KepalaBK fill:#e3f2fd
    style Sekda fill:#e3f2fd
    style AtasanStaf fill:#fff3cd
    style Verify fill:#d1ecf1
    style SignS1 fill:#d4edda
    style SignS2 fill:#d4edda
    style SignS3 fill:#d4edda
```

### Flow Verifikasi Staff

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLOW VERIFIKASI STAF                         │
└─────────────────────────────────────────────────────────────────┘

  [STAF / PELAKSANA]
       │
       │ 1. Submit Pengajuan
       ▼
  [ESOLON IV - Kepala Seki/Kasubbag]
       │
       │ • Review kelayakan izin belajar
       │ • Pertimbangkan beban kerja unit
       │ • Approve/Reject
       ▼
  [ADMIN BKPSDM]
       │
       │ 2. Verifikasi Kelengkapan Dokumen
       │ • Cek 9 dokumen persyaratan
       │ • Verifikasi keaslian dokumen
       │ • Approve/Reject
       ▼
  [PENANDATANGANAN SURAT]
       │
       │ D1/D2/D3/S1 → Kepala BKPSDM
       │ S2/Profesi → Sekda
       │ S3 → Bupati
       ▼
  [SURAT TERBIT]
```

### Catatan Penting untuk Staff:

1. **Atasan Langsung untuk Staff adalah Eselon IV (Kepala Seki/Kasubbag)**
   - Bukan Admin BKPSDM
   - Harus di unit kerja yang sama
   - Ditugaskan melalui field `atasan_id` di tabel users

2. **Dua Level Verifikasi untuk Staff:**
   - Level 1: Eselon IV (Kepala Seki/Kasubbag) - Approval kelayakan
   - Level 2: Admin BKPSDM - Verifikasi dokumen

3. **Jika Staff tidak memiliki Atasan:**
   - Sistem akan menampilkan warning: "Atasan belum ditetapkan"
   - Admin perlu menugaskan atasan melalui menu Pegawai

## 4. Alur Approval Atasan yang Mengajukan

```mermaid
flowchart TD
    Start([Atasan Buat Pengajuan]) --> CreateInput[Isi Data Pengajuan]
    CreateInput --> CreateUpload[Upload Dokumen]
    CreateUpload --> CreateSubmit[Kirim Pengajuan]

    CreateSubmit --> CheckLevel{Cek Eselon Atasan}

    CheckLevel -->|Eselon IV| NeedKabid[Butuh Approval: Kabid]
    CheckLevel -->|Eselon III| NeedKadis[Butuh Approval: Kadis]
    CheckLevel -->|Eselon II| NeedSekda[Butuh Approval: Sekda/Bupati]

    NeedKabid --> KabidApprove{Kabid Approve?}
    NeedKadis --> KadisApprove{Kadis Approve?}
    NeedSekda --> SekdaApprove{Sekda/Bupati Approve?}

    KabidApprove -->|Tolak| Reject1[Ditolak]
    KabidApprove -->|Setuju| CheckHigher{Perlu Approval Lebih Tinggi?}

    KadisApprove -->|Tolak| Reject2[Ditolak]
    KadisApprove -->|Setuju| CheckHigher

    SekdaApprove -->|Tolak| Reject3[Ditolak]
    SekdaApprove -->|Setuju| CheckHigher

    CheckHigher -->|Ya| NextLevel[Next Level Approval]
    CheckHigher -->|Tidak| AdminVerify[Admin Verifikasi]

    NextLevel --> CheckLevel

    Reject1 --> NotifReject[Notifikasi ke Atasan Pemohon]
    Reject2 --> NotifReject
    Reject3 --> NotifReject

    NotifReject --> Edit[Edit & Resubmit]
    Edit --> CreateSubmit

    AdminVerify --> DocVerify[Verifikasi Dokumen]
    DocVerify --> Approve[Setujui Pengajuan]
    Approve --> Signer[Tentukan Penandatangan]
    Signer --> End([Selesai])

    style Start fill:#e1f5e1
    style End fill:#ffe1e1
    style CheckLevel fill:#fff4e1
    style CheckHigher fill:#fff4e1
    style Reject1 fill:#f8d7da
    style Reject2 fill:#f8d7da
    style Reject3 fill:#f8d7da
    style AdminVerify fill:#fff3cd
```

## 5. Status Flow Pengajuan

```mermaid
stateDiagram-v2
    [*] --> Draft: Pemohon Buat Pengajuan
    Draft --> Submitted: Pemohon Submit
    Draft --> Rejected: Admin/Atasan Reject

    Submitted --> PendingAtasan: Menunggu Approval Atasan
    PendingAtasan --> PendingAdmin: Atasan Approve
    PendingAtasan --> Rejected: Atasan Reject

    PendingAdmin --> Verified: Admin Verify Documents
    PendingAdmin --> Rejected: Admin Reject

    Verified --> Approved: Admin Approve
    Verified --> PendingAdmin: Admin Request More Docs

    Approved --> Signed: Kepala BKPSDM TTE
    Signed --> Completed: Surat Terbit

    Rejected --> Draft: Pemohon Edit & Resubmit
    Completed --> [*]

    note right of Draft
        Draft = draft
        Pemohon bisa edit
    end note

    note right of PendingAtasan
        Status = pending_atasan
        Menunggu approval atasan langsung
    end note

    note right of PendingAdmin
        Status = pending_admin
        Menunggu verifikasi admin
    end note

    note right of Verified
        Status = verified
        Dokumen sudah diverifikasi lengkap
    end note

    note right of Approved
        Status = disetujui
        Siap untuk TTE
    end note

    note right of Signed
        Status = signed
        Surat sudah ditandatangani
    end note

    note right of Completed
        Status = completed
        Proses selesai
    end note
```

## 6. Alur Generate Surat Tugas

```mermaid
flowchart TD
    Start([Pengajuan Disetujui]) --> Prepare[Admin Prepare Data]
    Prepare --> CheckData{Data Lengkap?}

    CheckData -->|Tidak| Request[Request Data ke Pemohon]
    Request --> Prepare

    CheckData -->|Ya| GenerateNomor[Generate Nomor Surat]
    GenerateNomor --> Format[Format: 800.1.3.1/XXX/BKPSDM/Thn]

    Format --> LoadTemplate[Load Template Surat]
    LoadTemplate --> FillData[Fill Data Pegawai]
    FillData --> FillPend[Fill Data Pendidikan]
    FillPend --> FillRec[Fill Rekomendasi Atasan]

    FillRec --> GenerateQR[Generate QR Code]
    GenerateQR --> CreatePDF[Create PDF Draft]

    CreatePDF --> ReviewAdmin[Admin Review]
    ReviewAdmin --> AdminOK{Admin OK?}

    AdminOK -->|Tidak| Edit[Edit Data]
    Edit --> FillData

    AdminOK -->|Ya| SendToKepala[Kirim ke Kepala BKPSDM]
    SendToKepala --> PreviewKepala[Kepala Preview Surat]

    PreviewKepala --> KepalaOK{Kepala OK?}
    KepalaOK -->|Tidak| ReturnAdmin[Kembali ke Admin]
    ReturnAdmin --> ReviewAdmin

    KepalaOK -->|Ya| ProcessTTE[Proses TTE BSrE]
    ProcessTTE --> SignPDF[Sign PDF with TTE]

    SignPDF --> SavePath[Save to Storage]
    SavePath --> UpdateDB[Update Database]
    UpdateDB --> SendNotify[Send Notif to Pemohon]
    SendNotify --> End([Selesai])

    style Start fill:#e1f5e1
    style End fill:#ffe1e1
    style CheckData fill:#fff4e1
    style AdminOK fill:#fff4e1
    style KepalaOK fill:#fff4e1
    style ProcessTTE fill:#d4edda
    style SignPDF fill:#d4edda
```

## 7. Dokumen Persyaratan (9 Jenis)

```mermaid
graph LR
    subgraph Required["Dokumen Wajib"]
        D1[SK Pangkat Terakhir]
        D2[SK CPNS]
        D3[SKP 2 Tahun Terakhir]
        D4[Surat Keterangan Lulus]
        D5[Jadwal Perkuliahan]
        D6[Akreditasi Prodi]
        D7[Surat Pernyataan Mandiri]
        D8[Surat Pernyataan Ijazah]
        D9[Surat Keterangan Sehat]
    end

    subgraph Process["Proses Verifikasi"]
        Upload[Pemohon Upload]
        Verify[Admin Verifikasi]
        Status[Status: Lengkap/Tidak Lengkap]
    end

    D1 & D2 & D3 & D4 & D5 & D6 & D7 & D8 & D9 --> Upload
    Upload --> Verify
    Verify --> Status

    style D1 fill:#e3f2fd
    style D2 fill:#e3f2fd
    style D3 fill:#e3f2fd
    style D4 fill:#e3f2fd
    style D5 fill:#e3f2fd
    style D6 fill:#e3f2fd
    style D7 fill:#e3f2fd
    style D8 fill:#e3f2fd
    style D9 fill:#e3f2fd
    style Verify fill:#fff3cd
```

## 8. Role & Permission Matrix

```mermaid
graph TB
    subgraph Roles["User Roles"]
        Pemohon[Pemohon PNS]
        Atasan[Atasan Langsung]
        Admin[Admin BKPSDM]
        Kepala[Kepala BKPSDM]
    end

    subgraph Permissions["Permissions"]
        PM1[Buat Pengajuan Sendiri]
        PM2[Edit Pengajuan Draft]
        PM3[Upload Dokumen]
        PM4[Lihat Pengajuan Sendiri]
        PM5[Download Surat]

        AT1[Buat Pengajuan Sendiri]
        AT2[Approve Pengajuan Unit Kerja]
        AT3[Lihat Pengajuan Unit Kerja]
        AT4[Reject Pengajuan]

        AD1[Verifikasi Dokumen]
        AD2[Approve/Reject Pengajuan]
        AD3[Generate Surat]
        AD4[Manage Pegawai]
        AD5[Sync PDDikti]
        AD6[Assign Atasan]

        KP1[Preview Surat]
        KP2[TTE Surat]
        KP3[Lihat Semua Pengajuan]
    end

    Pemohon --> PM1 & PM2 & PM3 & PM4 & PM5
    Atasan --> AT1 & AT2 & AT3 & AT4
    Admin --> AD1 & AD2 & AD3 & AD4 & AD5 & AD6
    Kepala --> KP1 & KP2 & KP3

    style Pemohon fill:#e3f2fd
    style Atasan fill:#fff3cd
    style Admin fill:#ffe1e1
    style Kepala fill:#d4edda
```

## Ringkasan Status Pengajuan

| Kode Status | Nama Status | Deskripsi | Dapat Edit |
|------------|-------------|-----------|-----------|
| `draft` | Draft | Pengajuan belum dikirim | Ya |
| `submitted` | Terkirim | Menunggu verifikasi | Tidak |
| `pending_atasan` | Pending Atasan | Menunggu approval atasan | Tidak |
| `pending_admin` | Pending Admin | Menunggu verifikasi admin | Tidak |
| `verified` | Terverifikasi | Dokumen lengkap, menunggu approve | Tidak |
| `disetujui` | Disetujui | Siap untuk TTE | Tidak |
| `signed` | Ditandatangani | Surat sudah TTE | Tidak |
| `completed` | Selesai | Proses selesai | Tidak |
| `ditolak` | Ditolak | Pengajuan ditolak | Ya (resubmit) |

## Ringkasan API Endpoints

### Pengajuan
- `GET /api/pengajuan` - List pengajuan
- `POST /api/pengajuan` - Create pengajuan
- `GET /api/pengajuan/{id}` - Detail pengajuan
- `PUT /api/pengajuan/{id}` - Update pengajuan
- `DELETE /api/pengajuan/{id}` - Delete pengajuan
- `POST /api/pengajuan/{id}/submit` - Submit pengajuan

### Approval & Verification
- `POST /api/pengajuan/{id}/approve` - Approve pengajuan (admin)
- `POST /api/pengajuan/{id}/reject` - Reject pengajuan
- `PUT /api/dokumen/{id}/verify` - Verify individual document

### Verification Info
- `GET /api/verification/pengajuan/{id}` - Get verification chain & signer
- `GET /api/verification/rules` - List verification rules
- `GET /api/verification/categories` - List jabatan categories

### Surat
- `POST /api/pengajuan/{id}/generate-surat` - Generate surat
- `GET /api/surat/{id}` - Get surat
- `POST /api/surat/{id}/sign-tte` - Sign surat with TTE
- `GET /api/surat/{id}/download` - Download surat
