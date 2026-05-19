<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMasterStore } from '@/stores/master'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'
import DocumentInfoTooltip from '@/components/DocumentInfoTooltip.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PDDiktiDropdown from '@/components/PDDiktiDropdown.vue'

const router = useRouter()
const masterStore = useMasterStore()
const pengajuanStore = usePengajuanStore()

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
      alert(`Beberapa dokumen gagal diupload:\n${failedDocs.join('\n')}\n\nAnda dapat menguploadnya kembali nanti.`)
    } else if (uploadedCount > 0) {
      alert(`Draft berhasil disimpan dengan ${uploadedCount} dokumen. Sisa dokumen bisa ditambahkan nanti.`)
    } else {
      alert('Draft berhasil disimpan. Anda bisa menambahkan dokumen nanti.')
    }
    router.push(`/pengajuan/${pengajuanId}`)
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan draft')
  } finally {
    saving.value = false
  }
}

async function saveWithDocuments() {
  const missingDocs = jenisDokumenList.filter(doc => !documents.value[doc.key])
  if (missingDocs.length > 0) {
    alert(`Semua dokumen wajib diupload terlebih dahulu.\n\nDokumen yang belum ada:\n${missingDocs.map(d => '- ' + d.label).join('\n')}`)
    return
  }

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

    alert('Pengajuan berhasil disimpan lengkap dengan semua dokumen.')
    router.push(`/pengajuan/${pengajuanId}`)
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan pengajuan')
  } finally {
    saving.value = false
  }
}

const uploadedCount = computed(() => {
  return Object.values(documents.value).filter(d => d).length
})
</script>

