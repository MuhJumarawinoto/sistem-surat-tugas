# Sequence Diagram - Alur Pembuatan Surat Tugas Belajar Mandiri

## Overview

Dokumen ini menjelaskan alur lengkap pembuatan Surat Tugas Belajar Mandiri mulai dari pengajuan hingga surat terbit dan dapat didownload oleh pemohon.

---

## 1. Alur Utama (High Level)

```mermaid
sequenceDiagram
    actor Pemohon
    actor Atasan
    actor Admin
    actor Kepala
    participant Frontend
    participant Backend
    participant Database

    Note over Pemohon,Database: FASE 1: PENGAJUAN
    Pemohon->>Frontend: Login & Isi Form Pengajuan
    Pemohon->>Frontend: Upload 9 Dokumen Persyaratan
    Frontend->>Backend: POST /api/pengajuan
    Backend->>Database: Save Pengajuan (Draft)
    Pemohon->>Backend: POST /api/pengajuan/{id}/submit
    Backend->>Atasan: Notifikasi Approval

    Note over Pemohon,Database: FASE 2: APPROVAL ATASAN
    Atasan->>Frontend: Review Pengajuan
    Atasan->>Backend: POST /api/approval/{id}/approve
    Backend->>Database: Update Status: approved_atasan
    Backend->>Admin: Notifikasi Verifikasi

    Note over Pemohon,Database: FASE 3: VERIFIKASI ADMIN
    Admin->>Frontend: Review Dokumen
    Admin->>Backend: POST /api/approval/{id}/verify
    Backend->>Database: Update Status: verified
    Admin->>Backend: POST /api/surat/generate
    Backend->>Backend: Generate Nomor Surat
    Backend->>Backend: Generate PDF Draft
    Backend->>Kepala: Notifikasi Signing

    Note over Pemohon,Database: FASE 4: TTE & PENERBITAN
    Kepala->>Frontend: Preview Surat
    Kepala->>Backend: POST /api/surat/{id}/sign
    Backend->>Backend: Call TTE API (BSrE BSSN)
    Backend-->>Backend: Signed PDF
    Backend->>Database: Update Status: signed
    Backend->>Pemohon: Notifikasi Surat Terbit

    Note over Pemohon,Database: FASE 5: DOWNLOAD
    Pemohon->>Frontend: Buka Detail Pengajuan
    Pemohon->>Backend: GET /api/surat/{id}/download
    Backend-->>Pemohon: PDF Surat Tugas
```

---

## 2. Detail Generate Surat Tugas

```mermaid
sequenceDiagram
    participant Admin as Admin BKPSDM
    participant API as Backend API
    participant DB as Database
    participant PDF as PDF Generator
    participant TTE as TTE Service (BSrE)
    participant Notif as Notification Service

    Admin->>API: Request Generate Surat
    Note right of Admin: Pengajuan ID +<br/>Rekomendasi Atasan

    API->>DB: Get Pengajuan Data
    DB-->>API: Return Pengajuan Detail

    API->>API: Generate Nomor Surat
    Note right of API: Format: 800.1.3.1/001/BKPSDM/2026

    API->>API: Prepare Data for PDF
    Note right of API: - Data Pegawai<br/>- Data Pendidikan<br/>- Rekomendasi<br/>- 6 Dasar Hukum<br/>- 5 Ketentuan

    API->>PDF: Generate PDF Draft
    PDF-->>API: Return PDF Buffer

    API->>DB: Save Surat (Draft)
    Note right of DB: Table: surat_tugas<br/>- nomor_surat<br/>- file_path<br/>- status: draft

    API-->>Admin: Return PDF Preview

    Admin->>API: Request Sign (TTE)
    Note right of Admin: Konfirmasi Preview OK

    API->>TTE: Request TTE Signature
    Note right of TTE: PDF + Kepala BKPSDM<br/>Credentials

    TTE-->>API: Return Signed PDF

    API->>API: Generate QR Code
    Note right of API: Untuk verifikasi<br/>keaslian surat

    API->>DB: Update Surat
    Note right of DB: status: signed<br/>signed_at: now<br/>tte_path: path

    API->>Notif: Send Notification
    Note right of Notif: To: Pemohon<br/>Message: Surat Terbit

    API-->>Admin: Return Success (Signed)
```

---

## 3. Detail Download & Verifikasi Surat

```mermaid
sequenceDiagram
    participant Pemohon
    participant Frontend
    participant API as Backend API
    participant DB as Database
    participant Storage as File Storage

    Pemohon->>Frontend: Buka Halaman Detail
    Frontend->>API: GET /api/pengajuan/{id}
    API->>DB: Get Pengajuan + Surat
    DB-->>API: Return Data with Surat Status

    alt Status: Signed/Completed
        API-->>Frontend: Show Surat Available
        Frontend->>Pemohon: Tampilkan Tombol Download
        Pemohon->>Frontend: Klik Download
        Frontend->>API: GET /api/surat/{id}/download
        API->>Storage: Get Signed PDF
        Storage-->>API: Return File
        API-->>Pemohon: Download PDF
    else Status: Not Signed
        API-->>Frontend: Surat Belum Tersedia
        Frontend->>Pemohon: Tampilkan Status: Proses
    end

    Note over Pemohon,Storage: VERIFIKASI SURAT (Opsional)
    Pemohon->>Frontend: Scan QR Code
    Frontend->>API: GET /api/surat/verify/{qr_code}
    API->>DB: Validate QR Code
    DB-->>API: Return Surat Info
    API-->>Pemohon: Tampilkan Info Validasi
```

