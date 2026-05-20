<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const route = useRoute()
const pengajuanStore = usePengajuanStore()

const pengajuan = ref(null)
const loading = ref(false)
const showModal = ref(false)

onMounted(async () => {
  await loadPengajuan()
})

async function loadPengajuan() {
  loading.value = true
  try {
    pengajuan.value = await pengajuanStore.fetchPengajuanDetail(route.params.id)
  } catch (error) {
    alert('Gagal memuat pengajuan')
  } finally {
    loading.value = false
  }
}

async function approvePengajuan() {
  if (!confirm('Setujui pengajuan ini?')) return

  try {
    await api.post(`/pengajuan/${route.params.id}/approve-admin`)
    alert('Pengajuan disetujui')
    await loadPengajuan()
  } catch (error) {
    alert('Gagal menyetujui pengajuan')
  }
}

async function rejectPengajuan() {
  const catatan = prompt('Alasan penolakan:')
  if (!catatan) return

  try {
    await api.post(`/pengajuan/${route.params.id}/reject`, { catatan })
    alert('Pengajuan ditolak')
    window.location.href = '/admin/verifikasi'
  } catch (error) {
    alert('Gagal menolak pengajuan')
  }
}

function openSendMessageModal() {
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}
</script>

<template>
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="md" text="Memuat..." />
    </div>

    <div v-else-if="pengajuan" class="space-y-6 animate-fade-in">
      <Breadcrumb :current-page="pengajuan.nomor_pengajuan" />

      <div class="mb-4">
        <h2 class="text-2xl font-bold text-secondary-800">Verifikasi Pengajuan</h2>
        <p class="text-secondary-500 mt-1">{{ pengajuan.nomor_pengajuan }}</p>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-user-line text-primary-600"></i>
                Data Pemohon
              </h3>
            </div>
            <div class="card-body">
              <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div>
                  <dt class="text-secondary-500">Nama</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.user?.name }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">NIP</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.user?.nip }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">Unit Kerja</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.user?.unit_kerja }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">Jabatan</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.user?.jabatan }}</dd>
                </div>
              </dl>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-graduation-cap-line text-primary-600"></i>
                Data Pendidikan
              </h3>
            </div>
            <div class="card-body">
              <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div>
                  <dt class="text-secondary-500">Jenjang</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.jenjang?.nama }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">Program Studi</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.nama_prodi }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">Perguruan Tinggi</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.perguruan_tinggi }}</dd>
                </div>
                <div>
                  <dt class="text-secondary-500">Akreditasi</dt>
                  <dd class="text-secondary-800 font-medium">{{ pengajuan.akreditasi_prodi }}</dd>
                </div>
              </dl>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-file-text-line text-primary-600"></i>
                Dokumen
              </h3>
            </div>
            <div class="card-body">
              <div v-if="pengajuan.dokumen && pengajuan.dokumen.length > 0" class="space-y-2">
                <div v-for="doc in pengajuan.dokumen" :key="doc.id" class="flex justify-between items-center p-3 border border-secondary-200 rounded-lg">
                  <span class="text-sm text-secondary-700">{{ doc.file_name }}</span>
                  <span class="text-sm text-secondary-500">{{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB</span>
                </div>
              </div>
              <div v-else class="text-center py-8 text-secondary-500">
                <i class="ri-file-line text-3xl text-secondary-300"></i>
                <p class="mt-2">Tidak ada dokumen</p>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button @click="openSendMessageModal" class="btn btn-secondary flex-1 justify-center">
              <i class="ri-message-3-line mr-2"></i>
              Kirim Pesan
            </button>
            <button @click="approvePengajuan" class="btn btn-primary flex-1 justify-center">
              <i class="ri-check-line mr-2"></i>
              Setujui & Buat Surat
            </button>
            <button @click="rejectPengajuan" class="btn btn-danger flex-1 justify-center">
              <i class="ri-close-line mr-2"></i>
              Tolak
            </button>
        </div>
      </div>

    <SendMessageModal
      :show="showModal"
      :pengajuan-id="route.params.id"
      :pemohon-name="pengajuan?.user?.name"
      @close="showModal = false"
      @sent="handleMessageSent"
    />
  </MainLayout>
</template>
