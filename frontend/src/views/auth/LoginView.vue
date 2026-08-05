<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToastStore()

const form = ref({
  identity: '',
  password: '',
})

const loading = ref(false)

// Cek pesan session expired saat mount
onMounted(() => {
  const sessionMessage = sessionStorage.getItem('sessionMessage')
  if (sessionMessage) {
    toast.info(sessionMessage, 5000)
    sessionStorage.removeItem('sessionMessage')
    sessionStorage.removeItem('sessionExpiredShown')
  }
})

async function handleLogin() {
  loading.value = true

  try {
    await authStore.login(form.value.identity, form.value.password)

    // Redirect ke halaman yang sebelumnya diakses (jika ada)
    const redirectPath = sessionStorage.getItem('redirectAfterLogin')
    sessionStorage.removeItem('redirectAfterLogin')

    if (redirectPath && redirectPath !== '/login' && redirectPath !== '/register') {
      router.push(redirectPath)
    } else {
      // Redirect based on selected service
      const isPgaService = authStore.selectedService === 'pga'

      if (authStore.isAdmin) {
        // Admin redirect based on service
        router.push(isPgaService ? '/admin/pga-verifikasi' : '/admin/verifikasi')
      } else if (authStore.isKepala) {
        // Kepala redirect based on service
        router.push(isPgaService ? '/pga' : '/kepala/signing')
      } else if (authStore.isKepalaUnit) {
        // Kepala Unit redirect based on service
        router.push(isPgaService ? '/pga' : '/kepala/surat-tugas')
      } else {
        // Pemohon/Atasan redirect based on service
        router.push(isPgaService ? '/pga' : '/dashboard')
      }
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Login gagal. Silakan coba lagi.', 4000)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Left Side - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-accent relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 flex flex-col justify-center px-12 w-full">
        <div class="max-w-md">
          <!-- Logo -->
          <div class="flex items-center gap-4 mb-8">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center p-2">
              <img src="/logo.png" alt="Logo" class="h-full w-auto object-contain" />
            </div>
            <div>
              <h1 class="text-3xl font-bold text-white">SI-TEMA CANTIK</h1>
              <p class="text-white/80">BKPSDM Kabupaten Sukabumi</p>
            </div>
          </div>

          <!-- Tagline -->
          <div class="space-y-6">
            <h2 class="text-4xl font-bold text-white leading-tight">
              Sistem Informasi Tugas Belajar Mandiri<br>dan Pencantuman Gelar Akademik
            </h2>
            <p class="text-white/80 text-lg">
              BKPSDM Kabupaten Sukabumi
            </p>

            <!-- Features -->
            <div class="space-y-4 pt-8">
              <div class="flex items-center gap-3 text-white/90">
                <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                  <i class="ri-rocket-line text-xl"></i>
                </div>
                <span class="font-medium">Proses Pengajuan Cepat & Mudah</span>
              </div>
              <div class="flex items-center gap-3 text-white/90">
                <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                  <i class="ri-shield-check-line text-xl"></i>
                </div>
                <span class="font-medium">Terintegrasi dengan SIMPEG</span>
              </div>
              <div class="flex items-center gap-3 text-white/90">
                <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                  <i class="ri-smartphone-line text-xl"></i>
                </div>
                <span class="font-medium">Akses Dari Mana Saja</span>
              </div>
            </div>
          </div>

          <!-- Footer Info -->
          <div class="mt-12 pt-8 border-t border-white/20">
            <p class="text-white/60 text-sm">
              © {{ new Date().getFullYear() }} BKPSDM Kabupaten Sukabumi. All rights reserved.
            </p>
          </div>
        </div>
      </div>

      <!-- Bottom Wave -->
      <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
          <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
      </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-secondary-50">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden flex items-center gap-3 mb-8 justify-center">
          <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center p-1 shadow-soft">
            <img src="/logo.png" alt="Logo" class="h-full w-auto object-contain" />
          </div>
          <div>
            <h1 class="text-xl font-bold text-secondary-800">SI-TEMA CANTIK</h1>
            <p class="text-xs text-secondary-500">BKPSDM Kab. Sukabumi</p>
          </div>
        </div>

        <!-- Login Card -->
        <div class="card shadow-soft">
          <div class="card-body">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-secondary-800">Selamat Datang</h2>
              <p class="text-secondary-500 mt-1">Silakan masuk ke akun Anda</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="handleLogin" class="space-y-4">
              <div>
                <label for="identity" class="input-label">
                  NIP / Email
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400">
                    <i class="ri-user-line text-lg"></i>
                  </span>
                  <input
                    id="identity"
                    v-model="form.identity"
                    type="text"
                    required
                    class="input-field pl-10"
                    placeholder="Masukkan NIP atau Email"
                  />
                </div>
              </div>

              <div>
                <label for="password" class="input-label">
                  Password
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400">
                    <i class="ri-lock-line text-lg"></i>
                  </span>
                  <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    class="input-field pl-10"
                    placeholder="••••••••"
                  />
                </div>
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="btn btn-primary w-full py-3"
              >
                <LoadingSpinner v-if="loading" size="sm" color="white" />
                <span>{{ loading ? 'Memuat...' : 'Masuk' }}</span>
              </button>
            </form>

            <!-- Demo Accounts -->
            <div class="mt-6 p-4 rounded-xl bg-secondary-50 border border-secondary-200">
              <p class="text-xs font-semibold text-secondary-700 mb-2 flex items-center gap-1">
                <i class="ri-information-line"></i>
                Akun Demo
              </p>
              <div class="space-y-1 text-xs text-secondary-600">
                <p><span class="font-medium">Pemohon:</span> drajat@disdik.go.id / password</p>
                <p><span class="font-medium">Admin:</span> admin@bkpsdm.go.id / password</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Note -->
        <p class="text-center text-xs text-secondary-400 mt-6">
          Login dengan NIP (SIMPEG) atau Email yang terdaftar
        </p>
      </div>
    </div>
  </div>
</template>
