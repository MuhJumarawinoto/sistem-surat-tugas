<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()

const pengajuanList = ref([])
const suratList = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const selectedPengajuan = ref(null)
const submitting = ref(false)

const form = ref({
  pengajuan_id: null,
  nomor_surat: '',
  tahun: new Date().getFullYear().toString(),
  tanggal_surat: new Date().toISOString().split('T')[0],
  tempat_ttd: 'Sukabumi'
})

const stats = computed(() => {
  return {
    total: pengajuanList.value.length,
    sudahSurat: suratList.value.length,
    pending: pengajuanList.value.length - suratList.value.length
  }
})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [pendingRes, suratRes] = await Promise.all([
      api.get('/admin/surat-tugas-mandiri/pending'),
      api.get('/admin/surat-tugas-mandiri')
    ])
    pengajuanList.value = pendingRes.data.data || pendingRes.data || []
    suratList.value = suratRes.data.data || suratRes.data || []
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
  }
}

function openCreateModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  form.value = {
    pengajuan_id: pengajuan.id,
    nomor_surat: generateNomorSurat(),
    tahun: new Date().getFullYear().toString(),
    tanggal_surat: new Date().toISOString().split('T')[0],
    tempat_ttd: 'Sukabumi'
  }
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  selectedPengajuan.value = null
  form.value = {
    pengajuan_id: null,
    nomor_surat: '',
    tahun: new Date().getFullYear().toString(),
    tanggal_surat: new Date().toISOString().split('T')[0],
    tempat_ttd: 'Sukabumi'
  }
}

function generateNomorSurat() {
  const count = suratList.value.length + 1
  return String(count).padStart(3, '0')
}

async function handleSubmit() {
  submitting.value = true
  try {
    await api.post('/admin/surat-tugas-mandiri', form.value)
    closeModal()
    await loadData()
  } catch (error) {
    console.error('Failed to create surat:', error)
    alert('Gagal membuat surat: ' + (error.response?.data?.message || error.message))
  } finally {
    submitting.value = false
  }
}

function viewSurat(surat) {
  router.push(`/admin/surat-tugas-mandiri/${surat.id}`)
}

function hasSurat(pengajuanId) {
  return suratList.value.some(s => s.pengajuan_id === pengajuanId)
}

function getSuratByPengajuan(pengajuanId) {
  return suratList.value.find(s => s.pengajuan_id === pengajuanId)
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Surat Tugas Belajar Mandiri"
      subtitle="Surat tugas setelah Surat Izin Belajar diterbitkan"
    />

    <!-- Stats -->
    <div class="flex flex-wrap items-center gap-4 mb-5 animate-slide-up">
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-white rounded-lg border border-secondary-200">
        <i class="ri-file-list-3-line text-secondary-500"></i>
        <span class="text-sm text-secondary-500">Total Selesai:</span>
        <span class="font-semibold text-lg text-secondary-800">{{ stats.total }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-green-50 rounded-lg border border-green-200">
        <i class="ri-file-check-line text-green-500"></i>
        <span class="text-sm text-green-600">Sudah Surat:</span>
        <span class="font-semibold text-lg text-green-700">{{ stats.sudahSurat }}</span>
      </div>
      <div class="flex items-center gap-2.5 px-4 py-2.5 bg-orange-50 rounded-lg border border-orange-200">
        <i class="ri-time-line text-orange-500"></i>
        <span class="text-sm text-orange-600">Perlu Surat:</span>
        <span class="font-semibold text-lg text-orange-700">{{ stats.pending }}</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16 animate-slide-up">
      <LoadingSpinner size="sm" text="Memuat..." />
    </div>

    <!-- Empty -->
    <div v-else-if="pengajuanList.length === 0" class="text-center py-16 animate-slide-up">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-secondary-100 to-secondary-50 flex items-center justify-center mx-auto mb-4 border border-secondary-200">
        <i class="ri-inbox-archive-line text-4xl text-secondary-400"></i>
      </div>
      <h3 class="text-base font-semibold text-secondary-800 mb-1">Tidak Ada Pengajuan</h3>
      <p class="text-sm text-secondary-500">Belum ada pengajuan yang selesai dan memerlukan Surat Tugas Mandiri</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3 animate-slide-up">
      <div
        v-for="item in pengajuanList"
        :key="item.id"
        class="bg-white rounded-xl border border-secondary-200 shadow-sm hover:shadow-md transition-all"
      >
        <div class="p-4">
          <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="font-semibold text-base text-secondary-800">{{ item.nomor_pengajuan }}</span>
                <span class="badge badge-success text-sm py-1 px-2.5">Selesai</span>
              </div>
              <p class="text-base font-medium text-secondary-800 mb-1.5">{{ item.user?.name }}</p>
              <div class="flex items-center gap-3 text-sm text-secondary-500 flex-wrap">
                <span class="flex items-center gap-1">
                  <i class="ri-briefcase-line text-secondary-400"></i>
                  {{ item.user?.jabatan || '-' }}
                </span>
                <span class="text-secondary-300">•</span>
                <span class="flex items-center gap-1">
                  <i class="ri-graduation-cap-line text-secondary-400"></i>
                  {{ item.nama_prodi }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                v-if="!hasSurat(item.id)"
                @click="openCreateModal(item)"
                class="btn btn-primary btn-sm"
              >
                <i class="ri-file-add-line"></i>
                <span class="ml-1">Buat Surat</span>
              </button>
              <button
                v-else
                @click="viewSurat(getSuratByPengajuan(item.id))"
                class="btn btn-secondary btn-sm"
              >
                <i class="ri-eye-line"></i>
                <span class="ml-1">Lihat Surat</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b">
              <h3 class="text-lg font-semibold">Buat Surat Tugas Mandiri</h3>
              <button @click="closeModal" class="btn btn-ghost btn-icon">
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>

            <div class="p-4 space-y-4">
              <div v-if="selectedPengajuan" class="bg-secondary-50 rounded-lg p-3">
                <p class="text-sm font-medium text-secondary-800">{{ selectedPengajuan.user?.name }}</p>
                <p class="text-xs text-secondary-500">{{ selectedPengajuan.nama_prodi }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">Nomor Surat</label>
                <input
                  v-model="form.nomor_surat"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="001"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun</label>
                <input
                  v-model="form.tahun"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="2026"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">Tanggal Surat</label>
                <input
                  v-model="form.tanggal_surat"
                  type="date"
                  class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">Tempat TTD</label>
                <input
                  v-model="form.tempat_ttd"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="Sukabumi"
                >
              </div>
            </div>

            <div class="flex justify-end gap-2 p-4 border-t bg-secondary-50">
              <button @click="closeModal" class="btn btn-ghost">Batal</button>
              <button
                @click="handleSubmit"
                class="btn btn-primary"
                :disabled="submitting"
              >
                {{ submitting ? 'Memproses...' : 'Buat Surat' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </MainLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
