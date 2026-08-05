<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePgaStore } from '@/stores/pga'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import MainLayout from '@/components/layout/MainLayout.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const router = useRouter()
const authStore = useAuthStore()
const pgaStore = usePgaStore()

// Page header actions
const isRefreshing = ref(false)

const headerActions = computed(() => {
  const actions = []

  // Refresh button for all users
  actions.push({
    label: 'Refresh',
    icon: 'ri-refresh-line',
    onClick: refreshData,
    variant: 'btn-secondary',
    isLoading: isRefreshing.value
  })

  if (authStore.isPemohon || authStore.isAtasan || authStore.isKepala) {
    actions.push({
      label: 'Buat Pengajuan Baru',
      icon: 'ri-add-line',
      to: '/pga/baru',
      variant: 'btn-primary'
    })
  }

  return actions
})

// Refresh all data
async function refreshData() {
  isRefreshing.value = true
  try {
    await loadStats()
    toast.success('Data berhasil diperbarui')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal memperbarui data'
    toast.error(message)
  } finally {
    isRefreshing.value = false
  }
}

const stats = ref({
  draft: 0,
  pending: 0,
  selesai: 0,
  ditolak: 0,
})

const searchQuery = ref('')
const filterStatus = ref('')
const loadingStats = ref(true)
const statsError = ref(null)
const activeActionMenu = ref(null)

// Cancel modal state
const showCancelModal = ref(false)
const cancelingId = ref(null)
const cancelReason = ref('')

// Toggle action menu
function toggleActionMenu(id) {
  activeActionMenu.value = activeActionMenu.value === id ? null : id
}

// Close action menu
function closeActionMenu() {
  activeActionMenu.value = null
}

// Close all menus
function closeAllMenus() {
  activeActionMenu.value = null
}

// Check if pengajuan can be deleted (draft only)
const canDelete = (pga) => {
  const isOwn = authStore.user?.id === pga.user_id
  return isOwn && pga.status === 'draft'
}

// Check if pengajuan can be restored (dicabut only)
const canRestore = (pga) => {
  const isOwn = authStore.user?.id === pga.user_id
  return isOwn && pga.status === 'dicabut'
}

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'approved_admin', label: 'Menunggu Persetujuan' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'ditolak', label: 'Ditolak' },
]

const recentPga = computed(() => {
  let filtered = pgaList.value

  // Apply status filter
  if (filterStatus.value) {
    filtered = filtered.filter(p => p.status === filterStatus.value)
  }

  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p => {
      return (
        (p.nomor_pengajuan && p.nomor_pengajuan.toLowerCase().includes(query)) ||
        (p.nama_prodi && p.nama_prodi.toLowerCase().includes(query)) ||
        (p.perguruan_tinggi && p.perguruan_tinggi.toLowerCase().includes(query)) ||
        (p.status && p.status.toLowerCase().includes(query))
      )
    })
  }

  return filtered.slice(0, 10)
})

const pgaList = ref([])

async function loadStats() {
  loadingStats.value = true
  statsError.value = null
  try {
    const response = await pgaStore.fetchPga()
    pgaList.value = response.data || []
    updateStats(pgaList.value)
  } catch (error) {
    console.error('Failed to load stats:', error)
    statsError.value = error.response?.data?.message || 'Gagal memuat data'
  } finally {
    loadingStats.value = false
  }
}

function updateStats(pga) {
  stats.value = {
    draft: pga.filter((p) => p.status === 'draft').length,
    pending: pga.filter((p) => p.status === 'approved_admin').length,
    selesai: pga.filter((p) => p.status === 'selesai').length,
    ditolak: pga.filter((p) => p.status === 'ditolak').length,
  }
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    approved_admin: 'Menunggu Persetujuan',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
    dicabut: 'Dicabut',
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-secondary',
    approved_admin: 'badge-warning',
    selesai: 'badge-success',
    ditolak: 'badge-danger',
    dicabut: 'badge-secondary',
  }
  return badges[status] || 'badge-secondary'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    approved_admin: 'ri-time-line',
    selesai: 'ri-checkbox-circle-line',
    ditolak: 'ri-close-line',
    dicabut: 'ri-delete-bin-line',
  }
  return icons[status] || 'ri-file-line'
}

// Get milestone steps for PGA
function getMilestoneSteps(pga) {
  const status = pga.status
  const steps = []

  // PGA Flow: Draft → Menunggu → Selesai (3 steps)

  // Step 1: Draft
  steps.push({
    label: 'Draft',
    status: ['approved_admin', 'selesai'].includes(status) ? 'completed' : 'pending',
  })

  // Step 2: Menunggu
  steps.push({
    label: 'Verifikasi',
    status: ['selesai'].includes(status) ? 'completed' :
              ['approved_admin'].includes(status) ? 'current' : 'pending',
  })

  // Step 3: Selesai
  steps.push({
    label: 'Selesai',
    status: ['selesai'].includes(status) ? 'completed' : 'pending',
  })

  return steps
}

