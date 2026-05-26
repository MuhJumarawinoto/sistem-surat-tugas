<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await authStore.fetchUser()
      // Start token validity check
      authStore.startTokenCheck()
    } catch (error) {
      await authStore.logout()
      router.push('/login')
    }
  }
})

onUnmounted(() => {
  // Stop token check when app unmounts
  authStore.stopTokenCheck()
})
</script>

<template>
  <div id="app" class="min-h-screen bg-gray-100">
    <router-view />
  </div>
</template>
