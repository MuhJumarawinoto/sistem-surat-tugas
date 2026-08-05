<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const router = useRouter()
const pengajuanStore = usePengajuanStore()
const authStore = useAuthStore()
const toast = useToastStore()

const pengajuanList = ref([])
const verificationInfoMap = ref(new Map())
const suratIzinMap = ref(new Map()) // Store surat izin info
const suratTugasMap = ref(new Map()) // Store surat tugas info
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)
const activeDropdown = ref(null)
const searchQuery = ref('')
const statusFilter = ref('all')
const currentPage = ref(1)
const itemsPerPage = 3

// Status filter options
const statusOptions = [
  { value: 'all', label: 'Semua Status' },
  { value: 'verified', label: 'Terverifikasi' },
  { value: 'signed', label: 'Ditandatangani' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'ditolak', label: 'Ditolak' },
  { value: 'dicabut', label: 'Dicabut' },
]

// Filter untuk riwayat verifikasi
const filteredList = computed(() => {
  let list = pengajuanList.value

  // Status filter
  if (statusFilter.value === 'all') {
    // Show all statuses except draft
    list = list.filter(p => p.status !== 'draft')
  } else if (statusFilter.value === 'selesai') {
    // Selesai includes both selesai and completed
    list = list.filter(p => ['selesai', 'completed'].includes(p.status))
  } else {
    // Filter by specific status
    list = list.filter(p => p.status === statusFilter.value)
  }

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    list = list.filter(p =>
      (p.nomor_pengajuan && p.nomor_pengajuan.toLowerCase().includes(query)) ||
      (p.user?.name && p.user.name.toLowerCase().includes(query)) ||
      (p.nama_prodi && p.nama_prodi.toLowerCase().includes(query)) ||
      (p.perguruan_tinggi && p.perguruan_tinggi.toLowerCase().includes(query))
    )
  }

  return list
})

// Hitung statistik
const stats = computed(() => {
  const list = filteredList.value
  return {
    total: list.length,
    verified: list.filter(p => p.status === 'verified').length,
    signed: list.filter(p => p.status === 'signed').length,
    selesai: list.filter(p => p.status === 'selesai' || p.status === 'completed').length,
  }
})

// Pagination
const totalPages = computed(() => Math.ceil(filteredList.value.length / itemsPerPage))

const paginatedList = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredList.value.slice(start, end)
})

// Reset to page 1 when search or status filter changes
watch([searchQuery, statusFilter], () => {
  currentPage.value = 1
})

onMounted(async () => {
  await loadPengajuan()
})

function openSendMessageModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}

function openVerificationPage(id) {
  router.push(`/admin/verifikasi/${id}`)
}

