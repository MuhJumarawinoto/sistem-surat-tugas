<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()

const selectedService = ref(null)
const loading = ref(true)

onMounted(() => {
  // Initialize auth store from localStorage
  authStore.initializeFromStorage()

  // Redirect jika sudah login
  if (authStore.isAuthenticated) {
    // Redirect based on selected service and role
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
    return
  }

  loading.value = false
})

function selectService(service) {
  selectedService.value = service
  // Store selected service in auth store
  authStore.setService(service)
  // Small delay for visual feedback
  setTimeout(() => {
    // Always go to login (service is already stored)
    router.push('/login')
  }, 200)
}
</script>

<template>
  <!-- Loading State -->
  <div v-if="loading" class="min-h-screen flex items-center justify-center bg-secondary-50">
    <LoadingSpinner size="lg" text="Memuat..." />
  </div>

  <!-- Service Selection -->
  <div v-else class="min-h-screen flex">
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

    <!-- Right Side - Service Selection -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-secondary-50">
      <div class="w-full max-w-2xl">
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

        <!-- Selection Cards -->
        <div class="space-y-6">
          <!-- Header -->
          <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-secondary-800">Pilih Layanan</h2>
            <p class="text-secondary-500 mt-1">Silakan pilih jenis layanan yang ingin Anda ajukan</p>
          </div>

          <!-- Service Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tugas Belajar Mandiri Card -->
            <div
              @click="selectService('tugas-belajar')"
              class="group cursor-pointer"
            >
              <div class="card h-full transition-all duration-300 hover:shadow-xl hover:-translate-y-1 border-2 transition-colors"
                   :class="selectedService === 'tugas-belajar' ? 'border-primary-500 bg-primary-50' : 'border-transparent hover:border-primary-300'">
                <div class="card-body">
                  <!-- Icon -->
                  <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i class="ri-book-open-line text-3xl text-white"></i>
                  </div>

                  <!-- Title -->
                  <h3 class="text-xl font-bold text-secondary-800 mb-2">Tugas Belajar Mandiri</h3>

                  <!-- Description -->
                  <p class="text-secondary-500 text-sm mb-4">
                    Pengajuan izin belajar untuk PNS yang ingin melanjutkan pendidikan tanpa diberhentikan dari jabatan
                  </p>

                  <!-- Features -->
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Izin belajar D1/D2/D3/S1/S2/S3</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Biaya mandiri (tidak membebankan APBD)</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Proses online & verifikasi dokumen</span>
                    </div>
                  </div>

                  <!-- Button -->
                  <div class="mt-6 pt-4 border-t border-secondary-100">
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 group-hover:gap-3 transition-all">
                      Pilih Layanan
                      <i class="ri-arrow-right-line"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pencantuman Gelar Akademik Card -->
            <div
              @click="selectService('pga')"
              class="group cursor-pointer"
            >
              <div class="card h-full transition-all duration-300 hover:shadow-xl hover:-translate-y-1 border-2 transition-colors"
                   :class="selectedService === 'pga' ? 'border-accent bg-accent/10' : 'border-transparent hover:border-accent/50'">
                <div class="card-body">
                  <!-- Icon -->
                  <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-accent to-accent/80 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i class="ri-graduation-cap-line text-3xl text-white"></i>
                  </div>

                  <!-- Title -->
                  <h3 class="text-xl font-bold text-secondary-800 mb-2">Pencantuman Gelar Akademik</h3>

                  <!-- Description -->
                  <p class="text-secondary-500 text-sm mb-4">
                    Pencantuman gelar akademik bagi PNS yang telah menyelesaikan pendidikan dan memenuhi syarat
                  </p>

                  <!-- Features -->
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Sesuai Peraturan BKN</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Untuk lulusan D3/S1/S2/S3</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-secondary-600">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                      <span>Proses verifikasi berkas</span>
                    </div>
                  </div>

                  <!-- Button -->
                  <div class="mt-6 pt-4 border-t border-secondary-100">
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-accent group-hover:gap-3 transition-all">
                      Pilih Layanan
                      <i class="ri-arrow-right-line"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Note -->
          <div class="mt-8 p-4 rounded-xl bg-info-50 border border-info-200">
            <p class="text-sm text-info-800 flex items-start gap-2">
              <i class="ri-information-line text-lg mt-0.5 flex-shrink-0"></i>
              <span>
                Pilih layanan di atas untuk melanjutkan. Anda akan diarahkan ke halaman login untuk memproses pengajuan.
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-4px);
}
</style>
