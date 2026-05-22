<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import DocumentPreviewModal from '@/components/DocumentPreviewModal.vue'

const router = useRouter()
const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const allPengajuanList = ref([]) // Store all data for client-side filtering
const loading = ref(false)
const deleting = ref(false)
const currentPage = ref(1)
const perPage = ref(10)
const total = ref(0)
const totalPages = ref(1)
const lastPage = ref(1)
const openMenuId = ref(null)
const menuPosition = ref({ top: 0, right: 0 })
const searchQuery = ref('')

// Modal states
const showDetailModal = ref(false)
const selectedPengajuan = ref(null)
const loadingDetail = ref(false)

// Document preview states
const showDocumentModal = ref(false)
const selectedDocument = ref(null)
const documentPreviewUrl = ref('')
const openDocumentMenuId = ref(null)

function toggleDocumentMenu(id) {
  openDocumentMenuId.value = openDocumentMenuId.value === id ? null : id
}

// Backend URL for document URLs
const backendUrl = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace('/api', '')
  : 'http://localhost:8000'

const getDocumentUrl = (doc) => {
  if (!doc) return ''
  if (doc.file_url) {
    console.log('Document URL from file_url:', doc.file_url)
    return doc.file_url
  }
  if (doc.file_path) {
    const url = `${backendUrl}/storage/${doc.file_path}`
    console.log('Document URL constructed:', url)
    return url
  }
  return ''
}

const getDocumentIcon = (fileName) => {
  if (!fileName) return 'ri-file-line'
  const ext = fileName.toLowerCase().split('.').pop()
  const icons = {
    pdf: 'ri-file-pdf-line',
    doc: 'ri-file-word-line',
    docx: 'ri-file-word-line',
    xls: 'ri-file-excel-line',
    xlsx: 'ri-file-excel-line',
    jpg: 'ri-image-line',
    jpeg: 'ri-image-line',
    png: 'ri-image-line',
    gif: 'ri-image-line',
  }
  return icons[ext] || 'ri-file-text-line'
}

const isImageFile = (fileName) => {
  if (!fileName) return false
  const ext = fileName.toLowerCase().split('.').pop()
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)
}

const isPdfFile = (fileName) => {
  if (!fileName) return false
  const ext = fileName.toLowerCase().split('.').pop()
  return ext === 'pdf'
}

function openDocumentPreview(doc) {
  const url = getDocumentUrl(doc)
  if (!url) {
    alert('URL dokumen tidak tersedia')
    return
  }

  // Close menu
  openDocumentMenuId.value = null

  // Show all documents (image, pdf) in modal preview
  selectedDocument.value = doc
  documentPreviewUrl.value = url
  showDocumentModal.value = true
}

function closeDocumentModal() {
  showDocumentModal.value = false
  selectedDocument.value = null
  documentPreviewUrl.value = ''
}

