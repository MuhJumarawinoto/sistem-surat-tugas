<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const backendUrl = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace('/api', '')
  : 'http://localhost:8000'

// Form data
const formData = ref({
  // Data Surat
  nomor_surat: '800.1.3.1/001/BKPSDM/' + new Date().getFullYear(),
  tahun: new Date().getFullYear().toString(),
  tanggal_surat: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
  tempat_ttd: 'Sukabumi',

  // Data Pegawai
  nama: 'Drajat Sukmana',
  nip: '198505102015011001',
  pangkat: 'Pembina (IV/a)',
  jabatan: 'Kepala Seki',
  unit_kerja: 'Sekretariat Badan Kepegawaian dan Pengembangan SDM',

  // Data Pendidikan
  jenjang: 'Magister (S2)',
  nama_prodi: 'Magister Informatika',
  perguruan_tinggi: 'Universitas BSI',
  lokasi_pt: 'Kabupaten Sukabumi, Jawa Barat',

  // Data Surat Tugas Dinas
  nomor_surat_dinas: '001/DK/Mei/' + new Date().getFullYear(),
  tanggal_mulai: '2026-09-01',
  tanggal_selesai: '2028-09-01',
  dinas: 'Dinas Pendidikan',
  nama_kepala_dinas: 'Kepala Dinas Pendidikan',
  nip_kepala_dinas: '197001011995031001',
})

const previewUrl = ref('')
const loadingPreview = ref(false)
const loadingPdf = ref(false)
const activeTab = ref('preview') // 'preview' or 'code'

// Load data from pengajuan
async function loadFromPengajuan(pengajuanId) {
  try {
    loadingPreview.value = true
    const response = await api.get(`/pengajuan/${pengajuanId}`)
    const p = response.data

    if (p) {
      formData.value.nama = p.user?.name || ''
      formData.value.nip = p.user?.nip || ''
      formData.value.pangkat = p.user?.pangkat_gol || ''
      formData.value.jabatan = p.user?.jabatan || ''
      formData.value.unit_kerja = p.user?.unitKerja?.nama || ''
      formData.value.jenjang = p.jenjang?.nama || ''
      formData.value.nama_prodi = p.nama_prodi || ''
      formData.value.perguruan_tinggi = p.perguruan_tinggi || ''
      formData.value.lokasi_pt = p.lokasi_pt || ''

      if (p.surat_tugas_dinas) {
        formData.value.nomor_surat_dinas = p.surat_tugas_dinas.nomor_surat || ''
        formData.value.tanggal_mulai = p.surat_tugas_dinas.tanggal_mulai || ''
        formData.value.tanggal_selesai = p.surat_tugas_dinas.tanggal_selesai || ''
        formData.value.dinas = p.surat_tugas_dinas.unit_kerja?.nama || ''
        formData.value.nama_kepala_dinas = p.surat_tugas_dinas.kepala_dinas?.nama || ''
        formData.value.nip_kepala_dinas = p.surat_tugas_dinas.kepala_dinas?.nip || ''
      }
    }

    updatePreview()
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loadingPreview.value = false
  }
}

// Generate preview URL
function updatePreview() {
  const params = new URLSearchParams()
  const token = localStorage.getItem('token') || ''

  // Add auth token
  if (token) {
    params.append('token', token)
  }

  // Add form data to params
  Object.keys(formData.value).forEach(key => {
    if (formData.value[key]) {
      params.append(key, formData.value[key])
    }
  })

  previewUrl.value = `${backendUrl}/api/admin/surat-izin/editor/preview?${params.toString()}&t=${Date.now()}`
}

// Generate PDF
async function generatePdf() {
  loadingPdf.value = true
  try {
    const params = new URLSearchParams()
    const token = localStorage.getItem('token') || ''

    // Add auth token
    if (token) {
      params.append('token', token)
    }

    Object.keys(formData.value).forEach(key => {
      if (formData.value[key]) {
        params.append(key, formData.value[key])
      }
    })

    // Open in new tab
    const url = `${backendUrl}/api/admin/surat-izin/editor/pdf?${params.toString()}`
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to generate PDF:', error)
  } finally {
    loadingPdf.value = false
  }
}

// Load data on mount
onMounted(() => {
  updatePreview()
})

// Watch form changes and update preview
watch(formData, () => {
  updatePreview()
}, { deep: true })

