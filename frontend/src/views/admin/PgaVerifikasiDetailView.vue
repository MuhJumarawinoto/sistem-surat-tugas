<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const route = useRoute()
const toast = useToastStore()

const loading = ref(false)
const submitting = ref(false)
const pga = ref(null)
const dokumenList = ref([])
const documentChecks = ref({})
const documentNotes = ref({})
const showDocumentPreview = ref(false)
const selectedDocument = ref(null)

const rejectModal = ref(false)
const rejectReason = ref('')

// PGA document types - these match the database columns
const documentTypes = {
  surat_pengantar: { label: 'Surat Pengantar/Usulan', required: true },
  sk_pangkat: { label: 'SK Pangkat Terakhir', required: true },
  sk_jabatan: { label: 'SK Jabatan Terbaru', required: true },
  surat_izin: { label: 'Surat Izin Belajar/Tugas Belajar', required: true },
  ijazah: { label: 'Asli Ijazah', required: true },
  ijazah_forlap: { label: 'Lampiran Forlap Dikti', required: true },
  transkrip: { label: 'Asli Transkrip Nilai', required: true },
  akreditasi: { label: 'Akreditasi Program Studi', required: true },
  ijazah_dikti: { label: 'Ijazah Luar Negeri (disetarakan)', required: false },
}

const isAllDocumentsVerified = computed(() => {
  return Object.values(documentChecks.value).every(v => v === true)
})

const hasIncompleteDocuments = computed(() => {
  return Object.values(documentChecks.value).some(v => v === false)
})

const canApprove = computed(() => {
  return pga.value?.status === 'approved_admin' && isAllDocumentsVerified.value
})

const pgaId = computed(() => route.params.id)

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const response = await api.get(`/pga/${pgaId.value}`)
    pga.value = response.data

    // Build documents list from PGA data
    const docs = []
    const docFields = [
      { key: 'surat_pengantar_file', type: 'surat_pengantar' },
      { key: 'sk_pangkat_file', type: 'sk_pangkat' },
      { key: 'sk_jabatan_file', type: 'sk_jabatan' },
      { key: 'surat_izin_file', type: 'surat_izin' },
      { key: 'ijazah_file', type: 'ijazah' },
      { key: 'ijazah_forlap_file', type: 'ijazah_forlap' },
      { key: 'transkrip_file', type: 'transkrip' },
      { key: 'akreditasi_file', type: 'akreditasi' },
      { key: 'ijazah_dikti_file', type: 'ijazah_dikti' },
    ]

    docFields.forEach((field, index) => {
      if (pga.value[field.key]) {
        docs.push({
          id: index + 1,
          jenis_dokumen: field.type,
          file_name: getFileNameFromPath(pga.value[field.key]),
          file_path: pga.value[field.key],
          file_type: 'application/pdf',
          status_verifikasi: 'lengkap',
          catatan: null
        })
      }
    })

    dokumenList.value = docs

    // Initialize document checks - all complete by default since docs are uploaded
    documentChecks.value = {}
    documentNotes.value = {}
    docs.forEach(doc => {
      documentChecks.value[doc.id] = true
      documentNotes.value[doc.id] = ''
    })
  } catch (error) {
    console.error('Failed to load data:', error)
    toast.error('Gagal memuat data PGA')
  } finally {
    loading.value = false
  }
}

function getFileNameFromPath(path) {
  if (!path) return ''
  return path.split('/').pop()
}

function goBack() {
  router.push('/admin/pga-verifikasi')
}

function getDocumentIcon(type) {
  const ext = type?.split('/')?.[1] || ''
  if (['pdf'].includes(ext)) return 'ri-file-pdf-line'
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'ri-image-line'
  if (['doc', 'docx'].includes(ext)) return 'ri-file-word-line'
  if (['xls', 'xlsx'].includes(ext)) return 'ri-file-excel-line'
  return 'ri-file-line'
}

