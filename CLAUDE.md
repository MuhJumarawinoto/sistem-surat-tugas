# Sistem Surat Izin Belajar Mandiri (SIPINTAR)

Sistem informasi pengelolaan izin belajar mandiri bagi PNS di lingkungan Pemerintah Kabupaten Sukabumi.

## Tech Stack

- **Frontend**: Vue 3 (Composition API) + Vite + Tailwind CSS + Pinia
- **Backend**: Laravel 11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum

## Project Structure

```
sipintar/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/    # API Controllers
│   │   ├── Models/              # Eloquent Models
│   │   └── Services/            # Business Logic Services
│   ├── database/
│   │   ├── migrations/          # Database migrations
│   │   └── seeders/             # Database seeders
│   ├── public/                  # Public folder (document root for API subdomain)
│   └── routes/api.php           # API routes definition
├── frontend/                # Vue 3 SPA
│   ├── src/
│   │   ├── components/         # Reusable components
│   │   │   ├── layout/         # Layout components (TopNavbar, Sidebar)
│   │   │   ├── Breadcrumb.vue
│   │   │   ├── DocumentInfoTooltip.vue
│   │   │   ├── FileUpload.vue
│   │   │   ├── LoadingSpinner.vue
│   │   │   ├── NotificationBell.vue
│   │   │   ├── PDDiktiDropdown.vue
│   │   │   ├── SendMessageModal.vue
│   │   │   ├── DetailModal.vue  # Reusable detail modal pattern
│   │   │   └── ...
│   │   ├── views/              # Page components organized by role
│   │   │   ├── auth/           # Login page
│   │   │   ├── pemohon/        # Pemohon (PNS) pages
│   │   │   ├── atasan/         # Atasan pages
│   │   │   └── admin/          # Admin BKPSDM pages
│   │   ├── stores/             # Pinia stores
│   │   │   ├── auth.js         # Authentication state
│   │   │   ├── pengajuan.js    # Pengajuan state
│   │   │   ├── master.js       # Master data state
│   │   │   └── notification.js # Notification state
│   │   ├── router/             # Vue Router configuration
│   │   ├── services/           # API service layer
│   │   │   └── api.js          # Axios instance with interceptors
│   │   └── style.css           # Global styles & Tailwind directives
│   ├── dist/                   # Production build output (for deployment)
│   └── tailwind.config.js      # Tailwind configuration
├── DEPLOYMENT.md               # Deployment guide
└── CLAUDE.md                   # This file
```

## Key Architecture Patterns

### Frontend Patterns

1. **Vue 3 Composition API**: All components use `<script setup>` syntax
2. **Pinia for State Management**: Centralized state for auth, pengajuan, master data
3. **Route-based Code Splitting**: Lazy-loaded routes in router
4. **API Service Layer**: Single Axios instance with interceptors for auth tokens
5. **Role-based Access Control**: Different views for pemohon, atasan, admin, kepala
6. **Modal Pattern**: Details viewed in modal using Teleport for proper z-index layering
   - Example: "Lihat Pengajuan" opens detail modal instead of separate page
   - Use `Teleport to="body"` with overlay background
   - Include loading states, close on backdrop click, and ESC key handling
7. **Responsive Action Buttons**: Desktop shows buttons inline, Mobile shows dots menu
   - Use `hidden sm:flex` for desktop buttons
   - Use `sm:hidden` for mobile dropdown trigger
   - Dropdown menu positioned absolute with `right-0 top-full`

### Backend Patterns

1. **API Resources**: Laravel API Resources for consistent JSON responses
2. **Service Layer**: Business logic separated into Services
3. **Form Request Validation**: Request validation classes
4. **API Controllers**: RESTful controllers with standardized responses

## User Roles & Permissions

| Role | Code | Permissions |
|------|------|-------------|
| Pemohon (PNS) | `pemohon` | Create, view, edit own pengajuan; upload documents |
| Atasan Langsung | `atasan` | Create own pengajuan; view, approve/reject pengajuan from unit kerja |
| Admin BKPSDM | `admin` | Verify documents, approve/reject, generate surat; manage pegawai |
| Kepala BKPSDM | `kepala` | Sign surat with TTE |

