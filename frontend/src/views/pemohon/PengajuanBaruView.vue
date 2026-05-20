<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMasterStore } from '@/stores/master'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'
import DocumentInfoTooltip from '@/components/DocumentInfoTooltip.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PDDiktiDropdown from '@/components/PDDiktiDropdown.vue'

const router = useRouter()
const masterStore = useMasterStore()
const pengajuanStore = usePengajuanStore()
const toast = useToastStore()

const form = ref({
  jenjang_id: '',
  nama_prodi: '',
  perguruan_tinggi: '',
  akreditasi_prodi: '',
  lokasi_pt: '',
  rencana_mulai: '',
  rencana_selesai: '',
})

const selectedPT = ref(null)
const selectedProdi = ref(null)
const loadingPTDetail = ref(false)
const loadingProdiList = ref(false)
const syncingPDDikti = ref(false)

const documents = ref({
  sk_pangkat: null,
  sk_cpns: null,
  skp: null,
  surat_lulus: null,
  jadwal: null,
  akreditasi: null,
  surat_mandiri: null,
  surat_ijazah: null,
  surat_sehat: null,
})

const loading = ref(false)
const saving = ref(false)
const nomorPengajuan = ref('')
const loadingDropdown = ref(false)

const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

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

onMounted(async () => {
  await masterStore.fetchAll()
  nomorPengajuan.value = await pengajuanStore.getNomorPengajuan()
})

watch(selectedPT, async (newValue) => {
  if (newValue) {
    if (typeof newValue === 'object') {
      form.value.perguruan_tinggi = newValue.nama_pt
      if (newValue.id) {
        loadingPTDetail.value = true
        try {
          const response = await api.get(`/pddikti/universitas/${newValue.id}/detail`)
          const detail = response.data.data
          form.value.lokasi_pt = detail.kab_kota || detail.provinsi || ''
        } catch (error) {
          console.error('Failed to fetch PT detail:', error)
          form.value.lokasi_pt = ''
        } finally {
          loadingPTDetail.value = false
        }
      }
    } else {
      form.value.perguruan_tinggi = newValue
    }
  } else {
    form.value.perguruan_tinggi = ''
    form.value.lokasi_pt = ''
  }
  selectedProdi.value = null
  form.value.nama_prodi = ''
  form.value.akreditasi_prodi = ''
})

watch(selectedProdi, (newValue) => {
  if (newValue) {
    if (typeof newValue === 'object') {
      form.value.nama_prodi = newValue.nama_prodi
      form.value.akreditasi_prodi = newValue.akreditasi || ''
    } else {
      form.value.nama_prodi = newValue
    }
  } else {
    form.value.nama_prodi = ''
    form.value.akreditasi_prodi = ''
  }
})

function openImageModal(url) {
  currentImageSrc.value = url
  currentImageAlt.value = 'Dokumen'
  showImageModal.value = true
}

async function handleDropdownFocus(type) {
  if (type === 'jenjang' && masterStore.jenjang.length === 0 && !masterStore.loading) {
    loadingDropdown.value = true
    try {
      await masterStore.fetchJenjang()
    } finally {
      loadingDropdown.value = false
    }
  }
  if (type === 'akreditasi' && masterStore.akreditasi.length === 0 && !masterStore.loading) {
    loadingDropdown.value = true
    try {
      await masterStore.fetchAkreditasi()
    } finally {
      loadingDropdown.value = false
    }
  }
}

function getPreviewUrl(file) {
  if (!file || !file.type?.startsWith('image/')) return ''
  try {
    return URL?.createObjectURL?.(file) || ''
  } catch {
    return ''
  }
}

function handleFileUpload(docKey, event) {
  const file = event.target.files?.[0]
  if (!file) return

  // Check file size (5MB)
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    toast.error('Ukuran file terlalu besar. Maksimum 5MB')
    return
  }

  // Check file type
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
  if (!allowedTypes.includes(file.type)) {
    toast.error('Tipe file tidak valid. Gunakan PDF, JPG, atau PNG')
    return
  }

  documents.value[docKey] = file
}

