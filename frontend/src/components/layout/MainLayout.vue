<script setup>
import TopNavbar from './TopNavbar.vue'
import AppSidebar from './Sidebar.vue'
import ToastContainer from '../ToastContainer.vue'
import ToastAutoNotifier from '../ToastAutoNotifier.vue'
import SessionWarning from '../SessionWarning.vue'
</script>

<template>
  <div class="min-h-screen bg-secondary-50">
    <TopNavbar />
    <div class="flex pt-16">
      <AppSidebar />
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
    <ToastContainer />
    <ToastAutoNotifier />
    <SessionWarning />
  </div>
</template>