function getStepClass(step) {
  if (step.status === 'completed') return 'bg-green-500'
  if (step.status === 'current') return 'bg-blue-500'
  return 'bg-gray-300'
}

function getProgressLineClass(status) {
  // PGA Flow: 3 Steps - Draft → Menunggu → Selesai
  if (status === 'draft' || status === 'dicabut' || status === 'ditolak') {
    return 'w-0 bg-gray-200'
  }
  if (status === 'approved_admin') {
    return 'w-1/3 bg-blue-500'
  }
  if (status === 'selesai') {
    return 'w-full bg-green-500'
  }
  return 'w-0 bg-gray-200'
}

// Navigate to detail with state
function goToDetail(id) {
  router.push({
    path: `/pga/${id}`,
    state: { from: 'dashboard' }
  })
}

function goToEdit(id) {
  router.push(`/pga/${id}/edit`)
}

// Handle delete pengajuan - hapus permanen ke riwayat (dicabut)
async function handleDelete(id) {
  if (!confirm('Yakin ingin menghapus pengajuan ini? Data akan masuk ke Riwayat dan tidak dapat diedit kembali.')) return

  try {
    await pgaStore.deletePga(id)
    await loadStats()
    toast.success('Pengajuan dihapus dan masuk ke Riwayat')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus pengajuan'
    toast.error(message)
  }
}

// Handle restore pengajuan
async function handleRestore(id) {
  try {
    await pgaStore.restorePga(id)
    await loadStats()
    toast.success('Pengajuan berhasil dipulihkan')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal memulihkan pengajuan'
    toast.error(message)
  }
}

// Close menu when clicking outside
function handleClickOutside(event) {
  if (!event.target.closest('[id^="action-menu-"]')) {
    closeActionMenu()
  }
}

