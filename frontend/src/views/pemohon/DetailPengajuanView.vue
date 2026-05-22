<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'
import DocumentInfoTooltip from '@/components/DocumentInfoTooltip.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const route = useRoute()
const router = useRouter()
const pengajuanStore = usePengajuanStore()

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

const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

const isDraft = computed(() => pengajuan.value?.status === 'draft')
const isDitolak = computed(() => pengajuan.value?.status === 'ditolak')
const canSubmit = computed(() => isDraft.value || isDitolak.value)
const canEdit = computed(() => isDraft.value || isDitolak.value)
const isDisetujui = computed(() => pengajuan.value?.status === 'disetujui' || pengajuan.value?.status === 'selesai')

const jenisDokumenList = [
  {
    key: 'sk_pangkat',
    label: 'SK Pangkat Terakhir legalisir',
    requirements: ['SK Pangkat/Golongan terakhir yang sudah legalisir', 'Masih berlaku (minimal 1 tahun ke depan)', 'Scan dokumen asli dengan jelas'],
    notes: 'Legalisir bisa oleh pejabat pembina kepegawaian atau notaris'
  },
  {
    key: 'sk_cpns',
    label: 'SK CPNS legalisir',
    requirements: ['SK CPNS pertama kali diangkat', 'Scan dokumen asli dengan jelas', 'Terlihat nomor SK dan tanggal'],
    notes: 'Jika SK hilang, bisa diganti surat keterangan dari BKPSDM'
  },
  {
    key: 'skp',
    label: 'SKP 2 tahun terakhir',
    requirements: ['SKP 2 tahun terakhir (tahun berjalan dan tahun sebelumnya)', 'Nilai SKP minimal baik', 'Legalisir oleh atasan langsung'],
    notes: 'Upload dalam satu file PDF jika memungkinkan'
  },
  {
    key: 'surat_lulus',
    label: 'Surat Keterangan Lulus/Diterima dari PT',
    requirements: ['Surat Keterangan Lulus (SKL) atau Surat Diterima', 'Dikeluarkan oleh Perguruan Tinggi resmi', 'Tertera nama prodi dan jenjang'],
    notes: 'Bisa berupa SKL sementara atau surat diterima kuliah'
  },
  {
    key: 'jadwal',
    label: 'Jadwal Perkuliahan',
    requirements: ['Jadwal kuliah semester yang akan diikuti', 'Dikeluarkan oleh fakultas/prodi', 'Terlihat hari, jam, dan nama mata kuliah'],
    notes: 'Jika belum ada, bisa upload screenshot dari portal akademik'
  },
  {
    key: 'akreditasi',
    label: 'Sertifikat Akreditasi Prodi (min C)',
    requirements: ['Sertifikat akreditasi program studi', 'Minimal akreditasi C', 'Masih berlaku atau terbaru'],
    notes: 'Bisa dicek di banpt.or.id dan screenshot jika tidak ada sertifikat'
  },
  {
    key: 'surat_mandiri',
    label: 'Surat Pernyataan Biaya Mandiri',
    requirements: ['Surat pernyataan bermaterai 10.000', 'Menyatakan biaya kuliah mandiri', 'Ditandatangani dan bermaterai'],
    notes: 'Template surat bisa didownload di portal'
  },
  {
    key: 'surat_ijazah',
    label: 'Surat Pernyataan Tidak Menuntut Ijazah',
    requirements: ['Surat pernyataan bermaterai 10.000', 'Menyatakan tidak menuntut penyerahan ijazah asli', 'Ditandatangani dan bermaterai'],
    notes: 'Template surat bisa didownload di portal'
  },
  {
    key: 'surat_sehat',
    label: 'Surat Keterangan Sehat',
    requirements: ['Surat keterangan sehat dari dokter/Puskesmas/RS', 'Masih berlaku (maks 6 bulan)', 'Menyatakan sehat untuk melanjutkan studi'],
    notes: 'Bisa dari dokter pribadi atau fasilitas kesehatan pemerintah'
  },
]

const selectedDocInfo = computed(() => {
  return jenisDokumenList.find(d => d.key === additionalDocType.value)
})

onMounted(async () => {
  await loadPengajuan()
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
    `OK = Kirim dengan data lengkap (${complete ? '✓ 9 dokumen lengkap' : '✗ ' + (9 - missing) + '/9 dokumen'})\n` +
    `Cancel = Lengkapi data terlebih dahulu\n\n` +
    `${!complete ? '⚠️ Masih ada ' + missing + ' dokumen yang belum diupload.' : ''}`
  )

  if (!submitOption) {
    if (!complete) {
      alert('Silakan upload semua dokumen terlebih dahulu (9 dokumen wajib).')
    }
    return
  }

  if (!complete) {
    const proceed = confirm(
      `Anda akan mengirim pengajuan dengan data tidak lengkap (${9 - missing}/9 dokumen).\n\n` +
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
  const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp']
  const lowerFileName = fileName.toLowerCase()
  return imageExtensions.some(ext => lowerFileName.endsWith(ext))
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

      <!-- Progress Milestone -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-route-line text-primary-600"></i>
                Progress Pengajuan
              </h3>
            </div>
            <div class="card-body">
              <PengajuanMilestone :pengajuan-id="route.params.id" />
            </div>
          </div>

          <!-- Upload Section -->
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
            <div class="card">
              <div class="card-header">
                <div class="flex items-center justify-between">
                  <h3 class="card-title flex items-center gap-2">
                    <i class="ri-file-text-line text-primary-600"></i>
                    Dokumen
                  </h3>
                  <span class="badge badge-primary">{{ pengajuan.dokumen?.length || 0 }}/9</span>
                </div>
              </div>
              <div class="card-body">
                <div v-if="pengajuan.dokumen && pengajuan.dokumen.length > 0" class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                  <div v-for="doc in pengajuan.dokumen" :key="doc.id" class="p-3 border rounded-xl hover:bg-secondary-50 transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-secondary-800 truncate">{{ doc.file_name }}</p>
                        <p class="text-xs text-secondary-500">{{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB</p>
                      </div>
                      <span :class="[
                        'badge',
                        doc.status_verifikasi === 'lengkap' ? 'badge-success' :
                        doc.status_verifikasi === 'tidak_lengkap' ? 'badge-danger' :
                        'badge-default'
                      ]">
                        {{ getDocStatusLabel(doc.status_verifikasi) }}
                      </span>
                    </div>
                    <div v-if="isDocImage(doc)" class="mt-2">
                      <img :src="getStorageUrl(doc.file_path)" :alt="doc.file_name" class="max-w-xs max-h-32 rounded-lg cursor-pointer hover:opacity-80 transition-opacity" @click="openFile(doc.file_path)" />
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
                      Dokumen belum lengkap ({{ 9 - missingDocumentsCount }}/9)
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
  </MainLayout>
</template>
