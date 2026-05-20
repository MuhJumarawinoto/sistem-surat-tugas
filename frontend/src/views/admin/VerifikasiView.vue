<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)

onMounted(async () => {
  await loadPengajuan()
})

function openSendMessageModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}

async function loadPengajuan() {
  loading.value = true
  try {
    // Ambil semua pengajuan yang perlu verifikasi admin (pending_admin dan pending_atasan)
    // Filter akan dilakukan di backend berdasarkan role admin
    const response = await pengajuanStore.fetchPengajuan()
    pengajuanList.value = response.data || []
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

function getStatusBadgeClass(status) {
  const classes = {
    'pending_atasan': 'badge-warning',
    'pending_admin': 'badge-primary',
    'verified': 'badge-info',
    'disetujui': 'badge-success',
    'ditolak': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

function getStatusLabel(status) {
  const labels = {
    'pending_atasan': 'Menunggu Atasan',
    'pending_admin': 'Menunggu Verifikasi',
    'verified': 'Terverifikasi',
    'disetujui': 'Disetujui',
    'ditolak': 'Ditolak'
  }
  return labels[status] || status
}
</script>

<template>
  <MainLayout>
    <div class="mb-6 animate-fade-in">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">Verifikasi Pengajuan</h2>
          <p class="text-secondary-500 mt-1">Daftar pengajuan yang menunggu verifikasi admin</p>
        </div>
      </div>
    </div>

    <div class="card animate-slide-up">
          <div class="card-body">
            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat..." />
            </div>

            <div v-else-if="pengajuanList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500">Tidak ada pengajuan yang menunggu verifikasi</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="item in pengajuanList" :key="item.id" class="p-4 border border-secondary-200 rounded-xl hover:border-primary-300 hover:shadow-sm transition-all">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <p class="text-base font-semibold text-secondary-800">{{ item.nomor_pengajuan }}</p>
                      <span class="badge" :class="getStatusBadgeClass(item.status)">
                        {{ getStatusLabel(item.status) }}
                      </span>
                    </div>
                    <p class="text-sm text-secondary-600">{{ item.user?.name }} - {{ item.user?.unit_kerja }}</p>
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
                    <router-link v-if="item.status === 'pending_admin'" :to="`/admin/surat/${item.id}`" class="btn btn-primary btn-sm">
                      <i class="ri-check-line mr-1"></i>
                      Verifikasi
                    </router-link>
                    <span v-else class="text-xs text-secondary-500 italic">Menunggu atasan</span>
                  </div>
                </div>
              </div>
            </div>
        </div>
      </div>

    <SendMessageModal
      :show="showModal"
      :pengajuan-id="selectedPengajuan?.id"
      :pemohon-name="selectedPengajuan?.user?.name"
      @close="showModal = false"
      @sent="handleMessageSent"
    />
  </MainLayout>
</template>
