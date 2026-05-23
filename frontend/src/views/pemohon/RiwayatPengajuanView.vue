<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const router = useRouter()
const route = useRoute()
const pengajuanStore = usePengajuanStore()

// Page header actions
const headerActions = computed(() => [
  {
    label: 'Buat Baru',
    icon: 'ri-add-line',
    to: '/pengajuan/baru',
    variant: 'btn-primary'
  }
])

const pengajuanList = ref([])
const allPengajuanList = ref([]) // Store all data for client-side filtering
const loading = ref(false)
const currentPage = ref(1)
const perPage = ref(10)
const total = ref(0)
const totalPages = ref(1)
const lastPage = ref(1)
const searchQuery = ref('')
const filterStatus = ref('')

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'dicabut', label: 'Dihapus' },
  { value: 'terverifikasi', label: 'Terverifikasi' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'ditolak', label: 'Ditolak' },
]

// Modal states
const showDetailModal = ref(false)
const selectedPengajuan = ref(null)
const loadingDetail = ref(false)

// Document preview states
const showDocumentModal = ref(false)
const selectedDocument = ref(null)
const documentPreviewUrl = ref('')
const openDocumentMenuId = ref(null)

function toggleDocumentMenu(id) {
  openDocumentMenuId.value = openDocumentMenuId.value === id ? null : id
}

// Backend URL for document URLs
const backendUrl = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace('/api', '')
  : 'http://localhost:8000'

const getDocumentUrl = (doc) => {
  if (!doc) return ''
  if (doc.file_url) {
    console.log('Document URL from file_url:', doc.file_url)
    return doc.file_url
  }
  if (doc.file_path) {
    const url = `${backendUrl}/storage/${doc.file_path}`
    console.log('Document URL constructed:', url)
    return url
  }
  return ''
}

const getDocumentIcon = (fileName) => {
  if (!fileName) return 'ri-file-line'
  const ext = fileName.toLowerCase().split('.').pop()
  const icons = {
    pdf: 'ri-file-pdf-line',
    doc: 'ri-file-word-line',
    docx: 'ri-file-word-line',
    xls: 'ri-file-excel-line',
    xlsx: 'ri-file-excel-line',
    jpg: 'ri-image-line',
    jpeg: 'ri-image-line',
    png: 'ri-image-line',
    gif: 'ri-image-line',
  }
  return icons[ext] || 'ri-file-text-line'
}

const isImageFile = (fileName) => {
  if (!fileName) return false
  const ext = fileName.toLowerCase().split('.').pop()
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)
}

const isPdfFile = (fileName) => {
  if (!fileName) return false
  const ext = fileName.toLowerCase().split('.').pop()
  return ext === 'pdf'
}

function openDocumentPreview(doc) {
  const url = getDocumentUrl(doc)
  if (!url) {
    alert('URL dokumen tidak tersedia')
    return
  }

  // Close menu
  openDocumentMenuId.value = null

  // Show all documents (image, pdf) in modal preview
  selectedDocument.value = doc
  documentPreviewUrl.value = url
  showDocumentModal.value = true
}

function closeDocumentModal() {
  showDocumentModal.value = false
  selectedDocument.value = null
  documentPreviewUrl.value = ''
}

