<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'

const toast = useToastStore()

const pegawaiList = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterRole = ref('')
const filterUnitKerja = ref('')
const filterStatus = ref('')

const roles = ref([])
const unitKerjas = ref([])
const jabatanCategories = ref([])

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 5,
  total: 0,
})

// Sync states
const showImportModal = ref(false)
const importFile = ref(null)
const importMode = ref('sync') // create, update, or sync
const importing = ref(false)
const importResult = ref(null)

// SIMPEG Sync states
const syncing = ref(false)
const syncResult = ref(null)

// Modal states
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showStructureModal = ref(false)
const selectedPegawai = ref(null)

// Structure modal states
const structureData = ref({
  pegawai: null,
  atasan_chain: [],
  bawahan: []
})
const loadingStructure = ref(false)

const editForm = ref({
  name: '',
  email: '',
  nip: '',
  role_id: '',
  unit_kerja_id: '',
  atasan_id: '',
  jabatan_kategori: '',
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
  await loadJabatanCategories()
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

async function loadJabatanCategories() {
  try {
    const response = await api.get('/verification/categories')
    jabatanCategories.value = response.data || []
  } catch (error) {
    console.error('Failed to load jabatan categories:', error)
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
    atasan_id: pegawai.atasan_id || '',
    jabatan_kategori: pegawai.jabatan_kategori || '',
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

async function openStructureModal(pegawai) {
  selectedPegawai.value = pegawai
  loadingStructure.value = true
  showStructureModal.value = true

  try {
    const response = await api.get(`/pegawai/${pegawai.id}/structure`)
    structureData.value = response.data
  } catch (error) {
    console.error('Failed to load structure:', error)
    showStructureModal.value = false
  } finally {
    loadingStructure.value = false
  }
}

function closeStructureModal() {
  showStructureModal.value = false
  structureData.value = {
    pegawai: null,
    atasan_chain: [],
    bawahan: []
  }
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

// Import functions
function openImportModal() {
  importResult.value = null
  importFile.value = null
  importMode.value = 'sync'
  showImportModal.value = true
}

function closeImportModal() {
  showImportModal.value = false
  importResult.value = null
  importFile.value = null
}

async function downloadTemplate() {
  try {
    const response = await api.get('/admin/pegawai-sync/template')
    const template = response.data.template
    const blob = new Blob([JSON.stringify(template, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-pegawai.json'
    a.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    alert('Gagal download template: ' + (error.response?.data?.message || error.message))
  }
}

async function importPegawai() {
  if (!importFile.value) {
    alert('Pilih file JSON terlebih dahulu')
    return
  }

  importing.value = true
  importResult.value = null

  try {
    const formData = new FormData()
    formData.append('file', importFile.value)
    formData.append('mode', importMode.value)

    const response = await api.post('/admin/pegawai-sync/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    importResult.value = response.data.data

    if (importResult.value.success > 0 || importResult.value.updated > 0) {
      await loadPegawai(1)
    }
  } catch (error) {
    alert('Import gagal: ' + (error.response?.data?.message || error.message))
    importResult.value = {
      success: 0,
      updated: 0,
      skipped: 0,
      failed: 1,
      errors: [error.response?.data?.message || error.message]
    }
  } finally {
    importing.value = false
  }
}

function handleFileSelect(event) {
  const file = event.target.files?.[0]
  if (file) {
    importFile.value = file
  }
}

// SIMPEG Sync function
async function syncFromSimpeg() {
  if (syncing.value) return

  syncing.value = true
  syncResult.value = null

  try {
    const response = await api.post('/admin/pegawai-sync/sync-simpeg')
    syncResult.value = response.data.data

    // Show toast notification
    const totalProcessed = (syncResult.value.success || 0) + (syncResult.value.updated || 0)
    toast.success(
      `Sync berhasil! ${totalProcessed} pegawai diproses`,
      5000
    )

    // Refresh pegawai list
    await loadPegawai(1)
  } catch (error) {
    const errorMessage = error.response?.data?.message || error.message || 'Sync gagal'
    const suggestion = error.response?.data?.suggestion

    // Show error toast with suggestion
    let fullMessage = errorMessage
    if (suggestion) {
      fullMessage += '. ' + suggestion
    }

    toast.error(fullMessage, 8000) // Longer duration for messages with suggestions

    syncResult.value = {
      success: 0,
      updated: 0,
      skipped: 0,
      failed: 1
    }
  } finally {
    syncing.value = false
  }
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Data Pegawai"
      subtitle="Kelola data pegawai dan atasan"
    />

    <!-- Sync Stats & Actions -->
    <div class="card animate-slide-up mb-4">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-6">
            <div class="text-center">
              <p class="text-2xl font-bold text-secondary-800">{{ pagination.total }}</p>
              <p class="text-xs text-secondary-500">Total Pegawai</p>
            </div>
            <div class="h-10 w-px bg-secondary-200"></div>
            <div class="text-center">
              <p class="text-lg font-semibold text-green-600">{{ unitKerjas.length }}</p>
              <p class="text-xs text-secondary-500">Unit Kerja</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="downloadTemplate"
              class="btn btn-outline btn-sm"
            >
              <i class="ri-download-line mr-1"></i>
              Template
            </button>
            <button
              @click="openImportModal"
              class="btn btn-secondary btn-sm"
            >
              <i class="ri-upload-line mr-1"></i>
              Import JSON
            </button>
            <button
              @click="syncFromSimpeg"
              :disabled="syncing"
              class="btn btn-primary btn-sm relative"
            >
              <LoadingSpinner v-if="syncing" size="sm" />
              <i v-else class="ri-refresh-line mr-1"></i>
              <span>{{ syncing ? 'Syncing...' : 'Sync SIMPEG' }}</span>
            </button>
          </div>
        </div>
      </div>
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

            <div v-else>
              <!-- Mobile Card View -->
              <div class="lg:hidden space-y-3">
                <div
                  v-for="pegawai in pegawaiList"
                  :key="pegawai.id"
                  class="card card-body p-4"
                >
                  <!-- Header: Name + Status -->
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex-1 min-w-0">
                      <p class="font-semibold text-secondary-800 text-sm">{{ pegawai.name }}</p>
                      <p class="text-xs text-secondary-500 font-mono">{{ pegawai.nip }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 ml-2" :class="pegawai.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ pegawai.is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                  </div>

                  <!-- Details -->
                  <div class="space-y-2 text-sm mb-3">
                    <div class="flex justify-between">
                      <span class="text-secondary-500 text-xs">Email</span>
                      <span class="text-xs text-right truncate ml-2 max-w-[200px]">{{ pegawai.email || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-secondary-500 text-xs">Jabatan</span>
                      <span class="text-xs">{{ pegawai.jabatan || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-secondary-500 text-xs">Pangkat/Gol</span>
                      <span class="text-xs">{{ pegawai.pangkat_gol || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-secondary-500 text-xs">Unit Kerja</span>
                      <span class="text-xs text-right">{{ getUnitKerjaName(pegawai.unit_kerja_id) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-secondary-500 text-xs">Role</span>
                      <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ getRoleName(pegawai.role_id) }}</span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex gap-2 pt-2 border-t border-secondary-100">
                    <button
                      @click="openStructureModal(pegawai)"
                      class="flex-1 btn btn-outline btn-sm text-xs"
                    >
                      <i class="ri-organization-chart mr-1"></i>
                      Struktur
                    </button>
                    <button
                      @click="openEditModal(pegawai)"
                      class="flex-1 btn btn-secondary btn-sm text-xs"
                    >
                      <i class="ri-edit-line mr-1"></i>
                      Edit
                    </button>
                    <button
                      @click="openDeleteModal(pegawai)"
                      class="btn btn-danger btn-sm text-xs"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Desktop Table View -->
              <div class="hidden lg:block overflow-x-auto">
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
                            @click="openStructureModal(pegawai)"
                            class="p-1.5 rounded text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Lihat Struktur"
                          >
                            <i class="ri-organization-chart text-sm"></i>
                          </button>
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
          <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
            <div class="absolute inset-0 bg-black/50 lg:hidden" @click="showEditModal = false"></div>
            <div class="relative bg-white w-full h-full sm:h-auto sm:max-w-2xl sm:max-h-[90vh] sm:rounded-xl sm:shadow-xl overflow-hidden flex flex-col">
              <div class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Edit Pegawai</h3>
                <button @click="showEditModal = false" class="btn btn-ghost btn-icon sm:hidden">
                  <i class="ri-close-line text-2xl"></i>
                </button>
              </div>
              <div class="p-4 sm:p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                  <div>
                    <label class="input-label">Atasan Langsung</label>
                    <select v-model="editForm.atasan_id" class="select-field">
                      <option value="">Pilih Atasan</option>
                      <option v-for="staff in pegawaiList" :key="staff.id" :value="staff.id">
                        {{ staff.name }} - {{ staff.nip }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="input-label">Kategori Jabatan</label>
                    <select v-model="editForm.jabatan_kategori" class="select-field">
                      <option value="">Pilih Kategori</option>
                      <option v-for="cat in jabatanCategories" :key="cat.kode" :value="cat.kode">
                        {{ cat.nama_jabatan }}
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

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t sm:border-t-0 sticky bottom-0 bg-white sm:bg-transparent pb-safe sm:pb-0">
                  <button @click="showEditModal = false" class="btn btn-secondary flex-1 sm:flex-none">
                    Batal
                  </button>
                  <button @click="updatePegawai" class="btn btn-primary flex-1 sm:flex-none">
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
          <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="showDeleteModal = false">
            <div class="relative bg-white w-full sm:max-w-md sm:rounded-xl shadow-xl p-6">
              <h3 class="text-lg font-bold text-secondary-900 mb-2">Hapus Pegawai</h3>
              <p class="text-sm text-secondary-600 mb-4">
                Apakah Anda yakin ingin menghapus pegawai <strong>{{ selectedPegawai?.name }}</strong>?
              </p>
              <div class="flex justify-end gap-2">
                <button @click="showDeleteModal = false" class="btn btn-secondary flex-1 sm:flex-none">
                  Batal
                </button>
                <button @click="deletePegawai" class="btn btn-danger flex-1 sm:flex-none">
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Structure Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="showStructureModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="closeStructureModal">
            <div class="relative bg-white w-full h-full sm:h-auto sm:max-w-3xl sm:max-h-[90vh] sm:rounded-xl shadow-xl overflow-hidden flex flex-col">
              <div class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Struktur Organisasi</h3>
                <button @click="closeStructureModal" class="btn btn-ghost btn-icon">
                  <i class="ri-close-line text-xl"></i>
                </button>
              </div>
              <div class="p-4 sm:p-6 overflow-y-auto flex-1">
                <LoadingSpinner v-if="loadingStructure" text="Memuat struktur..." />

                <div v-else-if="structureData.pegawai" class="space-y-6">
                  <!-- Pegawai Info -->
                  <div class="bg-primary-50 rounded-lg p-4 border-2 border-primary-200">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                        <i class="ri-user-line text-xl text-primary-600"></i>
                      </div>
                      <div>
                        <p class="font-semibold text-primary-900">{{ structureData.pegawai.name }}</p>
                        <p class="text-sm text-primary-700">{{ structureData.pegawai.nip }}</p>
                        <p class="text-xs text-primary-600 mt-1">{{ structureData.pegawai.jabatan || '-' }} {{ structureData.pegawai.unit_kerja?.nama ? '• ' + structureData.pegawai.unit_kerja.nama : '' }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Atasan Chain -->
                  <div>
                    <h4 class="text-sm font-semibold text-secondary-700 mb-3 flex items-center gap-2">
                      <i class="ri-arrow-up-circle-fill text-green-600"></i>
                      Atasan Langsung
                    </h4>
                    <div v-if="structureData.atasan_chain.length === 0" class="text-sm text-secondary-500 italic bg-secondary-50 rounded-lg p-4">
                      Tidak ada atasan yang ditetapkan untuk pegawai ini.
                    </div>
                    <div v-else class="space-y-2">
                      <div
                        v-for="(atasan, index) in structureData.atasan_chain"
                        :key="atasan.id"
                        class="flex items-start gap-3 bg-green-50 rounded-lg p-3 border border-green-200"
                      >
                        <div class="flex flex-col items-center">
                          <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="ri-user-star-line text-green-700"></i>
                          </div>
                          <div v-if="index < structureData.atasan_chain.length - 1" class="w-0.5 flex-1 bg-green-300 my-1"></div>
                        </div>
                        <div class="flex-1">
                          <div class="flex items-center justify-between">
                            <p class="font-medium text-green-900">{{ atasan.name }}</p>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                              {{ getRoleName(atasan.role_id) }}
                            </span>
                          </div>
                          <p class="text-sm text-green-700">{{ atasan.nip }}</p>
                          <p class="text-xs text-green-600 mt-1">{{ atasan.jabatan || '-' }} {{ atasan.unit_kerja?.nama ? '• ' + atasan.unit_kerja.nama : '' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Bawahan -->
                  <div>
                    <h4 class="text-sm font-semibold text-secondary-700 mb-3 flex items-center gap-2">
                      <i class="ri-arrow-down-circle-fill text-blue-600"></i>
                      Bawahan Langsung
                    </h4>
                    <div v-if="structureData.bawahan.length === 0" class="text-sm text-secondary-500 italic bg-secondary-50 rounded-lg p-4">
                      Pegawai ini tidak memiliki bawahan.
                    </div>
                    <div v-else class="grid grid-cols-1 gap-2">
                      <div
                        v-for="bawahan in structureData.bawahan"
                        :key="bawahan.id"
                        class="flex items-center gap-3 bg-blue-50 rounded-lg p-3 border border-blue-200"
                      >
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                          <i class="ri-user-line text-blue-700"></i>
                        </div>
                        <div class="flex-1">
                          <div class="flex items-center justify-between">
                            <p class="font-medium text-blue-900">{{ bawahan.name }}</p>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                              {{ getRoleName(bawahan.role_id) }}
                            </span>
                          </div>
                          <p class="text-sm text-blue-700">{{ bawahan.nip }}</p>
                          <p class="text-xs text-blue-600 mt-1">{{ bawahan.jabatan || '-' }} {{ bawahan.unit_kerja?.nama ? '• ' + bawahan.unit_kerja.nama : '' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex justify-end p-4 border-t bg-white sticky bottom-0">
                <button @click="closeStructureModal" class="btn btn-secondary flex-1 sm:flex-none">
                  Tutup
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Import Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="closeImportModal">
            <div class="absolute inset-0 bg-black/50 lg:hidden" @click="closeImportModal"></div>
            <div class="relative bg-white w-full h-full sm:h-auto sm:max-w-lg sm:max-h-[90vh] sm:rounded-xl shadow-xl overflow-hidden flex flex-col">
              <div class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Import Pegawai dari JSON</h3>
                <button @click="closeImportModal" class="btn btn-ghost btn-icon">
                  <i class="ri-close-line text-xl"></i>
                </button>
              </div>
              <div class="p-4 sm:p-6 overflow-y-auto flex-1">
                <div v-if="!importResult" class="space-y-4">
                  <!-- Import Mode -->
                  <div>
                    <label class="input-label">Mode Import</label>
                    <select v-model="importMode" class="select-field">
                      <option value="sync">Sync (Create + Update)</option>
                      <option value="create">Create Only (Baru)</option>
                      <option value="update">Update Only (Existing)</option>
                    </select>
                    <p class="text-xs text-secondary-500 mt-1">
                      <span v-if="importMode === 'sync'">Tambah pegawai baru dan update data yang sudah ada</span>
                      <span v-else-if="importMode === 'create'">Hanya tambahkan pegawai baru, abaikan yang sudah ada</span>
                      <span v-else>Hanya update pegawai yang sudah ada, abaikan yang baru</span>
                    </p>
                  </div>

                  <!-- File Upload -->
                  <div>
                    <label class="input-label">File JSON</label>
                    <input
                      type="file"
                      accept=".json"
                      @change="handleFileSelect"
                      class="input-field"
                    />
                    <p class="text-xs text-secondary-500 mt-1">
                      Download template terlebih dahulu untuk melihat format yang benar
                    </p>
                  </div>

                  <!-- Import Button -->
                  <button
                    @click="importPegawai"
                    :disabled="!importFile || importing"
                    class="btn btn-primary w-full"
                  >
                    <LoadingSpinner v-if="importing" size="sm" />
                    <span v-else>Import Data</span>
                  </button>
                </div>

                <!-- Import Result -->
                <div v-else class="space-y-4">
                  <div class="bg-secondary-50 rounded-lg p-4">
                    <h4 class="font-semibold text-secondary-900 mb-3">Hasil Import</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                      <div class="bg-green-50 rounded p-2 text-center">
                        <p class="text-xl font-bold text-green-600">{{ importResult.success }}</p>
                        <p class="text-xs text-green-700">Berhasil Dibuat</p>
                      </div>
                      <div class="bg-blue-50 rounded p-2 text-center">
                        <p class="text-xl font-bold text-blue-600">{{ importResult.updated }}</p>
                        <p class="text-xs text-blue-700">Berhasil Diupdate</p>
                      </div>
                      <div class="bg-yellow-50 rounded p-2 text-center">
                        <p class="text-xl font-bold text-yellow-600">{{ importResult.skipped }}</p>
                        <p class="text-xs text-yellow-700">Dilewati</p>
                      </div>
                      <div class="bg-red-50 rounded p-2 text-center">
                        <p class="text-xl font-bold text-red-600">{{ importResult.failed }}</p>
                        <p class="text-xs text-red-700">Gagal</p>
                      </div>
                    </div>
                  </div>

                  <!-- Errors -->
                  <div v-if="importResult.errors.length > 0" class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-2">Error Details</h4>
                    <div class="max-h-40 overflow-y-auto space-y-1">
                      <p v-for="(error, index) in importResult.errors" :key="index" class="text-xs text-red-700">
                        {{ error }}
                      </p>
                    </div>
                  </div>

                  <div class="flex gap-2">
                    <button @click="openImportModal" class="btn btn-secondary flex-1">
                      Import Lagi
                    </button>
                    <button @click="closeImportModal" class="btn btn-primary flex-1">
                      Tutup
                    </button>
                  </div>
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
