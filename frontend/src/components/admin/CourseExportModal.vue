<script setup lang="ts">
import { ref } from 'vue'
import type { Course } from '../../types'

const props = defineProps<{
  courses: Course[]
}>()

const show = ref(false)
const selectedCourseIds = ref<number[]>([])

const emit = defineEmits(['export'])

const open = () => {
  selectedCourseIds.value = []
  show.value = true
}

const close = () => {
  show.value = false
}

const handleExport = () => {
  if (selectedCourseIds.value.length === 0) return
  emit('export', selectedCourseIds.value)
  close()
}

const toggleCourse = (id: number) => {
  const index = selectedCourseIds.value.indexOf(id)
  if (index === -1) {
    selectedCourseIds.value.push(id)
  } else {
    selectedCourseIds.value.splice(index, 1)
  }
}

const selectAll = () => {
  if (selectedCourseIds.value.length === props.courses.length) {
    selectedCourseIds.value = []
  } else {
    selectedCourseIds.value = props.courses.map((c) => c.id)
  }
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
              class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100"
            >
              <div class="bg-white px-4 pb-4 pt-5 sm:p-8">
                <header class="mb-6 flex justify-between items-center">
                  <div>
                    <h3
                      id="modal-title"
                      class="text-xl font-bold text-slate-800"
                    >
                      Exportar Cursos
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                      Selecciona los cursos que deseas exportar.
                    </p>
                  </div>
                  <button
                    class="text-sm text-indigo-600 font-bold hover:text-indigo-800"
                    @click="selectAll"
                  >
                    {{
                      selectedCourseIds.length === courses.length
                        ? 'Desmarcar Todos'
                        : 'Marcar Todos'
                    }}
                  </button>
                </header>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                  <div
                    v-for="course in courses"
                    :key="course.id"
                    class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                    :class="
                      selectedCourseIds.includes(course.id)
                        ? 'border-indigo-500 bg-indigo-50/50'
                        : 'border-slate-200 hover:border-indigo-300 hover:bg-slate-50'
                    "
                    @click="toggleCourse(course.id)"
                  >
                    <div class="flex-shrink-0">
                      <input
                        type="checkbox"
                        :checked="selectedCourseIds.includes(course.id)"
                        class="w-5 h-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 pointer-events-none"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-bold text-slate-900 truncate">
                        {{ course.title }}
                      </p>
                      <p class="text-xs text-slate-500 truncate">
                        {{ course.slug }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="bg-slate-50 px-4 py-6 sm:flex sm:flex-row-reverse sm:px-8 gap-3"
              >
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg hover:bg-indigo-700 transition-all sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="selectedCourseIds.length === 0"
                  @click="handleExport"
                >
                  Exportar ({{ selectedCourseIds.length }})
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all sm:mt-0 sm:w-auto"
                  @click="close"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
