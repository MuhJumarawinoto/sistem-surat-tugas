<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePgaStore } from '@/stores/pga'
import { useMasterStore } from '@/stores/master'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'
import api from '@/services/api'

// Debounce utility function
function debounce(fn, delay) {
  let timeoutId
  return function (...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(this, args), delay)
  }
}

// Format date string to YYYY-MM-DD for date input
function formatDateForInput(dateStr) {
  if (!dateStr) return ''

  // If already in YYYY-MM-DD format, return as is
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return dateStr
  }

  // Try to parse the date
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return ''

  // Format to YYYY-MM-DD (local time, not UTC)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const router = useRouter()
const route = useRoute()
const pgaStore = usePgaStore()
const masterStore = useMasterStore()
const toast = useToastStore()
const authStore = useAuthStore()

const loading = ref(false)
const loadingDocs = ref(false)
const submitting = ref(false)
const documentTypes = ref([])

const form = ref({
  jenjang_pendidikan_id: '',
  nama_prodi: '',
  perguruan_tinggi: '',
  lokasi_pt: '',
  gelar_akademik: '',
  nomor_ijazah: '',
  tanggal_ijazah: '',
  tahun_lulus: new Date().getFullYear(),
})

const fileInputs = ref({})

const documents = ref({})

// Perguruan Tinggi Dropdown States
const showPTDropdown = ref(false)
const filteredPT = ref([])
const loadingPT = ref(false)
const ptSearchKeyword = ref('')
const ptDropdownMessage = ref('')
const selectedPT = ref(null)

// Prodi Dropdown States
const showProdiDropdown = ref(false)
const filteredProdi = ref([])
const loadingProdi = ref(false)
const prodiSearchKeyword = ref('')
const prodiDropdownMessage = ref('')

// Document Preview Modal States
const showPreviewModal = ref(false)
const currentPreviewFile = ref(null)
const currentPreviewDoc = ref(null)

// Computed for uploaded count
const uploadedCount = computed(() => {
  return Object.values(documents.value).filter(d => d).length
})

// Required documents count
const requiredCount = computed(() => {
  return documentTypes.value.filter(doc => doc.isRequired).length
})

// Get selected jenjang name for display
const selectedJenjangName = computed(() => {
  if (!form.value.jenjang_pendidikan_id) return ''
  const jenjang = masterStore.jenjang.find(j => j.id === form.value.jenjang_pendidikan_id)
  return jenjang?.nama || ''
})

// Watch jenjang change to filter prodi
watch(() => form.value.jenjang_pendidikan_id, () => {
  // If PT is selected, reload prodi for new jenjang
  if (selectedPT.value) {
    loadProdiForPT(selectedPT.value.id)
  }
  // Reset selected prodi if jenjang doesn't match
  if (form.value.nama_prodi && filteredProdi.value.length > 0) {
    const prodiStillValid = filteredProdi.value.some(p => p.nama_prodi === form.value.nama_prodi)
    if (!prodiStillValid) {
      form.value.nama_prodi = ''
    }
  }
})

// Page header subtitle
const headerSubtitle = computed(() => {
  const isEdit = route.params.id !== 'baru' && route.name === 'pga.edit'
  return isEdit ? 'Edit formulir pencantuman gelar akademik' : 'Isi formulir untuk pencantuman gelar akademik'
})

// Page header title
const headerTitle = computed(() => {
  const isEdit = route.params.id !== 'baru' && route.name === 'pga.edit'
  return isEdit ? 'Edit Pengajuan Pencantuman Gelar Akademik' : 'Buat Pengajuan Pencantuman Gelar Akademik'
})

function handleFileUpload(key, event) {
  const file = event.target.files[0]
  if (!file) return

  // Check file size (1MB)
  const maxSize = 1 * 1024 * 1024
  if (file.size > maxSize) {
    toast.error('Ukuran file terlalu besar. Maksimum 1MB')
    return
  }

  // Check file type - only PDF allowed
  if (file.type !== 'application/pdf') {
    toast.error('Tipe file tidak valid. Hanya file PDF yang diperbolehkan')
    return
  }

  documents.value[key] = file
  fileInputs.value[key] = file
}