### Note: Atasan Can Submit Pengajuan
Based on research of current regulations, **atasan (pejabat eselon) ARE ALLOWED** to submit study permit applications (izin belajar) with proper approval from higher authorities (PPK/Bupati). The system supports this use case.

### Eligibility Requirements (Perbup Sukabumi No. 2 Tahun 2022)
**Semua PNS berhak mengajukan izin belajar mandiri** dengan ketentuan:
- PNS berstatus aktif (bukan PNS pensiunan/mutasi)
- Masa kerja minimum sesuai ketentuan (biasanya 2-5 tahun)
- Tidak sedang menjalankan hukuman disiplin
- Memiliki penilaian kinerja yang baik (minimal 2 tahun terakhir)
- Pendidikan yang diambil sesuai dengan kebutuhan organisasi/relevan dengan jabatan
- Mendapat rekomendasi dari atasan langsung
- Biaya pendidikan ditanggung sendiri (tidak membebankan APBD)

### Verification Matrix (Role-Based Approval)

Sistem menggunakan matriks verifikasi berdasarkan jabatan pemohon:

| Jabatan Pemohon | Atasan Langsung | Penandatangan S1 | Penandatangan S2 | Penandatangan S3 |
|----------------|-----------------|-----------------|-----------------|-----------------|
| Staf/Pelaksana | Kepala Seksi/Kasubbag | Kepala BKPSDM | Sekda | Bupati |
| Kepala Seksi/Kasubbag | Kepala Bidang | Kepala BKPSDM | Sekda | Bupati |
| Kepala Bidang | Kepala Dinas | Kepala BKPSDM | Sekda | Bupati |
| Kepala Dinas (non-BKPSDM) | Sekda | Kepala BKPSDM | Sekda | Bupati |
| Kepala BKPSDM | Sekda | Kepala BKPSDM | Sekda | Bupati |
| Sekda | Bupati | Kepala BKPSDM | Sekda | Bupati |
| Bupati/Wakil Bupati | - | Sekda | Bupati | Bupati |

**Implementation:**
- `users.jabatan_kategori`: Field kategori untuk mapping ke atasan
- `users.atasan_id`: Relasi ke atasan langsung (self-referential)
- `verification_rules`: Tabel aturan verifikasi per jabatan
- Penandatangan ditentukan berdasarkan jenjang pendidikan
- Memiliki penilaian kinerja yang baik (minimal 2 tahun terakhir)
- Pendidikan yang diambil sesuai dengan kebutuhan organisasi/relevan dengan jabatan
- Mendapat rekomendasi dari atasan langsung
- Biaya pendidikan ditanggung sendiri (tidak membebankan APBD)

## Common Tasks

### Running the Application

**Backend (Laravel):**
```bash
cd backend
php artisan serve
# Runs on http://localhost:8000
```

**Frontend (Vue):**
```bash
cd frontend
npm run dev
# Runs on http://localhost:5173
```

### Adding a New API Endpoint

1. Add route in `backend/routes/api.php`
2. Create/update controller in `backend/app/Http/Controllers/`
3. Add method to `frontend/src/services/api.js` if needed

### Creating a New Page

1. Create view component in `frontend/src/views/{role}/`
2. Add route in `frontend/src/router/index.js`
3. Update sidebar if needed in `frontend/src/components/layout/Sidebar.vue`

### Creating a Detail Modal

Use this pattern for viewing item details without page navigation:

```javascript
// In your component
const showDetailModal = ref(false)
const selectedItem = ref(null)
const loadingDetail = ref(false)

async function openDetailModal(id) {
  closeAllMenus() // Close any open dropdowns
  loadingDetail.value = true
  showDetailModal.value = true
  selectedItem.value = null

  try {
    const response = await api.get(`/resource/${id}`)
    selectedItem.value = response.data
  } catch (error) {
    console.error('Failed to load detail:', error)
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedItem.value = null
}
```

```vue
<!-- Template -->
<Teleport to="body">
  <Transition name="modal">
    <div
      v-if="showDetailModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      @click.self="closeDetailModal"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-lg font-semibold">Detail Item</h3>
          <button @click="closeDetailModal" class="btn btn-ghost btn-icon">
            <i class="ri-close-line text-xl"></i>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto flex-1">
          <LoadingSpinner v-if="loadingDetail" />
          <div v-else-if="selectedItem">
            <!-- Your detail content here -->
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end gap-2 p-6 border-t">
          <button @click="closeDetailModal" class="btn btn-ghost">Tutup</button>
        </div>
      </div>
    </div>
  </Transition>
</Teleport>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
```

