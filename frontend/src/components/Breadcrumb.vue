<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// Generate breadcrumbs from route
const breadcrumbs = computed(() => {
  const pathSegments = route.path.split('/').filter(Boolean)
  const crumbs = []

  // Always add home
  crumbs.push({ label: 'Beranda', to: '/dashboard', icon: 'ri-home-line' })

  const routeMap = {
    'dashboard': { label: 'Dashboard', icon: 'ri-dashboard-line' },
    'pengajuan': { label: 'Pengajuan', icon: 'ri-file-list-3-line' },
    'baru': { label: 'Buat Baru', icon: 'ri-add-circle-line' },
    'edit': { label: 'Edit', icon: 'ri-edit-line' },
    'profile': { label: 'Profil', icon: 'ri-user-line' },
    'admin': { label: 'Admin', icon: 'ri-admin-line' },
    'verifikasi': { label: 'Verifikasi', icon: 'ri-verified-badge-line' },
    'surat': { label: 'Surat', icon: 'ri-file-text-line' },
    'pegawai': { label: 'Pegawai', icon: 'ri-team-line' },
    'pddikti-sync': { label: 'Sync PDDikti', icon: 'ri-refresh-line' },
    'kepala': { label: 'Kepala', icon: 'ri-shield-line' },
    'signing': { label: 'Tanda Tangan', icon: 'ri-edit-sign-line' },
  }

  let accumulatedPath = ''

  pathSegments.forEach((segment, index) => {
    accumulatedPath += '/' + segment
    const isLast = index === pathSegments.length - 1
    const routeInfo = routeMap[segment]

    // Handle dynamic segments (IDs)
    if (segment.match(/^\d+$/)) {
      const prevSegment = pathSegments[index - 1]

      // Check if we came from dashboard
      const fromDashboard = history.state?.from === 'dashboard'

      if (prevSegment === 'pengajuan') {
        if (fromDashboard) {
          // From dashboard, don't show Riwayat in breadcrumb
          // Just show the detail as current
        } else {
          // From riwayat, show Riwayat
          crumbs.push({
            label: 'Riwayat',
            icon: 'ri-file-list-3-line',
            to: '/pengajuan'
          })
        }
        // Current page (detail) - don't add to breadcrumbs, will be shown as title
      } else if (prevSegment === 'surat') {
        crumbs.push({
          label: 'Surat',
          icon: 'ri-file-text-line',
          to: isLast ? null : accumulatedPath
        })
      }
    } else if (routeInfo) {
      // Skip 'baru' and 'edit' from breadcrumbs (they're actions, not pages)
      if (segment === 'baru' || segment === 'edit') {
        return
      }
      crumbs.push({
        label: routeInfo.label,
        icon: routeInfo.icon,
        to: isLast ? null : accumulatedPath
      })
    }
  })

  // Remove duplicates and filter out nulls
  return crumbs.filter((crumb, index, self) =>
    index === self.findIndex(c => c.label === crumb.label)
  )
})

const currentPageTitle = computed(() => {
  const pathSegments = route.path.split('/').filter(Boolean)
  const lastSegment = pathSegments[pathSegments.length - 1]

  if (lastSegment?.match(/^\d+$/)) {
    return 'Detail'
  }

  const titleMap = {
    'dashboard': 'Dashboard',
    'pengajuan': 'Riwayat Pengajuan',
    'baru': 'Buat Pengajuan Baru',
    'edit': 'Edit Pengajuan',
    'verifikasi': 'Verifikasi',
    'pegawai': 'Data Pegawai',
    'pddikti-sync': 'Sync PDDikti',
    'signing': 'Tanda Tangan',
    'profile': 'Profil',
  }

  return titleMap[lastSegment] || 'Halaman'
})
</script>

<template>
  <nav class="flex items-center mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1 text-sm">
      <!-- Breadcrumb Items -->
      <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
        <!-- Separator -->
        <span v-if="index > 0" class="mx-2 text-secondary-300">
          <i class="ri-arrow-right-s-line text-lg"></i>
        </span>

        <!-- Breadcrumb Link -->
        <component
          :is="crumb.to ? 'router-link' : 'span'"
          :to="crumb.to"
          class="flex items-center gap-1.5 transition-colors"
          :class="crumb.to || index === breadcrumbs.length - 1
            ? 'text-secondary-800 font-semibold'
            : 'text-secondary-500 hover:text-secondary-700'"
        >
          <i v-if="crumb.icon" :class="crumb.icon" class="text-base"></i>
          <span>{{ crumb.label }}</span>
        </component>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
/* Subtle animation for breadcrumb links */
a {
  position: relative;
}

a:hover::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 1px;
  background-color: currentColor;
  opacity: 0.3;
}
</style>