function downloadDocument(doc) {
  const url = getDocumentUrl(doc)
  if (!url) {
    alert('URL dokumen tidak tersedia')
    return
  }

  // Close menu
  openDocumentMenuId.value = null

  const link = document.createElement('a')
  link.href = url
  link.download = doc.file_name || `dokumen-${doc.id}`
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function closeAllMenus() {
  openMenuId.value = null
}

function toggleMenu(id, event) {
  event.stopPropagation()

  if (openMenuId.value === id) {
    openMenuId.value = null
  } else {
    openMenuId.value = id
    const button = event.currentTarget
    const rect = button.getBoundingClientRect()
    menuPosition.value = {
      top: rect.bottom + window.scrollY + 4,
      right: window.innerWidth - rect.right
    }
  }
}

async function openDetailModal(id) {
  closeAllMenus()
  loadingDetail.value = true
  showDetailModal.value = true
  selectedPengajuan.value = null

  try {
    const response = await api.get(`/pengajuan/${id}`)
    selectedPengajuan.value = response.data
  } catch (error) {
    console.error('Failed to load detail:', error)
    alert('Gagal memuat detail pengajuan')
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedPengajuan.value = null
}

onMounted(() => {
  document.addEventListener('click', closeAllMenus)
  window.addEventListener('scroll', closeAllMenus, true)
  loadPengajuan()
})

onUnmounted(() => {
  document.removeEventListener('click', closeAllMenus)
  window.removeEventListener('scroll', closeAllMenus, true)
})

async function loadPengajuan() {
  loading.value = true
  try {
    const response = await pengajuanStore.fetchPengajuan({
      page: 1,
      per_page: 1000 // Load all data for client-side filtering
    })

    if (response.data) {
      allPengajuanList.value = response.data.data || response.data || []
      applyFilter()
    } else {
      allPengajuanList.value = []
      pengajuanList.value = []
      total.value = 0
      lastPage.value = 1
      totalPages.value = 1
    }
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
    allPengajuanList.value = []
    pengajuanList.value = []
    total.value = 0
    lastPage.value = 1
    totalPages.value = 1
  } finally {
    loading.value = false
  }
}

function applyFilter() {
  let filtered = allPengajuanList.value

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(item => {
      return (
        (item.nomor_pengajuan && item.nomor_pengajuan.toLowerCase().includes(query)) ||
        (item.jenjang?.nama && item.jenjang.nama.toLowerCase().includes(query)) ||
        (item.nama_prodi && item.nama_prodi.toLowerCase().includes(query)) ||
        (item.perguruan_tinggi && item.perguruan_tinggi.toLowerCase().includes(query)) ||
        (item.lokasi_pt && item.lokasi_pt.toLowerCase().includes(query)) ||
        (item.status && item.status.toLowerCase().includes(query))
      )
    })
  }

  total.value = filtered.length
  lastPage.value = Math.ceil(filtered.length / perPage.value) || 1
  totalPages.value = lastPage.value

  // Reset to page 1 if current page is beyond last page
  if (currentPage.value > lastPage.value) {
    currentPage.value = 1
  }

  // Apply pagination
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  pengajuanList.value = filtered.slice(start, end)
}

function handleSearch() {
  currentPage.value = 1
  applyFilter()
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page
    applyFilter()
  }
}

function nextPage() {
  if (currentPage.value < lastPage.value) {
    changePage(currentPage.value + 1)
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    changePage(currentPage.value - 1)
  }
}

const fromItem = computed(() => {
  return total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
})

const toItem = computed(() => {
  const end = currentPage.value * perPage.value
  return end > total.value ? total.value : end
})

const displayedPages = computed(() => {
  const pages = []
  const total = lastPage.value
  const current = currentPage.value
  const delta = 1

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > delta + 3) {
      pages.push('...')
    }
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    if (current < total - delta - 2) {
      pages.push('...')
    }
    pages.push(total)
  }
  return pages
})

function canEdit(status) {
  return status === 'draft' || status === 'ditolak'
}

function canDelete(status) {
  return status === 'draft'
}

