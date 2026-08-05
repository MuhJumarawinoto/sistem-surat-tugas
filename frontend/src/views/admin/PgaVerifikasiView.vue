<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usePgaStore } from '@/stores/pga'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import MainLayout from '@/components/layout/MainLayout.vue'
import { useToastStore } from '@/stores/toast'

const authStore = useAuthStore()
const pgaStore = usePgaStore()
const toast = useToastStore()

const pgaList = ref([])
const loading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref('approved_admin')
const showRejectModal = ref(false)
const selectedPga = ref(null)
const rejectReason = ref('')
const processing = ref(false)

const stats = computed(() => ({
  total: pgaList.value.length,
  pending: pgaList.value.filter(p => p.status === 'approved_admin').length,
  selesai: pgaList.value.filter(p => p.status === 'selesai').length,
  ditolak: pgaList.value.filter(p => p.status === 'ditolak').length,
}))

const filteredPgaList = computed(() => {
  let filtered = pgaList.value

  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter(p => p.status === selectedStatus.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p =>
      p.nomor_pengajuan?.toLowerCase().includes(query) ||
      p.nama_prodi?.toLowerCase().includes(query) ||
      p.perguruan_tinggi?.toLowerCase().includes(query) ||
      p.user?.name?.toLowerCase().includes(query)
    )
  }

  return filtered
})

const statusLabels = {
  draft: 'Draft',
  approved_admin: 'Menunggu Persetujuan',
  selesai: 'Selesai',
  ditolak: 'Ditolak',
}

const statusBadgeClasses = {
  draft: 'badge-secondary',
  approved_admin: 'badge-warning',
  selesai: 'badge-success',
  ditolak: 'badge-danger',
}

function getStatusLabel(status) {
  return statusLabels[status] || status
}

function getStatusBadgeClass(status) {
  return statusBadgeClasses[status] || 'badge-secondary'
}

async function loadPga() {
  loading.value = true
  try {
    const response = await pgaStore.fetchPga({ status: 'approved_admin' })
    pgaList.value = response.data || []
  } catch (error) {
    toast.error('Gagal memuat data PGA')
  } finally {
    loading.value = false
  }
}

function openRejectModal(pga) {
  selectedPga.value = pga
  showRejectModal.value = true
  rejectReason.value = ''
}

function closeRejectModal() {
  showRejectModal.value = false
  selectedPga.value = null
  rejectReason.value = ''
}

async function handleReject() {
  if (!rejectReason.value.trim()) {
    toast.error('Silakan isi alasan penolakan')
    return
  }

  processing.value = true
  try {
    await pgaStore.rejectPga(selectedPga.value.id, rejectReason.value)
    toast.success('Pengajuan berhasil ditolak')
    closeRejectModal()
    await loadPga()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menolak pengajuan')
  } finally {
    processing.value = false
  }
}

async function handleApprove(pga) {
  if (!confirm('Setujui pengajuan PGA ini?')) return

  processing.value = true
  try {
    await pgaStore.approvePga(pga.id)
    toast.success('Pengajuan berhasil disetujui')
    await loadPga()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyetujui pengajuan')
  } finally {
    processing.value = false
  }
}

function getDocumentUrl(pga, type) {
  const fileMap = {
    ijazah: pga.ijazah_file,
    transkrip: pga.transkrip_file,
    sk_kum: pga.sk_kum_file,
  }

  const filePath = fileMap[type]
  if (!filePath) return null

  return `${import.meta.env.VITE_API_URL?.replace('/api', '') || ''}/storage/${filePath}`
}

onMounted(() => {
  loadPga()
})
</script>

