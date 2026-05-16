<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePengajuanStore } from '@/stores/pengajuan'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const router = useRouter()
const authStore = useAuthStore()
const pengajuanStore = usePengajuanStore()

const stats = ref({
  draft: 0,
  pending: 0,
  disetujui: 0,
  ditolak: 0,
  selesai: 0,
})

// Use computed property for reactive updates
const recentPengajuan = computed(() => pengajuanStore.pengajuanList.slice(0, 5))

onMounted(async () => {
  await loadStats()
})

// Watch for changes in store and update stats
watch(() => pengajuanStore.pengajuanList, (newList) => {
  updateStats(newList)
}, { deep: true })

function updateStats(pengajuan) {
  stats.value = {
    draft: pengajuan.filter((p) => p.status === 'draft').length,
    pending: pengajuan.filter((p) => p.status === 'pending_atasan' || p.status === 'pending_admin').length,
    disetujui: pengajuan.filter((p) => p.status === 'disetujui').length,
    ditolak: pengajuan.filter((p) => p.status === 'ditolak').length,
    selesai: pengajuan.filter((p) => p.status === 'selesai').length,
  }
}

async function loadStats() {
  try {
    const response = await pengajuanStore.fetchPengajuan({ per_page: 100 })
    updateStats(response.data || [])
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Draft',
    pending_atasan: 'Pending Atasan',
    pending_admin: 'Pending Admin',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    selesai: 'Selesai',
  }
  return labels[status] || status
}

function getStatusColor(status) {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    pending_atasan: 'bg-yellow-100 text-yellow-800',
    pending_admin: 'bg-blue-100 text-blue-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
    selesai: 'bg-purple-100 text-purple-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
          <p class="text-gray-600">Selamat datang, {{ authStore.user?.name }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
          <div class="card">
            <div class="text-center">
              <p class="text-3xl font-bold text-gray-900">{{ stats.draft }}</p>
              <p class="text-sm text-gray-600">Draft</p>
            </div>
          </div>
          <div class="card">
            <div class="text-center">
              <p class="text-3xl font-bold text-yellow-600">{{ stats.pending }}</p>
              <p class="text-sm text-gray-600">Pending</p>
            </div>
          </div>
          <div class="card">
            <div class="text-center">
              <p class="text-3xl font-bold text-green-600">{{ stats.disetujui }}</p>
              <p class="text-sm text-gray-600">Disetujui</p>
            </div>
          </div>
          <div class="card">
            <div class="text-center">
              <p class="text-3xl font-bold text-red-600">{{ stats.ditolak }}</p>
              <p class="text-sm text-gray-600">Ditolak</p>
            </div>
          </div>
          <div class="card">
            <div class="text-center">
              <p class="text-3xl font-bold text-purple-600">{{ stats.selesai }}</p>
              <p class="text-sm text-gray-600">Selesai</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Pengajuan Terbaru</h3>
            <router-link
              to="/pengajuan"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              Lihat Semua
            </router-link>
          </div>

          <div v-if="recentPengajuan.length === 0" class="text-center py-8 text-gray-500">
            <p>Belum ada pengajuan</p>
            <router-link
              to="/pengajuan/baru"
              class="btn-primary inline-block mt-4"
            >
              Buat Pengajuan Baru
            </router-link>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nomor
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Program Studi
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tanggal
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in recentPengajuan" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.nomor_pengajuan }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.nama_prodi }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(item.status)]">
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <router-link
                      :to="`/pengajuan/${item.id}`"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      Lihat
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
