<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const loading = ref(false)

onMounted(async () => {
  await loadPengajuan()
})

async function loadPengajuan() {
  loading.value = true
  try {
    const response = await pengajuanStore.fetchPengajuan({ status: 'pending_admin' })
    pengajuanList.value = response.data || []
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Verifikasi Pengajuan</h2>

        <div class="card">
          <p class="text-gray-500 mb-4">Daftar pengajuan yang menunggu verifikasi admin</p>

          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Loading...</p>
          </div>

          <div v-else-if="pengajuanList.length === 0" class="text-center py-8 text-gray-500">
            <p>Tidak ada pengajuan yang menunggu verifikasi</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="item in pengajuanList" :key="item.id" class="p-4 border rounded-lg">
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-medium text-gray-900">{{ item.nomor_pengajuan }}</p>
                  <p class="text-sm text-gray-500">{{ item.user?.name }} - {{ item.user?.unit_kerja }}</p>
                  <p class="text-sm text-gray-500">{{ item.nama_prodi }} - {{ item.perguruan_tinggi }}</p>
                </div>
                <router-link :to="`/admin/surat/${item.id}`" class="btn-primary text-sm">
                  Verifikasi
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