### Styling Guidelines

**Always use these classes for consistency:**
- Background: `bg-secondary-50` (light gray)
- Padding: `p-6` on `<main>` elements
- Cards: `card`, `card-header`, `card-body`, `card-title`
- Buttons: `btn`, `btn-primary`, `btn-secondary`, `btn-danger`, `btn-ghost`
- Badges: `badge`, `badge-primary`, `badge-success`, `badge-warning`, `badge-danger`
- Typography: `text-secondary-800` (headings), `text-secondary-500` (meta)
- Icons: RemixIcon (`ri-*` classes)

**Animations:**
- `animate-fade-in` for page load
- `animate-slide-up` for cards

### Import Pattern

Always use named imports from Vue:
```javascript
import { ref, onMounted, computed, watch } from 'vue'
```

## API Response Format

Success:
```json
{
  "data": { ... },
  "message": "Success message"
}
```

Error:
```json
{
  "message": "Error message",
  "errors": { ... }
}
```

## Environment Variables

**Backend (.env):**
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SANCTUM_STATEFUL_DOMAINS`

**Frontend (.env):**
- `VITE_API_URL` - API base URL (default: http://localhost:8000/api)

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Pemohon | drajat@disdik.go.id | password |
| Atasan | kadisdik@disdik.go.id | password |
| Admin BKPSDM | admin@bkpsdm.go.id | password |
| Kepala BKPSDM | kepala@bkpsdm.go.id | password |

## Known Issues & Notes

- PDF generation requires `wkhtmltopdf` installed
- File uploads limited to 5MB per file
- 9 required documents for complete pengajuan
- Bun.js has compatibility issues with Git Bash on Windows - use PowerShell or node directly
- PHP `mysqli` extension may be duplicated in Laragon php.ini - remove duplicate entry
- PHP `pdo_firebird` and `pdo_oci` extensions don't exist on Windows - comment out in php.ini

## API Endpoints Reference

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | User login |
| POST | `/api/auth/logout` | User logout |
| GET | `/api/auth/me` | Get current user |

### Pengajuan
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pengajuan` | List all pengajuan (filtered by role) |
| POST | `/api/pengajuan` | Create new pengajuan |
| GET | `/api/pengajuan/{id}` | Get detail pengajuan |
| PUT | `/api/pengajuan/{id}` | Update pengajuan |
| DELETE | `/api/pengajuan/{id}` | Delete pengajuan (draft only) |
| POST | `/api/pengajuan/{id}/submit` | Submit pengajuan for approval |

### Documents
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/dokumen` | Upload document |
| DELETE | `/api/dokumen/{id}` | Delete document |
| GET | `/api/dokumen/{id}` | Get document URL |

### Approval
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/approval/{id}/approve` | Approve pengajuan |
| POST | `/api/approval/{id}/reject` | Reject pengajuan |
| POST | `/api/approval/{id}/verify` | Verify documents (admin) |

### Surat
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/surat/{id}` | Generate surat izin belajar |
| POST | `/api/surat/{id}/sign` | Sign surat with TTE |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | List user notifications |
| PUT | `/api/notifications/{id}/read` | Mark as read |
| PUT | `/api/notifications/read-all` | Mark all as read |

### PDDikti (Perguruan Tinggi)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pddikti/universitas` | Search universities by keyword |
| GET | `/api/pddikti/universitas/{id}/detail` | Get university detail |
| GET | `/api/pddikti/universitas/{id}/prodi` | Get university study programs |
| GET | `/api/pddikti/prodi` | Search study programs by keyword |
| GET | `/api/pddikti/prodi/{id}` | Get study program detail |
| GET | `/api/pddikti/search` | Search all (universitas + prodi) |

