<script setup>
import { ref, onMounted, computed } from 'vue'
import MainLayout from '@/components/layout/MainLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import api from '@/services/api'

// State
const loading = ref(false)
const pengajuanList = ref([])
const suratList = ref([])
const activeTab = ref('pending') // 'pending' or 'list'
const showGenerateModal = ref(false)
const showSignModal = ref(false)
const selectedPengajuan = ref(null)
const selectedSurat = ref(null)
const submitting = ref(false)

// Stats
const stats = computed(() => ({
  pending: pengajuanList.value.length,
  total: suratList.value.length,
  signed: suratList.value.filter(s => s.status === 'signed' || s.status === 'completed').length
}))

// Load pending pengajuan (has surat dinas but no surat izin)
async function loadPendingPengajuan() {
  loading.value = true
  try {
    const response = await api.get('/admin/surat-izin/pending')
    pengajuanList.value = response.data.data
  } catch (error) {
    console.error('Failed to load pending pengajuan:', error)
  } finally {
    loading.value = false
  }
}

// Load surat izin list
async function loadSuratList() {
  loading.value = true
  try {
    const response = await api.get('/admin/surat-izin')
    suratList.value = response.data.data
  } catch (error) {
    console.error('Failed to load surat list:', error)
  } finally {
    loading.value = false
  }
}

// Open generate modal
function openGenerateModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showGenerateModal.value = true
}

// Close generate modal
function closeGenerateModal() {
  showGenerateModal.value = false
  selectedPengajuan.value = null
}

// Generate surat izin
async function generateSuratIzin() {
  submitting.value = true
  try {
    await api.post('/admin/surat-izin', {
      pengajuan_id: selectedPengajuan.value.id
    })
    closeGenerateModal()
    await Promise.all([loadPendingPengajuan(), loadSuratList()])
    showToast('Surat Izin Belajar dan Surat Tugas Mandiri berhasil dibuat', 'success')
  } catch (error) {
    console.error('Failed to generate surat:', error)
    const message = error.response?.data?.message || 'Gagal membuat surat'
    showToast(message, 'error')
  } finally {
    submitting.value = false
  }
}

// Open sign modal
function openSignModal(surat) {
  selectedSurat.value = surat
  showSignModal.value = true
}

// Close sign modal
function closeSignModal() {
  showSignModal.value = false
  selectedSurat.value = null
}

// Sign surat
async function signSurat() {
  submitting.value = true
  try {
    // TODO: Implement TTE integration
    await api.post(`/admin/surat-izin/${selectedSurat.value.id}/sign`, {
      tte_path: '/path/to/tte/file.pdf',
      qr_code: `QR-${selectedSurat.value.id}`
    })
    closeSignModal()
    await Promise.all([loadPendingPengajuan(), loadSuratList()])
    showToast('Surat izin belajar berhasil ditandatangani', 'success')
  } catch (error) {
    console.error('Failed to sign surat:', error)
    const message = error.response?.data?.message || 'Gagal menandatangani surat'
    showToast(message, 'error')
  } finally {
    submitting.value = false
  }
}

// Format date
function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Get status badge
function getStatusBadge(status) {
  const badges = {
    draft: 'badge-secondary',
    signed: 'badge-success',
    completed: 'badge-primary'
  }
  return badges[status] || 'badge-secondary'
}

// Get status label
function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    signed: 'Signed',
    completed: 'Completed'
  }
  return labels[status] || status
}

// Show toast (placeholder)
function showToast(message, type = 'info') {
  console.log(`[${type}] ${message}`)
}

// Tab change
function onTabChange(tab) {
  activeTab.value = tab
  if (tab === 'pending') {
    loadPendingPengajuan()
  } else {
    loadSuratList()
  }
}

