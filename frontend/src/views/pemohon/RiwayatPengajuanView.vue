<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'

const router = useRouter()
const pengajuanStore = usePengajuanStore()

const pengajuanList = ref([])
const loading = ref(false)
const deleting = ref(false)
const currentPage = ref(1)
const perPage = ref(10)
const total = ref(0)
const totalPages = ref(1)
const lastPage = ref(1)
const openMenuId = ref(null)
const menuPosition = ref({ top: 0, right: 0 })

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
      page: currentPage.value,
      per_page: perPage.value
    })

    if (response.data) {
      pengajuanList.value = response.data.data || response.data || []
      total.value = response.data.total || 0
      lastPage.value = response.data.last_page || 1
      totalPages.value = response.data.last_page || 1
    } else {
      pengajuanList.value = []
      total.value = 0
      lastPage.value = 1
      totalPages.value = 1
    }
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
    pengajuanList.value = []
    total.value = 0
    lastPage.value = 1
    totalPages.value = 1
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page
    loadPengajuan()
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
</script>

<template>
  <div class="flex min-h-screen bg-secondary-50">
    <AppSidebar />
    <div class="flex-1 flex flex-col">
      <AppHeader />
      <main class="flex-1 p-6 overflow-y-auto">
        <Breadcrumb />
        <div class="mb-6 animate-fade-in">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-secondary-800">Riwayat Pengajuan</h2>
              <p class="text-secondary-500 mt-1">Kelola dan pantau semua pengajuan izin belajar Anda</p>
            </div>
            <router-link to="/pengajuan/baru" class="btn btn-primary gap-2">
              <i class="ri-add-line"></i>
              <span>Buat Baru</span>
            </router-link>
          </div>
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
                                <router-link
                                  :to="`/pengajuan/${item.id}`"
                                  class="dropdown-item"
                                >
                                  <i class="ri-eye-line"></i>
                                  <span>Detail</span>
                                </router-link>

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
      </main>
    </div>
  </div>
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
</style>
