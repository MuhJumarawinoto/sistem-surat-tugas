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
│   │   │   ├── DocumentPreviewModal.vue  # Document preview (PDF/image)
│   │   │   ├── DocumentInfoTooltip.vue
│   │   │   ├── FileUpload.vue
│   │   │   ├── LoadingSpinner.vue
│   │   │   ├── NotificationBell.vue
│   │   │   ├── PageHeader.vue
│   │   │   ├── PDDiktiDropdown.vue
│   │   │   ├── PengajuanMilestone.vue    # Progress milestone (route style)
│   │   │   ├── SendMessageModal.vue
│   │   │   ├── Toast.vue                  # Toast notification
│   │   │   ├── ToastAutoNotifier.vue      # Auto toast notifier
│   │   │   ├── VerificationDetailModal.vue # Admin verification modal
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
6. **Page Header Pattern**: Consistent page layout using `PageHeader` component
   - Auto-generates title and subtitle based on route
   - Supports action buttons (router-link, button, or badge)
   - Example: `<PageHeader title="Title" subtitle="Description" :actions="actions" />`
7. **Breadcrumb Pattern**: Automatic breadcrumb navigation based on route path
   - Uses icons for visual clarity
   - Skips action routes (baru, edit) in breadcrumb
8. **Modal Pattern**: Details viewed in modal using Teleport for proper z-index layering
   - Example: "Lihat Pengajuan" opens detail modal instead of separate page
   - Use `Teleport to="body"` with overlay background
   - Include loading states, close on backdrop click, and ESC key handling
9. **Responsive Action Buttons**: Desktop shows buttons inline, Mobile shows dots menu
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
| Pemohon (PNS) | `pemohon` | Create, view, edit own pengajuan; upload documents; download surat |
| Atasan Langsung | `atasan` | Create own pengajuan; view, approve/reject pengajuan from unit kerja |
| Admin BKPSDM | `admin` | Verify documents; manage pegawai; generate Surat Izin Belajar; view all surat |
| Kepala BKPSDM | `kepala` | Sign Surat Izin Belajar with TTE |
| **Kepala Dinas** | `kepala` | Create Surat Tugas Belajar for unit kerja; view pengajuan verified |

### Kepala Dinas per Unit Kerja

Role `kepala` memiliki fungsi ganda:
1. **Kepala BKPSDM** - Menandatangani Surat Izin Belajar dengan TTE
2. **Kepala Dinas (OPD)** - Membuat Surat Tugas Belajar untuk pegawai di unit kerja nya

**Implementation:**
- `users.is_kepala_unit`: Boolean flag untuk menandai user adalah kepala unit kerja
- `users.unit_kerja_id`: Relasi ke unit kerja tempat user menjadi kepala
- Kepala Dinas hanya bisa melihat dan membuat surat untuk pegawai di unit kerja nya
- Admin BKPSDM bisa melihat semua surat tugas dinas

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

### Using PageHeader Component

Use this pattern for consistent page headers across all views:

```vue
<script setup>
import { computed } from 'vue'
import PageHeader from '@/components/PageHeader.vue'

// Define actions for the header
const headerActions = computed(() => [
  {
    label: 'Create New',
    icon: 'ri-add-line',
    to: '/resource/create',      // Use 'to' for router-link
    variant: 'btn-primary'
  },
  {
    label: 'Export',
    icon: 'ri-download-line',
    onClick: handleExport,        // Use 'onClick' for button
    variant: 'btn-secondary'
  },
  {
    label: 'Status',
    icon: 'ri-check-line',
    isBadge: true,                // Use 'isBadge' for non-clickable badge
    variant: 'badge-success'
  }
])
</script>

<template>
  <PageHeader
    title="Page Title"
    subtitle="Optional description"
    :actions="headerActions"
  />
</template>
```

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

### Creating a Verification Modal

For admin verification workflows, use this pattern similar to VerificationDetailModal:

```javascript
// State for modal
const showVerificationModal = ref(false)
const selectedId = ref(null)
const loading = ref(false)
const submitting = ref(false)
const data = ref(null)

// Open modal
function openVerificationModal(id) {
  selectedId.value = id
  showVerificationModal.value = true
}

// Load data when modal opens
watch(() => showVerificationModal.value, async (newVal) => {
  if (newVal && selectedId.value) {
    await loadData()
  }
})

async function loadData() {
  loading.value = true
  try {
    const response = await api.get(`/endpoint/${selectedId.value}`)
    data.value = response.data
  } finally {
    loading.value = false
  }
}

// Approve action
async function handleApprove() {
  submitting.value = true
  try {
    await api.post(`/endpoint/${selectedId.value}/approve`)
    emit('verified')
    emit('close')
  } finally {
    submitting.value = false
  }
}
```

```vue
<!-- Template -->
<VerificationDetailModal
  :show="showVerificationModal"
  :pengajuan-id="selectedId"
  @close="showVerificationModal = false"
  @verified="handleVerified"
/>
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
| GET | `/api/pengajuan` | List all pengajuan (filtered by role, paginated, add `?include_deleted=1` to include dicabut) |
| POST | `/api/pengajuan` | Create new pengajuan |
| GET | `/api/pengajuan/{id}` | Get detail pengajuan |
| PUT | `/api/pengajuan/{id}` | Update pengajuan |
| DELETE | `/api/pengajuan/{id}` | Delete pengajuan (soft delete to `dicabut`, draft only) |
| POST | `/api/pengajuan/{id}/submit` | Submit pengajuan for approval |
| POST | `/api/pengajuan/{id}/cancel` | Cancel/withdraw pengajuan (owner only, back to `draft`) |
| POST | `/api/pengajuan/{id}/restore` | Restore deleted/cancelled pengajuan back to draft |

### Documents
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pengajuan/{pengajuanId}/dokumen` | Get all documents for a pengajuan |
| POST | `/api/pengajuan/{pengajuanId}/dokumen` | Upload document for pengajuan |
| DELETE | `/api/dokumen/{id}` | Delete document |
| PUT | `/api/dokumen/{id}/verify` | Verify document (admin: lengkap/tidak_lengkap) |

### Approval
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/approval/{id}/approve` | Approve pengajuan |
| POST | `/api/approval/{id}/reject` | Reject pengajuan |
| POST | `/api/approval/{id}/verify` | Verify documents (admin) |

### Surat Tugas Dinas (Kepala Dinas)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/kepala/surat-tugas` | List surat tugas (kepala dinas only, filtered by unit kerja) |
| GET | `/api/kepala/surat-tugas/pending` | List pengajuan verified but no surat yet |
| POST | `/api/kepala/surat-tugas` | Create new surat tugas dinas |
| GET | `/api/kepala/surat-tugas/{id}` | Get detail surat tugas |
| PUT | `/api/kepala/surat-tugas/{id}` | Update surat tugas (draft only) |
| DELETE | `/api/kepala/surat-tugas/{id}` | Delete surat tugas (draft only) |
| GET | `/api/kepala/surat-tugas/{id}/pdf` | Generate and download PDF |
| GET | `/api/surat-tugas/{pengajuanId}` | Get surat tugas by pengajuan (admin/bkpsdm) |

