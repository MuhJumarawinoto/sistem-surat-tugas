<script setup>
import { ref, watch, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NotificationBell from '@/components/NotificationBell.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const showUserMenu = ref(false)

// Emit event for mobile menu toggle
const emit = defineEmits(['toggleMobileMenu'])

// Get service name based on route
const serviceName = computed(() => {
  const path = route.path

  // PGA routes
  if (path.startsWith('/pga') || path.startsWith('/admin/pga')) {
    return 'Pencantuman Gelar Akademik'
  }

  // Default: Izin Belajar
  return 'Surat Tugas Belajar Mandiri'
})

function toggleMenu() {
  showUserMenu.value = !showUserMenu.value
}

function toggleMobileMenu() {
  emit('toggleMobileMenu')
}

function closeMenu() {
  showUserMenu.value = false
}

function goHome() {
  const isPgaService = authStore.selectedService === 'pga'

  if (authStore.isAuthenticated) {
    // Redirect based on role and service
    if (authStore.isAdmin) {
      router.push(isPgaService ? '/admin/pga-verifikasi' : '/admin/verifikasi')
    } else if (authStore.isKepala) {
      router.push(isPgaService ? '/pga' : '/kepala/signing')
    } else if (authStore.isKepalaUnit) {
      router.push(isPgaService ? '/pga' : '/kepala/surat-tugas')
    } else {
      router.push(isPgaService ? '/pga' : '/dashboard')
    }
  } else {
    router.push('/login')
  }
}

async function handleLogout() {
  closeMenu()
  await authStore.logout()
  router.push('/service-selection')
}

function getInitials(name) {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function getRoleLabel(role) {
  const labels = {
    'pemohon': 'PNS Pemohon',
    'atasan': 'Atasan Langsung',
    'admin_bkpsdm': 'Admin BKPSDM',
    'kepala_bkpsdm': 'Kepala BKPSDM'
  }
  return labels[role] || role
}

// Close menu when clicking outside
function handleDocumentClick(event) {
  const menuEl = document.getElementById('user-dropdown-menu')
  const buttonEl = document.getElementById('user-menu-button')

  if (showUserMenu.value && menuEl && !menuEl.contains(event.target) && !buttonEl?.contains(event.target)) {
    closeMenu()
  }
}

watch(showUserMenu, (isOpen) => {
  if (isOpen) {
    document.addEventListener('click', handleDocumentClick)
  } else {
    document.removeEventListener('click', handleDocumentClick)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
  <!-- Top Navbar -->
  <nav class="top-navbar bg-gradient-to-r from-primary-700 via-primary-600 to-accent shadow-lg z-50">
    <div class="flex items-center justify-between px-4 sm:px-6 h-16">
      <!-- Left Side: Hamburger + Logo & Title -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Hamburger Menu Button (Mobile Only) -->
        <button
          v-if="authStore.isAuthenticated"
          @click="toggleMobileMenu"
          class="lg:hidden btn btn-ghost btn-icon text-white hover:bg-white/10"
          aria-label="Toggle menu"
        >
          <i class="ri-menu-line text-2xl"></i>
        </button>

        <!-- Logo & Title -->
        <div class="flex items-center gap-2 sm:gap-3 cursor-pointer" @click="goHome">
        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center p-1 flex-shrink-0">
          <img src="/logo.png" alt="Logo" class="h-full w-auto object-contain" />
        </div>
        <div class="text-white">
          <h1 class="text-base sm:text-lg font-bold leading-tight">SI-TEMA CANTIK {{ serviceName }}</h1>
          <p class="text-xs text-white/80 hidden sm:block">BKPSDM Kabupaten Sukabumi</p>
        </div>
      </div>
    </div>

    <!-- Right Side Actions -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- User Info (when logged in) -->
        <template v-if="authStore.isAuthenticated">
          <!-- Notification Bell -->
          <NotificationBell />

          <!-- User Avatar (Clickable) -->
          <div class="relative">
            <button
              id="user-menu-button"
              type="button"
              @click="toggleMenu"
              class="flex items-center gap-2 sm:gap-3 px-2 sm:px-3 py-2 rounded-lg bg-white/10 border border-white/20 hover:bg-white/15 transition-colors cursor-pointer"
            >
              <div class="text-right hidden sm:block">
                <p class="text-xs sm:text-sm font-semibold text-white leading-tight">{{ authStore.user?.name }}</p>
                <p class="text-xs text-white/70">{{ authStore.user?.nip || authStore.user?.email }}</p>
              </div>
              <div class="avatar avatar-sm sm:avatar-md bg-white/20 text-white border-2 border-white/30 flex-shrink-0">
                {{ getInitials(authStore.user?.name) }}
              </div>
              <i class="ri-arrow-down-s-line text-white hidden sm:block"></i>
            </button>

            <!-- Dropdown Menu -->
            <Transition name="dropdown">
              <div
                v-if="showUserMenu"
                id="user-dropdown-menu"
                class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-secondary-100 py-2 z-[100]"
                @click.stop
              >
                <!-- User Info Header -->
                <div class="px-4 py-3 border-b border-secondary-100">
                  <p class="text-sm font-semibold text-secondary-800">{{ authStore.user?.name }}</p>
                  <p class="text-xs text-secondary-500">{{ authStore.user?.nip || authStore.user?.email }}</p>
                  <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700">
                    {{ getRoleLabel(authStore.user?.role) }}
                  </span>
                </div>

                <!-- Menu Items -->
                <div class="py-1">
                  <router-link
                    to="/profile"
                    @click="closeMenu"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors"
                  >
                    <i class="ri-user-line text-lg text-secondary-400"></i>
                    <span>Profile</span>
                  </router-link>

                  <div class="border-t border-secondary-100 my-1"></div>

                  <button
                    @click="handleLogout"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <i class="ri-logout-box-r-line text-lg"></i>
                    <span>Logout</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </template>

        <!-- Login Button (when logged out) -->
        <router-link
          v-else
          to="/login"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-primary-700 hover:bg-white/90 font-medium transition-colors"
        >
          <i class="ri-login-line text-base"></i>
          <span>Login</span>
        </router-link>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.top-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

/* Responsive avatar sizes */
.avatar-sm {
  width: 32px;
  height: 32px;
  font-size: 12px;
}

.avatar-md {
  width: 40px;
  height: 40px;
  font-size: 14px;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
