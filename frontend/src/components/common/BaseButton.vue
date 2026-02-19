<script setup lang="ts">
interface Props {
  text: string
  variant?: 'primary'
  action?: () => void
  extraProps?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  action: () => {},
  extraProps: () => ({})
})

const handleClick = () => {
  if (props.extraProps?.disabled || props.extraProps?.loading) return
  props.action()
}
</script>

<template>
  <button
    v-bind="extraProps"
    @click="handleClick"
    :type="extraProps?.type || 'button'"
    :class="[
      'cursor-pointer w-full py-5 rounded-2xl font-black text-xl transition-all transform active:scale-[0.98] flex items-center justify-center shadow-2xl',
      variant === 'primary' ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white hover:from-indigo-400 hover:to-purple-500 hover:scale-[1.02]' : '',
      extraProps?.disabled || extraProps?.loading ? 'opacity-30 cursor-not-allowed scale-100' : ''
    ]"
    :disabled="extraProps?.disabled || extraProps?.loading"
  >
    <span v-if="!extraProps?.loading">{{ text }}</span>
    <span v-else class="flex items-center">
      <svg class="animate-spin -ml-1 mr-3 h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      {{ extraProps?.loadingText || text }}
    </span>
  </button>
</template>
