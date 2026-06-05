<script setup>
import { ref, onMounted, computed } from 'vue'
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
const verificationInfoMap = ref(new Map()) // Store verification info for each pengajuan
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)
const activeDropdown = ref(null)

// Hitung statistik verifikasi (hanya yang belum selesai diverifikasi)
const stats = computed(() => {
  return {
    total: pengajuanList.value.length,
    pendingAdmin: pengajuanList.value.filter(p => p.status === 'pending_admin').length,
    draft: pengajuanList.value.filter(p => p.status === 'draft').length,
    ditolak: pengajuanList.value.filter(p => p.status === 'ditolak').length,
  }
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
    // Ambil semua pengajuan yang perlu verifikasi admin
    // Filter akan dilakukan di frontend untuk hanya menampilkan yang belum selesai diverifikasi
    const data = await pengajuanStore.fetchPengajuan()
    // Filter: Hanya tampilkan yang belum selesai diverifikasi (bukan verified, signed, selesai, completed)
    // Data yang sudah selesai diverifikasi ada di menu Riwayat Verifikasi
    pengajuanList.value = (data || []).filter(p =>
      !['verified', 'signed', 'selesai', 'completed'].includes(p.status)
    )

    console.log('Admin pengajuan loaded:', pengajuanList.value.length, 'items')

    // Load verification info for each pengajuan
    await loadVerificationInfo()
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

async function loadVerificationInfo() {
  // Load verification info for each pengajuan in parallel
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

// Dapatkan informasi siapa yang perlu memverifikasi (dari API)
function getVerifierInfo(pengajuan) {
  const verificationInfo = verificationInfoMap.value.get(pengajuan.id)

  if (!verificationInfo) {
    // Fallback to simple logic if API data not loaded yet
    return getFallbackVerifierInfo(pengajuan)
  }

  // Find current verifier in chain
  const currentChain = verificationInfo.verification_chain?.find(c => c.status === 'current')
  const pendingChain = verificationInfo.verification_chain?.find(c => c.status === 'pending')

  if (pengajuan.status === 'pending_admin') {
    return {
      label: 'Perlu Verifikasi',
      name: 'Admin BKPSDM',
      jabatan: 'Verifikasi Dokumen',
      nip: '-',
      icon: 'ri-admin-line',
      color: 'blue'
    }
  } else if (pengajuan.status === 'verified') {
    return {
      label: 'Selanjutnya',
      name: 'Kepala Dinas',
      jabatan: 'Buat Surat Tugas Belajar',
      nip: '-',
      icon: 'ri-file-list-line',
      color: 'purple'
    }
  } else if (pengajuan.status === 'surat_dinas') {
    return {
      label: 'Selanjutnya',
      name: verificationInfo.final_signer?.nama || 'Admin BKPSDM',
      jabatan: 'Buat Surat Izin Belajar',
      nip: '-',
      icon: 'ri-file-text-line',
      color: 'blue'
    }
  }

  return null
}

// Fallback verifier info when API data not loaded
function getFallbackVerifierInfo(pengajuan) {
  if (pengajuan.status === 'pending_admin') {
    return {
      label: 'Perlu Verifikasi',
      name: 'Admin BKPSDM',
      jabatan: 'Verifikasi Dokumen',
      nip: '-',
      icon: 'ri-admin-line',
      color: 'blue'
    }
  } else if (pengajuan.status === 'verified') {
    return {
      label: 'Selanjutnya',
      name: 'Kepala Dinas',
      jabatan: 'Buat Surat Tugas Belajar',
      nip: '-',
      icon: 'ri-file-list-line',
      color: 'purple'
    }
  } else if (pengajuan.status === 'surat_dinas') {
    return {
      label: 'Selanjutnya',
      name: 'Admin BKPSDM',
      jabatan: 'Buat Surat Izin Belajar',
      nip: '-',
      icon: 'ri-file-text-line',
      color: 'blue'
    }
  }

  return null
}

// Get final signer info from verification data
function getFinalSigner(pengajuan) {
  const verificationInfo = verificationInfoMap.value.get(pengajuan.id)
  return verificationInfo?.final_signer || {
    nama: 'Kepala BKPSDM',
    jabatan: 'Penandatangan Surat',
    level: 'kepala_bkpsdm'
  }
}

// Milestone dot-line functions (Updated for Simplified Flow - 4 Steps)
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

  // Step 3: TTE (Kepala BKPSDM)
  steps.push({
    label: 'TTE',
    status: ['selesai', 'completed'].includes(status) ? 'completed' :
              ['signed'].includes(status) ? 'current' : 'pending',
  })

  // Step 4: Selesai
  steps.push({
    label: 'Selesai',
    status: ['selesai', 'completed'].includes(status) ? 'completed' :
              ['signed'].includes(status) ? 'current' : 'pending',
  })

  return steps
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
    <Breadcrumb />
    <PageHeader
      title="Verifikasi Pengajuan"
      subtitle="Daftar pengajuan yang menunggu verifikasi"
    />

    <!-- Compact Stats Row -->
    <div class="flex flex-wrap items-center gap-4 mb-5 animate-slide-up">
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-white rounded-lg border border-secondary-200">
        <i class="ri-file-list-3-line text-secondary-500"></i>
        <span class="text-sm text-secondary-500">Total:</span>
        <span class="font-semibold text-lg text-secondary-800">{{ stats.total }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-blue-50 rounded-lg border border-blue-200">
        <i class="ri-admin-line text-blue-500"></i>
        <span class="text-sm text-blue-600">Verifikasi:</span>
        <span class="font-semibold text-lg text-blue-700">{{ stats.pendingAdmin }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200">
        <i class="ri-draft-line text-gray-500"></i>
        <span class="text-sm text-gray-600">Draft:</span>
        <span class="font-semibold text-lg text-gray-700">{{ stats.draft }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-red-50 rounded-lg border border-red-200">
        <i class="ri-close-circle-line text-red-500"></i>
        <span class="text-sm text-red-600">Ditolak:</span>
        <span class="font-semibold text-lg text-red-700">{{ stats.ditolak }}</span>
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
    <div v-else-if="pengajuanList.length === 0" class="text-center py-16 animate-slide-up">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-secondary-100 to-secondary-50 flex items-center justify-center mx-auto mb-4 border border-secondary-200">
        <i class="ri-inbox-archive-line text-4xl text-secondary-400"></i>
      </div>
      <h3 class="text-base font-semibold text-secondary-800 mb-1">Tidak Ada Pengajuan</h3>
      <p class="text-sm text-secondary-500">Belum ada pengajuan yang menunggu verifikasi</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3 animate-slide-up">
      <div
        v-for="item in pengajuanList"
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
                <span v-if="item.user?.atasan" class="text-xs text-secondary-500 bg-secondary-100 px-2 py-1 rounded-md">
                  <i class="ri-user-star-line mr-0.5"></i>
                  {{ item.user.atasan.name }}
                </span>
                <span v-else class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded-md">
                  <i class="ri-error-warning-line mr-0.5"></i>
                  <span class="hidden sm:inline">Atasan belum ditetapkan</span>
                  <span class="sm:hidden">Atasan -</span>
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
                class="btn btn-sm"
                :class="item.status === 'pending_admin' ? 'btn-primary' : 'btn-secondary'"
              >
                <i :class="item.status === 'pending_admin' ? 'ri-checkbox-circle-line' : 'ri-eye-line'"></i>
                <span class="ml-1">{{ item.status === 'pending_admin' ? 'Verifikasi' : 'Detail' }}</span>
              </button>
              <button
                v-if="item.status !== 'disetujui' && item.status !== 'ditolak'"
                @click="openSendMessageModal(item)"
                class="btn btn-ghost btn-sm"
                title="Kirim Pesan"
              >
                <i class="ri-message-3-line"></i>
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
                class="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-secondary-200 py-1 z-20"
                @click.stop
              >
                <button
                  @click="openVerificationPage(item.id); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i :class="item.status === 'pending_admin' ? 'ri-checkbox-circle-line text-primary-600' : 'ri-eye-line text-secondary-600'"></i>
                  <span>{{ item.status === 'pending_admin' ? 'Verifikasi' : 'Detail' }}</span>
                </button>
                <button
                  v-if="item.status !== 'disetujui' && item.status !== 'ditolak'"
                  @click="openSendMessageModal(item); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i class="ri-message-3-line text-secondary-600"></i>
                  <span>Kirim Pesan</span>
                </button>
                <button
                  @click="openSendMessageModal(item); closeAllMenus()"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                >
                  <i class="ri-message-3-line text-secondary-600"></i>
                  <span>Kirim Pesan</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-secondary-100 my-3"></div>

          <!-- Verifier Info -->
          <div v-if="getVerifierInfo(item)" class="flex items-center gap-2 text-sm px-3 py-2 rounded-md" :class="`bg-${getVerifierInfo(item).color}-50`">
            <i :class="[getVerifierInfo(item).icon, `text-${getVerifierInfo(item).color}-600`]"></i>
            <span :class="`text-${getVerifierInfo(item).color}-700`">{{ getVerifierInfo(item).label }}:</span>
            <span class="font-medium" :class="`text-${getVerifierInfo(item).color}-900`">{{ getVerifierInfo(item).name }}</span>
            <span class="text-secondary-300">•</span>
            <span :class="`text-${getVerifierInfo(item).color}-600`">{{ getVerifierInfo(item).jabatan }}</span>
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

          <!-- Collapsible Documents Section - HIDDEN -->
          <!--
          <div class="mt-3">
            <button
              class="flex items-center justify-between w-full text-left hover:bg-secondary-50 px-2 py-2 -mx-2 rounded-lg transition-colors group"
              @click="toggleDocuments(item.id)"
            >
              <span class="text-sm font-medium text-secondary-600 flex items-center gap-1.5">
                <i class="ri-file-list-3-line text-primary-500"></i>
                Dokumen Lampiran
                <span class="text-xs text-secondary-400 font-normal">
                  ({{ getDocumentStatusCount(item.id).total }} file)
                </span>
              </span>
              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-xs">
                  <span class="badge badge-success py-0.5 px-1.5">{{ getDocumentStatusCount(item.id).lengkap }}</span>
                  <span class="badge badge-secondary py-0.5 px-1.5">{{ getDocumentStatusCount(item.id).belum }}</span>
                  <span v-if="getDocumentStatusCount(item.id).tidak_lengkap > 0" class="badge badge-danger py-0.5 px-1.5">{{ getDocumentStatusCount(item.id).tidak_lengkap }}</span>
                </span>
                <i
                  class="text-secondary-400 group-hover:text-secondary-500 transition-transform duration-200"
                  :class="isDocumentsCollapsed(item.id) ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"
                ></i>
              </div>
            </button>
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div v-show="!isDocumentsCollapsed(item.id)" class="mt-2 overflow-hidden">
                <div v-if="getDocuments(item.id).length === 0" class="text-center py-4 text-secondary-500 text-sm">
                  <i class="ri-inbox-line text-xl"></i>
                  <p class="mt-1">Tidak ada dokumen</p>
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="doc in getDocuments(item.id)"
                    :key="doc.id"
                    class="flex items-center gap-2 p-2 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors"
                  >
                    <div class="w-8 h-8 rounded bg-white flex items-center justify-center shrink-0">
                      <i :class="[getDocumentIcon(doc.file_type), 'text-lg text-secondary-500']"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-secondary-700 truncate">{{ documentTypes[doc.jenis_dokumen]?.label || doc.jenis_dokumen }}</p>
                      <p class="text-xs text-secondary-400 truncate">{{ doc.file_name }}</p>
                    </div>
                    <span class="badge text-xs py-0.5" :class="getDocumentStatusClass(doc.status_verifikasi)">
                      {{ getDocumentStatusLabel(doc.status_verifikasi) }}
                    </span>
                    <button
                      @click="previewDocument(doc)"
                      class="btn btn-ghost btn-sm p-1 h-7 w-7 text-primary-600 hover:text-primary-700 hover:bg-primary-50"
                      title="Preview Dokumen"
                    >
                      <i class="ri-eye-line"></i>
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
          -->

          <!-- Collapsible Milestone - HIDDEN -->
          <!--
          <div class="mt-3">
            <button
              class="flex items-center justify-between w-full text-left hover:bg-secondary-50 px-2 py-2 -mx-2 rounded-lg transition-colors group"
              @click="toggleMilestone(item.id)"
            >
              <span class="text-sm font-medium text-secondary-600 flex items-center gap-1.5">
                <i class="ri-route-line text-primary-500"></i>
                Progress Pengajuan
              </span>
              <i
                class="text-secondary-400 group-hover:text-secondary-500 transition-transform duration-200"
                :class="isMilestoneCollapsed(item.id) ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"
              ></i>
            </button>
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div v-show="!isMilestoneCollapsed(item.id)" class="bg-secondary-50 rounded-lg p-3 mt-2 overflow-hidden">
                <PengajuanMilestone :pengajuan-id="item.id" />
              </div>
            </Transition>
          </div>
          -->
        </div>
      </div>
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