<template>
  <div class="flex min-h-screen bg-secondary-50">
    <AppSidebar />
    <div class="flex-1 flex flex-col">
      <AppHeader />
      <main class="flex-1 p-6 overflow-y-auto">
        <Breadcrumb :current-page="'Pengajuan Baru'" />

        <div class="mb-6 animate-fade-in">
          <h2 class="text-2xl font-bold text-secondary-800">Pengajuan Baru</h2>
          <p class="text-secondary-500 mt-1">Nomor Pengajuan: <span class="font-mono text-primary-600">{{ nomorPengajuan }}</span></p>
        </div>

        <form @submit.prevent class="space-y-6">
          <!-- Data Pendidikan -->
          <div class="card animate-slide-up">
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

                <div>
                  <label class="input-label">Program Studi</label>
                  <input v-model="form.nama_prodi" type="text" required class="input-field" placeholder="Nama program studi" />
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                  <PDDiktiDropdown
                    v-model="selectedPT"
                    type="universitas"
                    placeholder="Cari nama perguruan tinggi di PDDikti..."
                    label="Perguruan Tinggi"
                    :required="true"
                  />
                </div>

                <div>
                  <label class="input-label">Lokasi PT</label>
                  <div class="relative">
                    <input
                      v-model="form.lokasi_pt"
                      type="text"
                      class="input-field pr-10"
                      :class="{ 'pl-10': loadingPTDetail }"
                      :placeholder="loadingPTDetail ? 'Memuat data...' : 'Otomatis dari PDDikti atau isi manual'"
                    />
                    <div v-if="loadingPTDetail" class="absolute inset-y-0 left-0 flex items-center pl-3">
                      <LoadingSpinner size="sm" />
                    </div>
                    <div v-else-if="form.lokasi_pt" class="absolute inset-y-0 right-0 flex items-center pr-3">
                      <i class="ri-check-line text-success text-lg"></i>
                    </div>
                  </div>
                  <p class="text-xs text-secondary-500 mt-1">
                    {{ loadingPTDetail ? 'Sedang mengambil data dari PDDikti...' : 'Otomatis dari PDDikti atau isi manual' }}
                  </p>
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                  <PDDiktiDropdown
                    v-model="selectedProdi"
                    type="prodi"
                    :id-pt="selectedPT?.id || selectedPT?.id"
                    placeholder="Cari program studi di PDDikti..."
                    label="Program Studi (dari PDDikti)"
                    :disabled="!selectedPT || loadingPTDetail"
                    :required="false"
                  />
                </div>

                <div>
                  <label class="input-label">Akreditasi Prodi</label>
                  <div class="flex gap-2">
                    <div class="relative flex-1">
                      <input
                        v-model="form.akreditasi_prodi"
                        type="text"
                        class="input-field pr-10"
                        placeholder="Otomatis dari PDDikti atau isi manual"
                      />
                      <div v-if="form.akreditasi_prodi" class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="ri-check-line text-success text-lg"></i>
                      </div>
                    </div>
                    <select v-model="form.akreditasi_prodi" class="select-field w-24">
                      <option value="">Pilih</option>
                      <option value="A">A</option>
                      <option value="B">B</option>
                      <option value="C">C</option>
                      <option value="Unggul">Unggul</option>
                    </select>
                  </div>
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
          <div class="card animate-slide-up" style="animation-delay: 50ms;">
            <div class="card-header">
              <div class="flex items-center justify-between">
                <h3 class="card-title flex items-center gap-2">
                  <i class="ri-file-upload-line text-primary-600"></i>
                  Upload Dokumen
                </h3>
                <span class="badge badge-primary">{{ uploadedCount }}/9</span>
              </div>
              <p class="text-sm text-secondary-500 mt-1">Max 5MB per file. Opsional untuk draft, wajib lengkap untuk submit.</p>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Upload -->
                <div class="space-y-3">
                  <h4 class="text-sm font-semibold text-secondary-700 flex items-center gap-2">
                    <i class="ri-upload-cloud-2-line text-primary-600"></i>
                    Upload Dokumen
                  </h4>
                  <div class="space-y-2 max-h-96 overflow-y-auto scrollbar-thin pr-2">
                    <div v-for="doc in jenisDokumenList" :key="doc.key" class="p-3 border rounded-xl" :class="documents[doc.key] ? 'border-success bg-green-50' : 'border-secondary-200 hover:border-secondary-300'">
                      <label class="block">
                        <div class="flex items-center justify-between mb-2">
                          <span class="flex items-center gap-2 text-sm font-medium text-secondary-700">
                            <i :class="documents[doc.key] ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-secondary-400'"></i>
                            <span class="truncate">{{ doc.label }}</span>
                          </span>
                          <DocumentInfoTooltip
                            :title="doc.label"
                            :requirements="doc.requirements"
                            :notes="doc.notes"
                          />
                        </div>
                      </label>
                      <FileUpload
                        v-model="documents[doc.key]"
                        :preview="false"
                        @preview="openImageModal"
                      />
                    </div>
                  </div>
                </div>

                <!-- Preview & Checklist -->
                <div class="space-y-4">
                  <h4 class="text-sm font-semibold text-secondary-700 flex items-center gap-2">
                    <i class="ri-eye-line text-primary-600"></i>
                    Preview & Status
                  </h4>

                  <!-- Preview List -->
                  <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                    <div v-if="uploadedCount === 0" class="text-center py-8 border-2 border-dashed border-secondary-200 rounded-xl">
                      <div class="w-12 h-12 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-2">
                        <i class="ri-file-upload-line text-2xl text-secondary-400"></i>
                      </div>
                      <p class="text-sm text-secondary-500">Belum ada dokumen</p>
                    </div>

                    <div v-for="doc in jenisDokumenList" :key="doc.key">
                      <div v-if="documents[doc.key]" class="p-2 bg-secondary-50 rounded-lg">
                        <div class="flex items-start justify-between">
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-secondary-800 truncate">{{ documents[doc.key].name }}</p>
                            <p class="text-xs text-secondary-500">{{ (documents[doc.key].size / 1024 / 1024).toFixed(2) }} MB</p>
                          </div>
                          <span class="badge badge-success">✓</span>
                        </div>
                        <div v-if="documents[doc.key].type?.startsWith('image/')" class="mt-2">
                          <img
                            :src="getPreviewUrl(documents[doc.key])"
                            class="max-w-full h-20 object-cover rounded-lg cursor-pointer hover:opacity-80"
                            @click="openImageModal(getPreviewUrl(documents[doc.key]))"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Checklist -->
                  <div class="p-4 bg-primary-50 rounded-xl border border-primary-200">
                    <p class="text-sm font-semibold text-primary-800 mb-2 flex items-center gap-1">
                      <i class="ri-task-line"></i>
                      Checklist Dokumen
                    </p>
                    <div class="grid grid-cols-1 gap-1 text-sm">
                      <div v-for="doc in jenisDokumenList" :key="doc.key" class="flex items-center gap-2">
                        <i :class="documents[doc.key] ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-secondary-400'"></i>
                        <span :class="documents[doc.key] ? 'text-primary-800' : 'text-secondary-500'" class="truncate">{{ doc.key.replace(/_/g, ' ') }}</span>
                      </div>
                    </div>
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
      </main>
    </div>

    <ImageModal
      :show="showImageModal"
      :src="currentImageSrc"
      :alt="currentImageAlt"
      @close="showImageModal = false"
    />
  </div>
</template>
