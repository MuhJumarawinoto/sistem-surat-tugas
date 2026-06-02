<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const qrInput = ref('')
const verifying = ref(false)
const result = ref(null)
const error = ref(null)
const showScanner = ref(false)

async function verifySurat() {
  if (!qrInput.value.trim()) {
    error.value = 'Silakan masukkan kode QR atau nomor surat'
    return
  }

  verifying.value = true
  error.value = null
  result.value = null

  try {
    // Try surat izin belajar first
    try {
      const response = await api.get(`/surat-izin/verify/${encodeURIComponent(qrInput.value)}`)
      result.value = {
        type: 'surat_izin_belajar',
        data: response.data.data
      }
      return
    } catch (e) {
      // Not a surat izin, try surat tugas dinas
    }

    // Try surat tugas dinas
    try {
      const response = await api.get(`/surat-tugas/verify/${encodeURIComponent(qrInput.value)}`)
      result.value = {
        type: 'surat_tugas_dinas',
        data: response.data.data
      }
      return
    } catch (e) {
      // Not a surat tugas dinas, try surat tugas mandiri
    }

    // Try surat tugas mandiri
    try {
      const response = await api.get(`/surat-tugas-mandiri/verify/${encodeURIComponent(qrInput.value)}`)
      result.value = {
        type: 'surat_tugas_mandiri',
        data: response.data.data
      }
      return
    } catch (e) {
      throw new Error('Surat tidak ditemukan atau tidak valid')
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Gagal memverifikasi surat'
  } finally {
    verifying.value = false
  }
}

function getSuratTypeLabel(type) {
  const labels = {
    'surat_izin_belajar': 'Surat Izin Belajar Mandiri',
    'surat_tugas_dinas': 'Surat Tugas Dinas',
    'surat_tugas_mandiri': 'Surat Tugas Belajar Mandiri'
  }
  return labels[type] || type
}

function getSuratTypeIcon(type) {
  const icons = {
    'surat_izin_belajar': 'ri-file-text-line',
    'surat_tugas_dinas': 'ri-file-list-line',
    'surat_tugas_mandiri': 'ri-task-line'
  }
  return icons[type] || 'ri-file-line'
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-accent/20 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-100 mb-4">
          <i class="ri-qr-code-line text-4xl text-primary-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-secondary-800 mb-2">Verifikasi Keaslian Surat</h1>
        <p class="text-secondary-500">Scan QR Code atau masukkan nomor surat untuk memverifikasi keaslian</p>
      </div>

      <!-- Main Card -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6">
          <!-- Input Form -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-secondary-700 mb-2">
              Kode QR / Nomor Surat
            </label>
            <div class="flex gap-2">
              <input
                v-model="qrInput"
                type="text"
                placeholder="Masukkan kode QR atau nomor surat..."
                class="flex-1 px-4 py-3 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                @keyup.enter="verifySurat"
              />
              <button
                @click="verifySurat"
                :disabled="verifying"
                class="btn btn-primary px-6"
              >
                <i v-if="verifying" class="ri-loader-4-line animate-spin"></i>
                <i v-else class="ri-search-line"></i>
              </button>
            </div>
          </div>

          <!-- Error State -->
          <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start gap-3">
              <i class="ri-error-warning-line text-red-500 text-xl mt-0.5"></i>
              <div>
                <p class="font-medium text-red-700">Verifikasi Gagal</p>
                <p class="text-sm text-red-600">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Result State -->
          <div v-if="result && !verifying" class="space-y-4">
            <!-- Success Header -->
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                </div>
                <div>
                  <p class="font-medium text-green-700">Surat Valid</p>
                  <p class="text-sm text-green-600">{{ getSuratTypeLabel(result.type) }}</p>
                </div>
              </div>
            </div>

            <!-- Surat Details -->
            <div class="border border-secondary-200 rounded-lg divide-y divide-secondary-100">
              <div class="p-4 flex items-center justify-between">
                <span class="text-secondary-500">Nomor Surat</span>
                <span class="font-medium text-secondary-800">{{ result.data.nomor_surat }}</span>
              </div>
              <div class="p-4 flex items-center justify-between">
                <span class="text-secondary-500">Tanggal TTD</span>
                <span class="font-medium text-secondary-800">
                  {{ new Date(result.data.tanggal_ttd).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                </span>
              </div>

              <!-- Penandatangan -->
              <div class="p-4">
                <p class="text-xs text-secondary-500 mb-2">Penandatangan</p>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                    <i class="ri-user-line text-primary-600"></i>
                  </div>
                  <div>
                    <p class="font-medium text-secondary-800">{{ result.data.kepala_dinas.nama }}</p>
                    <p class="text-sm text-secondary-500">NIP. {{ result.data.kepala_dinas.nip }}</p>
                  </div>
                </div>
              </div>

              <!-- Pengajuan Info (if available) -->
              <div v-if="result.data.pengajuan" class="p-4">
                <p class="text-xs text-secondary-500 mb-2">Informasi Pegawai</p>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <span class="text-secondary-600">Nama</span>
                    <span class="font-medium text-secondary-800">{{ result.data.pengajuan.nama }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-secondary-600">NIP</span>
                    <span class="font-medium text-secondary-800">{{ result.data.pengajuan.nip }}</span>
                  </div>
                  <div v-if="result.data.pengajuan.jenjang" class="flex justify-between">
                    <span class="text-secondary-600">Jenjang</span>
                    <span class="font-medium text-secondary-800">{{ result.data.pengajuan.jenjang }}</span>
                  </div>
                  <div v-if="result.data.pengajuan.prodi" class="flex justify-between">
                    <span class="text-secondary-600">Prodi</span>
                    <span class="font-medium text-secondary-800">{{ result.data.pengajuan.prodi }}</span>
                  </div>
                  <div v-if="result.data.pengajuan.perguruan_tinggi" class="flex justify-between">
                    <span class="text-secondary-600">Perguruan Tinggi</span>
                    <span class="font-medium text-secondary-800">{{ result.data.pengajuan.perguruan_tinggi }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Validity Badge -->
            <div class="flex items-center justify-center gap-2 p-4 bg-green-50 rounded-lg">
              <i class="ri-shield-check-line text-green-600"></i>
              <span class="text-sm font-medium text-green-700">
                Dokumen ini sah dan terverifikasi
              </span>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="verifying" class="flex items-center justify-center py-8">
            <LoadingSpinner size="md" text="Memverifikasi surat..." />
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-secondary-50 border-t border-secondary-100">
          <p class="text-xs text-secondary-500 text-center">
            Verifikasi dilakukan secara real-time terhadap database resmi BKPSDM Kabupaten Sukabumi
          </p>
        </div>
      </div>

      <!-- Back Button (for internal users) -->
      <div class="text-center mt-4">
        <a href="/" class="text-sm text-primary-600 hover:text-primary-700">
          <i class="ri-arrow-left-line mr-1"></i>
          Kembali ke Beranda
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}
</style>
