<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'

const pengajuanStore = usePengajuanStore()
const authStore = useAuthStore()

const pengajuanList = ref([])
const loading = ref(false)
const filterStatus = ref('pending_atasan')
const approving = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)

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

function getStatusBadge(status) {
  const badges = {
    draft: 'badge-default',
    pending_atasan: 'badge-warning',
    pending_admin: 'badge-info',
    disetujui: 'badge-success',
    ditolak: 'badge-danger',
    selesai: 'badge-purple',
  }
  return badges[status] || 'badge-default'
}

function getStatusIcon(status) {
  const icons = {
    draft: 'ri-draft-line',
    pending_atasan: 'ri-time-line',
    pending_admin: 'ri-time-line',
    disetujui: 'ri-check-line',
    ditolak: 'ri-close-line',
    selesai: 'ri-checkbox-circle-line',
  }
  return icons[status] || 'ri-file-line'
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

function openSendMessageModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}
</script>

<template>
  <MainLayout>
    <div class="mb-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">Persetujuan Pengajuan</h2>
          <p class="text-secondary-500 mt-1">Unit Kerja: {{ authStore.user?.unit_kerja || '-' }}</p>
        </div>
        <select v-model="filterStatus" @change="loadPengajuan" class="select-field w-48">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
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
              <p class="text-secondary-500 mb-2">Tidak ada pengajuan</p>
              <p class="text-sm text-secondary-400">Pastikan pemohon sudah submit pengajuan dan berada di unit kerja yang sama</p>
            </div>

            <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nomor</th>
                    <th>Pemohon</th>
                    <th>Prodi</th>
                    <th>Universitas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in pengajuanList" :key="item.id">
                    <td class="font-medium">{{ item.nomor_pengajuan }}</td>
                    <td>
                      <p class="font-medium text-secondary-800">{{ item.user?.name }}</p>
                      <p class="text-sm text-secondary-500">NIP: {{ item.user?.nip }}</p>
                    </td>
                    <td>{{ item.nama_prodi }}</td>
                    <td>{{ item.perguruan_tinggi }}</td>
                    <td>
                      <span :class="['badge', getStatusBadge(item.status), 'flex items-center gap-1 w-fit']">
                        <i :class="getStatusIcon(item.status)"></i>
                        {{ getStatusLabel(item.status) }}
                      </span>
                    </td>
                    <td>
                      <div class="flex items-center gap-1">
                        <button
                          @click="openSendMessageModal(item)"
                          class="btn btn-ghost btn-sm"
                          title="Kirim Pesan"
                        >
                          <i class="ri-message-3-line"></i>
                        </button>
                        <template v-if="item.status === 'pending_atasan'">
                          <button
                            @click="approvePengajuan(item.id)"
                            :disabled="approving"
                            class="btn btn-primary btn-sm"
                          >
                            <LoadingSpinner v-if="approving" size="sm" color="white" />
                            <span v-else class="flex items-center gap-1">
                              <i class="ri-check-line"></i>
                              Setujui
                            </span>
                          </button>
                          <button
                            @click="rejectPengajuan(item.id)"
                            :disabled="approving"
                            class="btn btn-danger btn-sm"
                          >
                            <LoadingSpinner v-if="approving" size="sm" color="white" />
                            <span v-else class="flex items-center gap-1">
                              <i class="ri-close-line"></i>
                              Tolak
                            </span>
                          </button>
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
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