### Surat Izin Belajar (Admin BKPSDM)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/surat-izin` | List all surat izin (admin only) |
| GET | `/api/admin/surat-izin/pending` | List pengajuan with surat dinas, need izin |
| POST | `/api/admin/surat-izin` | Generate surat izin PDF draft |
| GET | `/api/admin/surat-izin/{id}` | Get detail surat izin |
| GET | `/api/admin/surat-izin/{id}/preview` | Preview surat before signing |
| POST | `/api/admin/surat-izin/{id}/sign` | Sign surat with TTE |
| GET | `/api/admin/surat-izin/{id}/download` | Download signed surat |
| GET | `/api/surat-izin/verify/{qr}` | Verify surat authenticity (public) |
| GET | `/api/pengajuan/{id}/surat-izin` | Get surat izin by pengajuan (pemohon) |

### Surat Tugas Mandiri (Admin BKPSDM)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/surat-tugas-mandiri` | List all surat tugas mandiri (admin only) |
| GET | `/api/admin/surat-tugas-mandiri/pending` | List pengajuan with surat izin, need tugas mandiri |
| POST | `/api/admin/surat-tugas-mandiri` | Generate surat tugas mandiri PDF draft |
| GET | `/api/admin/surat-tugas-mandiri/{id}` | Get detail surat tugas mandiri |
| POST | `/api/admin/surat-tugas-mandiri/{id}/sign` | Sign surat with TTE |
| GET | `/api/admin/surat-tugas-mandiri/{id}/download` | Download signed surat |
| GET | `/api/surat-tugas-mandiri/verify/{qrCode}` | Verify surat authenticity (public) |
| GET | `/api/pengajuan/{id}/surat-tugas-mandiri` | Get surat tugas mandiri by pengajuan (pemohon) |

### Verification (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/surat-izin/verify/{qrCode}` | Verify surat izin authenticity |
| GET | `/api/surat-tugas/verify/{qrCode}` | Verify surat tugas dinas authenticity |
| GET | `/api/surat-tugas-mandiri/verify/{qrCode}` | Verify surat tugas mandiri authenticity |

