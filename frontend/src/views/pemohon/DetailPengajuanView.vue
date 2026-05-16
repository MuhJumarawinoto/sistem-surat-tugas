<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'

const route = useRoute()
const router = useRouter()
const pengajuanStore = usePengajuanStore()

// Backend URL for storage files
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
const fileUpload = ref(null)
const additionalDocType = ref('')
const additionalFile = ref(null)

// Image Modal
const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

// Image Modal
const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

const isDraft = computed(() => pengajuan.value?.status === 'draft')
const isDitolak = computed(() => pengajuan.value?.status === 'ditolak')
const canSubmit = computed(() => isDraft.value || isDitolak.value)
const canEdit = computed(() => isDraft.value || isDitolak.value)
const isDisetujui = computed(() => pengajuan.value?.status === 'disetujui' || pengajuan.value?.status === 'selesai')

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

async function submitPengajuan() {
  if (!confirm('Apakah Anda yakin ingin mengirim pengajuan? Pastikan semua data sudah benar.')) return

  submitting.value = true
  try {
    await pengajuanStore.submitPengajuan(route.params.id)
    alert('Pengajuan berhasil dikirim ke atasan')
    await loadPengajuan()
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Gagal mengirim pengajuan'
    if (errorMsg.includes('All documents must be uploaded')) {
      alert('Gagal mengirim: Semua dokumen wajib (9 dokumen) harus diupload terlebih dahulu.')
    } else if (errorMsg.includes('already submitted')) {
      alert('Gagal mengirim: Pengajuan sudah pernah dikirim.')
    } else {
      alert(errorMsg)
    }
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
  try {
    const formData = new FormData()
    formData.append('file', additionalFile.value)
    formData.append('jenis_dokumen', additionalDocType.value)

    await api.post(`/pengajuan/${route.params.id}/dokumen`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    alert('Dokumen berhasil diupload')
    additionalFile.value = null
    additionalDocType.value = ''
    await loadPengajuan()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengupload dokumen')
  } finally {
    uploading.value = false
  }
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    pending_atasan: 'Pending Atasan',
    pending_admin: 'Pending Admin',
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

  // Check by file_type first
  if (doc.file_type && doc.file_type.startsWith('image/')) {
    return true
  }

  // Fallback: check by file extension
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

function getStatusColor(status) {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    pending_atasan: 'bg-yellow-100 text-yellow-800',
    pending_admin: 'bg-blue-100 text-blue-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
    selesai: 'bg-purple-100 text-purple-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-500">Memuat...</p>
        </div>

        <div v-else-if="pengajuan" class="space-y-6">
          <div class="flex justify-between items-center">
            <div>
              <router-link to="/pengajuan" class="text-blue-600 hover:text-blue-800">
                &larr; Kembali
              </router-link>
              <h2 class="text-2xl font-bold text-gray-900 mt-2">Detail Pengajuan</h2>
              <p class="text-gray-600">{{ pengajuan.nomor_pengajuan }}</p>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="['px-3 py-1 text-sm rounded-full', getStatusColor(pengajuan.status)]">
                {{ getStatusLabel(pengajuan.status) }}
              </span>
              <button v-if="canEdit" @click="editPengajuan" class="btn-secondary text-sm">
                Edit
              </button>
              <button
                v-if="canSubmit"
                @click="submitPengajuan"
                :disabled="submitting"
                class="btn-primary text-sm"
              >
                {{ submitting ? 'Mengirim...' : 'Kirim ke Atasan' }}
              </button>
            </div>
          </div>

          <!-- Dokumen Upload Section for Draft -->
          <div v-if="canEdit" class="card bg-blue-50">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Dokumen Tambahan</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Dokumen</label>
                <select v-model="additionalDocType" required class="input-field">
                  <option value="">Pilih Jenis Dokumen</option>
                  <option v-for="doc in [
                    'sk_pangkat', 'sk_cpns', 'skp', 'surat_lulus', 'jadwal',
                    'akreditasi', 'surat_mandiri', 'surat_ijazah', 'surat_sehat'
                  ]" :key="doc" :value="doc">
                    {{ doc.replace(/_/g, ' ').toUpperCase() }}
                  </option>
                </select>
              </div>

              <FileUpload
                v-model="additionalFile"
                :preview="true"
                @preview="openImageModal"
              />

              <button
                @click="uploadAdditionalDocument"
                :disabled="!additionalDocType || !additionalFile || uploading"
                class="btn-primary w-full"
              >
                {{ uploading ? 'Mengupload...' : 'Upload Dokumen' }}
              </button>
            </div>
          </div>

          <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pendidikan</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-500">Jenjang</dt>
                <dd class="text-gray-900">{{ pengajuan.jenjang?.nama }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Program Studi</dt>
                <dd class="text-gray-900">{{ pengajuan.nama_prodi }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Perguruan Tinggi</dt>
                <dd class="text-gray-900">{{ pengajuan.perguruan_tinggi }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Akreditasi</dt>
                <dd class="text-gray-900">{{ pengajuan.akreditasi_prodi }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Lokasi</dt>
                <dd class="text-gray-900">{{ pengajuan.lokasi_pt }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Periode</dt>
                <dd class="text-gray-900">
                  {{ new Date(pengajuan.rencana_mulai).toLocaleDateString('id-ID') }}
                  -
                  {{ new Date(pengajuan.rencana_selesai).toLocaleDateString('id-ID') }}
                </dd>
              </div>
            </dl>
          </div>

          <div v-if="pengajuan.catatan_tolak" class="card bg-red-50">
            <h3 class="text-lg font-semibold text-red-900 mb-2">Catatan Penolakan</h3>
            <p class="text-red-700">{{ pengajuan.catatan_tolak }}</p>
          </div>

          <div class="card">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Dokumen</h3>
              <span class="text-sm text-gray-500">{{ pengajuan.dokumen?.length || 0 }}/9 dokumen</span>
            </div>

            <div v-if="pengajuan.dokumen && pengajuan.dokumen.length > 0" class="space-y-4">
              <div v-for="doc in pengajuan.dokumen" :key="doc.id" class="border rounded-lg p-4">
                <div class="flex justify-between items-center">
                  <div class="flex-1">
                    <span class="text-sm font-medium text-gray-700">{{ doc.file_name }}</span>
                    <span class="text-xs text-gray-500 ml-2">({{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB)</span>
                  </div>
                  <span :class="[
                    'px-2 py-1 text-xs rounded',
                    doc.status_verifikasi === 'lengkap' ? 'bg-green-100 text-green-800' :
                    doc.status_verifikasi === 'tidak_lengkap' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ getDocStatusLabel(doc.status_verifikasi) }}
                  </span>
                </div>
                <!-- Image Preview -->
                <div v-if="isDocImage(doc)" class="mt-3">
                  <img :src="getStorageUrl(doc.file_path)" :alt="doc.file_name" class="max-w-xs max-h-48 rounded border object-cover cursor-pointer" @click="openFile(doc.file_path)" />
                </div>
                <!-- PDF Icon / File Link -->
                <div v-else class="mt-3 flex items-center text-sm text-gray-500">
                  <a :href="getStorageUrl(doc.file_path)" target="_blank" class="flex items-center text-blue-600 hover:text-blue-800">
                    <svg class="w-8 h-8 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                    <span>Buka File</span>
                  </a>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500">
              <p>Belum ada dokumen yang diupload</p>
              <p class="text-sm mt-2">Silakan upload 9 dokumen wajib sebelum submit</p>
            </div>

            <!-- Document Checklist -->
            <div class="mt-4 pt-4 border-t">
              <p class="text-sm font-medium text-gray-700 mb-2">Checklist Dokumen Wajib:</p>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                <div v-for="doc in [
                  'sk_pangkat', 'sk_cpns', 'skp', 'surat_lulus', 'jadwal',
                  'akreditasi', 'surat_mandiri', 'surat_ijazah', 'surat_sehat'
                ]" :key="doc" class="flex items-center">
                  <span :class="pengajuan.dokumen?.find(d => d.jenis_dokumen === doc) ? 'text-green-500' : 'text-gray-300'">
                    {{ pengajuan.dokumen?.find(d => d.jenis_dokumen === doc) ? '✓' : '○' }}
                  </span>
                  <span class="ml-2 text-gray-600">{{ doc }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Approval History -->
          <div v-if="pengajuan.approval_history && pengajuan.approval_history.length > 0" class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Riwayat Approval</h3>
            <div class="space-y-3">
              <div v-for="history in pengajuan.approval_history" :key="history.id" class="p-3 border rounded-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ history.approver?.name || 'System' }}</p>
                    <p class="text-xs text-gray-500">{{ new Date(history.created_at).toLocaleString('id-ID') }}</p>
                  </div>
                  <span :class="[
                    'px-2 py-1 text-xs rounded',
                    history.status === 'setuju' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]">
                    {{ history.status === 'setuju' ? 'Disetujui' : 'Ditolak' }}
                  </span>
                </div>
                <p v-if="history.catatan" class="text-sm text-gray-600 mt-2">{{ history.catatan }}</p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Image Modal -->
    <ImageModal
      :show="showImageModal"
      :src="currentImageSrc"
      :alt="currentImageAlt"
      @close="showImageModal = false"
    />
  </div>
</template>
