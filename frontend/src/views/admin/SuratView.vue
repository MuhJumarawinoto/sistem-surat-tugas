<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const route = useRoute()
const pengajuanStore = usePengajuanStore()

const pengajuan = ref(null)
const loading = ref(false)

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
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-500">Loading...</p>
        </div>

        <div v-else-if="pengajuan" class="space-y-6">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Verifikasi Pengajuan</h2>
              <p class="text-gray-600">{{ pengajuan.nomor_pengajuan }}</p>
            </div>
          </div>

          <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Pemohon</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-500">Nama</dt>
                <dd class="text-gray-900">{{ pengajuan.user?.name }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">NIP</dt>
                <dd class="text-gray-900">{{ pengajuan.user?.nip }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Unit Kerja</dt>
                <dd class="text-gray-900">{{ pengajuan.user?.unit_kerja }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Jabatan</dt>
                <dd class="text-gray-900">{{ pengajuan.user?.jabatan }}</dd>
              </div>
            </dl>
          </div>

          <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Pendidikan</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-500">Jenjang</dt>
                <dd class="text-gray-900">{{ pengajuan.jenjang?.nama }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Program Studi</dt>
                <dd class="text-gray-900">{{ pengajuan.nama_prodi }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Perguruan Tinggi</dt>
                <dd class="text-gray-900">{{ pengajuan.perguruan_tinggi }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Akreditasi</dt>
                <dd class="text-gray-900">{{ pengajuan.akreditasi_prodi }}</dd>
              </div>
            </dl>
          </div>

          <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dokumen</h3>
            <div v-if="pengajuan.dokumen && pengajuan.dokumen.length > 0" class="space-y-2">
              <div v-for="doc in pengajuan.dokumen" :key="doc.id" class="flex justify-between items-center p-3 border rounded-lg">
                <span class="text-sm text-gray-700">{{ doc.file_name }}</span>
                <span class="text-xs text-gray-500">{{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB</span>
              </div>
            </div>
          </div>

          <div class="flex space-x-4">
            <button @click="approvePengajuan" class="btn-primary">
              Setujui & Buat Surat
            </button>
            <button @click="rejectPengajuan" class="btn-danger">
              Tolak
            </button>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
