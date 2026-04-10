<script setup lang="ts">
import { ref, reactive, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const show = ref(false)
const titleInput = ref<HTMLInputElement | null>(null)
const form = reactive({
  title: '',
  description: '',
})
const submitted = ref(false)

const emit = defineEmits(['create'])

const open = () => {
  submitted.value = false
  form.title = ''
  form.description = ''
  show.value = true
  nextTick(() => {
    titleInput.value?.focus()
  })
}

const close = () => {
  show.value = false
}

const handleSubmit = () => {
  submitted.value = true
  if (!form.title.trim()) return
  emit('create', {
    title: form.title.trim(),
    description: form.description.trim(),
  })
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
                <header class="mb-6">
                  <h3 id="modal-title" class="text-xl font-bold text-slate-800">
                    {{ t('course.management.new_course') }}
                  </h3>
                  <p class="text-sm text-slate-500 mt-1">
                    {{ t('course.management.modal.subtitle') }}
                  </p>
                </header>

                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                      {{ t('course.editor.modal.field_title') }}
                    </label>
                    <input
                      ref="titleInput"
                      v-model="form.title"
                      type="text"
                      class="w-full px-4 py-3 bg-slate-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-medium"
                      :class="
                        submitted && !form.title.trim()
                          ? 'border-red-500 bg-red-50'
                          : 'border-slate-200'
                      "
                      :placeholder="
                        t('course.editor.modal.field_title_placeholder')
                      "
                      @keyup.enter="handleSubmit"
                    />
                    <p
                      v-if="submitted && !form.title.trim()"
                      class="mt-1 text-xs font-bold text-red-600"
                    >
                      {{ t('auth.errors.required') }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                      {{ t('course.editor.modal.field_description') }}
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-medium resize-none"
                      :placeholder="
                        t('course.editor.modal.field_description_placeholder')
                      "
                    ></textarea>
                  </div>
                </div>
              </div>
              <div
                class="bg-slate-50 px-4 py-6 sm:flex sm:flex-row-reverse sm:px-8 gap-3"
              >
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg hover:bg-indigo-700 transition-all sm:w-auto"
                  @click="handleSubmit"
                >
                  {{ t('course.management.modal.create_button') }}
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all sm:mt-0 sm:w-auto"
                  @click="close"
                >
                  {{ t('common.cancel') }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
