<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const pengajuanStore = usePengajuanStore()
const authStore = useAuthStore()

const pengajuanList = ref([])
const loading = ref(false)
const filterStatus = ref('pending_atasan')
const approving = ref(false)

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_atasan', label: 'Pending Atasan' },
  { value: 'pending_admin', label: 'Pending Admin' },
  { value: 'disetujui', label: 'Disetujui' },
  { value: 'ditolak', label: 'Ditolak' },
]

onMounted(async () => {
  await loadPengajuan()
})

async function loadPengajuan() {
  loading.value = true
  try {
    const params = {}
    if (filterStatus.value) {
      params.status = filterStatus.value
    }
    const response = await pengajuanStore.fetchPengajuan(params)
    pengajuanList.value = response.data || []
    console.log('Pengajuan list:', pengajuanList.value)
    console.log('User unit kerja:', authStore.user?.unit_kerja_id)
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
    alert('Gagal memuat pengajuan: ' + (error.response?.data?.message || error.message))
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

async function approvePengajuan(id) {
  if (!confirm('Setujui pengajuan ini?')) return

  approving.value = true
  try {
    await api.post(`/pengajuan/${id}/approve-atasan`)
    alert('Pengajuan disetujui')
    await loadPengajuan()
  } catch (error) {
    alert('Gagal menyetujui: ' + (error.response?.data?.message || error.message))
  } finally {
    approving.value = false
  }
}

async function rejectPengajuan(id) {
  const catatan = prompt('Alasan penolakan:')
  if (!catatan) return

  approving.value = true
  try {
    await api.post(`/pengajuan/${id}/reject`, { catatan })
    alert('Pengajuan ditolak')
    await loadPengajuan()
  } catch (error) {
    alert('Gagal menolak: ' + (error.response?.data?.message || error.message))
  } finally {
    approving.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar />
    <div class="flex-1">
      <AppHeader />
      <main class="p-6">
        <div class="mb-6 flex justify-between items-center">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Persetujuan Pengajuan</h2>
            <p class="text-gray-600">Unit Kerja: {{ authStore.user?.unit_kerja || '-' }}</p>
          </div>
          <select v-model="filterStatus" @change="loadPengajuan" class="input-field w-48">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <div class="card">
          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Loading...</p>
          </div>

          <div v-else-if="pengajuanList.length === 0" class="text-center py-8">
            <p class="text-gray-500 mb-4">Tidak ada pengajuan</p>
            <div class="text-sm text-gray-400">
              <p>Pastikan:</p>
              <p>1. Pemohon sudah submit pengajuan (bukan Draft)</p>
              <p>2. Pemohon berada di unit kerja yang sama dengan Anda</p>
            </div>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemohon</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program Studi</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Universitas</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in pengajuanList" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.nomor_pengajuan }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <p class="font-medium text-gray-900">{{ item.user?.name }}</p>
                    <p class="text-gray-500">NIP: {{ item.user?.nip }}</p>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ item.nama_prodi }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    {{ item.perguruan_tinggi }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(item.status)]">
                      {{ getStatusLabel(item.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div v-if="item.status === 'pending_atasan'" class="flex space-x-2">
                      <button
                        @click="approvePengajuan(item.id)"
                        :disabled="approving"
                        class="btn-primary text-xs"
                      >
                        Setujui
                      </button>
                      <button
                        @click="rejectPengajuan(item.id)"
                        :disabled="approving"
                        class="btn-danger text-xs"
                      >
                        Tolak
                      </button>
                    </div>
                    <span v-else class="text-gray-400 text-xs">-</span>
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
