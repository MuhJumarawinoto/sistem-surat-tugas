<script setup>
import { computed, watch, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props for mobile menu
const props = defineProps({
  mobileMenuOpen: {
    type: Boolean,
    default: false
  }
})

// Emit event for close
const emit = defineEmits(['closeMobileMenu'])

// State for expanded sub menus
const expandedMenus = ref(new Set())

// Close mobile menu when route changes
watch(() => route.path, () => {
  if (props.mobileMenuOpen) {
    emit('closeMobileMenu')
  }
})

// Toggle sub menu expansion
function toggleMenu(menuId) {
  if (expandedMenus.value.has(menuId)) {
    expandedMenus.value.delete(menuId)
  } else {
    expandedMenus.value.add(menuId)
  }
  // Force reactivity by creating new Set
  expandedMenus.value = new Set(expandedMenus.value)
}

// Check if a menu has any active child
function hasActiveChild(children) {
  if (!children) return false
  return children.some(child => isActive(child.path))
}

const menuGroups = computed(() => {
  const groups = []
  const isPgaService = authStore.isPgaService

  // ========== MENU PEMOHON & ATASAN ==========
  if (authStore.isPemohon || authStore.isAtasan) {
    groups.push({
      title: 'Menu Utama',
      items: [
        { path: '/dashboard', label: 'Dashboard', icon: 'ri-dashboard-line' },
      ],
    })

    if (isPgaService) {
      groups.push({
        title: 'Pencantuman Gelar Akademik',
        items: [
          { path: '/pga', label: 'Riwayat PGA', icon: 'ri-graduation-cap-line' },
          { path: '/pga/baru', label: 'Buat Pengajuan Baru', icon: 'ri-add-circle-line' },
        ],
      })
    } else {
      groups.push({
        title: 'Pengajuan',
        items: [
          {
            path: '/pengajuan-menu',
            label: 'Pengajuan',
            icon: 'ri-file-list-3-line',
            children: [
              { path: '/pengajuan', label: 'Riwayat Pengajuan', icon: 'ri-history-line' },
              { path: '/pengajuan/baru', label: 'Buat Pengajuan Baru', icon: 'ri-add-circle-line' },
            ]
          },
        ],
      })
    }
  }

  // ========== MENU ADMIN BKPSDM ==========
  if (authStore.isAdmin) {
    if (isPgaService) {
      groups.push({
        title: 'Verifikasi PGA',
        items: [
          { path: '/admin/pga-verifikasi', label: 'Verifikasi PGA', icon: 'ri-verified-badge-line' },
        ],
      })
    } else {
      groups.push({
        title: 'Verifikasi',
        items: [
          {
            path: '/admin-verifikasi-menu',
            label: 'Verifikasi',
            icon: 'ri-verified-badge-line',
            children: [
              { path: '/admin/verifikasi', label: 'Verifikasi Dokumen', icon: 'ri-file-search-line' },
              { path: '/admin/riwayat-verifikasi', label: 'Riwayat Verifikasi', icon: 'ri-history-line' },
            ]
          },
        ],
      })

      groups.push({
        title: 'Manajemen Surat',
        items: [
          {
            path: '/admin-surat-menu',
            label: 'Surat',
            icon: 'ri-file-list-line',
            children: [
              { path: '/admin/surat-tugas', label: 'Surat Tugas Belajar', icon: 'ri-file-text-line' },
            ]
          },
        ],
      })
    }

    groups.push({
      title: 'Manajemen Data',
      items: [
        { path: '/admin/pegawai', label: 'Data Pegawai', icon: 'ri-team-line' },
      ],
    })

    groups.push({
      title: 'Master Data',
      items: [
        {
          path: '/admin-master-menu',
          label: 'Master Data',
          icon: 'ri-database-2-line',
          children: [
            { path: '/admin/jenis-dokumen', label: 'Jenis Dokumen', icon: 'ri-file-list-3-line' },
            { path: '/admin/jenis-dokumen-pga', label: 'Jenis Dokumen PGA', icon: 'ri-file-list-line' },
            { path: '/admin/pddikti-sync', label: 'Sync PDDikti', icon: 'ri-refresh-line' },
          ]
        },
      ],
    })
  }

  // ========== MENU KEPALA ==========
  if (authStore.isKepala) {
    if (isPgaService) {
      groups.push({
        title: 'Pencantuman Gelar Akademik',
        items: [
          { path: '/pga', label: 'Riwayat PGA', icon: 'ri-graduation-cap-line' },
          { path: '/pga/baru', label: 'Buat Pengajuan Baru', icon: 'ri-add-circle-line' },
        ],
      })
    } else {
      groups.push({
        title: 'Tanda Tangan',
        items: [
          {
            path: '/kepala-tte-menu',
            label: 'Tanda Tangan',
            icon: 'ri-edit-sign-line',
            children: [
              { path: '/kepala/signing', label: 'Surat Perlu TTE', icon: 'ri-file-sign-line' },
              { path: '/kepala/riwayat', label: 'Riwayat TTE', icon: 'ri-history-line' },
            ]
          },
        ],
      })

      if (authStore.user?.is_kepala_unit) {
        groups.push({
          title: 'Surat Tugas',
          items: [
            { path: '/kepala/surat-tugas', label: 'Surat Tugas Belajar', icon: 'ri-file-list-line' },
          ],
        })
      }
    }
  }

  return groups
})

// Auto-expand menu if it has active child
watch(() => route.path, () => {
  // Check all menu groups for active children
  menuGroups.value.forEach(group => {
    group.items.forEach(item => {
      if (item.children && hasActiveChild(item.children)) {
        const menuId = item.path || item.label
        if (!expandedMenus.value.has(menuId)) {
          expandedMenus.value.add(menuId)
        }
      }
    })
  })
}, { immediate: true })

function isActive(path) {
  if (!path) return false

  if (path === '/dashboard') {
    return route.path === '/dashboard'
  }

  if (path === '/pga') {
    return route.path === '/pga'
  }
  if (path === '/pga/baru') {
    return route.path === '/pga/baru' || (route.path.startsWith('/pga/') && route.path !== '/pga')
  }
  if (path === '/admin/pga-verifikasi') {
    return route.path === '/admin/pga-verifikasi' || route.path.startsWith('/admin/pga-verifikasi/')
  }

  if (path === '/admin/verifikasi') {
    return route.path === '/admin/verifikasi' || (route.path.startsWith('/admin/verifikasi/') && !route.path.startsWith('/admin/riwayat'))
  }
  if (path === '/admin/riwayat-verifikasi') {
    return route.path === '/admin/riwayat-verifikasi' || route.path.startsWith('/admin/riwayat-verifikasi/')
  }

  if (path === '/admin/surat-tugas') {
    return route.path === '/admin/surat-tugas' || route.path.startsWith('/admin/surat-tugas/')
  }

  if (path.startsWith('/kepala/surat-tugas')) {
    return route.path.startsWith('/kepala/surat-tugas')
  }
  if (path.startsWith('/kepala/signing')) {
    return route.path.startsWith('/kepala/signing')
  }
  if (path.startsWith('/kepala/riwayat')) {
    return route.path.startsWith('/kepala/riwayat')
  }

  if (path === '/pengajuan') {
    return route.path === '/pengajuan'
  }
  if (path === '/pengajuan/baru') {
    return route.path === '/pengajuan/baru' || route.path.startsWith('/pengajuan/')
  }

  // For menu items (not actual paths), check if any child is active
  if (path.includes('-menu')) {
    return false
  }

  return route.path === path || route.path.startsWith(path + '/')
}

// Check if parent menu should be highlighted (has active child)
function isParentActive(item) {
  if (item.children) {
    return hasActiveChild(item.children)
  }
  return isActive(item.path)
}

// Sub menu transition functions
function enterSubmenu(el) {
  el.style.height = '0'
  el.style.overflow = 'hidden'

  requestAnimationFrame(() => {
    el.style.height = el.scrollHeight + 'px'
    el.style.opacity = '1'
    el.style.transform = 'translateY(0)'
  })

  setTimeout(() => {
    el.style.height = ''
    el.style.overflow = ''
  }, 250)
}

function leaveSubmenu(el) {
  el.style.height = el.scrollHeight + 'px'
  el.style.overflow = 'hidden'

  requestAnimationFrame(() => {
    el.style.height = '0'
    el.style.opacity = '0'
    el.style.transform = 'translateY(-8px)'
  })
}
</script>

<template>
  <!-- Sidebar -->
  <aside
    class="sidebar bg-white"
    :class="mobileMenuOpen ? 'open' : 'closed'"
  >
    <!-- Close Button (Mobile Only) -->
    <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-secondary-200">
      <span class="font-semibold text-secondary-800">Menu</span>
      <button
        @click="emit('closeMobileMenu')"
        class="btn btn-ghost btn-icon text-secondary-600 hover:text-secondary-800"
        aria-label="Close menu"
      >
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
      <div v-for="(group, groupIndex) in menuGroups" :key="groupIndex" class="mb-6">
        <p v-if="group.title" class="px-3 mb-2 text-xs font-semibold text-secondary-400 uppercase tracking-wider">
          {{ group.title }}
        </p>
        <div class="space-y-1">
          <template v-for="item in group.items" :key="item.path || item.label">
            <!-- Parent Menu Item with Children -->
            <div v-if="item.children" class="menu-item-wrapper">
              <button
                @click="toggleMenu(item.path || item.label)"
                class="nav-link nav-link-parent w-full"
                :class="{ 'active': isParentActive(item) }"
              >
                <div class="flex items-center gap-0.75 flex-1">
                  <i :class="item.icon" class="text-lg"></i>
                  <span>{{ item.label }}</span>
                </div>
                <i
                  class="ri-arrow-down-s-line transition-transform duration-200 chevron"
                  :class="{ 'rotate-180': expandedMenus.has(item.path || item.label) }"
                ></i>
              </button>

              <!-- Nested Sub Menu Items -->
              <Transition
                name="submenu"
                @enter="enterSubmenu"
                @leave="leaveSubmenu"
              >
                <div
                  v-show="expandedMenus.has(item.path || item.label)"
                  class="submenu-wrapper"
                >
                  <router-link
                    v-for="child in item.children"
                    :key="child.path"
                    :to="child.path"
                    class="nav-link nav-link-child"
                    :class="{ 'active': isActive(child.path) }"
                    @click="emit('closeMobileMenu')"
                  >
                    <i :class="child.icon" class="text-lg"></i>
                    <span>{{ child.label }}</span>
                  </router-link>
                </div>
              </Transition>
            </div>

            <!-- Regular Menu Item without Children -->
            <router-link
              v-else
              :to="item.path"
              class="nav-link"
              :class="{ 'active': isActive(item.path) }"
              @click="emit('closeMobileMenu')"
            >
              <i :class="item.icon" class="text-lg"></i>
              <span>{{ item.label }}</span>
            </router-link>
          </template>
        </div>
      </div>

      <!-- Sidebar Footer -->
      <div class="mt-auto pt-4 border-t border-secondary-200 px-3">
        <div class="p-3 rounded-lg bg-gradient-to-br from-primary-50 to-accent/10 border border-primary-200">
          <div class="flex items-center gap-2 text-xs text-primary-700">
            <i class="ri-shield-check-line"></i>
            <span class="font-medium">Sistem Aman</span>
          </div>
          <p class="text-xs text-primary-600 mt-1">Terhubung dengan SIMPEG</p>
        </div>
      </div>
    </nav>
  </aside>
</template>

<style scoped>
/* ============================================
   SIDEBAR BASE STYLES
   ============================================ */
.sidebar {
  display: flex;
  flex-direction: column;
  background: white;
  border-right: 1px solid #e5e7eb;
  flex-shrink: 0;
  transition: transform 0.3s ease-in-out;
  height: auto;
}

/* Sidebar Navigation - scrollable area */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1rem;
}