function removeFile(docKey) {
  documents.value[docKey] = null
}

// Sync PDDikti Data
async function syncPDDiktiData() {
  syncingPDDikti.value = true
  try {
    // Sync popular universities
    const keywords = ['Universitas', 'Institut', 'Sekolah Tinggi']
    let totalSynced = 0

    for (const keyword of keywords.slice(0, 1)) { // Limit to 1 keyword for now
      const response = await api.post('/admin/pddikti-sync/universitas', {
        keyword: keyword,
        limit: 50
      })
      totalSynced += response.data.data.total || 0
    }

    toast.success(`Berhasil sync ${totalSynced} data perguruan tinggi`)
  } catch (error) {
    console.error('Sync error:', error)
    toast.warning('Fitur sync hanya untuk admin. Data akan diambil langsung dari PDDikti.')
  } finally {
    syncingPDDikti.value = false
  }
}

// Get akreditasi badge class
function getAkreditasiBadgeClass(akreditasi) {
  const classes = {
    'A': 'bg-green-600 text-white',
    'B': 'bg-blue-600 text-white',
    'C': 'bg-yellow-500 text-white',
    'Unggul': 'bg-green-700 text-white',
    'Baik Sekali': 'bg-blue-700 text-white',
    'Baik': 'bg-cyan-600 text-white',
  }
  return classes[akreditasi] || 'bg-gray-200 text-gray-700'
}

async function saveDraftOnly() {
  saving.value = true
  try {
    const response = await pengajuanStore.createPengajuan(form.value)
    const pengajuanId = response.id

    let uploadedCount = 0
    let failedDocs = []

    for (const doc of jenisDokumenList) {
      const file = documents.value[doc.key]
      if (file) {
        if (!(file instanceof File)) {
          console.error(`Invalid file object for ${doc.key}:`, file)
          failedDocs.push(doc.label)
          continue
        }

        const formData = new FormData()
        formData.append('file', file, file.name)
        formData.append('jenis_dokumen', doc.key)

        try {
          const result = await api.post(`/pengajuan/${pengajuanId}/dokumen`, formData, {
            headers: { 'Content-Type': undefined }
          })
          if (result.status === 201) {
            uploadedCount++
          }
        } catch (docError) {
          console.error(`Gagal upload ${doc.label}:`, docError.response?.data)
          let errorMsg = ''
          if (docError.response?.data?.errors) {
            const errors = docError.response.data.errors
            errorMsg = Object.values(errors).flat().join(', ')
          } else if (docError.response?.data?.message) {
            errorMsg = docError.response.data.message
          } else {
            errorMsg = docError.message || 'Unknown error'
          }
          failedDocs.push(`${doc.label}: ${errorMsg}`)
        }
      }
    }

    if (failedDocs.length > 0) {
      toast.warning(`Beberapa dokumen gagal diupload. ${uploadedCount} berhasil, ${failedDocs.length} gagal.`, 5000)
    } else if (uploadedCount > 0) {
      toast.success(`Draft berhasil disimpan dengan ${uploadedCount} dokumen. Sisa dokumen bisa ditambahkan nanti.`)
    } else {
      toast.success('Draft berhasil disimpan. Anda bisa menambahkan dokumen nanti.')
    }
    router.push(`/pengajuan/${pengajuanId}`)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan draft')
  } finally {
    saving.value = false
  }
}

const showIncompleteConfirm = ref(false)
const incompleteDocsList = ref([])

function checkIncompleteDocs() {
  const missingDocs = jenisDokumenList.filter(doc => !documents.value[doc.key])

  if (missingDocs.length > 0) {
    incompleteDocsList.value = missingDocs
    showIncompleteConfirm.value = true
    return false
  }
  return true
}

