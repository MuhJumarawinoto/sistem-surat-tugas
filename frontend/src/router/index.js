import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/pemohon/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pengajuan',
    name: 'pengajuan.index',
    component: () => import('@/views/pemohon/RiwayatPengajuanView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pengajuan/baru',
    name: 'pengajuan.create',
    component: () => import('@/views/pemohon/PengajuanBaruView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon'] },
  },
  {
    path: '/pengajuan/:id',
    name: 'pengajuan.show',
    component: () => import('@/views/pemohon/DetailPengajuanView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pengajuan/:id/edit',
    name: 'pengajuan.edit',
    component: () => import('@/views/pemohon/EditPengajuanView.vue'),
    meta: { requiresAuth: true, roles: ['pemohon'] },
  },
  {
    path: '/atasan/persetujuan',
    name: 'atasan.persetujuan',
    component: () => import('@/views/atasan/PersetujuanView.vue'),
    meta: { requiresAuth: true, roles: ['atasan'] },
  },
  {
    path: '/admin/verifikasi',
    name: 'admin.verifikasi',
    component: () => import('@/views/admin/VerifikasiView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/admin/surat/:id',
    name: 'admin.surat',
    component: () => import('@/views/admin/SuratView.vue'),
    meta: { requiresAuth: true, roles: ['admin_bkpsdm'] },
  },
  {
    path: '/kepala/signing',
    name: 'kepala.signing',
    component: () => import('@/views/admin/SigningView.vue'),
    meta: { requiresAuth: true, roles: ['kepala_bkpsdm'] },
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
    next('/dashboard')
  } else if (allowedRoles.length > 0 && !allowedRoles.includes(authStore.userRole)) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
