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
  <div class="min-h-screen bg-secondary-50">
    <!-- Navbar: z-50 (highest) -->
    <TopNavbar @toggle-mobile-menu="toggleMobileMenu" />

    <!-- Main Container -->
    <div class="flex pt-16">
      <!-- Sidebar: z-40 (below navbar, above content) -->
      <!-- Desktop: visible, takes space in flex -->
      <!-- Mobile: fixed overlay, doesn't push content -->
      <AppSidebar :mobile-menu-open="mobileMenuOpen" @close-mobile-menu="closeMobileMenu" />

      <!-- Main Content -->
      <!-- Desktop: flex-1 takes remaining space -->
      <!-- Mobile: no margin change, content stays in place -->
      <!-- z-10 ensures content is below sidebar on mobile -->
      <main
        class="flex-1 p-4 sm:p-6 overflow-y-auto relative z-10"
        @click="closeMobileMenu"
      >
        <slot />
      </main>
    </div>

    <!-- Mobile backdrop overlay - z-30 (below sidebar, above content) -->
    <Transition name="fade">
      <div
        v-if="mobileMenuOpen"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        @click="closeMobileMenu"
      ></div>
    </Transition>

    <!-- Toast Notifications - z-50 (above everything) -->
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