async function saveWithDocuments() {
  // Check for incomplete documents
  const missingDocs = jenisDokumenList.filter(doc => !documents.value[doc.key])

  if (missingDocs.length > 0) {
    incompleteDocsList.value = missingDocs
    showIncompleteConfirm.value = true
    return
  }

  await proceedWithSubmission()
}

async function proceedWithSubmission() {
  showIncompleteConfirm.value = false
  saving.value = true

  try {
    const response = await pengajuanStore.createPengajuan(form.value)
    const pengajuanId = response.id

    for (const doc of jenisDokumenList) {
      const file = documents.value[doc.key]
      if (file) {
        if (!(file instanceof File)) {
          throw new Error(`Invalid file object for ${doc.label}`)
        }

        const formData = new FormData()
        formData.append('file', file, file.name)
        formData.append('jenis_dokumen', doc.key)

        await api.post(`/pengajuan/${pengajuanId}/dokumen`, formData, {
          headers: { 'Content-Type': undefined }
        })
      }
    }

    const uploadedDocCount = jenisDokumenList.filter(doc => documents.value[doc.key]).length

    if (uploadedDocCount < 9) {
      toast.warning(`Pengajuan dikirim dengan ${uploadedDocCount}/9 dokumen. Silakan lengkapi dokumen lainnya melalui menu Riwayat.`, 8000)
    } else {
      toast.success('Pengajuan berhasil dikirim dengan semua dokumen lengkap!')
    }

    router.push(`/pengajuan/${pengajuanId}`)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan pengajuan')
  } finally {
    saving.value = false
  }
}

function cancelIncompleteConfirm() {
  showIncompleteConfirm.value = false
}

const uploadedCount = computed(() => {
  return Object.values(documents.value).filter(d => d).length
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9);
}
</style>

