<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useNotificationStore } from '@/stores/notification'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const notificationStore = useNotificationStore()

const router = useRouter()
const authStore = useAuthStore()
const pengajuanStore = usePengajuanStore()

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

  if (authStore.isPemohon || authStore.isAtasan) {
    actions.push({
      label: 'Buat Pengajuan Baru',
      icon: 'ri-add-line',
      to: '/pengajuan/baru',
      variant: 'btn-primary'
    })
  }

  return actions
})

// Refresh all data
async function refreshData() {
  isRefreshing.value = true
  try {
    await Promise.all([
      loadStats(),
      loadVerificationInfo(),
      loadPengajuanNotifications(),
      loadSuratInfo()
    ])
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

// Notification tracking per pengajuan
const pengajuanNotificationMap = ref(new Map()) // pengajuan_id -> unread count

// Cancel modal state
const showCancelModal = ref(false)
const cancelingId = ref(null)
const cancelReason = ref('')
const activeActionMenu = ref(null)
const hoveredMilestone = ref(null) // Track hovered milestone for tooltip
const tooltipPosition = ref({ left: '0px', top: '0px' }) // Fixed tooltip position

// Surat menu state
const activeSuratMenu = ref(null)
const suratInfoMap = ref(new Map()) // pengajuan_id -> { surat_izin, surat_tugas_mandiri, surat_tugas_dinas }
const loadingSurat = ref(false)
const downloadingSurat = ref(new Map()) // pengajuan_id -> { izin: false, tugas_mandiri: false, tugas_dinas: false }

// Mobile surat selection modal state
const showSuratModal = ref(false)
const selectedPengajuanForSurat = ref(null)

// Open surat modal (mobile)
function openSuratModal(pengajuan) {
  closeAllMenus()
  selectedPengajuanForSurat.value = pengajuan
  showSuratModal.value = true
}

// Close surat modal
function closeSuratModal() {
  showSuratModal.value = false
  selectedPengajuanForSurat.value = null
}

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
  activeSuratMenu.value = null
}

// Toggle surat menu
function toggleSuratMenu(id) {
  activeSuratMenu.value = activeSuratMenu.value === id ? null : id
}

// Close surat menu
function closeSuratMenu() {
  activeSuratMenu.value = null
}

// Check if pengajuan has any surat
function hasSurat(pengajuan) {
  const info = suratInfoMap.value.get(pengajuan.id)
  return info && (info.surat_izin || info.surat_tugas_mandiri || info.surat_tugas_dinas)
}

// Get surat info for a pengajuan
function getSuratInfo(pengajuan) {
  return suratInfoMap.value.get(pengajuan.id) || { surat_izin: null, surat_tugas_mandiri: null, surat_tugas_dinas: null }
}

// Load surat info for pengajuan
async function loadSuratInfo() {
  loadingSurat.value = true
  try {
    const pengajuanWithSurat = pengajuanStore.pengajuanList.filter(p =>
      ['signed', 'selesai', 'completed', 'surat_izin'].includes(p.status)
    )

    const promises = pengajuanWithSurat.map(async (pengajuan) => {
      try {
        // Try to get surat izin
        let suratIzin = null
        try {
          const izinResponse = await api.get(`/pengajuan/${pengajuan.id}/surat-izin`)
          suratIzin = izinResponse.data.data
        } catch (e) {
          // No surat izin
        }

        // Try to get surat tugas mandiri
        let suratTugasMandiri = null
        try {
          const tugasResponse = await api.get(`/pengajuan/${pengajuan.id}/surat-tugas-mandiri`)
          suratTugasMandiri = tugasResponse.data.data
        } catch (e) {
          // No surat tugas mandiri
        }

        // Try to get surat tugas dinas
        let suratTugasDinas = null
        try {
          const dinasResponse = await api.get(`/surat-tugas/${pengajuan.id}`)
          suratTugasDinas = dinasResponse.data.data
        } catch (e) {
          // No surat tugas dinas
        }

        suratInfoMap.value.set(pengajuan.id, {
          surat_izin: suratIzin,
          surat_tugas_mandiri: suratTugasMandiri,
          surat_tugas_dinas: suratTugasDinas
        })
      } catch (error) {
        console.error(`Failed to load surat info for pengajuan ${pengajuan.id}:`, error)
      }
    })
    await Promise.all(promises)
  } finally {
    loadingSurat.value = false
  }
}

// Download surat izin
function downloadSuratIzin(pengajuan) {
  const info = getSuratInfo(pengajuan)
  if (!info.surat_izin) {
    toast.error('Surat Izin Belajar belum tersedia')
    return
  }

  downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), izin: true })
  const token = authStore.token
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-izin/${info.surat_izin.id}/download?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
  toast.success('Surat Izin Belajar sedang diunduh...')
  closeSuratModal() // Close modal after download

  setTimeout(() => {
    downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), izin: false })
  }, 1000)
}

