<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()
const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const loading = ref(false)
const searchQuery = ref('')
const submitting = ref(false)

const headerActions = computed(() => [
  {
    label: 'Refresh',
    icon: 'ri-refresh-line',
    onClick: loadPengajuan,
    variant: 'btn-secondary'
  }
])

onMounted(async () => {
  await loadPengajuan()
})

async function loadPengajuan() {
  loading.value = true
  try {
    // New flow: Get verified pengajuan (pending surat izin belajar)
    const response = await api.get('/admin/surat-izin/pending', {
      params: {
        per_page: 50
      }
    })
    // Handle different response formats
    let pengajuanData = []
    if (response.data?.data && Array.isArray(response.data.data)) {
      pengajuanData = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      pengajuanData = response.data
    } else if (Array.isArray(response.data)) {
      pengajuanData = response.data
    }

    console.log('Pengajuan list:', pengajuanData)

    // Map pengajuan ke format yang dibutuhkan view
    pengajuanList.value = pengajuanData.map(p => ({
      id: p.id, // Use pengajuan ID
      pengajuan_id: p.id,
      nomor_pengajuan: p.nomor_pengajuan,
      nama_prodi: p.nama_prodi,
      perguruan_tinggi: p.perguruan_tinggi,
      status: 'verified', // verified berarti perlu generate & sign Surat Izin
      user: p.user,
      jenjang: p.jenjang
    }))

    console.log('Mapped list:', pengajuanList.value)
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

async function goToSigning(id) {
  submitting.value = true
  try {
    // New flow: Generate & sign Surat Izin Belajar in one step
    const response = await api.post('/admin/surat-izin', {
      pengajuan_id: id
    })
    console.log('Surat generated:', response.data)
    alert('Surat Izin Belajar berhasil dibuat dan ditandatangani!')
    await loadPengajuan()
  } catch (error) {
    console.error('Failed to generate surat:', error)
    alert('Gagal membuat surat: ' + (error.response?.data?.message || error.message))
  } finally {
    submitting.value = false
  }
}

function getStatusLabel(status) {
  const labels = {
    verified: 'Perlu Generate & TTE',
    draft: 'Draft',
    signed: 'Sudah Ditandatangani',
    selesai: 'Selesai',
    completed: 'Selesai'
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    verified: 'badge-primary',
    draft: 'badge-secondary',
    signed: 'badge-success',
    selesai: 'badge-success',
    completed: 'badge-success'
  }
  return badges[status] || 'badge-default'
}

const filteredList = computed(() => {
  if (!searchQuery.value.trim()) return pengajuanList.value

  const query = searchQuery.value.toLowerCase()
  return pengajuanList.value.filter(p => {
    return (
      (p.nomor_pengajuan && p.nomor_pengajuan.toLowerCase().includes(query)) ||
      (p.nama_prodi && p.nama_prodi.toLowerCase().includes(query)) ||
      (p.perguruan_tinggi && p.perguruan_tinggi.toLowerCase().includes(query)) ||
      (p.user?.name && p.user.name.toLowerCase().includes(query))
    )
  })
})
</script>

<template>
  <MainLayout>
    <PageHeader
      title="Tanda Tangan Surat Izin Belajar"
      subtitle="Daftar pengajuan verified yang siap dibuatkan Surat Izin Belajar dengan TTE"
      :actions="headerActions"
    />

    <!-- Search -->
    <div class="card mb-4">
      <div class="card-body py-3">
        <div class="relative">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nomor, nama, prodi..."
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
          <i class="ri-file-text-line text-3xl text-secondary-400"></i>
        </div>
        <p class="text-secondary-500">Tidak ada pengajuan yang siap dibuatkan Surat Izin Belajar</p>
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
                <span class="font-semibold text-secondary-800">{{ item.nomor_pengajuan || '-' }}</span>
                <span :class="['badge', getStatusBadge(item.status)]">
                  {{ getStatusLabel(item.status) }}
                </span>
              </div>
              <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-600">
                <span><i class="ri-user-line mr-1"></i>{{ item.user?.name }}</span>
                <span><i class="ri-graduation-cap-line mr-1"></i>{{ item.nama_prodi }}</span>
                <span><i class="ri-building-line mr-1"></i>{{ item.perguruan_tinggi }}</span>
              </div>
            </div>
            <!-- Action -->
            <div class="flex items-center gap-2">
              <button
                @click="goToSigning(item.id)"
                :disabled="submitting"
                class="btn btn-primary btn-sm"
              >
                <i class="ri-edit-line mr-1"></i>
                {{ submitting ? 'Memproses...' : 'Generate & TTE' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
