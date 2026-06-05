<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
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
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()
const masterStore = useMasterStore()
const pengajuanStore = usePengajuanStore()
const toast = useToastStore()

// Debounce utility
function debounce(fn, delay) {
  let timeoutId
  return function (...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(this, args), delay)
  }
}

// Page header subtitle with nomor pengajuan
const headerSubtitle = computed(() => {
  return nomorPengajuan.value ? `Nomor: ${nomorPengajuan.value}` : 'Isi formulir untuk mengajukan izin belajar mandiri'
})

// Show education fields only after jenjang is selected
const showEducationFields = computed(() => {
  return !!form.value.jenjang_id
})

// Get selected jenjang name
const selectedJenjangName = computed(() => {
  if (!form.value.jenjang_id) return ''
  const jenjang = masterStore.jenjang.find(j => j.id === form.value.jenjang_id)
  return jenjang?.nama || ''
})

const form = ref({
  jenjang_id: '',
  nama_prodi: '',
  perguruan_tinggi_id: '',
  perguruan_tinggi: '',
  akreditasi_prodi: '',
  lokasi_pt: '',
  rencana_mulai: '',
  rencana_selesai: '',
})

// Watch jenjang changes and reset related fields
watch(() => form.value.jenjang_id, (newVal, oldVal) => {
  // Only reset if jenjang actually changed (not on initial load)
  if (oldVal && newVal !== oldVal) {
    // Reset PT and prodi fields when jenjang changes
    selectedPT.value = null
    form.value.perguruan_tinggi_id = ''
    form.value.perguruan_tinggi = ''
    form.value.nama_prodi = ''
    form.value.akreditasi_prodi = ''
    form.value.lokasi_pt = ''
    filteredPT.value = []
    filteredProdi.value = []
    showPTDropdown.value = false
    showProdiDropdown.value = false

    toast.info(`Jenjang diubah. Silakan pilih perguruan tinggi jenjang ${selectedJenjangName.value}`)
  }
})

// Dropdown states
const ptSearchKeyword = ref('')
const prodiSearchKeyword = ref('')
const showPTDropdown = ref(false)
const showProdiDropdown = ref(false)
const filteredPT = ref([])
const filteredProdi = ref([])
const selectedPT = ref(null)
const loadingPT = ref(false)
const loadingProdi = ref(false)
const ptDropdownMessage = ref('')
const prodiDropdownMessage = ref('')

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

  // Close dropdowns when clicking outside - use a more specific check
  document.addEventListener('click', handleClickOutside)
})

function handleClickOutside(event) {
  // Check if click is outside any dropdown container
  const ptDropdown = event.target.closest('[data-dropdown="pt"]')
  const prodiDropdown = event.target.closest('[data-dropdown="prodi"]')

  if (!ptDropdown && !prodiDropdown) {
    closeDropdowns()
  }
}

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
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

// Perguruan Tinggi Dropdown
const debouncedSearchPT = ref(null)

async function searchPerguruanTinggi(keyword) {
  ptSearchKeyword.value = keyword

  if (keyword.length >= 2) {
    loadingPT.value = true
    ptDropdownMessage.value = ''
    try {
      const results = await masterStore.fetchPerguruanTinggi(keyword)
      filteredPT.value = results

      if (results.length === 0) {
        ptDropdownMessage.value = 'Tidak ditemukan. Ketik untuk mencari perguruan tinggi lain.'
      } else {
        ptDropdownMessage.value = `Ditemukan ${results.length} perguruan tinggi`
      }
      showPTDropdown.value = true
    } catch (error) {
      ptDropdownMessage.value = 'Gagal memuat data. Silakan coba lagi.'
      console.error('Error searching PT:', error)
    } finally {
      loadingPT.value = false
    }
  } else {
    filteredPT.value = []
    showPTDropdown.value = false
    ptDropdownMessage.value = keyword.length > 0 ? 'Ketik minimal 2 karakter untuk mencari...' : ''
  }
}

// Create debounced version (500ms delay)
const debouncedSearchPerguruanTinggi = debounce(searchPerguruanTinggi, 500)

function selectPerguruanTinggi(pt) {
  selectedPT.value = pt
  form.value.perguruan_tinggi_id = pt.id
  form.value.perguruan_tinggi = pt.nama_pt
  form.value.lokasi_pt = pt.kab_kota && pt.provinsi ? `${pt.kab_kota}, ${pt.provinsi}` : ''
  showPTDropdown.value = false
  ptSearchKeyword.value = ''
  // Reset prodi when PT changes
  form.value.nama_prodi = ''
  filteredProdi.value = []
  prodiDropdownMessage.value = ''
  // Auto-load programs for selected PT
  loadProdiForPT(pt.id)
}

