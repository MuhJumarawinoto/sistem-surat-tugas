<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'

const pegawaiList = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterRole = ref('')
const filterUnitKerja = ref('')
const filterStatus = ref('')

const roles = ref([])
const unitKerjas = ref([])

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

// Modal states
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedPegawai = ref(null)

const editForm = ref({
  name: '',
  email: '',
  nip: '',
  role_id: '',
  unit_kerja_id: '',
  pangkat_gol: '',
  jabatan: '',
  no_hp: '',
  alamat: '',
  is_active: true,
})

onMounted(async () => {
  await loadPegawai()
  await loadRoles()
  await loadUnitKerjas()
})

async function loadPegawai(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
    }

    if (searchQuery.value) params.search = searchQuery.value
    if (filterRole.value) params.role_id = filterRole.value
    if (filterUnitKerja.value) params.unit_kerja_id = filterUnitKerja.value
    if (filterStatus.value !== '') params.is_active = filterStatus.value === '1'

    const response = await api.get('/pegawai', { params })
    pegawaiList.value = response.data.data || []
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
    }
  } catch (error) {
    console.error('Failed to load pegawai:', error)
  } finally {
    loading.value = false
  }
}

async function loadRoles() {
  try {
    const response = await api.get('/pegawai/roles')
    roles.value = response.data || []
  } catch (error) {
    console.error('Failed to load roles:', error)
  }
}

async function loadUnitKerjas() {
  try {
    const response = await api.get('/pegawai/unit-kerjas')
    unitKerjas.value = response.data || []
  } catch (error) {
    console.error('Failed to load unit kerjas:', error)
  }
}

function openEditModal(pegawai) {
  selectedPegawai.value = pegawai
  editForm.value = {
    name: pegawai.name,
    email: pegawai.email,
    nip: pegawai.nip,
    role_id: pegawai.role_id,
    unit_kerja_id: pegawai.unit_kerja_id,
    pangkat_gol: pegawai.pangkat_gol || '',
    jabatan: pegawai.jabatan || '',
    no_hp: pegawai.no_hp || '',
    alamat: pegawai.alamat || '',
    is_active: pegawai.is_active,
  }
  showEditModal.value = true
}

function openDeleteModal(pegawai) {
  selectedPegawai.value = pegawai
  showDeleteModal.value = true
}

async function updatePegawai() {
  try {
    await api.put(`/pegawai/${selectedPegawai.value.id}`, editForm.value)
    alert('Data pegawai berhasil diupdate')
    showEditModal.value = false
    await loadPegawai(pagination.value.current_page)
  } catch (error) {
    alert('Gagal mengupdate: ' + (error.response?.data?.message || error.message))
  }
}

async function deletePegawai() {
  try {
    await api.delete(`/pegawai/${selectedPegawai.value.id}`)
    alert('Pegawai berhasil dihapus')
    showDeleteModal.value = false
    await loadPegawai(pagination.value.current_page)
  } catch (error) {
    alert('Gagal menghapus: ' + (error.response?.data?.message || error.message))
  }
}

function getRoleName(roleId) {
  const role = roles.value.find(r => r.id === roleId)
  return role?.name || '-'
}

function getUnitKerjaName(unitKerjaId) {
  const unit = unitKerjas.value.find(u => u.id === unitKerjaId)
  return unit?.nama || '-'
}

function getStatusColor(isActive) {
  return isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}

function getStatusLabel(isActive) {
  return isActive ? 'Aktif' : 'Tidak Aktif'
}

function resetFilters() {
  searchQuery.value = ''
  filterRole.value = ''
  filterUnitKerja.value = ''
  filterStatus.value = ''
  loadPegawai(1)
}
</script>

