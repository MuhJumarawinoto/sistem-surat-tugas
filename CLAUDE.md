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
│   │   │   ├── layout/         # Layout components (Header, Sidebar)
│   │   │   ├── Breadcrumb.vue
│   │   │   ├── DocumentInfoTooltip.vue
│   │   │   ├── FileUpload.vue
│   │   │   ├── LoadingSpinner.vue
│   │   │   ├── NotificationBell.vue
│   │   │   ├── PDDiktiDropdown.vue
│   │   │   ├── SendMessageModal.vue
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

### Backend Patterns

1. **API Resources**: Laravel API Resources for consistent JSON responses
2. **Service Layer**: Business logic separated into Services
3. **Form Request Validation**: Request validation classes
4. **API Controllers**: RESTful controllers with standardized responses

## User Roles & Permissions

| Role | Code | Permissions |
|------|------|-------------|
| Pemohon (PNS) | `pemohon` | Create, view, edit own pengajuan; upload documents |
| Atasan Langsung | `atasan` | View, approve/reject pengajuan from unit kerja |
| Admin BKPSDM | `admin` | Verify documents, approve/reject, generate surat |
| Kepala BKPSDM | `kepala` | Sign surat with TTE |

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
| POST | `/api/pegawai` | Create new pegawai |
| PUT | `/api/pegawai/{id}` | Update pegawai |
| DELETE | `/api/pegawai/{id}` | Delete pegawai |
| POST | `/api/pegawai/sync-simpeg` | Sync data from SIMPEG |

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
