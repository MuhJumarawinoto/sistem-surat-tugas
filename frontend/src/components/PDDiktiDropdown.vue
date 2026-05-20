<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api'

const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: ''
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['universitas', 'prodi'].includes(value)
  },
  placeholder: {
    type: String,
    default: 'Pilih dari PDDikti'
  },
  idPt: {
    type: String,
    default: null // Required for prodi type
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')
const results = ref([])
const loading = ref(false)
const useCustom = ref(false)
const customValue = ref('')
const hasSearched = ref(false)
const apiError = ref('')

const displayValue = computed(() => {
  if (useCustom.value) {
    return customValue.value
  }
  if (typeof props.modelValue === 'object' && props.modelValue) {
    return props.modelValue.nama_pt || props.modelValue.nama_prodi || ''
  }
  return props.modelValue || ''
})

const showCustomInput = computed(() => {
  return useCustom.value || (!results.value.length && !loading.value && searchQuery.value)
})

let searchTimeout = null
const hasLoadedOnce = ref(false)

async function fetchProdiList() {
  if (props.type !== 'prodi' || !props.idPt || hasLoadedOnce.value) {
    return
  }

  loading.value = true
  try {
    const endpoint = `/pddikti/universitas/${props.idPt}/prodi`
    const response = await api.get(endpoint)
    results.value = response.data.data || []
    hasLoadedOnce.value = true
  } catch (error) {
    console.error('Fetch prodi list error:', error)
    results.value = []

    // If API fails, switch to manual mode
    if (error.response?.status >= 500 || !error.response) {
      useCustom.value = true
      apiError.value = 'API PDDikti sedang tidak bisa diakses. Gunakan input manual.'
      setTimeout(() => {
        apiError.value = ''
      }, 5000)
    }
  } finally {
    loading.value = false
  }
}

async function handleSearch() {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    results.value = []
    return
  }

  loading.value = true
  isOpen.value = true
  hasSearched.value = true

  try {
    let endpoint = '/pddikti/search'
    const params = { keyword: searchQuery.value }

    if (props.type === 'universitas') {
      endpoint = '/pddikti/universitas'
    } else if (props.type === 'prodi') {
      if (props.idPt) {
        // Get prodi by universitas
        endpoint = `/pddikti/universitas/${props.idPt}/prodi`
      } else {
        endpoint = '/pddikti/prodi'
      }
    }

    const response = await api.get(endpoint, { params })
    results.value = response.data.data || []
  } catch (error) {
    console.error('Search error:', error)
    results.value = []

    // If API fails, switch to manual mode automatically
    if (error.response?.status >= 500 || !error.response) {
      // Auto switch to manual mode
      useCustom.value = true
      customValue.value = searchQuery.value
      isOpen.value = false

      // Show message in the placeholder
      apiError.value = 'API PDDikti sedang tidak bisa diakses. Gunakan input manual.'
      setTimeout(() => {
        apiError.value = ''
      }, 5000)
    }
  } finally {
    loading.value = false
  }
}

// Reset hasSearched when search query changes
watch(searchQuery, () => {
  hasSearched.value = false
})

function handleKeyPress(event) {
  if (event.key === 'Enter') {
    event.preventDefault()
    handleSearch()
  }
}

function selectItem(item) {
  useCustom.value = false
  emit('update:modelValue', item)
  isOpen.value = false
  searchQuery.value = ''
  results.value = []
  hasSearched.value = false
}

function selectCustom() {
  if (!customValue.value.trim()) return

  useCustom.value = true
  emit('update:modelValue', customValue.value)
  isOpen.value = false
}

async function handleInputClick() {
  if (!props.disabled) {
    isOpen.value = true
    // Auto-load prodi list when dropdown opens
    if (props.type === 'prodi' && props.idPt && !hasLoadedOnce.value) {
      await fetchProdiList()
    }
  }
}

function handleClickOutside() {
  setTimeout(() => {
    isOpen.value = false
  }, 200)
}

function toggleCustomMode() {
  useCustom.value = !useCustom.value
  if (useCustom.value) {
    // Switching to custom mode
    if (typeof props.modelValue === 'string') {
      customValue.value = props.modelValue
    }
    emit('update:modelValue', customValue.value)
  } else {
    // Switching to PDDikti mode
    customValue.value = ''
    emit('update:modelValue', '')
  }
}

// Watch for external value changes
watch(() => props.modelValue, (newValue) => {
  if (typeof newValue === 'string' && newValue && !useCustom.value) {
    customValue.value = newValue
  }
})