### PDDikti Sync (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/pddikti-sync/universitas` | Sync universities from PDDikti API |
| POST | `/api/admin/pddikti-sync/prodi` | Sync study programs for a university |
| GET | `/api/admin/pddikti-sync/stats` | Get sync statistics |
| GET | `/api/admin/pddikti-sync` | List synced universities |
| GET | `/api/admin/pddikti-sync/{id}` | Get university detail with prodis |
| GET | `/api/admin/pddikti-sync/{id}/prodis` | Get prodis for a university |
| DELETE | `/api/admin/pddikti-sync/{id}` | Delete synced data |

### Pegawai (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pegawai` | List all pegawai |
| GET | `/api/pegawai/{id}` | Get pegawai detail |
| GET | `/api/pegawai/{id}/structure` | Get pegawai structure (atasan chain & bawahan) |
| POST | `/api/pegawai` | Create new pegawai |
| PUT | `/api/pegawai/{id}` | Update pegawai (including atasan assignment) |
| DELETE | `/api/pegawai/{id}` | Delete pegawai |
| GET | `/api/pegawai/roles` | List all roles |
| GET | `/api/pegawai/unit-kerjas` | List all unit kerjas |

### Verification
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/verification/rules` | List all verification rules |
| GET | `/api/verification/categories` | List jabatan categories for dropdown |
| GET | `/api/verification/pengajuan/{id}` | Get verification chain & signer for pengajuan |

**Verification Response Structure:**
```json
{
  "verification_chain": [
    { "level": "atasan_langsung", "nama": "Kabid...", "status": "completed", "urutan": 1 },
    { "level": "admin_bkpsdm", "nama": "Admin BKPSDM", "status": "current", "urutan": 2 },
    { "level": "final_signer", "nama": "Kepala BKPSDM", "status": "pending", "urutan": 3 }
  ],
  "final_signer": {
    "nama": "Kepala BKPSDM",
    "jabatan": "Penandatangan Surat",
    "level": "kepala_bkpsdm"
  }
}
```

## Pinia Store Structure

### auth.js
```javascript
state: {
  user: null,           // User object with role, unit_kerja
  token: null,          // Sanctum token
  isAuthenticated: false
}
actions: {
  login(credentials), logout(), fetchUser()
}
```

### pengajuan.js
```javascript
state: {
  list: [],             // Array of pengajuan
  detail: null,         // Current pengajuan detail
  draft: null           // Draft pengajuan in progress
}
actions: {
  fetchList(), fetchDetail(id), create(data), update(id, data)
}
```

### notification.js
```javascript
state: {
  unread: 0,            // Unread count
  notifications: []     // Notification list
}
actions: {
  fetchNotifications(), markAsRead(id), markAllRead()
}
```

### toast.js
```javascript
state: {
  toasts: []            // Array of active toasts
}
actions: {
  success(message, duration), error(message, duration),
  warning(message, duration), info(message, duration),
  remove(id), clear()
}
```

## Required Documents (9 Types)

1. **Surat Permohonan** - Formal application letter
2. **SK CPNS** - Civil servant appointment decree
3. **SK PNS** - Civil servant decree
4. **SK Pangkat Terakhir** - Latest rank promotion decree
5. **SK Jabatan Terakhir** - Latest position decree
6. **Penilaian Kinerja (2 tahun)** - Performance evaluations (2 years)
7. **Rekomendasi Atasan** - Supervisor recommendation
8. **Statement Tidak Sedang Menjalankan Hukuman** - No disciplinary action statement
9. **Surat Pernyataan** - Personal declaration letter

## Pengajuan Status Flow

```
draft → submitted → approved_atasan → verified → approved_admin → signed → completed
                                  ↓
                              rejected