<template>
  <MainLayout>
    <main class="bg-secondary-50 min-h-screen">
      <PageHeader
        title="Verifikasi PGA"
        subtitle="Verifikasi pengajuan pencantuman gelar akademik"
      />

      <div class="p-6">
        <!-- Stats Row -->
        <div class="flex flex-wrap gap-3 mb-6">
          <div class="card flex-1 min-w-[140px]">
            <div class="card-body p-3">
              <div class="text-secondary-500 text-xs">Total</div>
              <div class="text-xl font-bold text-secondary-800">{{ stats.total }}</div>
            </div>
          </div>
          <div class="card flex-1 min-w-[140px]">
            <div class="card-body p-3">
              <div class="text-secondary-500 text-xs">Menunggu</div>
              <div class="text-xl font-bold text-warning">{{ stats.pending }}</div>
            </div>
          </div>
          <div class="card flex-1 min-w-[140px]">
            <div class="card-body p-3">
              <div class="text-secondary-500 text-xs">Selesai</div>
              <div class="text-xl font-bold text-success">{{ stats.selesai }}</div>
            </div>
          </div>
          <div class="card flex-1 min-w-[140px]">
            <div class="card-body p-3">
              <div class="text-secondary-500 text-xs">Ditolak</div>
              <div class="text-xl font-bold text-danger">{{ stats.ditolak }}</div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
          <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
              <div class="flex-1">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Cari nomor pengajuan, prodi, universitas, atau nama pegawai..."
                  class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:outline-none focus:border-primary-500"
                />
              </div>
              <div class="w-full md:w-48">
                <select
                  v-model="selectedStatus"
                  class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:outline-none focus:border-primary-500"
                >
                  <option value="approved_admin">Menunggu Persetujuan</option>
                  <option value="selesai">Selesai</option>
                  <option value="ditolak">Ditolak</option>
                  <option value="all">Semua</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- PGA List -->
        <LoadingSpinner v-if="loading" size="lg" text="Memuat data..." />

        <div v-else-if="filteredPgaList.length === 0" class="card">
          <div class="card-body text-center py-12">
            <i class="ri-file-list-3-line text-6xl text-secondary-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-secondary-800 mb-2">Tidak ada pengajuan</h3>
            <p class="text-secondary-500">Belum ada pengajuan PGA yang sesuai dengan filter</p>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
          <div
            v-for="pga in filteredPgaList"
            :key="pga.id"
            class="card hover:shadow-lg transition-shadow"
          >
            <div class="card-body">
              <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <!-- Left: Pengajuan Info -->
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="font-semibold text-secondary-800">{{ pga.nomor_pengajuan }}</h3>
                    <span :class="['badge', getStatusBadgeClass(pga.status)]">
                      {{ getStatusLabel(pga.status) }}
                    </span>
                  </div>
                  <div class="space-y-1 text-sm">
                    <p class="text-secondary-600">
                      <i class="ri-user-line mr-1"></i>
                      {{ pga.user?.name }} - {{ pga.user?.nip }}
                    </p>
                    <p class="text-secondary-600">
                      <i class="ri-graduation-cap-line mr-1"></i>
                      {{ pga.nama_prodi }} - {{ pga.jenjang_pendidikan?.nama }}
                    </p>
                    <p class="text-secondary-600">
                      <i class="ri-building-line mr-1"></i>
                      {{ pga.perguruan_tinggi }}
                    </p>
                    <p class="text-secondary-500">
                      <i class="ri-calendar-line mr-1"></i>
                      Tahun Lulus: {{ pga.tahun_lulus }}
                    </p>
                    <p v-if="pga.catatan_tolak" class="text-danger text-xs mt-2">
                      <i class="ri-error-warning-line mr-1"></i>
                      {{ pga.catatan_tolak }}
                    </p>
                  </div>
                </div>

                <!-- Right: Document Links & Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                  <!-- Document Links -->
                  <div class="flex flex-col gap-2">
                    <a
                      v-if="pga.ijazah_file"
                      :href="getDocumentUrl(pga, 'ijazah')"
                      target="_blank"
                      class="text-sm text-primary hover:underline flex items-center gap-1"
                    >
                      <i class="ri-file-text-line"></i> Ijazah
                    </a>
                    <a
                      v-if="pga.transkrip_file"
                      :href="getDocumentUrl(pga, 'transkrip')"
                      target="_blank"
                      class="text-sm text-primary hover:underline flex items-center gap-1"
                    >
                      <i class="ri-file-text-line"></i> Transkrip
                    </a>
                    <a
                      v-if="pga.sk_kum_file"
                      :href="getDocumentUrl(pga, 'sk_kum')"
                      target="_blank"
                      class="text-sm text-primary hover:underline flex items-center gap-1"
                    >
                      <i class="ri-file-text-line"></i> SK Kum
                    </a>
                  </div>

                  <!-- Action Buttons -->
                  <div v-if="pga.status === 'approved_admin'" class="flex gap-2">
                    <button
                      @click="handleApprove(pga)"
                      class="btn btn-success btn-sm"
                      :disabled="processing"
                    >
                      <i class="ri-check-line"></i>
                      <span class="hidden sm:inline ml-1">Setuju</span>
                    </button>
                    <button
                      @click="openRejectModal(pga)"
                      class="btn btn-danger btn-sm"
                      :disabled="processing"
                    >
                      <i class="ri-close-line"></i>
                      <span class="hidden sm:inline ml-1">Tolak</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Reject Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showRejectModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeRejectModal"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6 border-b">
              <h3 class="text-lg font-semibold">Tolak Pengajuan PGA</h3>
            </div>
            <div class="p-6">
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                Alasan Penolakan <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="rejectReason"
                rows="4"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:outline-none focus:border-primary-500"
                placeholder="Jelaskan alasan penolakan..."
              ></textarea>
            </div>
            <div class="p-6 border-t flex justify-end gap-2">
              <button
                @click="closeRejectModal"
                class="btn btn-ghost"
                :disabled="processing"
              >
                Batal
              </button>
              <button
                @click="handleReject"
                class="btn btn-danger"
                :disabled="processing"
              >
                <LoadingSpinner v-if="processing" size="sm" />
                <span v-else>Tolak Pengajuan</span>
              </button>
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