<template>
  <MainLayout>
    <Breadcrumb :current-page="'Pengajuan Baru'" />

    <div class="mb-6 animate-fade-in">
      <h2 class="text-2xl font-bold text-secondary-800">Pengajuan Baru</h2>
      <p class="text-secondary-500 mt-1">Nomor Pengajuan: <span class="font-mono text-primary-600">{{ nomorPengajuan }}</span></p>
    </div>

    <form @submit.prevent class="space-y-6">
      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Data Pendidikan -->
        <div class="card animate-slide-up">
          <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
              <i class="ri-graduation-cap-line text-lg text-primary-600"></i>
              Data Pendidikan
            </h3>
          </div>
          <div class="card-body">
            <!-- Sync Button -->
            <div class="flex items-center justify-end mb-4 pb-3 border-b border-secondary-200">
              <button
                type="button"
                @click="syncPDDiktiData"
                :disabled="syncingPDDikti"
                class="flex items-center gap-2 text-xs text-primary-600 hover:text-primary-700 transition-colors"
              >
                <LoadingSpinner v-if="syncingPDDikti" size="xs" />
                <i v-else class="ri-refresh-line"></i>
                <span>Sync Data PDDikti</span>
              </button>
            </div>

            <div class="space-y-4">
              <!-- 1. Jenjang -->
              <div>
                <label class="input-label">Jenjang</label>
                <div class="relative">
                  <select
                    v-model="form.jenjang_id"
                    @focus="handleDropdownFocus('jenjang')"
                    required
                    class="select-field appearance-none pr-10"
                    :disabled="loadingDropdown || masterStore.loading"
                  >
                    <option value="">Pilih Jenjang</option>
                    <option v-if="loadingDropdown || masterStore.loading" disabled>Loading...</option>
                    <option v-for="j in masterStore.jenjang" :key="j.id" :value="j.id">
                      {{ j.nama }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <LoadingSpinner v-if="loadingDropdown || masterStore.loading" size="sm" />
                    <i v-else class="ri-arrow-down-s-line text-secondary-400"></i>
                  </div>
                </div>
              </div>

              <!-- 2. Perguruan Tinggi -->
              <div>
                <label class="input-label">Perguruan Tinggi</label>
                <PDDiktiDropdown
                  v-model="selectedPT"
                  type="universitas"
                  placeholder="Cari nama perguruan tinggi..."
                  :required="true"
                />
              </div>

              <!-- 3. Rencana Mulai & Selesai -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="input-label">Rencana Mulai</label>
                  <input v-model="form.rencana_mulai" type="date" required class="input-field" />
                </div>
                <div>
                  <label class="input-label">Rencana Selesai</label>
                  <input v-model="form.rencana_selesai" type="date" required class="input-field" />
                </div>
              </div>

              <!-- 4. Lokasi PT (Auto-fill) -->
              <div>
                <label class="input-label">Lokasi Perguruan Tinggi</label>
                <div class="relative">
                  <input
                    v-model="form.lokasi_pt"
                    type="text"
                    class="input-field pr-10"
                    :class="{ 'pl-10': loadingPTDetail }"
                    placeholder="Otomatis terisi saat memilih perguruan tinggi"
                    readonly
                  />
                  <div v-if="loadingPTDetail" class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <LoadingSpinner size="sm" />
                  </div>
                  <div v-else-if="form.lokasi_pt" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <i class="ri-check-line text-success text-lg"></i>
                  </div>
                </div>
              </div>

              <!-- 5. Program Studi -->
              <div>
                <label class="input-label">Program Studi</label>
                <PDDiktiDropdown
                  v-model="selectedProdi"
                  type="prodi"
                  :id-pt="selectedPT?.id || selectedPT?.id"
                  placeholder="Cari program studi di PDDikti..."
                  :disabled="!selectedPT || loadingPTDetail"
                  :required="false"
                />
              </div>

              <!-- 6. Akreditasi Prodi (Auto-fill) -->
              <div>
                <label class="input-label">Akreditasi Prodi</label>
                <div class="relative">
                  <input
                    v-model="form.akreditasi_prodi"
                    type="text"
                    class="input-field pr-10"
                    placeholder="Otomatis terisi saat memilih program studi"
                    readonly
                  />
                  <div v-if="form.akreditasi_prodi" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <span class="px-2 py-1 rounded text-xs font-bold" :class="getAkreditasiBadgeClass(form.akreditasi_prodi)">
                      {{ form.akreditasi_prodi }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Dokumen -->
        <div class="card animate-slide-up" style="animation-delay: 50ms;">
          <div class="card-header">
            <div class="flex items-center justify-between">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-file-upload-line text-lg text-primary-600"></i>
                Upload Dokumen
              </h3>
              <span class="badge badge-primary">{{ uploadedCount }}/9</span>
            </div>
            <p class="text-sm text-secondary-500 mt-1">Max 5MB per file. PDF, JPG, PNG.</p>
          </div>
          <div class="card-body">
            <!-- Minimalis List Layout -->
            <div class="space-y-2">
              <div
                v-for="(doc, index) in jenisDokumenList"
                :key="doc.key"
                class="flex items-center gap-2 p-2 border rounded-lg"
                :class="documents[doc.key] ? 'border-success bg-green-50' : 'border-secondary-200'"
              >
                <!-- Nomor -->
                <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="documents[doc.key] ? 'bg-success text-white' : 'bg-secondary-200 text-secondary-600'">
                  {{ index + 1 }}
                </span>

                <!-- Info Dokumen -->
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-secondary-800 truncate">{{ doc.label }}</p>
                  <p v-if="documents[doc.key]" class="text-xs text-secondary-500">
                    {{ documents[doc.key].name }} ({{ (documents[doc.key].size / 1024 / 1024).toFixed(2) }} MB)
                  </p>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center gap-1 flex-shrink-0">
                  <DocumentInfoTooltip
                    :title="doc.label"
                    :requirements="doc.requirements"
                    :notes="doc.notes"
                  />

                  <!-- Upload Button -->
                  <label class="cursor-pointer p-1.5 rounded-lg transition-colors" :class="documents[doc.key] ? 'bg-success text-white hover:bg-green-600' : 'bg-primary-600 text-white hover:bg-primary-700'">
                    <i :class="documents[doc.key] ? 'ri-check-line' : 'ri-upload-line'" class="text-sm"></i>
                    <input
                      type="file"
                      class="hidden"
                      accept=".pdf,.jpg,.jpeg,.png"
                      @change="(e) => handleFileUpload(doc.key, e)"
                    />
                  </label>

                  <!-- Remove Button (jika sudah upload) -->
                  <button
                    v-if="documents[doc.key]"
                    @click="removeFile(doc.key)"
                    class="p-1.5 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                  >
                    <i class="ri-delete-bin-line text-sm"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-4 pt-3 border-t border-secondary-200">
              <div class="flex items-center justify-between text-xs text-secondary-600 mb-1">
                <span>Progress Dokumen</span>
                <span class="font-semibold" :class="uploadedCount === 9 ? 'text-success' : 'text-primary-600'">{{ uploadedCount }} dari 9 lengkap</span>
              </div>
              <div class="w-full bg-secondary-200 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-300" :class="uploadedCount === 9 ? 'bg-success' : 'bg-primary-600'" :style="{ width: (uploadedCount / 9 * 100) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-3 animate-slide-up" style="animation-delay: 100ms;">
        <button type="button" @click.prevent="saveDraftOnly" :disabled="saving" class="btn btn-secondary flex-1 justify-center">
          <LoadingSpinner v-if="saving" size="sm" />
          <span v-else class="flex items-center gap-2">
            <i class="ri-save-line"></i>
            <span>Simpan Draft</span>
          </span>
        </button>
        <button type="button" @click.prevent="saveWithDocuments" :disabled="saving" class="btn btn-primary flex-1 justify-center">
          <LoadingSpinner v-if="saving" size="sm" />
          <span v-else class="flex items-center gap-2">
            <i class="ri-send-plane-fill"></i>
            <span>Simpan & Kirim</span>
          </span>
        </button>
        <router-link to="/pengajuan" class="btn btn-ghost flex-1 justify-center">
          <i class="ri-close-line"></i>
          <span>Batal</span>
        </router-link>
      </div>
    </form>

    <ImageModal
      :show="showImageModal"
      :src="currentImageSrc"
      :alt="currentImageAlt"
      @close="showImageModal = false"
    />

    <!-- Incomplete Documents Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showIncompleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelIncompleteConfirm"></div>
          <div class="relative bg-white rounded-2xl shadow-soft max-w-md w-full overflow-hidden animate-slide-up">
            <!-- Header -->
            <div class="p-4 border-b border-secondary-100">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                  <i class="ri-alert-line text-xl text-amber-600"></i>
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-bold text-secondary-800">Dokumen Belum Lengkap</h3>
                  <p class="text-xs text-secondary-500">{{ incompleteDocsList.length }} dari 9 dokumen belum diupload</p>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-4">
              <p class="text-sm text-secondary-700 mb-3">
                Anda dapat menambahkan dokumen lainnya nanti melalui menu Riwayat Pengajuan.
              </p>
              <div class="max-h-48 overflow-y-auto space-y-1.5">
                <div v-for="doc in incompleteDocsList" :key="doc.key" class="flex items-center gap-2 p-2 bg-secondary-50 rounded-lg">
                  <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">
                    <i class="ri-close-line"></i>
                  </span>
                  <p class="text-xs text-secondary-700">{{ doc.label }}</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-4 bg-secondary-50 flex gap-2">
              <button @click="cancelIncompleteConfirm" class="btn btn-secondary flex-1">
                <i class="ri-arrow-left-line"></i>
                Lengkapi Dulu
              </button>
              <button @click="proceedWithSubmission" class="btn btn-primary flex-1">
                <i class="ri-send-plane-fill"></i>
                Tetap Kirim
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </MainLayout>
</template>
