<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import VideoConfigFields from './VideoConfigFields.vue'

const { t } = useI18n()
import { type NodeData, type CourseNodeField } from '../../../types/types'

const show = ref(false)
const isEditing = ref(false)
const submitted = ref(false)

const form = reactive<NodeData>({
  title: '',
  type: 'video',
  video_url: '',
  playback_speed: 1.0,
  meeting_link: '',
  show_description: true,
  content: {
    description: '',
    fields: [],
    buttons: [],
  },
})

const AVAILABLE_FIELDS = [
  {
    name: 'name',
    labelKey: 'course.form.fields.name',
    type: 'text',
    icon: 'user',
    required: true,
    min: 5,
    max: 100,
  },
  {
    name: 'email',
    labelKey: 'course.form.fields.email',
    type: 'email',
    icon: 'envelope',
    required: true,
    max: 100,
  },
  {
    name: 'phone',
    labelKey: 'course.form.fields.phone',
    type: 'tel',
    icon: 'phone',
    required: true,
    min: 7,
    max: 25,
  },
  {
    name: 'city',
    labelKey: 'course.form.fields.city',
    type: 'text',
    icon: 'map-pin',
    required: true,
    min: 3,
    max: 50,
  },
  {
    name: 'country',
    labelKey: 'course.form.fields.country',
    type: 'text',
    icon: 'globe',
    required: true,
    min: 5,
    max: 50,
  },
  {
    name: 'accept_terms',
    labelKey: 'course.form.fields.accept_terms',
    type: 'checkbox',
    icon: 'check',
    required: true,
  },
  {
    name: 'amway_code',
    labelKey: 'course.form.fields.amway_code',
    type: 'text',
    icon: 'identification',
    required: true,
    min: 8,
    max: 15,
  },
]

const toggleField = (field: {
  name: string
  labelKey: string
  type: string
  required: boolean
  min?: number
  max?: number
}) => {
  if (!form.content) form.content = {}
  if (!form.content.fields) form.content.fields = []

  const index = form.content.fields.findIndex(
    (f: CourseNodeField) => f.name === field.name
  )
  if (index > -1) {
    form.content.fields.splice(index, 1)
  } else {
    form.content.fields.push({
      name: field.name,
      label: t(field.labelKey),
      type: field.type,
      required: true,
      min: field.min,
      max: field.max,
    })

    // Keep sorting as per AVAILABLE_FIELDS
    form.content.fields.sort((a, b) => {
      const indexA = AVAILABLE_FIELDS.findIndex((f) => f.name === a.name)
      const indexB = AVAILABLE_FIELDS.findIndex((f) => f.name === b.name)
      return indexA - indexB
    })
  }
}

const isFieldSelected = (fieldName: string) => {
  return form.content?.fields?.some((f) => f.name === fieldName)
}

const addButton = () => {
  if (!form.content) form.content = {}
  if (!form.content.buttons) form.content.buttons = []
  form.content.buttons.push(`Botón ${form.content.buttons.length + 1}`)
}

const removeButton = (index: number) => {
  if (form.content?.buttons) {
    form.content.buttons.splice(index, 1)
  }
}

const emit = defineEmits(['save'])

const open = (data?: Partial<NodeData>) => {
  submitted.value = false
  if (data && data.id) {
    isEditing.value = true
    Object.assign(form, {
      ...data,
      playback_speed: data.playback_speed || 1.0,
      show_description:
        data.show_description !== undefined ? data.show_description : true,
      content: data.content || { description: '', fields: [], buttons: [] },
    })
    if (form.content && !form.content.buttons) form.content.buttons = []
  } else {
    isEditing.value = false
    Object.assign(form, {
      id: undefined,
      title: '',
      type: 'video',
      video_url: '',
      playback_speed: 1.0,
      meeting_link: '',
      show_description: true,
      content: {
        description: '',
        fields: AVAILABLE_FIELDS.filter((f) => f.name !== 'amway_code').map(
          (f) => ({
            name: f.name,
            label: t(f.labelKey),
            type: f.type,
            required: true,
            min: f.min,
            max: f.max,
          })
        ),
        buttons: [],
      },
    })
  }
  show.value = true
}

const close = () => {
  show.value = false
}

