<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '@/components/layout/MainLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import api from '@/services/api'

const route = useRoute()

// State
const loading = ref(false)
const pengajuanList = ref([])
const suratList = ref([])
const activeTab = ref('pending') // 'pending' or 'list'
const showCreateModal = ref(false)
const selectedPengajuan = ref(null)
const submitting = ref(false)

// Pagination state
const pengajuanPagination = ref({
  data: [],
  currentPage: 1,
  perPage: 3,
  totalPages: 1,
  total: 0
})

const suratPagination = ref({
  data: [],
  currentPage: 1,
  perPage: 3,
  totalPages: 1,
  total: 0
})

// Form state
const form = ref({
  pengajuan_id: null,
  nomor_surat: '',
  bulan: getCurrentMonth(),
  tahun: new Date().getFullYear().toString(),
  tanggal_mulai: '',
  tanggal_selesai: '',
  tanggal_ttd: new Date().toISOString().split('T')[0],
  tempat_ttd: 'Sukabumi'
})

// Stats
const stats = computed(() => ({
  pending: pengajuanPagination.value.total,
  total: suratPagination.value.total
}))

// Get current month name in Indonesian
function getCurrentMonth() {
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  return months[new Date().getMonth()]
}

// Load pending pengajuan (verified but no surat tugas dinas)
async function loadPendingPengajuan() {
  loading.value = true
  try {
    const response = await api.get('/kepala/surat-tugas/pending', {
      params: {
        page: pengajuanPagination.value.currentPage,
        per_page: pengajuanPagination.value.perPage
      }
    })
    // Backend uses custom format: { data: [...], meta: { current_page, last_page, per_page, total } }
    pengajuanPagination.value.data = response.data.data || []
    pengajuanPagination.value.total = response.data.meta?.total || 0
    pengajuanPagination.value.totalPages = response.data.meta?.last_page || 1
    pengajuanPagination.value.currentPage = response.data.meta?.current_page || 1
  } catch (error) {
    console.error('Failed to load pending pengajuan:', error)
    pengajuanPagination.value.data = []
    pengajuanPagination.value.total = 0
    pengajuanPagination.value.totalPages = 1
  } finally {
    loading.value = false
  }
}

// Load surat tugas dinas list
async function loadSuratList() {
  loading.value = true
  try {
    const response = await api.get('/kepala/surat-tugas', {
      params: {
        page: suratPagination.value.currentPage,
        per_page: suratPagination.value.perPage
      }
    })
    // Backend uses custom format: { data: [...], meta: { current_page, last_page, per_page, total } }
    suratPagination.value.data = response.data.data || []
    suratPagination.value.total = response.data.meta?.total || 0
    suratPagination.value.totalPages = response.data.meta?.last_page || 1
    suratPagination.value.currentPage = response.data.meta?.current_page || 1
  } catch (error) {
    console.error('Failed to load surat list:', error)
    suratPagination.value.data = []
    suratPagination.value.total = 0
    suratPagination.value.totalPages = 1
  } finally {
    loading.value = false
  }
}

// Pagination functions
function pengajuanPrevPage() {
  if (pengajuanPagination.value.currentPage > 1) {
    pengajuanPagination.value.currentPage--
    loadPendingPengajuan()
  }
}

function pengajuanNextPage() {
  if (pengajuanPagination.value.currentPage < pengajuanPagination.value.totalPages) {
    pengajuanPagination.value.currentPage++
    loadPendingPengajuan()
  }
}

function suratPrevPage() {
  if (suratPagination.value.currentPage > 1) {
    suratPagination.value.currentPage--
    loadSuratList()
  }
}

function suratNextPage() {
  if (suratPagination.value.currentPage < suratPagination.value.totalPages) {
    suratPagination.value.currentPage++
    loadSuratList()
  }
}

function getPengajuanPageRange() {
  const current = pengajuanPagination.value.currentPage
  const total = pengajuanPagination.value.totalPages
  const delta = 1
  const pages = []

  if (total <= 5) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > delta + 2) {
      pages.push('...')
    }
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    if (current < total - delta - 1) {
      pages.push('...')
    }
    pages.push(total)
  }
  return pages
}

