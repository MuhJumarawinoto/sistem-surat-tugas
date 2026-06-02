<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const authStore = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const showPasswordModal = ref(false)
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const profile = ref({
  name: '',
  email: '',
  nip: '',
  pangkat: '',
  jabatan: '',
  unit_kerja: '',
  role: ''
})

// Use authStore user as fallback
const displayName = computed(() => profile.value.name || authStore.user?.name || '')
const displayEmail = computed(() => profile.value.email || authStore.user?.email || '')

const getRoleLabel = (role) => {
  const labels = {
    'pemohon': 'PNS Pemohon',
    'atasan': 'Atasan Langsung',
    'admin_bkpsdm': 'Admin BKPSDM',
    'kepala_bkpsdm': 'Kepala BKPSDM'
  }
  return labels[role] || role
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

async function loadProfile() {
  loading.value = true
  try {
    const response = await api.get('/auth/me')
    const data = response.data

    // Map API response to profile fields
    profile.value = {
      name: data.name || data.nama || authStore.user?.name || '',
      email: data.email || authStore.user?.email || '',
      nip: data.nip || '',
      pangkat: data.pangkat || data.pangkat_gol || '',
      jabatan: data.jabatan || '',
      unit_kerja: data.unit_kerja?.nama || data.unit_kerja || '',
      role: data.role || ''
    }
  } catch (error) {
    console.error('Failed to load profile:', error)
    // Use authStore user as fallback
    if (authStore.user) {
      profile.value = {
        name: authStore.user.name || '',
        email: authStore.user.email || '',
        nip: authStore.user.nip || '',
        pangkat: authStore.user.pangkat || authStore.user.pangkat_gol || '',
        jabatan: authStore.user.jabatan || '',
        unit_kerja: authStore.user.unit_kerja?.nama || authStore.user.unit_kerja || '',
        role: authStore.user.role || ''
      }
    }
  } finally {
    loading.value = false
  }
}

async function updateProfile() {
  saving.value = true
  try {
    await api.put('/auth/profile', {
      name: profile.value.name,
      email: profile.value.email
    })
    await authStore.fetchUser()
    alert('Profile berhasil diperbarui')
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal memperbarui profile')
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  if (!passwordForm.value.current_password || !passwordForm.value.password) {
    alert('Mohon lengkapi semua field password')
    return
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    alert('Konfirmasi password tidak cocok')
    return
  }

  saving.value = true
  try {
    await api.put('/auth/password', passwordForm.value)
    alert('Password berhasil diubah')
    showPasswordModal.value = false
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: ''
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengubah password')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Profil Saya"
      subtitle="Kelola informasi profil dan keamanan akun Anda"
    />
    <div class="animate-fade-in pb-6">

      <LoadingSpinner v-if="loading" size="lg" text="Memuat profil..." />

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="lg:col-span-1">
          <div class="card">
            <div class="card-body text-center">
              <!-- Avatar -->
              <div class="avatar avatar-xl bg-primary-100 text-primary-700 mx-auto mb-4">
                {{ getInitials(displayName) }}
              </div>

              <!-- Name -->
              <h3 class="text-lg font-semibold text-secondary-800">{{ displayName }}</h3>
              <p class="text-sm text-secondary-500 mt-1 mb-3">{{ displayEmail }}</p>

              <!-- Role Badge -->
              <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-700">
                {{ getRoleLabel(profile.role) }}
              </span>

              <!-- Info -->
              <div class="mt-6 pt-6 border-t border-secondary-200 text-left space-y-4">
                <div v-if="profile.nip">
                  <p class="text-xs text-secondary-500 uppercase tracking-wide mb-1">NIP</p>
                  <p class="text-sm font-medium text-secondary-800">{{ profile.nip }}</p>
                </div>
                <div v-if="profile.pangkat">
                  <p class="text-xs text-secondary-500 uppercase tracking-wide mb-1">Pangkat/Golongan</p>
                  <p class="text-sm font-medium text-secondary-800">{{ profile.pangkat }}</p>
                </div>
                <div v-if="profile.jabatan">
                  <p class="text-xs text-secondary-500 uppercase tracking-wide mb-1">Jabatan</p>
                  <p class="text-sm font-medium text-secondary-800">{{ profile.jabatan }}</p>
                </div>
                <div v-if="profile.unit_kerja">
                  <p class="text-xs text-secondary-500 uppercase tracking-wide mb-1">Unit Kerja</p>
                  <p class="text-sm font-medium text-secondary-800">{{ profile.unit_kerja }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Personal Info -->
          <div class="card animate-slide-up">
            <div class="card-header">
              <h3 class="card-title">Informasi Pribadi</h3>
            </div>
            <div class="card-body space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Nama Lengkap</label>
                  <input
                    v-model="profile.name"
                    type="text"
                    class="w-full px-4 py-2.5 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="Nama Lengkap"
                  />
                </div>
                <div>
                  <label class="form-label">Email</label>
                  <input
                    v-model="profile.email"
                    type="email"
                    class="w-full px-4 py-2.5 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="email@example.com"
                  />
                </div>
              </div>

              <div class="flex justify-end pt-2">
                <button
                  @click="updateProfile"
                  :disabled="saving"
                  class="btn btn-primary gap-2"
                >
                  <i v-if="saving" class="ri-loader-4-line animate-spin"></i>
                  <i v-else class="ri-save-line"></i>
                  <span>{{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Security -->
          <div class="card animate-slide-up">
            <div class="card-header">
              <h3 class="card-title">Keamanan</h3>
            </div>
            <div class="card-body">
              <p class="text-sm text-secondary-500 mb-4">
                Ubah password secara berkala untuk menjaga keamanan akun Anda.
              </p>
              <button
                @click="showPasswordModal = true"
                class="btn btn-secondary gap-2"
              >
                <i class="ri-lock-line"></i>
                <span>Ubah Password</span>
              </button>
            </div>
          </div>

          <!-- Account Info -->
          <div class="card animate-slide-up">
            <div class="card-header">
              <h3 class="card-title">Informasi Akun</h3>
            </div>
            <div class="card-body">
              <div class="space-y-0 text-sm">
                <div class="flex justify-between py-3 border-b border-secondary-100">
                  <span class="text-secondary-500">Role</span>
                  <span class="font-medium text-secondary-800">{{ getRoleLabel(profile.role) }}</span>
                </div>
                <div class="flex justify-between py-3">
                  <span class="text-secondary-500">Status</span>
                  <span class="inline-flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="font-medium text-secondary-800">Aktif</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Change Password Modal -->
    <Transition name="modal">
      <div
        v-if="showPasswordModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showPasswordModal = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md animate-slide-up">
          <div class="flex items-center justify-between p-5 border-b">
            <h3 class="text-lg font-semibold text-secondary-800">Ubah Password</h3>
            <button @click="showPasswordModal = false" class="p-1 hover:bg-secondary-100 rounded-lg transition-colors">
              <i class="ri-close-line text-xl text-secondary-500"></i>
            </button>
          </div>
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">Password Saat Ini</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                class="w-full px-4 py-2.5 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Masukkan password saat ini"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">Password Baru</label>
              <input
                v-model="passwordForm.password"
                type="password"
                class="w-full px-4 py-2.5 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Masukkan password baru"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">Konfirmasi Password Baru</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                class="w-full px-4 py-2.5 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Ulangi password baru"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 p-5 border-t bg-secondary-50 rounded-b-xl">
            <button
              @click="showPasswordModal = false"
              class="px-4 py-2 border border-secondary-300 rounded-lg text-secondary-700 hover:bg-secondary-100 transition-colors"
            >
              Batal
            </button>
            <button
              @click="changePassword"
              :disabled="saving"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
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
