<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMasterStore } from '@/stores/master'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import ImageModal from '@/components/ImageModal.vue'
import FileUpload from '@/components/FileUpload.vue'

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

// Image Modal
const showImageModal = ref(false)
const currentImageSrc = ref('')
const currentImageAlt = ref('')

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

onMounted(async () => {
  await masterStore.fetchAll()
  nomorPengajuan.value = await pengajuanStore.getNomorPengajuan()
})

function openImageModal(url) {
  currentImageSrc.value = url
  currentImageAlt.value = 'Dokumen'
  showImageModal.value = true
}

async function saveDraftOnly() {
  saving.value = true
  try {
    // Create pengajuan draft
    const response = await pengajuanStore.createPengajuan(form.value)
    const pengajuanId = response.id

    // Upload selected documents (optional)
    let uploadedCount = 0
    for (const doc of jenisDokumenList) {
      const file = documents.value[doc.key]
      if (file) {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('jenis_dokumen', doc.key)

        await api.post(`/pengajuan/${pengajuanId}/dokumen`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        uploadedCount++
      }
    }

    if (uploadedCount > 0) {
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
  // Check if all documents are uploaded
  const missingDocs = jenisDokumenList.filter(doc => !documents.value[doc.key])
  if (missingDocs.length > 0) {
    alert(`Semua dokumen wajib diupload terlebih dahulu.\n\nDokumen yang belum ada:\n${missingDocs.map(d => '- ' + d.label).join('\n')}`)
    return
  }

  saving.value = true
  try {
    // Create pengajuan draft
    const response = await pengajuanStore.createPengajuan(form.value)
    const pengajuanId = response.id

    // Upload all documents
    for (const doc of jenisDokumenList) {
      const file = documents.value[doc.key]
      if (file) {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('jenis_dokumen', doc.key)

        await api.post(`/pengajuan/${pengajuanId}/dokumen`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
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
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <div class="mb-6">
          <router-link to="/pengajuan" class="text-blue-600 hover:text-blue-800">
            &larr; Kembali
          </router-link>
          <h2 class="text-2xl font-bold text-gray-900 mt-2">Pengajuan Baru</h2>
          <p class="text-gray-600">Nomor Pengajuan: {{ nomorPengajuan }}</p>
        </div>

        <div class="card">
          <form class="space-y-8">
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
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Upload Dokumen (Max 5MB)</h3>
              <p class="text-sm text-gray-500 mb-4">Opsional untuk draft. Wajib lengkap (9 dokumen) untuk submit ke atasan.</p>
              <div class="space-y-4">
                <div v-for="doc in jenisDokumenList" :key="doc.key" class="border rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ doc.label }}</label>
                  <FileUpload
                    v-model="documents[doc.key]"
                    :preview="true"
                    @preview="openImageModal"
                  />
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
              <button type="button" @click.prevent="saveDraftOnly" :disabled="saving" class="btn-secondary flex-1">
                {{ saving ? 'Menyimpan...' : 'Simpan Draft Saja' }}
              </button>
              <button type="button" @click.prevent="saveWithDocuments" :disabled="saving" class="btn-primary flex-1">
                {{ saving ? 'Menyimpan...' : 'Simpan & Upload Dokumen' }}
              </button>
              <router-link to="/pengajuan" class="btn-secondary flex-1 text-center">
                Batal
              </router-link>
            </div>
          </form>
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