// Watch for idPt changes (for prodi type)
watch(() => props.idPt, () => {
  // Reset loaded state when PT changes
  if (props.type === 'prodi') {
    hasLoadedOnce.value = false
    results.value = []
  }
})
</script>

<template>
  <div class="relative" v-click-outside="handleClickOutside">
    <!-- Mode Toggle -->
    <div class="flex items-center gap-2 mb-1">
      <button
        type="button"
        @click="toggleCustomMode"
        class="text-xs text-blue-600 hover:text-blue-800"
        title="Switch mode"
      >
        {{ useCustom ? 'Gunakan PDDikti' : 'Input Manual' }}
      </button>
      <span v-if="!useCustom" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
        PDDikti
      </span>
    </div>

    <!-- Custom Input Mode -->
    <div v-if="useCustom">
      <input
        v-model="customValue"
        @input="emit('update:modelValue', $event.target.value)"
        type="text"
        :placeholder="placeholder"
        :disabled="disabled"
        class="input-field py-2"
      />
      <p class="text-xs mt-1" :class="apiError ? 'text-amber-600' : 'text-gray-500'">
        {{ apiError || `Masukkan nama ${type === 'universitas' ? 'perguruan tinggi' : 'program studi'} secara manual` }}
      </p>
    </div>

    <!-- PDDikti Search Mode -->
    <div v-else>
      <div class="flex gap-1">
        <div class="relative flex-1">
          <input
            v-model="searchQuery"
            @keypress="handleKeyPress"
            @focus="handleInputClick"
            type="text"
            :placeholder="loading ? 'Memuat data...' : placeholder"
            :disabled="disabled || loading"
            :class="{ 'bg-gray-50': loading }"
            class="input-field py-2 pr-10"
          />

          <!-- Search Icon / Loading -->
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg v-if="!loading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <svg v-else class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>

        <!-- Search Button -->
        <button
          type="button"
          @click="handleSearch"
          :disabled="disabled || loading || searchQuery.length < 2"
          class="btn-primary px-3 py-2 text-sm"
          :class="{ 'opacity-50 cursor-not-allowed': disabled || loading || searchQuery.length < 2 }"
        >
          <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </button>
      </div>

      <!-- Selected Value Display -->
      <div v-if="displayValue && !isOpen" class="mt-1">
        <div class="flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded">
          <span class="text-sm text-blue-900">
            {{ typeof modelValue === 'object' ? (modelValue.nama_pt || modelValue.nama_prodi) : modelValue }}
          </span>
          <button
            type="button"
            @click="emit('update:modelValue', ''); searchQuery = ''; displayValue = ''"
            class="text-blue-600 hover:text-blue-800"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Dropdown Results -->
      <div
        v-if="isOpen"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      >
        <!-- Loading -->
        <div v-if="loading" class="p-3 text-center text-gray-500 text-sm">
          <div class="flex items-center justify-center space-x-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ searchQuery ? 'Mencari di PDDikti...' : 'Memuat daftar prodi...' }}</span>
          </div>
        </div>

        <!-- No Results (after search) -->
        <div v-else-if="!loading && hasSearched && results.length === 0" class="p-3 text-center text-gray-500 text-sm">
          <p>Tidak ditemukan di PDDikti</p>
          <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain</p>
        </div>

        <!-- Initial State -->
        <div v-else-if="!loading && !hasSearched" class="p-3 text-center text-gray-500 text-sm">
          <p v-if="type === 'prodi' && idPt && results.length === 0">Klik tombol cari untuk melihat daftar prodi</p>
          <p v-else>Ketik minimal 2 karakter, lalu tekan Enter atau klik tombol Cari</p>
          <p class="text-xs text-gray-400 mt-1">Data dari PDDikti Kemdikbud</p>
        </div>

        <!-- Results List -->
        <div v-else-if="results.length > 0" class="py-1">
          <div
            v-for="(item, index) in results"
            :key="index"
            @click="selectItem(item)"
            class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
          >
            <p class="font-medium text-gray-900">
              {{ type === 'universitas' ? item.nama_pt : item.nama_prodi }}
            </p>
            <p class="text-xs text-gray-500">
              <template v-if="type === 'universitas'">
                {{ item.nama_singkat }} - {{ item.kode_pt }}
              </template>
              <template v-else>
                {{ item.jenjang }} - {{ item.nama_pt }}
              </template>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
input[type="text"] {
  width: 100%;
}
</style>