function previewDocument(doc) {
  // Show document in modal
  const documentName = documentTypes[doc.jenis_dokumen]?.label || doc.file_name
  selectedDocument.value = {
    url: getDocumentViewUrl(doc.jenis_dokumen),
    name: documentName,
    type: doc.file_type || 'application/pdf'
  }
  showDocumentPreview.value = true
}

function getDocumentViewUrl(type) {
  // Use the viewDocument endpoint that serves files inline
  // Uses same authentication cookies as main request
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const token = localStorage.getItem('token') || ''
  return `${apiUrl}/pga/${pgaId.value}/view/${type}?token=${encodeURIComponent(token)}`
}

function getDocumentDownloadUrl(type) {
  // Use the downloadDocument endpoint for downloading
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  return `${apiUrl}/pga/${pgaId.value}/document/${type}`
}

function handleDocumentCheck(docId, checked) {
  documentChecks.value[docId] = checked
}

function openRejectModal() {
  rejectModal.value = true
}

async function handleReject() {
  if (!rejectReason.value.trim()) {
    toast.error('Mohon isi alasan penolakan')
    return
  }

  submitting.value = true
  try {
    await api.post(`/pga/${pgaId.value}/reject`, {
      catatan_tolak: rejectReason.value
    })
    toast.success('Pengajuan PGA berhasil ditolak')
    goBack()
  } catch (error) {
    console.error('Failed to reject:', error)
    toast.error(error.response?.data?.message || 'Gagal menolak pengajuan')
  } finally {
    submitting.value = false
    rejectModal.value = false
  }
}

async function handleApprove() {
  if (!confirm('Setujui pengajuan PGA ini?')) return

  submitting.value = true
  try {
    await api.post(`/pga/${pgaId.value}/approve`)
    toast.success('Pengajuan PGA berhasil disetujui')
    goBack()
  } catch (error) {
    console.error('Failed to approve:', error)
    toast.error(error.response?.data?.message || 'Gagal menyetujui pengajuan')
  } finally {
    submitting.value = false
  }
}

function getDocumentStatusClass(isChecked) {
  if (isChecked === true) return 'badge-success'
  if (isChecked === false) return 'badge-danger'
  return 'badge-secondary'
}

