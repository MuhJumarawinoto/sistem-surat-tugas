<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useMasterStore } from '@/stores/master'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'
import DocumentInfoTooltip from '@/components/DocumentInfoTooltip.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const route = useRoute()
const router = useRouter()
const pengajuanStore = usePengajuanStore()
const masterStore = useMasterStore()

const backendUrl = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace('/api', '')
  : 'http://localhost:8000'

const getStorageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${backendUrl}/storage/${path}`
}

const pengajuan = ref(null)
const loading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const fileUpload = ref(null)
const additionalDocType = ref('')
const additionalFile = ref(null)

// Highlight state from navigation
const highlightType = ref(route.query.highlight || history.state?.highlight || null)
const highlightId = ref(route.query.highlightId || history.state?.highlightId || null)
const showHighlight = ref(true)

// Clear highlight after animation
onMounted(async () => {
  // Load jenis dokumen if not already loaded
  if (masterStore.jenisDokumen.length === 0) {
    await masterStore.fetchJenisDokumen()
  }

  await loadPengajuan()

  if (highlightType.value) {
    // Scroll to highlighted element after DOM is updated
    await nextTick()
    setTimeout(() => {
      scrollToHighlighted()
    }, 300)

    // Stop highlight after animation
    setTimeout(() => {
      showHighlight.value = false
    }, 6000)
  }
})

// Scroll to highlighted element
function scrollToHighlighted() {
  if (highlightType.value === 'document' && highlightId.value) {
    const element = document.querySelector(`[data-doc-id="${highlightId.value}"]`)
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  }
}

// Check if document should be highlighted
const isDocHighlighted = (docId) => {
  return showHighlight.value && highlightType.value === 'document' && highlightId.value === String(docId)
}

// Get highlight class
const getHighlightClass = (docId) => {
  if (isDocHighlighted(docId)) {
    return 'highlight-pulse'
  }
  return ''
}

// Get container highlight class (for parent card)
const getContainerHighlightClass = () => {
  if (showHighlight.value && highlightType.value === 'document') {
    return 'container-highlight-pulse'
  }
  return ''
}

const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

// Document Preview Modal
const showDocPreviewModal = ref(false)
const previewDocSrc = ref('')
const previewDocAlt = ref('')
const previewDocType = ref('')

const isDraft = computed(() => pengajuan.value?.status === 'draft')
const isDitolak = computed(() => pengajuan.value?.status === 'ditolak')
const canSubmit = computed(() => isDraft.value || isDitolak.value)
const canEdit = computed(() => isDraft.value || isDitolak.value)
const isDisetujui = computed(() => pengajuan.value?.status === 'disetujui' || pengajuan.value?.status === 'selesai')

// Surat Tugas TTE (uploaded by admin)
const suratTugasDinas = ref(null)
const loadingSurat = ref(false)
const downloadingSurat = ref(false)

// Computed untuk cek apakah bisa download surat
const canDownloadSurat = computed(() => {
  return pengajuan.value?.status === 'selesai' || pengajuan.value?.status === 'completed'
})

// Load surat tugas dinas (with TTE)
async function loadSuratTugas() {
  if (!canDownloadSurat.value) return

  loadingSurat.value = true
  try {
    const response = await api.get(`/surat-tugas/${pengajuan.value.id}`)
    suratTugasDinas.value = response.data.data
  } catch (error) {
    console.error('Failed to load surat tugas:', error)
  } finally {
    loadingSurat.value = false
  }
}

// Download surat tugas TTE (admin uploaded)
async function downloadSuratTugas() {
  if (!suratTugasDinas.value || !suratTugasDinas.value.file_path_tte) {
    alert('Surat Tugas TTE belum tersedia')
    return
  }

  downloadingSurat.value = true
  try {
    // Use direct download with token to avoid CORS issues
    const token = localStorage.getItem('token')
    const baseUrl = import.meta.env.VITE_API_URL
      ? import.meta.env.VITE_API_URL.replace('/api', '')
      : 'http://localhost:8000'

    const url = `${baseUrl}/api/admin/surat-tugas/${suratTugasDinas.value.id}/download-tte?token=${encodeURIComponent(token)}`

    // Open in new tab to trigger download
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to download surat:', error)
    alert('Gagal mendownload surat. Silakan coba lagi.')
  } finally {
    downloadingSurat.value = false
    // Reset loading state after a delay
    setTimeout(() => {
      downloadingSurat.value = false
    }, 1000)
  }
}

// Watch pengajuan status changes untuk load surat
import { watch } from 'vue'
watch(() => pengajuan.value?.status, (newStatus) => {
  if (newStatus === 'selesai' || newStatus === 'completed') {
    loadSuratTugas()
  }
}, { immediate: true })

// Computed property untuk jenis dokumen dari master store
const jenisDokumenList = computed(() => {
  return masterStore.jenisDokumen.map(doc => ({
    key: doc.kode,
    label: doc.nama,
    requirements: doc.persyaratan || [],
    notes: doc.catatan || '',
    is_wajib: doc.is_wajib,
    urutan: doc.urutan
  }))
})

const selectedDocInfo = computed(() => {
  return jenisDokumenList.value.find(d => d.key === additionalDocType.value)
})

async function loadPengajuan() {
  loading.value = true
  try {
    pengajuan.value = await pengajuanStore.fetchPengajuanDetail(route.params.id)
  } catch (error) {
    alert('Gagal memuat pengajuan')
    router.push('/pengajuan')
  } finally {
    loading.value = false
  }
}

const isDocumentsComplete = computed(() => {
  if (!pengajuan.value?.dokumen) return false
  return pengajuan.value.dokumen.length >= 9
})

const missingDocumentsCount = computed(() => {
  if (!pengajuan.value?.dokumen) return 9
  return 9 - pengajuan.value.dokumen.length
})

async function submitPengajuan() {
  const complete = isDocumentsComplete.value
  const missing = missingDocumentsCount.value

  const submitOption = confirm(
    `Pilih opsi pengiriman:\n\n` +
    `OK = Kirim dengan data lengkap (${complete ? '✓ ' + jenisDokumenList.value.length + ' dokumen lengkap' : '✗ ' + (jenisDokumenList.value.length - missing) + '/' + jenisDokumenList.value.length + ' dokumen'})\n` +
    `Cancel = Lengkapi data terlebih dahulu\n\n` +
    `${!complete ? '⚠️ Masih ada ' + missing + ' dokumen yang belum diupload.' : ''}`
  )

  if (!submitOption) {
    if (!complete) {
      alert('Silakan upload semua dokumen terlebih dahulu (' + jenisDokumenList.value.length + ' dokumen wajib).')
    }
    return
  }

  if (!complete) {
    const proceed = confirm(
      `Anda akan mengirim pengajuan dengan data tidak lengkap (${jenisDokumenList.value.length - missing}/${jenisDokumenList.value.length} dokumen).\n\n` +
      `Pengajuan dengan dokumen tidak lengkap mungkin akan ditolak oleh atasan atau admin.\n\n` +
      `Lanjutkan kirim?`
    )
    if (!proceed) return
  }

  submitting.value = true
  try {
    await pengajuanStore.submitPengajuan(route.params.id)
    alert('Pengajuan berhasil dikirim ke atasan')
    await loadPengajuan()
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Gagal mengirim pengajuan'
    alert(errorMsg)
  } finally {
    submitting.value = false
  }
}

function editPengajuan() {
  router.push(`/pengajuan/${route.params.id}/edit`)
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file tidak boleh lebih dari 5MB')
      return
    }
    fileUpload.value = file
    uploadDocument()
  }
}

async function uploadDocument() {
  if (!fileUpload.value) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', fileUpload.value)
    formData.append('jenis_dokumen', 'additional')

    await api.post(`/pengajuan/${route.params.id}/dokumen`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    alert('Dokumen berhasil diupload')
    fileUpload.value = null
    await loadPengajuan()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengupload dokumen')
  } finally {
    uploading.value = false
  }
}

async function uploadAdditionalDocument() {
  if (!additionalFile.value) {
    alert('Pilih file terlebih dahulu')
    return
  }

  uploading.value = true
  uploadProgress.value = 0
  try {
    const formData = new FormData()
    formData.append('file', additionalFile.value)
    formData.append('jenis_dokumen', additionalDocType.value)

    await api.post(`/pengajuan/${route.params.id}/dokumen`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        uploadProgress.value = percentCompleted
      }
    })
    alert('Dokumen berhasil diupload')
    additionalFile.value = null
    additionalDocType.value = ''
    uploadProgress.value = 0
    await loadPengajuan()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengupload dokumen')
    uploadProgress.value = 0
  } finally {
    uploading.value = false
  }
}

// Header actions for PageHeader
const headerActions = computed(() => {
  const actions = []

  if (pengajuan.value) {
    // Add status badge (display only, not clickable)
    actions.push({
      label: getStatusLabel(pengajuan.value.status),
      icon: getStatusIcon(pengajuan.value.status),
      variant: getStatusBadge(pengajuan.value.status),
      isBadge: true, // Render as badge instead of button
    })

    // Add download surat tugas button if can download
    if (canDownloadSurat.value) {
      actions.push({
        label: downloadingSurat.value ? 'Mendownload...' : 'Download Surat Tugas',
        icon: downloadingSurat.value ? 'ri-loader-4-line animate-spin' : 'ri-download-line',
        variant: 'btn-success',
        onClick: downloadSuratTugas,
      })
    }

    // Add edit button if can edit
    if (canEdit.value) {
      actions.push({
        label: 'Edit',
        icon: 'ri-edit-line',
        variant: 'btn-secondary',
        onClick: editPengajuan,
      })
    }

    // Add submit button if can submit
    if (canSubmit.value) {
      actions.push({
        label: submitting.value ? 'Mengirim...' : 'Kirim',
        icon: submitting.value ? '' : 'ri-send-plane-fill',
        variant: 'btn-primary',
        onClick: submitPengajuan,
      })
    }
  }

  return actions
})

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    pending_admin: 'Menunggu Verifikasi',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    selesai: 'Selesai',
  }
  return labels[status] || status
}

function getDocStatusLabel(status) {
  const labels = {
    lengkap: 'Lengkap',
    tidak_lengkap: 'Tidak Lengkap',
    pending: 'Menunggu Verifikasi',
  }
  return labels[status] || 'Menunggu Verifikasi'
}

function isDocImage(doc) {
  if (!doc) return false
  if (doc.file_type && doc.file_type.startsWith('image/')) {
    return true
  }
  const fileName = doc.file_name || ''
  const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp']
  const lowerFileName = fileName.toLowerCase()
  return imageExtensions.some(ext => lowerFileName.endsWith(ext))
}

function isDocPdf(doc) {
  if (!doc) return false
  if (doc.file_type && doc.file_type.includes('pdf')) {
    return true
  }
  const fileName = doc.file_name || ''
  return fileName.toLowerCase().endsWith('.pdf')
}

function getDocFileType(doc) {
  if (isDocImage(doc)) return 'image'
  if (isDocPdf(doc)) return 'pdf'
  return 'unknown'
}

function getDocIcon(doc) {
  if (isDocImage(doc)) return 'ri-image-line'
  if (isDocPdf(doc)) return 'ri-file-pdf-line'
  return 'ri-file-text-line'
}

function getDocIconClass(doc) {
  if (isDocImage(doc)) return 'text-blue-500'
  if (isDocPdf(doc)) return 'text-red-500'
  return 'text-secondary-400'
}

function openFile(path) {
  currentImageSrc.value = getStorageUrl(path)
  currentImageAlt.value = 'Dokumen'
  showImageModal.value = true
}

function openImageModal(url) {
  currentImageSrc.value = url
  currentImageAlt.value = 'Dokumen'
  showImageModal.value = true
}

function openDocumentPreview(doc) {
  previewDocSrc.value = getStorageUrl(doc.file_path)
  previewDocAlt.value = doc.file_name || 'Dokumen'
  previewDocType.value = getDocFileType(doc)
  showDocPreviewModal.value = true
}

function closeDocumentPreview() {
  showDocPreviewModal.value = false
  previewDocSrc.value = ''
  previewDocAlt.value = ''
  previewDocType.value = ''
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-default',
    pending_admin: 'badge-warning',
    disetujui: 'badge-success',
    ditolak: 'badge-danger',
    selesai: 'badge-purple',
  }
  return badges[status] || 'badge-default'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    pending_admin: 'ri-time-line',
    disetujui: 'ri-check-line',
    ditolak: 'ri-close-line',
    selesai: 'ri-checkbox-circle-line',
  }
  return icons[status] || 'ri-file-line'
}
</script>

<template>
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat..." />
    </div>

    <div v-else-if="pengajuan" class="space-y-6 animate-fade-in">
      <Breadcrumb />

      <PageHeader
        title="Detail Pengajuan"
        :subtitle="pengajuan.nomor_pengajuan"
        :actions="headerActions"
      />

      <!-- Created By Banner (for kepala unit created pengajuan) -->
      <div v-if="pengajuan.created_by && pengajuan.created_by !== pengajuan.user_id" class="bg-info-50 border border-info-200 rounded-xl p-4 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-info-100 flex items-center justify-center flex-shrink-0">
            <i class="ri-information-line text-info-600 text-lg"></i>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-info-800">Pengajuan dibuat oleh kepala unit</p>
            <p class="text-xs text-info-600">
              {{ pengajuan.created_by?.name || '-' }} ({{ pengajuan.created_by?.nip || '-' }})
            </p>
          </div>
        </div>
      </div>

      <!-- Progress Milestone -->
          <!-- <div class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-route-line text-primary-600"></i>
                Progress Pengajuan
              </h3>
            </div>
            <div class="card-body">
              <PengajuanMilestone :pengajuan-id="route.params.id" />
            </div>
          </div> -->

          <!-- Upload Section - Hidden -->
          <!--
          <div v-if="canEdit" class="card border-l-4 border-l-primary-500">
            <div class="card-header bg-primary-50/50">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-upload-cloud-2-line text-primary-600"></i>
                Upload Dokumen Tambahan
              </h3>
            </div>
            <div class="card-body space-y-4">
              <div>
                <label class="input-label">
                  Jenis Dokumen
                  <DocumentInfoTooltip
                    v-if="selectedDocInfo"
                    :title="selectedDocInfo.label"
                    :requirements="selectedDocInfo.requirements"
                    :notes="selectedDocInfo.notes"
                  />
                </label>
                <select v-model="additionalDocType" required class="select-field">
                  <option value="">Pilih Jenis Dokumen</option>
                  <option v-for="doc in jenisDokumenList" :key="doc.key" :value="doc.key">
                    {{ doc.label }}
                  </option>
                </select>
              </div>

              <FileUpload
                v-model="additionalFile"
                :uploading="uploading"
                :upload-progress="uploadProgress"
                :preview="true"
                @preview="openImageModal"
              />

              <button
                @click="uploadAdditionalDocument"
                :disabled="!additionalDocType || !additionalFile || uploading"
                class="btn btn-primary w-full justify-center"
              >
                <LoadingSpinner v-if="uploading" size="sm" color="white" />
                <span>{{ uploading ? 'Mengupload...' : 'Upload Dokumen' }}</span>
              </button>
            </div>
          </div>
          -->

          <!-- Info Cards Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pendidikan Info -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                  <i class="ri-graduation-cap-line text-primary-600"></i>
                  Informasi Pendidikan
                </h3>
              </div>
              <div class="card-body">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <dt class="text-secondary-500">Jenjang</dt>
                    <dd class="text-secondary-800 font-medium">{{ pengajuan.jenjang?.nama }}</dd>
                  </div>
                  <div>
                    <dt class="text-secondary-500">Program Studi</dt>
                    <dd class="text-secondary-800 font-medium">{{ pengajuan.nama_prodi }}</dd>
                  </div>
                  <div>
                    <dt class="text-secondary-500">Perguruan Tinggi</dt>
                    <dd class="text-secondary-800 font-medium">{{ pengajuan.perguruan_tinggi }}</dd>
                  </div>
                  <div>
                    <dt class="text-secondary-500">Akreditasi</dt>
                    <dd class="text-secondary-800 font-medium">{{ pengajuan.akreditasi_prodi }}</dd>
                  </div>
                  <div>
                    <dt class="text-secondary-500">Lokasi</dt>
                    <dd class="text-secondary-800 font-medium">{{ pengajuan.lokasi_pt }}</dd>
                  </div>
                  <div>
                    <dt class="text-secondary-500">Periode</dt>
                    <dd class="text-secondary-800 font-medium">
                      {{ new Date(pengajuan.rencana_mulai).toLocaleDateString('id-ID') }}
                      -
                      {{ new Date(pengajuan.rencana_selesai).toLocaleDateString('id-ID') }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Documents Card -->
            <div class="card" :class="getContainerHighlightClass()">
              <div class="card-header">
                <div class="flex items-center justify-between">
                  <h3 class="card-title flex items-center gap-2">
                    <i class="ri-file-text-line text-primary-600"></i>
                    Dokumen
                  </h3>
                  <span class="badge badge-primary">{{ pengajuan.dokumen?.length || 0 }}/{{ jenisDokumenList.length }}</span>
                </div>
              </div>
              <div class="card-body">
                <div v-if="pengajuan.dokumen && pengajuan.dokumen.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto scrollbar-thin">
                  <div v-for="doc in pengajuan.dokumen" :key="doc.id" :data-doc-id="doc.id" class="p-3 border rounded-xl hover:bg-secondary-50 transition-colors" :class="[
                    doc.catatan ? 'border-amber-300 bg-amber-50/30' : '',
                    getHighlightClass(doc.id)
                  ]">
                    <div class="flex items-start justify-between mb-2">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <i :class="[getDocIcon(doc), getDocIconClass(doc)]" class="text-lg flex-shrink-0"></i>
                          <p class="text-sm font-medium text-secondary-800 truncate">{{ doc.file_name }}</p>
                          <i v-if="doc.catatan" class="ri-message-3-fill text-amber-500 text-sm flex-shrink-0" title="Ada catatan verifikasi"></i>
                        </div>
                        <p class="text-xs text-secondary-500">{{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB</p>
                      </div>
                      <span :class="[
                        'badge text-xs',
                        doc.status_verifikasi === 'lengkap' ? 'badge-success' :
                        doc.status_verifikasi === 'tidak_lengkap' ? 'badge-danger' :
                        'badge-default'
                      ]">
                        {{ getDocStatusLabel(doc.status_verifikasi) }}
                      </span>
                    </div>

                    <!-- Document Preview Thumbnail (for images) -->
                    <div v-if="isDocImage(doc)" class="mt-2">
                      <img :src="getStorageUrl(doc.file_path)" :alt="doc.file_name" class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity" @click="openDocumentPreview(doc)" />
                    </div>

                    <!-- PDF Thumbnail Placeholder -->
                    <div v-else-if="isDocPdf(doc)" class="mt-2">
                      <div @click="openDocumentPreview(doc)" class="w-full h-32 bg-red-50 border border-red-200 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-red-100 transition-colors">
                        <i class="ri-file-pdf-line text-4xl text-red-500 mb-1"></i>
                        <p class="text-xs text-red-600">Klik untuk preview PDF</p>
                      </div>
                    </div>

                    <!-- Other File Type Placeholder -->
                    <div v-else class="mt-2">
                      <div @click="openDocumentPreview(doc)" class="w-full h-32 bg-secondary-50 border border-secondary-200 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-secondary-100 transition-colors">
                        <i :class="[getDocIcon(doc), getDocIconClass(doc), 'text-4xl mb-1']"></i>
                        <p class="text-xs text-secondary-600">Klik untuk preview</p>
                      </div>
                    </div>

                    <!-- Preview & Download Buttons -->
                    <div class="mt-2 flex gap-2">
                      <button
                        @click="openDocumentPreview(doc)"
                        class="flex-1 btn btn-sm btn-primary justify-center"
                        title="Preview Dokumen"
                      >
                        <i class="ri-eye-line mr-1"></i>
                        Preview
                      </button>
                      <a
                        :href="getStorageUrl(doc.file_path)"
                        :download="doc.file_name"
                        target="_blank"
                        class="flex-1 btn btn-sm btn-secondary justify-center"
                        title="Download Dokumen"
                      >
                        <i class="ri-download-line mr-1"></i>
                        Download
                      </a>
                    </div>

                    <!-- Catatan Verifikasi -->
                    <div v-if="doc.catatan" class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                      <div class="flex items-start gap-2">
                        <i class="ri-chat-3-line text-amber-600 mt-0.5"></i>
                        <div class="flex-1">
                          <p class="text-xs font-medium text-amber-800">Catatan Verifikasi:</p>
                          <p class="text-sm text-amber-900">{{ doc.catatan }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-6">
                  <div class="w-12 h-12 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-2">
                    <i class="ri-file-upload-line text-xl text-secondary-400"></i>
                  </div>
                  <p class="text-sm text-secondary-500">Belum ada dokumen</p>
                </div>

                <!-- Warning -->
                <div v-if="canSubmit && !isDocumentsComplete" class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200">
                  <div class="flex items-start gap-2">
                    <i class="ri-alert-line text-amber-600 mt-0.5"></i>
                    <p class="text-sm text-amber-800">
                      Dokumen belum lengkap ({{ jenisDokumenList.length - missingDocumentsCount }}/{{ jenisDokumenList.length }})
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Catatan Penolakan -->
          <div v-if="pengajuan.catatan_tolak" class="card border-l-4 border-l-danger">
            <div class="card-body bg-red-50">
              <h3 class="text-sm font-semibold text-red-900 flex items-center gap-2 mb-2">
                <i class="ri-close-circle-line"></i>
                Catatan Penolakan
              </h3>
              <p class="text-sm text-red-700">{{ pengajuan.catatan_tolak }}</p>
            </div>
          </div>

          <!-- Document Checklist -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title text-sm">Checklist Dokumen Wajib</h3>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                <div v-for="doc in jenisDokumenList" :key="doc.key" class="flex items-center gap-2 p-2 rounded-lg" :class="pengajuan.dokumen?.find(d => d.jenis_dokumen === doc.key) ? 'bg-green-50' : 'bg-secondary-50'">
                  <span :class="pengajuan.dokumen?.find(d => d.jenis_dokumen === doc.key) ? 'text-success' : 'text-secondary-300'">
                    <i :class="pengajuan.dokumen?.find(d => d.jenis_dokumen === doc.key) ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'" class="text-lg"></i>
                  </span>
                  <span class="text-sm text-secondary-700 truncate">{{ doc.label }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Approval History -->
          <div v-if="pengajuan.approval_history && pengajuan.approval_history.length > 0" class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-history-line text-primary-600"></i>
                Riwayat Approval
              </h3>
            </div>
            <div class="card-body">
              <div class="space-y-3">
                <div v-for="history in pengajuan.approval_history" :key="history.id" class="p-3 border rounded-xl">
                  <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                      <div class="avatar avatar-md" :class="history.status === 'setuju' ? 'bg-success' : 'bg-danger'">
                        <i :class="history.status === 'setuju' ? 'ri-check-line' : 'ri-close-line'"></i>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-secondary-800">{{ history.approver?.name || 'System' }}</p>
                        <p class="text-xs text-secondary-500">{{ new Date(history.created_at).toLocaleString('id-ID') }}</p>
                      </div>
                    </div>
                    <span :class="['badge', history.status === 'setuju' ? 'badge-success' : 'badge-danger']">
                      {{ history.status === 'setuju' ? 'Disetujui' : 'Ditolak' }}
                    </span>
                  </div>
                  <p v-if="history.catatan" class="text-sm text-secondary-600 mt-2 ml-11">{{ history.catatan }}</p>
                </div>
              </div>
            </div>
        </div>
      </div>

    <ImageModal
      :show="showImageModal"
      :src="currentImageSrc"
      :alt="currentImageAlt"
      @close="showImageModal = false"
    />

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
/* Pulse animation for individual document card */
.highlight-pulse {
  animation: pulse-glow 2s ease-in-out 3;
  border-color: #f97316 !important;
  background-color: rgba(249, 115, 22, 0.05) !important;
  box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7), 0 0 20px rgba(249, 115, 22, 0.3);
  position: relative;
}

/* Pulse animation for container card (parent) */
.container-highlight-pulse {
  animation: container-pulse 2s ease-in-out 3;
  border-color: #f97316 !important;
  box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5), 0 0 40px rgba(249, 115, 22, 0.2);
  position: relative;
}

/* Add glow ring around container */
.container-highlight-pulse::before {
  content: '';
  position: absolute;
  inset: -6px;
  border-radius: inherit;
  border: 3px solid #f97316;
  opacity: 0.5;
  animation: container-ring 2s ease-in-out 3;
  pointer-events: none;
  z-index: -1;
}

/* Add additional glow ring around individual card */
.highlight-pulse::before {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: inherit;
  border: 2px solid #f97316;
  opacity: 0.6;
  animation: pulse-ring 2s ease-in-out 3;
  pointer-events: none;
  z-index: 1;
}

@keyframes pulse-glow {
  0% {
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7), 0 0 20px rgba(249, 115, 22, 0.4);
    background-color: rgba(249, 115, 22, 0.08);
  }
  50% {
    box-shadow: 0 0 0 15px rgba(249, 115, 22, 0), 0 0 30px rgba(249, 115, 22, 0.6);
    background-color: rgba(249, 115, 22, 0.12);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0), 0 0 20px rgba(249, 115, 22, 0.3);
    background-color: rgba(249, 115, 22, 0.05);
  }
}

@keyframes container-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5), 0 0 40px rgba(249, 115, 22, 0.2);
  }
  50% {
    box-shadow: 0 0 0 20px rgba(249, 115, 22, 0), 0 0 60px rgba(249, 115, 22, 0.4);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0), 0 0 40px rgba(249, 115, 22, 0.2);
  }
}

@keyframes pulse-ring {
  0% {
    opacity: 0.6;
    transform: scale(1);
  }
  50% {
    opacity: 0.3;
    transform: scale(1.02);
  }
  100% {
    opacity: 0.6;
    transform: scale(1);
  }
}

@keyframes container-ring {
  0% {
    opacity: 0.5;
    transform: scale(1);
  }
  50% {
    opacity: 0.2;
    transform: scale(1.01);
  }
  100% {
    opacity: 0.5;
    transform: scale(1);
  }
}
</style>