function clearPerguruanTinggi() {
  selectedPT.value = null
  form.value.perguruan_tinggi_id = ''
  form.value.perguruan_tinggi = ''
  form.value.lokasi_pt = ''
  form.value.nama_prodi = ''
  filteredPT.value = []
  filteredProdi.value = []
}

// Prodi Dropdown
// Get jenjang code from selected jenjang_id
function getJenjangCode() {
  if (!form.value.jenjang_id) return null
  const jenjang = masterStore.jenjang.find(j => j.id === form.value.jenjang_id)
  return jenjang?.kode || jenjang?.nama || null
}

// Filter prodi by selected jenjang (client-side)
function filterProdiByJenjang(prodiList) {
  const jenjangCode = getJenjangCode()
  if (!jenjangCode) return prodiList

  // Filter by jenjang - match by code or name
  return prodiList.filter(prodi => {
    const prodiJenjang = prodi.jenjang?.toUpperCase() || ''
    return prodiJenjang === jenjangCode.toUpperCase() ||
           prodiJenjang.includes(jenjangCode.toUpperCase()) ||
           jenjangCode.toUpperCase().includes(prodiJenjang)
  })
}

// Load all programs for a specific PT (when PT is selected)
async function loadProdiForPT(ptId) {
  loadingProdi.value = true
  prodiDropdownMessage.value = 'Memuat program studi...'
  showProdiDropdown.value = true
  try {
    const results = await masterStore.fetchProdi(ptId, '')
    // Filter by jenjang
    const filtered = filterProdiByJenjang(results)
    filteredProdi.value = filtered

    if (filtered.length === 0) {
      prodiDropdownMessage.value = `Tidak ada program studi jenjang ${selectedJenjangName.value} untuk perguruan tinggi ini.`
    } else {
      prodiDropdownMessage.value = `Ditemukan ${filtered.length} program studi jenjang ${selectedJenjangName.value} di ${selectedPT.value?.nama_pt}`
    }
  } catch (error) {
    prodiDropdownMessage.value = 'Gagal memuat data. Silakan coba lagi.'
    console.error('Error loading Prodi for PT:', error)
  } finally {
    loadingProdi.value = false
  }
}

async function searchProdi(keyword) {
  prodiSearchKeyword.value = keyword

  if (keyword.length >= 2) {
    loadingProdi.value = true
    prodiDropdownMessage.value = ''
    try {
      const ptId = selectedPT.value ? selectedPT.value.id : null
      const results = await masterStore.fetchProdi(ptId, keyword)
      // Filter by jenjang
      const filtered = filterProdiByJenjang(results)
      filteredProdi.value = filtered

      if (filtered.length === 0) {
        const jenjangText = selectedJenjangName.value ? ` jenjang ${selectedJenjangName.value}` : ''
        prodiDropdownMessage.value = ptId
          ? `Tidak ada program studi${jenjangText} untuk perguruan tinggi ini. Coba kata kunci lain.`
          : 'Tidak ditemukan. Pilih perguruan tinggi terlebih dahulu untuk hasil yang lebih spesifik.'
      } else {
        prodiDropdownMessage.value = `Ditemukan ${filtered.length} program studi jenjang ${selectedJenjangName.value}`
      }
      showProdiDropdown.value = true
    } catch (error) {
      prodiDropdownMessage.value = 'Gagal memuat data. Silakan coba lagi.'
      console.error('Error searching Prodi:', error)
    } finally {
      loadingProdi.value = false
    }
  } else {
    filteredProdi.value = []
    showProdiDropdown.value = false
    prodiDropdownMessage.value = keyword.length > 0 ? 'Ketik minimal 2 karakter untuk mencari...' : ''
  }
}

// Create debounced version (500ms delay)
const debouncedSearchProdi = debounce(searchProdi, 500)

function selectProdi(prodi) {
  form.value.nama_prodi = prodi.nama_prodi
  // Auto-fill akreditasi from prodi data
  if (prodi.akreditasi) {
    form.value.akreditasi_prodi = prodi.akreditasi
  }
  showProdiDropdown.value = false
  prodiSearchKeyword.value = ''
}