async function loadPengajuan() {
  loading.value = true
  try {
    const data = await pengajuanStore.fetchPengajuan()
    pengajuanList.value = data || []
    await loadVerificationInfo()
    // Tidak preload surat info - akan di-load saat tombol download diklik (lazy load)
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

async function loadVerificationInfo() {
  // Hanya load untuk halaman saat ini (paginatedList), bukan semua data
  const promises = paginatedList.value.map(async (pengajuan) => {
    try {
      const response = await api.get(`/verification/pengajuan/${pengajuan.id}`)
      verificationInfoMap.value.set(pengajuan.id, response.data)
    } catch (error) {
      // Silent fail - verification info might not be available
    }
  })
  await Promise.all(promises)
}

// Load surat info on-demand untuk satu pengajuan (dipanggil saat akan download)
async function loadSuratInfoForPengajuan(pengajuanId) {
  // Check cache dulu
  if (suratIzinMap.value.has(pengajuanId) && suratTugasMap.value.has(pengajuanId)) {
    return
  }

  // Load Surat Izin
  try {
    const suratIzinResponse = await api.get(`/pengajuan/${pengajuanId}/surat-izin`)
    suratIzinMap.value.set(pengajuanId, suratIzinResponse.data.data || suratIzinResponse.data)
  } catch (error) {
    suratIzinMap.value.set(pengajuanId, null)
  }

  // Load Surat Tugas Mandiri
  try {
    const suratTugasResponse = await api.get(`/pengajuan/${pengajuanId}/surat-tugas-mandiri`)
    suratTugasMap.value.set(pengajuanId, suratTugasResponse.data.data || suratTugasResponse.data)
  } catch (error) {
    suratTugasMap.value.set(pengajuanId, null)
  }
}

// Reload data saat halaman berubah
watch(currentPage, async () => {
  await loadVerificationInfo()
})

// Download Surat Izin Belajar (Lazy Load)
async function downloadSuratIzin(pengajuanId) {
  // Load surat info on-demand jika belum ada di cache
  if (!suratIzinMap.value.has(pengajuanId)) {
    await loadSuratInfoForPengajuan(pengajuanId)
  }

  const suratIzin = suratIzinMap.value.get(pengajuanId)
  if (!suratIzin || !suratIzin.id) {
    toast.error('Surat Izin Belajar belum tersedia')
    return
  }

  // Get token from localStorage
  const token = localStorage.getItem('token')
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-izin/${suratIzin.id}/download?token=${encodeURIComponent(token)}`

  // Open in new tab to trigger download (browser/IDM will handle it)
  window.open(url, '_blank')
  toast.success('Surat Izin Belajar sedang diunduh...')
}

// Download Surat Tugas Belajar Mandiri (Lazy Load)
async function downloadSuratTugas(pengajuanId) {
  // Load surat info on-demand jika belum ada di cache
  if (!suratTugasMap.value.has(pengajuanId)) {
    await loadSuratInfoForPengajuan(pengajuanId)
  }

  const suratTugas = suratTugasMap.value.get(pengajuanId)
  if (!suratTugas || !suratTugas.id) {
    toast.error('Surat Tugas Belajar belum tersedia')
    return
  }

  // Get token from localStorage
  const token = localStorage.getItem('token')
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-tugas-mandiri/${suratTugas.id}/download?token=${encodeURIComponent(token)}`

  // Open in new tab to trigger download (browser/IDM will handle it)
  window.open(url, '_blank')
  toast.success('Surat Tugas Belajar sedang diunduh...')
}

// Cek apakah surat izin tersedia (berdasarkan status pengajuan, bukan cache)
function hasSuratIzin(pengajuanId) {
  const pengajuan = pengajuanList.value.find(p => p.id === pengajuanId)
  // Surat izin tersedia jika status signed, selesai, atau completed
  return pengajuan && ['signed', 'selesai', 'completed'].includes(pengajuan.status)
}

// Cek apakah surat tugas tersedia (berdasarkan status pengajuan, bukan cache)
function hasSuratTugas(pengajuanId) {
  const pengajuan = pengajuanList.value.find(p => p.id === pengajuanId)
  // Surat tugas tersedia jika status selesai atau completed
  return pengajuan && ['selesai', 'completed'].includes(pengajuan.status)
}

function getStatusBadgeClass(status) {
  const classes = {
    'verified': 'badge-success',
    'signed': 'badge-info',
    'selesai': 'badge-success',
    'completed': 'badge-success'
  }
  return classes[status] || 'badge-secondary'
}

function getStatusLabel(status) {
  const labels = {
    'verified': 'Terverifikasi',
    'signed': 'Ditandatangani',
    'selesai': 'Selesai',
    'completed': 'Selesai'
  }
  return labels[status] || status
}

function getNextAction(status) {
  const actions = {
    'verified': { label: 'Siap Terbit Surat', icon: 'ri-file-text-line', color: 'blue' },
    'signed': { label: 'Menunggu Surat Tugas', icon: 'ri-file-list-line', color: 'purple' },
    'selesai': { label: 'Selesai', icon: 'ri-checkbox-circle-line', color: 'green' },
    'completed': { label: 'Selesai', icon: 'ri-checkbox-circle-line', color: 'green' }
  }
  return actions[status] || null
}

// Milestone dot-line functions (3 Steps)
function getMilestoneSteps(pengajuan) {
  const status = pengajuan.status
  const steps = []

  // Step 1: Dikirim
  steps.push({
    label: 'Dikirim',
    status: ['pending_admin', 'verified', 'signed', 'selesai', 'completed'].includes(status) ? 'completed' : 'pending',
  })

  // Step 2: Verifikasi
  steps.push({
    label: 'Verifikasi',
    status: ['verified', 'signed', 'selesai', 'completed'].includes(status) ? 'completed' :
              ['pending_admin'].includes(status) ? 'current' : 'pending',
  })

  // Step 3: Selesai
  steps.push({
    label: 'Selesai',
    status: ['selesai', 'completed'].includes(status) ? 'completed' :
              ['signed'].includes(status) ? 'current' : 'pending',
  })

  return steps
}

function getProgressLineClass(status) {
  if (status === 'verified') return 'w-2/3 bg-blue-500'
  if (status === 'signed' || status === 'selesai' || status === 'completed') return 'w-full bg-green-500'
  return 'w-0 bg-gray-200'
}

function getStepClass(step) {
  if (step.status === 'completed') return 'bg-green-500'
  if (step.status === 'current') return 'bg-blue-500'
  return 'bg-gray-300'
}

function getStepStatusDescription(status, label) {
  if (status === 'completed') return 'Sudah selesai'
  if (status === 'current') return 'Sedang diproses'
  if (status === 'pending') return 'Belum diproses'
  return label
}

// Dropdown menu functions
function toggleDropdown(id, event) {
  event?.stopPropagation()
  activeDropdown.value = activeDropdown.value === id ? null : id
}

function closeAllMenus() {
  activeDropdown.value = null
}

// Delete pengajuan (admin only)
async function deletePengajuan(id, nomor) {
  if (!confirm(`Hapus pengajuan "${nomor}"? Data yang dihapus tidak dapat dikembalikan.`)) {
    return
  }

  try {
    await api.delete(`/admin/pengajuan/${id}`)
    toast.success('Pengajuan berhasil dihapus')
    await loadPengajuan()
  } catch (error) {
    console.error('Failed to delete pengajuan:', error)
    toast.error(error.response?.data?.message || 'Gagal menghapus pengajuan')
  }
}

// Delete all filtered pengajuans (admin only)
async function deleteAllFiltered() {
  const count = filteredList.value.length
  if (count === 0) {
    toast.warning('Tidak ada pengajuan untuk dihapus')
    return
  }

  const statusLabel = statusFilter.value === 'all'
    ? 'semua riwayat'
    : statusOptions.find(o => o.value === statusFilter.value)?.label.toLowerCase()

  if (!confirm(`Hapus ${count} pengajuan dengan status "${statusLabel}"?\n\nData yang dihapus tidak dapat dikembalikan.`)) {
    return
  }

  try {
    const ids = filteredList.value.map(p => p.id)
    const response = await api.post('/admin/pengajuan/delete-multiple', { ids })
    toast.success(response.data.message || `${count} pengajuan berhasil dihapus`)
    await loadPengajuan()
  } catch (error) {
    console.error('Failed to delete pengajuans:', error)
    toast.error(error.response?.data?.message || 'Gagal menghapus pengajuan')
  }
}

// Close dropdown when clicking outside
if (typeof window !== 'undefined') {
  window.addEventListener('click', () => {
    if (activeDropdown.value) {
      activeDropdown.value = null
    }
  })
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Riwayat Verifikasi"
      subtitle="Daftar pengajuan yang sudah selesai diverifikasi"
    />

    <!-- Compact Stats Row -->
    <div class="flex flex-wrap items-center gap-4 mb-5 animate-slide-up">
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-white rounded-lg border border-secondary-200">
        <i class="ri-file-list-3-line text-secondary-500"></i>
        <span class="text-sm text-secondary-500">Total:</span>
        <span class="font-semibold text-lg text-secondary-800">{{ stats.total }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-green-50 rounded-lg border border-green-200">
        <i class="ri-checkbox-circle-line text-green-500"></i>
        <span class="text-sm text-green-600">Terverifikasi:</span>
        <span class="font-semibold text-lg text-green-700">{{ stats.verified }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-blue-50 rounded-lg border border-blue-200">
        <i class="ri-edit-sign-line text-blue-500"></i>
        <span class="text-sm text-blue-600">Ditandatangani:</span>
        <span class="font-semibold text-lg text-blue-700">{{ stats.signed }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-purple-50 rounded-lg border border-purple-200">
        <i class="ri-checkbox-circle-fill text-purple-500"></i>
        <span class="text-sm text-purple-600">Selesai:</span>
        <span class="font-semibold text-lg text-purple-700">{{ stats.selesai }}</span>
      </div>
    </div>

    <!-- Search Box & Status Filter -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
      <!-- Search Box -->
      <div class="flex-1 relative">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari berdasarkan nomor, nama, prodi, atau universitas..."
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

      <!-- Status Filter Dropdown -->
      <div class="sm:w-56 relative">
        <select
          v-model="statusFilter"
          class="w-full pl-4 pr-10 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent appearance-none bg-white"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <i class="ri-filter-line absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none"></i>
      </div>

      <!-- Hapus Semua Button -->
      <button
        @click="deleteAllFiltered"
        :disabled="filteredList.length === 0"
        class="sm:w-auto px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg flex items-center gap-2 transition-colors"
        title="Hapus semua pengajuan yang terfilter"
      >
        <i class="ri-delete-bin-line"></i>
        <span class="hidden sm:inline">Hapus Semua</span>
      </button>
    </div>

    <!-- Result Count -->
    <div v-if="searchQuery || statusFilter !== 'all'" class="text-xs text-secondary-500 mb-4 flex items-center gap-3">
      <div>
        <span v-if="searchQuery">{{ filteredList.length }} hasil ditemukan</span>
        <span v-if="searchQuery && statusFilter !== 'all'" class="mx-1">•</span>
        <span v-if="statusFilter !== 'all'">Filter: {{ statusOptions.find(o => o.value === statusFilter)?.label }}</span>
      </div>
      <div v-if="filteredList.length > 0" class="text-red-600 flex items-center gap-1">
        <i class="ri-information-line"></i>
        <span>{{ filteredList.length }} pengajuan akan dihapus jika klik "Hapus Semua"</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 animate-slide-up">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-secondary-200 p-4 animate-pulse">
        <div class="flex items-start gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <div class="h-5 w-32 bg-secondary-200 rounded"></div>
              <div class="h-6 w-20 bg-secondary-200 rounded-full"></div>
            </div>
            <div class="h-5 w-48 bg-secondary-200 rounded mb-2"></div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-24 bg-secondary-200 rounded"></div>
              <div class="h-4 w-32 bg-secondary-200 rounded"></div>
              <div class="h-4 w-28 bg-secondary-200 rounded"></div>
            </div>
          </div>
          <div class="flex gap-2">
            <div class="h-9 w-20 bg-secondary-200 rounded-lg"></div>
            <div class="h-9 w-9 bg-secondary-200 rounded-lg"></div>
          </div>
        </div>
        <div class="mt-3 pt-3 border-t border-secondary-100">
          <div class="h-8 w-full bg-secondary-100 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredList.length === 0" class="text-center py-16 animate-slide-up">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-secondary-100 to-secondary-50 flex items-center justify-center mx-auto mb-4 border border-secondary-200">
        <i class="ri-inbox-archive-line text-4xl text-secondary-400"></i>
      </div>
      <h3 class="text-base font-semibold text-secondary-800 mb-1">Tidak Ada Data</h3>
      <p class="text-sm text-secondary-500">
        <template v-if="searchQuery">
          Tidak ditemukan hasil pencarian
        </template>
        <template v-else-if="statusFilter !== 'all'">
          Tidak ada pengajuan dengan status {{ statusOptions.find(o => o.value === statusFilter)?.label.toLowerCase() }}
        </template>
        <template v-else>
          Belum ada pengajuan yang selesai diverifikasi
        </template>
      </p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3 animate-slide-up">
      <div
        v-for="item in paginatedList"
        :key="item.id"
        class="bg-white rounded-xl border border-secondary-200 shadow-sm hover:shadow-md transition-all"
      >
        <div class="p-4">
          <!-- Compact Header Row -->
          <div class="flex items-start gap-4">
            <!-- Main Info -->
            <div class="flex-1 min-w-0">
              <!-- First row: Number + badges -->
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="font-semibold text-base text-secondary-800">{{ item.nomor_pengajuan }}</span>
                <span class="badge text-sm py-1 px-2.5" :class="getStatusBadgeClass(item.status)">
                  {{ getStatusLabel(item.status) }}
                </span>
              </div>
              <!-- Second row: Name -->
              <p class="text-base font-medium text-secondary-800 mb-1.5">{{ item.user?.name }}</p>
              <!-- Third row: Details -->
              <div class="flex items-center gap-3 text-sm text-secondary-500 flex-wrap">
                <span class="flex items-center gap-1">
                  <i class="ri-briefcase-line text-secondary-400"></i>
                  {{ item.user?.jabatan || '-' }}
                </span>
                <span class="text-secondary-300">•</span>
                <span class="flex items-center gap-1">
                  <i class="ri-graduation-cap-line text-secondary-400"></i>
                  {{ item.nama_prodi }}
                </span>
                <span class="text-secondary-300">•</span>
                <span class="flex items-center gap-1">
                  <i class="ri-building-line text-secondary-400"></i>
                  {{ item.perguruan_tinggi }}
                </span>
              </div>
            </div>

            <!-- Actions - Desktop -->
            <div class="hidden sm:flex items-center gap-2 shrink-0">
              <button
                @click="openVerificationPage(item.id)"
                class="btn btn-sm btn-secondary"
              >
                <i class="ri-eye-line"></i>
                <span class="ml-1">Detail</span>
              </button>
              <!-- Download Surat Tugas -->
              <button
                v-if="hasSuratTugas(item.id)"
                @click="downloadSuratTugas(item.id)"
                class="btn btn-sm bg-green-600 hover:bg-green-700 text-white border-green-700"
                title="Download Surat Tugas Belajar"
              >
                <i class="ri-file-download-line"></i>
                <span class="ml-1">Surat Tugas</span>
              </button>
              <button
                @click="openSendMessageModal(item)"
                class="btn btn-ghost btn-sm"
                title="Kirim Pesan"
              >
                <i class="ri-message-3-line"></i>
              </button>
              <!-- Delete Button -->
              <button
                @click="deletePengajuan(item.id, item.nomor_pengajuan)"
                class="btn btn-sm bg-red-600 hover:bg-red-700 text-white border-red-700"
                title="Hapus Pengajuan"
              >
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>

            <!-- Actions - Mobile Dropdown -->
            <div class="sm:hidden relative">
              <button
                @click="toggleDropdown(item.id, $event)"
                class="btn btn-ghost btn-sm p-2"
              >
                <i class="ri-more-2-fill text-lg"></i>
              </button>
              <div
                v-if="activeDropdown === item.id"
                class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-secondary-200 py-1 z-20"
                @click.stop
              >
                <button
                  @click="openVerificationPage(item.id); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i class="ri-eye-line text-secondary-600"></i>
                  <span>Detail</span>
                </button>
                <button
                  v-if="hasSuratTugas(item.id)"
                  @click="downloadSuratTugas(item.id); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i class="ri-file-download-line text-green-600"></i>
                  <span>Surat Tugas Belajar</span>
                </button>
                <button
                  @click="openSendMessageModal(item); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i class="ri-message-3-line text-secondary-600"></i>
                  <span>Kirim Pesan</span>
                </button>
                <div class="border-t border-secondary-100 my-1"></div>
                <button
                  @click="deletePengajuan(item.id, item.nomor_pengajuan); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-red-50 flex items-center gap-2 text-red-600"
                >
                  <i class="ri-delete-bin-line"></i>
                  <span>Hapus Pengajuan</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-secondary-100 my-3"></div>

          <!-- Next Action Info -->
          <div v-if="getNextAction(item.status)" class="flex items-center gap-2 text-sm px-3 py-2 rounded-md" :class="`bg-${getNextAction(item.status).color}-50`">
            <i :class="[getNextAction(item.status).icon, `text-${getNextAction(item.status).color}-600`]"></i>
            <span :class="`text-${getNextAction(item.status).color}-700`">{{ getNextAction(item.status).label }}</span>
          </div>

          <!-- Milestone Dot-Line -->
          <div class="px-4 pb-2">
            <div class="flex items-center justify-between relative py-2">
              <!-- Progress Line Background -->
              <div class="absolute top-1/2 left-0 right-0 h-0.5 -translate-y-1/2 bg-gray-200 z-0"></div>
              <!-- Progress Line Active -->
              <div
                class="absolute top-1/2 left-0 h-0.5 -translate-y-1/2 z-0 transition-all duration-300"
                :class="getProgressLineClass(item.status)"
              ></div>

              <!-- Dots -->
              <div
                v-for="(step, idx) in getMilestoneSteps(item)"
                :key="idx"
                class="relative z-10 flex flex-col items-center"
              >
                <!-- Pulse Ring for Current Step -->
                <div
                  v-if="step.status === 'current'"
                  class="absolute inset-0 rounded-full bg-blue-400 animate-ping opacity-75"
                  style="animation-duration: 1.5s;"
                ></div>
                <div
                  class="w-3 h-3 rounded-full transition-all duration-300 relative cursor-pointer hover:scale-125"
                  :class="getStepClass(step)"
                  :title="`${step.label}: ${getStepStatusDescription(step.status, step.label)}`"
                ></div>
                <span
                  class="text-xs mt-1 whitespace-nowrap"
                  :class="step.status === 'current' ? 'text-blue-600 font-medium' : 'text-gray-600'"
                >{{ step.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="filteredList.length > 0 && totalPages > 1" class="flex items-center justify-center gap-2 mt-6">
      <button
        @click="currentPage--"
        :disabled="currentPage === 1"
        class="px-4 py-2 rounded-lg border border-secondary-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-secondary-50 transition-colors"
      >
        <i class="ri-arrow-left-s-line"></i>
      </button>
      <span class="text-sm text-secondary-600">
        Halaman {{ currentPage }} dari {{ totalPages }}
      </span>
      <button
        @click="currentPage++"
        :disabled="currentPage === totalPages"
        class="px-4 py-2 rounded-lg border border-secondary-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-secondary-50 transition-colors"
      >
        <i class="ri-arrow-right-s-line"></i>
      </button>
    </div>

    <SendMessageModal
      :show="showModal"
      :pengajuan-id="selectedPengajuan?.id"
      :pemohon-name="selectedPengajuan?.user?.name"
      @close="showModal = false"
      @sent="handleMessageSent"
    />
  </MainLayout>
</template>
