<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const router = useRouter()
const authStore = useAuthStore()
const pengajuanStore = usePengajuanStore()

// Page header actions
const headerActions = computed(() => {
  if (authStore.isPemohon) {
    return [{
      label: 'Buat Pengajuan Baru',
      icon: 'ri-add-line',
      to: '/pengajuan/baru',
      variant: 'btn-primary'
    }]
  }
  return []
})

const stats = ref({
  draft: 0,
  pending: 0,
  verified: 0,
  disetujui: 0,
  ditolak: 0,
  selesai: 0,
})

const searchQuery = ref('')
const filterStatus = ref('')
const verificationInfoMap = ref(new Map())
const loadingVerification = ref(false)
const loadingStats = ref(true)
const statsError = ref(null)

// Cancel modal state
const showCancelModal = ref(false)
const cancelingId = ref(null)
const cancelReason = ref('')
const activeActionMenu = ref(null)

// Toggle action menu
function toggleActionMenu(id) {
  activeActionMenu.value = activeActionMenu.value === id ? null : id
}

// Close action menu
function closeActionMenu() {
  activeActionMenu.value = null
}

// Close menu when clicking outside
function handleClickOutside(event) {
  if (!event.target.closest('[id^="action-menu-"]')) {
    closeActionMenu()
  }
}

// Check if pengajuan can be canceled (own pengajuan with pending/verified status)
const canCancel = (pengajuan) => {
  const isOwn = authStore.user?.id === pengajuan.user_id
  const cancellableStatus = ['pending_atasan', 'pending_admin', 'verified'].includes(pengajuan.status)
  return isOwn && cancellableStatus
}

// Check if pengajuan can be deleted (draft only)
const canDelete = (pengajuan) => {
  const isOwn = authStore.user?.id === pengajuan.user_id
  return isOwn && pengajuan.status === 'draft'
}

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_atasan', label: 'Pending Atasan' },
  { value: 'pending_admin', label: 'Pending Admin' },
  { value: 'verified', label: 'Terverifikasi' },
  { value: 'disetujui', label: 'Disetujui' },
  { value: 'signed', label: 'Signed' },
  { value: 'ditolak', label: 'Ditolak' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'completed', label: 'Completed' },
]

