<script setup>
import { ref } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

const progress1 = ref(0)
const progress2 = ref(45)
const progress3 = ref(78)

// Simulate progress
setInterval(() => {
  progress1.value = (progress1.value + 1) % 101
}, 100)

const types = [
  { name: 'Spin (Default)', type: 'spin', description: 'Spinner berputar classic' },
  { name: 'Progress Ring', type: 'progress', description: 'Lingkaran progress dengan persentase' },
  { name: 'Indeterminate Progress', type: 'progress', indeterminate: true, description: 'Progress animasi tak terbatas' },
  { name: 'Dots', type: 'dots', description: 'Titik-titik memantul' },
  { name: 'Pulse', type: 'pulse', description: 'Efek pulsing' }
]

const colors = ['blue', 'green', 'red', 'purple']
const sizes = ['sm', 'md', 'lg', 'xl']
</script>

<template>
  <div class="p-8 space-y-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Loading Spinner Demo</h1>
      <p class="text-gray-600 mb-8">Berbagai tipe animasi loading yang tersedia</p>

      <!-- Tipe Spinner -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Tipe Spinner</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="t in types" :key="t.name" class="p-4 border rounded-lg bg-white">
            <div class="flex flex-col items-center justify-center py-4">
              <LoadingSpinner
                :type="t.type"
                :indeterminate="t.indeterminate"
                :progress="t.type === 'progress' ? progress1 : undefined"
                :show-percent="t.type === 'progress'"
                size="lg"
              />
            </div>
            <p class="text-sm font-medium text-gray-900 text-center mt-2">{{ t.name }}</p>
            <p class="text-xs text-gray-500 text-center">{{ t.description }}</p>
          </div>
        </div>
      </div>

      <!-- Ukuran -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Ukuran</h2>
        <div class="flex items-end justify-center space-x-8 py-4">
          <div v-for="s in sizes" :key="s" class="flex flex-col items-center">
            <LoadingSpinner :size="s" type="spin" />
            <span class="text-xs text-gray-500 mt-2 capitalize">{{ s }}</span>
          </div>
        </div>
      </div>

      <!-- Warna -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Warna</h2>
        <div class="flex items-center justify-center space-x-6 py-4">
          <div v-for="c in colors" :key="c" class="flex flex-col items-center">
            <LoadingSpinner :color="c" type="progress" :progress="75" show-percent size="lg" />
            <span class="text-xs text-gray-500 mt-2 capitalize">{{ c }}</span>
          </div>
        </div>
      </div>

      <!-- Progress Examples -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Contoh Progress</h2>
        <div class="space-y-6">
          <div class="flex items-center space-x-4">
            <LoadingSpinner type="progress" :progress="progress1" show-percent size="md" color="blue" />
            <span class="text-sm text-gray-600">Upload Dokumen 1</span>
          </div>
          <div class="flex items-center space-x-4">
            <LoadingSpinner type="progress" :progress="progress2" show-percent size="md" color="green" />
            <span class="text-sm text-gray-600">Upload Dokumen 2</span>
          </div>
          <div class="flex items-center space-x-4">
            <LoadingSpinner type="progress" :progress="progress3" show-percent size="md" color="purple" />
            <span class="text-sm text-gray-600">Upload Dokumen 3</span>
          </div>
        </div>
      </div>

      <!-- With Text -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Dengan Teks</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 border rounded-lg bg-white flex items-center justify-center space-x-3">
            <LoadingSpinner type="dots" text="Memuat data..." color="blue" />
          </div>
          <div class="p-4 border rounded-lg bg-white flex items-center justify-center space-x-3">
            <LoadingSpinner type="pulse" text="Menyimpan..." color="purple" />
          </div>
        </div>
      </div>

      <!-- Indeterminate -->
      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Indeterminate (Loading Tak Terbatas)</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 border rounded-lg bg-white flex flex-col items-center">
            <LoadingSpinner type="progress" indeterminate size="lg" color="blue" />
            <span class="text-sm text-gray-600 mt-2">Memproses...</span>
          </div>
          <div class="p-4 border rounded-lg bg-white flex flex-col items-center">
            <LoadingSpinner type="dots" size="lg" color="green" />
            <span class="text-sm text-gray-600 mt-2">Memuat...</span>
          </div>
          <div class="p-4 border rounded-lg bg-white flex flex-col items-center">
            <LoadingSpinner type="pulse" size="lg" color="purple" />
            <span class="text-sm text-gray-600 mt-2">Menunggu...</span>
          </div>
        </div>
      </div>

      <!-- Usage Code -->
      <div class="card mt-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Penggunaan</h2>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
          <pre class="text-sm text-green-400"><code>&lt;!-- Spinner Default --&gt;
&lt;LoadingSpinner /&gt;

&lt;!-- Dengan Teks --&gt;
&lt;LoadingSpinner text="Memuat data..." /&gt;

&lt;!-- Progress Ring dengan Persentase --&gt;
&lt;LoadingSpinner
  type="progress"
  :progress="75"
  show-percent
  size="lg"
  color="blue"
/&gt;

&lt;!-- Indeterminate Progress --&gt;
&lt;LoadingSpinner
  type="progress"
  indeterminate
  text="Memproses..."
  size="xl"
/&gt;

&lt;!-- Dots --&gt;
&lt;LoadingSpinner type="dots" color="green" /&gt;

&lt;!-- Pulse --&gt;
&lt;LoadingSpinner type="pulse" color="purple" /&gt;</code></pre>
        </div>
      </div>
    </div>
  </div>
</template>