/* Scrollbar styling */
.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* ============================================
   MOBILE (< 1024px)
   ============================================ */
@media (max-width: 1023px) {
  .sidebar {
    position: fixed;
    top: 64px;
    left: 0;
    bottom: 0;
    width: 280px;
    max-width: 85vw;
    height: auto;
    border-right: none;
    z-index: 40;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
  }

  .sidebar.closed {
    transform: translateX(-100%);
  }

  .sidebar.open {
    transform: translateX(0);
  }
}

/* ============================================
   DESKTOP (>= 1024px)
   ============================================ */
@media (min-width: 1024px) {
  .sidebar {
    position: sticky;
    top: 64px;
    width: 260px;
    min-width: 260px;
    height: calc(100vh - 64px);
    max-height: calc(100vh - 64px);
    transform: none !important;
  }

  .sidebar.open,
  .sidebar.closed {
    transform: none;
  }
}

/* ============================================
   NAV LINK STYLES
   ============================================ */
.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  border-radius: 0.5rem;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s;
  font-size: 0.875rem;
  font-weight: 500;
}

.nav-link:hover {
  background-color: #f1f5f9;
  color: #475569;
}

.nav-link.active {
  background: linear-gradient(135deg, #857800 0%, #A39700 50%, #E8D800 100%);
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(133, 120, 0, 0.3);
}

.nav-link.active i {
  color: white;
}

/* Parent menu item with chevron */
.nav-link-parent {
  cursor: pointer;
  user-select: none;
}

.nav-link-parent .chevron {
  margin-left: auto;
  transition: transform 0.2s;
}

.nav-link-parent .chevron.rotate-180 {
  transform: rotate(180deg);
}

/* ============================================
   NESTED SUB MENU STYLES
   ============================================ */
.menu-item-wrapper {
  display: flex;
  flex-direction: column;
}

.submenu-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding-left: 2.25rem;
  margin-top: 0.25rem;
  overflow: hidden;
}

.nav-link-child {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  position: relative;
}

.nav-link-child::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: #cbd5e1;
  transition: background-color 0.2s;
}

.nav-link-child:hover::before {
  background-color: #94a3b8;
}

.nav-link-child.active::before {
  background-color: white;
}

/* ============================================
   SUB MENU TRANSITION
   ============================================ */
.submenu-enter-active,
.submenu-leave-active {
  transition: all 0.25s ease;
}

.submenu-enter-from,
.submenu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