```

| Status | Description | Can Edit |
|--------|-------------|----------|
| `draft` | Initial state, not submitted | Yes |
| `submitted` | Waiting for atasan approval | No |
| `approved_atasan` | Approved by atasan, waiting admin | No |
| `rejected` | Rejected by atasan/admin | No |
| `verified` | Documents verified by admin | No |
| `approved_admin` | Approved by admin, waiting signing | No |
| `signed` | Surat signed by kepala | No |
| `completed` | Process complete | No |

## Custom Tailwind Classes

In `style.css`, these custom classes are defined:
- `.card` - White background with shadow
- `.btn` - Base button styles with variants
- `.badge` - Status badge styles
- `.animate-fade-in` - Fade animation
- `.animate-slide-up` - Slide up animation

## Error Handling Pattern

All API calls use try-catch with consistent error handling:
```javascript
try {
  const response = await api.post('/endpoint', data)
  // Handle success
} catch (error) {
  const message = error.response?.data?.message || 'Terjadi kesalahan'
  // Show error toast/notification
}
```

## Testing

**Backend Tests:**
```bash
cd backend
php artisan test
```

**Frontend Tests:**
```bash
cd frontend
npm run test
```

## Development Tips

1. **Use Vue DevTools** for debugging Pinia stores and components
2. **Check Network Tab** for API calls and responses
3. **Use Laravel Telescope** (if installed) for backend debugging
4. **Clear browser cache** when experiencing auth issues
5. **Run `php artisan config:clear`** after changing backend config

## Recent Changes (2026-05-22)

### Role-Based Verification System
Complete implementation of jabatan-based verification matrix:

**Database Changes:**
- `verification_rules` table: Stores verification rules per jabatan category
- `users.jabatan_kategori`: Field to categorize employees for verification
- `users.atasan_id`: Self-referential relationship for direct supervisor
- Seeder populated with 10 jabatan categories (staf, kasi, kabid, kadis, sekda, bupati, etc.)

**Backend Implementation:**
- **VerificationController**: New controller for verification logic
  - `getVerificationInfo()`: Returns verification chain & final signer
  - `getRules()`: List all verification rules
  - `getJabatanCategories()`: Dropdown options for admin
- **VerificationRule Model**: Methods for signer determination by jenjang
- API Endpoints: `/api/verification/*` for verification-related data

**Frontend Updates:**
- **VerifikasiView**: 
  - Stats dashboard (total, pending atasan, pending admin, verified)
  - Atasan info displayed next to status badge
  - Warning when atasan not assigned (orange badge)
- **PegawaiView**:
  - "Struktur" button to view hierarchy
  - "Kategori Jabatan" dropdown in edit form
  - "Atasan Langsung" assignment dropdown

**Verification Flow by Jenjang:**
- D1/D2/D3/S1 → Kepala BKPSDM signs
- S2/Profesi → Sekretaris Daerah signs
- S3 → Bupati signs

### Database Schema
```sql
-- verification_rules table
CREATE TABLE verification_rules (
  id BIGINT PRIMARY KEY,
  kode VARCHAR UNIQUE,              -- staf, kasi, kabid, kadis, sekda, bupati
  nama_jabatan VARCHAR,
  atasan_level VARCHAR,             -- Required atasan level
  signer_s1 VARCHAR DEFAULT 'Kepala BKPSDM',
  signer_s2 VARCHAR DEFAULT 'Sekretaris Daerah',
  signer_s3 VARCHAR DEFAULT 'Bupati',
  urutan INT,                       -- 1=lowest, 10=highest
  is_active BOOLEAN DEFAULT TRUE
);

-- users table additions
ALTER TABLE users ADD COLUMN atasan_id BIGINT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN jabatan_kategori VARCHAR(50) NULL;
ALTER TABLE users ADD FOREIGN KEY (atasan_id) REFERENCES users(id);
```

---

## Recent Changes (2026-05-19)

### Layout System Update
- **MainLayout Component**: Simplified to include TopNavbar and Sidebar only (removed redundant AppHeader)
- All pages now use `<MainLayout>` wrapper instead of individual layout components
- Pages manage their own headers within the main content area

### Pengajuan Baru Page Layout
- **Compact Document Upload**: 3-column grid layout for all 9 documents without scrolling
- **Numbered Documents**: Each document has a visible number badge (1-9)
- **Progress Bar**: Visual progress indicator showing uploaded documents count
- **Confirmation Dialog**: Custom modal for incomplete document submission

### Toast Notifications System
- **ToastAutoNotifier**: Automatic toast notifications for important alerts
- **Replaced Modal Alerts**: NotificationAlertModal replaced with non-intrusive toasts
- **Toast Store**: Centralized toast management with success, error, warning, info types
- **Auto-dismiss**: Toasts automatically dismiss after configurable duration

### PDDikti Local Database Sync
- **Local Storage**: PDDikti data stored locally for stability and faster access
- **Admin Sync Panel**: `/admin/pddikti-sync` for syncing universities and prodis
- **Auto-fill Data**: Location, accreditation, and program study auto-filled from PDDikti
- **Manual Input Preserved**: Users can still input data manually if needed

### Database Tables
- **perguruan_tinggi**: Stores synced university data from PDDikti
- **prodis**: Stores synced study program data linked to universities

## Development Environment Setup

### Windows-Specific Notes

**Bun.js Issue:** Bun has compatibility issues with Git Bash on Windows (`Operation not permitted` error).

**Workarounds:**
1. Use PowerShell/CMD instead of Git Bash for `bun run dev`
2. Or use `npm run dev:bash` which runs with node directly
3. Or create `frontend/dev.bat`:
   ```batch
   @echo off
   cd /d "%~dp0"
   node node_modules\vite\bin\vite.js
   ```

**PHP Configuration (Laragon):**
- Remove duplicate `extension=mysqli` in `php.ini`
- Comment out `pdo_firebird` and `pdo_oci` (not available on Windows)

### Running Frontend Dev Server

**Git Bash:**
```bash
cd frontend
node node_modules/vite/bin/vite.js
```

**PowerShell/CMD:**
```bash
cd frontend
npm run dev
# or
bun run dev
```

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete cPanel deployment guide.

### Quick Deployment Checklist

- [ ] Build frontend: `npm run build`
- [ ] Upload `frontend/dist/` to `public_html/`
- [ ] Upload `backend/` to `public_html/api/` (exclude `node_modules`, `vendor`)
- [ ] Create database in cPanel
- [ ] Update `.env` with production credentials
- [ ] Run `composer install --no-dev`
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan storage:link`
- [ ] Run `php artisan config:cache`

### Production Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

---

## Template Surat Tugas Belajar Mandiri

### Document Overview
**Jenis:** Surat Tugas Belajar Mandiri (Tidak Diberhentikan dari Jabatan)
**Penerbit:** Kepala BKPSDM Kabupaten Sukabumi
**Penandatanganan:** Tanda Tangan Elektronik (TTE) BSrE BSSN

### Struktur Dokumen

```
┌─────────────────────────────────────────────────────┐
│  PEMERINTAH KABUPATEN SUKABUMI                     │
│  BADAN KEPEGAWAIAN DAN PENGEMBANGAN                │
│  SUMBER DAYA MANUSIA                               │
│                                                     │
│  SURAT TUGAS                                       │
│  NOMOR: 800.1.3.1/.../BKPSDM/Thn                   │
│                                                     │
│  TENTANG                                           │
│  BELAJAR MANDIRI TIDAK DIBERHENTIKAN DARI JABATAN  │
│  JENJANG PENDIDIKAN ...                            │
└─────────────────────────────────────────────────────┘
```

### Dasar Hukum (6 Points)
| No | Dasar Hukum |
|----|-------------|
| 1 | UU Nomor 20 Tahun 2003 - Sistem Pendidikan Nasional |
| 2 | UU Nomor 20 Tahun 2023 - Aparatur Sipil Negara |
| 3 | PP Nomor 17 Tahun 2020 - Manajemen PNS |
| 4 | Perda Kab. Sukabumi No. 3 Tahun 2024 - Perangkat Daerah |
| 5 | **Perbup Sukabumi No. 2 Tahun 2022** - Pedoman Tugas Belajar |
| 6 | Surat Kepala Dinas ... Nomor: ... tanggal ... |

### Data Pegawai (MENUGASKAN)
- Nama: [Nama Pegawai]
- NIP: [NIP]
- Pangkat/Gol.Ruang: [Pangkat/Golongan]
- Jabatan: [Jabatan] pada [Unit Kerja]

### Tujuan Pendidikan
- Jenjang: [D1/D2/D3/S1/S2/S3/Profesi]
- Program Studi: [Nama Prodi]
- Perguruan Tinggi: [Nama PT]

### Ketentuan (5 Poin)
| No | Ketentuan |
|----|-----------|
| 1 | Tugas mengikuti pendidikan diberikan di luar jam kerja |
| 2 | Tidak mengganggu tugas-tugas kedinasan |
| 3 | Pendidikan harus sesuai norma dan kaidah akademik |
| 4 | Biaya pendidikan sepenuhnya ditanggung yang bersangkutan |
| 5 | Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan |

### Format Penomoran Surat
Format: `800.1.3.1/[Nomor Urut]/BKPSDM/[Tahun]`

### Placeholder untuk Diisi
- Nomor Surat
- Jenjang Pendidikan
- Nama Dinas (rekomendasi atasan)
- Nomor/Tanggal Surat Dinas
- Nama, NIP, Pangkat, Jabatan Pegawai
- Jenjang, Program Studi, Perguruan Tinggi
- Tanggal Penetapan

---

## TopNavbar Component

### Location
`frontend/src/components/layout/TopNavbar.vue`

### Features
- Gradient background (primary-700 → accent)
- Logo + Title (clickable to go home/dashboard)
- User info display when logged in
- **Avatar dropdown menu** with Profile and Logout options
- Login button when logged out
- Responsive design (mobile/desktop)

### User Dropdown Menu
When logged in, clicking the avatar shows:
1. **Profile** - Navigate to `/profile`
2. **Logout** - Logout and redirect to login

### Key Functions
```javascript
goHome()          // Navigate to dashboard or login
toggleMenu()       // Show/hide user dropdown
handleLogout()     // Logout action
getInitials()      // Get user initials from name
getRoleLabel()     // Get user role display label
```

### Recent Updates (2026-05-22)

#### Document Preview in Detail Modal
- Added **document preview feature** in pengajuan detail modal
- Support for image preview with zoom & pan (scroll to zoom, drag to pan)
- Support for PDF preview in iframe
- Document icon based on file type (PDF, Word, Excel, Image)
- **Responsive action buttons**: Desktop shows both buttons, Mobile shows dots menu (3 dots)
- Download button for all document types
- Document verification status display (Lengkap/Perlu Cek)

#### Search Feature in Riwayat Pengajuan
- Added **search box** for filtering pengajuan list
- Client-side filtering with real-time search
- Search across: nomor pengajuan, jenjang, prodi, universitas, lokasi, status
- Shows search result count
- Clear button to reset search

#### Navigation Fixes
- Fixed tombol "Batal" in EditPengajuanView - now correctly navigates to `/pengajuan` (riwayat list)
- Previously navigated to detail page causing confusion

#### Bug Fixes
- Fixed syntax errors in RiwayatPengajuanView.vue (invalid end tag)
- Fixed syntax errors in PersetujuanView.vue (missing end tag)
- Fixed syntax errors in SuratView.vue (missing end tag)
- All Vue components now build successfully

#### New Components
- **DocumentPreviewModal.vue** - Modal for previewing PDF and image documents
  - PDF viewer using iframe
  - Image viewer with zoom controls
  - Download and open in new tab options
  - Keyboard shortcuts (ESC to close, scroll to zoom for images)

#### Previous Updates (2026-05-20)

##### TopNavbar Component
- Added user dropdown menu on avatar click
- Profile and Logout in dropdown
- Improved responsive layout
- Fixed z-index and positioning issues

##### Atasan Approval Flow Feature
- **Atasan can now create pengajuan** for themselves (based on regulation research)
- Added `approval_level` field to `pengajuan` table ('biasa' or 'atasan')
- Added `approved_by_atasan` and `approved_at_atasan` fields for tracking higher-level approval
- Updated frontend router to allow atasan access to pengajuan routes
- Updated sidebar to show "Buat Pengajuan Baru" and "Riwayat Pengajuan" for atasan
- Approval flow for atasan applicants: Eselon IV → Kabid → Kepala Dinas → Sekda/Bupati
- Backend API updated to handle atasan creating their own pengajuan

---

## Surat Tugas - Implementation Requirements

### Template Analysis Summary

**Document Type:** Surat Tugas Belajar Mandiri (Tidak Diberhentikan dari Jabatan)

| Aspect | Detail |
|--------|--------|
| Penerbit | Kepala BKPSDM Kabupaten Sukabumi |
| Signing Method | Tanda Tangan Elektronik (TTE) BSrE BSSN |
| Key Regulation | Perbup Sukabumi No. 2 Tahun 2022 |
| Number Format | `800.1.3.1/[Nomor Urut]/BKPSDM/[Tahun]` |

### Required Data Fields for PDF Generation

```javascript
// Surat Tugas Data Structure
{
  nomor_surat: "800.1.3.1/001/BKPSDM/2026",
  tanggal_surat: "2026-05-20",

  // Pegawai Data
  pegawai: {
    nama: "Drajat Sukmana, S.IP",
    nip: "197506152005011002",
    pangkat: "Pembina",
    golongan: "IV/a",
    jabatan: "Kepala Seki",
    unit_kerja: "Dinas Pendidikan"
  },

  // Pendidikan Data
  pendidikan: {
    jenjang: "S2",
    program_studi: "Magister Hukum",
    perguruan_tinggi: "Universitas Padjadjaran"
  },

  // Rekomendasi Atasan
  rekomendasi: {
    nama_dinas: "Dinas Pendidikan",
    nomor_surat: "005/123/DISDIK/2026",
    tanggal_surat: "2026-05-15"
  }
}
```

### Implementation Checklist

#### Backend Requirements
- [ ] **Nomor Surat Generator** - Auto-increment format `800.1.3.1/XXX/BKPSDM/YYYY`
- [ ] **PDF Template** - Using DOMPDF/snappy with official letterhead
- [ ] **TTE Integration** - BSrE BSSN API for electronic signature
- [ ] **QR Code Generator** - For document verification
- [ ] **Surat Model & Migration** - Store generated surat data

#### Frontend Requirements
- [ ] **Preview Surat** - Show PDF preview before signing
- [ ] **Download Surat** - Allow pemohon to download signed surat
- [ ] **Status Tracking** - Show surat status (draft, signed, completed)

#### Database Schema Addition
```sql
-- Surat Tugas Table
CREATE TABLE surat_tugas (
  id BIGINT PRIMARY KEY,
  pengajuan_id BIGINT,
  nomor_surat VARCHAR(100),
  tanggal_surat DATE,
  file_path VARCHAR(255),
  tte_path VARCHAR(255),
  qr_code VARCHAR(255),
  status ENUM('draft', 'signed', 'completed'),
  signed_at TIMESTAMP NULL,
  signed_by VARCHAR(100),
  created_at TIMESTAMP,
  FOREIGN KEY (pengajuan_id) REFERENCES pengajuan(id)
);
```

### Surat Generation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    ALUR GENERATE SURAT TUGAS                    │
└─────────────────────────────────────────────────────────────────┘

  [Admin BKPSDM]
       │
       ├─ Verify Pengajuan
       ├─ Input Rekomendasi Atasan (Nomor & Tanggal Surat)
       ├─ Generate Nomor Surat (Otomatis)
       │
       ▼
  [Generate PDF Draft]
       │
       ├─ Load Template
       ├─ Fill Data (Pegawai, Pendidikan, Rekomendasi)
       ├─ Generate QR Code
       │
       ▼
  [Kepala BKPSDM]
       │
       ├─ Preview Surat
       ├─ Sign with TTE (BSrE BSSN)
       │
       ▼
  [Surat Terbit]
       │
       ├─ Status: Signed
       ├─ Send Notification to Pemohon
       │
       ▼
  [Pemohon]
       │
       └─ Download / Print Surat
```

### API Endpoints (Additional)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/surat/generate` | Generate surat PDF draft |
| GET | `/api/surat/{id}/preview` | Preview surat before signing |
| POST | `/api/surat/{id}/sign` | Sign surat with TTE |
| GET | `/api/surat/{id}/download` | Download signed surat |
| GET | `/api/surat/verify/{qr}` | Verify surat authenticity |

### 5 Ketentuan Surat (Hardcoded in Template)

1. Tugas mengikuti pendidikan diberikan di luar jam kerja
2. Tidak mengganggu tugas-tugas kedinasan
3. Pendidikan yang diikuti harus sesuai norma dan kaidah akademik
4. Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan
5. Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan

### Notes for Development

- **Letterhead positioning**: Ensure proper margins for official BKPSDM letterhead
- **TTE placement**: Reserve space in bottom-right for Kepala BKPSDM signature
- **QR Code**: Include verification URL or unique identifier
- **Font**: Use Times New Roman or similar for official document feel
- **Paper size**: A4 with standard margins
