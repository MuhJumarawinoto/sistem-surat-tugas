<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props for mobile menu
const props = defineProps({
  mobileMenuOpen: {
    type: Boolean,
    default: false
  }
})

// Emit event for close
const emit = defineEmits(['closeMobileMenu'])

// Close mobile menu when route changes
watch(() => route.path, () => {
  if (props.mobileMenuOpen) {
    emit('closeMobileMenu')
  }
})

const menuGroups = computed(() => {
  const groups = []

  // ========== MENU PEMOHON & ATASAN ONLY ==========
  if (authStore.isPemohon || authStore.isAtasan) {
    // Menu Utama with Dashboard
    groups.push({
      title: 'Menu Utama',
      items: [
        { path: '/dashboard', label: 'Dashboard', icon: 'ri-dashboard-line' },
      ],
    })
  }

  // ========== MENU PENGAJUAN (PEMOHON & ATASAN ONLY) ==========
  if (authStore.isPemohon || authStore.isAtasan) {
    groups.push({
      title: 'Pengajuan',
      items: [
        { path: '/pengajuan', label: 'Riwayat Pengajuan', icon: 'ri-file-list-3-line' },
        { path: '/pengajuan/baru', label: 'Buat Pengajuan Baru', icon: 'ri-add-circle-line' },
      ],
    })
  }

  // ========== MENU ADMIN BKPSDM ==========
  if (authStore.isAdmin) {
    // Admin starts directly with Verifikasi (no Dashboard)
    groups.push({
      title: 'Verifikasi',
      items: [
        { path: '/admin/verifikasi', label: 'Verifikasi Dokumen', icon: 'ri-verified-badge-line' },
        { path: '/admin/riwayat-verifikasi', label: 'Riwayat Verifikasi', icon: 'ri-history-line' },
      ],
    })
    groups.push({
      title: 'Surat',
      items: [
        { path: '/admin/surat-izin', label: 'Surat Izin Belajar', icon: 'ri-file-text-line' },
        { path: '/admin/surat-tugas', label: 'Surat Tugas Belajar', icon: 'ri-file-list-line' },
      ],
    })
    groups.push({
      title: 'Manajemen Data',
      items: [
        { path: '/admin/pegawai', label: 'Data Pegawai', icon: 'ri-team-line' },
      ],
    })
    groups.push({
      title: 'Master Data',
      items: [
        { path: '/admin/pddikti-sync', label: 'Sync PDDikti', icon: 'ri-refresh-line' },
      ],
    })
  }

  // ========== MENU KEPALA (Kepala Unit / Kepala BKPSDM) ==========
  if (authStore.isKepala) {
    groups.push({
      title: 'Tanda Tangan',
      items: [
        { path: '/kepala/signing', label: 'Surat Perlu TTE', icon: 'ri-edit-sign-line' },
        { path: '/kepala/riwayat', label: 'Riwayat TTE', icon: 'ri-history-line' },
      ],
    })

    // Additional menu for Kepala Unit (non-BKPSDM)
    if (authStore.user?.is_kepala_unit) {
      groups.push({
        title: 'Surat Tugas',
        items: [
          { path: '/kepala/surat-tugas', label: 'Surat Tugas Belajar', icon: 'ri-file-list-line' },
        ],
      })
    }
  }

  return groups
})

function isActive(path) {
  // Exact match for dashboard
  if (path === '/dashboard') {
    return route.path === '/dashboard'
  }

  // Admin verifikasi routes
  if (path === '/admin/verifikasi') {
    return route.path === '/admin/verifikasi' || route.path.startsWith('/admin/verifikasi/')
  }
  if (path === '/admin/riwayat-verifikasi') {
    return route.path === '/admin/riwayat-verifikasi' || route.path.startsWith('/admin/riwayat-verifikasi/')
  }

  // Exact match for admin surat routes (no startsWith - causes all to highlight)
  if (path === '/admin/surat-izin') {
    return route.path === '/admin/surat-izin' || route.path.startsWith('/admin/surat-izin/')
  }
  if (path === '/admin/surat-tugas') {
    return route.path === '/admin/surat-tugas' || route.path.startsWith('/admin/surat-tugas/')
  }
  if (path === '/admin/surat-tugas-mandiri') {
    return route.path === '/admin/surat-tugas-mandiri' || route.path.startsWith('/admin/surat-tugas-mandiri/')
  }

  // Kepala routes
  if (path.startsWith('/kepala/surat-tugas')) {
    return route.path.startsWith('/kepala/surat-tugas')
  }
  if (path.startsWith('/kepala/signing')) {
    return route.path.startsWith('/kepala/signing')
  }
  if (path.startsWith('/kepala/riwayat')) {
    return route.path.startsWith('/kepala/riwayat')
  }

  // Default: check if route starts with path
  return route.path.startsWith(path)
}
</script>

<template>
  <!-- Mobile sidebar with off-canvas behavior -->
  <aside
    class="sidebar bg-white fixed lg:relative z-40 transition-transform duration-300 ease-in-out"
    :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Close Button (Mobile Only) -->
    <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-secondary-200">
      <span class="font-semibold text-secondary-800">Menu</span>
      <button
        @click="emit('closeMobileMenu')"
        class="btn btn-ghost btn-icon text-secondary-600 hover:text-secondary-800"
        aria-label="Close menu"
      >
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav overflow-y-auto scrollbar-thin">
      <div v-for="(group, groupIndex) in menuGroups" :key="groupIndex" class="mb-6">
        <p v-if="group.title" class="px-3 mb-2 text-xs font-semibold text-secondary-400 uppercase tracking-wider">
          {{ group.title }}
        </p>
        <div class="space-y-1">
          <router-link
            v-for="item in group.items"
            :key="item.path"
            :to="item.path"
            class="nav-link"
            :class="isActive(item.path) ? 'active' : ''"
          >
            <i :class="item.icon" class="text-lg"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </div>
      </div>

      <!-- Sidebar Footer -->
      <div class="mt-auto pt-4 border-t border-secondary-200 px-3">
        <div class="p-3 rounded-lg bg-gradient-to-br from-primary-50 to-accent/10 border border-primary-200">
          <div class="flex items-center gap-2 text-xs text-primary-700">
            <i class="ri-shield-check-line"></i>
            <span class="font-medium">Sistem Aman</span>
          </div>
          <p class="text-xs text-primary-600 mt-1">Terhubung dengan SIMPEG</p>
        </div>
      </div>
    </nav>
  </aside>
</template>

<style scoped>
/* Sidebar base styles */
.sidebar {
  /* Desktop: full height of viewport, taking space in flex container */
  height: calc(100vh - 64px);
}

/* Mobile: fixed positioning, below navbar */
@media (max-width: 1023px) {
  .sidebar {
    position: fixed;
    top: 64px;
    left: 0;
  }
}

/* Desktop: relative positioning, part of flex layout */
@media (min-width: 1024px) {
  .sidebar {
    position: relative;
    top: 0;
  }
}
</style>
