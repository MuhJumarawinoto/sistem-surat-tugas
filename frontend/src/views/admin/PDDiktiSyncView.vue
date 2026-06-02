<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'

const toast = useToastStore()

const stats = ref({
  total_perguruan_tinggi: 0,
  total_prodi: 0,
  last_sync: null
})

const syncKeyword = ref('')
const syncing = ref(false)
const syncResult = ref(null)

const ptList = ref([])
const loadingPT = ref(false)
const ptSearch = ref('')

const selectedPT = ref(null)
const prodiList = ref([])
const loadingProdi = ref(false)
const syncingProdi = ref(false)

onMounted(() => {
  fetchStats()
  fetchPTList()
})

async function fetchStats() {
  try {
    const response = await api.get('/admin/pddikti-sync/stats')
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

async function fetchPTList() {
  loadingPT.value = true
  try {
    const params = {}
    if (ptSearch.value) {
      params.search = ptSearch.value
    }
    const response = await api.get('/admin/pddikti-sync', { params })
    ptList.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat daftar perguruan tinggi')
  } finally {
    loadingPT.value = false
  }
}

async function handleSyncUniversitas() {
  if (!syncKeyword.value || syncKeyword.value.length < 3) {
    toast.warning('Masukkan minimal 3 karakter untuk pencarian')
    return
  }

  syncing.value = true
  syncResult.value = null

  try {
    const response = await api.post('/admin/pddikti-sync/universitas', {
      keyword: syncKeyword.value
    })

    syncResult.value = response.data.data
    toast.success(response.data.message)

    // Refresh data
    await fetchStats()
    await fetchPTList()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal sync data')
  } finally {
    syncing.value = false
  }
}

async function handleSelectPT(pt) {
  selectedPT.value = pt
  await fetchProdis(pt.id)
}

async function fetchProdis(ptId) {
  loadingProdi.value = true
  try {
    const response = await api.get(`/admin/pddikti-sync/${ptId}/prodis`)
    prodiList.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat daftar prodi')
  } finally {
    loadingProdi.value = false
  }
}

async function handleSyncProdi() {
  if (!selectedPT.value) return

  syncingProdi.value = true
  try {
    const response = await api.post('/admin/pddikti-sync/prodi', {
      perguruan_tinggi_id: selectedPT.value.id,
      with_detail: true
    })

    toast.success(response.data.message)

    // Refresh prodi list
    await fetchProdis(selectedPT.value.id)
    await fetchStats()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal sync prodi')
  } finally {
    syncingProdi.value = false
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getJenjangColor(jenjang) {
  const colors = {
    'D3': 'bg-blue-100 text-blue-800',
    'D4': 'bg-indigo-100 text-indigo-800',
    'S1': 'bg-green-100 text-green-800',
    'S2': 'bg-purple-100 text-purple-800',
    'S3': 'bg-red-100 text-red-800',
    'Profesi': 'bg-amber-100 text-amber-800',
    'Spesialis': 'bg-pink-100 text-pink-800',
  }
  return colors[jenjang] || 'bg-gray-100 text-gray-800'
}

function getAkreditasiColor(akreditasi) {
  const colors = {
    'A': 'bg-success text-white',
    'B': 'bg-primary-600 text-white',
    'C': 'bg-warning text-white',
    'Unggul': 'bg-success text-white',
    'Baik Sekali': 'bg-primary-600 text-white',
    'Baik': 'bg-info-600 text-white',
  }
  return colors[akreditasi] || 'bg-gray-200 text-gray-700'
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Sinkronisasi PDDikti"
      subtitle="Sinkronkan data perguruan tinggi dan program studi dari API PDDikti ke database lokal"
    />

    <div class="space-y-6 animate-fade-in">

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card">
          <div class="card-body">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center">
                <i class="ri-government-line text-2xl text-primary-600"></i>
              </div>
              <div>
                <p class="text-sm text-secondary-500">Perguruan Tinggi</p>
                <p class="text-2xl font-bold text-secondary-800">{{ stats.total_perguruan_tinggi }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center">
                <i class="ri-book-open-line text-2xl text-success"></i>
              </div>
              <div>
                <p class="text-sm text-secondary-500">Program Studi</p>
                <p class="text-2xl font-bold text-secondary-800">{{ stats.total_prodi }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                <i class="ri-refresh-line text-2xl text-amber-600"></i>
              </div>
              <div>
                <p class="text-sm text-secondary-500">Terakhir Sync</p>
                <p class="text-sm font-medium text-secondary-800">{{ formatDate(stats.last_sync) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sync Universitas -->
      <div class="card animate-slide-up">
        <div class="card-header">
          <h3 class="card-title flex items-center gap-2">
            <i class="ri-download-cloud-2-line text-lg text-primary-600"></i>
            Sync Perguruan Tinggi
          </h3>
        </div>
        <div class="card-body">
          <p class="text-sm text-secondary-500 mb-4">
            Masukkan nama perguruan tinggi untuk mencari dan menyimpan data dari PDDikti ke database lokal.
          </p>

          <div class="flex gap-2">
            <input
              v-model="syncKeyword"
              type="text"
              class="input-field flex-1"
              placeholder="Contoh: Universitas Indonesia, Institut Teknologi Bandung..."
              @keypress.enter="handleSyncUniversitas"
            />
            <button
              @click="handleSyncUniversitas"
              :disabled="syncing || !syncKeyword || syncKeyword.length < 3"
              class="btn btn-primary"
            >
              <LoadingSpinner v-if="syncing" size="sm" />
              <i v-else class="ri-search-line"></i>
              <span class="ml-2">Cari & Sync</span>
            </button>
          </div>

          <!-- Sync Result -->
          <div v-if="syncResult" class="mt-4 p-3 rounded-lg" :class="syncResult.synced > 0 ? 'bg-green-50 border border-green-200' : 'bg-secondary-50 border border-secondary-200'">
            <p class="text-sm font-medium" :class="syncResult.synced > 0 ? 'text-green-800' : 'text-secondary-700'">
              <i :class="syncResult.synced > 0 ? 'ri-checkbox-circle-fill text-success' : 'ri-information-line text-secondary-500'"></i>
              {{ syncResult.synced }} data baru disimpan, {{ syncResult.updated }} data diperbarui
            </p>
          </div>
        </div>
      </div>

      <!-- Local PT List & Prodi Sync -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- PT List -->
        <div class="card animate-slide-up" style="animation-delay: 50ms;">
          <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
              <i class="ri-list-check text-lg text-primary-600"></i>
              Data Tersimpan
            </h3>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <input
                v-model="ptSearch"
                type="text"
                class="input-field w-full text-sm"
                placeholder="Cari perguruan tinggi..."
                @input="fetchPTList"
              />
            </div>

            <div class="max-h-80 overflow-y-auto space-y-1">
              <div v-if="loadingPT" class="p-4 text-center text-secondary-500">
                <LoadingSpinner size="sm" />
                <p class="text-xs mt-2">Memuat data...</p>
              </div>

              <div v-else-if="ptList.length === 0" class="p-4 text-center text-secondary-500">
                <i class="ri-inbox-line text-2xl text-secondary-400"></i>
                <p class="text-xs mt-1">Belum ada data</p>
              </div>

              <div
                v-else
                v-for="pt in ptList"
                :key="pt.id"
                @click="handleSelectPT(pt)"
                class="p-2 rounded-lg cursor-pointer transition-colors"
                :class="selectedPT?.id === pt.id ? 'bg-primary-50 border border-primary-200' : 'hover:bg-secondary-50 border border-transparent'"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-secondary-800 truncate">{{ pt.nama_pt }}</p>
                    <p class="text-xs text-secondary-500">{{ pt.kode_pt }}</p>
                  </div>
                  <i v-if="selectedPT?.id === pt.id" class="ri-check-line text-primary-600 flex-shrink-0"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Prodi Detail & Sync -->
        <div class="card animate-slide-up" style="animation-delay: 100ms;">
          <div class="card-header">
            <div class="flex items-center justify-between">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-book-mark-line text-lg text-primary-600"></i>
                Program Studi
              </h3>
              <button
                v-if="selectedPT"
                @click="handleSyncProdi"
                :disabled="syncingProdi"
                class="btn btn-primary btn-sm"
              >
                <LoadingSpinner v-if="syncingProdi" size="xs" />
                <i v-else class="ri-refresh-line"></i>
                <span class="ml-1">Sync Prodi</span>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="!selectedPT" class="p-6 text-center text-secondary-500">
              <i class="ri-arrow-left-line text-2xl text-secondary-400"></i>
              <p class="text-sm mt-2">Pilih perguruan tinggi dari daftar</p>
            </div>

            <div v-else>
              <div class="mb-3 p-2 bg-primary-50 rounded-lg">
                <p class="text-sm font-medium text-primary-800">{{ selectedPT.nama_pt }}</p>
                <p class="text-xs text-primary-600">{{ selectedPT.provinsi }}</p>
              </div>

              <div v-if="loadingProdi" class="p-4 text-center text-secondary-500">
                <LoadingSpinner size="sm" />
                <p class="text-xs mt-2">Memuat prodi...</p>
              </div>

              <div v-else-if="prodiList.length === 0" class="p-4 text-center text-secondary-500">
                <i class="ri-book-open-line text-2xl text-secondary-400"></i>
                <p class="text-xs mt-1">Belum ada data prodi</p>
                <p class="text-xs text-secondary-400 mt-1">Klik "Sync Prodi" untuk mengambil data</p>
              </div>

              <div v-else class="max-h-56 overflow-y-auto space-y-1">
                <div
                  v-for="prodi in prodiList"
                  :key="prodi.id"
                  class="p-2 bg-secondary-50 rounded-lg"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-secondary-800 truncate">{{ prodi.nama_prodi }}</p>
                      <div class="flex items-center gap-1 mt-1">
                        <span class="px-1.5 py-0.5 rounded text-xs font-medium" :class="getJenjangColor(prodi.jenjang)">
                          {{ prodi.jenjang }}
                        </span>
                        <span v-if="prodi.akreditasi" class="px-1.5 py-0.5 rounded text-xs font-medium" :class="getAkreditasiColor(prodi.akreditasi)">
                          {{ prodi.akreditasi }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.input-field {
  @apply px-3 py-2 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent;
}
</style>