<template>
  <MainLayout>
    <div class="mb-6 animate-fade-in">
      <h2 class="text-2xl font-bold text-secondary-800">Data Pegawai</h2>
      <p class="text-secondary-500 mt-1">Kelola data pegawai BKPSDM</p>
    </div>

    <!-- Filters -->
    <div class="card animate-slide-up">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
          <div>
                <label class="input-label">Cari</label>
                <input
                  v-model="searchQuery"
                  @keyup.enter="loadPegawai(1)"
                  type="text"
                  placeholder="Nama / NIP"
                  class="input-field"
                />
              </div>
              <div>
                <label class="input-label">Role</label>
                <select v-model="filterRole" @change="loadPegawai(1)" class="select-field">
                  <option value="">Semua Role</option>
                  <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="input-label">Unit Kerja</label>
                <select v-model="filterUnitKerja" @change="loadPegawai(1)" class="select-field">
                  <option value="">Semua Unit Kerja</option>
                  <option v-for="unit in unitKerjas" :key="unit.id" :value="unit.id">
                    {{ unit.nama }}
                  </option>
                </select>
              </div>
              <div>
                <label class="input-label">Status</label>
                <select v-model="filterStatus" @change="loadPegawai(1)" class="select-field">
                  <option value="">Semua Status</option>
                  <option value="1">Aktif</option>
                  <option value="0">Tidak Aktif</option>
                </select>
              </div>
              <div class="flex items-end">
                <button @click="resetFilters" class="btn btn-secondary w-full">
                  <i class="ri-refresh-line mr-1"></i>
                  Reset
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="card animate-slide-up">
          <div class="card-body">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm text-secondary-500">
                Total {{ pagination.total }} pegawai
              </p>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-12">
              <LoadingSpinner size="md" text="Memuat..." />
            </div>

            <div v-else-if="pegawaiList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-user-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500">Tidak ada data pegawai</p>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-secondary-50 border-b border-secondary-200">
                    <th class="px-3 py-2 text-left font-semibold text-secondary-700 whitespace-nowrap">NIP</th>
                    <th class="px-3 py-2 text-left font-semibold text-secondary-700">Nama</th>
                    <th class="px-3 py-2 text-left font-semibold text-secondary-700">Jabatan</th>
                    <th class="px-3 py-2 text-left font-semibold text-secondary-700">Unit Kerja</th>
                    <th class="px-3 py-2 text-left font-semibold text-secondary-700 whitespace-nowrap">Role</th>
                    <th class="px-3 py-2 text-center font-semibold text-secondary-700 whitespace-nowrap">Status</th>
                    <th class="px-3 py-2 text-center font-semibold text-secondary-700 whitespace-nowrap">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pegawai in pegawaiList" :key="pegawai.id" class="border-b border-secondary-100 hover:bg-secondary-50">
                    <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ pegawai.nip }}</td>
                    <td class="px-3 py-2">
                      <p class="font-medium text-secondary-800 text-xs">{{ pegawai.name }}</p>
                      <p class="text-xs text-secondary-500 truncate max-w-[150px]">{{ pegawai.email }}</p>
                    </td>
                    <td class="px-3 py-2">
                      <p class="text-xs">{{ pegawai.jabatan || '-' }}</p>
                      <p class="text-xs text-secondary-500">{{ pegawai.pangkat_gol || '-' }}</p>
                    </td>
                    <td class="px-3 py-2 text-xs">{{ getUnitKerjaName(pegawai.unit_kerja_id) }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ getRoleName(pegawai.role_id) }}</span>
                    </td>
                    <td class="px-3 py-2 text-center">
                      <span class="px-2 py-0.5 rounded text-xs font-medium" :class="pegawai.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ pegawai.is_active ? 'Aktif' : 'Tidak Aktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex items-center justify-center gap-1">
                        <button
                          @click="openEditModal(pegawai)"
                          class="p-1.5 rounded text-primary-600 hover:bg-primary-50 transition-colors"
                          title="Edit"
                        >
                          <i class="ri-edit-line text-sm"></i>
                        </button>
                        <button
                          @click="openDeleteModal(pegawai)"
                          class="p-1.5 rounded text-red-600 hover:bg-red-50 transition-colors"
                          title="Hapus"
                        >
                          <i class="ri-delete-bin-line text-sm"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t border-secondary-200">
              <p class="text-sm text-secondary-500">
                Hal {{ pagination.current_page }} dari {{ pagination.last_page }}
              </p>
              <div class="flex items-center gap-1">
                <button
                  @click="loadPegawai(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="btn btn-ghost btn-sm"
                >
                  <i class="ri-arrow-left-s-line"></i>
                </button>
                <button
                  @click="loadPegawai(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                  class="btn btn-ghost btn-sm"
                >
                  <i class="ri-arrow-right-s-line"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

    <!-- Edit Modal -->
    <Teleport to="body">
        <Transition name="modal">
          <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showEditModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div class="card-header">
                <h3 class="card-title">Edit Pegawai</h3>
              </div>
              <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="input-label">Nama</label>
                    <input v-model="editForm.name" type="text" class="input-field" />
                  </div>
                  <div>
                    <label class="input-label">NIP</label>
                    <input v-model="editForm.nip" type="text" class="input-field" />
                  </div>
                  <div>
                    <label class="input-label">Email</label>
                    <input v-model="editForm.email" type="email" class="input-field" />
                  </div>
                  <div>
                    <label class="input-label">No. HP</label>
                    <input v-model="editForm.no_hp" type="text" class="input-field" />
                  </div>
                  <div>
                    <label class="input-label">Pangkat/Gol</label>
                    <input v-model="editForm.pangkat_gol" type="text" class="input-field" />
                  </div>
                  <div>
                    <label class="input-label">Jabatan</label>
                    <input v-model="editForm.jabatan" type="text" class="input-field" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                    <select v-model="editForm.role_id" class="input-field py-1.5 text-sm w-full">
                      <option v-for="role in roles" :key="role.id" :value="role.id">
                        {{ role.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="input-label">Unit Kerja</label>
                    <select v-model="editForm.unit_kerja_id" class="select-field">
                      <option value="">Pilih Unit Kerja</option>
                      <option v-for="unit in unitKerjas" :key="unit.id" :value="unit.id">
                        {{ unit.nama }}
                      </option>
                    </select>
                  </div>
                  <div class="md:col-span-2">
                    <label class="input-label">Alamat</label>
                    <textarea v-model="editForm.alamat" rows="2" class="input-field"></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="flex items-center gap-2">
                      <input v-model="editForm.is_active" type="checkbox" class="rounded" />
                      <span class="text-sm text-secondary-700">Aktif</span>
                    </label>
                  </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                  <button @click="showEditModal = false" class="btn btn-secondary">
                    Batal
                  </button>
                  <button @click="updatePegawai" class="btn btn-primary">
                    Simpan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Delete Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full">
              <div class="card-body">
                <h3 class="text-lg font-bold text-secondary-900 mb-2">Hapus Pegawai</h3>
                <p class="text-sm text-secondary-600 mb-4">
                  Apakah Anda yakin ingin menghapus pegawai <strong>{{ selectedPegawai?.name }}</strong>?
                </p>
                <div class="flex justify-end gap-2">
                  <button @click="showDeleteModal = false" class="btn btn-secondary">
                    Batal
                  </button>
                  <button @click="deletePegawai" class="btn btn-danger">
                    Hapus
                  </button>
                </div>
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
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
