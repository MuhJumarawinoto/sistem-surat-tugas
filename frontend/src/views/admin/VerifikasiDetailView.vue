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
  sk_pangkat: { label: 'SK Pangkat', required: true },
  sk_cpns: { label: 'SK CPNS', required: true },
  skp: { label: 'SKP 2 Thn', required: true },
  surat_lulus: { label: 'Surat Lulus', required: true },
  jadwal: { label: 'Jadwal', required: true },
  akreditasi: { label: 'Akreditasi', required: true },
  surat_mandiri: { label: 'Biaya Mandiri', required: true },
  surat_ijazah: { label: 'Tidak Menuntut', required: true },
  surat_sehat: { label: 'Surat Sehat', required: true }
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
  if (path.startsWith('http')) return path

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
  if (!confirm('Setujui pengajuan ini?')) return

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
      catatan: rejectReason.value
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

function getDocumentStatusLabel(status) {
  const labels = {
    'pending': 'Belum',
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
</script>

<template>
  <MainLayout>
    <Breadcrumb />

    <!-- Compact Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <div class="flex items-center gap-3">
        <button
          @click="goBack"
          class="btn btn-ghost btn-icon"
        >
          <i class="ri-arrow-left-line text-xl"></i>
        </button>
        <div>
          <h1 class="text-lg font-semibold text-secondary-800">{{ pengajuan?.nomor_pengajuan || 'Detail Pengajuan' }}</h1>
          <p class="text-xs text-secondary-500">{{ pengajuan?.user?.name }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Verification Status -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium" :class="{
          'bg-green-100 text-green-700 border border-green-200': isAllDocumentsVerified,
          'bg-red-100 text-red-700 border border-red-200': hasIncompleteDocuments,
          'bg-amber-100 text-amber-700 border border-amber-200': !isAllDocumentsVerified && !hasIncompleteDocuments
        }">
          <i :class="[
            isAllDocumentsVerified ? 'ri-checkbox-circle-fill' :
            hasIncompleteDocuments ? 'ri-close-circle-fill' :
            'ri-time-line'
          ]"></i>
          <span>
            {{ Object.values(documentChecks).filter(v => v === true).length }}/{{ Object.keys(documentChecks).length }} Dokumen
          </span>
        </div>

        <button
          v-if="pengajuan?.status === 'pending_admin'"
          @click="openRejectModal"
          class="btn btn-sm btn-danger"
          :disabled="submitting"
        >
          <i class="ri-close-line"></i>
          <span class="hidden sm:inline ml-1">Tolak</span>
        </button>

        <button
          v-if="pengajuan?.status === 'pending_admin'"
          @click="handleApprove"
          class="btn btn-sm btn-primary"
          :disabled="submitting || !canApprove"
        >
          <i class="ri-check-line"></i>
          <span class="hidden sm:inline ml-1">Setujui</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <LoadingSpinner size="sm" text="Memuat data..." />
    </div>

    <div v-else-if="pengajuan" class="space-y-4">
      <!-- Compact Info Row -->
      <div class="card">
        <div class="card-body py-3">
          <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-2 text-xs">
            <div>
              <p class="text-secondary-500">NIP</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.user?.nip || '-' }}</p>
            </div>
            <div>
              <p class="text-secondary-500">Pangkat/Gol</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.user?.pangkat_gol || '-' }}</p>
            </div>
            <div>
              <p class="text-secondary-500">Jabatan</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.user?.jabatan || '-' }}</p>
            </div>
            <div>
              <p class="text-secondary-500">Unit Kerja</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.user?.unit_kerja?.nama || '-' }}</p>
            </div>
            <div>
              <p class="text-secondary-500">Jenjang</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.jenjang?.nama || '-' }}</p>
            </div>
            <div>
              <p class="text-secondary-500">Prodi</p>
              <p class="font-medium text-secondary-800 truncate">{{ pengajuan.nama_prodi || '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Compact Document List -->
      <div class="card">
        <div class="card-header py-2">
          <h4 class="card-title text-sm">Dokumen yang Diunggah</h4>
        </div>
        <div class="card-body p-0">
          <div v-if="dokumenList.length === 0" class="text-center py-8 text-secondary-500">
            <i class="ri-inbox-line text-2xl"></i>
            <p class="text-xs mt-1">Tidak ada dokumen</p>
          </div>

          <div v-else class="divide-y divide-secondary-100">
            <!-- Document List Item (Compact) -->
            <div
              v-for="doc in dokumenList"
              :key="doc.id"
              class="flex items-center gap-3 p-2.5 hover:bg-secondary-50 transition-colors"
              :class="{ 'bg-red-50': documentChecks[doc.id] === false }"
            >
              <!-- Document Icon -->
              <div class="w-8 h-8 rounded bg-secondary-100 flex items-center justify-center shrink-0">
                <i :class="[getDocumentIcon(doc.file_type), 'text-base text-secondary-500']"></i>
              </div>

              <!-- Document Info -->
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-secondary-800 truncate">
                  {{ documentTypes[doc.jenis_dokumen]?.label || doc.jenis_dokumen }}
                </p>
                <p class="text-xs text-secondary-500 truncate">{{ doc.file_name }}</p>
              </div>

              <!-- Status Badge -->
              <span class="badge text-xs shrink-0" :class="getDocumentStatusClass(doc.status_verifikasi)">
                {{ getDocumentStatusLabel(doc.status_verifikasi) }}
              </span>

              <!-- Preview Button -->
              <button
                @click="previewDocument(doc)"
                class="btn btn-ghost btn-icon btn-sm text-primary-600 hover:text-primary-700 shrink-0"
                title="Preview"
              >
                <i class="ri-eye-line"></i>
              </button>

              <!-- Verification Checkbox -->
              <label class="flex items-center gap-1.5 cursor-pointer shrink-0" title="Verifikasi">
                <input
                  type="checkbox"
                  v-model="documentChecks[doc.id]"
                  @change="updateDocumentVerification(doc.id)"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >
                <i
                  class="text-sm transition-colors"
                  :class="documentChecks[doc.id] ? 'ri-checkbox-circle-fill text-green-600' : 'ri-checkbox-blank-circle-line text-gray-400'"
                ></i>
              </label>

              <!-- Notes Input -->
              <div class="w-24 shrink-0">
                <input
                  type="text"
                  v-model="documentNotes[doc.id]"
                  @blur="updateDocumentVerification(doc.id)"
                  placeholder="Catatan..."
                  class="w-full px-2 py-1 border border-secondary-200 rounded text-xs focus:outline-none focus:ring-1 focus:ring-primary-500"
                  :class="{ 'border-red-300 bg-red-50': documentChecks[doc.id] === false }"
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Missing Documents Warning -->
      <div v-if="dokumenList.length < Object.keys(documentTypes).length" class="card bg-amber-50 border-amber-200">
        <div class="card-body py-3">
          <div class="flex items-start gap-2">
            <i class="ri-error-warning-line text-amber-600 mt-0.5"></i>
            <div class="flex-1">
              <p class="text-xs font-medium text-amber-800">Dokumen Belum Lengkap</p>
              <p class="text-xs text-amber-700 mt-0.5">
                {{ Object.keys(documentTypes).length - dokumenList.length }} dari {{ Object.keys(documentTypes).length }} dokumen belum diunggah
              </p>
            </div>
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
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5">
          <h3 class="text-base font-semibold text-red-600 mb-2">
            <i class="ri-close-circle-line mr-1"></i> Tolak Pengajuan
          </h3>
          <p class="text-sm text-secondary-600 mb-3">
            Pengajuan akan ditolak dan dikembalikan ke pemohon. Mohon isi alasan penolakan.
          </p>
          <textarea
            v-model="rejectReason"
            placeholder="Alasan penolakan..."
            class="w-full px-3 py-2 border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
            rows="3"
          ></textarea>
          <div class="flex justify-end gap-2 mt-3">
            <button @click="rejectModal = false" class="btn btn-ghost btn-sm">Batal</button>
            <button @click="handleReject" class="btn btn-danger btn-sm" :disabled="submitting">
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
