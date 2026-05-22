<script setup>
import { ref, onMounted, computed } from 'vue'
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

const router = useRouter()
const route = useRoute()
const masterStore = useMasterStore()
const pengajuanStore = usePengajuanStore()

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
  perguruan_tinggi: '',
  akreditasi_prodi: '',
  lokasi_pt: '',
  rencana_mulai: '',
  rencana_selesai: '',
})

const existingDocs = ref([])
const newDocuments = ref({
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

const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

const loading = ref(false)
const saving = ref(false)

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

const docMap = computed(() => {
  const map = {}
  existingDocs.value.forEach(doc => {
    map[doc.jenis_dokumen] = doc
  })
  return map
})

const hasDoc = (key) => !!docMap.value[key]

onMounted(async () => {
  await loadPengajuan()
  await masterStore.fetchAll()
})

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

    for (const doc of jenisDokumenList) {
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
</script>

<template>
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat..." />
    </div>

    <div v-else class="space-y-6 animate-fade-in">
      <Breadcrumb :current-page="'Edit Pengajuan'" />

      <div class="mb-4">
        <h2 class="text-2xl font-bold text-secondary-800">Edit Pengajuan</h2>
        <p class="text-secondary-500 mt-1">ID: <span class="font-mono">{{ route.params.id }}</span></p>
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
                    <input v-model="form.nama_prodi" type="text" required class="input-field" placeholder="Nama program studi" />
                  </div>

                  <div>
                    <label class="input-label">Perguruan Tinggi</label>
                    <input v-model="form.perguruan_tinggi" type="text" required class="input-field" placeholder="Nama universitas" />
                  </div>

                  <div>
                    <label class="input-label">Akreditasi Prodi</label>
                    <input v-model="form.akreditasi_prodi" type="text" required class="input-field" placeholder="Contoh: A, B, C, Unggul" />
                  </div>

                  <div>
                    <label class="input-label">Lokasi PT</label>
                    <input v-model="form.lokasi_pt" type="text" required class="input-field" placeholder="Kab/Kota" />
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
            <div class="card">
              <div class="card-header">
                <div class="flex items-center justify-between">
                  <h3 class="card-title flex items-center gap-2">
                    <i class="ri-file-upload-line text-primary-600"></i>
                    Upload/Ubah Dokumen
                  </h3>
                  <span class="badge badge-primary">{{ totalDocs }}/9</span>
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
                      <div v-for="doc in jenisDokumenList" :key="doc.key" class="p-3 border rounded-xl" :class="newDocuments[doc.key] || docMap[doc.key] ? 'border-success bg-green-50' : 'border-secondary-200'">
                        <label class="block">
                          <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-2 text-sm font-medium text-secondary-700">
                              <i :class="newDocuments[doc.key] || docMap[doc.key] ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-secondary-400'"></i>
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
                          v-model="newDocuments[doc.key]"
                          :existingFile="docMap[doc.key]"
                          :existingFileUrl="docMap[doc.key] ? getStorageUrl(docMap[doc.key].file_path) : ''"
                          :preview="false"
                          @preview="openImageModal"
                        />
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
