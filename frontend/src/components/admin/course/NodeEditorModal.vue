<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

export interface NodeData {
    id?: number
    title: string
    type: 'video' | 'form' | 'menu'
    video_url?: string
    content?: {
        description?: string
        [key: string]: any
    }
}

const show = ref(false)
const isEditing = ref(false)
const submitted = ref(false)

const form = reactive<NodeData>({
    title: '',
    type: 'video',
    video_url: '',
    content: {
        description: ''
    }
})

const emit = defineEmits(['save'])

const open = (data?: Partial<NodeData>) => {
    submitted.value = false
    if (data && data.id) {
        isEditing.value = true
        Object.assign(form, {
            ...data,
            content: data.content || { description: '' }
        })
    } else {
        isEditing.value = false
        Object.assign(form, {
            title: '',
            type: 'video',
            video_url: '',
            content: { description: '' }
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
            <div v-if="show" class="fixed inset-0 z-[10000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="close"></div>

                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <Transition
                        enter-active-class="ease-out duration-300 transition"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200 transition"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-100">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-8">
                                <header class="mb-6">
                                    <h3 class="text-xl font-bold text-slate-800" id="modal-title">
                                        {{ isEditing ? t('course.editor.modal.edit_title') : t('course.editor.modal.create_title') }}
                                    </h3>
                                    <p class="text-sm text-slate-500 mt-1">{{ t('course.editor.modal.subtitle') }}</p>
                                </header>

                                <div class="space-y-6">
                                    <!-- Title -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('course.editor.modal.field_title') }}</label>
                                        <input 
                                            v-model="form.title"
                                            type="text" 
                                            class="w-full px-4 py-3 bg-slate-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-medium"
                                            :class="submitted && !form.title.trim() ? 'border-red-500 bg-red-50' : 'border-slate-200'"
                                            :placeholder="t('course.editor.modal.field_title_placeholder')"
                                        />
                                        <p v-if="submitted && !form.title.trim()" class="mt-1 text-xs font-bold text-red-600">
                                            {{ t('auth.errors.required') }}
                                        </p>
                                    </div>

                                    <!-- Type Tabs -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('course.editor.modal.field_type') }}</label>
                                        <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                                            <button 
                                                v-for="nodeType in (['video', 'form', 'menu'] as const)"
                                                :key="nodeType"
                                                @click="form.type = nodeType"
                                                class="flex-1 py-2 px-4 rounded-lg text-sm font-bold transition-all"
                                                :class="form.type === nodeType ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                            >
                                                {{ t(`course.editor.modal.tabs.${nodeType}`) }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Conditional Content -->
                                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                                        <!-- Video Content -->
                                        <div v-if="form.type === 'video'" class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                                    {{ t('course.editor.modal.field_video_url') }}
                                                </label>
                                                <input 
                                                    v-model="form.video_url"
                                                    type="text" 
                                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                                                    placeholder="https://youtube.com/..."
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                                    {{ t('course.editor.modal.field_description') }}
                                                </label>
                                                <textarea 
                                                    v-model="form.content!.description"
                                                    rows="4"
                                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all resize-none"
                                                    :placeholder="t('course.editor.modal.field_description_placeholder')"
                                                ></textarea>
                                            </div>
                                        </div>

                                        <!-- Form Content -->
                                        <div v-if="form.type === 'form'" class="py-12 flex flex-col items-center justify-center text-center">
                                            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600 max-w-xs">
                                                {{ t('course.editor.modal.form_placeholder') }}
                                            </p>
                                        </div>

                                        <!-- Menu/Links Content -->
                                        <div v-if="form.type === 'menu'" class="py-12 flex flex-col items-center justify-center text-center">
                                            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600 max-w-xs">
                                                {{ t('course.editor.modal.menu_placeholder') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-4 py-6 sm:flex sm:flex-row-reverse sm:px-8 gap-3">
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
