<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  currentPage: {
    type: String,
    default: ''
  }
})

const route = useRoute()

// Generate breadcrumbs from route if not provided
const breadcrumbs = computed(() => {
  if (props.items.length > 0) {
    return props.items
  }

  // Default breadcrumbs based on route
  const pathSegments = route.path.split('/').filter(Boolean)
  const crumbs = []

  // Always add home
  crumbs.push({ label: 'Beranda', to: '/dashboard' })

  // Build from path
  let currentPath = ''
  const routeMap = {
    'dashboard': { label: 'Dashboard', to: '/dashboard' },
    'pengajuan': { label: 'Pengajuan', to: '/pengajuan' },
    'baru': { label: 'Buat Pengajuan', to: '/pengajuan/baru' },
    'edit': { label: 'Edit', to: null },
    'atasan': { label: 'Atasan', to: null },
    'persetujuan': { label: 'Persetujuan', to: '/atasan/persetujuan' },
    'admin': { label: 'Admin', to: null },
    'verifikasi': { label: 'Verifikasi', to: '/admin/verifikasi' },
    'surat': { label: 'Surat', to: null },
    'signing': { label: 'Tanda Tangan', to: '/kepala/signing' },
    'kepala': { label: 'Kepala BKPSDM', to: null },
  }

  pathSegments.forEach((segment, index) => {
    currentPath += '/' + segment
    const routeInfo = routeMap[segment]

    if (routeInfo) {
      if (routeInfo.to) {
        crumbs.push({ label: routeInfo.label, to: routeInfo.to })
      } else if (index === pathSegments.length - 1) {
        // Last segment without to becomes current page
        // Skip adding as it will be shown as current page
      } else {
        crumbs.push({ label: routeInfo.label, to: currentPath })
      }
    } else if (segment.match(/^\d+$/)) {
      // ID parameter
      crumbs.push({ label: 'Detail', to: currentPath })
    }
  })

  return crumbs
})

const displayCurrentPage = computed(() => {
  return props.currentPage || route.meta?.title || getCurrentPageTitle()
})

function getCurrentPageTitle() {
  const pathSegments = route.path.split('/').filter(Boolean)
  const lastSegment = pathSegments[pathSegments.length - 1]

  const titleMap = {
    'dashboard': 'Dashboard',
    'pengajuan': 'Riwayat Pengajuan',
    'baru': 'Buat Pengajuan Baru',
    'persetujuan': 'Persetujuan Pengajuan',
    'verifikasi': 'Verifikasi Pengajuan',
    'signing': 'Tanda Tangan Surat',
  }

  if (lastSegment?.match(/^\d+$/)) {
    return 'Detail Pengajuan'
  }

  return titleMap[lastSegment] || 'Halaman'
}
</script>

<template>
  <nav class="flex mb-2" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 text-xs">
      <!-- Breadcrumb Items -->
      <li v-for="(item, index) in breadcrumbs" :key="index" class="flex items-center">
        <!-- Chevron Separator -->
        <svg v-if="index > 0" class="w-3 h-3 text-gray-400 mx-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>

        <!-- Breadcrumb Link -->
        <router-link
          v-if="item.to && index < breadcrumbs.length - 1"
          :to="item.to"
          class="text-blue-600 hover:text-blue-800 font-medium transition-colors"
        >
          {{ item.label }}
        </router-link>

        <!-- Current Page -->
        <span v-else class="text-gray-500 font-medium">
          {{ item.label }}
        </span>
      </li>

      <!-- Current Page (if not already shown in breadcrumbs) -->
      <li v-if="displayCurrentPage && (!breadcrumbs.length || breadcrumbs[breadcrumbs.length - 1]?.label !== displayCurrentPage)">
        <svg class="w-3 h-3 text-gray-400 mx-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="text-gray-500 font-medium">{{ displayCurrentPage }}</span>
      </li>
    </ol>
  </nav>
</template>
