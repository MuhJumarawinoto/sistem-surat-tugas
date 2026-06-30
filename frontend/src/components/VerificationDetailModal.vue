<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'

const router = useRouter()
const toastStore = useToastStore()

const props = defineProps({
  show: Boolean,
  pengajuanId: [String, Number]
})

const emit = defineEmits(['close', 'verified'])

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
  return (pengajuan.value?.status === 'pending_admin' || pengajuan.value?.status === 'verified') && isAllDocumentsVerified.value
})

watch(() => props.show, async (newVal) => {
  if (newVal && props.pengajuanId) {
    await loadData()
  } else {
    resetState()
  }
})

// Watch for when all documents are verified and show toast notification
watch(isAllDocumentsVerified, (isVerified) => {
  if (isVerified && pengajuan.value?.status === 'pending_admin') {
    // Show toast notification with action button
    toastStore.success(
      'Semua dokumen telah diverifikasi lengkap. Apakah ingin lanjut buat surat tugas?',
      0, // No auto-dismiss
      {
        label: 'Ya, Lanjut ke Surat Tugas',
        onClick: () => {
          router.push('/admin/surat-tugas')
        }
      }
    )
  }
})

async function loadData() {
  loading.value = true
  try {
    // Load pengajuan detail
    const [pengajuanRes, dokumenRes, verificationRes] = await Promise.all([
      api.get(`/pengajuan/${props.pengajuanId}`),
      api.get(`/pengajuan/${props.pengajuanId}/dokumen`),
      api.get(`/verification/pengajuan/${props.pengajuanId}`)
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

function resetState() {
  pengajuan.value = null
  verificationInfo.value = null
  dokumenList.value = []
  documentChecks.value = {}
  documentNotes.value = {}
  rejectReason.value = ''
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

  // If path is already a full URL, return as is
  if (path.startsWith('http')) {
    return path
  }

  // Build URL from storage path
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

    // Update local document
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
  if (!confirm('Setujui pengajuan ini? Dokumen akan ditandai lengkap.')) return

  submitting.value = true
  try {
    await api.post(`/pengajuan/${props.pengajuanId}/approve`)
    emit('verified')
    emit('close')
  } catch (error) {
    console.error('Failed to approve:', error)
    alert('Ggal menyetujui pengajuan')
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
    await api.post(`/pengajuan/${props.pengajuanId}/reject`, {
      alasan: rejectReason.value
    })
    emit('verified')
    emit('close')
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
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="$emit('close')"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-6 border-b bg-gradient-to-r from-primary-700 to-accent">
            <div class="text-white">
              <h3 class="text-lg font-semibold">Verifikasi Pengajuan</h3>
              <p v-if="pengajuan" class="text-sm opacity-80">{{ pengajuan.nomor_pengajuan }}</p>
            </div>
            <button @click="$emit('close')" class="btn btn-ghost btn-icon text-white hover:bg-white/20">
              <i class="ri-close-line text-xl"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="flex-1 overflow-y-auto p-6">
            <LoadingSpinner v-if="loading" />

            <div v-else-if="pengajuan" class="space-y-6">
              <!-- Verification Chain -->
              <div v-if="verificationInfo?.verification_chain" class="bg-secondary-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-secondary-700 mb-3">
                  <i class="ri-flow-chart mr-1"></i> Alur Verifikasi
                </h4>
                <div class="space-y-2">
                  <div
                    v-for="step in verificationInfo.verification_chain"
                    :key="step.level"
                    class="flex items-center gap-3 p-2 rounded border"
                    :class="getVerifierStatusClass(step.status)"
                  >
                    <i :class="[getVerifierStatusIcon(step.status), 'text-lg']"></i>
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

              <!-- Pegawai Info -->
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">
                    <i class="ri-user-line mr-1"></i> Informasi Pegawai
                  </h4>
                </div>
                <div class="card-body">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <div>
                      <p class="text-xs text-secondary-500">Kategori Jabatan</p>
                      <p class="font-medium">{{ pengajuan.user?.jabatan_kategori || '-' }}</p>
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
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <div>
                      <p class="text-xs text-secondary-500">Rencana Mulai</p>
                      <p class="font-medium">{{ pengajuan.rencana_mulai }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-secondary-500">Rencana Selesai</p>
                      <p class="font-medium">{{ pengajuan.rencana_selesai }}</p>
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
                <div class="card-body space-y-4">
                  <div v-if="dokumenList.length === 0" class="text-center py-8 text-secondary-500">
                    <i class="ri-inbox-line text-3xl"></i>
                    <p class="mt-2">Tidak ada dokumen diunggah</p>
                  </div>

                  <div
                    v-for="doc in dokumenList"
                    :key="doc.id"
                    class="p-4 border rounded-lg"
                    :class="documentChecks[doc.id] === false ? 'border-red-300 bg-red-50' : 'border-secondary-200'"
                  >
                    <div class="flex flex-col md:flex-row md:items-start gap-4">
                      <!-- Document Info -->
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <i :class="[getDocumentIcon(doc.file_type), 'text-xl text-secondary-500']"></i>
                          <span class="font-medium">{{ documentTypes[doc.jenis_dokumen]?.label || doc.jenis_dokumen }}</span>
                          <span class="badge" :class="getDocumentStatusClass(doc.status_verifikasi)">
                            {{ getDocumentStatusLabel(doc.status_verifikasi) }}
                          </span>
                        </div>
                        <p class="text-sm text-secondary-500">
                          {{ doc.file_name }} ({{ doc.file_size ? (doc.file_size / 1024 / 1024).toFixed(2) : '0' }} MB)
                        </p>
                      </div>

                      <!-- Actions -->
                      <div class="flex items-center gap-2">
                        <button
                          @click="previewDocument(doc)"
                          class="btn btn-ghost btn-sm"
                          title="Preview Dokumen"
                        >
                          <i class="ri-eye-line"></i>
                        </button>

                        <label class="flex items-center gap-2 cursor-pointer">
                          <input
                            type="checkbox"
                            v-model="documentChecks[doc.id]"
                            @change="updateDocumentVerification(doc.id)"
                            class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                          >
                          <span class="text-sm">Lengkap</span>
                        </label>
                      </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-3">
                      <label class="text-xs text-secondary-500">Catatan Verifikasi:</label>
                      <input
                        type="text"
                        v-model="documentNotes[doc.id]"
                        @blur="updateDocumentVerification(doc.id)"
                        placeholder="Tambahkan catatan untuk dokumen ini..."
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                      >
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
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-between p-6 border-t bg-secondary-50">
            <div class="text-sm text-secondary-500">
              <span v-if="isAllDocumentsVerified" class="text-green-600">
                <i class="ri-checkbox-circle-line mr-1"></i> Semua dokumen lengkap
              </span>
              <span v-else-if="hasIncompleteDocuments" class="text-red-600">
                <i class="ri-error-warning-line mr-1"></i> Ada dokumen tidak lengkap
              </span>
              <span v-else class="text-orange-600">
                <i class="ri-time-line mr-1"></i> Verifikasi dokumen belum selesai
              </span>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="$emit('close')"
                class="btn btn-ghost"
                :disabled="submitting"
              >
                Tutup
              </button>

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
                v-if="pengajuan?.status === 'pending_admin' || pengajuan?.status === 'verified'"
                @click="handleApprove"
                class="btn btn-primary"
                :disabled="submitting || !canApprove"
              >
                <i class="ri-check-line mr-1"></i>
                {{ submitting ? 'Memproses...' : 'Setujui & Lanjutkan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Reject Confirmation Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="rejectModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50"
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
