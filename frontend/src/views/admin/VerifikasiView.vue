<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const router = useRouter()
const pengajuanStore = usePengajuanStore()
const authStore = useAuthStore()

const pengajuanList = ref([])
const verificationInfoMap = ref(new Map())
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)
const activeDropdown = ref(null)
const searchQuery = ref('')
const statusFilter = ref('all')
const currentPage = ref(1)
const itemsPerPage = 5

// Status filter options
const statusOptions = [
  { value: 'all', label: 'Semua Status' },
  { value: 'pending_admin', label: 'Menunggu Verifikasi' },
  { value: 'verified', label: 'Terverifikasi' },
  { value: 'ditolak', label: 'Ditolak' },
]

// Filter untuk list verifikasi
const filteredList = computed(() => {
  let list = pengajuanList.value

  // Status filter
  if (statusFilter.value === 'all') {
    // Show all statuses (already filtered in loadPengajuan)
  } else {
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

// Hitung statistik verifikasi (berdasarkan filtered list)
const stats = computed(() => {
  const list = pengajuanList.value
  return {
    total: list.length,
    pendingAdmin: list.filter(p => p.status === 'pending_admin').length,
    verified: list.filter(p => p.status === 'verified').length,
    ditolak: list.filter(p => p.status === 'ditolak').length,
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
    const data = await pengajuanStore.fetchPengajuan({ per_page: 100 })
    pengajuanList.value = (data || []).filter(p =>
      !['signed', 'selesai', 'completed', 'dicabut', 'draft'].includes(p.status)
    )

    // Load verification info for each pengajuan
    await loadVerificationInfo()
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

async function loadVerificationInfo() {
  const promises = pengajuanList.value.map(async (pengajuan) => {
    try {
      const response = await api.get(`/verification/pengajuan/${pengajuan.id}`)
      verificationInfoMap.value.set(pengajuan.id, response.data)
    } catch (error) {
      console.error(`Failed to load verification info for pengajuan ${pengajuan.id}:`, error)
    }
  })
  await Promise.all(promises)
}

function getStatusBadgeClass(status) {
  const classes = {
    'pending_admin': 'badge-primary',
    'verified': 'badge-success',
    'surat_dinas': 'badge-info',
    'disetujui': 'badge-info',
    'signed': 'badge-info',
    'ditolak': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

function getStatusLabel(status) {
  const labels = {
    'pending_admin': 'Menunggu Verifikasi',
    'verified': 'Terverifikasi',
    'surat_dinas': 'Surat Tugas Dinas',
    'disetujui': 'Disetujui',
    'signed': 'Ditandatangani',
    'ditolak': 'Ditolak'
  }
  return labels[status] || status
}

// Dapatkan informasi siapa yang perlu memverifikasi
function getVerifierInfo(pengajuan) {
  if (pengajuan.status === 'pending_admin') {
    return {
      label: 'Perlu Verifikasi',
      name: 'Admin BKPSDM',
      jabatan: 'Verifikasi Dokumen',
      icon: 'ri-admin-line',
      bgColor: 'bg-blue-50',
      textColor: 'text-blue-700',
      iconColor: 'text-blue-600',
      borderColor: 'border-blue-200'
    }
  } else if (pengajuan.status === 'verified') {
    return {
      label: 'Selanjutnya',
      name: 'Kepala Dinas',
      jabatan: 'Buat Surat Tugas Belajar',
      icon: 'ri-file-list-line',
      bgColor: 'bg-purple-50',
      textColor: 'text-purple-700',
      iconColor: 'text-purple-600',
      borderColor: 'border-purple-200'
    }
  } else if (pengajuan.status === 'surat_dinas') {
    const verificationInfo = verificationInfoMap.value.get(pengajuan.id)
    return {
      label: 'Selanjutnya',
      name: verificationInfo?.final_signer?.nama || 'Admin BKPSDM',
      jabatan: 'Buat Surat Izin Belajar',
      icon: 'ri-file-text-line',
      bgColor: 'bg-blue-50',
      textColor: 'text-blue-700',
      iconColor: 'text-blue-600',
      borderColor: 'border-blue-200'
    }
  }

  return null
}

// Milestone functions (4 Steps)
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

  // Step 3: Surat Tugas
  steps.push({
    label: 'Surat Tugas',
    status: ['signed', 'selesai', 'completed'].includes(status) ? 'completed' :
              ['verified'].includes(status) ? 'current' : 'pending',
  })

  // Step 4: Selesai
  steps.push({
    label: 'Selesai',
    status: ['selesai', 'completed'].includes(status) ? 'completed' : 'pending',
  })

  return steps
}

function getProgressLineClass(status) {
  // Flow: 4 Steps - Dikirim → Verifikasi → Surat Tugas → Selesai
  if (status === 'draft' || status === 'dicabut' || status === 'ditolak') {
    return 'w-0 bg-gray-200'
  }
  if (status === 'pending_admin') {
    return 'w-1/4 bg-blue-500'
  }
  if (status === 'verified') {
    return 'w-2/4 bg-blue-500'
  }
  if (status === 'signed') {
    return 'w-3/4 bg-blue-500'
  }
  if (status === 'selesai' || status === 'completed') {
    return 'w-full bg-green-500'
  }
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
    <!-- Breadcrumb -->
    <Breadcrumb />

    <!-- Page Header -->
    <PageHeader
      title="Verifikasi Surat Tugas Belajar Mandiri"
      subtitle="Verifikasi kelengkapan dokumen pengajuan izin belajar"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 animate-slide-up">
      <!-- Total -->
      <div class="bg-white rounded-xl border border-secondary-200 p-4 hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center">
            <i class="ri-file-list-3-line text-xl text-secondary-600"></i>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-0.5">Total</p>
            <p class="text-xl font-bold text-secondary-800">{{ stats.total }}</p>
          </div>
        </div>
      </div>

      <!-- Pending Verification -->
      <div class="bg-white rounded-xl border border-blue-200 p-4 hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
            <i class="ri-admin-line text-xl text-blue-600"></i>
          </div>
          <div>
            <p class="text-xs text-blue-600 mb-0.5">Verifikasi</p>
            <p class="text-xl font-bold text-blue-700">{{ stats.pendingAdmin }}</p>
          </div>
        </div>
      </div>

      <!-- Verified -->
      <div class="bg-white rounded-xl border border-green-200 p-4 hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
            <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
          </div>
          <div>
            <p class="text-xs text-green-600 mb-0.5">Terverifikasi</p>
            <p class="text-xl font-bold text-green-700">{{ stats.verified }}</p>
          </div>
        </div>
      </div>

      <!-- Rejected -->
      <div class="bg-white rounded-xl border border-red-200 p-4 hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
            <i class="ri-close-circle-line text-xl text-red-600"></i>
          </div>
          <div>
            <p class="text-xs text-red-600 mb-0.5">Ditolak</p>
            <p class="text-xl font-bold text-red-700">{{ stats.ditolak }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
      <!-- Search Box -->
      <div class="flex-1 relative">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari berdasarkan nomor, nama, prodi, atau universitas..."
          class="w-full pl-10 pr-10 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
        <button
          v-if="searchQuery"
          @click="searchQuery = ''"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600 transition-colors"
        >
          <i class="ri-close-line"></i>
        </button>
      </div>

      <!-- Status Filter Dropdown -->
      <div class="sm:w-56 relative">
        <select
          v-model="statusFilter"
          class="w-full pl-4 pr-10 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent appearance-none bg-white cursor-pointer"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <i class="ri-filter-line absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none"></i>
      </div>
    </div>

    <!-- Result Count -->
    <div v-if="searchQuery || statusFilter !== 'all'" class="text-sm text-secondary-500 mb-4 flex items-center gap-2">
      <i class="ri-information-line"></i>
      <span v-if="searchQuery">{{ filteredList.length }} hasil ditemukan untuk "{{ searchQuery }}"</span>
      <span v-if="searchQuery && statusFilter !== 'all'" class="text-secondary-300">•</span>
      <span v-if="statusFilter !== 'all'" class="badge badge-secondary text-xs">{{ statusOptions.find(o => o.value === statusFilter)?.label }}</span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 animate-slide-up">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-secondary-200 p-5 animate-pulse">
        <div class="flex items-start gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <div class="h-5 w-32 bg-secondary-200 rounded"></div>
              <div class="h-6 w-24 bg-secondary-200 rounded-full"></div>
            </div>
            <div class="h-5 w-48 bg-secondary-200 rounded mb-2"></div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-24 bg-secondary-200 rounded"></div>
              <div class="h-4 w-32 bg-secondary-200 rounded"></div>
            </div>
          </div>
          <div class="flex gap-2">
            <div class="h-10 w-24 bg-secondary-200 rounded-lg"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredList.length === 0" class="text-center py-16 animate-slide-up">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-secondary-100 to-secondary-50 flex items-center justify-center mx-auto mb-4 border border-secondary-200">
        <i class="ri-inbox-archive-line text-4xl text-secondary-400"></i>
      </div>
      <h3 class="text-lg font-semibold text-secondary-800 mb-2">Tidak Ada Pengajuan</h3>
      <p class="text-sm text-secondary-500 max-w-md mx-auto">
        <template v-if="searchQuery">
          Tidak ditemukan hasil pencarian untuk "{{ searchQuery }}". Coba kata kunci lain.
        </template>
        <template v-else-if="statusFilter !== 'all'">
          Tidak ada pengajuan dengan status {{ statusOptions.find(o => o.value === statusFilter)?.label.toLowerCase() }}.
        </template>
        <template v-else>
          Belum ada pengajuan yang menunggu verifikasi. Pengajuan yang sudah diverifikasi akan ditampilkan di menu Riwayat Verifikasi.
        </template>
      </p>
    </div>

    <!-- List -->
    <div v-else class="space-y-4 animate-slide-up">
      <div
        v-for="item in paginatedList"
        :key="item.id"
        class="bg-white rounded-xl border border-secondary-200 shadow-sm hover:shadow-md hover:border-secondary-300 transition-all"
      >
        <div class="p-5">
          <!-- Header Row -->
          <div class="flex items-start gap-4 mb-4">
            <!-- Main Info -->
            <div class="flex-1 min-w-0">
              <!-- Number + Status Badge -->
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="font-semibold text-base text-secondary-800">{{ item.nomor_pengajuan }}</span>
                <span class="badge text-sm" :class="getStatusBadgeClass(item.status)">
                  {{ getStatusLabel(item.status) }}
                </span>
              </div>

              <!-- Name -->
              <p class="text-base font-semibold text-secondary-800 mb-2">{{ item.user?.name }}</p>

              <!-- Details -->
              <div class="flex items-center gap-2 text-sm text-secondary-500 flex-wrap">
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
                class="btn btn-sm"
                :class="item.status === 'pending_admin' ? 'btn-primary' : 'btn-secondary'"
              >
                <i :class="item.status === 'pending_admin' ? 'ri-checkbox-circle-line' : 'ri-eye-line'"></i>
                <span class="ml-1">{{ item.status === 'pending_admin' ? 'Verifikasi' : 'Detail' }}</span>
              </button>
            </div>

            <!-- Actions - Mobile Dropdown -->
            <div class="sm:hidden relative">
              <button
                @click="toggleDropdown(item.id, $event)"
                class="btn btn-ghost btn-icon p-2"
              >
                <i class="ri-more-2-fill text-xl"></i>
              </button>
              <div
                v-if="activeDropdown === item.id"
                class="absolute right-0 top-full mt-1 w-44 bg-white rounded-lg shadow-lg border border-secondary-200 py-1 z-20"
                @click.stop
              >
                <button
                  @click="openVerificationPage(item.id); closeAllMenus()"
                  class="w-full px-4 py-2.5 text-left text-sm hover:bg-secondary-50 flex items-center gap-2 transition-colors"
                >
                  <i :class="item.status === 'pending_admin' ? 'ri-checkbox-circle-line text-primary-600' : 'ri-eye-line text-secondary-600'"></i>
                  <span>{{ item.status === 'pending_admin' ? 'Verifikasi' : 'Detail' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-secondary-100 my-4"></div>

          <!-- Verifier Info -->
          <div v-if="getVerifierInfo(item)" class="mb-4">
            <div class="flex items-center gap-3 px-4 py-3 rounded-lg border" :class="[
              getVerifierInfo(item).bgColor,
              getVerifierInfo(item).borderColor
            ]">
              <div class="w-8 h-8 rounded-full bg-white/80 flex items-center justify-center">
                <i :class="[getVerifierInfo(item).icon, getVerifierInfo(item).iconColor]"></i>
              </div>
              <div class="flex-1">
                <p class="text-xs font-medium opacity-80">{{ getVerifierInfo(item).label }}</p>
                <p class="text-sm font-semibold" :class="getVerifierInfo(item).textColor">
                  {{ getVerifierInfo(item).name }}
                  <span class="text-secondary-300">•</span>
                  {{ getVerifierInfo(item).jabatan }}
                </p>
              </div>
            </div>
          </div>

          <!-- Milestone Progress (4 Steps) -->
          <div class="px-2">
            <div class="flex items-center justify-between relative py-3">
              <!-- Progress Line Background -->
              <div class="absolute top-1/2 left-0 right-0 h-1 -translate-y-1/2 bg-gray-200 rounded-full z-0"></div>
              <!-- Progress Line Active -->
              <div
                class="absolute top-1/2 left-0 h-1 -translate-y-1/2 rounded-full z-0 transition-all duration-500"
                :class="getProgressLineClass(item.status)"
              ></div>

              <!-- Dots -->
              <div
                v-for="(step, idx) in getMilestoneSteps(item)"
                :key="idx"
                class="relative z-10 flex flex-col items-center gap-1.5"
              >
                <!-- Pulse Ring for Current Step -->
                <div
                  v-if="step.status === 'current'"
                  class="absolute inset-0 rounded-full bg-blue-400 animate-ping opacity-75"
                  style="animation-duration: 1.5s;"
                ></div>
                <div
                  class="w-4 h-4 rounded-full transition-all duration-300 relative cursor-pointer hover:scale-110 border-2 border-white shadow-sm"
                  :class="getStepClass(step)"
                  :title="`${step.label}: ${getStepStatusDescription(step.status, step.label)}`"
                ></div>
                <span
                  class="text-xs font-medium whitespace-nowrap"
                  :class="step.status === 'current' ? 'text-blue-600' : step.status === 'completed' ? 'text-green-600' : 'text-gray-400'"
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
        class="w-10 h-10 rounded-lg border border-secondary-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-secondary-50 transition-colors flex items-center justify-center"
      >
        <i class="ri-arrow-left-s-line"></i>
      </button>
      <span class="text-sm text-secondary-600 px-4">
        Halaman <span class="font-semibold">{{ currentPage }}</span> dari <span class="font-semibold">{{ totalPages }}</span>
      </span>
      <button
        @click="currentPage++"
        :disabled="currentPage === totalPages"
        class="w-10 h-10 rounded-lg border border-secondary-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-secondary-50 transition-colors flex items-center justify-center"
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
