<script setup lang="ts">
import {
  BaseEdge,
  EdgeLabelRenderer,
  getBezierPath,
  useVueFlow,
  type EdgeProps,
} from '@vue-flow/core'
import { computed, ref, watch } from 'vue'

const props = defineProps<EdgeProps>()
const emit = defineEmits(['label-change'])

const { removeEdges } = useVueFlow()

const path = computed(() => getBezierPath(props))

const onRemove = () => {
  removeEdges([props.id])
}

// Editable label logic
const inputValue = ref(props.label || '')

// Sync with prop changes (e.g. from server)
watch(
  () => props.label,
  (newVal) => {
    if (
      newVal !== inputValue.value &&
      document.activeElement !== inputRef.value
    ) {
      inputValue.value = newVal || ''
    }
  }
)

const handleInput = (e: Event) => {
  inputValue.value = (e.target as HTMLInputElement).value
}

const handleBlurOrEnter = () => {
  if (inputValue.value !== props.label) {
    emit('label-change', inputValue.value)
  }
}

const inputRef = ref<HTMLInputElement | null>(null)
</script>

<template>
  <!-- Use BaseEdge for the actual line -->
  <BaseEdge
    :id="props.id"
    :path="path[0]"
    :marker-end="props.markerEnd"
    :style="props.style"
    :class="[
      'vue-flow__edge-path transition-all duration-200',
      props.selected ? '!stroke-indigo-600 !stroke-[3px]' : 'stroke-slate-400',
    ]"
  />

  <!-- Use EdgeLabelRenderer for the label and delete button -->
  <EdgeLabelRenderer>
    <div
      :style="{
        position: 'absolute',
        transform: `translate(-50%, -50%) translate(${path[1]}px, ${path[2]}px)`,
        pointerEvents: 'all',
        zIndex: 1000,
      }"
      class="nodrag nopan flex flex-col items-center gap-2"
    >
      <!-- Editable Label -->
      <div
        class="bg-white px-2 py-0.5 rounded-full border border-slate-200 shadow-sm flex items-center gap-1 transition-all focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100"
      >
        <input
          ref="inputRef"
          type="text"
          :value="inputValue"
          :readonly="!!props.sourceHandleId"
          class="bg-transparent border-none outline-none text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-20 focus:text-indigo-600"
          :class="props.sourceHandleId ? 'cursor-default' : ''"
          :title="
            props.sourceHandleId ? 'Este texto proviene del botón del menú' : ''
          "
          placeholder="LABEL"
          @input="handleInput"
          @blur="handleBlurOrEnter"
          @keydown.enter="handleBlurOrEnter"
        />
      </div>

      <!-- Delete Button (only on selection) -->
      <button
        v-if="props.selected"
        class="w-6 h-6 bg-white border-2 border-red-500 text-red-500 rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-all transform hover:scale-110 shadow-md group"
        :title="$t('course.management.delete')"
        @click.stop="onRemove"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="2.5"
          stroke="currentColor"
          class="w-4 h-4"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </EdgeLabelRenderer>
</template>

<style scoped>
.vue-flow__edge-path {
  stroke-dasharray: 5;
  animation: dash 20s linear infinite;
}

@keyframes dash {
  from {
    stroke-dashoffset: 1000;
  }
  to {
    stroke-dashoffset: 0;
  }
}
</style>

<style>
/* Ensure edge labels and their delete buttons always appear above nodes */
.vue-flow__edge-labels {
  z-index: 10 !important;
}
</style>
