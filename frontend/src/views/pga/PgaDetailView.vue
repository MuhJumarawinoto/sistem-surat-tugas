<script setup>
import { ref, onMounted, computed, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePgaStore } from '@/stores/pga'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'

const router = useRouter()
const route = useRoute()
const pgaStore = usePgaStore()
const authStore = useAuthStore()
const toast = useToastStore()

const pga = ref(null)
const loading = ref(false)
const loadingDocs = ref(false)

// Document preview modal
const showDocPreviewModal = ref(false)
const previewDocSrc = ref('')
const previewDocAlt = ref('')
const previewDocType = ref('')

const headerActions = computed(() => {
  const actions = []

  if (!pga.value) return actions

  // Add status badge
  actions.push({
    label: getStatusLabel(pga.value.status),
    icon: getStatusIcon(pga.value.status),
    variant: getStatusBadge(pga.value.status),
    isBadge: true,
  })

  // Add edit button for draft/ditolak
  const canEdit = pga.value.status === 'draft' || pga.value.status === 'ditolak'
  if (canEdit) {
    actions.push({
      label: 'Edit',
      icon: 'ri-edit-line',
      to: `/pga/${route.params.id}/edit`,
      variant: 'btn-secondary',
    })
  }

  return actions
})

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    approved_admin: 'Menunggu Persetujuan',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-secondary',
    approved_admin: 'badge-warning',
    selesai: 'badge-success',
    ditolak: 'badge-danger',
  }
  return badges[status] || 'badge-default'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    approved_admin: 'ri-time-line',
    selesai: 'ri-checkbox-circle-line',
    ditolak: 'ri-close-line',
  }
  return icons[status] || 'ri-file-line'
}

// Get milestone steps for PGA
function getMilestoneSteps(pga) {
  const status = pga.status
  const steps = []

  // PGA Flow: Draft → Verifikasi → Selesai (3 steps)

  // Step 1: Draft
  if (status === 'draft') {
    steps.push({ label: 'Draft', status: 'current' })
  } else if (['approved_admin', 'selesai', 'ditolak'].includes(status)) {
    steps.push({ label: 'Draft', status: 'completed' })
  } else {
    steps.push({ label: 'Draft', status: 'pending' })
  }

  // Step 2: Verifikasi
  if (status === 'approved_admin') {
    steps.push({ label: 'Verifikasi', status: 'current' })
  } else if (status === 'selesai') {
    steps.push({ label: 'Verifikasi', status: 'completed' })
  } else if (status === 'ditolak') {
    steps.push({ label: 'Verifikasi', status: 'rejected' })
  } else {
    steps.push({ label: 'Verifikasi', status: 'pending' })
  }

  // Step 3: Selesai
  if (status === 'selesai') {
    steps.push({ label: 'Selesai', status: 'completed' })
  } else if (status === 'ditolak') {
    steps.push({ label: 'Ditolak', status: 'rejected' })
  } else {
    steps.push({ label: 'Selesai', status: 'pending' })
  }

  return steps
}

function getStepClass(step) {
  if (step.status === 'completed') return 'bg-green-500'
  if (step.status === 'current') return 'bg-blue-500 animate-pulse'
  if (step.status === 'rejected') return 'bg-red-500'
  return 'bg-gray-300'
}

function getProgressLineClass(status) {
  // PGA Flow: 3 Steps - Draft → Verifikasi → Selesai
  if (status === 'draft') {
    return 'bg-blue-500'
  }
  if (status === 'approved_admin') {
    return 'bg-blue-500'
  }
  if (status === 'selesai') {
    return 'bg-green-500'
  }
  if (status === 'ditolak') {
    return 'bg-red-500'
  }
  return 'bg-gray-200'
}

function getProgressLineWidth(status) {
  // Calculate width percentage for progress line
  if (status === 'draft') {
    return '10%'  // Small progress at first step
  }
  if (status === 'approved_admin') {
    return '50%'  // Middle of flow
  }
  if (status === 'selesai') {
    return '100%'  // Complete
  }
  if (status === 'ditolak') {
    return '50%'  // Rejected at verifikasi
  }
  return '0%'
}

function getStepTooltip(step) {
  if (step.status === 'completed') return `${step.label}: Selesai`
  if (step.status === 'current') return `${step.label}: Sedang diproses`
  if (step.status === 'rejected') return `${step.label}: Ditolak`
  return `${step.label}: Belum diproses`
}

