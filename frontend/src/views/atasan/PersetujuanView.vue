<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const pengajuanStore = usePengajuanStore()
const authStore = useAuthStore()

const pengajuanList = ref([])
const loading = ref(false)
const filterStatus = ref('pending_atasan')
const approving = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)
const showMilestoneModal = ref(false)
const selectedPengajuanId = ref(null)

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

function openMilestoneModal(pengajuanId) {
  selectedPengajuanId.value = pengajuanId
  showMilestoneModal.value = true
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

            <div v-else class="space-y-4">
              <!-- Pengajuan Cards with Inline Milestone -->
              <div
                v-for="item in pengajuanList"
                :key="item.id"
                class="card border-l-4"
                :class="{
                  'border-l-warning': item.status === 'pending_atasan',
                  'border-l-success': item.status === 'disetujui',
                  'border-l-danger': item.status === 'ditolak',
                  'border-l-info': item.status === 'pending_admin'
                }"
              >
                <div class="card-body">
                  <!-- Card Header -->
                  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-secondary-100">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <p class="text-base font-semibold text-secondary-800">{{ item.nomor_pengajuan }}</p>
                        <span :class="['badge', getStatusBadge(item.status), 'flex items-center gap-1']">
                          <i :class="getStatusIcon(item.status)"></i>
                          {{ getStatusLabel(item.status) }}
                        </span>
                      </div>
                      <p class="text-sm text-secondary-800 font-medium">{{ item.user?.name }}</p>
                      <p class="text-xs text-secondary-500">NIP: {{ item.user?.nip }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <button
                        @click="openSendMessageModal(item)"
                        class="btn btn-ghost btn-sm"
                        title="Kirim Pesan"
                      >
                        <i class="ri-message-3-line text-lg"></i>
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
                  </div>

                  <!-- Education Info -->
                  <div class="mt-3 p-3 bg-secondary-50 rounded-lg">
                    <div class="flex items-center gap-4 text-sm">
                      <span class="flex items-center gap-1 text-secondary-700">
                        <i class="ri-graduation-cap-line"></i>
                        {{ item.nama_prodi }}
                      </span>
                      <span class="text-secondary-400">•</span>
                      <span class="flex items-center gap-1 text-secondary-700">
                        <i class="ri-building-line"></i>
                        {{ item.perguruan_tinggi }}
                      </span>
                      <span class="text-secondary-400">•</span>
                      <span class="flex items-center gap-1 text-secondary-700">
                        <i class="ri-calendar-line"></i>
                        {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
                      </span>
                    </div>
                  </div>

                  <!-- Inline Milestone -->
                  <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-secondary-700 flex items-center gap-1">
                        <i class="ri-route-line text-primary-600"></i>
                        Progress Pengajuan
                      </span>
                      <button
                        @click="openMilestoneModal(item.id)"
                        class="text-xs text-primary-600 hover:text-primary-700 flex items-center gap-1"
                      >
                        Lihat Detail
                        <i class="ri-arrow-right-s-line"></i>
                      </button>
                    </div>
                    <div class="bg-secondary-50 rounded-lg p-3">
                      <PengajuanMilestone :pengajuan-id="item.id" />
                    </div>
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

      <!-- Milestone Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="showMilestoneModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="showMilestoneModal = false"
          >
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
              <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">Progress Pengajuan</h3>
                <button @click="showMilestoneModal = false" class="btn btn-ghost btn-icon">
                  <i class="ri-close-line text-xl"></i>
                </button>
              </div>
              <div class="p-4 max-h-[70vh] overflow-y-auto">
                <PengajuanMilestone :pengajuan-id="selectedPengajuanId" />
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
  </MainLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