const handleSave = () => {
  submitted.value = true
  if (!form.title.trim()) return

  if (
    form.type === 'form' &&
    (!form.content?.fields || form.content.fields.length === 0)
  )
    return

  if (form.type === 'menu' && form.content?.buttons) {
    form.content.buttons = form.content.buttons.filter(
      (b) => b && b.trim() !== ''
    )
  }

  emit('save', { ...form })
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
              class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-100"
            >
              <div class="bg-white px-4 pb-4 pt-5 sm:p-8">
                <header class="mb-6">
                  <h3 id="modal-title" class="text-xl font-bold text-slate-800">
                    {{
                      isEditing
                        ? t('course.editor.modal.edit_title')
                        : t('course.editor.modal.create_title')
                    }}
                  </h3>
                  <p class="text-sm text-slate-500 mt-1">
                    {{ t('course.editor.modal.subtitle') }}
                  </p>
                </header>

                <div class="space-y-6">
                  <!-- Title -->
                  <div>
                    <label
                      class="block text-sm font-bold text-slate-700 mb-2"
                      >{{ t('course.editor.modal.field_title') }}</label
                    >
                    <input
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
                    />
                    <p
                      v-if="submitted && !form.title.trim()"
                      class="mt-1 text-xs font-bold text-red-600"
                    >
                      {{ t('auth.errors.required') }}
                    </p>
                  </div>

                  <!-- Shared Fields: Description & Meeting Link -->
                  <div class="space-y-4">
                    <div>
                      <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-slate-700"
                          >{{
                            t('course.editor.modal.field_description_label')
                          }}
                          ({{
                            t('course.editor.modal.field_description_optional')
                          }})</label
                        >
                        <div
                          class="flex items-center gap-2 cursor-pointer select-none"
                        >
                          <input
                            id="show_description"
                            v-model="form.show_description"
                            type="checkbox"
                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer"
                          />
                          <label
                            for="show_description"
                            class="text-xs font-bold text-slate-600 cursor-pointer"
                            >{{
                              t('course.editor.modal.field_show_description')
                            }}</label
                          >
                        </div>
                      </div>
                      <textarea
                        v-model="form.content!.description"
                        rows="3"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all resize-none"
                        :placeholder="
                          t('course.editor.modal.field_description_placeholder')
                        "
                      ></textarea>
                    </div>
                  </div>

                  <!-- Type Tabs -->
                  <div>
                    <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                      <button
                        v-for="nodeType in ['video', 'form', 'menu'] as const"
                        :key="nodeType"
                        class="flex-1 py-2 px-4 rounded-lg text-sm font-bold transition-all"
                        :class="
                          form.type === nodeType
                            ? 'bg-white text-indigo-600 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        "
                        @click="form.type = nodeType"
                      >
                        {{ t(`course.editor.modal.tabs.${nodeType}`) }}
                      </button>
                    </div>
                  </div>

                  <!-- Conditional Content -->
                  <div
                    class="bg-slate-50 rounded-2xl p-6 border border-slate-200"
                  >
                    <!-- Video Content -->
                    <div v-if="form.type === 'video'">
                      <VideoConfigFields
                        :label="t('course.editor.modal.field_video_url')"
                        :video-url="form.video_url ?? undefined"
                        :playback-speed="
                          (form.playback_speed as number) ?? undefined
                        "
                        @update:video-url="form.video_url = $event ?? undefined"
                        @update:playback-speed="
                          form.playback_speed = $event ?? undefined
                        "
                      />
                    </div>

                    <!-- Form Content -->
                    <div v-if="form.type === 'form'" class="space-y-4">
                      <header class="flex items-center justify-between mb-2">
                        <label
                          class="block text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >
                          {{ t('course.editor.modal.form_fields_title') }}
                        </label>
                        <span
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="
                            submitted &&
                            (!form.content?.fields ||
                              form.content.fields.length === 0)
                              ? 'bg-red-100 text-red-600'
                              : 'bg-indigo-50 text-indigo-500'
                          "
                        >
                          {{ form.content?.fields?.length || 0 }}
                          {{ t('course.editor.modal.selected') }}
                        </span>
                      </header>
                      <p
                        v-if="
                          submitted &&
                          form.type === 'form' &&
                          (!form.content?.fields ||
                            form.content.fields.length === 0)
                        "
                        class="mb-3 text-xs font-bold text-red-600"
                      >
                        Selecciona al menos un campo para el formulario.
                      </p>

                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button
                          v-for="field in AVAILABLE_FIELDS"
                          :key="field.name"
                          class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left group"
                          :class="[
                            isFieldSelected(field.name)
                              ? 'bg-indigo-50 border-indigo-500 text-indigo-700'
                              : 'bg-white border-slate-100 text-slate-600 hover:border-slate-200',
                            'cursor-pointer',
                          ]"
                          @click="toggleField(field)"
                        >
                          <div
                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            :class="
                              isFieldSelected(field.name)
                                ? 'bg-indigo-600 text-white'
                                : 'bg-slate-50 text-slate-400 group-hover:bg-slate-100'
                            "
                          >
                            <!-- Simplified Icons -->
                            <svg
                              v-if="field.icon === 'user'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"
                              />
                            </svg>
                            <svg
                              v-else-if="field.icon === 'envelope'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"
                              />
                            </svg>
                            <svg
                              v-else-if="field.icon === 'phone'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                              />
                            </svg>
                            <svg
                              v-else-if="field.icon === 'identification'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"
                              />
                            </svg>
                            <svg
                              v-else-if="field.icon === 'globe'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"
                              />
                            </svg>
                            <svg
                              v-else-if="field.icon === 'map-pin'"
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                              />
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z"
                              />
                            </svg>
                            <svg
                              v-else
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke-width="2"
                              stroke="currentColor"
                              class="w-4 h-4"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                              />
                            </svg>
                          </div>
                          <span class="font-bold text-sm">{{
                            t(field.labelKey) || field.name
                          }}</span>
                        </button>
                      </div>
                    </div>

                    <!-- Menu/Links Content -->
                    <div v-if="form.type === 'menu'" class="space-y-4">
                      <VideoConfigFields
                        :label="
                          t('course.editor.modal.field_video_url_optional')
                        "
                        :video-url="form.video_url ?? undefined"
                        :playback-speed="
                          (form.playback_speed as number) ?? undefined
                        "
                        @update:video-url="form.video_url = $event ?? undefined"
                        @update:playback-speed="
                          form.playback_speed = $event ?? undefined
                        "
                      />

                      <header class="flex items-center justify-between mb-2">
                        <label
                          class="block text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >
                          {{ t('course.editor.modal.menu_buttons_title') }}
                        </label>
                        <button
                          class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full hover:bg-indigo-100 transition-colors flex items-center gap-1"
                          @click="addButton()"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="w-3 h-3"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15"
                            />
                          </svg>
                          Agregar Botón
                        </button>
                      </header>

                      <div class="space-y-3">
                        <div
                          v-for="(_btn, idx) in form.content?.buttons || []"
                          :key="idx"
                          class="flex gap-2 items-center"
                        >
                          <input
                            v-model="form.content!.buttons![idx]"
                            type="text"
                            class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            :class="
                              !(form.content?.buttons?.[idx] || '').trim()
                                ? 'border-red-400 focus:ring-red-500'
                                : ''
                            "
                            :placeholder="'Texto del botón ' + (idx + 1)"
                          />
                          <button
                            class="p-2.5 text-red-500 bg-red-50 rounded-xl hover:bg-red-500 hover:text-white transition-all group"
                            title="Eliminar Botón"
                            @click="removeButton(idx)"
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

                        <div
                          v-if="!form.content?.buttons?.length"
                          class="text-center py-6 text-slate-400 text-sm italic border-2 border-dashed border-slate-200 rounded-xl bg-white/50"
                        >
                          {{ t('course.editor.modal.menu_no_buttons') }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label
                      class="block text-sm font-bold text-slate-700 shrink-0"
                      >{{ t('course.editor.modal.field_meeting_link') }}</label
                    >
                    <input
                      v-model="form.meeting_link"
                      type="text"
                      class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium text-sm"
                      :placeholder="
                        t('course.editor.modal.field_meeting_link_placeholder')
                      "
                    />
                  </div>
                </div>
              </div>
              <div
                class="bg-slate-50 px-4 py-6 sm:flex sm:flex-row-reverse sm:px-8 gap-3"
              >
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg hover:bg-indigo-700 transition-all sm:w-auto"
                  @click="handleSave"
                >
                  {{ t('course.editor.modal.save') }}
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
