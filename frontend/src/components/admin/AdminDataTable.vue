<script setup lang="ts">
interface Header {
  label: string
  class?: string
}

defineProps<{
  headers: Header[]
  loading?: boolean
  emptyMessage?: string
  hasData?: boolean
}>()
</script>

<template>
  <div
    class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative"
  >
    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-white/80 z-10 fade-in"
    >
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
      ></div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse min-w-full">
        <thead class="bg-slate-50 border-b border-slate-100">
          <tr>
            <th
              v-for="(header, index) in headers"
              :key="index"
              class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap"
              :class="header.class"
            >
              {{ header.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <slot></slot>
          <tr v-if="!hasData && !loading">
            <td
              :colspan="headers.length"
              class="px-6 py-12 text-center text-slate-500 italic"
            >
              <slot name="empty">{{ emptyMessage }}</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
