<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const verificationInfoMap = ref(new Map()) // Store verification info for each pengajuan
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)

// Hitung statistik verifikasi
const stats = computed(() => {
  return {
    total: pengajuanList.value.length,
    pendingAtasan: pengajuanList.value.filter(p => p.status === 'pending_atasan').length,
    pendingAdmin: pengajuanList.value.filter(p => p.status === 'pending_admin').length,
    verified: pengajuanList.value.filter(p => p.status === 'verified').length,
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

async function loadPengajuan() {
  loading.value = true
  try {
    // Ambil semua pengajuan yang perlu verifikasi admin (pending_admin dan pending_atasan)
    // Filter akan dilakukan di backend berdasarkan role admin
    const response = await pengajuanStore.fetchPengajuan()
    pengajuanList.value = response.data || []

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
    'pending_atasan': 'badge-warning',
    'pending_admin': 'badge-primary',
    'verified': 'badge-info',
    'disetujui': 'badge-success',
    'ditolak': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

function getStatusLabel(status) {
  const labels = {
    'pending_atasan': 'Menunggu Atasan',
    'pending_admin': 'Menunggu Verifikasi',
    'verified': 'Terverifikasi',
    'disetujui': 'Disetujui',
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

  if (pengajuan.status === 'pending_atasan') {
    const atasanChain = verificationInfo.verification_chain?.find(c => c.level === 'atasan_langsung')
    return {
      label: 'Perlu Verifikasi',
      name: atasanChain?.nama || 'Atasan Langsung',
      jabatan: atasanChain?.jabatan || 'Belum ditetapkan',
      nip: atasanChain?.nip || '-',
      icon: 'ri-user-star-line',
      color: atasanChain?.nama === 'Belum ditetapkan' ? 'gray' : 'orange'
    }
  } else if (pengajuan.status === 'pending_admin') {
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
      name: verificationInfo.final_signer?.nama || 'Kepala BKPSDM',
      jabatan: verificationInfo.final_signer?.jabatan || 'Penandatangan Surat',
      nip: '-',
      icon: 'ri-quill-pen-line',
      color: 'green'
    }
  }

  return null
}

// Fallback verifier info when API data not loaded
function getFallbackVerifierInfo(pengajuan) {
  const user = pengajuan.user
  const atasan = user?.atasan

  if (pengajuan.status === 'pending_atasan') {
    if (atasan) {
      return {
        label: 'Perlu Verifikasi',
        name: atasan.name,
        jabatan: atasan.jabatan || atasan.role?.name || 'Atasan',
        nip: atasan.nip || '-',
        icon: 'ri-user-star-line',
        color: 'orange'
      }
    }
    return {
      label: 'Perlu Verifikasi',
      name: 'Atasan Langsung',
      jabatan: 'Belum ditetapkan',
      nip: '-',
      icon: 'ri-error-warning-line',
      color: 'gray'
    }
  } else if (pengajuan.status === 'pending_admin') {
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
      name: 'Kepala BKPSDM',
      jabatan: 'Penandatanganan Surat',
      nip: '-',
      icon: 'ri-quill-pen-line',
      color: 'green'
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
</script>

<template>
  <MainLayout>
    <div class="mb-6 animate-fade-in">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">Verifikasi Pengajuan</h2>
          <p class="text-secondary-500 mt-1">Daftar pengajuan yang menunggu verifikasi</p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 animate-slide-up">
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center">
              <i class="ri-file-list-3-line text-secondary-600"></i>
            </div>
            <div>
              <p class="text-2xl font-bold text-secondary-800">{{ stats.total }}</p>
              <p class="text-xs text-secondary-500">Total Pengajuan</p>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
              <i class="ri-user-star-line text-orange-600"></i>
            </div>
            <div>
              <p class="text-2xl font-bold text-orange-600">{{ stats.pendingAtasan }}</p>
              <p class="text-xs text-secondary-500">Menunggu Atasan</p>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
              <i class="ri-admin-line text-blue-600"></i>
            </div>
            <div>
              <p class="text-2xl font-bold text-blue-600">{{ stats.pendingAdmin }}</p>
              <p class="text-xs text-secondary-500">Menunggu Admin</p>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
              <i class="ri-checkbox-circle-line text-green-600"></i>
            </div>
            <div>
              <p class="text-2xl font-bold text-green-600">{{ stats.verified }}</p>
              <p class="text-xs text-secondary-500">Terverifikasi</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card animate-slide-up">
          <div class="card-body">
            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat..." />
            </div>

            <div v-else-if="pengajuanList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500">Tidak ada pengajuan yang menunggu verifikasi</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="item in pengajuanList" :key="item.id" class="p-4 border border-secondary-200 rounded-xl hover:border-primary-300 hover:shadow-sm transition-all">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                      <p class="text-base font-semibold text-secondary-800">{{ item.nomor_pengajuan }}</p>
                      <span class="badge" :class="getStatusBadgeClass(item.status)">
                        {{ getStatusLabel(item.status) }}
                      </span>
                      <span v-if="item.user?.atasan" class="text-xs text-secondary-500 bg-secondary-100 px-2 py-0.5 rounded">
                        <i class="ri-user-star-line mr-1"></i>
                        Atasan: {{ item.user.atasan.name }}
                      </span>
                      <span v-else class="text-xs text-orange-600 bg-orange-50 px-2 py-0.5 rounded">
                        <i class="ri-error-warning-line mr-1"></i>
                        Atasan belum ditetapkan
                      </span>
                    </div>
                    <div class="flex flex-col gap-1">
                      <p class="text-sm font-medium text-secondary-800">{{ item.user?.name }}</p>
                      <div class="flex items-center gap-3 text-xs text-secondary-500">
                        <span><i class="ri-briefcase-line mr-1"></i>{{ item.user?.jabatan || '-' }}</span>
                        <span><i class="ri-medal-line mr-1"></i>{{ item.user?.pangkat_gol || '-' }}</span>
                        <span><i class="ri-building-line mr-1"></i>{{ item.user?.unit_kerja?.nama || item.user?.unit_kerja }}</span>
                      </div>
                    </div>
                    <p class="text-sm text-secondary-600 mt-1">
                      <i class="ri-graduation-cap-line mr-1"></i>{{ item.nama_prodi }} - {{ item.perguruan_tinggi }}
                    </p>

                    <!-- Verifier Info -->
                    <div v-if="getVerifierInfo(item)" class="mt-3 p-2 rounded-lg" :class="`bg-${getVerifierInfo(item).color}-50 border border-${getVerifierInfo(item).color}-200`">
                      <div class="flex items-center gap-2">
                        <i :class="[getVerifierInfo(item).icon, `text-${getVerifierInfo(item).color}-600`]"></i>
                        <div class="flex-1">
                          <p class="text-xs font-medium" :class="`text-${getVerifierInfo(item).color}-700`">{{ getVerifierInfo(item).label }}</p>
                          <p class="text-sm font-semibold" :class="`text-${getVerifierInfo(item).color}-900`">{{ getVerifierInfo(item).name }}</p>
                        </div>
                        <div class="text-right">
                          <p class="text-xs" :class="`text-${getVerifierInfo(item).color}-600`">{{ getVerifierInfo(item).jabatan }}</p>
                          <p class="text-xs text-secondary-500">{{ getVerifierInfo(item).nip !== '-' ? 'NIP: ' + getVerifierInfo(item).nip : '' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="openSendMessageModal(item)"
                      class="btn btn-ghost btn-sm"
                      title="Kirim Pesan"
                    >
                      <i class="ri-message-3-line text-lg"></i>
                    </button>
                    <!-- Show verify button for all non-completed statuses -->
                    <router-link v-if="!['disetujui', 'ditolak'].includes(item.status)" :to="`/admin/surat/${item.id}`" class="btn btn-primary btn-sm">
                      <i class="ri-eye-line mr-1"></i>
                      {{ item.status === 'pending_admin' ? 'Verifikasi' : 'Detail' }}
                    </router-link>
                    <span v-else class="text-xs text-red-500 italic">{{ getStatusLabel(item.status) }}</span>
                  </div>
                </div>
              </div>
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
