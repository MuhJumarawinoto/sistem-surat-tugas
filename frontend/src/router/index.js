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

  // ========== REDIRECT ==========
  {
    path: '/',
    redirect: () => {
      const authStore = useAuthStore()
      // Redirect based on role
      if (authStore.isAdmin) return '/admin/verifikasi'
      if (authStore.isKepala) return '/kepala/signing'
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

  // Pengajuan Management
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
    meta: { requiresAuth: true, roles: ['pemohon', 'atasan', 'admin_bkpsdm'] },
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

  // Manajemen Pegawai
  {
    path: '/admin/pegawai',
    name: 'admin.pegawai',
    component: () => import('@/views/admin/PegawaiView.vue'),
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
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)
  const allowedRoles = to.meta.roles || []

  if (requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    // Redirect to appropriate home based on role after login
    if (authStore.isAdmin) {
      next('/admin/verifikasi')
    } else if (authStore.isKepala) {
      next('/kepala/signing')
    } else {
      next('/dashboard')
    }
  } else if (allowedRoles.length > 0 && !allowedRoles.includes(authStore.userRole)) {
    // Redirect to appropriate home if role doesn't match
    if (authStore.isAdmin) {
      next('/admin/verifikasi')
    } else if (authStore.isKepala) {
      next('/kepala/signing')
    } else {
      next('/dashboard')
    }
  } else {
    next()
  }
})

export default router
