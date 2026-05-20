<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  pengajuanId: {
    type: [String, Number],
    required: true
  },
  pemohonName: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'sent'])

const message = ref('')
const messageType = ref('info')
const sending = ref(false)
const error = ref('')

const typeOptions = [
  { value: 'info', label: 'Info', color: 'text-blue-600' },
  { value: 'warning', label: 'Peringatan', color: 'text-yellow-600' },
  { value: 'success', label: 'Sukses', color: 'text-green-600' },
  { value: 'error', label: 'Penting', color: 'text-red-600' }
]

watch(() => props.show, (newVal) => {
  if (newVal) {
    message.value = ''
    messageType.value = 'info'
    error.value = ''
  }
})

async function sendMessage() {
  if (!message.value.trim()) {
    error.value = 'Pesan tidak boleh kosong'
    return
  }

  if (message.value.length > 500) {
    error.value = 'Pesan maksimal 500 karakter'
    return
  }

  sending.value = true
  error.value = ''

  try {
    const api = (await import('@/services/api')).default
    await api.post(`/pengajuan/${props.pengajuanId}/send-notification`, {
      message: message.value,
      type: messageType.value
    })

    emit('sent')
    emit('close')
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mengirim pesan'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-4">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-bold text-gray-900">Kirim Pesan ke Pemohon</h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="pemohonName" class="mb-3 p-2 bg-gray-50 rounded text-xs text-gray-600">
            <span class="font-medium">Kepada:</span> {{ pemohonName }}
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Tipe Pesan</label>
              <div class="flex space-x-2">
                <button
                  v-for="opt in typeOptions"
                  :key="opt.value"
                  @click="messageType = opt.value"
                  :class="[
                    'flex-1 py-1.5 px-2 text-xs rounded border transition-colors',
                    messageType === opt.value
                      ? 'border-blue-500 bg-blue-50 text-blue-600'
                      : 'border-gray-300 text-gray-600 hover:border-gray-400'
                  ]"
                >
                  {{ opt.label }}
                </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Pesan</label>
              <textarea
                v-model="message"
                rows="4"
                maxlength="500"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                placeholder="Tulis pesan untuk pemohon..."
                :disabled="sending"
              ></textarea>
              <div class="flex justify-between mt-1">
                <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
                <span class="text-xs text-gray-400 ml-auto">{{ message.length }}/500</span>
              </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2">
              <button
                @click="$emit('close')"
                :disabled="sending"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50"
              >
                Batal
              </button>
              <button
                @click="sendMessage"
                :disabled="sending || !message.trim()"
                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center space-x-1"
              >
                <svg v-if="sending" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ sending ? 'Mengirim...' : 'Kirim Pesan' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
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

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
}
</style>
