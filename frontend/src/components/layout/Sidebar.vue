<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const menuGroups = computed(() => {
  const groups = []

  // Main Menu
  const mainMenu = [
    { path: '/dashboard', label: 'Dashboard', icon: 'ri-dashboard-line', badge: null },
  ]

  if (authStore.isPemohon) {
    mainMenu.push(
      { path: '/pengajuan', label: 'Riwayat Pengajuan', icon: 'ri-file-list-3-line', badge: null },
      { path: '/pengajuan/baru', label: 'Buat Pengajuan Baru', icon: 'ri-add-circle-line', badge: null },
    )
  }

  if (authStore.isAtasan) {
    mainMenu.push(
      { path: '/atasan/persetujuan', label: 'Persetujuan', icon: 'ri-check-double-line', badge: null },
    )
  }

  if (authStore.isAdmin) {
    mainMenu.push(
      { path: '/admin/verifikasi', label: 'Verifikasi', icon: 'ri-verified-badge-line', badge: null },
      { path: '/admin/surat', label: 'Tanda Tangan Surat', icon: 'ri-edit-sign-line', badge: null },
      { path: '/admin/pegawai', label: 'Data Pegawai', icon: 'ri-team-line', badge: null },
      { path: '/admin/pddikti-sync', label: 'Sync PDDikti', icon: 'ri-refresh-line', badge: null },
    )
  }

  if (authStore.isKepala) {
    mainMenu.push(
      { path: '/kepala/signing', label: 'Tanda Tangan Surat', icon: 'ri-edit-sign-line', badge: null },
    )
  }

  groups.push({
    title: 'Menu Utama',
    items: mainMenu,
  })

  return groups
})

function isActive(path) {
  if (path === '/dashboard') {
    return route.path === '/dashboard'
  }
  if (path === '/admin/surat') {
    return route.path.match(/^\/admin\/surat\/?\d*$/) && route.path !== '/admin/surat/create'
  }
  return route.path.startsWith(path)
}
</script>

<template>
  <aside class="sidebar bg-white">
    <!-- Sidebar Header -->
    <!-- <div class="sidebar-header border-b border-primary-100">
      <div class="flex items-center gap-3"> -->
        <!-- <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-accent rounded-lg flex items-center justify-center">
          <img src="/logo.png" alt="Logo" class="w-6 h-6 object-contain" />
        </div> -->
        <!-- <div> -->
          <!-- <p class="text-sm font-bold text-secondary-800">Menu Utama</p>
          <p class="text-xs text-secondary-500">Navigasi Aplikasi</p> -->
        <!-- </div> -->
      <!-- </div>
    </div> -->

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
            <span v-if="item.badge" class="ml-auto badge badge-primary">{{ item.badge }}</span>
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