onMounted(() => {
  loadPendingPengajuan()
  loadSuratList()
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Surat Izin Belajar Mandiri"
      subtitle="Generate surat izin belajar dan surat tugas mandiri secara otomatis"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Perlu Surat Izin</p>
            <p class="text-2xl font-bold text-secondary-800">{{ stats.pending }}</p>
          </div>
          <div class="badge badge-warning text-lg">Pending</div>
        </div>
      </div>
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Total Surat</p>
            <p class="text-2xl font-bold text-secondary-800">{{ stats.total }}</p>
          </div>
          <div class="badge badge-primary text-lg">Total</div>
        </div>
      </div>
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Sudah Ditandatangani</p>
            <p class="text-2xl font-bold text-secondary-800">{{ stats.signed }}</p>
          </div>
          <div class="badge badge-success text-lg">Signed</div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-secondary-200 mb-6">
      <button
        @click="onTabChange('pending')"
        :class="[
          'px-4 py-2 font-medium transition-colors',
          activeTab === 'pending'
            ? 'border-b-2 border-primary-500 text-primary-600'
            : 'text-secondary-500 hover:text-secondary-700'
        ]"
      >
        Perlu Surat Izin ({{ stats.pending }})
      </button>
      <button
        @click="onTabChange('list')"
        :class="[
          'px-4 py-2 font-medium transition-colors',
          activeTab === 'list'
            ? 'border-b-2 border-primary-500 text-primary-600'
            : 'text-secondary-500 hover:text-secondary-700'
        ]"
      >
        Daftar Surat ({{ stats.total }})
      </button>
    </div>

    <!-- Content -->
    <LoadingSpinner v-if="loading" />

    <div v-else-if="activeTab === 'pending'" class="space-y-4">
      <!-- Empty State -->
      <div v-if="pengajuanList.length === 0" class="card card-body text-center py-12">
        <i class="ri-check-double-line text-4xl text-secondary-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-secondary-700 mb-2">Tidak Ada Pengajuan Pending</h3>
        <p class="text-secondary-500">Semua pengajuan sudah memiliki surat izin belajar.</p>
      </div>

      <!-- Pending List -->
      <div v-else class="space-y-4">
        <div
          v-for="pengajuan in pengajuanList"
          :key="pengajuan.id"
          class="card card-body"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="font-semibold text-secondary-800">{{ pengajuan.user?.name }}</h4>
                <span class="badge badge-primary">Surat Dinas Ready</span>
              </div>
              <div class="text-sm text-secondary-600 mb-2">
                <span class="font-medium">Unit Kerja:</span> {{ pengajuan.surat_tugas_dinas?.unit_kerja?.nama }}
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary-600">
                <div><span class="font-medium">NIP:</span> {{ pengajuan.user?.nip }}</div>
                <div><span class="font-medium">Jenjang:</span> {{ pengajuan.jenjang?.kode }}</div>
                <div><span class="font-medium">Prodi:</span> {{ pengajuan.nama_prodi }}</div>
                <div><span class="font-medium">Universitas:</span> {{ pengajuan.perguruan_tinggi }}</div>
              </div>
              <div class="text-sm text-secondary-600 mt-2">
                <span class="font-medium">No. Surat Dinas:</span>
                {{ pengajuan.surat_tugas_dinas?.nomor_surat }}/DK/{{ pengajuan.surat_tugas_dinas?.bulan }}/{{ pengajuan.surat_tugas_dinas?.tahun }}
              </div>
            </div>
            <button
              @click="openGenerateModal(pengajuan)"
              class="btn btn-primary"
            >
              <i class="ri-file-add-line mr-1"></i>
              Generate Surat
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <!-- Empty State -->
      <div v-if="suratList.length === 0" class="card card-body text-center py-12">
        <i class="ri-file-text-line text-4xl text-secondary-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-secondary-700 mb-2">Belum Ada Surat Izin</h3>
        <p class="text-secondary-500">Silakan generate surat izin untuk pengajuan yang sudah memiliki surat dinas.</p>
      </div>

      <!-- Surat List -->
      <div v-else class="space-y-4">
        <div
          v-for="surat in suratList"
          :key="surat.id"
          class="card card-body"
        >
          <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <h4 class="font-semibold text-secondary-800">{{ surat.pengajuan?.user?.name }}</h4>
                  <span :class="['badge', getStatusBadge(surat.status)]">
                    {{ getStatusLabel(surat.status) }}
                  </span>
                </div>
                <div class="text-sm text-secondary-600 mb-2">
                  <span class="font-medium">Nomor:</span> {{ surat.nomor_surat }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary-600">
                  <div><span class="font-medium">Prodi:</span> {{ surat.pengajuan?.nama_prodi }}</div>
                  <div><span class="font-medium">Jenjang:</span> {{ surat.pengajuan?.jenjang?.kode }}</div>
                  <div><span class="font-medium">Unit Kerja:</span>
                    {{ surat.surat_tugas_dinas?.unit_kerja?.nama }}
                  </div>
                  <div><span class="font-medium">Dibuat:</span> {{ formatDate(surat.created_at) }}</div>
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  v-if="surat.status === 'draft'"
                  @click="openSignModal(surat)"
                  class="btn btn-primary"
                >
                  <i class="ri-edit-line mr-1"></i>
                  TTE
                </button>
                <button
                  v-if="surat.is_signed"
                  class="btn btn-secondary"
                >
                  <i class="ri-download-line mr-1"></i>
                  Download
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Generate Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showGenerateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeGenerateModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6">
              <h3 class="text-lg font-semibold mb-4">Generate Surat Izin Belajar</h3>
              <div v-if="selectedPengajuan" class="mb-4 p-4 bg-secondary-50 rounded-lg">
                <div class="text-sm space-y-1">
                  <div><span class="font-medium">Nama:</span> {{ selectedPengajuan.user?.name }}</div>
                  <div><span class="font-medium">NIP:</span> {{ selectedPengajuan.user?.nip }}</div>
                  <div><span class="font-medium">Prodi:</span> {{ selectedPengajuan.nama_prodi }}</div>
                  <div><span class="font-medium">Jenjang:</span> {{ selectedPengajuan.jenjang?.kode }}</div>
                </div>
              </div>
              <p class="text-secondary-600 mb-4">
                Surat Izin Belajar dan Surat Tugas Mandiri akan digenerate secara otomatis.
              </p>
              <div class="flex justify-end gap-2">
                <button @click="closeGenerateModal" class="btn btn-ghost">Batal</button>
                <button
                  @click="generateSuratIzin"
                  :disabled="submitting"
                  class="btn btn-primary"
                >
                  <LoadingSpinner v-if="submitting" size="sm" class="mr-2" />
                  {{ submitting ? 'Generating...' : 'Generate' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Sign Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showSignModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeSignModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6">
              <h3 class="text-lg font-semibold mb-4">Tandatangani Surat Izin Belajar</h3>
              <div v-if="selectedSurat" class="mb-4 p-4 bg-secondary-50 rounded-lg">
                <div class="text-sm space-y-1">
                  <div><span class="font-medium">Nomor:</span> {{ selectedSurat.nomor_surat }}</div>
                  <div><span class="font-medium">Nama:</span> {{ selectedSurat.pengajuan?.user?.name }}</div>
                </div>
              </div>
              <p class="text-secondary-600 mb-4">
                Surat akan ditandatangani secara elektronik (TTE).
              </p>
              <div class="flex justify-end gap-2">
                <button @click="closeSignModal" class="btn btn-ghost">Batal</button>
                <button
                  @click="signSurat"
                  :disabled="submitting"
                  class="btn btn-primary"
                >
                  <LoadingSpinner v-if="submitting" size="sm" class="mr-2" />
                  {{ submitting ? 'Signing...' : 'Tandatangani' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </MainLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
