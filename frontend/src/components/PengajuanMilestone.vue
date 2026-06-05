<script setup>
import { computed, ref, watch } from 'vue'
import api from '@/services/api'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps({
  pengajuanId: {
    type: [String, Number],
    required: true
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const loading = ref(false)
const pengajuan = ref(null)

// Updated status mapping based on actual database values (Simplified Flow - 4 Steps)
const steps = computed(() => {
  if (!pengajuan.value) return []

  const status = pengajuan.value.status

  return [
    {
      id: 'kirim',
      label: 'Dikirim',
      icon: 'ri-send-plane-fill',
      description: getStatusDescription(status, 'kirim'),
      status: getStepStatus(status, 'kirim')
    },
    {
      id: 'verifikasi',
      label: 'Verifikasi',
      icon: 'ri-file-search-line',
      description: 'Admin memverifikasi kelengkapan dokumen',
      status: getStepStatus(status, 'verifikasi')
    },
    {
      id: 'tte',
      label: 'TTE',
      icon: 'ri-edit-sign-line',
      description: 'Kepala BKPSDM menandatangani surat',
      status: getStepStatus(status, 'tte')
    },
    {
      id: 'selesai',
      label: 'Selesai',
      icon: 'ri-checkbox-circle-fill',
      description: 'Surat siap diunduh',
      status: getStepStatus(status, 'selesai')
    }
  ]
})

// Get dynamic description for kirim step based on status
function getStatusDescription(pengajuanStatus, stepId) {
  if (stepId === 'kirim') {
    if (pengajuanStatus === 'pending_atasan') return 'Menunggu approval atasan'
    if (pengajuanStatus === 'pending_admin') return 'Menunggu verifikasi admin'
    return pengajuan.value?.nomor_pengajuan || 'Menunggu verifikasi'
  }
  return ''
}

// Determine step status based on pengajuan status (Simplified Flow - 4 Steps)
function getStepStatus(pengajuanStatus, stepId) {
  // Rejected status
  if (pengajuanStatus === 'ditolak') return 'rejected'

  // Step status logic
  const stepOrder = ['kirim', 'verifikasi', 'tte', 'selesai']

  // Map pengajuan status to step index
  let currentStepIndex = -1

  if (pengajuanStatus === 'draft') {
    currentStepIndex = -1 // Not started
  } else if (pengajuanStatus === 'pending_admin') {
    currentStepIndex = 0 // At 'kirim' step - menunggu verifikasi admin
  } else if (pengajuanStatus === 'verified') {
    currentStepIndex = 1 // At 'verifikasi' step
  } else if (pengajuanStatus === 'signed') {
    currentStepIndex = 2 // At 'tte' step
  } else if (pengajuanStatus === 'selesai' || pengajuanStatus === 'completed') {
    currentStepIndex = 3 // At 'selesai' step
  }

  const stepIndex = stepOrder.indexOf(stepId)

  if (stepIndex < currentStepIndex) {
    return 'completed'
  } else if (stepIndex === currentStepIndex) {
    return 'current'
  } else {
    return 'pending'
  }
}

const progressPercentage = computed(() => {
  // Simplified Flow: 4 Steps - Dikirim → Verifikasi → TTE → Selesai
  const status = pengajuan.value?.status
  if (status === 'ditolak' || status === 'draft') return 0
  if (status === 'pending_admin') return 25  // 1/4 - Dikirim completed, Verifikasi current
  if (status === 'verified') return 50      // 2/4 - Verifikasi completed, TTE current
  if (status === 'signed') return 75       // 3/4 - TTE completed, Selesai current
  if (status === 'selesai' || status === 'completed') return 100  // 4/4 - Selesai
  return 0
})

async function loadData() {
  loading.value = true
  try {
    const res = await api.get(`/pengajuan/${props.pengajuanId}`)
    pengajuan.value = res.data
  } catch (error) {
    console.error('Failed to load pengajuan:', error)
  } finally {
    loading.value = false
  }
}

function getStepClass(step) {
  if (step.status === 'completed') {
    return 'border-green-200 bg-green-50'
  } else if (step.status === 'current') {
    return 'border-primary-200 bg-primary-50'
  } else if (step.status === 'rejected') {
    return 'border-red-200 bg-red-50'
  }
  return 'border-secondary-200 bg-secondary-50'
}

function getIconClass(step) {
  if (step.status === 'completed') {
    return 'bg-green-500 text-white'
  } else if (step.status === 'current') {
    return 'bg-primary-500 text-white animate-pulse'
  } else if (step.status === 'rejected') {
    return 'bg-red-500 text-white'
  }
  return 'bg-secondary-300 text-secondary-600'
}

function getStatusLabel(status) {
  const labels = {
    'draft': 'Draft',
    'pending_admin': 'Menunggu Verifikasi',
    'verified': 'Terverifikasi',
    'surat_dinas': 'Surat Tugas Dinas',
    'surat_izin': 'Surat Izin Belajar',
    'signed': 'Ditandatangani',
    'selesai': 'Selesai',
    'completed': 'Selesai',
    'ditolak': 'Ditolak',
    'disetujui': 'Disetujui'
  }
  return labels[status] || status
}

// Load data on mount
loadData()

// Watch for pengajuanId changes
watch(() => props.pengajuanId, () => {
  loadData()
})
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center py-8">
    <LoadingSpinner size="sm" />
  </div>

  <div v-else class="pengajuan-milestone">
    <!-- Progress Bar -->
    <div v-if="!compact" class="mb-4">
      <div class="flex items-center justify-between text-sm mb-2">
        <span class="text-secondary-600">Progress Pengajuan</span>
        <span class="font-semibold" :class="progressPercentage === 100 ? 'text-green-600' : 'text-primary-600'">
          {{ progressPercentage }}%
        </span>
      </div>
      <div class="w-full bg-secondary-200 rounded-full h-2.5">
        <div
          class="h-2.5 rounded-full transition-all duration-500"
          :class="pengajuan?.status === 'ditolak' ? 'bg-red-500' : progressPercentage === 100 ? 'bg-green-500' : 'bg-primary-500'"
          :style="{ width: progressPercentage + '%' }"
        ></div>
      </div>
    </div>

    <!-- Steps - Horizontal Timeline -->
    <div class="relative">
      <!-- Connecting Line -->
      <div class="absolute top-5 left-0 right-0 h-1 bg-secondary-200 -z-10"></div>
      <div
        class="absolute top-5 left-0 h-1 bg-green-500 transition-all duration-500 -z-10"
        :style="{ width: progressPercentage + '%' }"
      ></div>

      <!-- Steps -->
      <div class="flex justify-between items-start">
        <div
          v-for="(step, index) in steps"
          :key="step.id"
          class="flex flex-col items-center"
          :class="index === steps.length - 1 ? '' : 'flex-1'"
        >
          <!-- Step Icon -->
          <div class="relative z-10 mb-2">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300"
              :class="getIconClass(step)"
            >
              <i v-if="step.status === 'completed'" class="ri-check-line text-lg"></i>
              <i v-else-if="step.status === 'rejected'" class="ri-close-line text-lg"></i>
              <i v-else :class="step.icon + ' text-lg'"></i>
            </div>
            <!-- Step Number -->
            <div
              v-if="step.status === 'pending'"
              class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-secondary-100 border-2 border-white flex items-center justify-center text-xs font-semibold text-secondary-500"
            >
              {{ index + 1 }}
            </div>
          </div>

          <!-- Step Label -->
          <div class="text-center max-w-[100px]">
            <p class="text-xs font-semibold" :class="{
              'text-green-600': step.status === 'completed',
              'text-primary-600': step.status === 'current',
              'text-red-600': step.status === 'rejected',
              'text-secondary-400': step.status === 'pending'
            }">
              {{ step.label }}
            </p>
            <p v-if="step.status === 'current'" class="text-xs text-primary-500 mt-0.5">
              Proses
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Step Detail (Non-compact) -->
    <div v-if="!compact" class="mt-6">
      <div
        v-for="step in steps"
        :key="step.id"
        v-show="step.status === 'current' || step.status === 'completed'"
        class="p-4 rounded-lg border transition-all"
        :class="getStepClass(step)"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5" :class="getIconClass(step)">
            <i :class="step.icon + ' text-base'"></i>
          </div>
          <div class="flex-1">
            <p class="font-medium text-secondary-800">{{ step.label }}</p>
            <p class="text-sm text-secondary-600 mt-1">{{ step.description }}</p>
          </div>
          <div class="flex-shrink-0">
            <span v-if="step.status === 'completed'" class="badge badge-success text-xs">
              <i class="ri-check-line mr-1"></i>Selesai
            </span>
            <span v-else-if="step.status === 'current'" class="badge badge-primary text-xs">
              <i class="ri-time-line mr-1"></i>Sedang Diproses
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection Info -->
    <div
      v-if="!compact && pengajuan?.status === 'ditolak' && pengajuan?.catatan_tolak"
      class="mt-4 p-4 rounded-lg bg-red-50 border border-red-200"
    >
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
          <i class="ri-error-warning-line text-red-600 text-lg"></i>
        </div>
        <div>
          <p class="font-semibold text-red-800">Pengajuan Ditolak</p>
          <p class="text-sm text-red-600 mt-1">{{ pengajuan.catatan_tolak }}</p>
        </div>
      </div>
    </div>

    <!-- Success Info -->
    <div
      v-if="!compact && (pengajuan?.status === 'selesai' || pengajuan?.status === 'completed')"
      class="mt-4 p-4 rounded-lg bg-green-50 border border-green-200"
    >
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
          <i class="ri-checkbox-circle-line text-green-600 text-lg"></i>
        </div>
        <div>
          <p class="font-semibold text-green-800">Pengajuan Selesai!</p>
          <p class="text-sm text-green-600 mt-1">Surat izin belajar telah terbit dan siap diunduh.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pengajuan-milestone {
  position: relative;
}

/* Pulse animation for current step */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
  }
}

.animate-pulse {
  animation: pulse-glow 2s ease-in-out infinite;
}
</style>