async function deletePengajuan(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus pengajuan ini? Tindakan ini tidak dapat dibatalkan.')) {
    return
  }

  deleting.value = true
  try {
    await api.delete(`/pengajuan/${id}`)
    alert('Pengajuan berhasil dihapus')
    if (pengajuanList.value.length === 1 && currentPage.value > 1) {
      currentPage.value = 1
    }
    await loadPengajuan()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menghapus pengajuan')
  } finally {
    deleting.value = false
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

function getDocumentCount(pengajuan) {
  if (!pengajuan || !pengajuan.dokumen) return 0
  return pengajuan.dokumen.length
}

// Watch for search query changes
watch(searchQuery, () => {
  currentPage.value = 1
  applyFilter()
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <div class="mb-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">Riwayat Pengajuan</h2>
          <p class="text-secondary-500 mt-1">Kelola dan pantau semua pengajuan izin belajar Anda</p>
        </div>
        <router-link to="/pengajuan/baru" class="btn btn-primary gap-2 sm:w-auto w-full justify-center">
          <i class="ri-add-line"></i>
          <span>Buat Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Search Box -->
    <div class="mb-4 animate-fade-in">
      <div class="relative">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari berdasarkan nomor, jenjang, prodi, universitas, lokasi, atau status..."
          class="w-full pl-10 pr-4 py-2.5 border border-secondary-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
        <button
          v-if="searchQuery"
          @click="searchQuery = ''"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
        >
          <i class="ri-close-line"></i>
        </button>
      </div>
      <p v-if="searchQuery" class="text-sm text-secondary-500 mt-2">
        Menampilkan {{ total }} hasil pencarian untuk "{{ searchQuery }}"
      </p>
    </div>

    <div class="card animate-slide-up">
      <div class="card-body">
            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat data..." />
            </div>

            <div v-else-if="pengajuanList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500 mb-4">Belum ada pengajuan</p>
              <router-link to="/pengajuan/baru" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Buat Pengajuan Baru
              </router-link>
            </div>

            <div v-else>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Nomor</th>
                      <th>Jenjang</th>
                      <th>Prodi</th>
                      <th>Universitas</th>
                      <th>Tanggal</th>
                      <th>Status</th>
                      <th class="text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in pengajuanList" :key="item.id">
                      <td class="font-medium">{{ item.nomor_pengajuan || '-' }}</td>
                      <td>{{ item.jenjang?.nama }}</td>
                      <td>{{ item.nama_prodi }}</td>
                      <td>{{ item.perguruan_tinggi }}</td>
                      <td class="text-secondary-500">
                        {{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                      </td>
                      <td>
                        <span :class="['badge', 'flex items-center gap-1 w-fit', getStatusBadge(item.status)]">
                          <i :class="getStatusIcon(item.status)"></i>
                          {{ getStatusLabel(item.status) }}
                        </span>
                      </td>
                      <td class="text-right">
                        <div class="relative inline-block">
                          <button
                            @click="toggleMenu(item.id, $event)"
                            class="btn btn-ghost btn-icon"
                          >
                            <i class="ri-more-2-fill text-lg"></i>
                          </button>

                          <Teleport to="body">
                            <Transition name="dropdown">
                              <div
                                v-if="openMenuId === item.id"
                                class="dropdown-menu"
                                :style="{ top: `${menuPosition.top}px`, right: `${menuPosition.right}px` }"
                                @click.stop
                              >
                                <button
                                  @click="openDetailModal(item.id)"
                                  class="dropdown-item"
                                >
                                  <i class="ri-eye-line"></i>
                                  <span>Lihat Detail</span>
                                </button>

                                <router-link
                                  v-if="canEdit(item.status)"
                                  :to="`/pengajuan/${item.id}/edit`"
                                  class="dropdown-item"
                                >
                                  <i class="ri-edit-line"></i>
                                  <span>Edit</span>
                                </router-link>

                                <div v-if="canDelete(item.status)" class="border-t border-secondary-100 my-1"></div>

                                <button
                                  v-if="canDelete(item.status)"
                                  @click="deletePengajuan(item.id)"
                                  :disabled="deleting"
                                  class="dropdown-item text-danger hover:bg-red-50"
                                >
                                  <i class="ri-delete-bin-line"></i>
                                  <span>Hapus</span>
                                </button>
                              </div>
                            </Transition>
                          </Teleport>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div v-if="totalPages > 1" class="flex items-center justify-between mt-4 pt-4 border-t border-secondary-200">
                <div class="text-sm text-secondary-500">
                  Menampilkan {{ fromItem }} - {{ toItem }} dari {{ total }} pengajuan
                </div>

                <div class="flex items-center gap-1">
                  <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="btn btn-ghost btn-sm"
                  >
                    <i class="ri-arrow-left-s-line"></i>
                  </button>

                  <template v-for="page in displayedPages" :key="page">
                    <span v-if="page === '...'" class="px-2 text-secondary-400">...</span>
                    <button
                      v-else
                      @click="changePage(page)"
                      :class="[
                        'btn btn-sm',
                        currentPage === page ? 'btn-primary' : 'btn-ghost'
                      ]"
                    >
                      {{ page }}
                    </button>
                  </template>

                  <button
                    @click="nextPage"
                    :disabled="currentPage === lastPage"
                    class="btn btn-ghost btn-sm"
                  >
                    <i class="ri-arrow-right-s-line"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

    <!-- Detail Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDetailModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          @click.self="closeDetailModal"
        >
          <div
            v-if="selectedPengajuan || loadingDetail"
            class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden animate-slide-up flex flex-col"
          >
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-secondary-200">
              <div>
                <h3 class="text-lg font-semibold text-secondary-800">Detail Pengajuan</h3>
                <p class="text-sm text-secondary-500">{{ selectedPengajuan?.nomor_pengajuan || '-' }}</p>
              </div>
              <button
                @click="closeDetailModal"
                class="btn btn-ghost btn-icon"
              >
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1">
              <LoadingSpinner v-if="loadingDetail" size="md" text="Memuat detail..." />

              <div v-else-if="selectedPengajuan" class="space-y-6">
                <!-- Status -->
                <div class="flex items-center justify-between p-4 rounded-lg bg-secondary-50">
                  <div>
                    <p class="text-sm text-secondary-500">Status</p>
                    <p class="text-lg font-semibold text-secondary-800 mt-1">{{ getStatusLabel(selectedPengajuan.status) }}</p>
                  </div>
                  <span :class="['badge badge-lg', getStatusBadge(selectedPengajuan.status)]">
                    <i :class="getStatusIcon(selectedPengajuan.status)"></i>
                  </span>
                </div>

                <!-- Info Pendidikan -->
                <div>
                  <h4 class="text-sm font-semibold text-secondary-600 uppercase mb-3">Informasi Pendidikan</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Jenjang</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.jenjang?.nama || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Program Studi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.nama_prodi || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Perguruan Tinggi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.perguruan_tinggi || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Lokasi</p>
                      <p class="font-medium text-secondary-800 mt-1">{{ selectedPengajuan.lokasi_pt || '-' }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Rencana Mulai</p>
                      <p class="font-medium text-secondary-800 mt-1">
                        {{ selectedPengajuan.rencana_mulai ? new Date(selectedPengajuan.rencana_mulai).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
                      </p>
                    </div>
                    <div class="p-3 rounded-lg bg-secondary-50">
                      <p class="text-xs text-secondary-500">Rencana Selesai</p>
                      <p class="font-medium text-secondary-800 mt-1">
                        {{ selectedPengajuan.rencana_selesai ? new Date(selectedPengajuan.rencana_selesai).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Dokumen -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-secondary-600 uppercase">Dokumen</h4>
                    <span class="text-sm text-secondary-500">{{ getDocumentCount(selectedPengajuan) }} / 9 dokumen</span>
                  </div>
                  <div v-if="selectedPengajuan.dokumen && selectedPengajuan.dokumen.length > 0" class="grid grid-cols-1 gap-2">
                    <div
                      v-for="doc in selectedPengajuan.dokumen"
                      :key="doc.id"
                      class="flex items-center gap-2 p-3 rounded-lg bg-secondary-50 hover:bg-secondary-100 transition-colors"
                    >
                      <i :class="[getDocumentIcon(doc.file_name || doc.jenis_dokumen), 'text-xl text-secondary-400 flex-shrink-0']"></i>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-secondary-800 truncate text-sm">{{ doc.file_name || doc.jenis_dokumen }}</p>
                        <p v-if="doc.status_verifikasi" :class="[
                          'text-xs',
                          doc.status_verifikasi === 'lengkap' ? 'text-green-600' : 'text-orange-600'
                        ]">
                          {{ doc.status_verifikasi === 'lengkap' ? '✓ Dokumen Lengkap' : '⚠ Perlu Verifikasi' }}
                        </p>
                      </div>
                      <!-- Desktop: Show both buttons -->
                      <div class="hidden sm:flex items-center gap-1 flex-shrink-0">
                        <button
                          @click="openDocumentPreview(doc)"
                          class="btn btn-ghost btn-sm btn-icon"
                          title="Lihat Dokumen"
                        >
                          <i class="ri-eye-line text-lg"></i>
                        </button>
                        <button
                          @click="downloadDocument(doc)"
                          class="btn btn-ghost btn-sm btn-icon"
                          title="Download Dokumen"
                        >
                          <i class="ri-download-line text-lg"></i>
                        </button>
                      </div>
                      <!-- Mobile: Show dropdown menu -->
                      <div class="sm:hidden relative flex-shrink-0">
                        <button
                          @click="toggleDocumentMenu(doc.id)"
                          class="btn btn-ghost btn-sm btn-icon"
                        >
                          <i class="ri-more-2-fill text-lg"></i>
                        </button>
                        <div
                          v-if="openDocumentMenuId === doc.id"
                          class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-secondary-200 py-1 z-10 min-w-[120px]"
                          @click.stop
                        >
                          <button
                            @click="openDocumentPreview(doc)"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                          >
                            <i class="ri-eye-line"></i>
                            Lihat
                          </button>
                          <button
                            @click="downloadDocument(doc)"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-secondary-50 flex items-center gap-2"
                          >
                            <i class="ri-download-line"></i>
                            Download
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-4 text-secondary-400 text-sm">
                    <i class="ri-folder-open-line text-2xl mb-2 block"></i>
                    Belum ada dokumen yang diupload
                  </div>
                </div>

                <!-- Catatan Tolak (jika ada) -->
                <div v-if="selectedPengajuan.catatan_tolak" class="p-4 rounded-lg bg-red-50 border border-red-200">
                  <h4 class="text-sm font-semibold text-red-600 mb-2">Catatan Penolakan</h4>
                  <p class="text-sm text-red-700">{{ selectedPengajuan.catatan_tolak }}</p>
                </div>

                <!-- Timestamps -->
                <div class="text-xs text-secondary-400 space-y-1">
                  <p>Dibuat: {{ new Date(selectedPengajuan.created_at).toLocaleString('id-ID') }}</p>
                  <p v-if="selectedPengajuan.tanggal_submit_atasan">Submit Atasan: {{ new Date(selectedPengajuan.tanggal_submit_atasan).toLocaleString('id-ID') }}</p>
                  <p v-if="selectedPengajuan.tanggal_approve_atasan">Approve Atasan: {{ new Date(selectedPengajuan.tanggal_approve_atasan).toLocaleString('id-ID') }}</p>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div v-if="!loadingDetail && selectedPengajuan" class="flex items-center justify-end gap-2 p-6 border-t border-secondary-200">
              <button
                @click="closeDetailModal"
                class="btn btn-ghost"
              >
                Tutup
              </button>
              <router-link
                v-if="canEdit(selectedPengajuan.status)"
                :to="`/pengajuan/${selectedPengajuan.id}/edit`"
                @click="closeDetailModal"
                class="btn btn-primary gap-2"
              >
                <i class="ri-edit-line"></i>
                <span>Edit Pengajuan</span>
              </router-link>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Document Preview Modal -->
    <DocumentPreviewModal
      :show="showDocumentModal"
      :src="documentPreviewUrl"
      :alt="selectedDocument?.file_name || selectedDocument?.jenis_dokumen || 'Dokumen'"
      :file-type="selectedDocument?.file_name ? (isImageFile(selectedDocument.file_name) ? 'image' : isPdfFile(selectedDocument.file_name) ? 'pdf' : '') : ''"
      @close="closeDocumentModal"
    />
  </MainLayout>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .animate-slide-up,
.modal-leave-active .animate-slide-up {
  transition: transform 0.3s ease;
}

.modal-enter-from .animate-slide-up,
.modal-leave-to .animate-slide-up {
  transform: translateY(20px);
}
</style>
