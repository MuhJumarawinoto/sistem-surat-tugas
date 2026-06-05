<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(false)
const surat = ref(null)
const showPreview = ref(false)
const selectedDocument = ref(null)
const showQrCode = ref(false)
const qrCodeData = ref('')

const suratId = computed(() => route.params.id)
const canSign = computed(() => {
  return authStore.isKepala && surat.value?.status === 'draft'
})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const response = await api.get(`/admin/surat-tugas-mandiri/${suratId.value}`)
    surat.value = response.data

    // If signed, prepare QR code data
    if (response.data?.status === 'signed' || response.data?.status === 'completed') {
      const qrData = {
        type: 'surat_tugas_mandiri',
        id: response.data.id,
        nomor: response.data.nomor_surat,
        signed_at: response.data.signed_at,
      }
      qrCodeData.value = JSON.stringify(qrData)
    }
  } catch (error) {
    console.error('Failed to load surat:', error)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/admin/surat-tugas-mandiri')
}

function previewPdf() {
  const token = authStore.token
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-tugas-mandiri/${suratId.value}/pdf?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
}

function downloadPdf() {
  const token = authStore.token
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-tugas-mandiri/${suratId.value}/download?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
}

async function handleSign() {
  if (!confirm('Tandatangani surat ini dengan TTE?')) return

  loading.value = true
  try {
    await api.post(`/admin/surat-tugas-mandiri/${suratId.value}/sign`)
    await loadData()
  } catch (error) {
    console.error('Failed to sign:', error)
    alert('Gagal menandatangani surat')
  } finally {
    loading.value = false
  }
}

function toggleQrCode() {
  showQrCode.value = !showQrCode.value
}

function getVerificationUrl() {
  const baseUrl = window.location.origin
  return `${baseUrl}/verify?code=${encodeURIComponent(qrCodeData.value)}`
}

function getStatusBadge(status) {
  const badges = {
    'draft': 'badge-secondary',
    'signed': 'badge-success',
    'completed': 'badge-success'
  }
  return badges[status] || 'badge-secondary'
}

function getStatusLabel(status) {
  const labels = {
    'draft': 'Draft',
    'signed': 'Ditandatangani',
    'completed': 'Selesai'
  }
  return labels[status] || status
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <div class="flex items-center justify-between mb-5">
      <div>
        <button
          @click="goBack"
          class="flex items-center gap-2 text-secondary-600 hover:text-primary-600 transition-colors mb-2"
        >
          <i class="ri-arrow-left-line"></i>
          <span class="text-sm font-medium">Kembali</span>
        </button>
        <h1 class="text-2xl font-semibold text-secondary-800">Detail Surat Tugas Mandiri</h1>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="surat?.status === 'signed'"
          @click="downloadPdf"
          class="btn btn-secondary"
        >
          <i class="ri-download-line mr-1"></i>
          Download PDF
        </button>
        <button
          v-if="surat?.status === 'signed'"
          @click="toggleQrCode"
          class="btn btn-primary"
        >
          <i class="ri-qr-code-line mr-1"></i>
          {{ showQrCode ? 'Sembunyikan' : 'Tampilkan' }} QR
        </button>
        <button
          v-if="canSign"
          @click="handleSign"
          class="btn btn-primary"
          :disabled="loading"
        >
          <i class="ri-edit-sign-line mr-1"></i>
          TTE
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <LoadingSpinner size="sm" text="Memuat..." />
    </div>

    <!-- Content -->
    <div v-else-if="surat" class="space-y-5">
      <!-- Surat Info Card -->
      <div class="card">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <h4 class="card-title">Informasi Surat</h4>
            <span class="badge" :class="getStatusBadge(surat.status)">
              {{ getStatusLabel(surat.status) }}
            </span>
          </div>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-secondary-500">Nomor Surat</p>
              <p class="font-medium">{{ surat.nomor_surat }}/TBM/{{ surat.tahun }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Tanggal Surat</p>
              <p class="font-medium">{{ new Date(surat.tanggal_surat).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Tempat TTD</p>
              <p class="font-medium">{{ surat.tempat_ttd }}</p>
            </div>
            <div v-if="surat.status === 'signed'">
              <p class="text-xs text-secondary-500">Ditandatangani</p>
              <p class="font-medium">{{ new Date(surat.signed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pegawai Info -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-user-line mr-1"></i> Informasi Pegawai
          </h4>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-secondary-500">Nama</p>
              <p class="font-medium">{{ surat.pengajuan?.user?.name }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">NIP</p>
              <p class="font-medium">{{ surat.pengajuan?.user?.nip || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Pangkat/Golongan</p>
              <p class="font-medium">{{ surat.pengajuan?.user?.pangkat_gol || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Jabatan</p>
              <p class="font-medium">{{ surat.pengajuan?.user?.jabatan || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs text-secondary-500">Unit Kerja</p>
              <p class="font-medium">{{ surat.pengajuan?.user?.unitKerja?.nama || surat.pengajuan?.user?.unit_kerja || '-' }}</p>
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-secondary-500">Program Studi</p>
              <p class="font-medium">{{ surat.pengajuan?.nama_prodi }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Jenjang</p>
              <p class="font-medium">{{ surat.pengajuan?.jenjang?.nama || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Perguruan Tinggi</p>
              <p class="font-medium">{{ surat.pengajuan?.perguruan_tinggi }}</p>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Lokasi</p>
              <p class="font-medium">{{ surat.pengajuan?.lokasi_pt }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Referensi Surat -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-file-list-line mr-1"></i> Referensi Surat
          </h4>
        </div>
        <div class="card-body space-y-3">
          <div v-if="surat.suratTugasDinas" class="bg-secondary-50 rounded-lg p-3">
            <p class="text-xs text-secondary-500">Surat Tugas Dinas</p>
            <p class="font-medium">{{ surat.suratTugasDinas.nomor_surat }}/DK/{{ surat.suratTugasDinas.bulan }}/{{ surat.suratTugasDinas.tahun }}</p>
            <p class="text-xs text-secondary-600">Dinas: {{ surat.suratTugasDinas.unitKerja?.nama || '-' }}</p>
          </div>
          <div v-if="surat.suratIzinBelajar" class="bg-secondary-50 rounded-lg p-3">
            <p class="text-xs text-secondary-500">Surat Izin Belajar</p>
            <p class="font-medium">{{ surat.suratIzinBelajar.nomor_surat }}</p>
            <p class="text-xs text-secondary-600">Ditandatangani: {{ surat.suratIzinBelajar.signed_by || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Preview Surat</h4>
        </div>
        <div class="card-body">
          <button
            @click="previewPdf"
            class="btn btn-secondary w-full"
          >
            <i class="ri-file-pdf-line mr-1"></i>
            Buka Preview PDF
          </button>
        </div>
      </div>

      <!-- QR Code Section -->
      <div v-if="surat?.status === 'signed' && showQrCode" class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-qr-code-line mr-1"></i> QR Code Verifikasi
          </h4>
        </div>
        <div class="card-body">
          <div class="text-center p-6 bg-secondary-50 rounded-xl border border-secondary-200">
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
      </div>
    </div>
  </MainLayout>
</template>
