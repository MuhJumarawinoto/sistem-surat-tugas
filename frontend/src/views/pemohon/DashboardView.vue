<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePengajuanStore } from '@/stores/pengajuan'
import AppHeader from '@/components/layout/Header.vue'
import AppSidebar from '@/components/layout/Sidebar.vue'

const router = useRouter()
const authStore = useAuthStore()
const pengajuanStore = usePengajuanStore()

const stats = ref({
  draft: 0,
  pending: 0,
  disetujui: 0,
  ditolak: 0,
  selesai: 0,
})

const recentPengajuan = computed(() => pengajuanStore.pengajuanList.slice(0, 5))

onMounted(async () => {
  await loadStats()
})

watch(() => pengajuanStore.pengajuanList, (newList) => {
  updateStats(newList)
}, { deep: true })

function updateStats(pengajuan) {
  stats.value = {
    draft: pengajuan.filter((p) => p.status === 'draft').length,
    pending: pengajuan.filter((p) => p.status === 'pending_atasan' || p.status === 'pending_admin').length,
    disetujui: pengajuan.filter((p) => p.status === 'disetujui').length,
    ditolak: pengajuan.filter((p) => p.status === 'ditolak').length,
    selesai: pengajuan.filter((p) => p.status === 'selesai').length,
  }
}

async function loadStats() {
  try {
    const response = await pengajuanStore.fetchPengajuan({ per_page: 100 })
    updateStats(response.data || [])
  } catch (error) {
    console.error('Failed to load stats:', error)
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
        <!-- Page Header -->
        <div class="mb-6 animate-fade-in">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-secondary-800">Dashboard</h2>
              <p class="text-secondary-500 mt-1">Selamat datang, {{ authStore.user?.name }}</p>
            </div>
            <router-link
              v-if="authStore.isPemohon"
              to="/pengajuan/baru"
              class="btn btn-primary gap-2"
            >
              <i class="ri-add-line"></i>
              <span>Pengajuan Baru</span>
            </router-link>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
          <div class="card animate-slide-up" style="animation-delay: 0ms;">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-3xl font-bold text-secondary-800">{{ stats.draft }}</p>
                  <p class="text-sm text-secondary-500 mt-1">Draft</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-secondary-100 flex items-center justify-center">
                  <i class="ri-draft-line text-2xl text-secondary-500"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="card animate-slide-up" style="animation-delay: 50ms;">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-3xl font-bold text-warning">{{ stats.pending }}</p>
                  <p class="text-sm text-secondary-500 mt-1">Pending</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                  <i class="ri-time-line text-2xl text-warning"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="card animate-slide-up" style="animation-delay: 100ms;">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-3xl font-bold text-success">{{ stats.disetujui }}</p>
                  <p class="text-sm text-secondary-500 mt-1">Disetujui</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                  <i class="ri-check-line text-2xl text-success"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="card animate-slide-up" style="animation-delay: 150ms;">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-3xl font-bold text-danger">{{ stats.ditolak }}</p>
                  <p class="text-sm text-secondary-500 mt-1">Ditolak</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                  <i class="ri-close-line text-2xl text-danger"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="card animate-slide-up" style="animation-delay: 200ms;">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-3xl font-bold text-primary-600">{{ stats.selesai }}</p>
                  <p class="text-sm text-secondary-500 mt-1">Selesai</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center">
                  <i class="ri-checkbox-circle-line text-2xl text-primary-600"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Submissions Table -->
        <div class="card animate-slide-up" style="animation-delay: 250ms;">
          <div class="card-header">
            <div class="flex items-center justify-between">
              <h3 class="card-title flex items-center gap-2">
                <i class="ri-file-list-3-line text-primary-600"></i>
                Pengajuan Terbaru
              </h3>
              <router-link
                to="/pengajuan"
                class="btn btn-ghost btn-sm"
              >
                Lihat Semua
                <i class="ri-arrow-right-line"></i>
              </router-link>
            </div>
          </div>

          <div class="card-body p-0">
            <div v-if="recentPengajuan.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500 mb-4">Belum ada pengajuan</p>
              <router-link
                v-if="authStore.isPemohon"
                to="/pengajuan/baru"
                class="btn btn-primary"
              >
                <i class="ri-add-line"></i>
                Buat Pengajuan Baru
              </router-link>
            </div>

            <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nomor</th>
                    <th>Program Studi</th>
                    <th>Universitas</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in recentPengajuan" :key="item.id">
                    <td class="font-medium">{{ item.nomor_pengajuan || '-' }}</td>
                    <td>{{ item.nama_prodi }}</td>
                    <td>{{ item.nama_universitas || '-' }}</td>
                    <td class="text-secondary-500">
                      {{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                    </td>
                    <td>
                      <span :class="['badge', getStatusBadge(item.status), 'flex items-center gap-1 w-fit']">
                        <i :class="getStatusIcon(item.status)"></i>
                        {{ getStatusLabel(item.status) }}
                      </span>
                    </td>
                    <td>
                      <router-link
                        :to="`/pengajuan/${item.id}`"
                        class="btn btn-ghost btn-sm text-primary-600 hover:text-primary-700 hover:bg-primary-50"
                      >
                        <i class="ri-eye-line"></i>
                        Lihat
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-secondary-200 px-6 py-4">
        <div class="flex items-center justify-between text-sm text-secondary-500">
          <p>© {{ new Date().getFullYear() }} BKPSDM Kabupaten Sukabumi</p>
          <p class="flex items-center gap-1">
            <i class="ri-shield-check-line text-success"></i>
            Sistem Aman & Terpercaya
          </p>
        </div>
      </footer>
    </div>
  </div>
</template>
