<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const menuGroups = computed(() => {
  const groups = []

  // ========== MENU PEMOHON & ATASAN ==========
  if (authStore.isPemohon || authStore.isAtasan) {
    // Menu Utama with Dashboard
    groups.push({
      title: 'Menu Utama',
      items: [
        { path: '/dashboard', label: 'Dashboard', icon: 'ri-dashboard-line' },
      ],
    })

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
      ],
    })
    groups.push({
      title: 'Manajemen',
      items: [
        { path: '/admin/surat', label: 'Surat Izin Belajar', icon: 'ri-file-text-line' },
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

  // ========== MENU KEPALA BKPSDM ==========
  if (authStore.isKepala) {
    groups.push({
      title: 'Tanda Tangan',
      items: [
        { path: '/kepala/signing', label: 'Surat Perlu TTE', icon: 'ri-edit-sign-line' },
      ],
    })
  }

  return groups
})

function isActive(path) {
  if (path === '/dashboard') {
    return route.path === '/dashboard'
  }
  if (path.startsWith('/admin/surat')) {
    return route.path.startsWith('/admin/surat')
  }
  return route.path.startsWith(path)
}
</script>

<template>
  <aside class="sidebar bg-white">
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
