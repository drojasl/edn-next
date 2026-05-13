<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  text: string
  variant?: 'primary'
  action?: () => void | Promise<unknown>
  extraProps?: {
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    loading?: boolean
    loadingText?: string
    [key: string]: unknown
  }
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  action: () => {},
  extraProps: () => ({}),
})

const internalLoading = ref(false)

const handleClick = async () => {
  if (
    props.extraProps?.disabled ||
    props.extraProps?.loading ||
    internalLoading.value
  )
    return

  internalLoading.value = true
  try {
    await props.action()
  } finally {
    internalLoading.value = false
  }
}
</script>

<template>
  <button
    v-bind="extraProps"
    :type="extraProps?.type || 'button'"
    :class="[
      'cursor-pointer w-full py-4 px-2 rounded-2xl font-black text-xl transition-all transform active:scale-[0.98] flex items-center justify-center shadow-2xl',
      variant === 'primary'
        ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white hover:from-indigo-400 hover:to-purple-500 hover:scale-[1.02]'
        : '',
      extraProps?.disabled || extraProps?.loading || internalLoading
        ? 'opacity-30 cursor-not-allowed scale-100'
        : '',
    ]"
    :disabled="extraProps?.disabled || extraProps?.loading || internalLoading"
    @click.stop="handleClick"
  >
    <span v-if="!(extraProps?.loading || internalLoading)">{{ text }}</span>
    <span v-else class="flex items-center">
      <svg
        class="animate-spin -ml-1 mr-3 h-6 w-6 text-white"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        ></circle>
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        ></path>
      </svg>
      {{ extraProps?.loadingText || text }}
    </span>
  </button>
</template>
