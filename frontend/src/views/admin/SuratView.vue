<script setup>
import { ref, onMounted } from 'vue'
import { usePengajuanStore } from '@/stores/pengajuan'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import PageHeader from '@/components/PageHeader.vue'
import SendMessageModal from '@/components/SendMessageModal.vue'
import PengajuanMilestone from '@/components/PengajuanMilestone.vue'

const pengajuanStore = usePengajuanStore()

const suratList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedPengajuan = ref(null)

onMounted(async () => {
  await loadSurat()
})

async function loadSurat() {
  loading.value = true
  try {
    const response = await api.get('/pengajuan', { params: { status: 'disetujui' } })
    suratList.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load surat:', error)
  } finally {
    loading.value = false
  }
}

async function signSurat(id) {
  if (!confirm('Tanda tangani surat ini?')) return

  try {
    await api.post(`/surat/${id}/sign-tte`)
    alert('Surat berhasil ditandatangani')
    await loadSurat()
  } catch (error) {
    alert('Gagal menandatangani surat')
  }
}

function openSendMessageModal(pengajuan) {
  selectedPengajuan.value = pengajuan
  showModal.value = true
}

function handleMessageSent() {
  alert('Pesan berhasil dikirim ke pemohon')
}
</script>

<template>
  <MainLayout>
    <Breadcrumb />
    <PageHeader
      title="Tanda Tangan Surat"
      subtitle="Daftar surat yang siap ditandatangani"
    />

    <div class="card animate-slide-up">
      <div class="card-body">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <LoadingSpinner size="md" text="Memuat..." />
        </div>

        <div v-else-if="suratList.length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
                <i class="ri-file-sign-line text-3xl text-secondary-400"></i>
              </div>
              <p class="text-secondary-500">Tidak ada surat yang menunggu tanda tangan</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="item in suratList"
                :key="item.id"
                class="card border-l-4 border-l-success"
              >
                <div class="card-body">
                  <!-- Card Header -->
                  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-secondary-100">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-1">
                        <p class="text-base font-semibold text-secondary-800">{{ item.nomor_pengajuan }}</p>
                        <span class="badge badge-success">
                          <i class="ri-check-line"></i>
                          Siap TTD
                        </span>
                      </div>
                      <p class="text-sm font-medium text-secondary-800">{{ item.user?.name }}</p>
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
                      <button @click="signSurat(item.id)" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line mr-1"></i>
                        Tanda Tangan
                      </button>
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
                    </div>
                  </div>

                  <!-- Inline Milestone -->
                  <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-secondary-700 flex items-center gap-1">
                        <i class="ri-route-line text-primary-600"></i>
                        Progress Pengajuan
                      </span>
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
  </MainLayout>
</template>
