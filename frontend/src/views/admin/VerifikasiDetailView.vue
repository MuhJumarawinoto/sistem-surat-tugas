<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const submitting = ref(false)
const pengajuan = ref(null)
const verificationInfo = ref(null)
const dokumenList = ref([])
const documentChecks = ref({})
const documentNotes = ref({})
const showDocumentPreview = ref(false)
const selectedDocument = ref(null)

const rejectModal = ref(false)
const rejectReason = ref('')

const documentTypes = {
  sk_pangkat: { label: 'SK Pangkat Terakhir', required: true },
  sk_cpns: { label: 'SK CPNS', required: true },
  skp: { label: 'SKP 2 Tahun Terakhir', required: true },
  surat_lulus: { label: 'Surat Keterangan Lulus/Diterima', required: true },
  jadwal: { label: 'Jadwal Perkuliahan', required: true },
  akreditasi: { label: 'Sertifikat Akreditasi Prodi', required: true },
  surat_mandiri: { label: 'Surat Pernyataan Biaya Mandiri', required: true },
  surat_ijazah: { label: 'Surat Pernyataan Tidak Menuntut Ijazah', required: true },
  surat_sehat: { label: 'Surat Keterangan Sehat', required: true }
}

const isAllDocumentsVerified = computed(() => {
  return Object.values(documentChecks.value).every(v => v === true)
})

const hasIncompleteDocuments = computed(() => {
  return Object.values(documentChecks.value).some(v => v === false)
})

const canApprove = computed(() => {
  return pengajuan.value?.status === 'pending_admin' && isAllDocumentsVerified.value
})

const pengajuanId = computed(() => route.params.id)

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [pengajuanRes, dokumenRes, verificationRes] = await Promise.all([
      api.get(`/pengajuan/${pengajuanId.value}`),
      api.get(`/pengajuan/${pengajuanId.value}/dokumen`),
      api.get(`/verification/pengajuan/${pengajuanId.value}`)
    ])

    pengajuan.value = pengajuanRes.data
    dokumenList.value = dokumenRes.data.data || dokumenRes.data || []
    verificationInfo.value = verificationRes.data

    // Initialize document checks
    documentChecks.value = {}
    documentNotes.value = {}
    dokumenList.value.forEach(doc => {
      documentChecks.value[doc.id] = doc.status_verifikasi === 'lengkap'
      documentNotes.value[doc.id] = doc.catatan || ''
    })
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/admin/verifikasi')
}

function getDocumentIcon(type) {
  const ext = type?.split('/')?.[1] || ''
  if (['pdf'].includes(ext)) return 'ri-file-pdf-line'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'ri-image-line'
  if (['doc', 'docx'].includes(ext)) return 'ri-file-word-line'
  if (['xls', 'xlsx'].includes(ext)) return 'ri-file-excel-line'
  return 'ri-file-line'
}

function getDocumentUrl(path) {
  if (!path) return '#'

  if (path.startsWith('http')) {
    return path
  }

  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const baseUrl = apiUrl.endsWith('/api') ? apiUrl.slice(0, -4) : apiUrl
  return `${baseUrl}/storage/${path}`
}

function previewDocument(doc) {
  selectedDocument.value = {
    id: doc.id,
    name: doc.file_name,
    type: doc.file_type,
    url: getDocumentUrl(doc.file_path)
  }
  showDocumentPreview.value = true
}

async function updateDocumentVerification(docId) {
  try {
    await api.put(`/dokumen/${docId}/verify`, {
      status: documentChecks.value[docId] ? 'lengkap' : 'tidak_lengkap',
      catatan: documentNotes.value[docId]
    })

    const idx = dokumenList.value.findIndex(d => d.id === docId)
    if (idx !== -1) {
      dokumenList.value[idx].status_verifikasi = documentChecks.value[docId] ? 'lengkap' : 'tidak_lengkap'
      dokumenList.value[idx].catatan = documentNotes.value[docId]
    }
  } catch (error) {
    console.error('Failed to update document verification:', error)
  }
}

async function handleApprove() {
  if (!confirm('Verifikasi dan lanjutkan pengajuan ini? Status akan berubah menjadi "Terverifikasi" dan menunggu Surat Tugas Belajar dari Kepala Dinas.')) return

  submitting.value = true
  try {
    await api.post(`/pengajuan/${pengajuanId.value}/approve`)
    goBack()
  } catch (error) {
    console.error('Failed to approve:', error)
    alert('Gagal memverifikasi pengajuan')
  } finally {
    submitting.value = false
  }
}

function openRejectModal() {
  rejectModal.value = true
}