function getDocumentStatusLabel(isChecked) {
  if (isChecked === true) return 'Lengkap'
  if (isChecked === false) return 'Tidak Lengkap'
  return 'Belum Diverifikasi'
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
          <span class="text-sm font-medium">Kembali ke Verifikasi PGA</span>
        </button>
        <h1 class="text-2xl font-semibold text-secondary-800">Verifikasi Pengajuan PGA</h1>
        <p v-if="pga" class="text-secondary-500">{{ pga.nomor_pengajuan }}</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="pga?.status === 'approved_admin'"
          @click="openRejectModal"
          class="btn btn-danger"
          :disabled="submitting"
        >
          <i class="ri-close-line mr-1"></i>
          Tolak
        </button>

        <button
          v-if="pga?.status === 'approved_admin'"
          @click="handleApprove"
          class="btn btn-primary"
          :disabled="submitting || !canApprove"
        >
          <i class="ri-check-line mr-1"></i>
          {{ submitting ? 'Memproses...' : 'Setujui' }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <LoadingSpinner size="lg" text="Memuat data..." />
    </div>

    <!-- Content -->
    <div v-else-if="pga" class="space-y-6">
      <!-- Info Pegawai Card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Informasi Pegawai</h3>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-secondary-500">Nama Pegawai</label>
              <p class="font-medium text-secondary-800">{{ pga.user?.name }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">NIP</label>
              <p class="font-medium text-secondary-800">{{ pga.user?.nip }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Jabatan</label>
              <p class="font-medium text-secondary-800">{{ pga.user?.jabatan || '-' }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Unit Kerja</label>
              <p class="font-medium text-secondary-800">{{ pga.user?.unit_kerja?.nama || '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Pendidikan Card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Informasi Pendidikan</h3>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-secondary-500">Program Studi</label>
              <p class="font-medium text-secondary-800">{{ pga.nama_prodi }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Jenjang</label>
              <p class="font-medium text-secondary-800">{{ pga.jenjang_pendidikan?.nama }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Perguruan Tinggi</label>
              <p class="font-medium text-secondary-800">{{ pga.perguruan_tinggi }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Lokasi PT</label>
              <p class="font-medium text-secondary-800">{{ pga.lokasi_pt || '-' }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Gelar Akademik</label>
              <p class="font-medium text-secondary-800">{{ pga.gelar_akademik || '-' }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Tahun Lulus</label>
              <p class="font-medium text-secondary-800">{{ pga.tahun_lulus }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Nomor Ijazah</label>
              <p class="font-medium text-secondary-800">{{ pga.nomor_ijazah || '-' }}</p>
            </div>
            <div>
              <label class="text-xs text-secondary-500">Tanggal Ijazah</label>
              <p class="font-medium text-secondary-800">{{ pga.tanggal_ijazah || '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Dokumen Card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Dokumen Lampiran</h3>
        </div>
        <div class="card-body">
          <!-- Progress -->
          <div class="mb-4 p-3 bg-secondary-50 rounded-lg">
            <div class="flex items-center justify-between">
              <span class="text-sm text-secondary-600">
                Dokumen Terverifikasi: <strong>{{ Object.values(documentChecks).filter(v => v === true).length }} / {{ Object.keys(documentChecks).length }}</strong>
              </span>
              <span v-if="hasIncompleteDocuments" class="badge badge-danger text-xs">Ada yang Tidak Lengkap</span>
              <span v-else-if="isAllDocumentsVerified" class="badge badge-success text-xs">Semua Lengkap</span>
            </div>
          </div>

          <!-- Document List -->
          <div class="space-y-3">
            <div
              v-for="doc in dokumenList"
              :key="doc.id"
              class="flex items-start gap-3 p-3 border border-secondary-200 rounded-lg hover:bg-secondary-50 transition-colors"
            >
              <!-- Document Icon -->
              <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center flex-shrink-0">
                <i :class="getDocumentIcon(doc.file_type)" class="text-lg text-primary-600"></i>
              </div>

              <!-- Document Info -->
              <div class="flex-1 min-w-0">
                <p class="font-medium text-secondary-800 text-sm">{{ documentTypes[doc.jenis_dokumen]?.label || doc.jenis_dokumen }}</p>
                <p class="text-xs text-secondary-500 truncate">{{ doc.file_name }}</p>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <button
                  @click="previewDocument(doc)"
                  class="btn btn-ghost btn-sm p-2"
                  title="Preview Dokumen"
                >
                  <i class="ri-eye-line text-primary-600"></i>
                </button>
                <a
                  :href="getDocumentDownloadUrl(doc.jenis_dokumen)"
                  target="_blank"
                  class="btn btn-ghost btn-sm p-2"
                  title="Download Dokumen"
                >
                  <i class="ri-download-line text-secondary-600"></i>
                </a>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="dokumenList.length === 0" class="text-center py-8 text-secondary-500">
            <i class="ri-file-list-3-line text-4xl mb-2"></i>
            <p>Tidak ada dokumen</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Preview Modal -->
    <DocumentPreviewModal
      :show="showDocumentPreview"
      :document="selectedDocument"
      @close="showDocumentPreview = false"
    />

    <!-- Reject Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="rejectModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="rejectModal = false"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6 border-b">
              <h3 class="text-lg font-semibold">Tolak Pengajuan PGA</h3>
            </div>
            <div class="p-6">
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                Alasan Penolakan <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="rejectReason"
                rows="4"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:outline-none focus:border-primary-500"
                placeholder="Jelaskan alasan penolakan..."
              ></textarea>
            </div>
            <div class="p-6 border-t flex justify-end gap-2">
              <button
                @click="rejectModal = false"
                class="btn btn-ghost"
                :disabled="submitting"
              >
                Batal
              </button>
              <button
                @click="handleReject"
                class="btn btn-danger"
                :disabled="submitting"
              >
                <LoadingSpinner v-if="submitting" size="sm" />
                <span v-else>Tolak Pengajuan</span>
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