function removeFile(key) {
  documents.value[key] = null
  fileInputs.value[key] = null
}

function getPreviewUrl(file) {
  if (!file) return ''
  if (file === 'existing') return ''

  // For newly uploaded files, create object URL
  if (file instanceof File) {
    try {
      return URL?.createObjectURL?.(file) || ''
    } catch {
      return ''
    }
  }

  return ''
}

function openPreviewModal(docKey) {
  const file = documents.value[docKey]
  if (!file || file === 'existing') return

  currentPreviewFile.value = getPreviewUrl(file)
  currentPreviewDoc.value = {
    name: fileInputs.value[docKey]?.name || file.name,
    type: file.type,
    size: file.size
  }
  showPreviewModal.value = true
}

function closePreviewModal() {
  showPreviewModal.value = false
  currentPreviewFile.value = null
  currentPreviewDoc.value = null
}

async function loadMasterData() {
  try {
    await masterStore.fetchJenjang()
  } catch (error) {
    toast.error('Gagal memuat data master')
  }
}

async function loadDocumentTypes() {
  loadingDocs.value = true
  try {
    const docs = await masterStore.fetchJenisDokumenPga()
    documentTypes.value = docs.map(doc => ({
      key: doc.kode,
      label: doc.nama,
      requirements: doc.deskripsi || '',
      notes: doc.catatan || (doc.format_nama ? `Format: ${doc.format_nama}` : ''),
      isRequired: doc.required,
      urutan: doc.urutan,
    }))

    // Initialize documents object with null values for all document types
    documentTypes.value.forEach(doc => {
      if (documents.value[doc.key] === undefined) {
        documents.value[doc.key] = null
      }
      if (fileInputs.value[doc.key] === undefined) {
        fileInputs.value[doc.key] = null
      }
    })
  } catch (error) {
    toast.error('Gagal memuat jenis dokumen')
  } finally {
    loadingDocs.value = false
  }
}

// ===== PDDIKTI SEARCH FUNCTIONS =====

// Close dropdowns when clicking outside
function handleClickOutside(event) {
  const ptDropdown = event.target.closest('[data-dropdown="pt"]')
  const prodiDropdown = event.target.closest('[data-dropdown="prodi"]')

  if (!ptDropdown && !prodiDropdown) {
    closeDropdowns()
  }
}

function closeDropdowns() {
  showPTDropdown.value = false
  showProdiDropdown.value = false
}

// Perguruan Tinggi Search
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

// Debounced version (500ms delay)
const debouncedSearchPerguruanTinggi = debounce(searchPerguruanTinggi, 500)

async function selectPerguruanTinggi(pt) {
  selectedPT.value = pt
  form.value.perguruan_tinggi = pt.nama_pt

  // Fetch detail to get location (kab_kota & provinsi)
  if (pt.id && !pt.kab_kota) {
    try {
      const response = await api.get(`/pddikti/universitas/${pt.id}/detail`)
      const ptDetail = response.data.data
      form.value.lokasi_pt = ptDetail.kab_kota && ptDetail.provinsi
        ? `${ptDetail.kab_kota}, ${ptDetail.provinsi}`
        : ''
    } catch (error) {
      console.error('Failed to fetch PT detail:', error)
      form.value.lokasi_pt = pt.kab_kota && pt.provinsi ? `${pt.kab_kota}, ${pt.provinsi}` : ''
    }
  } else {
    form.value.lokasi_pt = pt.kab_kota && pt.provinsi ? `${pt.kab_kota}, ${pt.provinsi}` : ''
  }

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
  form.value.perguruan_tinggi = ''
  form.value.lokasi_pt = ''
  form.value.nama_prodi = ''
  filteredPT.value = []
  filteredProdi.value = []
}

