<script setup lang="ts">
import { Handle, Position, useVueFlow } from '@vue-flow/core'
import { computed, watch, onMounted } from 'vue'

const props = defineProps<{
  id: string
  data: {
    title: string
    description?: string
    type?: string // 'video', 'form', 'course', etc.
    isStart?: boolean
    isEnd?: boolean
    options?: { id: string; label: string }[] // For branching nodes
    content?: {
      description?: string
      buttons?: string[]
      [key: string]: any
    }
    // For Course nodes, we might just use a default output
    isCourse?: boolean
  }
  selected?: boolean
}>()

defineEmits(['action'])

const isCourse = computed(() => props.data?.isCourse)
const isStart = computed(() => props.data?.isStart)
const isEnd = computed(() => props.data?.isEnd)

const { updateNodeInternals } = useVueFlow()

// Ensure Vue Flow recalculates handles if the menu buttons change dynamically
watch(
  () => props.data?.content?.buttons,
  () => {
    // Need a slight pause for Vue DOM rendering and Vue Flow Handle mounting/measuring
    setTimeout(() => {
      updateNodeInternals([props.id])
    }, 50)
  },
  { deep: true }
)

onMounted(() => {
  if (props.data?.type === 'menu') {
    setTimeout(() => {
      updateNodeInternals([props.id])
    }, 50)
  }
})

// 22: const isCourse = computed(() => props.data?.isCourse)
// ...

// Dynamic class for border color
const borderColor = computed(() => {
  if (props.selected) return 'border-indigo-600 ring-2 ring-indigo-200'
  if (isStart.value) return 'border-green-500'
  if (isEnd.value) return 'border-red-500'
  return 'border-slate-200 hover:border-indigo-400'
})
</script>

<template>
  <div
    class="bg-white rounded-lg shadow-md border-2 w-64 transition-all duration-200"
    :class="borderColor"
  >
    <!-- Input Handle (Target) - All nodes can receive connections -->
    <Handle
      type="target"
      :position="Position.Left"
      class="!w-4 !h-4 !bg-slate-300 !border-2 !border-white hover:!bg-indigo-500 transition-colors"
    />

    <div class="p-4">
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="flex items-center gap-2 min-w-0">
          <div class="min-w-0">
            <h3
              class="font-bold text-slate-800 truncate text-sm"
              :title="data.title"
            >
              {{ data.title }}
            </h3>
            <div v-if="!isCourse" class="text-xs text-slate-500 capitalize">
              {{
                data.type
                  ? $t(`course.node.types.${data.type}`)
                  : $t('course.node.default_type')
              }}
            </div>
          </div>
        </div>

        <!-- Action Icons (Top Right) -->
        <div class="flex gap-0.5 shrink-0 -mt-1">
          <button
            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors group"
            :title="$t('course.management.edit')"
            @click.stop="$emit('action', { type: 'edit', id: props.id })"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke="currentColor"
              class="w-4 h-4 transition-transform group-hover:scale-110"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
              />
            </svg>
          </button>
          <button
            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors group"
            :title="$t('course.management.delete')"
            @click.stop="$emit('action', { type: 'delete', id: props.id })"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke="currentColor"
              class="w-4 h-4 transition-transform group-hover:scale-110"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Description for courses and info nodes -->
      <p
        v-if="data.description"
        class="text-xs text-slate-600 line-clamp-2 mt-1 mb-2 italic"
      >
        {{ data.description }}
      </p>

      <!-- Menu Buttons and Custom Handles -->
      <div
        v-if="data.type === 'menu' && data.content?.buttons?.length"
        class="mt-2 space-y-2 border-t border-slate-100 pt-3 pb-1 -mx-4 px-4"
      >
        <div
          v-for="(btn, idx) in data.content.buttons"
          :key="`${btn}-${idx}`"
          class="relative group"
        >
          <div
            class="text-[10px] font-bold text-slate-600 flex items-center justify-between px-2 py-1.5 bg-slate-50 hover:bg-slate-100 rounded border border-slate-200 transition-colors"
          >
            <span class="truncate">{{ btn }}</span>
          </div>
          <!-- Handle specifically for this button -->
          <Handle
            :id="`menu-btn-${idx}`"
            type="source"
            :position="Position.Right"
            class="!w-3 !h-3 !bg-indigo-500 !border-2 !border-white hover:!scale-125 transition-transform !absolute !-right-1 !top-1/2 !-translate-y-1/2"
          />
        </div>
      </div>
    </div>

    <!-- Default Output Handle (Source) - Hide for menu type since it uses custom handles -->
    <Handle
      v-if="data.type !== 'menu'"
      type="source"
      :position="Position.Right"
      class="!w-4 !h-4 !bg-indigo-500 !border-2 !border-white hover:!scale-125 transition-transform"
    />
  </div>
</template>

<style>
/* Vue Flow Handle Styles Override if needed */
</style>
