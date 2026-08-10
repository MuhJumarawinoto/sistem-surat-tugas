<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  actions: {
    type: Array,
    default: () => []
  },
  showBack: {
    type: Boolean,
    default: null // null = auto-detect
  },
  backTo: {
    type: [String, Object],
    default: null
  }
})

const router = useRouter()
const route = useRoute()

// Determine if we should show back button
const shouldShowBack = computed(() => {
  if (props.showBack !== null) return props.showBack

  // Auto-detect: show back button on detail/edit pages
  const pathSegments = route.path.split('/').filter(Boolean)
  const lastSegment = pathSegments[pathSegments.length - 1]

  // Show back for pages with ID (detail pages)
  if (lastSegment?.match(/^\d+$/)) return true

  // Show back for edit pages
  if (lastSegment === 'edit') return true

  // Don't show for main pages
  return false
})

// Determine back path
const backPath = computed(() => {
  if (props.backTo) return props.backTo

  const pathSegments = route.path.split('/').filter(Boolean)
  const lastSegment = pathSegments[pathSegments.length - 1]

  // For detail pages with ID
  if (lastSegment?.match(/^\d+$/)) {
    const prevSegment = pathSegments[pathSegments.length - 2]

    // Check if we came from dashboard (check navigation state or referrer)
    const fromDashboard = history.state?.from === 'dashboard'

    if (fromDashboard) {
      return '/dashboard'
    }

    // Default back path based on context
    if (prevSegment === 'pengajuan') {
      return '/pengajuan' // Back to riwayat
    }
    if (prevSegment === 'surat') {
      return '/admin/surat'
    }
  }

  // For edit pages
  if (lastSegment === 'edit') {
    const idSegment = pathSegments[pathSegments.length - 2]
    if (idSegment?.match(/^\d+$/)) {
      return `/pengajuan/${idSegment}` // Back to detail
    }
  }

  // Default back
  return '/dashboard'
})

const backLabel = computed(() => {
  const path = typeof backPath.value === 'string' ? backPath.value : backPath.value.path
  const labelMap = {
    '/dashboard': 'Dashboard',
    '/pengajuan': 'Riwayat',
    '/admin/surat': 'Daftar Surat',
    '/admin/verifikasi': 'Verifikasi',
  }
  return labelMap[path] || 'Kembali'
})

function goBack() {
  // Try to use router back first if we have history
  if (window.history.state?.back) {
    router.back()
  } else {
    router.push(backPath.value)
  }
}

// Auto-generate title based on route if not provided
const displayTitle = computed(() => {
  if (props.title) return props.title

  const pathSegments = route.path.split('/').filter(Boolean)
  const lastSegment = pathSegments[pathSegments.length - 1]

  const titleMap = {
    'dashboard': 'Dashboard',
    'pengajuan': 'Riwayat Pengajuan',
    'baru': 'Buat Pengajuan Baru',
    'edit': 'Edit Pengajuan',
    'verifikasi': 'Verifikasi Dokumen',
    'pegawai': 'Data Pegawai',
    'pddikti-sync': 'Sinkronisasi PDDikti',
    'signing': 'Tanda Tangan Surat',
    'profile': 'Profil Pengguna',
  }

  // Check for ID (detail page)
  if (lastSegment?.match(/^\d+$/)) {
    const prevSegment = pathSegments[pathSegments.length - 2]
    if (prevSegment === 'pengajuan') return 'Detail Pengajuan'
    if (prevSegment === 'surat') return 'Detail Surat'
    return 'Detail'
  }

  return titleMap[lastSegment] || 'Halaman'
})

const displaySubtitle = computed(() => {
  if (props.subtitle) return props.subtitle

  const subtitleMap = {
    '/dashboard': 'Selamat datang di Sistem Surat Surat Tugas Belajar Mandiri',
    '/pengajuan': 'Daftar pengajuan yang telah selesai diproses',
    '/pengajuan/baru': 'Isi formulir untuk mengajukan izin belajar mandiri',
    '/admin/verifikasi': 'Verifikasi dokumen pengajuan izin belajar',
    '/admin/pegawai': 'Kelola data pegawai dan atasan',
    '/admin/pddikti-sync': 'Sinkronkan data perguruan tinggi dari PDDikti',
    '/admin/surat': 'Daftar surat yang siap ditandatangani',
    '/kepala/signing': 'Daftar surat yang memerlukan tanda tangan elektronik',
    '/profile': 'Kelola informasi profil Anda',
  }

  // For dynamic routes (with ID)
  if (route.path.match(/\/pengajuan\/\d+/)) {
    return 'Informasi lengkap pengajuan izin belajar'
  }
  if (route.path.match(/\/pengajuan\/\d+\/edit/)) {
    return 'Perbarui data pengajuan izin belajar'
  }

  return subtitleMap[route.path] || ''
})

const hasActions = computed(() => {
  return props.actions && props.actions.length > 0
})
</script>

<template>
  <div class="mb-6 animate-fade-in">
    <!-- Back Button (only on detail pages) -->
    <div v-if="shouldShowBack" class="mb-3">
      <button
        @click="goBack"
        class="inline-flex items-center gap-1 text-sm text-secondary-600 hover:text-primary-600 transition-colors group"
      >
        <i class="ri-arrow-left-line text-lg group-hover:-translate-x-0.5 transition-transform"></i>
        <span>{{ backLabel }}</span>
      </button>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex-1">
        <h1 class="text-2xl font-bold text-secondary-800">{{ displayTitle }}</h1>
        <p v-if="displaySubtitle" class="text-secondary-500 mt-1">{{ displaySubtitle }}</p>
      </div>
      <div v-if="hasActions" class="flex items-center gap-2 sm:w-auto w-full justify-end">
        <template v-for="(action, index) in actions" :key="index">
          <!-- Badge (non-clickable status display) -->
          <span v-if="action.isBadge" :class="['badge', action.variant || 'badge-default']" class="flex items-center gap-1">
            <i v-if="action.icon" :class="action.icon"></i>
            <span>{{ action.label }}</span>
          </span>
          <!-- Router Link -->
          <router-link
            v-else-if="action.to"
            :to="action.to"
            :class="['btn', action.variant || 'btn-primary', 'gap-2']"
          >
            <i v-if="action.icon" :class="action.icon"></i>
            <span>{{ action.label }}</span>
          </router-link>
          <!-- Button -->
          <button
            v-else
            :class="['btn', action.variant || 'btn-primary', 'gap-2', { 'opacity-75 cursor-not-allowed': action.isLoading }]"
            @click="action.onClick"
            :disabled="action.isLoading"
          >
            <i v-if="action.isLoading" class="ri-loader-4-line animate-spin"></i>
            <i v-else-if="action.icon" :class="action.icon"></i>
            <span>{{ action.label }}</span>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
