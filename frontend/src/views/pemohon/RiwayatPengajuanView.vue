<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const loading = ref(false)
const currentPage = ref(1)

onMounted(async () => {
  await loadPengajuan()
})

async function loadPengajuan() {
  loading.value = true
  try {
    const response = await pengajuanStore.fetchPengajuan({ page: currentPage.value })
    pengajuanList.value = response.data || []
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
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
        <div class="mb-6 flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-900">Riwayat Pengajuan</h2>
          <router-link to="/pengajuan/baru" class="btn-primary">
            Buat Pengajuan Baru
          </router-link>
        </div>

        <div class="card">
          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Memuat...</p>
          </div>

          <div v-else-if="pengajuanList.length === 0" class="text-center py-8 text-gray-500">
            <p>Belum ada pengajuan</p>
            <router-link to="/pengajuan/baru" class="btn-primary inline-block mt-4">
              Buat Pengajuan Baru
            </router-link>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenjang</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program Studi</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Universitas</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in pengajuanList" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.nomor_pengajuan }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.jenjang?.nama }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ item.nama_prodi }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ item.perguruan_tinggi }}
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
