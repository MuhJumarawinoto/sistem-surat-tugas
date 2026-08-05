<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NotificationBell from '@/components/NotificationBell.vue'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/service-selection')
}

function getInitials(name) {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <header class="app-header sticky top-0 z-40 bg-white border-b border-secondary-200">
    <div class="flex items-center justify-between">
      <!-- Logo & Title -->
      <div class="flex items-center gap-4">
        <!-- Logo -->
        <div class="w-14 h-14 flex items-center justify-center">
          <img src="/logo.png" alt="Logo" class="h-12 w-auto object-contain" />
        </div>

        <div>
          <h1 class="text-lg font-bold text-secondary-800 tracking-tight">SI-TEMA CANTIK</h1>
          <p class="text-xs text-secondary-500">BKPSDM Kabupaten Sukabumi</p>
        </div>
      </div>

      <!-- User Info & Actions -->
      <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        <NotificationBell v-if="authStore.isPemohon" />

        <!-- User Info Display -->
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-secondary-50 border-l border-secondary-200">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-secondary-800">{{ authStore.user?.name }}</p>
            <p class="text-xs text-secondary-500">{{ authStore.user?.nip || authStore.user?.email }}</p>
          </div>

          <!-- Avatar -->
          <div class="avatar avatar-md bg-primary-100 text-primary-700">
            {{ getInitials(authStore.user?.name) }}
          </div>
        </div>

        <!-- Profile Button -->
        <router-link
          to="/profile"
          class="btn btn-ghost btn-icon"
          title="Profile"
        >
          <i class="ri-user-line text-lg"></i>
        </router-link>

        <!-- Logout Button -->
        <button
          @click="handleLogout"
          class="btn btn-secondary gap-2"
          title="Logout"
        >
          <i class="ri-logout-box-r-line"></i>
          <span class="hidden sm:inline">Logout</span>
        </button>
      </div>
    </div>

    <!-- BKPSDM Branding Bar -->
    <div class="bkpsdm-accent mt-4"></div>
  </header>
</template>

<style scoped>
</style>
