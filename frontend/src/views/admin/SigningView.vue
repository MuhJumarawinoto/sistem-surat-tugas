<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const pengajuanStore = usePengajuanStore()

const suratList = ref([])
const loading = ref(false)

onMounted(async () => {
  await loadSurat()
})

async function loadSurat() {
  loading.value = true
  try {
    const response = await api.get('/pengajuan', { params: { status: 'disetujui' } })
    suratList.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load surat:', error)
  } finally {
    loading.value = false
  }
}

async function signSurat(id) {
  if (!confirm('Tanda tangani surat ini?')) return

  try {
    await api.post(`/surat/${id}/sign-tte`)
    alert('Surat berhasil ditandatangani')
    await loadSurat()
  } catch (error) {
    alert('Gagal menandatangani surat')
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Tanda Tangan Surat</h2>

        <div class="card">
          <p class="text-gray-500 mb-4">Daftar surat yang siap ditandatangani</p>

          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Loading...</p>
          </div>

          <div v-else-if="suratList.length === 0" class="text-center py-8 text-gray-500">
            <p>Tidak ada surat yang menunggu tanda tangan</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="item in suratList" :key="item.id" class="p-4 border rounded-lg">
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-medium text-gray-900">{{ item.nomor_pengajuan }}</p>
                  <p class="text-sm text-gray-500">{{ item.user?.name }}</p>
                  <p class="text-sm text-gray-500">{{ item.nama_prodi }} - {{ item.perguruan_tinggi }}</p>
                </div>
                <button @click="signSurat(item.id)" class="btn-primary text-sm">
                  Tanda Tangan
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