// Download surat tugas mandiri
function downloadSuratTugasMandiri(pengajuan) {
  const info = getSuratInfo(pengajuan)
  if (!info.surat_tugas_mandiri) {
    toast.error('Surat Tugas Belajar Mandiri belum tersedia')
    return
  }

  downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), tugas_mandiri: true })
  const token = authStore.token
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/admin/surat-tugas-mandiri/${info.surat_tugas_mandiri.id}/download?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
  toast.success('Surat Tugas Belajar Mandiri sedang diunduh...')
  closeSuratModal() // Close modal after download

  setTimeout(() => {
    downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), tugas_mandiri: false })
  }, 1000)
}

// Download surat tugas dinas
function downloadSuratTugasDinas(pengajuan) {
  const info = getSuratInfo(pengajuan)
  if (!info.surat_tugas_dinas) {
    toast.error('Surat Tugas Belajar dari Kepala Dinas belum tersedia')
    return
  }

  downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), tugas_dinas: true })
  const token = authStore.token
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const url = `${apiUrl}/kepala/surat-tugas/${info.surat_tugas_dinas.id}/pdf?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
  toast.success('Surat Tugas Belajar sedang diunduh...')
  closeSuratModal() // Close modal after download

  setTimeout(() => {
    downloadingSurat.value.set(pengajuan.id, { ...downloadingSurat.value.get(pengajuan.id), tugas_dinas: false })
  }, 1000)
}

// Helper to check if downloading
function isDownloading(pengajuanId, type) {
  const state = downloadingSurat.value.get(pengajuanId)
  return state ? state[type] : false
}

// Close menu when clicking outside
function handleClickOutside(event) {
  if (!event.target.closest('[id^="action-menu-"]')) {
    closeActionMenu()
  }
  if (!event.target.closest('[id^="surat-menu-"]')) {
    closeSuratMenu()
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

// Check if pengajuan can be restored (dicabut only)
const canRestore = (pengajuan) => {
  const isOwn = authStore.user?.id === pengajuan.user_id
  return isOwn && pengajuan.status === 'dicabut'
}

// Handle restore pengajuan
async function handleRestore(id) {
  try {
    await api.post(`/pengajuan/${id}/restore`)
    await loadStats()
    toast.success('Pengajuan berhasil dipulihkan')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal memulihkan pengajuan'
    toast.error(message)
  }
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
  { value: 'dicabut', label: 'Dicabut' },
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
  await loadPengajuanNotifications()
  await loadSuratInfo()
  // Add click outside listener
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch removed - updateStats already called in loadStats

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

// Load notifications for each pengajuan
async function loadPengajuanNotifications() {
  try {
    const unreadNotifications = await notificationStore.fetchUnreadNotifications()
    // Create a map of pengajuan_id -> notification count
    pengajuanNotificationMap.value.clear()
    unreadNotifications.forEach(notif => {
      if (notif.pengajuan_id) {
        const currentCount = pengajuanNotificationMap.value.get(notif.pengajuan_id) || 0
        pengajuanNotificationMap.value.set(notif.pengajuan_id, currentCount + 1)
      }
    })
  } catch (error) {
    console.error('Failed to load pengajuan notifications:', error)
  }
}

// Get unread notification count for a pengajuan
function getPengajuanNotificationCount(pengajuanId) {
  return pengajuanNotificationMap.value.get(pengajuanId) || 0
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
    dicabut: 'Dicabut',
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
    dicabut: 'badge-secondary',
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
    dicabut: 'ri-delete-bin-line',
    selesai: 'ri-checkbox-circle-line',
    completed: 'ri-checkbox-circle-line',
  }
  return icons[status] || 'ri-file-line'
}

function getMilestoneSteps(pengajuan) {
  const verificationInfo = verificationInfoMap.value.get(pengajuan.id)
  const status = pengajuan.status

  const steps = []

  // Simplified Flow: 4 Steps - Dikirim → Verifikasi → TTE → Selesai

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

  // Step 3: TTE (Kepala BKPSDM)
  steps.push({
    label: 'TTE',
    status: ['selesai', 'completed'].includes(status) ? 'completed' :
              ['signed'].includes(status) ? 'current' : 'pending',
  })

  // Step 4: Selesai
  steps.push({
    label: 'Selesai',
    status: ['selesai', 'completed'].includes(status) ? 'completed' : 'pending',
  })

  return steps
}

function getStepClass(step) {
  if (step.status === 'completed') return 'bg-green-500'
  if (step.status === 'current') return 'bg-blue-500'
  return 'bg-gray-300'
}

function getProgressLineClass(status) {
  // Simplified Flow: 4 Steps - Dikirim → Verifikasi → TTE → Selesai
  // Draft - no progress
  if (status === 'draft' || status === 'dicabut' || status === 'ditolak') {
    return 'w-0 bg-gray-200'
  }
  // Pending Admin - 1/4 progress (25%) - Dikirim completed, Verifikasi current
  if (status === 'pending_admin') {
    return 'w-1/4 bg-blue-500'
  }
  // Verified - 2/4 progress (50%) - Verifikasi completed, TTE current
  if (status === 'verified') {
    return 'w-2/4 bg-blue-500'
  }
  // Signed - 3/4 progress (75%) - TTE completed, Selesai current
  if (status === 'signed') {
    return 'w-3/4 bg-blue-500'
  }
  // Selesai/Completed - full progress (100%)
  if (status === 'selesai' || status === 'completed') {
    return 'w-full bg-green-500'
  }
  // Default
  return 'w-0 bg-gray-200'
}

function getLineClass(index, steps) {
  const currentStepIndex = steps.findIndex(s => s.status === 'current')
  if (index < currentStepIndex) return 'bg-green-500'
  if (index === currentStepIndex) return 'bg-blue-500'
  return 'bg-gray-200'
}

// Get tooltip info for milestone step (Updated for 4 Steps)
function getMilestoneTooltip(step, pengajuan) {
  if (step.status === 'completed') {
    const completedInfo = {
      'Dikirim': 'Pengajuan telah dikirim',
      'Verifikasi': 'Dokumen lengkap & telah diverifikasi admin',
      'TTE': 'Surat telah ditandatangani secara elektronik',
      'Selesai': 'Proses pengajuan telah selesai'
    }
    return {
      title: 'Selesai',
      description: completedInfo[step.label] || 'Tahap ini telah selesai',
      icon: 'ri-checkbox-circle-line',
      color: 'text-green-600'
    }
  }

  if (step.status === 'current') {
    const currentInfo = {
      'Dikirim': 'Menunggu verifikasi dari admin BKPSDM',
      'Verifikasi': 'Sedang diverifikasi dokumen oleh admin BKPSDM',
      'TTE': 'Sedang proses penandatanganan elektronik (TTE)',
      'Selesai': 'Proses hampir selesai'
    }
    return {
      title: 'Sedang Diproses',
      description: currentInfo[step.label] || 'Tahap ini sedang diproses',
      icon: 'ri-loader-4-line',
      color: 'text-blue-600'
    }
  }

  // Pending steps
  const pendingInfo = {
    'Dikirim': 'Pengajuan sudah dikirim',
    'Verifikasi': 'Menunggu verifikasi dokumen oleh admin BKPSDM',
    'TTE': 'Menunggu proses Tanda Tangan Elektronik',
    'Selesai': 'Menunggu proses penyelesaian'
  }
  return {
    title: 'Menunggu',
    description: pendingInfo[step.label] || 'Tahap ini belum dimulai',
    icon: 'ri-time-line',
    color: 'text-gray-500'
  }
}

// Update tooltip position when hovering milestone
function updateTooltipPosition(event, itemId, stepIndex) {
  const rect = event.target.getBoundingClientRect()
  tooltipPosition.value = {
    left: `${rect.left + rect.width / 2}px`,
    top: `${rect.top - 8}px`
  }
  hoveredMilestone.value = `${itemId}-${stepIndex}`
}

// Get tooltip data for currently hovered milestone
function getMilestoneTooltipForHover() {
  if (!hoveredMilestone.value) {
    return { title: '', description: '', icon: '', color: '' }
  }

  const [itemId, stepIndex] = hoveredMilestone.value.split('-').map(Number)
  const item = pengajuanStore.pengajuanList.find(p => p.id === itemId)
  if (!item) return { title: '', description: '', icon: '', color: '' }

  const steps = getMilestoneSteps(item)
  const step = steps[stepIndex]
  if (!step) return { title: '', description: '', icon: '', color: '' }

  return getMilestoneTooltip(step, item)
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

// Handle cancel pengajuan - tarik kembali ke draft
async function handleCancel() {
  if (!cancelingId.value) return

  try {
    await pengajuanStore.cancelPengajuan(cancelingId.value)
    await loadStats() // Refresh data
    toast.success('Pengajuan berhasil ditarik kembali ke Draft')
    closeCancelModal()
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menarik pengajuan'
    toast.error(message)
  }
}

// Handle delete pengajuan - hapus permanen ke riwayat (dicabut)
async function handleDelete(id) {
  if (!confirm('Yakin ingin menghapus pengajuan ini? Data akan masuk ke Riwayat dan tidak dapat diedit kembali.')) return

  try {
    await pengajuanStore.deletePengajuan(id)
    await loadStats() // Refresh data
    toast.success('Pengajuan dihapus dan masuk ke Riwayat')
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
                      <!-- Surat button in mobile - opens modal -->
                      <button
                        v-if="hasSurat(item)"
                        @click.stop="openSuratModal(item)"
                        class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2 border-t border-secondary-100"
                      >
                        <i class="ri-file-text-line text-primary-600"></i> Surat
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
                      <button
                        v-if="canCancel(item)"
                        @click="openCancelModal(item.id)"
                        class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2"
                      >
                        <i class="ri-close-circle-line"></i> Cabut Berkas
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
                    <!-- Notification Badge -->
                    <span
                      v-if="getPengajuanNotificationCount(item.id) > 0"
                      class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-xs font-medium"
                    >
                      <i class="ri-notification-3-line"></i>
                      <span>{{ getPengajuanNotificationCount(item.id) }}</span>
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
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
                    <!-- Surat dropdown for desktop -->
                    <div v-if="hasSurat(item)" class="relative" :id="`surat-menu-${item.id}`">
                      <button
                        @click.stop="toggleSuratMenu(item.id)"
                        class="btn btn-secondary btn-sm"
                      >
                        <i class="ri-file-text-line mr-1"></i>
                        Surat
                        <i class="ri-arrow-down-s-line ml-1"></i>
                      </button>
                      <div
                        v-if="activeSuratMenu === item.id"
                        class="absolute right-0 top-full mt-1 w-56 bg-white rounded-lg shadow-lg border border-secondary-200 z-[100]"
                      >
                        <button
                          v-if="getSuratInfo(item).surat_izin"
                          @click="downloadSuratIzin(item)"
                          :disabled="isDownloading(item.id, 'izin')"
                          class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <i v-if="isDownloading(item.id, 'izin')" class="ri-loader-4-line animate-spin text-primary-600"></i>
                          <i v-else class="ri-download-line text-primary-600"></i>
                          <span>{{ isDownloading(item.id, 'izin') ? 'Mengunduh...' : 'Surat Izin Belajar (BKPSDM)' }}</span>
                        </button>
                        <button
                          v-if="getSuratInfo(item).surat_tugas_mandiri"
                          @click="downloadSuratTugasMandiri(item)"
                          :disabled="isDownloading(item.id, 'tugas_mandiri')"
                          class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <i v-if="isDownloading(item.id, 'tugas_mandiri')" class="ri-loader-4-line animate-spin text-primary-600"></i>
                          <i v-else class="ri-download-line text-primary-600"></i>
                          <span>{{ isDownloading(item.id, 'tugas_mandiri') ? 'Mengunduh...' : 'Surat Tugas Mandiri (Admin)' }}</span>
                        </button>
                        <button
                          v-if="getSuratInfo(item).surat_tugas_dinas"
                          @click="downloadSuratTugasDinas(item)"
                          :disabled="isDownloading(item.id, 'tugas_dinas')"
                          class="w-full text-left px-4 py-2 hover:bg-secondary-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <i v-if="isDownloading(item.id, 'tugas_dinas')" class="ri-loader-4-line animate-spin text-primary-600"></i>
                          <i v-else class="ri-download-line text-primary-600"></i>
                          <span>{{ isDownloading(item.id, 'tugas_dinas') ? 'Mengunduh...' : 'Surat Tugas Dinas (Kepala Dinas)' }}</span>
                        </button>
                      </div>
                    </div>
                    <button
                      v-if="canRestore(item)"
                      @click="handleRestore(item.id)"
                      class="btn btn-success btn-sm"
                    >
                      <i class="ri-refresh-line mr-1"></i>
                      Pulihkan
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

                <!-- Dots with Tooltip -->
                <div
                  v-for="(step, idx) in getMilestoneSteps(item)"
                  :key="idx"
                  class="relative z-10 flex flex-col items-center group"
                  @mouseenter="updateTooltipPosition($event, item.id, idx)"
                  @mouseleave="hoveredMilestone = null"
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

  <!-- Surat Selection Modal (Mobile) -->
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="showSuratModal"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center p-0 sm:p-4 bg-black/50"
        @click.self="closeSuratModal"
      >
        <div class="bg-white w-full sm:max-w-md sm:rounded-xl rounded-t-2xl shadow-xl max-h-[70vh] overflow-hidden flex flex-col">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold">Pilih Surat untuk Diunduh</h3>
            <button @click="closeSuratModal" class="btn btn-ghost btn-icon">
              <i class="ri-close-line text-xl"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="p-4 overflow-y-auto flex-1">
            <div v-if="selectedPengajuanForSurat" class="space-y-3">
              <p class="text-sm text-secondary-500 mb-4">
                Nomor: <span class="font-medium text-secondary-800">{{ selectedPengajuanForSurat.nomor_pengajuan || '-' }}</span>
              </p>

              <!-- Surat Izin Belajar -->
              <div
                v-if="getSuratInfo(selectedPengajuanForSurat).surat_izin"
                class="border border-secondary-200 rounded-xl overflow-hidden"
              >
                <button
                  @click="downloadSuratIzin(selectedPengajuanForSurat)"
                  :disabled="isDownloading(selectedPengajuanForSurat.id, 'izin')"
                  class="w-full px-4 py-3 flex items-center justify-between hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                      <i class="ri-file-text-line text-primary-600"></i>
                    </div>
                    <div class="text-left">
                      <p class="font-medium text-secondary-800">Surat Izin Belajar (BKPSDM)</p>
                      <p class="text-xs text-secondary-500">{{ getSuratInfo(selectedPengajuanForSurat).surat_izin.nomor_surat }}</p>
                    </div>
                  </div>
                  <i v-if="isDownloading(selectedPengajuanForSurat.id, 'izin')" class="ri-loader-4-line animate-spin text-xl text-primary-600"></i>
                  <i v-else class="ri-download-line text-xl text-secondary-400"></i>
                </button>
              </div>

              <!-- Surat Tugas Mandiri -->
              <div
                v-if="getSuratInfo(selectedPengajuanForSurat).surat_tugas_mandiri"
                class="border border-secondary-200 rounded-xl overflow-hidden"
              >
                <button
                  @click="downloadSuratTugasMandiri(selectedPengajuanForSurat)"
                  :disabled="isDownloading(selectedPengajuanForSurat.id, 'tugas_mandiri')"
                  class="w-full px-4 py-3 flex items-center justify-between hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <i class="ri-file-text-line text-blue-600"></i>
                    </div>
                    <div class="text-left">
                      <p class="font-medium text-secondary-800">Surat Tugas Mandiri (Admin)</p>
                      <p class="text-xs text-secondary-500">{{ getSuratInfo(selectedPengajuanForSurat).surat_tugas_mandiri.nomor_surat }}</p>
                    </div>
                  </div>
                  <i v-if="isDownloading(selectedPengajuanForSurat.id, 'tugas_mandiri')" class="ri-loader-4-line animate-spin text-xl text-primary-600"></i>
                  <i v-else class="ri-download-line text-xl text-secondary-400"></i>
                </button>
              </div>

              <!-- Surat Tugas Dinas -->
              <div
                v-if="getSuratInfo(selectedPengajuanForSurat).surat_tugas_dinas"
                class="border border-secondary-200 rounded-xl overflow-hidden"
              >
                <button
                  @click="downloadSuratTugasDinas(selectedPengajuanForSurat)"
                  :disabled="isDownloading(selectedPengajuanForSurat.id, 'tugas_dinas')"
                  class="w-full px-4 py-3 flex items-center justify-between hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                      <i class="ri-file-text-line text-green-600"></i>
                    </div>
                    <div class="text-left">
                      <p class="font-medium text-secondary-800">Surat Tugas Dinas (Kepala Dinas)</p>
                      <p class="text-xs text-secondary-500">{{ getSuratInfo(selectedPengajuanForSurat).surat_tugas_dinas.nomor_surat }}</p>
                    </div>
                  </div>
                  <i v-if="isDownloading(selectedPengajuanForSurat.id, 'tugas_dinas')" class="ri-loader-4-line animate-spin text-xl text-primary-600"></i>
                  <i v-else class="ri-download-line text-xl text-secondary-400"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="p-4 border-t bg-secondary-50">
            <button @click="closeSuratModal" class="btn btn-ghost w-full">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Fixed Milestone Tooltip (Teleport to body for z-index fix) -->
  <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="hoveredMilestone"
          class="fixed z-[10000] pointer-events-none"
          :style="{ left: tooltipPosition.left, top: tooltipPosition.top, transform: 'translate(-50%, -100%)' }"
        >
          <div class="bg-white rounded-lg shadow-xl border border-secondary-200 p-3 text-sm w-48 sm:w-56">
            <div class="flex items-start gap-2">
              <i :class="[getMilestoneTooltipForHover().icon, getMilestoneTooltipForHover().color, 'mt-0.5 flex-shrink-0']"></i>
              <div>
                <p class="font-medium text-secondary-800">{{ getMilestoneTooltipForHover().title }}</p>
                <p class="text-xs text-secondary-500 mt-0.5">{{ getMilestoneTooltipForHover().description }}</p>
              </div>
            </div>
            <!-- Arrow pointing down -->
            <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-r border-b transform rotate-45"></div>
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

/* Mobile slide-up animation for surat modal */
@media (max-width: 640px) {
  .modal-enter-active {
    transition: transform 0.3s ease-out;
  }
  .modal-leave-active {
    transition: transform 0.2s ease-in;
  }
  .modal-enter-from {
    transform: translateY(100%);
  }
  .modal-leave-to {
    transform: translateY(100%);
  }
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(4px);
}
</style>
