<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const pengajuanStore = usePengajuanStore()

const suratList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)

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

function openSendMessageModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}
</script>

<template>
  <div class="flex min-h-screen bg-secondary-50">
    <AppSidebar />
    <div class="flex-1 flex flex-col">
      <AppHeader />
      <main class="flex-1 p-6 overflow-y-auto">
        <div class="mb-6 animate-fade-in">
          <h2 class="text-2xl font-bold text-secondary-800">Tanda Tangan Surat</h2>
          <p class="text-secondary-500 mt-1">Daftar surat yang siap ditandatangani</p>
        </div>

        <div class="card animate-slide-up">
          <div class="card-body">
            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat..." />
            </div>

            <div v-else-if="suratList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-file-sign-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500">Tidak ada surat yang menunggu tanda tangan</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="item in suratList" :key="item.id" class="p-4 border border-secondary-200 rounded-xl hover:border-primary-300 hover:shadow-sm transition-all">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <p class="text-base font-semibold text-secondary-800">{{ item.nomor_pengajuan }}</p>
                    <p class="text-sm text-secondary-600 mt-1">{{ item.user?.name }}</p>
                    <p class="text-sm text-secondary-500">{{ item.nama_prodi }} - {{ item.perguruan_tinggi }}</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="openSendMessageModal(item)"
                      class="btn btn-ghost btn-sm"
                      title="Kirim Pesan"
                    >
                      <i class="ri-message-3-line text-lg"></i>
                    </button>
                    <button @click="signSurat(item.id)" class="btn btn-primary btn-sm">
                      <i class="ri-edit-line mr-1"></i>
                      Tanda Tangan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <SendMessageModal
    :show="showModal"
    :pengajuan-id="selectedPengajuan?.id"
    :pemohon-name="selectedPengajuan?.user?.name"
    @close="showModal = false"
    @sent="handleMessageSent"
  />
</template>