async function handleReject() {
  if (!rejectReason.value.trim()) {
    alert('Mohon isi alasan penolakan')
    return
  }

  submitting.value = true
  try {
    await api.post(`/pengajuan/${pengajuanId.value}/reject`, {
      alasan: rejectReason.value
    })
    goBack()
  } catch (error) {
    console.error('Failed to reject:', error)
    alert('Gagal menolak pengajuan')
  } finally {
    submitting.value = false
    rejectModal.value = false
  }
}

function getVerifierStatusClass(status) {
  const classes = {
    'completed': 'bg-green-50 border-green-200 text-green-700',
    'current': 'bg-blue-50 border-blue-200 text-blue-700',
    'pending': 'bg-gray-50 border-gray-200 text-gray-500'
  }
  return classes[status] || classes['pending']
}

function getVerifierStatusIcon(status) {
  const icons = {
    'completed': 'ri-checkbox-circle-fill text-green-600',
    'current': 'ri-time-line text-blue-600',
    'pending': 'ri-circle-line text-gray-400'
  }
  return icons[status] || icons['pending']
}

function getDocumentStatusLabel(status) {
  const labels = {
    'pending': 'Belum Diverifikasi',
    'lengkap': 'Lengkap',
    'tidak_lengkap': 'Tidak Lengkap'
  }
  return labels[status] || status
}

function getDocumentStatusClass(status) {
  const classes = {
    'pending': 'badge-secondary',
    'lengkap': 'badge-success',
    'tidak_lengkap': 'badge-danger'
  }
  return classes[status] || classes['pending']
}