function getSuratPageRange() {
  const current = suratPagination.value.currentPage
  const total = suratPagination.value.totalPages
  const delta = 1
  const pages = []

  if (total <= 5) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > delta + 2) {
      pages.push('...')
    }
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    if (current < total - delta - 1) {
      pages.push('...')
    }
    pages.push(total)
  }
  return pages
}

// Open create modal
function openCreateModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  form.value = {
    pengajuan_id: pengajuan.id,
    nomor_surat: '',
    bulan: getCurrentMonth(),
    tahun: new Date().getFullYear().toString(),
    tanggal_mulai: pengajuan.rencana_mulai || '',
    tanggal_selesai: pengajuan.rencana_selesai || '',
    tanggal_ttd: new Date().toISOString().split('T')[0],
    tempat_ttd: 'Sukabumi'
  }
  showCreateModal.value = true
}

// Close modal
function closeModal() {
  showCreateModal.value = false
  selectedPengajuan.value = null
  form.value = {
    pengajuan_id: null,
    nomor_surat: '',
    bulan: getCurrentMonth(),
    tahun: new Date().getFullYear().toString(),
    tanggal_mulai: '',
    tanggal_selesai: '',
    tanggal_ttd: new Date().toISOString().split('T')[0],
    tempat_ttd: 'Sukabumi'
  }
}

