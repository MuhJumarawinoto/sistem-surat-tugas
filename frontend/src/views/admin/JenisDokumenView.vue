<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingItem = ref(null)
const formData = ref({
  kode: '',
  nama: '',
  deskripsi: '',
  is_wajib: true,
  urutan: 0,
  is_active: true
})
const formErrors = ref({})

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    const response = await api.get('/admin/jenis-dokumen')
    list.value = response.data
  } catch (error) {
    console.error('Failed to load data:', error)
    toast.error('Gagal memuat data')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingItem.value = null
  formData.value = {
    kode: '',
    nama: '',
    deskripsi: '',
    is_wajib: true,
    urutan: list.value.length + 1,
    is_active: true
  }
  formErrors.value = {}
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  formData.value = {
    kode: item.kode,
    nama: item.nama,
    deskripsi: item.deskripsi || '',
    is_wajib: item.is_wajib,
    urutan: item.urutan,
    is_active: item.is_active
  }
  formErrors.value = {}
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingItem.value = null
  formData.value = {
    kode: '',
    nama: '',
    deskripsi: '',
    is_wajib: true,
    urutan: 0,
    is_active: true
  }
  formErrors.value = {}
}

async function handleSubmit() {
  try {
    const payload = {
      ...formData.value,
      urutan: parseInt(formData.value.urutan)
    }

    if (editingItem.value) {
      await api.put(`/admin/jenis-dokumen/${editingItem.value.id}`, payload)
      toast.success('Jenis dokumen berhasil diperbarui')
    } else {
      await api.post('/admin/jenis-dokumen', payload)
      toast.success('Jenis dokumen berhasil ditambahkan')
    }

    closeModal()
    await loadData()
  } catch (error) {
    if (error.response?.status === 422) {
      formErrors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Terjadi kesalahan')
    }
  }
}

async function handleDelete(item) {
  if (!confirm(`Hapus jenis dokumen "${item.nama}"?`)) return

  try {
    await api.delete(`/admin/jenis-dokumen/${item.id}`)
    toast.success('Jenis dokumen berhasil dihapus')
    await loadData()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus')
  }
}

function getActiveClass(isActive) {
  return isActive ? 'badge-success' : 'badge-secondary'
}

function getRequiredClass(isRequired) {
  return isRequired ? 'badge-danger' : 'badge-warning'
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Manajemen Jenis Dokumen"
      subtitle="Atur jenis dokumen yang diperlukan untuk pengajuan izin belajar"
      :actions="[
        {
          label: 'Tambah Jenis Dokumen',
          icon: 'ri-add-line',
          onClick: openCreateModal,
          variant: 'btn-primary'
        }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <LoadingSpinner size="lg" text="Memuat data..." />
    </div>

    <!-- List -->
    <div v-else class="card animate-slide-up">
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-secondary-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-secondary-700">Urutan</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-secondary-700">Kode</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-secondary-700">Nama Dokumen</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-secondary-700">Deskripsi</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-secondary-700">Wajib</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-secondary-700">Status</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-secondary-700">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in list"
                :key="item.id"
                class="border-b border-secondary-100 hover:bg-secondary-50 transition-colors"
              >
                <td class="py-3 px-4 text-sm">{{ item.urutan }}</td>
                <td class="py-3 px-4">
                  <span class="badge badge-xs badge-primary font-mono">{{ item.kode }}</span>
                </td>
                <td class="py-3 px-4 text-sm font-medium text-secondary-800">{{ item.nama }}</td>
                <td class="py-3 px-4 text-sm text-secondary-600 max-w-xs truncate">{{ item.deskripsi || '-' }}</td>
                <td class="py-3 px-4 text-center">
                  <span class="badge badge-xs" :class="getRequiredClass(item.is_wajib)">
                    {{ item.is_wajib ? 'Wajib' : 'Opsional' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="badge badge-xs" :class="getActiveClass(item.is_active)">
                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="openEditModal(item)"
                      class="text-blue-600 hover:text-blue-800"
                      title="Edit"
                    >
                      <i class="ri-edit-line text-lg"></i>
                    </button>
                    <button
                      @click="handleDelete(item)"
                      class="text-red-600 hover:text-red-800"
                      title="Hapus"
                    >
                      <i class="ri-delete-bin-line text-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="list.length === 0" class="text-center py-12 text-secondary-500">
          <i class="ri-inbox-line text-4xl"></i>
          <p class="mt-2">Belum ada jenis dokumen</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <!-- Note: Modal hanya bisa ditutup dengan tombol X atau Batal/Simpan (tidak close saat klik backdrop) -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <!-- Backdrop - klik tidak menutup modal -->
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click.stop></div>
          <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden animate-slide-up">
            <!-- Header -->
            <div class="p-6 border-b border-secondary-100 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-secondary-800">
                {{ editingItem ? 'Edit Jenis Dokumen' : 'Tambah Jenis Dokumen' }}
              </h3>
              <!-- Tombol X untuk menutup modal -->
              <button
                @click="closeModal"
                class="p-2 rounded-full hover:bg-red-100 hover:text-red-600 transition-colors"
                title="Tutup"
              >
                <i class="ri-close-line text-2xl"></i>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
              <!-- Kode -->
              <div>
                <label class="input-label">Kode</label>
                <input
                  v-model="formData.kode"
                  type="text"
                  class="input-field"
                  placeholder="Contoh: sk_pangkat"
                  :class="{ 'border-red-300': formErrors.kode }"
                />
                <p v-if="formErrors.kode" class="text-xs text-red-600 mt-1">{{ formErrors.kode[0] }}</p>
                <p class="text-xs text-secondary-500 mt-1">Kode unik untuk identifikasi dokumen (gunakan underscore, bukan spasi)</p>
              </div>

              <!-- Nama -->
              <div>
                <label class="input-label">Nama Dokumen</label>
                <input
                  v-model="formData.nama"
                  type="text"
                  class="input-field"
                  placeholder="Contoh: SK Pangkat Terakhir"
                  :class="{ 'border-red-300': formErrors.nama }"
                />
                <p v-if="formErrors.nama" class="text-xs text-red-600 mt-1">{{ formErrors.nama[0] }}</p>
              </div>

              <!-- Deskripsi -->
              <div>
                <label class="input-label">Deskripsi</label>
                <textarea
                  v-model="formData.deskripsi"
                  class="input-field"
                  rows="2"
                  placeholder="Keterangan singkat tentang dokumen..."
                ></textarea>
              </div>

              <!-- Urutan & Wajib -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="input-label">Urutan</label>
                  <input
                    v-model.number="formData.urutan"
                    type="number"
                    class="input-field"
                    min="1"
                  />
                </div>
                <div>
                  <label class="input-label">Status</label>
                  <select v-model="formData.is_wajib" class="select-field">
                    <option :value="true">Wajib</option>
                    <option :value="false">Opsional</option>
                  </select>
                </div>
              </div>

              <!-- Active Status -->
              <div class="flex items-center gap-2">
                <input
                  v-model="formData.is_active"
                  type="checkbox"
                  id="is_active"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <label for="is_active" class="text-sm text-secondary-700">Aktif</label>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-6 bg-secondary-50 flex justify-end gap-3">
              <button @click="closeModal" class="btn btn-ghost">Batal</button>
              <button
                @click="handleSubmit"
                class="btn btn-primary"
              >
                {{ editingItem ? 'Simpan Perubahan' : 'Tambah' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </MainLayout>
</template>

<style scoped>
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
