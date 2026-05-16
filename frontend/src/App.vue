<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      await authStore.logout()
      router.push('/login')
    }
  }
})
</script>

<template>
  <div id="app" class="min-h-screen bg-gray-100">
    <router-view />
  </div>
</template>
