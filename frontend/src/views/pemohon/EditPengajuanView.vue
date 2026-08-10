<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMasterStore } from '@/stores/master'
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

const router = useRouter()
const route = useRoute()
const masterStore = useMasterStore()
const pengajuanStore = usePengajuanStore()

// Highlight state from navigation
const highlightType = ref(route.query.highlight || history.state?.highlight || null)
const highlightId = ref(route.query.highlightId || route.query.highlightId || history.state?.highlightId || null)
const showHighlight = ref(true)

// Clear highlight after animation
// Scroll to highlighted element
function scrollToHighlighted() {
  if (highlightType.value === 'document' && highlightId.value) {
    // Find the target document
    const targetDoc = existingDocs.value.find(d => String(d.id) === highlightId.value)
    if (targetDoc) {
      // Find element by jenis_dokumen
      const element = document.querySelector(`[data-doc-type="${targetDoc.jenis_dokumen}"]`)
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    }
  }
}

// Check if document should be highlighted by doc id
const isDocHighlighted = (docId) => {
  return showHighlight.value && highlightType.value === 'document' && highlightId.value === String(docId)
}

// Check if document type should be highlighted by jenis_dokumen
const isDocTypeHighlighted = (jenisDokumen) => {
  if (!showHighlight.value || highlightType.value !== 'document' || !highlightId.value) return false

  // Check if any existing doc matches the highlight id
  const targetDoc = existingDocs.value.find(d => String(d.id) === highlightId.value)
  return targetDoc && targetDoc.jenis_dokumen === jenisDokumen
}

// Get highlight class for document card
const getHighlightClass = (doc) => {
  if (doc && isDocHighlighted(doc.id)) {
    return 'highlight-pulse'
  }
  return ''
}

// Get highlight class for document type (new upload area)
const getDocTypeHighlightClass = (jenisDokumen) => {
  if (isDocTypeHighlighted(jenisDokumen)) {
    return 'highlight-pulse'
  }
  return ''
}

// Get container highlight class
const getContainerHighlightClass = () => {
  if (showHighlight.value && highlightType.value === 'document') {
    return 'container-highlight-pulse'
  }
  return ''
}

const backendUrl = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace('/api', '')
  : 'http://localhost:8000'

const getStorageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${backendUrl}/storage/${path}`
}

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

// Dropdown states
const ptSearchKeyword = ref('')
const prodiSearchKeyword = ref('')
const showPTDropdown = ref(false)
const showProdiDropdown = ref(false)
const filteredPT = ref([])
const filteredProdi = ref([])
const selectedPT = ref(null)

const existingDocs = ref([])
const newDocuments = ref({})

const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

const loading = ref(false)
const saving = ref(false)
const refreshingDokumen = ref(false)

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

// Initialize newDocuments object when jenisDokumenList changes
watch(jenisDokumenList, (newList) => {
  const newDocs = {}
  newList.forEach(doc => {
    // Preserve existing file if already uploaded
    if (newDocuments.value[doc.key]) {
      newDocs[doc.key] = newDocuments.value[doc.key]
    } else {
      newDocs[doc.key] = null
    }
  })
  newDocuments.value = newDocs
}, { immediate: true })

const docMap = computed(() => {
  const map = {}
  existingDocs.value.forEach(doc => {
    map[doc.jenis_dokumen] = doc
  })
  return map
})

const hasDoc = (key) => !!docMap.value[key]

async function refreshJenisDokumen() {
  refreshingDokumen.value = true
  try {
    await masterStore.fetchJenisDokumen(true)
    toast.success('Daftar dokumen berhasil diperbarui')
  } catch (error) {
    console.error('Failed to refresh jenis dokumen:', error)
    toast.error('Gagal memperbarui daftar dokumen')
  } finally {
    refreshingDokumen.value = false
  }
}

async function loadPengajuan() {
  loading.value = true
  try {
    const pengajuan = await pengajuanStore.fetchPengajuanDetail(route.params.id)

    const formatDateForInput = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    }

    form.value = {
      jenjang_id: pengajuan.jenjang_id,
      nama_prodi: pengajuan.nama_prodi,
      perguruan_tinggi: pengajuan.perguruan_tinggi,
      akreditasi_prodi: pengajuan.akreditasi_prodi,
      lokasi_pt: pengajuan.lokasi_pt,
      rencana_mulai: formatDateForInput(pengajuan.rencana_mulai),
      rencana_selesai: formatDateForInput(pengajuan.rencana_selesai),
    }
    // Set search keyword to show existing PT value in input
    ptSearchKeyword.value = pengajuan.perguruan_tinggi || ''
    prodiSearchKeyword.value = pengajuan.nama_prodi || ''
    existingDocs.value = pengajuan.dokumen || []
  } catch (error) {
    alert('Gagal memuat pengajuan')
    router.push('/pengajuan')
  } finally {
    loading.value = false
  }
}

async function updatePengajuan() {
  saving.value = true
  try {
    await pengajuanStore.updatePengajuan(route.params.id, form.value)

    let uploadedCount = 0
    let failedDocs = []

    for (const doc of jenisDokumenList.value) {
      const file = newDocuments.value[doc.key]
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
          const result = await api.post(`/pengajuan/${route.params.id}/dokumen`, formData, {
            headers: { 'Content-Type': undefined }
          })
          if (result.status === 201 || result.status === 200) {
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
      alert(`Pengajuan berhasil diperbarui, tapi beberapa dokumen gagal diupload:\n${failedDocs.join('\n')}\n\nAnda dapat menguploadnya kembali nanti.`)
    } else if (uploadedCount > 0) {
      alert(`Pengajuan berhasil diperbarui. ${uploadedCount} dokumen berhasil diupload.`)
    } else {
      alert('Pengajuan berhasil diperbarui.')
    }
    router.push(`/pengajuan/${route.params.id}`)
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal memperbarui pengajuan')
  } finally {
    saving.value = false
  }
}

function openImageModal(url) {
  currentImageSrc.value = url
  currentImageAlt.value = 'Dokumen'
  showImageModal.value = true
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

function getNewDocPreviewUrl(file) {
  if (!file || !file.type?.startsWith('image/')) return ''
  try {
    return URL?.createObjectURL?.(file) || ''
  } catch {
    return ''
  }
}

function getPreviewUrl(newDoc, existingDoc) {
  if (newDoc && newDoc.type?.startsWith('image/')) {
    try {
      return URL?.createObjectURL?.(newDoc) || ''
    } catch {
      return ''
    }
  }
  if (existingDoc && isDocImage(existingDoc)) {
    return getStorageUrl(existingDoc.file_path)
  }
  return ''
}

const totalDocs = computed(() => {
  return Object.keys(docMap.value).length + Object.values(newDocuments.value).filter(d => d).length
})

// Perguruan Tinggi Dropdown
async function searchPerguruanTinggi(keyword) {
  ptSearchKeyword.value = keyword
  if (keyword.length >= 2) {
    filteredPT.value = await masterStore.fetchPerguruanTinggi(keyword)
    showPTDropdown.value = true
  } else {
    filteredPT.value = []
    showPTDropdown.value = false
  }
}

function selectPerguruanTinggi(pt) {
  selectedPT.value = pt
  form.value.perguruan_tinggi_id = pt.id
  form.value.perguruan_tinggi = pt.nama_pt
  form.value.lokasi_pt = pt.kab_kota && pt.provinsi ? `${pt.kab_kota}, ${pt.provinsi}` : form.value.lokasi_pt
  showPTDropdown.value = false
  ptSearchKeyword.value = ''
  // Reset prodi when PT changes
  form.value.nama_prodi = ''
  filteredProdi.value = []
}

// Prodi Dropdown
async function searchProdi(keyword) {
  prodiSearchKeyword.value = keyword
  if (keyword.length >= 2 && selectedPT.value) {
    filteredProdi.value = await masterStore.fetchProdi(selectedPT.value.id, keyword)
    showProdiDropdown.value = true
  } else if (keyword.length >= 2) {
    // Search all prodis if no PT selected
    filteredProdi.value = await masterStore.fetchProdi(null, keyword)
    showProdiDropdown.value = true
  } else {
    filteredProdi.value = []
    showProdiDropdown.value = false
  }
}

function selectProdi(prodi) {
  form.value.nama_prodi = prodi.nama_prodi
  if (prodi.akreditasi && !form.value.akreditasi_prodi) {
    form.value.akreditasi_prodi = prodi.akreditasi
  }
  showProdiDropdown.value = false
  prodiSearchKeyword.value = ''
}

function closeDropdowns() {
  showPTDropdown.value = false
  showProdiDropdown.value = false
}

function handleClickOutside(event) {
  if (!event.target.closest('.relative')) {
    closeDropdowns()
  }
}

onMounted(async () => {
  // Load master data dulu supaya dropdown options terisi
  await masterStore.fetchAll()

  // Force refresh jenis dokumen to get latest data (bypass cache)
  // This ensures newly added document types appear immediately
  await masterStore.fetchJenisDokumen(true)

  // Baru load data pengajuan
  await loadPengajuan()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat..." />
    </div>

    <div v-else class="space-y-6 animate-fade-in">
      <Breadcrumb />

      <PageHeader
        title="Edit Pengajuan Izin Belajar"
        :subtitle="`ID: ${route.params.id}`"
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

          <form @submit.prevent="updatePengajuan" class="space-y-6">
            <!-- Data Pendidikan -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                  <i class="ri-graduation-cap-line text-primary-600"></i>
                  Data Pendidikan
                </h3>
              </div>
              <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div>
                    <label class="input-label">Jenjang</label>
                    <select v-model="form.jenjang_id" required class="select-field">
                      <option value="">Pilih Jenjang</option>
                      <option v-for="j in masterStore.jenjang" :key="j.id" :value="j.id">
                        {{ j.nama }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="input-label">Program Studi</label>
                    <div class="relative">
                      <input
                        :value="form.nama_prodi"
                        @input="(e) => { form.nama_prodi = e.target.value; searchProdi(e.target.value) }"
                        @focus="() => form.nama_prodi && searchProdi(form.nama_prodi)"
                        type="text"
                        required
                        class="input-field"
                        placeholder="Ketik untuk cari program studi..."
                      />
                      <!-- Dropdown Results -->
                      <div
                        v-if="showProdiDropdown && filteredProdi.length > 0"
                        class="absolute z-10 w-full mt-1 bg-white border border-secondary-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                      >
                        <div
                          v-for="prodi in filteredProdi"
                          :key="prodi.id"
                          @click="selectProdi(prodi)"
                          class="p-3 hover:bg-secondary-50 cursor-pointer border-b border-secondary-100 last:border-b-0"
                        >
                          <p class="text-sm font-medium text-secondary-800">{{ prodi.nama_prodi }}</p>
                          <p class="text-xs text-secondary-500">
                            {{ prodi.jenjang }} • {{ prodi.perguruan_tinggi?.nama_pt }}
                            <span v-if="prodi.akreditasi" class="badge badge-xs badge-success ml-1">{{ prodi.akreditasi }}</span>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label class="input-label">Perguruan Tinggi</label>
                    <div class="relative">
                      <input
                        :value="form.perguruan_tinggi"
                        @input="(e) => { form.perguruan_tinggi = e.target.value; searchPerguruanTinggi(e.target.value) }"
                        @focus="() => form.perguruan_tinggi && searchPerguruanTinggi(form.perguruan_tinggi)"
                        type="text"
                        required
                        class="input-field"
                        placeholder="Ketik untuk cari perguruan tinggi..."
                      />
                      <!-- Dropdown Results -->
                      <div
                        v-if="showPTDropdown && filteredPT.length > 0"
                        class="absolute z-10 w-full mt-1 bg-white border border-secondary-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                      >
                        <div
                          v-for="pt in filteredPT"
                          :key="pt.id"
                          @click="selectPerguruanTinggi(pt)"
                          class="p-3 hover:bg-secondary-50 cursor-pointer border-b border-secondary-100 last:border-b-0"
                        >
                          <p class="text-sm font-medium text-secondary-800">{{ pt.nama_pt }}</p>
                          <p class="text-xs text-secondary-500">{{ pt.kab_kota }}, {{ pt.provinsi }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label class="input-label">Akreditasi Prodi</label>
                    <select v-model="form.akreditasi_prodi" required class="select-field">
                      <option value="">Pilih Akreditasi</option>
                      <option v-for="a in masterStore.akreditasi" :key="a.value" :value="a.value">
                        {{ a.label }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="input-label">Lokasi PT</label>
                    <input v-model="form.lokasi_pt" type="text" required class="input-field bg-secondary-50" placeholder="Kab/Kota" readonly />
                    <p class="text-xs text-secondary-500 mt-1">
                      <i class="ri-information-line"></i>
                      Lokasi otomatis terisi setelah memilih perguruan tinggi. Bisa diedit manual jika perlu.
                    </p>
                  </div>

                  <div>
                    <label class="input-label">Rencana Mulai</label>
                    <input v-model="form.rencana_mulai" type="date" required class="input-field" />
                  </div>

                  <div>
                    <label class="input-label">Rencana Selesai</label>
                    <input v-model="form.rencana_selesai" type="date" required class="input-field" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Upload Dokumen -->
            <div class="card" :class="getContainerHighlightClass()">
              <div class="card-header">
                <div class="flex items-center justify-between">
                  <h3 class="card-title flex items-center gap-2">
                    <i class="ri-file-upload-line text-primary-600"></i>
                    Upload/Ubah Dokumen
                  </h3>
                  <div class="flex items-center gap-2">
                    <span class="badge badge-primary">{{ totalDocs }}/{{ jenisDokumenList.length }}</span>
                    <button
                      @click="refreshJenisDokumen"
                      :disabled="refreshingDokumen"
                      class="p-1.5 rounded-lg hover:bg-secondary-100 transition-colors"
                      title="Refresh daftar dokumen"
                    >
                      <i :class="refreshingDokumen ? 'ri-loader-4-line animate-spin' : 'ri-refresh-line'" class="text-secondary-500"></i>
                    </button>
                  </div>
                </div>
                <p class="text-sm text-secondary-500 mt-1">Opsional. Pilih dokumen yang ingin diganti.</p>
              </div>
              <div class="card-body">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <!-- Form Upload -->
                  <div class="space-y-2">
                    <h4 class="text-sm font-semibold text-secondary-700 flex items-center gap-2">
                      <i class="ri-upload-cloud-2-line text-primary-600"></i>
                      Upload/Ubah Dokumen
                    </h4>
                    <div class="space-y-2 max-h-96 overflow-y-auto scrollbar-thin pr-2">
                      <div v-for="doc in jenisDokumenList" :key="doc.key" :data-doc-type="doc.key" class="p-3 border rounded-xl transition-all" :class="[
                        newDocuments[doc.key] || docMap[doc.key] ? 'border-success bg-green-50' : 'border-secondary-200',
                        getDocTypeHighlightClass(doc.key)
                      ]">
                        <label class="block">
                          <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-2 text-sm font-medium text-secondary-700">
                              <i :class="newDocuments[doc.key] || docMap[doc.key] ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-secondary-400'"></i>
                              <span class="truncate">{{ doc.label }}</span>
                              <!-- Icon catatan admin -->
                              <i v-if="docMap[doc.key]?.catatan" class="ri-message-3-fill text-amber-500 text-sm" title="Ada catatan dari admin"></i>
                            </span>
                            <DocumentInfoTooltip
                              :title="doc.label"
                              :requirements="doc.requirements"
                              :notes="doc.notes"
                            />
                          </div>
                        </label>
                        <FileUpload
                          v-model="newDocuments[doc.key]"
                          :existingFile="docMap[doc.key]"
                          :existingFileUrl="docMap[doc.key] ? getStorageUrl(docMap[doc.key].file_path) : ''"
                          :preview="false"
                          @preview="openImageModal"
                        />
                        <!-- Catatan Admin -->
                        <div v-if="docMap[doc.key]?.catatan" class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                          <div class="flex items-start gap-2">
                            <i class="ri-chat-3-line text-amber-600 mt-0.5 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                              <p class="text-xs font-medium text-amber-800 flex items-center gap-1">
                                Catatan Admin
                                <span class="badge badge-xs" :class="docMap[doc.key].status_verifikasi === 'lengkap' ? 'badge-success' : 'badge-danger'">
                                  {{ docMap[doc.key].status_verifikasi === 'lengkap' ? 'Lengkap' : 'Perlu Diperbaiki' }}
                                </span>
                              </p>
                              <p class="text-sm text-amber-900">{{ docMap[doc.key].catatan }}</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Status & Preview -->
                  <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-secondary-700 flex items-center gap-2">
                      <i class="ri-eye-line text-primary-600"></i>
                      Status Dokumen
                    </h4>

                    <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                      <div v-for="doc in jenisDokumenList" :key="doc.key">
                        <div v-if="docMap[doc.key] || newDocuments[doc.key]" class="p-2 bg-secondary-50 rounded-lg">
                          <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                              <p class="text-sm font-medium text-secondary-800 truncate">
                                {{ newDocuments[doc.key]?.name || docMap[doc.key]?.file_name }}
                              </p>
                              <p class="text-xs text-secondary-500">
                                {{ newDocuments[doc.key] ? (newDocuments[doc.key].size / 1024 / 1024).toFixed(2) + ' MB' : (docMap[doc.key].file_size / 1024 / 1024).toFixed(2) + ' MB' }}
                              </p>
                            </div>
                            <span class="badge" :class="newDocuments[doc.key] ? 'badge-info' : 'badge-success'">
                              {{ newDocuments[doc.key] ? 'Baru' : 'Ada' }}
                            </span>
                          </div>

                          <div v-if="newDocuments[doc.key]?.type?.startsWith('image/') || isDocImage(docMap[doc.key])" class="mt-2">
                            <img
                              :src="getPreviewUrl(newDocuments[doc.key], docMap[doc.key])"
                              class="max-w-full h-20 object-cover rounded-lg cursor-pointer hover:opacity-80"
                              @click="openImageModal(getPreviewUrl(newDocuments[doc.key], docMap[doc.key]))"
                            />
                          </div>
                        </div>

                        <div v-else class="p-2 bg-secondary-50 rounded-lg opacity-60">
                          <div class="flex items-center text-sm text-secondary-500">
                            <i class="ri-file-line mr-2"></i>
                            <span class="truncate">{{ doc.label }} - Belum diupload</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Checklist -->
                    <div class="p-3 bg-primary-50 rounded-xl border border-primary-200">
                      <p class="text-sm font-semibold text-primary-800 mb-2">Status Dokumen:</p>
                      <div class="grid grid-cols-2 gap-1 text-sm">
                        <div v-for="doc in jenisDokumenList" :key="doc.key" class="flex items-center">
                          <i :class="docMap[doc.key] || newDocuments[doc.key] ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-secondary-400'"></i>
                          <span class="ml-1 truncate text-primary-800">{{ doc.key.replace(/_/g, ' ') }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
              <button type="submit" :disabled="saving" class="btn btn-primary flex-1 justify-center">
                <LoadingSpinner v-if="saving" />
                <span v-else class="flex items-center gap-2">
                  <i class="ri-save-line"></i>
                  <span>Simpan Perubahan</span>
                </span>
              </button>
              <router-link to="/pengajuan" class="btn btn-secondary flex-1 justify-center">
                <i class="ri-close-line"></i>
                <span>Batal</span>
              </router-link>
          </div>
        </form>
      </div>

    <ImageModal
      :show="showImageModal"
      :src="currentImageSrc"
      :alt="currentImageAlt"
      @close="showImageModal = false"
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
