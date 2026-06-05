<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()

const suratList = ref([])
const loading = ref(false)
const searchQuery = ref('')

const headerActions = computed(() => [
  {
    label: 'Refresh',
    icon: 'ri-refresh-line',
    onClick: loadSurat,
    variant: 'btn-secondary'
  },
  {
    label: 'Tanda Tangan Baru',
    icon: 'ri-edit-line',
    to: '/kepala/signing',
    variant: 'btn-primary'
  }
])

onMounted(async () => {
  await loadSurat()
})

async function loadSurat() {
  loading.value = true
  try {
    // Ambil surat izin yang sudah ditandatangani (status: signed)
    const response = await api.get('/admin/surat-izin', {
      params: {
        status: 'signed',
        per_page: 100
      }
    })

    // Handle different response formats
    let data = []
    if (response.data?.data && Array.isArray(response.data.data)) {
      data = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      data = response.data
    } else if (Array.isArray(response.data)) {
      data = response.data
    }

    suratList.value = data
  } catch (error) {
    console.error('Failed to load surat history:', error)
  } finally {
    loading.value = false
  }
}

function viewDetail(id) {
  router.push(`/kepala/signing/${id}`)
}

async function downloadSurat(surat) {
  if (!surat.id) {
    alert('ID surat tidak tersedia')
    return
  }

  try {
    // Use direct download with token to avoid CORS issues
    const token = localStorage.getItem('token')
    const baseUrl = import.meta.env.VITE_API_URL
      ? import.meta.env.VITE_API_URL.replace('/api', '')
      : 'http://localhost:8000'

    const url = `${baseUrl}/api/admin/surat-izin/${surat.id}/download?token=${encodeURIComponent(token)}`
    window.open(url, '_blank')
  } catch (error) {
    console.error('Download failed:', error)
    const message = error.response?.data?.message || 'Gagal mendownload surat'
    alert(message)
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const filteredList = computed(() => {
  if (!searchQuery.value.trim()) return suratList.value

  const query = searchQuery.value.toLowerCase()
  return suratList.value.filter(s => {
    return (
      (s.nomor_surat && s.nomor_surat.toLowerCase().includes(query)) ||
      (s.pengajuan?.nomor_pengajuan && s.pengajuan.nomor_pengajuan.toLowerCase().includes(query)) ||
      (s.pengajuan?.nama_prodi && s.pengajuan.nama_prodi.toLowerCase().includes(query)) ||
      (s.pengajuan?.perguruan_tinggi && s.pengajuan.perguruan_tinggi.toLowerCase().includes(query)) ||
      (s.pengajuan?.user?.name && s.pengajuan.user.name.toLowerCase().includes(query))
    )
  })
})

// Stats
const stats = computed(() => ({
  total: suratList.value.length,
  thisMonth: suratList.value.filter(s => {
    if (!s.signed_at) return false
    const date = new Date(s.signed_at)
    const now = new Date()
    return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()
  }).length,
  thisYear: suratList.value.filter(s => {
    if (!s.signed_at) return false
    const date = new Date(s.signed_at)
    const now = new Date()
    return date.getFullYear() === now.getFullYear()
  }).length
}))
</script>

<template>
  <MainLayout>
    <PageHeader
      title="Riwayat Tanda Tangan"
      subtitle="Daftar surat yang sudah ditandatangani"
      :actions="headerActions"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary-500">Total Ditandatangani</p>
              <p class="text-2xl font-bold text-secondary-800">{{ stats.total }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center">
              <i class="ri-file-text-line text-2xl text-primary-600"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary-500">Bulan Ini</p>
              <p class="text-2xl font-bold text-secondary-800">{{ stats.thisMonth }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center">
              <i class="ri-calendar-line text-2xl text-success"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body py-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-secondary-500">Tahun Ini</p>
              <p class="text-2xl font-bold text-secondary-800">{{ stats.thisYear }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center">
              <i class="ri-calendar-check-line text-2xl text-info"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="card mb-4">
      <div class="card-body py-3">
        <div class="relative">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nomor surat, nama, prodi..."
            class="w-full pl-10 pr-4 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat..." />
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredList.length === 0" class="card">
      <div class="card-body text-center py-12">
        <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
          <i class="ri-history-line text-3xl text-secondary-400"></i>
        </div>
        <p class="text-secondary-500 mb-4">Belum ada riwayat tanda tangan</p>
        <router-link to="/kepala/signing" class="btn btn-primary btn-sm">
          <i class="ri-edit-line mr-1"></i>
          Tandatangani Surat
        </router-link>
      </div>
    </div>

    <!-- List -->
    <div v-else class="space-y-4">
      <div
        v-for="item in filteredList"
        :key="item.id"
        class="card hover:shadow-md transition-shadow"
      >
        <div class="card-body">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Info -->
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="font-semibold text-secondary-800">{{ item.nomor_surat || '-' }}</span>
                <span class="badge badge-success">
                  <i class="ri-checkbox-circle-line mr-1"></i>Sudah Ditandatangani
                </span>
                <span class="text-xs text-secondary-500">
                  <i class="ri-time-line mr-1"></i>{{ formatDate(item.signed_at) }}
                </span>
              </div>
              <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
                <span><i class="ri-file-list-line mr-1"></i>{{ item.pengajuan?.nomor_pengajuan || '-' }}</span>
                <span><i class="ri-user-line mr-1"></i>{{ item.pengajuan?.user?.name }}</span>
                <span><i class="ri-graduation-cap-line mr-1"></i>{{ item.pengajuan?.nama_prodi }}</span>
                <span><i class="ri-building-line mr-1"></i>{{ item.pengajuan?.perguruan_tinggi }}</span>
              </div>
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button
                @click="viewDetail(item.id)"
                class="btn btn-secondary btn-sm"
                title="Lihat Detail"
              >
                <i class="ri-eye-line"></i>
              </button>
              <button
                @click="downloadSurat(item)"
                class="btn btn-success btn-sm"
                title="Download Surat"
              >
                <i class="ri-download-line"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