// Get jenjang code from selected jenjang_id
function getJenjangCode() {
  if (!form.value.jenjang_pendidikan_id) return null
  const jenjang = masterStore.jenjang.find(j => j.id === form.value.jenjang_pendidikan_id)
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

// Load all programs for a specific PT
async function loadProdiForPT(ptId) {
  loadingProdi.value = true
  prodiDropdownMessage.value = 'Memuat program studi...'
  showProdiDropdown.value = true
  try {
    let results = []

    // Try PDDikti API first
    try {
      const pddiktiResponse = await api.get(`/pddikti/universitas/${ptId}/prodi`)
      results = pddiktiResponse.data.data || []
    } catch (pddiktiError) {
      console.log('PDDikti API failed, trying local database...', pddiktiError)
      // Fall back to local database
      results = await masterStore.fetchProdi(ptId, '')
    }

    // Filter by jenjang
    const filtered = filterProdiByJenjang(results)
    filteredProdi.value = filtered

    if (filtered.length === 0) {
      prodiDropdownMessage.value = `Tidak ada program studi jenjang ${selectedJenjangName.value} untuk perguruan tinggi ini. Coba gunakan input manual untuk program studi.`
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

// Prodi Search
async function searchProdi(keyword) {
  prodiSearchKeyword.value = keyword

  if (keyword.length >= 2) {
    loadingProdi.value = true
    prodiDropdownMessage.value = ''
    try {
      const ptId = selectedPT.value ? selectedPT.value.id : null
      let results = []

      // Try PDDikti API first
      if (ptId) {
        try {
          const allProdi = await api.get(`/pddikti/universitas/${ptId}/prodi`)
          const allProdiData = allProdi.data.data || []
          results = allProdiData.filter(p =>
            (p.nama_prodi || '').toLowerCase().includes(keyword.toLowerCase())
          )
        } catch (pddiktiError) {
          console.log('PDDikti API failed, trying local database...', pddiktiError)
          results = await masterStore.fetchProdi(ptId, keyword)
        }
      } else {
        results = await masterStore.fetchProdi(null, keyword)
      }

      // Filter by jenjang
      const filtered = filterProdiByJenjang(results)
      filteredProdi.value = filtered

      if (filtered.length === 0) {
        const jenjangText = selectedJenjangName.value ? ` jenjang ${selectedJenjangName.value}` : ''
        prodiDropdownMessage.value = ptId
          ? `Tidak ada program studi${jenjangText} untuk perguruan tinggi ini. Coba kata kunci lain atau gunakan input manual.`
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

// Debounced version
const debouncedSearchProdi = debounce(searchProdi, 500)

function selectProdi(prodi) {
  form.value.nama_prodi = prodi.nama_prodi
  showProdiDropdown.value = false
  prodiSearchKeyword.value = ''
}

// ===== END PDDIKTI SEARCH FUNCTIONS =====

// Show incomplete confirm modal
const showIncompleteConfirm = ref(false)
const incompleteDocsList = ref([])

function checkIncompleteDocs() {
  const missingDocs = documentTypes.value.filter(doc => doc.isRequired && !documents.value[doc.key])

  if (missingDocs.length > 0) {
    incompleteDocsList.value = missingDocs
    showIncompleteConfirm.value = true
    return false
  }
  return true
}

async function proceedWithSubmission() {
  showIncompleteConfirm.value = false
  await saveForm(true)
}

function cancelIncompleteConfirm() {
  showIncompleteConfirm.value = false
}

async function saveForm(submit = false) {
  // Validate required fields
  if (!form.value.jenjang_pendidikan_id) {
    toast.error('Silakan pilih jenjang pendidikan')
    return
  }
  if (!form.value.nama_prodi) {
    toast.error('Silakan isi nama program studi')
    return
  }
  if (!form.value.perguruan_tinggi) {
    toast.error('Silakan isi nama perguruan tinggi')
    return
  }
  if (!form.value.tahun_lulus) {
    toast.error('Silakan isi tahun kelulusan')
    return
  }

  // Check file uploads if submitting
  if (submit) {
    const requiredDocs = documentTypes.value.filter(doc => doc.isRequired)
    for (const doc of requiredDocs) {
      if (!documents.value[doc.key]) {
        toast.error(`Silakan upload ${doc.label}`)
        return
      }
    }
  }

  submitting.value = true
  try {
    // Debug log
    console.log('[PGA Edit] Form values:', form.value)

    const formData = new FormData()

    // Add form fields - always include required fields even if empty
    // This ensures backend validation can provide proper error messages
    const requiredFields = ['jenjang_pendidikan_id', 'nama_prodi', 'perguruan_tinggi', 'tahun_lulus']
    const optionalFields = ['lokasi_pt', 'gelar_akademik', 'nomor_ijazah', 'tanggal_ijazah']

    // Add required fields
    requiredFields.forEach(key => {
      const value = form.value[key] !== null && form.value[key] !== undefined ? form.value[key] : ''
      formData.append(key, value)
      console.log(`[PGA Edit] ${key}:`, value, `(${typeof value})`)
    })

    // Add optional fields only if they have values
    optionalFields.forEach(key => {
      if (form.value[key] !== null && form.value[key] !== undefined && form.value[key] !== '') {
        formData.append(key, form.value[key])
      }
    })

    // Add files
    Object.keys(documents.value).forEach(key => {
      if (documents.value[key] && documents.value[key] !== 'existing') {
        formData.append(key, documents.value[key])
      }
    })

    if (route.params.id && route.name === 'pga.edit') {
      // Update existing
      await pgaStore.updatePga(route.params.id, formData)
      toast.success('Pengajuan PGA berhasil diperbarui')
    } else {
      // Create new
      await pgaStore.createPga(formData)
      toast.success(submit ? 'Pengajuan PGA berhasil dikirim' : 'Draft pengajuan PGA berhasil disimpan')
    }

    router.push('/pga')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan pengajuan')
  } finally {
    submitting.value = false
  }
}

async function handleSubmit() {
  // Check for incomplete documents
  const missingDocs = documentTypes.value.filter(doc => doc.isRequired && !documents.value[doc.key])

  if (missingDocs.length > 0) {
    incompleteDocsList.value = missingDocs
    showIncompleteConfirm.value = true
    return
  }

  await proceedWithSubmission()
}

async function saveDraft() {
  await saveForm(false)
}

onMounted(async () => {
  await loadMasterData()
  await loadDocumentTypes()

  // Close dropdowns when clicking outside
  document.addEventListener('click', handleClickOutside)

  // Load existing data if editing
  if (route.params.id && route.name === 'pga.edit') {
    loading.value = true
    try {
      const response = await pgaStore.fetchPgaById(route.params.id)
      // fetchPgaById returns the PGA data directly (not wrapped in response.data)
      const data = response.data || response

      // Update form values while preserving reactivity
      Object.assign(form.value, {
        jenjang_pendidikan_id: data.jenjang_pendidikan_id || '',
        nama_prodi: data.nama_prodi || '',
        perguruan_tinggi: data.perguruan_tinggi || '',
        lokasi_pt: data.lokasi_pt || '',
        gelar_akademik: data.gelar_akademik || '',
        nomor_ijazah: data.nomor_ijazah || '',
        // Format tanggal for date input (YYYY-MM-DD)
        tanggal_ijazah: formatDateForInput(data.tanggal_ijazah),
        tahun_lulus: data.tahun_lulus || new Date().getFullYear(),
      })

      // Load existing files info - use document types from API
      documentTypes.value.forEach(doc => {
        const field = doc.key
        if (data[field]) {
          fileInputs.value[field] = { name: `${doc.label} (existing)` }
          documents.value[field] = 'existing'
        }
      })
    } catch (error) {
      toast.error('Gagal memuat data pengajuan')
      router.push('/pga')
    } finally {
      loading.value = false
    }
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />

    <PageHeader
      :title="headerTitle"
      :subtitle="headerSubtitle"
    />

    <LoadingSpinner v-if="loading" size="lg" text="Memuat data..." />

    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
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
              <!-- Jenjang Pendidikan -->
              <div>
                <label class="input-label">Jenjang Pendidikan <span class="text-danger">*</span></label>
                <div class="relative">
                  <select
                    v-model="form.jenjang_pendidikan_id"
                    class="select-field appearance-none pr-10"
                    required
                  >
                    <option value="">Pilih Jenjang</option>
                    <option v-if="masterStore.loading" disabled>Loading...</option>
                    <option v-else-if="masterStore.jenjang.length === 0" disabled>Tidak ada data</option>
                    <option v-for="jenjang in masterStore.jenjang" :key="jenjang.id" :value="jenjang.id">
                      {{ jenjang.nama }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <LoadingSpinner v-if="masterStore.loading" size="sm" />
                    <i v-else class="ri-arrow-down-s-line text-secondary-400"></i>
                  </div>
                </div>
              </div>

              <!-- Perguruan Tinggi -->
              <div class="relative" data-dropdown="pt">
                <label class="input-label">Perguruan Tinggi <span class="text-danger">*</span> <span class="text-xs text-primary-600 font-normal">(Ketik untuk mencari dari database PDDikti)</span></label>
                <div class="relative">
                  <input
                    :value="form.perguruan_tinggi"
                    @input="e => { form.perguruan_tinggi = e.target.value; debouncedSearchPerguruanTinggi(e.target.value) }"
                    @focus="showPTDropdown = filteredPT.length > 0"
                    type="text"
                    class="input-field pr-20"
                    placeholder="Ketik untuk mencari perguruan tinggi..."
                    required
                    autocomplete="off"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                    <button
                      v-if="form.perguruan_tinggi"
                      @click="clearPerguruanTinggi"
                      type="button"
                      class="p-1 rounded-full hover:bg-secondary-100 transition-colors"
                      title="Hapus"
                    >
                      <i class="ri-close-line text-secondary-400"></i>
                    </button>
                    <i class="ri-search-line text-secondary-400"></i>
                  </div>
                </div>

                <!-- Dropdown Results -->
                <div
                  v-if="showPTDropdown"
                  class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-secondary-200 max-h-60 overflow-y-auto"
                >
                  <div v-if="loadingPT" class="p-4 text-center text-secondary-500">
                    <LoadingSpinner size="sm" />
                    <span class="ml-2">Mencari...</span>
                  </div>
                  <div v-else-if="filteredPT.length === 0" class="p-4 text-center text-secondary-500">
                    {{ ptDropdownMessage || 'Tidak ditemukan' }}
                  </div>
                  <div v-else>
                    <div class="px-3 py-2 text-xs text-secondary-500 bg-secondary-50 border-b sticky top-0">
                      {{ ptDropdownMessage }}
                    </div>
                    <button
                      v-for="pt in filteredPT"
                      :key="pt.id"
                      @click="selectPerguruanTinggi(pt)"
                      type="button"
                      class="w-full px-3 py-2 text-left hover:bg-primary-50 transition-colors border-b last:border-b-0"
                    >
                      <div class="font-medium text-secondary-800">{{ pt.nama_pt }}</div>
                      <div class="text-xs text-secondary-500">{{ pt.kab_kota }}, {{ pt.provinsi }}</div>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Nama Program Studi -->
              <div class="relative" data-dropdown="prodi">
                <label class="input-label">Program Studi <span class="text-danger">*</span> <span class="text-xs text-primary-600 font-normal">(Ketik untuk mencari dari database PDDikti)</span></label>
                <div class="relative">
                  <input
                    :value="form.nama_prodi"
                    @input="e => { form.nama_prodi = e.target.value; debouncedSearchProdi(e.target.value) }"
                    @focus="showProdiDropdown = filteredProdi.length > 0"
                    type="text"
                    class="input-field pr-20"
                    :placeholder="selectedPT ? 'Cari program studi...' : 'Pilih perguruan tinggi terlebih dahulu...'"
                    required
                    autocomplete="off"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <i v-if="loadingProdi" class="ri-loader-4-line animate-spin text-primary-600"></i>
                    <i v-else class="ri-search-line text-secondary-400"></i>
                  </div>
                </div>

                <!-- Dropdown Results -->
                <div
                  v-if="showProdiDropdown"
                  class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-secondary-200 max-h-60 overflow-y-auto"
                >
                  <div v-if="loadingProdi" class="p-4 text-center text-secondary-500">
                    <LoadingSpinner size="sm" />
                    <span class="ml-2">Mencari...</span>
                  </div>
                  <div v-else-if="filteredProdi.length === 0" class="p-4 text-center text-secondary-500">
                    {{ prodiDropdownMessage || 'Tidak ditemukan' }}
                  </div>
                  <div v-else>
                    <div class="px-3 py-2 text-xs text-secondary-500 bg-secondary-50 border-b sticky top-0">
                      {{ prodiDropdownMessage }}
                    </div>
                    <button
                      v-for="prodi in filteredProdi"
                      :key="prodi.id || prodi.nama_prodi"
                      @click="selectProdi(prodi)"
                      type="button"
                      class="w-full px-3 py-2 text-left hover:bg-primary-50 transition-colors border-b last:border-b-0"
                    >
                      <div class="font-medium text-secondary-800">{{ prodi.nama_prodi }}</div>
                      <div class="text-xs text-secondary-500">{{ prodi.jenjang }} - {{ prodi.akreditasi || '-' }}</div>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Lokasi Perguruan Tinggi -->
              <div>
                <label class="input-label">Lokasi Perguruan Tinggi <span class="text-xs text-primary-600 font-normal">(Auto dari database)</span></label>
                <div class="relative">
                  <input
                    v-model="form.lokasi_pt"
                    type="text"
                    class="input-field"
                    placeholder="Contoh: Bandung, Jawa Barat"
                    readonly
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <i class="ri-magic-line text-primary-600"></i>
                  </div>
                </div>
              </div>

              <!-- Gelar Akademik -->
              <div>
                <label class="input-label">Gelar Akademik</label>
                <input
                  v-model="form.gelar_akademik"
                  type="text"
                  class="input-field"
                  placeholder="Contoh: S.Kom, M.H., Dr."
                />
              </div>

              <!-- Nomor & Tanggal Ijazah -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="input-label">Nomor Ijazah</label>
                  <input
                    v-model="form.nomor_ijazah"
                    type="text"
                    class="input-field"
                    placeholder="Nomor ijazah"
                  />
                </div>
                <div>
                  <label class="input-label">Tanggal Ijazah</label>
                  <input
                    v-model="form.tanggal_ijazah"
                    type="date"
                    class="input-field"
                  />
                </div>
              </div>

              <!-- Tahun Lulus -->
              <div>
                <label class="input-label">Tahun Lulus <span class="text-danger">*</span></label>
                <input
                  v-model.number="form.tahun_lulus"
                  type="number"
                  min="1970"
                  :max="new Date().getFullYear() + 1"
                  class="input-field"
                  required
                />
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
              <span class="badge badge-primary">{{ uploadedCount }}/{{ requiredCount }}+1</span>
            </div>
            <p class="text-sm text-secondary-500 mt-1">Max 1MB per file. Hanya PDF.</p>
          </div>
          <div class="card-body">
            <!-- Document List -->
            <div class="space-y-2">
              <div
                v-for="(doc, index) in documentTypes"
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
                  <p v-if="fileInputs[doc.key]" class="text-xs text-secondary-500">
                    {{ fileInputs[doc.key].name }}
                  </p>
                  <p v-else-if="!doc.isRequired" class="text-xs text-secondary-400 italic">
                    Opsional
                  </p>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center gap-1 flex-shrink-0">
                  <!-- Info Tooltip -->
                  <div class="group relative">
                    <button class="p-1.5 rounded-lg hover:bg-secondary-100 transition-colors">
                      <i class="ri-information-line text-sm text-secondary-400"></i>
                    </button>
                    <div class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-secondary-200 p-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                      <p class="text-xs text-secondary-600">{{ doc.requirements }}</p>
                      <p class="text-xs text-secondary-400 mt-1">{{ doc.notes }}</p>
                    </div>
                  </div>

                  <!-- Upload Button -->
                  <label class="cursor-pointer p-1.5 rounded-lg transition-colors" :class="documents[doc.key] ? 'bg-success text-white hover:bg-green-600' : 'bg-primary-600 text-white hover:bg-primary-700'">
                    <i :class="documents[doc.key] ? 'ri-check-line' : 'ri-upload-line'" class="text-sm"></i>
                    <input
                      type="file"
                      class="hidden"
                      accept=".pdf"
                      @change="(e) => handleFileUpload(doc.key, e)"
                    />
                  </label>

                  <!-- Preview Button (jika sudah upload) -->
                  <button
                    v-if="documents[doc.key] && documents[doc.key] !== 'existing'"
                    @click="openPreviewModal(doc.key)"
                    type="button"
                    class="p-1.5 rounded-lg bg-secondary-100 text-secondary-600 hover:bg-secondary-200 transition-colors"
                    title="Lihat dokumen"
                  >
                    <i class="ri-eye-line text-sm"></i>
                  </button>

                  <!-- Remove Button (jika sudah upload) -->
                  <button
                    v-if="documents[doc.key] && documents[doc.key] !== 'existing'"
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
                <span>Progress Dokumen Wajib</span>
                <span class="font-semibold" :class="uploadedCount === requiredCount ? 'text-success' : 'text-primary-600'">{{ uploadedCount }} dari {{ requiredCount }} lengkap</span>
              </div>
              <div class="w-full bg-secondary-200 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-300" :class="uploadedCount === requiredCount ? 'bg-success' : 'bg-primary-600'" :style="{ width: Math.min(uploadedCount / requiredCount * 100, 100) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-3 animate-slide-up" style="animation-delay: 100ms;">
        <button type="button" @click.prevent="saveDraft" :disabled="submitting" class="btn btn-secondary flex-1 justify-center">
          <LoadingSpinner v-if="submitting" size="sm" />
          <span v-else class="flex items-center gap-2">
            <i class="ri-save-line"></i>
            <span>Simpan Draft</span>
          </span>
        </button>
        <button type="submit" :disabled="submitting" class="btn btn-primary flex-1 justify-center">
          <LoadingSpinner v-if="submitting" size="sm" />
          <span v-else class="flex items-center gap-2">
            <i class="ri-send-plane-fill"></i>
            <span>Simpan & Kirim</span>
          </span>
        </button>
        <router-link to="/pga" class="btn btn-ghost flex-1 justify-center">
          <i class="ri-close-line"></i>
          <span>Batal</span>
        </router-link>
      </div>
    </form>

    <!-- Incomplete Documents Warning Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showIncompleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelIncompleteConfirm"></div>
          <div class="relative bg-white rounded-2xl shadow-soft max-w-md w-full overflow-hidden animate-slide-up">
            <!-- Header -->
            <div class="p-4 border-b border-secondary-100">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                  <i class="ri-error-warning-line text-xl text-red-600"></i>
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-bold text-secondary-800">Dokumen Belum Lengkap</h3>
                  <p class="text-xs text-secondary-500">{{ incompleteDocsList.length }} dari {{ requiredCount }} dokumen wajib belum diupload</p>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-4">
              <p class="text-sm text-secondary-700 mb-3">
                <strong>Semua dokumen wajib harus diupload sebelum dapat mengirim pengajuan.</strong> Silakan lengkapi dokumen yang belum diupload.
              </p>
              <div class="max-h-48 overflow-y-auto space-y-1.5">
                <div v-for="doc in incompleteDocsList" :key="doc.key" class="flex items-center gap-2 p-2 bg-red-50 rounded-lg">
                  <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-bold">
                    <i class="ri-close-line"></i>
                  </span>
                  <p class="text-xs text-secondary-700">{{ doc.label }}</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-4 bg-secondary-50">
              <button @click="cancelIncompleteConfirm" class="btn btn-primary w-full justify-center">
                <i class="ri-arrow-left-line"></i>
                Lengkapi Dokumen
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Document Preview Modal -->
    <DocumentPreviewModal
      :show="showPreviewModal"
      :src="currentPreviewFile"
      :alt="currentPreviewDoc?.name || 'Dokumen'"
      :file-type="currentPreviewDoc?.type || ''"
      @close="closePreviewModal"
    />
  </MainLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