function downloadDocument(doc) {
  const url = getDocumentUrl(doc)
  if (!url) {
    alert('URL dokumen tidak tersedia')
    return
  }

  // Close menu
  openDocumentMenuId.value = null

  const link = document.createElement('a')
  link.href = url
  link.download = doc.file_name || `dokumen-${doc.id}`
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

async function openDetailModal(id) {
  loadingDetail.value = true
  showDetailModal.value = true
  selectedPengajuan.value = null

  try {
    const response = await api.get(`/pengajuan/${id}`)
    selectedPengajuan.value = response.data
  } catch (error) {
    console.error('Failed to load detail:', error)
    alert('Gagal memuat detail pengajuan')
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedPengajuan.value = null
}

onMounted(() => {
  loadPengajuan()
})

onUnmounted(() => {
  // Cleanup if needed
})

async function loadPengajuan() {
  loading.value = true
  try {
    const rawData = await pengajuanStore.fetchPengajuan({
      page: 1,
      per_page: 1000, // Load all data for client-side filtering
      include_deleted: '1' // Include deleted (dicabut) pengajuan for history
    })

    // rawData is already the array of pengajuan (response.data.data from store)
    // Filter: hanya yang selesai (terverifikasi, selesai, ditolak, dicabut)
    const filteredData = (rawData || []).filter(p =>
      ['dicabut', 'terverifikasi', 'selesai', 'ditolak'].includes(p.status)
    )

    allPengajuanList.value = filteredData
    applyFilter()

    console.log('Riwayat loaded:', filteredData.length, 'items')
    console.log('Statuses:', filteredData.map(p => p.status))
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
    allPengajuanList.value = []
    pengajuanList.value = []
    total.value = 0
    lastPage.value = 1
    totalPages.value = 1
  } finally {
    loading.value = false
  }
}

function applyFilter() {
  let filtered = allPengajuanList.value

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(item => {
      return (
        (item.nomor_pengajuan && item.nomor_pengajuan.toLowerCase().includes(query)) ||
        (item.jenjang?.nama && item.jenjang.nama.toLowerCase().includes(query)) ||
        (item.nama_prodi && item.nama_prodi.toLowerCase().includes(query)) ||
        (item.perguruan_tinggi && item.perguruan_tinggi.toLowerCase().includes(query)) ||
        (item.lokasi_pt && item.lokasi_pt.toLowerCase().includes(query)) ||
        (item.status && item.status.toLowerCase().includes(query))
      )
    })
  }

  // Apply status filter
  if (filterStatus.value) {
    filtered = filtered.filter(p => p.status === filterStatus.value)
  }

  total.value = filtered.length
  lastPage.value = Math.ceil(filtered.length / perPage.value) || 1
  totalPages.value = lastPage.value

  // Reset to page 1 if current page is beyond last page
  if (currentPage.value > lastPage.value) {
    currentPage.value = 1
  }

  // Apply pagination
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  pengajuanList.value = filtered.slice(start, end)
}

function handleSearch() {
  currentPage.value = 1
  applyFilter()
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page
    applyFilter()
  }
}

function nextPage() {
  if (currentPage.value < lastPage.value) {
    changePage(currentPage.value + 1)
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    changePage(currentPage.value - 1)
  }
}

const fromItem = computed(() => {
  return total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
})

const toItem = computed(() => {
  const end = currentPage.value * perPage.value
  return end > total.value ? total.value : end
})

const displayedPages = computed(() => {
  const pages = []
  const total = lastPage.value
  const current = currentPage.value
  const delta = 1

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > delta + 3) {
      pages.push('...')
    }
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    if (current < total - delta - 2) {
      pages.push('...')
    }
    pages.push(total)
  }
  return pages
})

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    kirim: 'Dikirim',
    dicabut: 'Dihapus',
    terverifikasi: 'Terverifikasi',
    ditolak: 'Ditolak',
    selesai: 'Selesai',
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-default',
    kirim: 'badge-warning',
    dicabut: 'badge-secondary',
    terverifikasi: 'badge-info',
    ditolak: 'badge-danger',
    selesai: 'badge-success',
  }
  return badges[status] || 'badge-default'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    kirim: 'ri-send-plane-line',
    dicabut: 'ri-delete-bin-line',
    terverifikasi: 'ri-verified-badge-line',
    ditolak: 'ri-close-line',
    selesai: 'ri-checkbox-circle-line',
  }
  return icons[status] || 'ri-file-line'
}

function getDocumentCount(pengajuan) {
  return pengajuan.dokumen?.length || 0
}

// Riwayat pengajuan is read-only - no editing allowed for completed/rejected applications
function canEdit(status) {
  // Always return false for riwayat (history) view
  return false
}

function canRestore(status) {
  // Can restore if status is 'dicabut'
  return status === 'dicabut'
}

