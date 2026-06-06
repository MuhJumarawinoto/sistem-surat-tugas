<script setup>
import { ref, onMounted, computed } from 'vue'
import MainLayout from '@/components/layout/MainLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import api from '@/services/api'

// State
const loading = ref(false)
const suratList = ref([])
const showMenus = ref({})

// Stats
const stats = computed(() => ({
  total: suratList.value.length,
  signed: suratList.value.filter(s => s.status === 'signed' || s.status === 'completed').length
}))

// Load surat izin list
async function loadSuratList() {
  loading.value = true
  try {
    const response = await api.get('/admin/surat-izin')
    suratList.value = response.data.data
  } catch (error) {
    console.error('Failed to load surat list:', error)
  } finally {
    loading.value = false
  }
}

// Preview surat
function previewSurat(surat) {
  const token = localStorage.getItem('token')
  const url = `${import.meta.env.VITE_API_URL}/admin/surat-izin/${surat.id}/preview?token=${encodeURIComponent(token)}`
  window.open(url, '_blank')
}

// Download surat
async function downloadSurat(surat) {
  try {
    const token = localStorage.getItem('token')
    const url = `${import.meta.env.VITE_API_URL}/admin/surat-izin/${surat.id}/download?token=${encodeURIComponent(token)}`
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to download surat:', error)
  }
}

// Toggle dropdown menu
function toggleMenu(suratId) {
  showMenus.value[suratId] = !showMenus.value[suratId]
}

// Close all menus
function closeAllMenus() {
  showMenus.value = {}
}

// Format date
function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Get status badge
function getStatusBadge(status) {
  const badges = {
    signed: 'badge-success',
    completed: 'badge-primary'
  }
  return badges[status] || 'badge-secondary'
}

// Get status label
function getStatusLabel(status) {
  const labels = {
    signed: 'Signed',
    completed: 'Completed'
  }
  return labels[status] || status
}

onMounted(() => {
  loadSuratList()
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Surat Izin Belajar Mandiri"
      subtitle="Daftar surat izin belajar yang telah diterbitkan"
    />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Total Surat</p>
            <p class="text-2xl font-bold text-secondary-800">{{ stats.total }}</p>
          </div>
          <div class="badge badge-primary text-lg">Total</div>
        </div>
      </div>
      <div class="card card-body">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm">Sudah Ditandatangani</p>
            <p class="text-2xl font-bold text-secondary-800">{{ stats.signed }}</p>
          </div>
          <div class="badge badge-success text-lg">Signed</div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <LoadingSpinner v-if="loading" />

    <div v-else class="space-y-4" @click="closeAllMenus">
      <!-- Empty State -->
      <div v-if="suratList.length === 0" class="card card-body text-center py-12">
        <i class="ri-file-text-line text-4xl text-secondary-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-secondary-700 mb-2">Belum Ada Surat Izin</h3>
        <p class="text-secondary-500">Silakan buat Surat Izin Belajar melalui menu Tanda Tangan Surat.</p>
      </div>

      <!-- Surat List -->
      <div v-else class="space-y-4">
        <div
          v-for="surat in suratList"
          :key="surat.id"
          class="card card-body !overflow-visible"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="font-semibold text-secondary-800">{{ surat.pengajuan?.user?.name }}</h4>
                <span :class="['badge', getStatusBadge(surat.status)]">
                  {{ getStatusLabel(surat.status) }}
                </span>
              </div>
              <div class="text-sm text-secondary-600 mb-2">
                <span class="font-medium">Nomor:</span> {{ surat.nomor_surat }}
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary-600">
                <div><span class="font-medium">NIP:</span> {{ surat.pengajuan?.user?.nip }}</div>
                <div><span class="font-medium">Prodi:</span> {{ surat.pengajuan?.nama_prodi }}</div>
                <div><span class="font-medium">Jenjang:</span> {{ surat.pengajuan?.jenjang?.kode }}</div>
                <div><span class="font-medium">Dibuat:</span> {{ formatDate(surat.created_at) }}</div>
              </div>
            </div>

            <!-- Desktop: Inline buttons -->
            <div class="hidden sm:flex items-center gap-2">
              <button
                @click="previewSurat(surat)"
                class="btn btn-outline btn-sm"
              >
                <i class="ri-eye-line mr-1"></i>
                Preview
              </button>
              <button
                @click="downloadSurat(surat)"
                class="btn btn-secondary btn-sm"
              >
                <i class="ri-download-line mr-1"></i>
                Download
              </button>
            </div>

            <!-- Mobile: Dropdown menu -->
            <div class="sm:hidden relative">
              <button
                @click="toggleMenu(surat.id)"
                class="btn btn-ghost btn-icon"
              >
                <i class="ri-more-2-fill text-xl"></i>
              </button>

              <!-- Dropdown -->
              <Transition name="dropdown">
                <div
                  v-if="showMenus[surat.id]"
                  class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border z-50"
                  @click.stop
                >
                  <button
                    @click="previewSurat(surat)"
                    class="w-full px-4 py-3 text-left hover:bg-secondary-50 flex items-center gap-3 first:rounded-t-lg"
                  >
                    <i class="ri-eye-line text-secondary-500"></i>
                    <span>Preview Surat</span>
                  </button>
                  <button
                    @click="downloadSurat(surat)"
                    class="w-full px-4 py-3 text-left hover:bg-secondary-50 flex items-center gap-3 last:rounded-b-lg"
                  >
                    <i class="ri-download-line text-secondary-500"></i>
                    <span>Download PDF</span>
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