function closeDropdowns() {
  showPTDropdown.value = false
  showProdiDropdown.value = false
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
    <Breadcrumb />

    <PageHeader
      title="Buat Pengajuan Baru"
      :subtitle="headerSubtitle"
    />

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
            <div class="space-y-4">
              <!-- 1. Jenjang -->
              <div>
                <label class="input-label">Jenjang</label>
                <div class="relative">
                  <select
                    v-model="form.jenjang_id"
                    required
                    class="select-field appearance-none pr-10"
                  >
                    <option value="">Pilih Jenjang</option>
                    <option v-if="masterStore.loading" disabled>Loading...</option>
                    <option v-else-if="masterStore.jenjang.length === 0" disabled>Tidak ada data</option>
                    <option v-for="j in masterStore.jenjang" :key="j.id" :value="j.id">
                      {{ j.nama }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <LoadingSpinner v-if="masterStore.loading" size="sm" />
                    <i v-else class="ri-arrow-down-s-line text-secondary-400"></i>
                  </div>
                </div>
              </div>

              <!-- Education Fields (readonly until jenjang is selected) -->
              <div class="space-y-4">

                <!-- Info Banner (shows when jenjang is selected) -->
                <div v-if="showEducationFields" class="flex items-center gap-2 p-3 bg-success-50 border border-success-200 rounded-lg mb-4">
                  <i class="ri-check-double-line text-success-600"></i>
                  <p class="text-sm text-success-700">
                    Menampilkan program studi jenjang <span class="font-semibold">{{ selectedJenjangName }}</span>
                  </p>
                </div>

                <!-- Info Banner (shows when jenjang NOT selected) -->
                <div v-else class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg mb-4">
                  <i class="ri-information-line text-amber-600"></i>
                  <p class="text-sm text-amber-700">
                    Pilih jenjang terlebih dahulu untuk mengaktifkan formulir
                  </p>
                </div>

              <!-- 2. Perguruan Tinggi -->
              <div class="relative" data-dropdown="pt">
                <label class="input-label">Perguruan Tinggi <span class="text-xs text-primary-600 font-normal">(Ketik untuk mencari dari database PDDikti)</span></label>
                <div class="relative">
                  <input
                    v-model="form.perguruan_tinggi"
                    @input="(e) => { if(showEducationFields) { debouncedSearchPerguruanTinggi(e.target.value) } }"
                    @focus="() => showEducationFields && form.perguruan_tinggi && searchPerguruanTinggi(form.perguruan_tinggi)"
                    type="text"
                    required
                    :readonly="!showEducationFields"
                    :class="{'input-field': !showEducationFields, 'input-field cursor-text': showEducationFields, 'bg-secondary-100 cursor-not-allowed': !showEducationFields}"
                    :placeholder="showEducationFields ? 'Ketik untuk mencari perguruan tinggi...' : 'Pilih jenjang terlebih dahulu...'"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <LoadingSpinner v-if="loadingPT" size="sm" />
                    <i v-else class="ri-search-line text-secondary-400"></i>
                  </div>
                </div>
                <!-- Dropdown Results -->
                <div
                  v-if="showPTDropdown"
                  class="absolute z-10 w-full mt-1 bg-white border border-primary-300 rounded-lg shadow-xl max-h-72 overflow-hidden"
                >
                  <!-- Message Header -->
                  <div v-if="ptDropdownMessage" class="px-3 py-2 bg-primary-50 border-b border-primary-100 text-xs text-primary-700">
                    {{ ptDropdownMessage }}
                  </div>
                  <!-- Loading State -->
                  <div v-if="loadingPT" class="p-4 text-center text-secondary-500 text-sm">
                    <i class="ri-loader-4-line animate-spin text-lg"></i>
                    <p class="mt-1">Mencari perguruan tinggi...</p>
                  </div>
                  <!-- Results -->
                  <div v-else-if="filteredPT.length > 0" class="max-h-60 overflow-y-auto">
                    <div
                      v-for="pt in filteredPT"
                      :key="pt.id"
                      @click="selectPerguruanTinggi(pt)"
                      class="p-3 hover:bg-primary-50 cursor-pointer border-b border-secondary-100 last:border-b-0 transition-colors"
                    >
                      <p class="text-sm font-semibold text-secondary-800">{{ pt.nama_pt }}</p>
                      <p class="text-xs text-secondary-500 flex items-center gap-1 mt-0.5">
                        <i class="ri-map-pin-line"></i>
                        {{ pt.kab_kota }}, {{ pt.provinsi }}
                      </p>
                    </div>
                  </div>
                  <!-- No Results -->
                  <div v-else-if="!loadingPT" class="p-4 text-center text-secondary-500 text-sm">
                    <i class="ri-search-2-line text-2xl text-secondary-300 mb-1"></i>
                    <p>{{ ptDropdownMessage || 'Tidak ditemukan' }}</p>
                  </div>
                </div>
                <!-- Selected PT Badge -->
                <div v-if="selectedPT" class="mt-2 flex items-center gap-2">
                  <span class="badge badge-success text-xs">
                    <i class="ri-check-line mr-1"></i>
                    {{ selectedPT.nama_pt }}
                  </span>
                  <button @click="clearPerguruanTinggi" class="text-xs text-red-500 hover:text-red-700">
                    <i class="ri-close-line"></i> Hapus
                  </button>
                </div>
              </div>

              <!-- 3. Program Studi -->
              <div class="relative" data-dropdown="prodi">
                <label class="input-label">Program Studi <span class="text-xs text-primary-600 font-normal">(Ketik untuk mencari dari database PDDikti)</span></label>
                <div class="relative">
                  <input
                    v-model="form.nama_prodi"
                    @input="(e) => { if(showEducationFields) { debouncedSearchProdi(e.target.value) } }"
                    @focus="() => showEducationFields && selectedPT && !form.nama_prodi && loadProdiForPT(selectedPT.id)"
                    type="text"
                    required
                    :readonly="!showEducationFields"
                    :class="{'input-field': !showEducationFields, 'input-field cursor-text': showEducationFields, 'bg-secondary-100 cursor-not-allowed': !showEducationFields}"
                    :placeholder="showEducationFields ? (selectedPT ? 'Ketik untuk mencari program studi...' : 'Pilih perguruan tinggi terlebih dahulu...') : 'Pilih jenjang terlebih dahulu...'"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <LoadingSpinner v-if="loadingProdi" size="sm" />
                    <i v-else class="ri-search-line text-secondary-400"></i>
                  </div>
                </div>
                <!-- Dropdown Results -->
                <div
                  v-if="showProdiDropdown"
                  class="absolute z-10 w-full mt-1 bg-white border border-primary-300 rounded-lg shadow-xl max-h-72 overflow-hidden"
                >
                  <!-- Message Header -->
                  <div v-if="prodiDropdownMessage" class="px-3 py-2 bg-primary-50 border-b border-primary-100 text-xs text-primary-700">
                    {{ prodiDropdownMessage }}
                  </div>
                  <!-- Loading State -->
                  <div v-if="loadingProdi" class="p-4 text-center text-secondary-500 text-sm">
                    <i class="ri-loader-4-line animate-spin text-lg"></i>
                    <p class="mt-1">Mencari program studi...</p>
                  </div>
                  <!-- Results -->
                  <div v-else-if="filteredProdi.length > 0" class="max-h-60 overflow-y-auto">
                    <div
                      v-for="prodi in filteredProdi"
                      :key="prodi.id"
                      @click="selectProdi(prodi)"
                      class="p-3 hover:bg-primary-50 cursor-pointer border-b border-secondary-100 last:border-b-0 transition-colors"
                    >
                      <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-semibold text-secondary-800">{{ prodi.nama_prodi }}</p>
                          <p class="text-xs text-secondary-500 flex items-center gap-2 mt-0.5">
                            <span class="badge badge-xs badge-primary">{{ prodi.jenjang }}</span>
                            <span v-if="prodi.perguruan_tinggi" class="truncate">{{ prodi.perguruan_tinggi.nama_pt }}</span>
                          </p>
                        </div>
                        <span v-if="prodi.akreditasi" class="badge badge-xs flex-shrink-0"
                          :class="{
                            'badge-success': ['A', 'Unggul', 'Baik Sekali'].includes(prodi.akreditasi),
                            'badge-warning': ['B', 'Baik'].includes(prodi.akreditasi),
                            'badge-danger': ['C', 'Cukup'].includes(prodi.akreditasi)
                          }">
                          {{ prodi.akreditasi }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <!-- No Results -->
                  <div v-else-if="!loadingProdi" class="p-4 text-center text-secondary-500 text-sm">
                    <i class="ri-search-2-line text-2xl text-secondary-300 mb-1"></i>
                    <p>{{ prodiDropdownMessage || 'Tidak ditemukan' }}</p>
                  </div>
                </div>
                <!-- Hint -->
                <p v-if="!selectedPT" class="text-xs text-amber-600 mt-1">
                  <i class="ri-lightbulb-line"></i>
                  Pilih perguruan tinggi terlebih dahulu untuk melihat program studi yang tersedia.
                </p>
                <p v-else class="text-xs text-success-600 mt-1">
                  <i class="ri-check-line"></i>
                  Klik pada kolom untuk melihat semua program studi di {{ selectedPT.nama_pt }}, atau ketik untuk mencari.
                </p>
              </div>

              <!-- 4. Akreditasi Prodi -->
              <div>
                <label class="input-label">Akreditasi Prodi <span class="text-xs text-success-600 font-normal">(Otomatis dari program studi)</span></label>
                <div class="relative">
                  <select
                    v-model="form.akreditasi_prodi"
                    @focus="handleDropdownFocus('akreditasi')"
                    required
                    :disabled="!showEducationFields"
                    class="select-field appearance-none pr-10"
                    :class="{'bg-secondary-100 cursor-not-allowed': !showEducationFields}"
                  >
                    <option value="">Pilih Akreditasi Prodi</option>
                    <option v-if="loadingDropdown || masterStore.loading" disabled>Loading...</option>
                    <option v-for="a in masterStore.akreditasi" :key="a.value" :value="a.value">
                      {{ a.label }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <LoadingSpinner v-if="loadingDropdown || masterStore.loading" size="sm" />
                    <i v-else class="ri-arrow-down-s-line text-secondary-400"></i>
                  </div>
                </div>
                <p v-if="form.akreditasi_prodi" class="text-xs mt-1">
                  <span class="badge badge-xs"
                    :class="{
                      'badge-success': ['A', 'Unggul', 'Baik Sekali', 'Terakreditasi Unggul'].includes(form.akreditasi_prodi),
                      'badge-warning': ['B', 'Baik', 'Terakreditasi'].includes(form.akreditasi_prodi),
                      'badge-danger': ['C', 'Cukup'].includes(form.akreditasi_prodi)
                    }">
                    {{ form.akreditasi_prodi }}
                  </span>
                  <span class="text-secondary-500 ml-1">- Dipilih</span>
                  <span v-if="form.nama_prodi" class="text-success-600 ml-1">
                    <i class="ri-magic-line"></i> Otomatis dari program studi
                  </span>
                </p>
                <p v-else class="text-xs text-secondary-500 mt-1">
                  <i class="ri-information-line text-primary-500"></i>
                  Akreditasi akan otomatis terisi saat memilih program studi
                </p>
              </div>

              <!-- 5. Lokasi Perguruan Tinggi -->
              <div>
                <label class="input-label">Lokasi Perguruan Tinggi <span class="text-xs text-primary-600 font-normal">(Auto dari database)</span></label>
                <div class="relative">
                  <input
                    v-model="form.lokasi_pt"
                    type="text"
                    required
                    :readonly="!showEducationFields"
                    :class="{'input-field': showEducationFields, 'input-field bg-secondary-100 cursor-not-allowed': !showEducationFields}"
                    placeholder="Otomatis terisi dari perguruan tinggi yang dipilih"
                  />
                  <div v-if="form.lokasi_pt" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <i class="ri-map-pin-line text-success-500"></i>
                  </div>
                </div>
                <p class="text-xs text-secondary-500 mt-1">
                  <i class="ri-information-line text-primary-500"></i>
                  Lokasi otomatis terisi setelah memilih perguruan tinggi. Bisa diedit manual jika perlu.
                </p>
              </div>

              <!-- 6. Rencana Mulai & Selesai -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="input-label">Rencana Mulai</label>
                  <input v-model="form.rencana_mulai" type="date" required :readonly="!showEducationFields" :class="{'input-field': showEducationFields, 'input-field bg-secondary-100 cursor-not-allowed': !showEducationFields}" />
                </div>
                <div>
                  <label class="input-label">Rencana Selesai</label>
                  <input v-model="form.rencana_selesai" type="date" required :readonly="!showEducationFields" :class="{'input-field': showEducationFields, 'input-field bg-secondary-100 cursor-not-allowed': !showEducationFields}" />
                </div>
              </div>
              </div>
              <!-- End Education Fields -->
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
