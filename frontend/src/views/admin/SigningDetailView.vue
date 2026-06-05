<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const kepalaBKPSDM = computed(() => authStore.user)

const suratIzin = ref(null)
const pengajuan = ref(null)
const loading = ref(false)
const signing = ref(false)
const showQrCode = ref(false)
const qrCodeData = ref('')

onMounted(async () => {
  await loadSuratIzin()
})

async function loadSuratIzin() {
  loading.value = true
  try {
    console.log('Loading surat izin ID:', route.params.id)

    if (!route.params.id || route.params.id === 'undefined') {
      throw new Error('Invalid surat ID')
    }

    // Ambil detail surat izin
    const response = await api.get(`/admin/surat-izin/${route.params.id}`)

    console.log('Response:', response.data)

    // Handle different response formats
    const suratData = response.data?.data || response.data

    if (!suratData) {
      throw new Error('Surat tidak ditemukan')
    }

    suratIzin.value = suratData
    pengajuan.value = suratData.pengajuan

    // If signed, prepare QR code data
    if (suratData.status === 'signed' || suratData.status === 'completed') {
      const qrData = {
        type: 'surat_izin_belajar',
        id: suratData.id,
        nomor: suratData.nomor_surat,
        signed_at: suratData.signed_at,
      }
      qrCodeData.value = JSON.stringify(qrData)
    }
  } catch (error) {
    console.error('Failed to load surat izin:', error)
    const message = error.response?.data?.message || error.message || 'Gagal memuat surat izin'
    alert(message + ' (ID: ' + route.params.id + ')')
    router.push('/kepala/signing')
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/kepala/signing')
}

async function signSurat() {
  if (!confirm('Tandatangani surat izin belajar ini? Tanda tangan elektronik akan diterapkan.')) return

  signing.value = true
  try {
    await api.post(`/admin/surat-izin/${route.params.id}/sign`, {
      tte_path: '/placeholder/path/tte.pdf', // TODO: Implement TTE integration
      qr_code: `QR-${route.params.id}`
    })
    alert('Surat berhasil ditandatangani')
    await loadSuratIzin()
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menandatangani surat'
    alert(message)
  } finally {
    signing.value = false
  }
}

function getNomorSurat() {
  return suratIzin.value?.nomor_surat || '-'
}

async function downloadSurat() {
  if (!suratIzin.value?.id) return

  try {
    // Use direct download with token to avoid CORS issues
    const token = localStorage.getItem('token')
    const baseUrl = import.meta.env.VITE_API_URL
      ? import.meta.env.VITE_API_URL.replace('/api', '')
      : 'http://localhost:8000'

    const url = `${baseUrl}/api/admin/surat-izin/${suratIzin.value.id}/download?token=${encodeURIComponent(token)}`
    window.open(url, '_blank')
  } catch (error) {
    console.error('Download failed:', error)
    const message = error.response?.data?.message || 'Gagal mendownload surat'
    alert(message)
  }
}

function toggleQrCode() {
  showQrCode.value = !showQrCode.value
}

function getVerificationUrl() {
  const baseUrl = window.location.origin
  return `${baseUrl}/verify?code=${encodeURIComponent(qrCodeData.value)}`
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <button
          @click="goBack"
          class="flex items-center gap-2 text-secondary-600 hover:text-primary-600 transition-colors mb-2"
        >
          <i class="ri-arrow-left-line"></i>
          <span class="text-sm font-medium">Kembali ke Daftar</span>
        </button>
        <h1 class="text-2xl font-semibold text-secondary-800">Tanda Tangan Surat Izin Belajar</h1>
        <p v-if="suratIzin" class="text-secondary-500">Nomor: {{ getNomorSurat() }}</p>
      </div>

      <button
        v-if="suratIzin?.status === 'draft'"
        @click="signSurat"
        class="btn btn-primary"
        :disabled="signing"
      >
        <i v-if="signing" class="ri-loader-4-line animate-spin mr-1"></i>
        <i v-else class="ri-edit-line mr-1"></i>
        {{ signing ? 'Memproses...' : 'Tandatangani Surat' }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <LoadingSpinner size="md" text="Memuat data..." />
    </div>

    <div v-else-if="pengajuan" class="space-y-5">
      <!-- Info Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Pegawai Info -->
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              <i class="ri-user-line mr-1"></i> Informasi Pegawai
            </h4>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div>
                <p class="text-xs text-secondary-500">Nama</p>
                <p class="font-medium">{{ pengajuan.user?.name }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">NIP</p>
                <p class="font-medium">{{ pengajuan.user?.nip || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Pangkat/Golongan</p>
                <p class="font-medium">{{ pengajuan.user?.pangkat_gol || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Jabatan</p>
                <p class="font-medium">{{ pengajuan.user?.jabatan || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Unit Kerja</p>
                <p class="font-medium">{{ pengajuan.user?.unit_kerja || '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Pendidikan Info -->
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              <i class="ri-graduation-cap-line mr-1"></i> Informasi Pendidikan
            </h4>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div>
                <p class="text-xs text-secondary-500">Jenjang</p>
                <p class="font-medium">{{ pengajuan.jenjang?.nama_jenjang || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Program Studi</p>
                <p class="font-medium">{{ pengajuan.nama_prodi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Perguruan Tinggi</p>
                <p class="font-medium">{{ pengajuan.perguruan_tinggi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Akreditasi Prodi</p>
                <p class="font-medium">{{ pengajuan.akreditasi_prodi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Lokasi</p>
                <p class="font-medium">{{ pengajuan.lokasi_pt }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Penandatangan Info -->
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              <i class="ri-draft-line mr-1"></i> Penandatangan
            </h4>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div>
                <p class="text-xs text-secondary-500">Penandatangan</p>
                <p class="font-medium">Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Nama</p>
                <p class="font-medium">{{ kepalaBKPSDM?.name || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">NIP</p>
                <p class="font-medium">{{ kepalaBKPSDM?.nip || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Pangkat/Golongan</p>
                <p class="font-medium">{{ kepalaBKPSDM?.pangkat_gol || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Milestone -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-route-line mr-1"></i> Progress Pengajuan
          </h4>
        </div>
        <div class="card-body">
          <PengajuanMilestone :pengajuan-id="pengajuan.id" />
        </div>
      </div>

      <!-- Surat Preview -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-file-text-line mr-1"></i> Status Surat Izin Belajar
          </h4>
        </div>
        <div class="card-body">
          <div v-if="suratIzin?.status === 'signed' || suratIzin?.status === 'completed'" class="text-center py-8">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <i class="ri-checkbox-circle-line text-3xl text-green-600"></i>
            </div>
            <p class="text-green-700 font-medium mb-2">Surat Sudah Ditandatangani</p>
            <div class="flex items-center justify-center gap-2 mb-4">
              <button @click="downloadSurat" class="btn btn-secondary btn-sm">
                <i class="ri-download-line mr-1"></i>
                Download Surat
              </button>
              <button @click="toggleQrCode" class="btn btn-primary btn-sm">
                <i class="ri-qr-code-line mr-1"></i>
                {{ showQrCode ? 'Sembunyikan' : 'Tampilkan' }} QR Code
              </button>
            </div>

            <!-- QR Code Display -->
            <div v-if="showQrCode" class="mt-6 p-6 bg-secondary-50 rounded-xl border border-secondary-200">
              <p class="text-sm text-secondary-500 mb-4">Scan QR Code untuk memverifikasi keaslian surat</p>
              <div class="bg-white p-4 rounded-lg inline-block">
                <div class="w-48 h-48 flex items-center justify-center bg-secondary-100 rounded-lg">
                  <img
                    v-if="qrCodeData"
                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrCodeData)}`"
                    alt="QR Code"
                    class="w-full h-full"
                  />
                  <div v-else class="text-secondary-400 text-center">
                    <i class="ri-qr-code-line text-4xl"></i>
                    <p class="text-xs mt-2">QR Code</p>
                  </div>
                </div>
              </div>
              <div class="mt-4 text-xs text-secondary-400">
                <p>Atau verifikasi secara online di:</p>
                <a :href="getVerificationUrl()" target="_blank" class="text-primary-600 hover:underline break-all">
                  {{ getVerificationUrl() }}
                </a>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-secondary-500">
            <div class="w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center mx-auto mb-4">
              <i class="ri-edit-line text-3xl text-yellow-600"></i>
            </div>
            <p class="text-yellow-700 font-medium mb-2">Surat Belum Ditandatangani</p>
            <p>Klik tombol Tandatangani Surat untuk memproses TTE</p>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
