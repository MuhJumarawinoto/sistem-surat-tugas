<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NotificationBell from '@/components/NotificationBell.vue'
import NotificationAlertModal from '@/components/NotificationAlertModal.vue'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
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
        <!-- Logo Pemda/BKPSDM -->
        <div class="w-12 h-12 bg-gradient-to-br from-primary-600 to-accent rounded-xl flex items-center justify-center shadow-soft">
          <img v-if="false" src="/logo-bkpsdm.png" alt="BKPSDM" class="w-8 h-8 object-contain" />
          <svg v-else class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
          </svg>
        </div>

        <div>
          <h1 class="text-lg font-bold text-secondary-800 tracking-tight">SIPINTAR</h1>
          <p class="text-xs text-secondary-500">Sistem Informasi Pendidikan & Tunjangan</p>
        </div>
      </div>

      <!-- User Info & Actions -->
      <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        <NotificationBell v-if="authStore.isPemohon" />

        <!-- User Profile -->
        <div class="flex items-center gap-3 pl-4 border-l border-secondary-200">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-secondary-800">{{ authStore.user?.name }}</p>
            <p class="text-xs text-secondary-500">{{ authStore.user?.nip || authStore.user?.email }}</p>
          </div>

          <!-- Avatar -->
          <div class="avatar avatar-md bg-primary-100 text-primary-700">
            {{ getInitials(authStore.user?.name) }}
          </div>

          <!-- Logout Button -->
          <button
            @click="handleLogout"
            class="btn btn-ghost btn-icon text-secondary-500 hover:text-danger hover:bg-red-50"
            title="Keluar"
          >
            <i class="ri-logout-box-r-line text-lg"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- BKPSDM Branding Bar -->
    <div class="bkpsdm-accent mt-4"></div>

    <NotificationAlertModal v-if="authStore.isPemohon" />
  </header>
</template>