function getStepLabelClass(step) {
  if (step.status === 'completed') return 'text-green-600'
  if (step.status === 'current') return 'text-blue-600 font-semibold'
  if (step.status === 'rejected') return 'text-red-600'
  return 'text-gray-600'
}

async function loadPga() {
  loading.value = true
  try {
    const id = route.params.id
    const data = await pgaStore.fetchPgaDetail(id)
    pga.value = data
  } catch (error) {
    toast.error('Gagal memuat detail PGA')
    console.error('Failed to load PGA detail:', error)
    router.push('/pga')
  } finally {
    loading.value = false
  }
}

// Document functions
function openDocumentPreview(doc) {
  const backendUrl = import.meta.env.VITE_API_URL
    ? import.meta.env.VITE_API_URL.replace('/api', '')
    : 'http://localhost:8000'

  previewDocSrc.value = `${backendUrl}/storage/${doc.file_path}`
  previewDocAlt.value = doc.file_name || 'Dokumen'
  previewDocType.value = doc.file_type?.includes('pdf') ? 'pdf' : 'image'
  showDocPreviewModal.value = true
}

function closeDocumentPreview() {
  showDocPreviewModal.value = false
  previewDocSrc.value = ''
  previewDocAlt.value = ''
  previewDocType.value = ''
}

function getDocIcon(doc) {
  if (doc.file_type?.includes('pdf')) return 'ri-file-pdf-line text-red-500'
  if (doc.file_type?.startsWith('image/')) return 'ri-image-line text-blue-500'
  return 'ri-file-text-line text-secondary-400'
}

function getDocStatusLabel(status) {
  const labels = {
    lengkap: 'Lengkap',
    tidak_lengkap: 'Tidak Lengkap',
    pending: 'Menunggu Verifikasi',
  }
  return labels[status] || 'Menunggu Verifikasi'
}

onMounted(() => {
  loadPga()
})
</script>