// Sections for organized editing
const sections = [
  {
    title: 'Data Surat',
    icon: 'ri-file-text-line',
    fields: [
      { key: 'nomor_surat', label: 'Nomor Surat', type: 'text', col: 2 },
      { key: 'tahun', label: 'Tahun', type: 'text', col: 1 },
      { key: 'tanggal_surat', label: 'Tanggal Surat', type: 'text', col: 1 },
      { key: 'tempat_ttd', label: 'Tempat TTD', type: 'text', col: 1 },
    ]
  },
  {
    title: 'Data Pegawai',
    icon: 'ri-user-line',
    fields: [
      { key: 'nama', label: 'Nama Pegawai', type: 'text', col: 2 },
      { key: 'nip', label: 'NIP', type: 'text', col: 1 },
      { key: 'pangkat', label: 'Pangkat/Golongan', type: 'text', col: 1 },
      { key: 'jabatan', label: 'Jabatan', type: 'text', col: 2 },
      { key: 'unit_kerja', label: 'Unit Kerja', type: 'text', col: 2 },
    ]
  },
  {
    title: 'Data Pendidikan',
    icon: 'ri-graduation-cap-line',
    fields: [
      { key: 'jenjang', label: 'Jenjang', type: 'text', col: 1 },
      { key: 'nama_prodi', label: 'Program Studi', type: 'text', col: 2 },
      { key: 'perguruan_tinggi', label: 'Perguruan Tinggi', type: 'text', col: 2 },
      { key: 'lokasi_pt', label: 'Lokasi', type: 'text', col: 2 },
    ]
  },
  {
    title: 'Data Surat Tugas Dinas',
    icon: 'ri-file-list-line',
    fields: [
      { key: 'nomor_surat_dinas', label: 'Nomor Surat Dinas', type: 'text', col: 2 },
      { key: 'tanggal_mulai', label: 'Tanggal Mulai', type: 'date', col: 1 },
      { key: 'tanggal_selesai', label: 'Tanggal Selesai', type: 'date', col: 1 },
      { key: 'dinas', label: 'Dinas', type: 'text', col: 2 },
      { key: 'nama_kepala_dinas', label: 'Nama Kepala Dinas', type: 'text', col: 1 },
      { key: 'nip_kepala_dinas', label: 'NIP Kepala Dinas', type: 'text', col: 1 },
    ]
  },
]
</script>

<template>
  <MainLayout>
    <!-- Page Header -->
    <PageHeader
      title="PDF Editor"
      subtitle="Edit dan preview Surat Izin Belajar"
      :actions="[
        {
          label: loadingPdf ? 'Generating...' : 'Generate PDF',
          icon: loadingPdf ? 'ri-loader-4-line animate-spin' : 'ri-file-pdf-line',
          onClick: generatePdf,
          variant: 'btn-primary'
        },
        {
          label: 'Reset',
          icon: 'ri-refresh-line',
          onClick: () => { Object.assign(formData, { nomor_surat: '800.1.3.1/001/BKPSDM/' + new Date().getFullYear(), tahun: new Date().getFullYear().toString(), tanggal_surat: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }), tempat_ttd: 'Sukabumi', nama: 'Drajat Sukmana', nip: '198505102015011001', pangkat: 'Pembina (IV/a)', jabatan: 'Kepala Seki', unit_kerja: 'Sekretariat Badan Kepegawaian dan Pengembangan SDM', jenjang: 'Magister (S2)', nama_prodi: 'Magister Informatika', perguruan_tinggi: 'Universitas BSI', lokasi_pt: 'Kabupaten Sukabumi, Jawa Barat', nomor_surat_dinas: '001/DK/Mei/' + new Date().getFullYear(), tanggal_mulai: '2026-09-01', tanggal_selesai: '2028-09-01', dinas: 'Dinas Pendidikan', nama_kepala_dinas: 'Kepala Dinas Pendidikan', nip_kepala_dinas: '197001011995031001' }); updatePreview(); },
          variant: 'btn-secondary'
        }
      ]"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form Editor -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-edit-2-line text-primary-600"></i>
            Edit Data Surat
          </h3>
        </div>
        <div class="card-body space-y-6 max-h-[calc(100vh-200px)] overflow-y-auto scrollbar-thin">
          <div v-for="section in sections" :key="section.title" class="space-y-4">
            <h4 class="text-sm font-semibold text-secondary-700 flex items-center gap-2 pb-2 border-b border-secondary-200">
              <i :class="section.icon" class="text-primary-600"></i>
              {{ section.title }}
            </h4>
            <div class="grid grid-cols-2 gap-4">
              <div v-for="field in section.fields" :key="field.key" :class="field.col === 2 ? 'col-span-2' : 'col-span-1'">
                <label class="input-label text-xs">{{ field.label }}</label>
                <input
                  v-model="formData[field.key]"
                  :type="field.type || 'text'"
                  class="w-full px-3 py-2 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
                  @input="updatePreview"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-eye-line text-primary-600"></i>
            Live Preview
          </h3>
        </div>
        <div class="card-body p-0">
          <div v-if="loadingPreview" class="flex items-center justify-center h-[600px]">
            <LoadingSpinner size="md" text="Memuat preview..." />
          </div>
          <iframe
            v-else
            :src="previewUrl"
            class="w-full h-[calc(100vh-200px)] border-0"
            @load="loadingPreview = false"
          ></iframe>
        </div>
      </div>
    </div>

    <!-- Info Card -->
    <div class="card bg-blue-50 border-blue-200 mt-6">
      <div class="card-body py-4">
        <div class="flex items-start gap-3">
          <i class="ri-information-line text-blue-600 text-xl mt-0.5"></i>
          <div class="flex-1">
            <p class="text-sm font-medium text-blue-800">Cara Menggunakan PDF Editor</p>
            <ul class="text-sm text-blue-700 mt-2 space-y-1">
              <li>• Edit data di form sebelah kiri, preview akan otomatis terupdate</li>
              <li>• Klik "Generate PDF" untuk mengunduh file PDF</li>
              <li>• Gunakan data ini sebagai referensi untuk mengedit template Blade di <code>resources/views/pdf/surat-izin-belajar.blade.php</code></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
