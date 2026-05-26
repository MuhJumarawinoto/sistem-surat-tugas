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
    // Ambil surat izin yang statusnya draft (perlu TTE)
    const response = await api.get('/admin/surat-izin', {
      params: {
        status: 'draft',
        per_page: 50
      }
    })
    // Handle different response formats
    let suratList = []
    if (response.data?.data && Array.isArray(response.data.data)) {
      suratList = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      suratList = response.data
    } else if (Array.isArray(response.data)) {
      suratList = response.data
    }

    console.log('Surat list:', suratList)

    // Map surat izin ke format yang dibutuhkan view
    pengajuanList.value = suratList.map(surat => ({
      id: surat.id, // Use surat_izin ID as primary ID
      pengajuan_id: surat.pengajuan?.id,
      nomor_pengajuan: surat.pengajuan?.nomor_pengajuan,
      nama_prodi: surat.pengajuan?.nama_prodi,
      perguruan_tinggi: surat.pengajuan?.perguruan_tinggi,
      status: 'draft', // draft berarti perlu TTE
      user: surat.pengajuan?.user,
      jenjang: surat.pengajuan?.jenjang,
      surat_izin: surat
    }))

    console.log('Mapped list:', pengajuanList.value)
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

function goToSigning(id) {
  // Navigate ke signing detail dengan surat_izin_id
  router.push(`/kepala/signing/${id}`)
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Perlu TTE',
    signed: 'Sudah Ditandatangani',
    selesai: 'Selesai',
    completed: 'Selesai'
  }
  return labels[status] || status
}

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-warning',
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
      title="Tanda Tangan Surat"
      subtitle="Daftar pengajuan yang siap ditandatangani"
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
        <p class="text-secondary-500">Tidak ada pengajuan yang siap ditandatangani</p>
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
                class="btn btn-primary btn-sm"
              >
                <i class="ri-edit-line mr-1"></i>
                Tandatangani
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