onMounted(async () => {
  await loadStats()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <MainLayout>
    <!-- Page Header -->
    <PageHeader
      title="Dashboard Pencantuman Gelar Akademik"
      :subtitle="`Selamat datang, ${authStore.user?.name}`"
      :actions="headerActions"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Loading Skeleton -->
      <template v-if="loadingStats">
        <div v-for="i in 4" :key="i" class="card animate-pulse">
          <div class="card-body">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="h-8 bg-secondary-200 rounded w-16 mb-2"></div>
                <div class="h-4 bg-secondary-200 rounded w-24"></div>
              </div>
              <div class="w-12 h-12 rounded-xl bg-secondary-200"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <template v-else-if="statsError">
        <div class="col-span-full card bg-red-50 border-red-200">
          <div class="card-body py-4">
            <div class="flex items-center gap-3 text-red-700">
              <i class="ri-error-warning-line text-xl"></i>
              <div>
                <p class="font-medium">Gagal memuat data</p>
                <p class="text-sm text-red-600">{{ statsError }}</p>
              </div>
              <button @click="loadStats" class="btn btn-sm btn-danger ml-auto">
                <i class="ri-refresh-line"></i>
                Retry
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Stats Cards -->
      <template v-else>
        <div class="card animate-slide-up" style="animation-delay: 0ms;">
          <div class="card-body">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold text-secondary-800">{{ stats.draft }}</p>
                <p class="text-sm text-secondary-500 mt-1">Draft</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-secondary-100 flex items-center justify-center">
                <i class="ri-draft-line text-2xl text-secondary-500"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="card animate-slide-up" style="animation-delay: 50ms;">
          <div class="card-body">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold text-warning">{{ stats.pending }}</p>
                <p class="text-sm text-secondary-500 mt-1">Menunggu</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                <i class="ri-time-line text-2xl text-warning"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="card animate-slide-up" style="animation-delay: 125ms;">
          <div class="card-body">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold text-success">{{ stats.selesai }}</p>
                <p class="text-sm text-secondary-500 mt-1">Selesai</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                <i class="ri-checkbox-circle-line text-2xl text-success"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="card animate-slide-up" style="animation-delay: 175ms;">
          <div class="card-body">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold text-danger">{{ stats.ditolak }}</p>
                <p class="text-sm text-secondary-500 mt-1">Ditolak</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                <i class="ri-close-line text-2xl text-danger"></i>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Recent Submissions Table -->
    <div class="card animate-slide-up" style="animation-delay: 250ms;">
      <div class="card-header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-file-list-3-line text-lg text-primary-600"></i>
            Pengajuan Terbaru
          </h3>
          <div class="flex items-center gap-2">
            <router-link to="/pga" class="btn btn-ghost btn-sm gap-1">
              Lihat Semua
              <i class="ri-arrow-right-line text-base"></i>
            </router-link>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Search & Filter -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
          <div class="relative flex-1">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nomor, prodi, universitas..."
              class="w-full pl-10 pr-4 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
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

        <!-- Loading State -->
        <div v-if="loadingStats" class="space-y-4">
          <div v-for="i in 3" :key="i" class="border border-secondary-200 rounded-xl p-4 animate-pulse">
            <div class="flex items-center gap-4 mb-3">
              <div class="h-5 bg-secondary-200 rounded w-32"></div>
              <div class="h-6 bg-secondary-200 rounded w-20"></div>
            </div>
            <div class="flex flex-wrap gap-4">
              <div class="h-4 bg-secondary-200 rounded w-40"></div>
              <div class="h-4 bg-secondary-200 rounded w-32"></div>
              <div class="h-4 bg-secondary-200 rounded w-24"></div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="recentPga.length === 0 && !statsError" class="text-center py-12">
          <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
            <i class="ri-inbox-line text-3xl text-secondary-400"></i>
          </div>
          <p class="text-secondary-500 mb-4">Tidak ada pengajuan</p>
          <router-link v-if="authStore.isPemohon" to="/pga/baru" class="btn btn-primary">
            <i class="ri-add-line"></i>
            Buat Pengajuan Baru
          </router-link>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="item in recentPga"
            :key="item.id"
            class="border border-secondary-200 rounded-xl hover:border-primary-300 hover:shadow-sm transition-all relative overflow-visible"
          >
            <!-- Main Row -->
            <div class="p-4">
              <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                <!-- Mobile: Absolute positioned menu button (top-right) -->
                <div class="absolute top-3 right-3 sm:hidden z-[100]">
                  <button
                    @click.stop="toggleActionMenu(item.id)"
                    class="btn btn-ghost btn-sm btn-icon"
                  >
                    <i class="ri-more-2-fill text-xl"></i>
                  </button>
                  <!-- Mobile Dropdown Menu -->
                  <div
                    v-if="activeActionMenu === item.id"
                    class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-xl border border-secondary-200 z-[100]"
                  >
                    <button @click="goToDetail(item.id)" class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2">
                      <i class="ri-eye-line"></i> Lihat
                    </button>
                    <button
                      v-if="canRestore(item)"
                      @click="handleRestore(item.id)"
                      class="w-full text-left px-4 py-2 hover:bg-green-50 text-green-600 flex items-center gap-2"
                    >
                      <i class="ri-refresh-line"></i> Pulihkan
                    </button>
                    <button
                      v-if="canDelete(item)"
                      @click="handleDelete(item.id)"
                      class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2"
                    >
                      <i class="ri-delete-bin-line"></i> Hapus
                    </button>
                  </div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0 pr-10 sm:pr-0">
                  <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="font-semibold text-secondary-800">{{ item.nomor_pengajuan || '-' }}</span>
                    <span :class="['badge', getStatusBadge(item.status), 'flex items-center gap-1']">
                      <i :class="getStatusIcon(item.status)"></i>
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
                    <span><i class="ri-user-line mr-1"></i>{{ item.user?.name || '-' }}</span>
                    <span><i class="ri-graduation-cap-line mr-1"></i>{{ item.nama_prodi }}</span>
                    <span><i class="ri-building-line mr-1"></i>{{ item.perguruan_tinggi || '-' }}</span>
                    <span><i class="ri-calendar-line mr-1"></i>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                  </div>
                </div>

                <!-- Desktop buttons -->
                <div class="hidden sm:flex items-center gap-2">
                  <button @click="goToDetail(item.id)" class="btn btn-primary btn-sm">
                    <i class="ri-eye-line mr-1"></i>
                    Lihat
                  </button>
                  <button
                    v-if="canRestore(item)"
                    @click="handleRestore(item.id)"
                    class="btn btn-success btn-sm"
                  >
                    <i class="ri-refresh-line mr-1"></i>
                    Pulihkan
                  </button>
                  <button
                    v-if="canDelete(item)"
                    @click="handleDelete(item.id)"
                    class="btn btn-danger btn-sm"
                  >
                    <i class="ri-delete-bin-line mr-1"></i>
                    Hapus
                  </button>
                </div>
              </div>

              <!-- Milestone Dot-Line -->
              <div class="px-4 pb-4">
                <div class="flex items-center justify-between relative">
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
                    <div
                      class="w-3 h-3 rounded-full transition-all duration-300"
                      :class="getStepClass(step)"
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
      </div>
    </div>
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