async function handleRestore(id) {
  if (!confirm('Apakah Anda yakin ingin memulihkan pengajuan ini? Pengajuan akan kembali ke status draft.')) {
    return
  }

  try {
    await pengajuanStore.restorePengajuan(id)

    // Refresh the list
    await loadPengajuan()

    alert('Pengajuan berhasil dipulihkan!')
  } catch (error) {
    console.error('Failed to restore pengajuan:', error)
    alert(error.response?.data?.message || 'Gagal memulihkan pengajuan')
  }
}

// Watch for search query changes
watch(searchQuery, () => {
  currentPage.value = 1
  applyFilter()
})

// Refresh data when entering this route (e.g., after cancel from dashboard)
watch(() => route.path, (newPath) => {
  if (newPath === '/pengajuan/riwayat' || newPath === '/riwayat') {
    loadPengajuan()
  }
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Riwayat Pengajuan"
      subtitle="Daftar pengajuan yang telah selesai diproses, ditolak, atau dihapus"
      :actions="headerActions"
    />

    <!-- Search & Filter -->
    <div class="mb-4 animate-fade-in">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari berdasarkan nomor, prodi, universitas, atau status..."
            class="w-full pl-10 pr-4 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
          >
            <i class="ri-close-line"></i>
          </button>
        </div>
        <select v-model="filterStatus" class="select-field sm:w-48">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
      <p v-if="searchQuery" class="text-sm text-secondary-500 mt-2">
        Menampilkan {{ total }} hasil pencarian untuk "{{ searchQuery }}"
      </p>
    </div>

    <div class="card animate-slide-up">
      <div class="card-body">
            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat data..." />
            </div>

            <div v-else-if="pengajuanList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-history-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500 mb-4">Belum ada riwayat pengajuan</p>
              <p class="text-sm text-secondary-400">Pengajuan yang selesai diproses akan muncul di sini</p>
            </div>

            <div v-else class="space-y-4">
              <!-- Pengajuan Cards with Milestone -->
              <div
                v-for="item in pengajuanList"
                :key="item.id"
                class="border border-secondary-200 rounded-xl hover:border-primary-300 hover:shadow-sm transition-all overflow-hidden"
              >
                <!-- Main Row -->
                <div class="p-4">
                  <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="font-semibold text-secondary-800">{{ item.nomor_pengajuan || '-' }}</span>
                        <span :class="['badge', 'flex items-center gap-1', getStatusBadge(item.status)]">
                          <i :class="getStatusIcon(item.status)"></i>
                          {{ getStatusLabel(item.status) }}
                        </span>
                        <span class="text-xs text-secondary-500 bg-secondary-100 px-2 py-0.5 rounded">
                          {{ item.jenjang?.nama }}
                        </span>
                      </div>
                      <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
                        <span><i class="ri-graduation-cap-line mr-1"></i>{{ item.nama_prodi }}</span>
                        <span><i class="ri-building-line mr-1"></i>{{ item.perguruan_tinggi }}</span>
                        <span><i class="ri-calendar-line mr-1"></i>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                      </div>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                      <button
                        @click="openDetailModal(item.id)"
                        class="btn btn-primary btn-sm"
                      >
                        <i class="ri-eye-line mr-1"></i>
                        Lihat Detail
                      </button>
                      <button
                        v-if="canRestore(item.status)"
                        @click="handleRestore(item.id)"
                        class="btn btn-secondary btn-sm"
                      >
                        <i class="ri-refresh-line mr-1"></i>
                        Pulihkan
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between mt-4 pt-4 border-t border-secondary-200">
                <div class="text-sm text-secondary-500">
                  Menampilkan {{ fromItem }} - {{ toItem }} dari {{ total }} pengajuan
                </div>

                <div class="flex items-center gap-1">
                  <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="btn btn-ghost btn-sm"
                  >
                    <i class="ri-arrow-left-s-line"></i>
                  </button>

                  <template v-for="page in displayedPages" :key="page">
                    <span v-if="page === '...'" class="px-2 text-secondary-400">...</span>
                    <button
                      v-else
                      @click="changePage(page)"
                      :class="[
                        'btn btn-sm',
                        currentPage === page ? 'btn-primary' : 'btn-ghost'
                      ]"
                    >
                      {{ page }}
                    </button>
                  </template>

                  <button
                    @click="nextPage"
                    :disabled="currentPage === lastPage"
                    class="btn btn-ghost btn-sm"
                  >
                    <i class="ri-arrow-right-s-line"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

    <!-- Detail Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDetailModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeDetailModal"
        >
          <div
            v-if="selectedPengajuan || loadingDetail"
            class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden animate-slide-up flex flex-col"
          >
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-secondary-200">
              <div>
                <h3 class="text-lg font-semibold text-secondary-800">Detail Pengajuan</h3>
                <p class="text-sm text-secondary-500">{{ selectedPengajuan?.nomor_pengajuan || '-' }}</p>
              </div>
              <button
                @click="closeDetailModal"
                class="btn btn-ghost btn-icon"
              >
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1">
              <LoadingSpinner v-if="loadingDetail" size="md" text="Memuat detail..." />

              <div v-else-if="selectedPengajuan" class="space-y-6">
                <!-- Status -->
                <div class="flex items-center justify-between p-4 rounded-lg bg-secondary-50">
                  <div>
                    <p class="text-sm text-secondary-500">Status</p>
                    <p class="text-lg font-semibold text-secondary-800 mt-1">{{ getStatusLabel(selectedPengajuan.status) }}</p>
                  </div>
                  <span :class="['badge badge-lg', getStatusBadge(selectedPengajuan.status)]">
                    <i :class="getStatusIcon(selectedPengajuan.status)"></i>
                  </span>
                </div>

                <!-- Progress Milestone -->
                <div>
                  <h4 class="text-sm font-semibold text-secondary-600 uppercase mb-3">Progress Pengajuan</h4>
                  <PengajuanMilestone :pengajuan-id="selectedPengajuan.id" />
                </div>

                <!-- Info Pendidikan -->
                <div>
                  <h4 class="text-sm font-semibold text-secondary-600 uppercase mb-3">Informasi Pendidikan</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Jenjang</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.jenjang?.nama || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Program Studi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.nama_prodi || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Perguruan Tinggi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.perguruan_tinggi || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Lokasi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.lokasi_pt || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Rencana Mulai</p>
                      <p class="font-medium text-secondary-800 mt-1">
                        {{ selectedPengajuan.rencana_mulai ? new Date(selectedPengajuan.rencana_mulai).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
                      </p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Rencana Selesai</p>
                      <p class="font-medium text-secondary-800 mt-1">
                        {{ selectedPengajuan.rencana_selesai ? new Date(selectedPengajuan.rencana_selesai).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Dokumen -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-secondary-600 uppercase">Dokumen</h4>
                    <span class="text-sm text-secondary-500">{{ getDocumentCount(selectedPengajuan) }} / 9 dokumen</span>
                  </div>
                  <div v-if="selectedPengajuan.dokumen && selectedPengajuan.dokumen.length > 0" class="grid grid-cols-1 gap-2">
                    <div
                      v-for="doc in selectedPengajuan.dokumen"
                      :key="doc.id"
                      class="flex items-center gap-2 p-3 rounded-lg bg-secondary-50 hover:bg-secondary-100 transition-colors"
                    >
                      <i :class="[getDocumentIcon(doc.file_name || doc.jenis_dokumen), 'text-xl text-secondary-400 flex-shrink-0']"></i>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-secondary-800 truncate text-sm">{{ doc.file_name || doc.jenis_dokumen }}</p>
                        <p v-if="doc.status_verifikasi" :class="[
                          'text-xs',
                          doc.status_verifikasi === 'lengkap' ? 'text-green-600' : 'text-orange-600'
                        ]">
                          {{ doc.status_verifikasi === 'lengkap' ? '✓ Dokumen Lengkap' : '⚠ Perlu Verifikasi' }}
                        </p>
                      </div>
                      <!-- Desktop: Show both buttons -->
                      <div class="hidden sm:flex items-center gap-1 flex-shrink-0">
                        <button
                          @click="openDocumentPreview(doc)"
                          class="btn btn-ghost btn-sm btn-icon"
                          title="Lihat Dokumen"
                        >
                          <i class="ri-eye-line text-lg"></i>
                        </button>
                        <button
                          @click="downloadDocument(doc)"
                          class="btn btn-ghost btn-sm btn-icon"
                          title="Download Dokumen"
                        >
                          <i class="ri-download-line text-lg"></i>
                        </button>
                      </div>
                      <!-- Mobile: Show dropdown menu -->
                      <div class="sm:hidden relative flex-shrink-0">
                        <button
                          @click="toggleDocumentMenu(doc.id)"
                          class="btn btn-ghost btn-sm btn-icon"
                        >
                          <i class="ri-more-2-fill text-lg"></i>
                        </button>
                        <div
                          v-if="openDocumentMenuId === doc.id"
                          class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-secondary-200 py-1 z-10 min-w-[120px]"
                          @click.stop
                        >
                          <button
                            @click="openDocumentPreview(doc)"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                          >
                            <i class="ri-eye-line"></i>
                            Lihat
                          </button>
                          <button
                            @click="downloadDocument(doc)"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                          >
                            <i class="ri-download-line"></i>
                            Download
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-4 text-secondary-400 text-sm">
                    <i class="ri-folder-open-line text-2xl mb-2 block"></i>
                    Belum ada dokumen yang diupload
                  </div>
                </div>

                <!-- Catatan Tolak (jika ada) -->
                <div v-if="selectedPengajuan.catatan_tolak" class="p-4 rounded-lg bg-red-50 border border-red-200">
                  <h4 class="text-sm font-semibold text-red-600 mb-2">Catatan Penolakan</h4>
                  <p class="text-sm text-red-700">{{ selectedPengajuan.catatan_tolak }}</p>
                </div>

                <!-- Timestamps -->
                <div class="text-xs text-secondary-400 space-y-1">
                  <p>Dibuat: {{ new Date(selectedPengajuan.created_at).toLocaleString('id-ID') }}</p>
                  <p v-if="selectedPengajuan.tanggal_submit_atasan">Submit Atasan: {{ new Date(selectedPengajuan.tanggal_submit_atasan).toLocaleString('id-ID') }}</p>
                  <p v-if="selectedPengajuan.tanggal_approve_atasan">Approve Atasan: {{ new Date(selectedPengajuan.tanggal_approve_atasan).toLocaleString('id-ID') }}</p>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div v-if="!loadingDetail && selectedPengajuan" class="flex items-center justify-end gap-2 p-6 border-t border-secondary-200">
              <button
                @click="closeDetailModal"
                class="btn btn-ghost"
              >
                Tutup
              </button>
              <router-link
                v-if="canEdit(selectedPengajuan.status)"
                :to="`/pengajuan/${selectedPengajuan.id}/edit`"
                @click="closeDetailModal"
                class="btn btn-primary gap-2"
              >
                <i class="ri-edit-line"></i>
                <span>Edit Pengajuan</span>
              </router-link>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Document Preview Modal -->
    <DocumentPreviewModal
      :show="showDocumentModal"
      :src="documentPreviewUrl"
      :alt="selectedDocument?.file_name || selectedDocument?.jenis_dokumen || 'Dokumen'"
      :file-type="selectedDocument?.file_name ? (isImageFile(selectedDocument.file_name) ? 'image' : isPdfFile(selectedDocument.file_name) ? 'pdf' : '') : ''"
      @close="closeDocumentModal"
    />
  </MainLayout>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .animate-slide-up,
.modal-leave-active .animate-slide-up {
  transition: transform 0.3s ease;
}

.modal-enter-from .animate-slide-up,
.modal-leave-to .animate-slide-up {
  transform: translateY(20px);
}
</style>
