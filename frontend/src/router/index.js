import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Progress bar for route transitions (optional - can be added later)
// import NProgress from 'nprogress'
// import 'nprogress/nprogress.css'

const routes = [
  // ========== PUBLIC ROUTES ==========
  {
    path: '/service-selection',
    name: 'service-selection',
    component: () => import('@/views/ServiceSelectionView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/verify',
    name: 'verify',
    component: () => import('@/views/VerificationView.vue'),
    meta: { requiresAuth: false },
  },

  // ========== REDIRECT ==========
  {
    path: '/',
    redirect: '/service-selection',
  },

  // ========== PEMOHAN & ATASAN ROUTES ==========
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/pemohon/DashboardView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'kepala'] },
  },

  // Pengajuan Management (only pemohon & atasan can create/edit)
  {
    path: '/pengajuan',
    name: 'pengajuan.index',
    component: () => import('@/views/pemohon/RiwayatPengajuanView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan'] },
  },
  {
    path: '/pengajuan/baru',
    name: 'pengajuan.create',
    component: () => import('@/views/pemohon/PengajuanBaruView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan'] },
  },
  {
    path: '/pengajuan/:id',
    name: 'pengajuan.show',
    component: () => import('@/views/pemohon/DetailPengajuanView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'admin_bkpsdm', 'kepala_bkpsdm'] },
  },
  {
    path: '/pengajuan/:id/edit',
    name: 'pengajuan.edit',
    component: () => import('@/views/pemohon/EditPengajuanView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan'] },
  },

  // Profile
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/pemohon/ProfileView.vue'),
    meta: { requiresAuth: true },
  },

  // Notifications
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/NotificationView.vue'),
    meta: { requiresAuth: true },
  },

  // ========== PGA ROUTES (PENCANTUMAN GELAR AKADEMIK) ==========
  {
    path: '/pga',
    name: 'pga.index',
    component: () => import('@/views/pga/PgaDashboardView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'kepala'] },
  },
  {
    path: '/pga/baru',
    name: 'pga.create',
    component: () => import('@/views/pga/PgaBaruView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'kepala'] },
  },
  {
    path: '/pga/:id',
    name: 'pga.show',
    component: () => import('@/views/pga/PgaDashboardView.vue'), // Reuse dashboard for now
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'kepala'] },
  },
  {
    path: '/pga/:id/edit',
    name: 'pga.edit',
    component: () => import('@/views/pga/PgaBaruView.vue'), // Reuse baru view for edit
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'kepala'] },
  },

  // PGA Verifikasi (Admin BKPSDM)
  {
    path: '/admin/pga-verifikasi',
    name: 'admin.pga.verifikasi',
    component: () => import('@/views/admin/PgaVerifikasiView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm', 'kepala_bkpsdm'] },
  },

  // ========== ADMIN BKPSDM ROUTES ==========
  {
    path: '/admin',
    redirect: '/admin/verifikasi',
  },

  // Verifikasi Pengajuan (Admin Home)
  {
    path: '/admin/verifikasi',
    name: 'admin.verifikasi',
    component: () => import('@/views/admin/VerifikasiView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/admin/verifikasi/:id',
    name: 'admin.verifikasi.detail',
    component: () => import('@/views/admin/VerifikasiDetailView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Riwayat Verifikasi (Pengajuan yang sudah selesai diverifikasi)
  {
    path: '/admin/riwayat-verifikasi',
    name: 'admin.riwayat-verifikasi',
    component: () => import('@/views/admin/RiwayatVerifikasiView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Manajemen Surat
  {
    path: '/admin/surat',
    name: 'admin.surat.index',
    component: () => import('@/views/admin/SuratView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/admin/surat/:id',
    name: 'admin.surat.show',
    component: () => import('@/views/admin/SuratView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Surat Izin Belajar (Admin BKPSDM)
  {
    path: '/admin/surat-izin',
    name: 'admin.surat-izin',
    component: () => import('@/views/admin/SuratIzinView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Surat Tugas Belajar (Admin BKPSDM - setelah Surat Izin ditandatangani)
  {
    path: '/admin/surat-tugas',
    name: 'admin.surat-tugas',
    component: () => import('@/views/admin/AdminSuratTugasView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Surat Tugas Mandiri (Admin BKPSDM - setelah Surat Izin)
  {
    path: '/admin/surat-tugas-mandiri',
    name: 'admin.surat-tugas-mandiri',
    component: () => import('@/views/admin/SuratTugasMandiriView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/admin/surat-tugas-mandiri/:id',
    name: 'admin.surat-tugas-mandiri.detail',
    component: () => import('@/views/admin/SuratTugasMandiriDetailView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/admin/pdf-editor',
    name: 'admin.pdf-editor',
    component: () => import('@/views/admin/PdfEditorView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Surat Tugas Dinas (Kepala Unit)
  {
    path: '/kepala/surat-tugas',
    name: 'kepala.surat-tugas',
    component: () => import('@/views/admin/SuratTugasDinasView.vue'),
    meta: { requiresAuth: true, roles: ['kepala', 'atasan'] },
  },

  // Manajemen Pegawai
  {
    path: '/admin/pegawai',
    name: 'admin.pegawai',
    component: () => import('@/views/admin/PegawaiView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Jenis Dokumen Management
  {
    path: '/admin/jenis-dokumen',
    name: 'admin.jenis-dokumen',
    component: () => import('@/views/admin/JenisDokumenView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // Jenis Dokumen PGA Management
  {
    path: '/admin/jenis-dokumen-pga',
    name: 'admin.jenis-dokumen-pga',
    component: () => import('@/views/admin/JenisDokumenPgaView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // PDDikti Sync
  {
    path: '/admin/pddikti-sync',
    name: 'admin.pddikti-sync',
    component: () => import('@/views/admin/PDDiktiSyncView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },

  // ========== KEPALA BKPSDM ROUTES ==========
  {
    path: '/kepala',
    redirect: '/kepala/signing',
  },
  {
    path: '/kepala/signing',
    name: 'kepala.signing',
    component: () => import('@/views/admin/SigningView.vue'),
    meta: { requiresAuth: true, roles: ['kepala_bkpsdm'] },
  },
  {
    path: '/kepala/signing/:id',
    name: 'kepala.signing.detail',
    component: () => import('@/views/admin/SigningDetailView.vue'),
    meta: { requiresAuth: true, roles: ['kepala_bkpsdm'] },
  },
  {
    path: '/kepala/riwayat',
    name: 'kepala.riwayat',
    component: () => import('@/views/admin/SigningHistoryView.vue'),
    meta: { requiresAuth: true, roles: ['kepala_bkpsdm'] },
  },

  // ========== DEMO / TEST ROUTES ==========
  {
    path: '/demo/loading',
    name: 'demo.loading',
    component: () => import('@/components/LoadingSpinnerDemo.vue'),
    meta: { requiresAuth: false },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Re-initialize from storage to ensure fresh data
  authStore.initializeFromStorage()

  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)
  const allowedRoles = to.meta.roles || []

  // Direct check from localStorage for reliability (bypass reactive state)
  const hasToken = !!localStorage.getItem('token') && localStorage.getItem('token') !== 'undefined'
  const hasUser = !!localStorage.getItem('user') && localStorage.getItem('user') !== 'undefined'

  // Use store state but fallback to direct localStorage check
  const isAuthenticated = authStore.isAuthenticated || (hasToken && hasUser)

  console.log('[ROUTER] Navigation guard:', {
    to: to.path,
    from: from.path,
    isAuthenticated,
    storeToken: !!authStore.token,
    storeUser: !!authStore.user,
    hasToken,
    hasUser,
    userRole: authStore.userRole,
    requiresAuth,
    allowedRoles
  })

  // Redirect to service-selection if not authenticated
  if (requiresAuth && !isAuthenticated) {
    console.log('[ROUTER] Redirecting to service-selection - not authenticated')
    // Clear any corrupted data
    if (!hasToken) localStorage.removeItem('token')
    if (!hasUser) localStorage.removeItem('user')
    // Store intended destination for after login
    if (to.path !== '/service-selection' && to.path !== '/login') {
      sessionStorage.setItem('redirectAfterLogin', to.fullPath)
    }
    next('/service-selection')
    return
  }

  // Redirect to appropriate home if already on login or service-selection page and authenticated
  if ((to.path === '/login' || to.path === '/service-selection') && isAuthenticated) {
    const isPgaService = authStore.selectedService === 'pga'

    if (authStore.isAdmin) {
      // Admin redirect based on service
      next(isPgaService ? '/admin/pga-verifikasi' : '/admin/verifikasi')
    } else if (authStore.isKepala) {
      // Kepala redirect based on service
      next(isPgaService ? '/pga' : '/kepala/signing')
    } else if (authStore.isKepalaUnit) {
      // Kepala Unit redirect based on service
      next(isPgaService ? '/pga' : '/kepala/surat-tugas')
    } else {
      // Pemohon/Atasan redirect based on service
      next(isPgaService ? '/pga' : '/dashboard')
    }
    return
  }

  // Redirect to appropriate home if role doesn't match
  if (allowedRoles.length > 0 && !allowedRoles.includes(authStore.userRole)) {
    const isPgaService = authStore.selectedService === 'pga'

    if (authStore.isAdmin) {
      next(isPgaService ? '/admin/pga-verifikasi' : '/admin/verifikasi')
    } else if (authStore.isKepala) {
      next(isPgaService ? '/pga' : '/kepala/signing')
    } else if (authStore.isKepalaUnit) {
      next(isPgaService ? '/pga' : '/kepala/surat-tugas')
    } else {
      next(isPgaService ? '/pga' : '/dashboard')
    }
    return
  }

  next()
})

export default router
