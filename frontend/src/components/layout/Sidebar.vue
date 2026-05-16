<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const menuItems = computed(() => {
  const items = [
    { path: '/dashboard', label: 'Dashboard', icon: '🏠' },
  ]

  if (authStore.isPemohon) {
    items.push(
      { path: '/pengajuan', label: 'Riwayat Pengajuan', icon: '📋' },
      { path: '/pengajuan/baru', label: 'Buat Pengajuan Baru', icon: '➕' },
    )
  }

  if (authStore.isAtasan) {
    items.push(
      { path: '/atasan/persetujuan', label: 'Persetujuan Pengajuan', icon: '✅' },
    )
  }

  if (authStore.isAdmin) {
    items.push(
      { path: '/admin/verifikasi', label: 'Verifikasi Pengajuan', icon: '🔍' },
    )
  }

  if (authStore.isKepala) {
    items.push(
      { path: '/kepala/signing', label: 'Tanda Tangan Surat', icon: '✒️' },
    )
  }

  return items
})

function isActive(path) {
  if (path === '/dashboard') {
    return route.path === '/dashboard'
  }
  return route.path.startsWith(path)
}
</script>

<template>
  <aside class="bg-white shadow-sm w-64 min-h-screen">
    <nav class="p-4 space-y-2">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors"
        :class="isActive(item.path) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
      >
        <span class="text-xl">{{ item.icon }}</span>
        <span class="font-medium">{{ item.label }}</span>
      </router-link>
    </nav>
  </aside>
</template>