<template>
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat detail..." />
    </div>

    <div v-else-if="pga" class="space-y-6 animate-fade-in">
      <Breadcrumb />

      <PageHeader
        :title="`Detail PGA - ${pga.nomor_pengajuan || '-'}`"
        subtitle="Detail pengajuan pencantuman gelar akademik"
        :actions="headerActions"
      />

      <!-- Main Card Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informasi Pegawai -->
        <div class="card animate-slide-up">
          <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
              <i class="ri-user-line text-lg text-primary-600"></i>
              Informasi Pegawai
            </h3>
          </div>
          <div class="card-body">
            <dl class="space-y-3 text-sm">
              <div class="flex justify-between">
                <dt class="text-secondary-500">Nama</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.user?.name || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">NIP</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.user?.nip || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Jabatan</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.user?.jabatan || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Unit Kerja</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.user?.unit_kerja?.nama || '-' }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Informasi Pendidikan -->
        <div class="card animate-slide-up">
          <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
              <i class="ri-graduation-cap-line text-lg text-primary-600"></i>
              Informasi Pendidikan
            </h3>
          </div>
          <div class="card-body">
            <dl class="space-y-3 text-sm">
              <div class="flex justify-between">
                <dt class="text-secondary-500">Jenjang</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.jenjang_pendidikan?.nama || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Gelar Akademik</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.gelar_akademik || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Program Studi</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.nama_prodi || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Perguruan Tinggi</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.perguruan_tinggi || '-' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-secondary-500">Lokasi</dt>
                <dd class="text-secondary-800 font-medium">{{ pga.lokasi_pt || '-' }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Informasi Ijazah -->
      <div class="card animate-slide-up">
        <div class="card-header">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-book-open-line text-lg text-primary-600"></i>
            Informasi Ijazah
          </h3>
        </div>
        <div class="card-body">
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-secondary-500">Nomor Ijazah</dt>
              <dd class="text-secondary-800 font-medium">{{ pga.nomor_ijazah || '-' }}</dd>
            </div>
            <div>
              <dt class="text-secondary-500">Tanggal Ijazah</dt>
              <dd class="text-secondary-800 font-medium">
                {{ pga.tanggal_ijazah ? new Date(pga.tanggal_ijazah).toLocaleDateString('id-ID') : '-' }}
              </dd>
            </div>
            <div>
              <dt class="text-secondary-500">Tahun Lulus</dt>
              <dd class="text-secondary-800 font-medium">{{ pga.tahun_lulus || '-' }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Dokumen -->
      <div class="card animate-slide-up">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <h3 class="card-title flex items-center gap-2">
              <i class="ri-file-text-line text-lg text-primary-600"></i>
              Dokumen
            </h3>
            <span class="badge badge-primary">{{ pga.dokumen?.length || 0 }}/3</span>
          </div>
        </div>
        <div class="card-body">
          <div v-if="pga.dokumen && pga.dokumen.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              v-for="doc in pga.dokumen"
              :key="doc.id"
              class="p-4 border rounded-lg hover:bg-secondary-50 transition-colors"
              :class="doc.catatan ? 'border-amber-300 bg-amber-50/30' : ''"
            >
              <div class="flex items-start gap-3">
                <i :class="getDocIcon(doc)" class="text-2xl flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-secondary-800 truncate">{{ doc.file_name }}</p>
                  <p class="text-xs text-secondary-500 mt-1">
                    {{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB
                  </p>
                  <span class="badge badge-xs mt-2" :class="doc.status_verifikasi === 'lengkap' ? 'badge-success' : 'badge-warning'">
                    {{ getDocStatusLabel(doc.status_verifikasi) }}
                  </span>
                </div>
              </div>
              <button
                @click="openDocumentPreview(doc)"
                class="mt-3 w-full btn btn-sm btn-outline justify-center"
              >
                <i class="ri-eye-line mr-1"></i>
                Preview
              </button>
              <!-- Catatan Verifikasi -->
              <div v-if="doc.catatan" class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-900">
                <i class="ri-chat-3-line mr-1"></i>
                {{ doc.catatan }}
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6 text-secondary-500">
            <i class="ri-file-upload-line text-3xl mb-2"></i>
            <p>Belum ada dokumen</p>
          </div>
        </div>
      </div>

      <!-- Catatan Penolakan -->
      <div v-if="pga.catatan_tolak" class="card border-l-4 border-l-danger animate-slide-up">
        <div class="card-body bg-red-50">
          <h3 class="text-sm font-semibold text-red-900 flex items-center gap-2 mb-2">
            <i class="ri-close-circle-line"></i>
            Catatan Penolakan
          </h3>
          <p class="text-sm text-red-700">{{ pga.catatan_tolak }}</p>
        </div>
      </div>

      <!-- Progress Milestone -->
      <div class="card animate-slide-up">
        <div class="card-header">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-route-line text-lg text-primary-600"></i>
            Progress Pengajuan
          </h3>
        </div>
        <div class="card-body">
          <div class="flex items-center justify-between relative px-4">
            <!-- Progress Line Background -->
            <div class="absolute top-5 left-4 right-4 h-1 bg-gray-200 -z-0 rounded-full"></div>
            <!-- Progress Line Active -->
            <div
              class="absolute top-5 left-4 h-1 -z-0 transition-all duration-300 rounded-full"
              :class="getProgressLineClass(pga.status)"
              :style="{ width: getProgressLineWidth(pga.status) }"
            ></div>

            <!-- Milestone Dots -->
            <div
              v-for="(step, index) in getMilestoneSteps(pga)"
              :key="index"
              class="relative z-10 flex flex-col items-center"
            >
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold transition-all duration-300 shadow-sm"
                :class="getStepClass(step)"
                :title="getStepTooltip(step)"
              >
                <i v-if="step.status === 'completed'" class="ri-check-line text-lg"></i>
                <i v-else-if="step.status === 'rejected'" class="ri-close-line text-lg"></i>
                <span v-else>{{ index + 1 }}</span>
              </div>
              <p
                class="text-xs mt-2 font-medium"
                :class="getStepLabelClass(step)"
              >{{ step.label }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="flex justify-center">
        <button @click="router.push('/pga')" class="btn btn-secondary">
          <i class="ri-arrow-left-line mr-2"></i>
          Kembali ke Daftar
        </button>
      </div>
    </div>

    <!-- Document Preview Modal -->
    <DocumentPreviewModal
      :show="showDocPreviewModal"
      :src="previewDocSrc"
      :alt="previewDocAlt"
      :file-type="previewDocType"
      @close="closeDocumentPreview"
    />
  </MainLayout>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.animate-slide-up {
  animation: slide-up 0.4s ease-out;
}
</style>
