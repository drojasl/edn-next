<script setup lang="ts">
import { ref } from 'vue'

export type ToastType = 'error' | 'success'

interface ToastState {
  show: boolean
  message: string
  type: ToastType
}

const state = ref<ToastState>({
  show: false,
  message: '',
  type: 'error',
})

const timeout = ref<number | null>(null)
const progressWidth = ref(100)

const show = (message: string, type: ToastType = 'error') => {
  if (timeout.value) clearTimeout(timeout.value)

  // If already showing, just update content and reset timer
  // This avoids glitchy transitions when multiple toasts are fired
  if (state.value.show) {
    state.value.message = message
    state.value.type = type
    progressWidth.value = 100

    // Reset progress bar animation
    setTimeout(() => {
      progressWidth.value = 0
    }, 10)
  } else {
    progressWidth.value = 100
    state.value = { show: true, message, type }

    setTimeout(() => {
      progressWidth.value = 0
    }, 10)
  }

  timeout.value = window.setTimeout(() => {
    state.value.show = false
  }, 4000)
}

defineExpose({ show })
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transform transition ease-out duration-300"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="state.show"
        class="fixed top-4 right-4 z-[99999] pointer-events-none sm:top-5 sm:right-5"
      >
        <div
          class="pointer-events-auto w-full min-w-[320px] max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg
                  v-if="state.type === 'success'"
                  class="h-6 w-6 text-green-500"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <!-- Error Icon -->
                <svg
                  v-else
                  class="h-6 w-6 text-red-500"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                  />
                </svg>
              </div>
              <div class="ml-3 flex-1 pt-0.5">
                <p class="text-sm font-bold text-slate-900">
                  {{
                    state.type === 'success'
                      ? $t('toast.success_title')
                      : $t('toast.error_title')
                  }}
                </p>
                <p
                  class="mt-1 text-sm font-medium text-slate-500 leading-relaxed"
                >
                  {{ state.message }}
                </p>
              </div>
              <div class="ml-4 flex flex-shrink-0">
                <button
                  type="button"
                  class="inline-flex rounded-lg bg-white text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all p-1"
                  @click="state.show = false"
                >
                  <span class="sr-only">{{ $t('common.cancel') }}</span>
                  <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path
                      d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <!-- Animated Progress Bar -->
          <div class="h-1 w-full bg-slate-50">
            <div
              class="h-full transition-all duration-[4000ms] ease-linear"
              :class="state.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
              :style="{ width: progressWidth + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