const recentPengajuan = computed(() => {
  let filtered = pengajuanStore.pengajuanList

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

onMounted(async () => {
  await loadStats()
  await loadVerificationInfo()
  // Add click outside listener
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(() => pengajuanStore.pengajuanList, (newList) => {
  updateStats(newList)
}, { deep: true })

async function loadVerificationInfo() {
  loadingVerification.value = true
  try {
    const promises = pengajuanStore.pengajuanList.slice(0, 10).map(async (pengajuan) => {
      try {
        const response = await api.get(`/verification/pengajuan/${pengajuan.id}`)
        verificationInfoMap.value.set(pengajuan.id, response.data)
      } catch (error) {
        console.error(`Failed to load verification info for pengajuan ${pengajuan.id}:`, error)
      }
    })
    await Promise.all(promises)
  } finally {
    loadingVerification.value = false
  }
}

function updateStats(pengajuan) {
  stats.value = {
    draft: pengajuan.filter((p) => p.status === 'draft').length,
    pending: pengajuan.filter((p) =>
      p.status === 'pending_atasan' || p.status === 'pending_admin'
    ).length,
    verified: pengajuan.filter((p) => p.status === 'verified').length,
    disetujui: pengajuan.filter((p) =>
      p.status === 'disetujui' || p.status === 'signed'
    ).length,
    ditolak: pengajuan.filter((p) => p.status === 'ditolak').length,
    selesai: pengajuan.filter((p) =>
      p.status === 'selesai' || p.status === 'completed'
    ).length,
  }
}

async function loadStats() {
  loadingStats.value = true
  statsError.value = null
  try {
    const response = await pengajuanStore.fetchPengajuan({ per_page: 100 })
    updateStats(response || [])
  } catch (error) {
    console.error('Failed to load stats:', error)
    statsError.value = error.response?.data?.message || 'Gagal memuat data'
  } finally {
    loadingStats.value = false
  }
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    pending_atasan: 'Pending Atasan',
    pending_admin: 'Pending Admin',
    verified: 'Terverifikasi',
    disetujui: 'Disetujui',
    signed: 'Signed',
    ditolak: 'Ditolak',
    selesai: 'Selesai',
    completed: 'Completed',
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-default',
    pending_atasan: 'badge-warning',
    pending_admin: 'badge-warning',
    verified: 'badge-info',
    disetujui: 'badge-primary',
    signed: 'badge-primary',
    ditolak: 'badge-danger',
    selesai: 'badge-success',
    completed: 'badge-success',
  }
  return badges[status] || 'badge-default'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    pending_atasan: 'ri-time-line',
    pending_admin: 'ri-time-line',
    verified: 'ri-verified-badge-line',
    disetujui: 'ri-check-line',
    signed: 'ri-edit-line',
    ditolak: 'ri-close-line',
    selesai: 'ri-checkbox-circle-line',
    completed: 'ri-checkbox-circle-line',
  }
  return icons[status] || 'ri-file-line'
}

function getMilestoneSteps(pengajuan) {
  const verificationInfo = verificationInfoMap.value.get(pengajuan.id)
  const status = pengajuan.status

  const steps = []

  // Step 1: Submit
  steps.push({
    label: 'Dikirim',
    status: ['pending_atasan', 'pending_admin', 'verified', 'disetujui', 'signed', 'selesai', 'completed'].includes(status) ? 'completed' : 'pending',
  })

  // Step 2: Verifikasi Admin
  const adminStep = verificationInfo?.verification_chain?.find(c => c.level === 'admin_bkpsdm')
  steps.push({
    label: 'Verifikasi',
    status: ['verified', 'disetujui', 'signed', 'selesai', 'completed'].includes(status) ? 'completed' :
              ['pending_atasan', 'pending_admin'].includes(status) ? 'current' : 'pending',
  })

  // Step 3: Disetujui
  steps.push({
    label: 'Disetujui',
    status: ['signed', 'selesai', 'completed'].includes(status) ? 'completed' :
              status === 'verified' ? 'current' : 'pending',
  })

  // Step 4: TTE/Tandatangan
  steps.push({
    label: 'TTE',
    status: ['selesai', 'completed'].includes(status) ? 'completed' :
              ['disetujui', 'signed'].includes(status) ? 'current' : 'pending',
  })

  // Step 5: Selesai
  steps.push({
    label: 'Selesai',
    status: status === 'completed' ? 'completed' :
              status === 'selesai' ? 'current' : 'pending',
  })

  return steps
}

function getStepClass(step) {
  if (step.status === 'completed') return 'bg-green-500'
  if (step.status === 'current') return 'bg-primary-500 animate-pulse'
  return 'bg-secondary-300'
}

function getLineClass(index, steps) {
  const currentStepIndex = steps.findIndex(s => s.status === 'current')
  if (index < currentStepIndex) return 'bg-green-500'
  if (index === currentStepIndex) return 'bg-primary-500'
  return 'bg-secondary-200'
}

// Navigate to detail with state to remember we came from dashboard
function goToDetail(id) {
  router.push({
    path: `/pengajuan/${id}`,
    state: { from: 'dashboard' }
  })
}

// Open cancel modal
function openCancelModal(id) {
  cancelingId.value = id
  cancelReason.value = ''
  showCancelModal.value = true
}

// Close cancel modal
function closeCancelModal() {
  showCancelModal.value = false
  cancelingId.value = null
  cancelReason.value = ''
}

// Handle cancel pengajuan
async function handleCancel() {
  if (!cancelingId.value) return

  try {
    await pengajuanStore.cancelPengajuan(cancelingId.value)
    await loadStats() // Refresh data
    toast.success('Berkas pengajuan berhasil dicabut')
    closeCancelModal()
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mencabut berkas pengajuan'
    toast.error(message)
  }
}

// Handle delete pengajuan (draft only)
async function handleDelete(id) {
  if (!confirm('Yakin ingin menghapus pengajuan ini?')) return

  try {
    await pengajuanStore.deletePengajuan(id)
    await loadStats() // Refresh data
    toast.success('Pengajuan berhasil dihapus')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus pengajuan'
    toast.error(message)
  }
}

</script>

<template>
  <MainLayout>
    <!-- Page Header -->
    <PageHeader
      title="Dashboard"
      :subtitle="`Selamat datang, ${authStore.user?.name}`"
      :actions="headerActions"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
      <!-- Loading Skeleton -->
      <template v-if="loadingStats">
        <div v-for="i in 6" :key="i" class="card animate-pulse">
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
              <p class="text-sm text-secondary-500 mt-1">Pending</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
              <i class="ri-time-line text-2xl text-warning"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="card animate-slide-up" style="animation-delay: 75ms;">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-3xl font-bold text-info">{{ stats.verified }}</p>
              <p class="text-sm text-secondary-500 mt-1">Terverifikasi</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
              <i class="ri-verified-badge-line text-2xl text-info"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="card animate-slide-up" style="animation-delay: 125ms;">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-3xl font-bold text-success">{{ stats.disetujui }}</p>
              <p class="text-sm text-secondary-500 mt-1">Disetujui</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <i class="ri-check-line text-2xl text-success"></i>
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

      <div class="card animate-slide-up" style="animation-delay: 225ms;">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-3xl font-bold text-primary-600">{{ stats.selesai }}</p>
              <p class="text-sm text-secondary-500 mt-1">Selesai</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center">
              <i class="ri-checkbox-circle-line text-2xl text-primary-600"></i>
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
            <router-link to="/pengajuan" class="btn btn-ghost btn-sm gap-1">
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
        <div v-else-if="recentPengajuan.length === 0 && !statsError" class="text-center py-12">
          <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
            <i class="ri-inbox-line text-3xl text-secondary-400"></i>
          </div>
          <p class="text-secondary-500 mb-4">Tidak ada pengajuan</p>
          <router-link v-if="authStore.isPemohon" to="/pengajuan/baru" class="btn btn-primary">
            <i class="ri-add-line"></i>
            Buat Pengajuan Baru
          </router-link>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="item in recentPengajuan"
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
                    <span :class="['badge', getStatusBadge(item.status), 'flex items-center gap-1']">
                      <i :class="getStatusIcon(item.status)"></i>
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
                    <span><i class="ri-graduation-cap-line mr-1"></i>{{ item.nama_prodi }}</span>
                    <span><i class="ri-building-line mr-1"></i>{{ item.perguruan_tinggi || '-' }}</span>
                    <span><i class="ri-calendar-line mr-1"></i>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                  </div>
                </div>
                <!-- Action -->
                <div class="flex items-center gap-2">
                  <!-- Mobile dropdown -->
                  <div class="relative sm:hidden" :id="`action-menu-${item.id}`">
                    <button
                      @click.stop="toggleActionMenu(item.id)"
                      class="btn btn-ghost btn-sm btn-icon"
                    >
                      <i class="ri-more-2-fill text-xl"></i>
                    </button>
                    <div
                      v-if="activeActionMenu === item.id"
                      class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-secondary-200 z-20"
                    >
                      <button @click="goToDetail(item.id)" class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2">
                        <i class="ri-eye-line"></i> Lihat
                      </button>
                      <button
                        v-if="canDelete(item)"
                        @click="handleDelete(item.id)"
                        class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2"
                      >
                        <i class="ri-delete-bin-line"></i> Hapus
                      </button>
                      <button
                        v-if="canCancel(item)"
                        @click="openCancelModal(item.id)"
                        class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2"
                      >
                        <i class="ri-close-circle-line"></i> Cabut Berkas
                      </button>
                    </div>
                  </div>

                  <!-- Desktop buttons -->
                  <div class="hidden sm:flex items-center gap-2">
                    <button @click="goToDetail(item.id)" class="btn btn-primary btn-sm">
                      <i class="ri-eye-line mr-1"></i>
                      Lihat
                    </button>
                    <button
                      v-if="canCancel(item)"
                      @click="openCancelModal(item.id)"
                      class="btn btn-danger btn-sm"
                    >
                      <i class="ri-close-circle-line mr-1"></i>
                      Cabut Berkas
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
              </div>
            </div>

            <!-- Milestone Dot-Line -->
            <div class="px-4 pb-4">
              <div class="flex items-center justify-between relative">
                <!-- Progress Line Background -->
                <div class="absolute top-1/2 left-0 right-0 h-0.5 -translate-y-1/2 bg-secondary-200 z-0"></div>
                <!-- Progress Line Active -->
                <div
                  class="absolute top-1/2 left-0 h-0.5 -translate-y-1/2 z-0 transition-all duration-300"
                  :class="{
                    'w-0': item.status === 'draft',
                    'w-1/5': item.status === 'pending_atasan' || item.status === 'pending_admin',
                    'w-2/5': item.status === 'verified',
                    'w-3/5': item.status === 'disetujui' || item.status === 'signed',
                    'w-4/5': item.status === 'selesai',
                    'w-full': item.status === 'completed'
                  }"
                  :style="{ backgroundColor: item.status === 'draft' ? '' : (item.status === 'completed' || item.status === 'selesai' ? '#22c55e' : '#3b82f6') }"
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
                  <span class="text-xs mt-1 text-secondary-600 whitespace-nowrap">{{ step.label }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCancelModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeCancelModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b">
              <h3 class="text-lg font-semibold">Cabut Berkas Pengajuan?</h3>
              <button @click="closeCancelModal" class="btn btn-ghost btn-icon">
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
              <p class="text-secondary-600 mb-4">
                Anda yakin ingin menarik pengajuan ini? Status akan kembali menjadi Draft dan Anda dapat mengeditnya kembali.
              </p>
              <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <i class="ri-error-warning-line text-red-500 text-xl mt-0.5"></i>
                  <div class="text-sm text-red-800">
                    <p class="font-medium">Perhatian</p>
                    <p>Pengajuan yang ditarik perlu dikirim ulang untuk diproses kembali.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-2 p-6 border-t">
              <button @click="closeCancelModal" class="btn btn-ghost">
                Batal
              </button>
              <button
                @click="handleCancel"
                :disabled="pengajuanStore.loading"
                class="btn btn-danger"
              >
                <i v-if="pengajuanStore.loading" class="ri-loader-4-line animate-spin mr-1"></i>
                <i v-else class="ri-close-circle-line mr-1"></i>
                Ya, Cabut Berkas
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
