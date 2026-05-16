<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMasterStore } from '@/stores/master'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'

const router = useRouter()
const route = useRoute()
const masterStore = useMasterStore()
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

// Image Modal
const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

const loading = ref(false)
const saving = ref(false)

const jenisDokumenList = [
  { key: 'sk_pangkat', label: 'SK Pangkat Terakhir legalisir' },
  { key: 'sk_cpns', label: 'SK CPNS legalisir' },
  { key: 'skp', label: 'SKP 2 tahun terakhir' },
  { key: 'surat_lulus', label: 'Surat Keterangan Lulus/Diterima dari PT' },
  { key: 'jadwal', label: 'Jadwal Perkuliahan' },
  { key: 'akreditasi', label: 'Sertifikat Akreditasi Prodi (min C)' },
  { key: 'surat_mandiri', label: 'Surat Pernyataan Biaya Mandiri' },
  { key: 'surat_ijazah', label: 'Surat Pernyataan Tidak Menuntut Ijazah' },
  { key: 'surat_sehat', label: 'Surat Keterangan Sehat' },
]

// Computed property for document map (reactive)
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

    // Format date for input type="date" (YYYY-MM-DD)
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
    // Update form data
    await pengajuanStore.updatePengajuan(route.params.id, form.value)

    // Upload new documents
    let uploadedCount = 0
    for (const doc of jenisDokumenList) {
      const file = newDocuments.value[doc.key]
      if (file) {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('jenis_dokumen', doc.key)

        await api.post(`/pengajuan/${route.params.id}/dokumen`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        uploadedCount++
      }
    }

    if (uploadedCount > 0) {
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

        <div v-else class="space-y-6">
          <div class="mb-6">
            <router-link to="/pengajuan" class="text-blue-600 hover:text-blue-800">
              &larr; Kembali
            </router-link>
            <h2 class="text-2xl font-bold text-gray-900 mt-2">Edit Pengajuan</h2>
            <p class="text-gray-600">ID: {{ route.params.id }}</p>
          </div>

          <div class="card">
            <form @submit.prevent="updatePengajuan" class="space-y-8">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Pendidikan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenjang</label>
                    <select v-model="form.jenjang_id" required class="input-field">
                      <option value="">Pilih Jenjang</option>
                      <option v-for="j in masterStore.jenjang" :key="j.id" :value="j.id">
                        {{ j.nama }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Program Studi</label>
                    <input v-model="form.nama_prodi" type="text" required class="input-field" placeholder="Nama program studi" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Perguruan Tinggi</label>
                    <input v-model="form.perguruan_tinggi" type="text" required class="input-field" placeholder="Nama universitas" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Akreditasi Prodi</label>
                    <select v-model="form.akreditasi_prodi" required class="input-field">
                      <option value="">Pilih Akreditasi</option>
                      <option v-for="a in masterStore.akreditasi" :key="a.value" :value="a.value">
                        {{ a.label }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi PT</label>
                    <input v-model="form.lokasi_pt" type="text" required class="input-field" placeholder="Kab/Kota" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rencana Mulai</label>
                    <input v-model="form.rencana_mulai" type="date" required class="input-field" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rencana Selesai</label>
                    <input v-model="form.rencana_selesai" type="date" required class="input-field" />
                  </div>
                </div>
              </div>

              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Upload/Ubah Dokumen (Max 5MB)</h3>
                <p class="text-sm text-gray-500 mb-4">Opsional. Pilih dokumen yang ingin diganti. Dokumen yang sudah ada akan tetap tersimpan.</p>
                <div class="space-y-4">
                  <div v-for="doc in jenisDokumenList" :key="doc.key" class="border rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ doc.label }}</label>
                    <FileUpload
                      v-model="newDocuments[doc.key]"
                      :existingFile="docMap.value[doc.key]"
                      :existingFileUrl="docMap.value[doc.key] ? getStorageUrl(docMap.value[doc.key].file_path) : ''"
                      :preview="true"
                      @preview="openImageModal"
                    />
                  </div>
                </div>
              </div>

              <div class="flex space-x-4">
                <button type="submit" :disabled="saving" class="btn-primary">
                  {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
                <router-link :to="`/pengajuan/${route.params.id}`" class="btn-secondary">
                  Batal
                </router-link>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                {{ Object.values(newDocuments).filter(f => f).length }} dokumen baru akan diupload
              </p>
            </form>
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