### Surat (Legacy - Deprecated)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/surat/{id}` | Generate surat izin belajar (use `/api/admin/surat-izin` instead) |
| POST | `/api/surat/{id}/sign` | Sign surat with TTE (use `/api/admin/surat-izin/{id}/sign` instead) |

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
  loading: false,       // Loading state
  error: null           // Error message
}
actions: {
  fetchList(params), fetchDetail(id), create(data), update(id, data),
  submitPengajuan(id), cancelPengajuan(id), deletePengajuan(id)
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
draft → pending_atasan → pending_admin → verified → surat_dinas → surat_izin → signed → selesai/completed
                                             ↓                              ↓
                                          ditolak                         ditolak
                                             ↑
                                 cancel (tarik kembali)

draft → delete → dicabut (masuk riwayat)
```

| Status | Penjelasan | Bisa Edit/Hapus | Bisa Dicabut | Bisa Dipulihkan |
|--------|------------|-----------------|--------------|-----------------|
| `draft` | Pengajuan dibuat, belum dikirim | Ya | - | - |
| `pending_atasan` | Menunggu approval atasan langsung | Tidak | Ya (→draft) | - |
| `pending_admin` | Menunggu verifikasi admin | Tidak | Ya (→draft) | - |
| `verified` | Dokumen diverifikasi admin, menunggu Surat Tugas Dinas | Tidak | Ya (→draft) | - |
| `surat_dinas` | Surat Tugas Dinas sudah dibuat, menunggu Surat Izin | Tidak | Tidak | - |
| `surat_izin` | Surat Izin sudah dibuat, menunggu TTE | Tidak | Tidak | - |
| `disetujui` | Disetujui oleh penandatangan | Tidak | Tidak | - |
| `signed` | Surat ditandatangani (TTE) | Tidak | Tidak | - |
| `ditolak` | Ditolak admin/atasan | Tidak | Tidak | - |
| `selesai` | Surat selesai | Tidak | Tidak | - |
| `completed` | Proses lengkap | Tidak | Tidak | - |
| `dicabut` | Pengajuan dihapus dari draft/riwayat | Tidak | - | Ya |

### Alur Lengkap dengan 2 Tahap Surat

```
┌─────────────────────────────────────────────────────────────────┐
│              ALUR PENGAJUAN DENGAN 2 TAHAP SURAT                │
└─────────────────────────────────────────────────────────────────┘

  [PEMOHON]                    [ATASAN]                  [ADMIN]
       │                           │                          │
   Buat Pengajuan            Review & Approve         Verify Dokumen
       │                           │                          │
       ▼                           ▼                          ▼
    draft ──────────────→ pending_atasan ─────────→ pending_admin
                                                           │
                                                           ▼
                                                        verified
                                                           │
                                    ┌──────────────────────┘
                                    │
                                    ▼
                         ┌───────────────────────┐
                         │   [KEPALA DINAS]       │
                         │   (Unit Kerja)         │
                         └───────────────────────┘
                                    │
                         Create Surat Tugas Belajar
                                    │
                                    ▼
                                   surat_dinas
                                    │
                                    ▼
                         ┌───────────────────────┐
                         │   [ADMIN BKPSDM]      │
                         └───────────────────────┘
                                    │
                      Generate Surat Izin Belajar
                                    │
                                    ▼
                                  surat_izin
                                    │
                                    ▼
                         ┌───────────────────────┐
                         │   [KEPALA BKPSDM]      │
                         └───────────────────────┘
                                    │
                              Sign with TTE
                                    │
                                    ▼
                                   signed
                                    │
                                    ▼
                                 selesai
```

**Alur Penarikan (Cancel):**
1. **Pending/Verified** → Klik "Cabut Berkas" → Status kembali jadi **draft**
2. Pengajuan dapat diedit kembali dan dikirim ulang

**Alur Penghapusan (Delete):**
1. **Draft** → Klik "Hapus" → Status jadi **dicabut** → Masuk **Riwayat**
2. **Dicabut** → Klik "Pulihkan" → Status kembali jadi **draft**
3. Data tetap tersimpan (tidak dihapus permanen)

**Alur Singkat:**
1. **User** → Buat & kirim pengajuan
2. **Admin** → Verifikasi dokumen
3. **Kepala Dinas** → Buat Surat Tugas Belajar
4. **Admin BKPSDM** → Generate Surat Izin Belajar
5. **Kepala BKPSDM** → TTE elektronik
6. **Selesai** → User unduh surat

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
6. **Clear application cache** after updating master data: `POST /api/admin/cache/clear`

### Cache Management

**Cached Data:**
- Master data (jenjang pendidikan, unit kerja) - 1 hour TTL
- Notification count - 30 seconds TTL
- Notification list - 1 minute TTL

**Cache Invalidation:**
- Manual: `POST /api/admin/cache/clear` (admin only)
- Automatic: After updating master data (jenjang, unit kerja)

### Performance Monitoring

**Key Metrics:**
- API response time should be < 100ms for cached endpoints
- Notification polling: every 90 seconds
- Pagination: 10 items per page for pengajuan list

## Business Process Flow

Complete business process documentation is available at [docs/business-flow.md](docs/business-flow.md) with Mermaid.js diagrams covering:

- **Main Application Flow** - End-to-end pengajuan process
- **Admin Verification Flow** - Document verification workflow
- **Jabatan Verification Matrix** - Approval chain by position
- **Atasan Approval Flow** - For atasan who submit their own pengajuan
- **Status State Diagram** - All possible status transitions
- **Surat Generation Flow** - Letter creation and TTE process
- **Required Documents** - 9 document types overview
- **Role & Permission Matrix** - User roles and their permissions

## Recent Changes (2026-05-22)

### Admin Verification System
Complete implementation of admin document verification with modal interface:

**New Components:**
- **VerificationDetailModal**: Comprehensive modal for admin to verify pengajuan
  - View complete pengajuan details (pegawai info, pendidikan info)
  - View verification chain with current status
  - Preview all uploaded documents
  - Mark each document as "Lengkap" or "Tidak Lengkap"
  - Add notes for each document
  - Approve or reject pengajuan after verification
  - View final signer information

**Frontend Features:**
- **VerifikasiView**: Updated to use new modal
  - Click "Verifikasi" button opens detail modal
  - Shows verification status (all complete, incomplete, or pending)
  - Can approve only when all documents are marked complete
  - Reject with reason for incomplete applications

**Backend API Endpoints:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/dokumen/{id}/verify` | Verify individual document (lengkap/tidak_lengkap) |
| POST | `/api/pengajuan/{id}/approve` | Approve pengajuan (admin only) |
| POST | `/api/pengajuan/{id}/reject` | Reject pengajuan with reason |
| GET | `/api/verification/pengajuan/{id}` | Get verification chain & signer info |

**Verification Workflow:**
1. Admin opens verification modal from list
2. Review all pengajuan details
3. Preview and verify each document
4. Mark documents as complete/incomplete with notes
5. When all documents complete → Approve button enabled
6. If documents incomplete → Reject with reason

**Document Types:**
- SK Pangkat Terakhir
- SK CPNS
- SKP 2 Tahun Terakhir
- Surat Keterangan Lulus/Diterima
- Jadwal Perkuliahan
- Sertifikat Akreditasi Prodi
- Surat Pernyataan Biaya Mandiri
- Surat Pernyataan Tidak Menuntut Ijazah
- Surat Keterangan Sehat

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

## Recent Changes (2026-05-23)

### Performance Optimizations

**Database Indexes:**
- Added composite index `(user_id, is_read)` on `notifications` table for faster query
- Added indexes on `pengajuans` table: `(user_id, status)`, `status`, `created_at`, `jenjang_id`
- Added indexes on `users` table: `unit_kerja_id`, `jabatan_kategori`, `atasan_id`
- Added indexes on `dokumen_pengajuan` table: `(pengajuan_id, jenis_dokumen)`, `status_verifikasi`
- Migration: `2026_05_23_000001_add_indexes_to_notifications_table.php`
- Migration: `2026_05_23_000002_add_performance_indexes.php`

**Database Schema Updates:**
- Migration: `2026_05_23_000003_add_missing_statuses_to_pengajuan_table.php`
  - Added missing enum values: `verified`, `signed`, `completed`, `dicabut`
  - Full status enum: `draft`, `pending_atasan`, `pending_admin`, `verified`, `disetujui`, `signed`, `ditolak`, `selesai`, `completed`, `dicabut`

**API Service Layer:**
- Multiple axios instances with different timeouts:
  - `api` - 30s timeout (default)
  - `apiQuick` - 5s timeout (for non-critical requests like notifications)
  - `apiLong` - 60s timeout (for file uploads)
- Import: `import { apiQuick, apiLong } from '@/services/api'`

**Caching System:**
- Master data caching with 1-hour TTL (jenjang, unit kerja)
- Cache invalidation endpoint: `POST /api/admin/cache/clear` (admin only)
- Implemented in `MasterController.php`

**Notification Polling Optimization:**
- Reduced polling frequency: 60s → 90s
- Smart polling: fetch `unread-count` first, only fetch full list if count increased
- Client-side caching: 30s for count, 60s for list
- Implemented in `ToastAutoNotifier.vue` and `notification.js`

**Query Optimization:**
- Selective eager loading with specific fields instead of `*`
- Example: `'user:id,name,nip,jabatan,unit_kerja_id'` instead of full user object
- Reduces data transfer by ~50%

**Loading States:**
- Skeleton animations for all data loading states
- Error states with retry button
- Implemented in `DashboardView.vue`

### Cabut Berkas Pengajuan Feature

**Backend Implementation:**
- New endpoint: `POST /api/pengajuan/{id}/cancel`
- Method: `PengajuanController::cancel()`
- Rules:
  - Only owner can cancel their own pengajuan
  - Cancellable statuses: `pending_atasan`, `pending_admin`, `verified`
  - **Status changes BACK to `draft`** (can edit and resubmit)
  - Resets timestamps: `tanggal_submit_atasan`, `tanggal_approve_atasan`, `tanggal_approve_admin`

**Frontend Implementation:**
- Store method: `pengajuanStore.cancelPengajuan(id)`
- Dashboard actions:
  - Desktop: Inline button "Cabut Berkas" (red)
  - Mobile: Dropdown menu with "Cabut Berkas" option
- Confirmation modal: "Status akan kembali menjadi Draft dan Anda dapat mengeditnya kembali"
- Toast: "Pengajuan berhasil ditarik kembali ke Draft"

### Pengajuan Dihapus Masuk Riwayat (Soft Delete & Restore)

**Fitur:**
- Pengajuan yang dihapus (draft) atau dicabut (pending/verified) **tidak dihapus permanen**
- Status berubah menjadi `dicabut` dan muncul di **Riwayat Pengajuan**
- Pemohon dapat **memulihkan** pengajuan yang dicabut kembali menjadi draft

**Backend Implementation:**
- **Soft Delete**: `DELETE /api/pengajuan/{id}` → status changes to `dicabut`
- **Cancel**: `POST /api/pengajuan/{id}/cancel` → status changes to `dicabut`
- **Restore**: `POST /api/pengajuan/{id}/restore` → status back to `draft`
- Query parameter: `?include_deleted=1` to fetch `dicabut` status

**Frontend Implementation:**
- **RiwayatPengajuanView** now displays `dicabut` status
- Added "Dihapus" filter option
- **Restore button** appears for `dicabut` status
- Store method: `pengajuanStore.restorePengajuan(id)`
- New badge style: `badge-secondary` for "Dihapus" status

**Status Badge:**
| Status | Label | Badge | Icon |
|--------|-------|-------|------|
| `dicabut` | Dihapus | `badge-secondary` (gray) | `ri-delete-bin-line` |

### Dashboard Status Update

**New Status Cards (6 cards):**
1. **Draft** - Pengajuan belum dikirim
2. **Pending** - `pending_atasan` + `pending_admin`
3. **Terverifikasi** - `verified` status
4. **Disetujui** - `disetujui` + `signed`
5. **Ditolak** - `ditolak`
6. **Selesai** - `selesai` + `completed`

**Status Mapping:**
| Database Status | Display Label | Badge Color |
|-----------------|---------------|-------------|
| `draft` | Draft | Gray |
| `dicabut` | Dihapus | Gray (Secondary) |
| `pending_atasan` | Pending Atasan | Yellow |
| `pending_admin` | Pending Admin | Yellow |
| `verified` | Terverifikasi | Blue |
| `surat_dinas` | Surat Tugas Dinas | Purple |
| `surat_izin` | Surat Izin | Purple |
| `disetujui` | Disetujui | Primary |
| `signed` | Signed | Primary |
| `ditolak` | Ditolak | Red |
| `selesai` | Selesai | Green |
| `completed` | Completed | Green |

**Milestone Steps (6 steps):**
1. Dikirim
2. Verifikasi
3. Surat Dinas (Kepala Dinas)
4. Surat Izin (Admin BKPSDM)
5. TTE (Tanda Tangan Elektronik)
6. Selesai

### Bug Fixes

**Database Column Names:**
- Fixed eager load queries to use correct column names:
  - `users.nama` → `users.name`
  - `jenjang_pendidikan.jenjang` → `jenjang_pendidikan.kode`
  - `dokumen_pengajuan.jenis` → `dokumen_pengajuan.jenis_dokumen`
  - `dokumen_pengajuan.is_verified` → `dokumen_pengajuan.status_verifikasi`

### API Endpoints Added

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/pengajuan/{id}/cancel` | Cancel/withdraw pengajuan (owner only, back to `draft`) |
| POST | `/api/pengajuan/{id}/restore` | Restore deleted/cancelled pengajuan back to draft (owner only) |
| POST | `/api/admin/cache/clear` | Clear master data cache (admin only) |

---

## Recent Changes (2026-05-24)

### Admin Verification Page Redesign

**Compact Layout:**
- Inline stats row instead of large cards (horizontal badges)
- Reduced card spacing (`space-y-3` instead of `space-y-4`)
- Tighter padding throughout (`p-4` instead of default card-body)
- Verifier info displayed as compact inline badge instead of full box
- All information visible without excessive scrolling

**Collapsible Sections:**
- Progress Pengajuan: Collapsed by default, click to expand
- Dokumen Lampiran: **Expanded by default** for quick document access
- Smooth animations for expand/collapse

**Document Preview on Verification Page:**
- Documents now loaded and displayed directly in the verification list
- Each pengajuan shows document count with status badges (lengkap/belum/tidak_lengkap)
- Grid layout (2 columns) for document cards
- Preview button always visible (not just on hover)
- Click preview button to open `DocumentPreviewModal`
- Document types configured with icons and labels

**Bug Fixes:**
- **Route ordering issue**: Moved `/{pengajuanId}/dokumen` route before `/{id}` route to prevent conflicts
- **DocumentPreviewModal**: Fixed to accept `document` prop (object with url, name, type) OR individual props (src, alt, fileType)
- **getDocumentUrl**: Added null check and fallback for undefined VITE_API_URL

### Milestone Component Redesign

**Route/Path Style Design:**
- Changed from "box cards" to "map route" style
- Horizontal connecting line between steps
- Circular nodes with icons instead of boxes
- Animated progress line that fills as steps complete
- 4 steps: Dikirim → Verifikasi → TTD → Selesai
- Clean, modern look with proper spacing

**Status Colors:**
- Completed: Green (success)
- Current: Blue (primary) with pulse animation
- Pending: Gray (secondary)
- Rejected: Red (danger)

### Components Updated

**PengajuanMilestone.vue:**
- Complete redesign with route-style layout
- Progress percentage calculation
- Responsive design for mobile

**VerifikasiView.vue:**
- Added document loading with `dokumenMap` state
- Added `collapsedDocuments` state for collapsible document section
- Added document-related helper functions
- Integrated `DocumentPreviewModal`

**DocumentPreviewModal.vue:**
- Added `document` prop support for backward compatibility
- Computed values for src, alt, and fileType
- Supports both object prop and individual props

### API Endpoint Fixes

**Route Order (backend/routes/api.php):**
```php
// Documents routes - must come before /{id} to avoid conflicts
Route::prefix('/{pengajuanId}/dokumen')->group(function () {
    Route::get('/', [DokumenController::class, 'index']);
    Route::post('/', [DokumenController::class, 'store']);
});

Route::get('/{id}', [PengajuanController::class, 'show']);
```

### Performance Improvements

**Notification Polling:**
- Reduced frequency: 90s → 120s (2 minutes)
- Increased initial delay: 3s → 5s
- apiQuick timeout: 5s → 3s for faster fail
- Silent error handling to reduce console spam

---

## Recent Changes (2026-05-25)

### Sistem Surat 2 Tahap (Surat Tugas Dinas + Surat Izin Belajar)

**Overview:**
Sistem sekarang menggunakan **2 tahap surat** dalam proses Izin Belajar Mandiri:
1. **Surat Tugas Belajar** - Dikeluarkan oleh Kepala Dinas (tempat pegawai bekerja)
2. **Surat Izin Belajar Mandiri** - Dikeluarkan oleh Kepala BKPSDM

**Alur Baru:**
```
verified → Surat Tugas Dinas (Kepala Dinas) → Surat Izin (Admin BKPSDM) → TTE → Selesai
```

**Database Changes:**
- `users.is_kepala_unit`: Boolean flag untuk menandai user adalah kepala unit kerja
- `surat_tugas_dinas` table: Store surat tugas dinas data
  - Fields: nomor_surat, bulan, tahun, tanggal_mulai, tanggal_selesai, file_path, status
  - Unique: (unit_kerja_id, nomor_surat, tahun)
- `surat_izin_belajar` table: Store surat izin belajar mandiri data
  - Fields: nomor_surat, tahun, file_path, tte_path, qr_code, status
  - Foreign key ke `surat_tugas_dinas`

**Status Pengajuan Baru:**
| Status | Penjelasan |
|--------|------------|
| `verified` | Dokumen diverifikasi admin, menunggu Surat Tugas Dinas |
| `surat_dinas` | Surat Tugas Dinas sudah dibuat, menunggu Surat Izin |
| `surat_izin` | Surat Izin sudah dibuat, menunggu TTE |

**Role "kepala" (Kepala Dinas):**
- Bisa melihat pengajuan `verified` untuk unit kerja nya
- Bisa membuat Surat Tugas Belajar
- Nomor surat auto-increment per unit kerja per tahun
- Format: `[Nomor]/DK/[Bulan]/[Tahun]`

**Frontend Features:**
- **SuratTugasDinasView** - List pengajuan untuk buat surat (role: kepala)
- **CreateSuratTugasModal** - Form buat surat tugas
- **Preview & Download PDF** - HTML preview dengan download PDF option
- **Integration** - Otomatis muncul di Admin BKPSDM setelah dibuat

**API Endpoints (Surat Tugas Dinas):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/kepala/surat-tugas` | List surat tugas (kepala dinas only) |
| GET | `/api/kepala/surat-tugas/pending` | List pengajuan verified but no surat yet |
| POST | `/api/kepala/surat-tugas` | Create new surat tugas dinas |
| GET | `/api/kepala/surat-tugas/{id}/pdf` | Generate and download PDF |

**API Endpoints (Surat Izin Belajar):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/surat-izin` | List all surat izin (admin only) |
| GET | `/api/admin/surat-izin/pending` | List pengajuan with surat dinas, need izin |
| POST | `/api/admin/surat-izin` | Generate surat izin PDF draft |
| POST | `/api/admin/surat-izin/{id}/sign` | Sign surat with TTE |

**Template Surat:**
- **Surat Tugas Dinas**: HTML template dengan kop surat dinamis sesuai unit kerja
- **Surat Izin Belajar**: Menggunakan template BKPSDM dengan referensi ke Surat Tugas Dinas

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

#### Riwayat Pengajuan Filter Update
- **Menu Riwayat Pengajuan** now only displays completed pengajuan (berhasil/gagal)
- Shows only: Disetujui, Ditolak, Selesai, Sudah Ditandatangani
- Hides: Draft, Submitted, pending approvals
- This keeps history focused on final outcomes only

### Document Preview in Detail Modal
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

# SISTEM SURAT - 2 TAHAP

## Overview

Sistem menggunakan **2 tahap surat** dalam proses Izin Belajar Mandiri:

```
┌─────────────────────────────────────────────────────────────────┐
│                 ALUR LENGKAP 2 TAHAP SURAT                      │
└─────────────────────────────────────────────────────────────────┘

  [Pengajuan Terverifikasi]
       │
       ▼
  ┌──────────────────────────────────────────────────────────────┐
  │  TAHAP 1: SURAT TUGAS BELAJAR                                │
  │  ──────────────────────────────────                         │
  │  Dikeluarkan oleh: Kepala Dinas (tempat pegawai bekerja)    │
  │  Role: kepala                                               │
  │  Format: [Nomor]/DK/[Bulan]/[Tahun]                         │
  │  Isi: Menugaskan pegawai untuk belajar                      │
  └──────────────────────────────────────────────────────────────┘
       │
       ▼
  ┌──────────────────────────────────────────────────────────────┐
  │  TAHAP 2: SURAT IZIN BELAJAR MANDIRI                        │
  │  ──────────────────────────────────                         │
  │  Dikeluarkan oleh: Kepala BKPSDM                            │
  │  Role: kepala (BKPSDM)                                      │
  │  Format: 800.1.3.1/[Nomor]/BKPSDM/[Tahun]                  │
  │  Isi: Izin belajar mandiri tidak diberhentikan             │
  │  Dasar hukum point 6: Surat Tugas Dinas                    │
  └──────────────────────────────────────────────────────────────┘
       │
       ▼
  3. TTE Kepala BKPSDM → Selesai
```

---

## TAHAP 1: Surat Tugas Belajar (Kepala Dinas)

### Template Analysis Summary

**Document Type:** Surat Tugas Belajar

| Aspect | Detail |
|--------|--------|
| Penerbit | Kepala Dinas (tempat pegawai bekerja) |
| Role | `kepala` (per unit kerja) |
| Number Format | `[Nomor]/DK/[Bulan]/[Tahun]` |
| Viewable By | Kepala Dinas terkait, Admin BKPSDM, Pemohon |

### Required Data Fields

```javascript
// Surat Tugas Dinas Data Structure
{
  // Header Dinas (auto dari unit_kerja pegawai)
  dinas: {
    nama: "Dinas Pendidikan",
    alamat: "Jl. Raya Sukabumi...",
    telepon: "(0266) xxxxxx",
    email: "disdik@sukabumikab.go.id"
  },

  // Nomor Surat
  nomor_surat: "001",
  bulan: "Mei",
  tahun: "2026",
  tanggal_ttd: "2026-05-20",

  // Data Kepala Dinas (auto dari user role kepala di unit kerja)
  kepala_dinas: {
    nama: "Drs. H. Nama Kepala",
    nip: "19700101 199503 1 001",
    pangkat: "Pembina Utama",
    golongan: "IV/e"
  },

  // Data Pegawai (auto dari pengajuan)
  pegawai: {
    nama: "Drajat Sukmana, S.IP",
    nip: "197506152005011002",
    pangkat: "Pembina",
    golongan: "IV/a",
    jabatan: "Kepala Seksi",
    unit_kerja: "Dinas Pendidikan"
  },

  // Data Program Belajar (auto dari pengajuan)
  program: {
    nama_prodi: "Magister Hukum",
    jenjang: "S2",
    perguruan_tinggi: "Universitas Padjadjaran",
    lokasi: "Bandung, Jawa Barat"
  },

  // Waktu Penugasan
  waktu: {
    mulai: "2026-09-01",
    selesai: "2028-09-01"
  }
}
```

### HTML Template Structure

```
┌─────────────────────────────────────────────────────────────┐
│  PEMERINTAH KABUPATEN SUKABUMI                             │
│  [NAMA DINAS]                                              │
│  Alamat, Telepon, Email                                    │
├─────────────────────────────────────────────────────────────┤
│              SURAT TUGAS BELAJAR                            │
│  Nomor: [Nomor]/DK/[Bulan]/[Tahun]                        │
├─────────────────────────────────────────────────────────────┤
│  Yang bertanda tangan di bawah ini:                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Nama     : [Nama Kepala Dinas]                      │   │
│  │ NIP      : [NIP Kepala Dinas]                       │   │
│  │ Pangkat  : [Pangkat/Golongan]                       │   │
│  │ Jabatan  : Kepala Dinas [Nama Dinas]                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Dengan ini menugaskan kepada pegawai:                     │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Nama     : [Nama Pegawai]                          │   │
│  │ NIP      : [NIP Pegawai]                           │   │
│  │ Pangkat  : [Pangkat/Golongan]                      │   │
│  │ Jabatan  : [Jabatan]                               │   │
│  │ Unit Kerja: [Unit Kerja]                           │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Untuk melaksanakan Penugasan Belajar:                     │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Program Studi/Bidang: [Nama Prodi]                 │   │
│  │ Jenjang          : [S1/S2/S3]                      │   │
│  │ Lembaga          : [Nama Universitas]              │   │
│  │ Lokasi           : [Kota, Provinsi]                │   │
│  │ Waktu            : [Tgl Mulai] s.d. [Tgl Selesai]  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Kewajiban:                                                 │
│  a. Mengikuti perkuliahan dengan sungguh-sungguh           │
│  b. Menyelesaikan studi tepat waktu                        │
│  c. Lapor perkembangan setiap semester                     │
│  d. Wajib kembali bertugas 2x masa studi                   │
│                                                             │
│  Sanksi:                                                    │
│  Apabila tidak memenuhi, mengembalikan biaya negara         │
│                                                             │
│                                    KEPALA DINAS [NAMA]     │
│                                    [Nama Kepala]           │
│                                    NIP. [NIP]               │
└─────────────────────────────────────────────────────────────┘
```

### Implementation Checklist

#### Backend Requirements
- [ ] **SuratTugasDinas Model & Migration** - Store surat tugas dinas data
- [ ] **Nomor Surat Generator** - Auto-increment per dinas per tahun
- [ ] **Kepala Dinas per Unit Kerja** - Relasi user kepala dengan unit_kerja
- [ ] **PDF Generator** - Using DOMPDF/barryvdh/laravel-dompdf
- [ ] **API Endpoints** - CRUD surat tugas dinas

#### Frontend Requirements
- [ ] **SuratTugasDinasView** - List pengajuan untuk buat surat (role: kepala)
- [ ] **CreateSuratTugasModal** - Form buat surat tugas
- [ ] **Preview Surat** - HTML preview with download PDF option
- [ ] **Integration** - Otomatis muncul di Admin BKPSDM setelah dibuat

#### Database Schema Addition
```sql
-- Kepala Dinas per Unit Kerja (add to users table)
ALTER TABLE users ADD COLUMN is_kepala_unit BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD INDEX idx_unit_kerja_kepala (unit_kerja_id, is_kepala_unit);

-- Surat Tugas Dinas Table
CREATE TABLE surat_tugas_dinas (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  pengajuan_id BIGINT NOT NULL,
  unit_kerja_id BIGINT NOT NULL,
  kepala_dinas_id BIGINT NOT NULL,

  -- Nomor Surat
  nomor_surat VARCHAR(50) NOT NULL,
  bulan VARCHAR(20) NOT NULL,
  tahun VARCHAR(4) NOT NULL,

  -- Data Surat
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  tanggal_ttd DATE NOT NULL,
  tempat_ttd VARCHAR(100) DEFAULT 'Sukabumi',

  -- File
  file_path VARCHAR(255),

  -- Status
  status ENUM('draft', 'signed', 'completed') DEFAULT 'signed',

  -- Timestamps
  signed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (pengajuan_id) REFERENCES pengajuan(id),
  FOREIGN KEY (unit_kerja_id) REFERENCES unit_kerjas(id),
  FOREIGN KEY (kepala_dinas_id) REFERENCES users(id),

  UNIQUE KEY unique_nomor (unit_kerja_id, nomor_surat, tahun)
);
```

### API Endpoints (Surat Tugas Dinas)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/kepala/surat-tugas` | List surat tugas (kepala dinas only) |
| GET | `/api/kepala/surat-tugas/pending` | List pengajuan verified but no surat yet |
| POST | `/api/kepala/surat-tugas` | Create new surat tugas dinas |
| GET | `/api/kepala/surat-tugas/{id}` | Get detail surat tugas |
| PUT | `/api/kepala/surat-tugas/{id}` | Update surat tugas (draft only) |
| DELETE | `/api/kepala/surat-tugas/{id}` | Delete surat tugas (draft only) |
| GET | `/api/kepala/surat-tugas/{id}/pdf` | Generate and download PDF |
| GET | `/api/surat-tugas/{pengajuanId}` | Get surat tugas by pengajuan (admin/bkpsdm) |

---

## TAHAP 2: Surat Izin Belajar Mandiri (Kepala BKPSDM)

### Template Analysis Summary

**Document Type:** Surat Izin Belajar Mandiri (Tidak Diberhentikan dari Jabatan)

| Aspect | Detail |
|--------|--------|
| Penerbit | Kepala BKPSDM Kabupaten Sukabumi |
| Signing Method | Tanda Tangan Elektronik (TTE) BSrE BSSN |
| Key Regulation | Perbup Sukabumi No. 2 Tahun 2022 |
| Number Format | `800.1.3.1/[Nomor Urut]/BKPSDM/[Tahun]` |
| Prerequisite | Surat Tugas Dinas must exist |

### Required Data Fields

```javascript
// Surat Izin Belajar Mandiri Data Structure
{
  nomor_surat: "800.1.3.1/001/BKPSDM/2026",
  tanggal_surat: "2026-05-20",

  // Pegawai Data (auto from pengajuan)
  pegawai: {
    nama: "Drajat Sukmana, S.IP",
    nip: "197506152005011002",
    pangkat: "Pembina",
    golongan: "IV/a",
    jabatan: "Kepala Seki",
    unit_kerja: "Dinas Pendidikan"
  },

  // Pendidikan Data (auto from pengajuan)
  pendidikan: {
    jenjang: "S2",
    program_studi: "Magister Hukum",
    perguruan_tinggi: "Universitas Padjadjaran"
  },

  // Surat Tugas Dinas (auto from surat_tugas_dinas)
  surat_dinas: {
    nomor: "001/DK/Mei/2026",
    tanggal: "2026-05-15",
    nama_dinas: "Dinas Pendidikan"
  }
}
```

### 5 Ketentuan Surat (Hardcoded)

1. Tugas mengikuti pendidikan diberikan di luar jam kerja
2. Tidak mengganggu tugas-tugas kedinasan
3. Pendidikan yang diikuti harus sesuai norma dan kaidah akademik
4. Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan
5. Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan

### Database Schema Addition
```sql
-- Surat Izin Belajar Mandiri Table
CREATE TABLE surat_izin_belajar (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  pengajuan_id BIGINT NOT NULL,
  surat_tugas_dinas_id BIGINT NOT NULL,

  -- Nomor Surat
  nomor_surat VARCHAR(100) NOT NULL,
  tahun VARCHAR(4) NOT NULL,

  -- File
  file_path VARCHAR(255),
  tte_path VARCHAR(255),
  qr_code VARCHAR(255),

  -- Status
  status ENUM('draft', 'signed', 'completed') DEFAULT 'draft',

  -- Timestamps
  signed_at TIMESTAMP NULL,
  signed_by VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (pengajuan_id) REFERENCES pengajuan(id),
  FOREIGN KEY (surat_tugas_dinas_id) REFERENCES surat_tugas_dinas(id),

  UNIQUE KEY unique_nomor (nomor_surat)
);
```

### API Endpoints (Surat Izin Belajar)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/surat-izin` | List all surat izin (admin only) |
| GET | `/api/admin/surat-izin/pending` | List pengajuan with surat dinas, need izin |
| POST | `/api/admin/surat-izin` | Generate surat izin PDF draft |
| GET | `/api/admin/surat-izin/{id}` | Get detail surat izin |
| GET | `/api/admin/surat-izin/{id}/preview` | Preview surat before signing |
| POST | `/api/admin/surat-izin/{id}/sign` | Sign surat with TTE |
| GET | `/api/admin/surat-izin/{id}/download` | Download signed surat |
| GET | `/api/surat-izin/verify/{qr}` | Verify surat authenticity |
| GET | `/api/pengajuan/{id}/surat-izin` | Get surat izin by pengajuan (pemohon) |

---

## Update Pengajuan Status Flow

Status flow diperbarui untuk mencakup 2 tahap surat:

```
draft → pending_atasan → pending_admin → verified
                                      │
                                      ▼
                               surat_tugas_dinas
                               (Kepala Dinas create)
                                      │
                                      ▼
                               surat_izin_belajar
                               (Admin BKPSDM create)
                                      │
                                      ▼
                               signed → selesai/completed
```

| Status | Penjelasan |
|--------|------------|
| `verified` | Dokumen diverifikasi admin, menunggu Surat Tugas Dinas |
| `surat_dinas` | Surat Tugas Dinas sudah dibuat, menunggu Surat Izin |
| `surat_izin` | Surat Izin sudah dibuat, menunggu TTE |
| `signed` | Sudah ditandatangani TTE |
| `selesai` / `completed` | Proses lengkap |

---

## Role & Permission Updates

### Role "kepala" (Kepala Dinas per Unit Kerja)

| Permission | Description |
|------------|-------------|
| `view_surat_tugas` | View list pengajuan verified untuk unit kerja nya |
| `create_surat_tugas` | Create surat tugas dinas |
| `update_surat_tugas` | Update surat tugas (draft only) |
| `delete_surat_tugas` | Delete surat tugas (draft only) |
| `download_surat_tugas` | Download PDF surat tugas |

### Role "admin" (Admin BKPSDM)

| Permission | Description |
|------------|-------------|
| `view_all_surat_tugas` | View all surat tugas dinas |
| `create_surat_izin` | Generate surat izin belajar mandiri |
| `sign_surat_izin` | Sign surat izin with TTE (kepala) |

### Role "kepala" (Kepala BKPSDM)

| Permission | Description |
|------------|-------------|
| `sign_surat_izin` | Sign surat izin with TTE |

---

## Notes for Development

### Surat Tugas Dinas
- **Letterhead**: Dynamic sesuai unit kerja masing-masing
- **Kepala Dinas**: Auto-detect dari user role kepala di unit kerja
- **Nomor Surat**: Auto-increment per unit kerja per tahun
- **Wajib**: Harus dibuat sebelum Admin BKPSDM bisa buat Surat Izin

### Surat Izin Belajar
- **Letterhead**: BKPSDM (fixed)
- **Dasar Hukum Point 6**: Refer ke Surat Tugas Dinas (nomor, tanggal, nama dinas)
- **TTE**: BSrE BSSN integration
- **Font**: Times New Roman for official document feel
- **Paper size**: A4 with standard margins

---

## Recent Changes (2026-05-26)

### PDDikti Local Database Import

**Overview:**
- Import PDDikti data (universities and study programs) from `scrape_progress.json` to local database
- Enables fast auto-fill in pengajuan form without external API calls
- **Database Status:** 4755 Perguruan Tinggi, ~30,000+ Prodi

**Import Command:**
```bash
# Import with limit (testing)
php artisan pddikti:import --file=scrape_progress.json --limit=100

# Force update existing records
php artisan pddikti:import --file=scrape_progress.json --force

# Full import (all 4755 universities)
php artisan pddikti:import --file=scrape_progress.json
```

**Database Tables:**
- `perguruan_tinggi`: Stores university data (kode_pt, nama_pt, alamat, provinsi, akreditasi, etc.)
- `prodis`: Stores study program data linked to universities (nama_prodi, jenjang, akreditasi, etc.)

**API Endpoints (Local):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/master/perguruan-tinggi?keyword=...` | Search local universities |
| GET | `/api/master/prodi?perguruan_tinggi_id=...&keyword=...` | Search local study programs |

**Frontend Integration:**
- `masterStore.fetchPerguruanTinggi()` - Search universities from local DB
- `masterStore.fetchProdi()` - Search study programs from local DB
- Auto-fill features: University name, location (kab_kota + provinsi), accreditation

**Artisan Command:**
- `ImportPDDiktiData` - Console command for importing data from JSON file
- Options: `--file`, `--limit`, `--skip`, `--force`
- Handles both universities and their associated study programs

### PDF Templates Updated (Quick Reference Style)

**Both templates updated to match official government document standards:**

**Font & Typography:**
- Primary: Arial (15pt headers, 11pt body)
- Fallback: Times New Roman
- Line height: 1.3x for better readability
- Text alignment: Headers centered, body justified

**Page Layout:**
- Paper size: A4 Portrait (210mm x 297mm)
- Margins: 20mm all sides
- Separator lines: 3pt top border, 1pt bottom border

**QR Code Integration:**
- Size: 80px (Surat Izin), 70px (Surat Tugas)
- Position: Bottom-center
- Generated using QrCodeService (local storage)
- Verification data: type, id, nomor_surat, signed_at

**Template Files:**
- `backend/resources/views/pdf/surat-izin-belajar.blade.php`
- `backend/resources/views/pdf/surat-tugas-dinas.blade.php`

### QR Code Service

**New Service: QrCodeService**
- Location: `backend/app/Services/QrCodeService.php`
- Methods:
  - `generateAndSave()` - Generate QR code and save to storage
  - `generateForSurat()` - Generate QR code for surat verification
  - `getVerificationUrl()` - Get verification URL
  - `generateAsBase64()` - Generate QR code as base64 string
- Storage: `storage/app/public/qr-codes/`
- Uses: External API (api.qrserver.com) for generation

### Bug Fixes

**Prodi Model:**
- Fixed table name: `prodi` → `prodis`
- Allows correct Eloquent relationship with PerguruanTinggi

**Import Command:**
- Fixed logic to import prodis even when universities already exist
- Previously: Prodis only imported when university was new
- Now: Prodis imported for all universities (with `--force` flag)

**Database Migrations:**
- Removed duplicate migrations for perguruan_tinggi and prodis tables
- Fixed foreign key references (pegawai → users)
- Fixed index table names (pengajuans → pengajuan, unit_kerjas → unit_kerja)

### API Endpoints Added

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/master/perguruan-tinggi` | Search local universities (with keyword filter) |
| GET | `/api/master/prodi` | Search local study programs (with PT and keyword filter) |

---

## Recent Changes (2026-05-29)

### Dashboard "Surat" Button Feature

**Overview:**
- Added "Surat" button in Dashboard's "Pengajuan Terbaru" section for pengajuan that already have surat
- Button appears when pengajuan has Surat Izin Belajar or Surat Tugas Mandiri
- Provides quick access to download available surats directly from dashboard

**Frontend Implementation:**
- **DashboardView.vue** - Updated with surat menu functionality
  - `hasSurat(pengajuan)` - Check if pengajuan has any surat
  - `getSuratInfo(pengajuan)` - Get surat information (surat_izin, surat_tugas_mandiri)
  - `loadSuratInfo()` - Load surat info for all pengajuan with relevant status
  - `downloadSuratIzin()` - Download Surat Izin Belajar
  - `downloadSuratTugasMandiri()` - Download Surat Tugas Mandiri

**Surat Menu Button:**
- Desktop: Shows "Surat" button with dropdown menu
- Mobile: Shows "Surat" option in the action dropdown
- Dropdown displays available surats:
  - "Surat Izin Belajar" - Downloads signed surat izin belajar
  - "Surat Tugas Mandiri" - Downloads signed surat tugas mandiri

**Statuses That Trigger Surat Check:**
- `signed` - Has Surat Izin Belajar
- `selesai` - Has both Surat Izin Belajar and Surat Tugas Mandiri
- `completed` - Has both surats
- `surat_izin` - Has Surat Izin Belajar

**API Endpoints Used:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pengajuan/{id}/surat-izin` | Get surat izin by pengajuan |
| GET | `/api/pengajuan/{id}/surat-tugas-mandiri` | Get surat tugas mandiri by pengajuan |
| GET | `/api/admin/surat-izin/{id}/download` | Download surat izin with token |
| GET | `/api/admin/surat-tugas-mandiri/{id}/download` | Download surat tugas mandiri with token |

### QR Code Verification Feature

**Overview:**
- Added QR Code generation and verification system for surat authenticity
- Each signed surat (Surat Izin Belajar, Surat Tugas Mandiri) gets a unique QR code
- Public verification page allows anyone to verify surat authenticity by scanning QR code
- QR code contains: type, id, nomor_surat, signed_at

**Frontend Implementation:**
- **VerificationView.vue** - Public verification page at `/verify`
  - No authentication required
  - Users can enter QR code or scan to verify surat
  - Shows surat details if valid (nomor, tanggal, penandatangan, pegawai info)
  - Displays validity badge with "Dokumen ini sah dan terverifikasi" message

- **SigningDetailView.vue** - Updated with QR code display
  - "Tampilkan QR Code" button appears after signing
  - Shows QR code using api.qrserver.com for generation
  - Displays verification URL for manual verification

- **SuratTugasMandiriDetailView.vue** - Updated with QR code display
  - Same QR code functionality as SigningDetailView
  - Button and display for signed surat tugas mandiri

**Backend Implementation:**
- **SuratTugasMandiriController::verify()** - Fixed verification method
  - Properly decodes JSON QR code data
  - Returns standardized response format
  - Includes surat details: nomor_surat, tanggal_ttd, kepala_dinas, pengajuan info

**QR Code Data Format:**
```json
{
  "type": "surat_izin_belajar" | "surat_tugas_mandiri",
  "id": 123,
  "nomor": "800.1.3.1/001/BKPSDM/2026",
  "signed_at": "2026-05-29T10:00:00.000000Z"
}
```

**API Endpoints (Public - No Auth Required):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/surat-izin/verify/{qrCode}` | Verify surat izin authenticity |
| GET | `/api/surat-tugas/verify/{qrCode}` | Verify surat tugas dinas authenticity |
| GET | `/api/surat-tugas-mandiri/verify/{qrCode}` | Verify surat tugas mandiri authenticity |

**Frontend Routes:**
| Path | Component | Access |
|------|-----------|--------|
| `/verify` | VerificationView.vue | Public (no auth) |

---

## Recent Changes (2026-06-02)

### Auto-Generation of SuratTugasMandiri

**Overview:**
- SuratTugasMandiri sekarang otomatis dibuat saat Admin BKPSDM membuat SuratIzinBelajar
- Tidak perlu lagi membuat SuratTugasMandiri secara terpisah
- Proses lebih efisien: 1 langkah untuk menghasilkan 2 surat sekaligus

**Backend Implementation:**
- **SuratIzinBelajarController::store()** - Updated to auto-create SuratTugasMandiri
  - Setelah SuratIzinBelajar dibuat, SuratTugasMandiri otomatis dibuat
  - Menggunakan data yang sama dari pengajuan dan surat izin
  - Status pengajuan berubah menjadi `surat_izin`

**Database Relationship:**
- `surat_tugas_mandiri.surat_izin_belajar_id` - Foreign key ke SuratIzinBelajar
- `surat_tugas_mandiri.surat_tugas_dinas_id` - Foreign key ke SuratTugasDinas (dari surat izin)

### Bug Fixes

**Undefined in Download URLs:**
- Fixed undefined values in download URLs by using proper response.data.data access
- Updated DashboardView.vue to correctly parse paginated API responses
- All download links now work correctly with proper token authentication

**Admin Page Layout:**
- Added MainLayout wrapper to SuratIzinView.vue
- Added MainLayout wrapper to SuratTugasDinasView.vue
- Breadcrumb component now properly included in admin pages
- Navbar and sidebar now appear correctly in all admin views

### Known Issues & Debugging Notes

**LocalStorage Token Issue:**
- If login works but token remains null in localStorage, check:
  1. DevTools Console for JavaScript errors during login
  2. Network tab to verify API response contains token
  3. frontend/.env has correct VITE_API_URL pointing to localhost:8000/api
  4. Browser storage settings allow localStorage
  5. Clear browser cache and localStorage, retry login

**Debug Commands:**
```javascript
// In browser console after login attempt
localStorage.getItem('token')  // Should return token string
localStorage.getItem('user')    // Should return user JSON string
```

```bash
# Backend - verify login returns token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"identity":"kepala@bkpsdm.go.id","password":"password"}'
```

### Updated API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/surat-izin` | Generate surat izin + auto-create surat tugas mandiri |
| GET | `/api/admin/surat-tugas-mandiri` | List all surat tugas mandiri (admin only) - now auto-generated |
| GET | `/api/kepala/signing` | View pengajuan ready for TTE signing (kepala role) |

### Milestone Color System Fixes

**Overview:**
- Fixed milestone status colors not appearing correctly in pemohon dashboard
- Fixed milestone status colors not appearing correctly in admin verification page
- Updated color system to use standard Tailwind colors instead of custom colors

**Frontend Implementation:**
- **DashboardView.vue** - Fixed milestone color system
  - Replaced custom Tailwind colors (`bg-primary-500`, `bg-secondary-300`) with standard colors
  - Added `getProgressLineClass()` function for proper progress line styling
  - Updated `getMilestoneSteps()` with 6 steps: Dikirim, Verifikasi, Surat Dinas, Surat Izin, TTE, Selesai
  - Color mapping: Green (completed), Blue (current/pulse), Gray (pending)

- **VerifikasiView.vue** - Fixed milestone color system
  - Same fixes as DashboardView
  - Changed from inline style to Tailwind classes
  - Handles all statuses including `surat_dinas` and `surat_izin`

**Status Color Mapping:**
| Step Status | Color Class | Description |
|-------------|-------------|-------------|
| `completed` | `bg-green-500` | Step sudah selesai |
| `current` | `bg-blue-500` | Step sedang diproses (with pulse animation) |
| `pending` | `bg-gray-300` | Step belum dimulai |
| `rejected` | `bg-red-500` | Pengajuan ditolak |

**Progress Line Width by Status:**
| Status | Width | Color |
|--------|-------|-------|
| `draft`, `dicabut`, `ditolak` | 0% | Gray |
| `pending_atasan`, `pending_admin` | 16% | Blue |
| `verified` | 33% | Blue |
| `surat_dinas` | 50% | Blue |
| `surat_izin` | 66% | Blue |
| `signed` | 83% | Blue |
| `selesai`, `completed` | 100% | Green |

### Document Preview Auto-Download Fix

**Overview:**
- Fixed documents automatically downloading when admin clicks "Verifikasi" or "Detail" button
- Replaced direct `<img>` and `<iframe>` loading with clickable placeholder

**Frontend Implementation:**
- **VerifikasiDetailView.vue** - Fixed auto-download issue
  - Removed document preloading that triggered automatic downloads
  - Replaced with clickable placeholder showing document icon
  - Click placeholder to open `DocumentPreviewModal` for preview

### TTE Menu Not Showing for Kepala

**Overview:**
- Fixed TTE (Tanda Tangan Elektronik) menu not showing for kepala@bkpsdm.go.id account
- Kepala users now have access to signing functionality

**Backend Fix:**
- **SuratIzinBelajarController.php** - Fixed missing Notification import
  - Added: `use App\Models\Notification;`
  - Fixed missing closing brace for `verify()` function
  - 500 error when signing surat now resolved

### Barcode Integration

**Overview:**
- Added barcode generation for Surat Izin Belajar PDF
- Barcode contains surat number for quick identification
- Uses `picqer/php-barcode-generator` library

**Backend Implementation:**
- **BarcodeService** - New service for barcode generation
  - `generateAndSave()` - Generate barcode and save to storage
  - `generateForSurat()` - Generate barcode for surat with nomor_surat
  - `generateAsHtml()` - Generate barcode as HTML for PDF rendering

**Frontend/PDF Integration:**
- **surat-izin-belajar.blade.php** - Barcode displayed in PDF
  - Positioned at bottom of document
  - Contains surat nomor for identification
  - Renders below QR code section

---