// Submit form
async function submitForm() {
  submitting.value = true
  try {
    await api.post('/kepala/surat-tugas', form.value)
    closeModal()
    // Reset pagination to page 1 and refresh both lists
    pengajuanPagination.value.currentPage = 1
    suratPagination.value.currentPage = 1
    await Promise.all([loadPendingPengajuan(), loadSuratList()])
    showToast('Surat tugas dinas berhasil dibuat', 'success')
  } catch (error) {
    console.error('Failed to create surat tugas dinas:', error)
    const message = error.response?.data?.message || 'Gagal membuat surat tugas dinas'
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

// Show toast (placeholder - should use toast store)
function showToast(message, type = 'info') {
  // TODO: Use toast store
  console.log(`[${type}] ${message}`)
}

// Download PDF
async function downloadPdf(id) {
  try {
    // Use direct download with token to avoid CORS issues
    const token = localStorage.getItem('token')
    const baseUrl = import.meta.env.VITE_API_URL
      ? import.meta.env.VITE_API_URL.replace('/api', '')
      : 'http://localhost:8000'

    const url = `${baseUrl}/api/kepala/surat-tugas/${id}/pdf?token=${token}`
    window.open(url, '_blank')
    showToast('Surat berhasil didownload', 'success')
  } catch (error) {
    console.error('Download failed:', error)
    const message = error.response?.data?.message || 'Gagal mendownload surat'
    showToast(message, 'error')
  }
}

// Tab change
function onTabChange(tab) {
  activeTab.value = tab
  // Reset pagination when switching tabs
  if (tab === 'pending') {
    pengajuanPagination.value.currentPage = 1
    loadPendingPengajuan()
  } else {
    suratPagination.value.currentPage = 1
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
    <!-- Service Type Indicator -->
    <!-- <div class="mb-4 px-1">
      <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
          <i class="ri-file-list-3-line text-blue-600"></i>
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-blue-800">Izin Belajar / Surat Tugas Belajar</p>
          <p class="text-xs text-blue-600">Kelola surat tugas belajar untuk pegawai di unit kerja Anda</p>
        </div>
        <span class="badge badge-info text-xs">Surat</span>
      </div>
    </div> -->

    <Breadcrumb />
    <PageHeader
      title="Surat Tugas Belajar"
      subtitle="Kelola surat tugas belajar untuk pegawai di unit kerja Anda"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Perlu Surat Tugas</p>
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
        Perlu Surat Tugas ({{ stats.pending }})
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
      <div v-if="pengajuanPagination.data.length === 0" class="card card-body text-center py-12">
        <i class="ri-check-double-line text-4xl text-secondary-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-secondary-700 mb-2">Tidak Ada Pengajuan Pending</h3>
        <p class="text-secondary-500">Semua pengajuan sudah memiliki surat tugas dinas.</p>
      </div>

      <!-- Pending List -->
      <div v-else class="space-y-4">
        <div
          v-for="pengajuan in pengajuanPagination.data"
          :key="pengajuan.id"
          class="card card-body"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="font-semibold text-secondary-800">{{ pengajuan.user?.name }}</h4>
                <span class="badge badge-warning">Verified</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary-600">
                <div><span class="font-medium">NIP:</span> {{ pengajuan.user?.nip }}</div>
                <div><span class="font-medium">Jenjang:</span> {{ pengajuan.jenjang?.kode }}</div>
                <div><span class="font-medium">Prodi:</span> {{ pengajuan.nama_prodi }}</div>
                <div><span class="font-medium">Universitas:</span> {{ pengajuan.perguruan_tinggi }}</div>
              </div>
            </div>
            <button
              @click="openCreateModal(pengajuan)"
              class="btn btn-primary"
            >
              <i class="ri-file-add-line mr-1"></i>
              Buat Surat Tugas
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination for Pending -->
      <div v-if="pengajuanPagination.totalPages > 1" class="flex items-center justify-between mt-4 pt-4 border-t border-secondary-200">
        <div class="text-sm text-secondary-500">
          Menampilkan {{ (pengajuanPagination.currentPage - 1) * pengajuanPagination.perPage + 1 }} - {{ Math.min(pengajuanPagination.currentPage * pengajuanPagination.perPage, pengajuanPagination.total) }} dari {{ pengajuanPagination.total }} pengajuan
        </div>
        <div class="flex items-center gap-1">
          <button
            @click="pengajuanPrevPage"
            :disabled="pengajuanPagination.currentPage === 1"
            class="btn btn-ghost btn-sm"
          >
            <i class="ri-arrow-left-s-line"></i>
          </button>
          <template v-for="page in getPengajuanPageRange()" :key="page">
            <span v-if="page === '...'" class="px-2 text-secondary-400">...</span>
            <button
              v-else
              @click="pengajuanPagination.currentPage = page; loadPendingPengajuan()"
              :class="[
                'btn btn-sm',
                pengajuanPagination.currentPage === page ? 'btn-primary' : 'btn-ghost'
              ]"
            >
              {{ page }}
            </button>
          </template>
          <button
            @click="pengajuanNextPage"
            :disabled="pengajuanPagination.currentPage === pengajuanPagination.totalPages"
            class="btn btn-ghost btn-sm"
          >
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <!-- Empty State -->
      <div v-if="suratPagination.data.length === 0" class="card card-body text-center py-12">
        <i class="ri-file-text-line text-4xl text-secondary-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-secondary-700 mb-2">Belum Ada Surat Tugas</h3>
        <p class="text-secondary-500">Silakan buat surat tugas untuk pengajuan yang sudah verified.</p>
      </div>

      <!-- Surat List -->
      <div v-else class="space-y-4">
        <div
          v-for="surat in suratPagination.data"
          :key="surat.id"
          class="card card-body"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="font-semibold text-secondary-800">{{ surat.pengajuan?.user?.name }}</h4>
                <span :class="['badge', getStatusBadge(surat.status)]">
                  {{ getStatusLabel(surat.status) }}
                </span>
              </div>
              <div class="text-sm text-secondary-600 mb-2">
                <span class="font-medium">Nomor:</span>
                {{ surat.nomor_surat }}/DK/{{ surat.bulan }}/{{ surat.tahun }}
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary-600">
                <div><span class="font-medium">Prodi:</span> {{ surat.pengajuan?.nama_prodi }}</div>
                <div><span class="font-medium">Jenjang:</span> {{ surat.pengajuan?.jenjang?.kode }}</div>
                <div><span class="font-medium">Periode:</span>
                  {{ formatDate(surat.tanggal_mulai) }} - {{ formatDate(surat.tanggal_selesai) }}
                </div>
                <div><span class="font-medium">Dibuat:</span> {{ formatDate(surat.created_at) }}</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button
                @click="downloadPdf(surat.id)"
                class="btn btn-secondary"
              >
                <i class="ri-download-line mr-1"></i>
                Download PDF
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination for Surat List -->
      <div v-if="suratPagination.totalPages > 1" class="flex items-center justify-between mt-4 pt-4 border-t border-secondary-200">
        <div class="text-sm text-secondary-500">
          Menampilkan {{ (suratPagination.currentPage - 1) * suratPagination.perPage + 1 }} - {{ Math.min(suratPagination.currentPage * suratPagination.perPage, suratPagination.total) }} dari {{ suratPagination.total }} surat
        </div>
        <div class="flex items-center gap-1">
          <button
            @click="suratPrevPage"
            :disabled="suratPagination.currentPage === 1"
            class="btn btn-ghost btn-sm"
          >
            <i class="ri-arrow-left-s-line"></i>
          </button>
          <template v-for="page in getSuratPageRange()" :key="page">
            <span v-if="page === '...'" class="px-2 text-secondary-400">...</span>
            <button
              v-else
              @click="suratPagination.currentPage = page; loadSuratList()"
              :class="[
                'btn btn-sm',
                suratPagination.currentPage === page ? 'btn-primary' : 'btn-ghost'
              ]"
            >
              {{ page }}
            </button>
          </template>
          <button
            @click="suratNextPage"
            :disabled="suratPagination.currentPage === suratPagination.totalPages"
            class="btn btn-ghost btn-sm"
          >
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b">
              <h3 class="text-lg font-semibold">Buat Surat Tugas Belajar</h3>
              <button @click="closeModal" class="btn btn-ghost btn-icon">
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1">
              <div v-if="selectedPengajuan" class="mb-4 p-4 bg-secondary-50 rounded-lg">
                <h4 class="font-medium text-secondary-700 mb-2">Data Pegawai</h4>
                <div class="text-sm space-y-1">
                  <div><span class="font-medium">Nama:</span> {{ selectedPengajuan.user?.name }}</div>
                  <div><span class="font-medium">NIP:</span> {{ selectedPengajuan.user?.nip }}</div>
                  <div><span class="font-medium">Prodi:</span> {{ selectedPengajuan.nama_prodi }}</div>
                  <div><span class="font-medium">Jenjang:</span> {{ selectedPengajuan.jenjang?.kode }}</div>
                </div>
              </div>

              <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-1">Nomor Surat</label>
                  <input
                    v-model="form.nomor_surat"
                    type="text"
                    placeholder="Contoh: 001"
                    required
                    class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Bulan</label>
                    <select
                      v-model="form.bulan"
                      class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                      <option>Januari</option>
                      <option>Februari</option>
                      <option>Maret</option>
                      <option>April</option>
                      <option>Mei</option>
                      <option>Juni</option>
                      <option>Juli</option>
                      <option>Agustus</option>
                      <option>September</option>
                      <option>Oktober</option>
                      <option>November</option>
                      <option>Desember</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun</label>
                    <input
                      v-model="form.tahun"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Tanggal Mulai</label>
                    <input
                      v-model="form.tanggal_mulai"
                      type="date"
                      required
                      class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Tanggal Selesai</label>
                    <input
                      v-model="form.tanggal_selesai"
                      type="date"
                      required
                      class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-1">Tanggal Penandatangan</label>
                  <input
                    v-model="form.tanggal_ttd"
                    type="date"
                    required
                    class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-1">Tempat Penandatangan</label>
                  <input
                    v-model="form.tempat_ttd"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>
              </form>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-2 p-6 border-t">
              <button @click="closeModal" class="btn btn-ghost">Batal</button>
              <button
                @click="submitForm"
                :disabled="submitting"
                class="btn btn-primary"
              >
                <LoadingSpinner v-if="submitting" size="sm" class="mr-2" />
                <i v-else class="ri-save-line mr-1"></i>
                {{ submitting ? 'Menyimpan...' : 'Simpan' }}
              </button>
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
