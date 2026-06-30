import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Progress bar for route transitions (optional - can be added later)
// import NProgress from 'nprogress'
// import 'nprogress/nprogress.css'

const routes = [
  // ========== PUBLIC ROUTES ==========
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
    redirect: () => {
      const authStore = useAuthStore()
      // Redirect based on role
      if (authStore.isAdmin) return '/admin/verifikasi'
      if (authStore.isKepala) return '/kepala/signing' // Kepala BKPSDM (prioritas TTE surat izin)
      if (authStore.isKepalaUnit) return '/kepala/surat-tugas' // Kepala Dinas per unit kerja (bukan Kepala BKPSDM)
      return '/dashboard'
    },
  },

  // ========== PEMOHAN & ATASAN ROUTES ==========
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/pemohon/DashboardView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan'] },
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

  console.log('[ROUTER] Navigation guard:', {
    to: to.path,
    from: from.path,
    isAuthenticated: authStore.isAuthenticated,
    hasToken: !!authStore.token,
    hasUser: !!authStore.user,
    userRole: authStore.userRole,
    requiresAuth,
    allowedRoles,
    localStorageToken: localStorage.getItem('token')?.substring(0, 20) + '...'
  })

  if (requiresAuth && !authStore.isAuthenticated) {
    console.log('[ROUTER] Redirecting to login - not authenticated')
    next('/login')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    // Redirect to appropriate home based on role after login
    if (authStore.isAdmin) {
      next('/admin/verifikasi')
    } else if (authStore.isKepala) {
      next('/kepala/signing') // Kepala BKPSDM ke signing
    } else if (authStore.isKepalaUnit) {
      next('/kepala/surat-tugas') // Kepala Dinas per unit kerja
    } else {
      next('/dashboard')
    }
  } else if (allowedRoles.length > 0 && !allowedRoles.includes(authStore.userRole)) {
    // Redirect to appropriate home if role doesn't match
    if (authStore.isAdmin) {
      next('/admin/verifikasi')
    } else if (authStore.isKepala) {
      next('/kepala/signing') // Kepala BKPSDM ke signing
    } else if (authStore.isKepalaUnit) {
      next('/kepala/surat-tugas') // Kepala Dinas per unit kerja
    } else {
      next('/dashboard')
    }
  } else {
    next()
  }
})

export default router
