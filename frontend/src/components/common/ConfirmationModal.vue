<script setup lang="ts">
import { ref } from 'vue'

export interface ModalConfig {
  title: string
  message: string
  isDestructive?: boolean
  confirmText?: string
  cancelText?: string
  onConfirm?: () => void | Promise<void>
}

const show = ref(false)
const config = ref<ModalConfig>({
  title: '',
  message: '',
  isDestructive: false,
  confirmText: '',
  cancelText: '',
})

const open = (newConfig: ModalConfig) => {
  config.value = {
    ...newConfig,
    confirmText: newConfig.confirmText,
    cancelText: newConfig.cancelText,
    isDestructive: newConfig.isDestructive || false,
  }
  show.value = true
}

const close = () => {
  show.value = false
}

const handleConfirm = async () => {
  if (config.value.onConfirm) {
    await config.value.onConfirm()
  }
  close()
}

defineExpose({ open, close })
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="ease-out duration-300 transition"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-200 transition"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[10000] overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
          @click="close"
        ></div>

        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
        >
          <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="show"
              class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100"
            >
              <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div
                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                    :class="
                      config.isDestructive
                        ? 'bg-red-100 text-red-600'
                        : 'bg-indigo-100 text-indigo-600'
                    "
                  >
                    <svg
                      v-if="config.isDestructive"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                      stroke="currentColor"
                      class="h-6 w-6"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                      />
                    </svg>
                    <svg
                      v-else
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                      stroke="currentColor"
                      class="h-6 w-6"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                      />
                    </svg>
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3
                      id="modal-title"
                      class="text-lg font-bold leading-6 text-slate-800"
                    >
                      {{ config.title }}
                    </h3>
                    <div
                      class="mt-2 text-sm text-slate-500 font-medium leading-relaxed"
                    >
                      {{ config.message }}
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 gap-2"
              >
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all sm:w-auto"
                  :class="
                    config.isDestructive
                      ? 'bg-red-600 hover:bg-red-700'
                      : 'bg-indigo-600 hover:bg-indigo-700'
                  "
                  @click="handleConfirm"
                >
                  {{ config.confirmText || $t('common.confirm') }}
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all sm:mt-0 sm:w-auto"
                  @click="close"
                >
                  {{ config.cancelText || $t('common.cancel') }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
