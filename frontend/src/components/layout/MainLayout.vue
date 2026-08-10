<script setup>
import { ref } from 'vue'
import TopNavbar from './TopNavbar.vue'
import AppSidebar from './Sidebar.vue'
import ToastContainer from '../ToastContainer.vue'
import ToastAutoNotifier from '../ToastAutoNotifier.vue'
import SessionWarning from '../SessionWarning.vue'

// Mobile menu state
const mobileMenuOpen = ref(false)

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 flex flex-col">
    <!-- Navbar: fixed at top -->
    <TopNavbar @toggle-mobile-menu="toggleMobileMenu" />

    <!-- Main Container: starts below navbar -->
    <div class="flex flex-1 lg:flex-1">
      <!-- Sidebar: normal flow, below navbar -->
      <!-- Mobile: fixed overlay when open -->
      <!-- Desktop: sticky positioning with full height -->
      <AppSidebar :mobile-menu-open="mobileMenuOpen" @close-mobile-menu="closeMobileMenu" />

      <!-- Main Content -->
      <main class="flex-1 p-4 sm:p-6 overflow-y-auto min-h-0">
        <slot />
      </main>
    </div>

    <!-- Mobile backdrop overlay -->
    <Transition name="fade">
      <div
        v-if="mobileMenuOpen"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        @click="closeMobileMenu"
      ></div>
    </Transition>

    <!-- Toast Notifications -->
    <div class="fixed inset-0 pointer-events-none z-50 flex flex-col items-end gap-2 p-4">
      <ToastContainer />
    </div>
    <ToastAutoNotifier />
    <SessionWarning />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