function getDocumentCheckStatus(docTypeKey) {
  // Find document by type
  const doc = dokumenList.value.find(d => d.jenis_dokumen === docTypeKey)

  if (!doc) {
    return {
      icon: 'ri-checkbox-blank-circle-line text-gray-400',
      textClass: 'text-gray-500',
      bgClass: 'bg-gray-100'
    }
  }

  const isChecked = documentChecks.value[doc.id]

  if (isChecked === true) {
    return {
      icon: 'ri-checkbox-circle-fill text-green-600',
      textClass: 'text-green-700',
      bgClass: 'bg-green-100'
    }
  } else if (isChecked === false) {
    return {
      icon: 'ri-close-circle-fill text-red-600',
      textClass: 'text-red-700',
      bgClass: 'bg-red-100'
    }
  } else {
    return {
      icon: 'ri-time-line text-orange-600',
      textClass: 'text-orange-700',
      bgClass: 'bg-orange-100'
    }
  }
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <div class="flex items-center justify-between mb-5">
      <div>
        <button
          @click="goBack"
          class="flex items-center gap-2 text-secondary-600 hover:text-primary-600 transition-colors mb-2"
        >
          <i class="ri-arrow-left-line"></i>
          <span class="text-sm font-medium">Kembali ke Verifikasi</span>
        </button>
        <h1 class="text-2xl font-semibold text-secondary-800">Verifikasi Pengajuan</h1>
        <p v-if="pengajuan" class="text-secondary-500">{{ pengajuan.nomor_pengajuan }}</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="pengajuan?.status === 'pending_admin'"
          @click="openRejectModal"
          class="btn btn-danger"
          :disabled="submitting"
        >
          <i class="ri-close-line mr-1"></i>
          Tolak
        </button>

        <button
          v-if="pengajuan?.status === 'pending_admin'"
          @click="handleApprove"
          class="btn btn-primary"
          :disabled="submitting || !canApprove"
        >
          <i class="ri-check-line mr-1"></i>
          {{ submitting ? 'Memproses...' : 'Verifikasi & Lanjutkan' }}
        </button>
      </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
      <!-- Verification Progress Card -->
      <div class="card bg-gradient-to-br from-primary-500 to-primary-600 text-white">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-primary-100 mb-1">Progress Verifikasi</p>
              <p class="text-2xl font-bold">
                {{ Object.values(documentChecks).filter(v => v === true).length }} / {{ Object.keys(documentChecks).length }}
              </p>
              <p class="text-xs text-primary-100 mt-1">dokumen terverifikasi</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
              <i class="ri-file-check-line text-2xl"></i>
            </div>
          </div>
          <!-- Progress Bar -->
          <div class="mt-3 bg-white/20 rounded-full h-2">
            <div
              class="bg-white rounded-full h-2 transition-all duration-300"
              :style="{ width: (Object.values(documentChecks).filter(v => v === true).length / Object.keys(documentChecks).length * 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Status Card -->
      <div class="card" :class="{
        'bg-green-50 border-green-200': isAllDocumentsVerified,
        'bg-red-50 border-red-200': hasIncompleteDocuments,
        'bg-amber-50 border-amber-200': !isAllDocumentsVerified && !hasIncompleteDocuments
      }">
        <div class="card-body">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="{
              'bg-green-100': isAllDocumentsVerified,
              'bg-red-100': hasIncompleteDocuments,
              'bg-amber-100': !isAllDocumentsVerified && !hasIncompleteDocuments
            }">
              <i :class="[
                'text-xl',
                isAllDocumentsVerified ? 'ri-checkbox-circle-fill text-green-600' :
                hasIncompleteDocuments ? 'ri-close-circle-fill text-red-600' :
                'ri-time-line text-amber-600'
              ]"></i>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Status Verifikasi</p>
              <p class="text-sm font-semibold" :class="{
                'text-green-700': isAllDocumentsVerified,
                'text-red-700': hasIncompleteDocuments,
                'text-amber-700': !isAllDocumentsVerified && !hasIncompleteDocuments
              }">
                {{ isAllDocumentsVerified ? 'Lengkap Semua' : hasIncompleteDocuments ? 'Ada Tidak Lengkap' : 'Dalam Proses' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Complete Count Card -->
      <div class="card">
        <div class="card-body">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-xl text-success"></i>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Dokumen Lengkap</p>
              <p class="text-lg font-bold text-success">{{ Object.values(documentChecks).filter(v => v === true).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Incomplete Count Card -->
      <div class="card">
        <div class="card-body">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-danger/10 flex items-center justify-center">
              <i class="ri-close-circle-fill text-xl text-danger"></i>
            </div>
            <div>
              <p class="text-xs text-secondary-500">Dokumen Tidak Lengkap</p>
              <p class="text-lg font-bold text-danger">{{ Object.values(documentChecks).filter(v => v === false).length }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Checklist Summary -->
    <div class="card mb-5">
      <div class="card-header">
        <h4 class="card-title text-sm">
          <i class="ri-list-check-2 mr-1"></i> Checklist Dokumen
        </h4>
      </div>
      <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
          <div
            v-for="(docType, key) in documentTypes"
            :key="key"
            class="flex items-center gap-2 px-3 py-2.5 rounded-lg border transition-all hover:shadow-sm"
            :class="{
              'bg-green-50 border-green-200': getDocumentCheckStatus(key).icon.includes('green'),
              'bg-red-50 border-red-200': getDocumentCheckStatus(key).icon.includes('red'),
              'bg-gray-50 border-gray-200': getDocumentCheckStatus(key).icon.includes('gray') || getDocumentCheckStatus(key).icon.includes('orange')
            }"
          >
            <i :class="[getDocumentCheckStatus(key).icon, 'text-base']"></i>
            <span class="text-xs font-medium" :class="getDocumentCheckStatus(key).textClass">{{ docType.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <LoadingSpinner size="sm" text="Memuat data..." />
    </div>

    <div v-else-if="pengajuan" class="space-y-5">
      <!-- Verification Chain -->
      <div v-if="verificationInfo?.verification_chain" class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-flow-chart mr-1"></i> Alur Verifikasi
          </h4>
        </div>
        <div class="card-body">
          <div class="space-y-2">
            <div
              v-for="step in verificationInfo.verification_chain"
              :key="step.level"
              class="flex items-center gap-3 p-3 rounded-lg border"
              :class="getVerifierStatusClass(step.status)"
            >
              <i :class="[getVerifierStatusIcon(step.status), 'text-xl']"></i>
              <div class="flex-1">
                <p class="text-sm font-medium">{{ step.nama }}</p>
                <p class="text-xs opacity-75">{{ step.jabatan }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs font-medium" :class="step.status === 'completed' ? 'text-green-600' : ''">
                  {{ step.status === 'completed' ? 'Selesai' : step.status === 'current' ? 'Sedang Diproses' : 'Menunggu' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Pegawai Info -->
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              <i class="ri-user-line mr-1"></i> Informasi Pegawai
            </h4>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div>
                <p class="text-xs text-secondary-500">Nama</p>
                <p class="font-medium">{{ pengajuan.user?.name }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">NIP</p>
                <p class="font-medium">{{ pengajuan.user?.nip || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Pangkat/Golongan</p>
                <p class="font-medium">{{ pengajuan.user?.pangkat_gol || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Jabatan</p>
                <p class="font-medium">{{ pengajuan.user?.jabatan || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Unit Kerja</p>
                <p class="font-medium">{{ pengajuan.user?.unit_kerja?.nama || pengajuan.user?.unit_kerja || '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Pendidikan Info -->
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              <i class="ri-graduation-cap-line mr-1"></i> Informasi Pendidikan
            </h4>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div>
                <p class="text-xs text-secondary-500">Jenjang</p>
                <p class="font-medium">{{ pengajuan.jenjang?.nama_jenjang || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Program Studi</p>
                <p class="font-medium">{{ pengajuan.nama_prodi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Perguruan Tinggi</p>
                <p class="font-medium">{{ pengajuan.perguruan_tinggi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Akreditasi Prodi</p>
                <p class="font-medium">{{ pengajuan.akreditasi_prodi }}</p>
              </div>
              <div>
                <p class="text-xs text-secondary-500">Lokasi</p>
                <p class="font-medium">{{ pengajuan.lokasi_pt }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents Verification -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ri-file-list-3-line mr-1"></i> Verifikasi Dokumen
          </h4>
        </div>
        <div class="card-body">
          <div v-if="dokumenList.length === 0" class="text-center py-8 text-secondary-500">
            <i class="ri-inbox-line text-3xl"></i>
            <p class="mt-2">Tidak ada dokumen diunggah</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="doc in dokumenList"
              :key="doc.id"
              class="border rounded-lg overflow-hidden"
              :class="documentChecks[doc.id] === false ? 'border-red-300 bg-red-50' : 'border-secondary-200'"
            >
              <!-- Document Header -->
              <div class="p-2.5 bg-secondary-50 border-b flex items-center gap-2">
                <div class="w-7 h-7 rounded bg-white flex items-center justify-center shrink-0">
                  <i :class="[getDocumentIcon(doc.file_type), 'text-base text-secondary-500']"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium truncate text-secondary-700">{{ documentTypes[doc.jenis_dokumen]?.label || doc.jenis_dokumen }}</p>
                </div>
                <span class="badge text-xs py-0.5 px-1.5" :class="getDocumentStatusClass(doc.status_verifikasi)">
                  {{ getDocumentStatusLabel(doc.status_verifikasi) }}
                </span>
              </div>

              <!-- Document Preview Placeholder (No auto-load to prevent downloads) -->
              <div
                class="bg-secondary-100 aspect-[4/3] flex items-center justify-center overflow-hidden cursor-pointer hover:bg-secondary-200 transition-colors"
                @click="previewDocument(doc)"
              >
                <!-- File Icon with Preview Prompt -->
                <div class="text-center">
                  <i :class="[getDocumentIcon(doc.file_type), 'text-4xl text-secondary-400']"></i>
                  <p class="text-xs text-secondary-500 mt-2">Klik untuk preview</p>
                </div>
              </div>

              <!-- Actions & Notes -->
              <div class="p-2.5 space-y-2">
                <div class="flex items-center justify-between">
                  <button
                    @click="previewDocument(doc)"
                    class="text-xs text-primary-600 hover:text-primary-700"
                  >
                    <i class="ri-external-link-line mr-0.5"></i>
                    Buka
                  </button>

                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="documentChecks[doc.id]"
                      @change="updateDocumentVerification(doc.id)"
                      class="w-3.5 h-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    <span class="text-xs font-medium" :class="documentChecks[doc.id] ? 'text-green-600' : 'text-secondary-600'">
                      {{ documentChecks[doc.id] ? 'Lengkap' : 'Verifikasi' }}
                    </span>
                  </label>
                </div>

                <input
                  type="text"
                  v-model="documentNotes[doc.id]"
                  @blur="updateDocumentVerification(doc.id)"
                  placeholder="Catatan..."
                  class="w-full px-2 py-1.5 border rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Final Signer Info -->
      <div v-if="verificationInfo?.final_signer" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="ri-quill-pen-line text-blue-600"></i>
          </div>
          <div>
            <p class="text-xs text-blue-600">Penandatangan Surat</p>
            <p class="font-semibold text-blue-900">{{ verificationInfo.final_signer.nama }}</p>
            <p class="text-sm text-blue-700">{{ verificationInfo.final_signer.jabatan }}</p>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>

  <!-- Reject Confirmation Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="rejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="rejectModal = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-lg font-semibold text-red-600 mb-4">
            <i class="ri-close-circle-line mr-1"></i> Tolak Pengajuan
          </h3>
          <p class="text-sm text-secondary-600 mb-4">
            Pengajuan akan ditolak dan dikembalikan ke pemohon. Mohon isi alasan penolakan.
          </p>
          <textarea
            v-model="rejectReason"
            placeholder="Alasan penolakan..."
            class="w-full px-3 py-2 border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-primary-500"
            rows="3"
          ></textarea>
          <div class="flex justify-end gap-2 mt-4">
            <button @click="rejectModal = false" class="btn btn-ghost">Batal</button>
            <button @click="handleReject" class="btn btn-danger" :disabled="submitting">
              {{ submitting ? 'Memproses...' : 'Ya, Tolak' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Document Preview Modal -->
  <DocumentPreviewModal
    :show="showDocumentPreview"
    :document="selectedDocument"
    @close="showDocumentPreview = false"
  />
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