---

## 4. Mapping Data ke Template Surat

```mermaid
graph LR
    A[Database] --> B[Surat Generator]
    C[Pengajuan Table] --> B
    D[Pegawai Table] --> B
    E[Unit Kerja Table] --> B

    B --> F[PDF Template]

    F --> G[Kop Surat]
    F --> H[Nomor Surat]
    F --> I[Dasar Hukum]
    F --> J[Data Pegawai]
    F --> K[Tujuan Pendidikan]
    F --> L[Ketentuan]
    F --> M[Tanda Tangan]

    style B fill:#f9f,stroke:#333,stroke-width:2px
    style F fill:#bbf,stroke:#333,stroke-width:2px
```

### Detail Mapping:

| Section | Source Table | Fields |
|---------|--------------|--------|
| **Kop Surat** | Static | PEMERINTAH KABUPATEN SUKABUMI - BKPSDM |
| **Nomor Surat** | surat_tugas | nomor_surat (auto-generated) |
| **Dasar Hukum 1-5** | Static | UU 20/2003, UU 20/2023, PP 17/2020, Perda 3/2024, Perbup 2/2022 |
| **Dasar Hukum 6** | pengajuan | Surat rekomendasi atasan (manual input) |
| **Nama** | pegawai | nama |
| **NIP** | pegawai | nip |
| **Pangkat/Gol** | pegawai | pangkat, golongan |
| **Jabatan** | pegawai | jabatan |
| **Unit Kerja** | pegawai | unit_kerja |
| **Jenjang** | pengajuan | jenjang_id |
| **Program Studi** | pengajuan | nama_prodi |
| **Perguruan Tinggi** | pengajuan | perguruan_tinggi |
| **Ketentuan 1-5** | Static | 5 pasal tetap |
| **Tanda Tangan** | surat_tugas | TTE Kepala BKPSDM |

---

## 5. State Transitions

```mermaid
stateDiagram-v2
    [*] --> Draft: Create Pengajuan
    Draft --> Submitted: Submit by Pemohon
    Submitted --> ApprovedAtasan: Approve by Atasan
    Submitted --> Rejected: Reject by Atasan
    ApprovedAtasan --> Verified: Verify by Admin
    Verified --> SuratDraft: Generate Surat
    SuratDraft --> Signed: TTE by Kepala
    Signed --> Completed: Pemohon Download
    Rejected --> [*]
    Completed --> [*]

    note right of SuratDraft
        Nomor surat generated
        PDF draft created
        Waiting TTE
    end note

    note right of Signed
        TTE signed
        QR code generated
        Notification sent
    end note
```

---

## 6. Error Handling

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant DB
    participant TTE

    Client->>API: POST /api/surat/generate

    alt Pengajuan Not Found
        API-->>Client: 404 - Pengajuan tidak ditemukan
    else Status Not Verified
        API-->>Client: 400 - Pengajuan belum diverifikasi
    else Missing Required Data
        API-->>Client: 400 - Data rekomendasi belum diisi
    else Database Error
        API-->>Client: 500 - Gagal menyimpan surat
    else Success
        API->>DB: Save Surat Draft
        API-->>Client: 200 - PDF Draft generated
    end

    Client->>API: POST /api/surat/{id}/sign
    API->>TTE: Request Signature

    alt TTE Service Unavailable
        API-->>Client: 503 - Layanan TTE tidak tersedia
    else Invalid Credentials
        API-->>Client: 401 - Kredensial TTE tidak valid
    else Success
        TTE-->>API: Signed PDF
        API->>DB: Update status: signed
        API-->>Client: 200 - Surat berhasil ditandatangani
    end
```

---

## 7. Entities & Relationships

```
┌──────────────────┐       ┌──────────────────┐
│     pegawai      │       │    pengajuan     │
├──────────────────┤       ├──────────────────┤
│ id               │       │ id               │
│ nama             │◄──────│ pegawai_id       │
│ nip              │       │ jenjang_id       │
│ pangkat          │       │ nama_prodi       │
│ golongan         │       │ perguruan_tinggi │
│ jabatan          │       │ status           │
│ unit_kerja_id    │       │ created_at       │
└──────────────────┘       └────────┬─────────┘
                                    │
                                    │ 1
                                    │
                                    │ 1
                                    ▼
                           ┌──────────────────┐
                           │    surat_tugas    │
                           ├──────────────────┤
                           │ id               │
                           │ pengajuan_id     │
                           │ nomor_surat      │
                           │ file_path        │
                           │ tte_path         │
                           │ qr_code          │
                           │ status           │
                           │ signed_at        │
                           │ signed_by        │
                           └──────────────────┘
```

---

## Ringkasan API Endpoints

| Endpoint | Method | Request | Response |
|----------|--------|---------|----------|
| `/api/surat/generate` | POST | `{ pengajuan_id, rekomendasi_nomor, rekomendasi_tanggal }` | `{ surat_id, nomor_surat, pdf_url }` |
| `/api/surat/{id}/preview` | GET | - | PDF File |
| `/api/surat/{id}/sign` | POST | `{ credentials }` | `{ status: signed, pdf_url }` |
| `/api/surat/{id}/download` | GET | - | PDF File |
| `/api/surat/verify/{qr}` | GET | - | `{ valid: true, surat_info }` |

---

*Document Version: 1.0*
*Last Updated: 2026-05-20*
