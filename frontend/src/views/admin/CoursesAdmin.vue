<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CourseFlowEditor from '../../components/admin/CourseFlowEditor.vue'
import { apiRequest } from '../../api/apiClient'
import { useDebounce } from '../../composables/useDebounce'
import type { FlowNodeChange, CourseConnectionUpdate } from '../../types/CourseFlow'

interface Course {
    id: number
    title: string
    next_course_id: number | null
    pos_x: number
    pos_y: number
}

interface ModalConfig {
    title: string
    message: string
    isDestructive?: boolean
    confirmText?: string
    cancelText?: string
    onConfirm: () => void | Promise<void>
}

type ToastType = 'error' | 'success'

interface ToastState {
    show: boolean
    message: string
    type: ToastType
}

const courses = ref<Course[]>([])
const loading = ref(true)
const router = useRouter()
const { t } = useI18n()

const toast = ref<ToastState>({ show: false, message: '', type: 'error' })
const toastTimeout = ref<number | null>(null)

const showToast = (message: string, type: ToastType = 'error') => {
    if (toastTimeout.value) clearTimeout(toastTimeout.value)
    toast.value = { show: true, message, type }
    toastTimeout.value = setTimeout(() => {
        toast.value.show = false
    }, 4000)
}

const modal = ref({
    show: false,
    title: '',
    message: '',
    onConfirm: () => {},
    confirmText: '',
    cancelText: '',
    isDestructive: false
})

const openModal = (config: ModalConfig) => {
    modal.value = { 
        show: true, 
        title: config.title, 
        message: config.message, 
        onConfirm: config.onConfirm,
        confirmText: config.confirmText || t('common.confirm'),
        cancelText: config.cancelText || t('common.cancel'),
        isDestructive: config.isDestructive || false
    }
}

const { createDebouncer } = useDebounce(1000)

const fetchCourses = async () => {
    loading.value = true
    const response = await apiRequest({
        method: 'GET',
        url: '/v1/admin/courses'
    })
    
    if (response.success && response.data) {
        courses.value = response.data.data
    }
    loading.value = false
}

const handleAction = async ({ type, id }: { type: string, id: string }) => {
    if (type === 'edit') {
        router.push(`/admin/cursos/${id}/edit`)
    } else if (type === 'delete') {
        const courseId = parseInt(id)
        const course = courses.value.find((c: any) => c.id === courseId)
        
        // 1. Check outgoing connection (the course has a next_course_id)
        const hasOutgoing = course && course.next_course_id !== null
        
        // 2. Check incoming connection (any other course has this course as next_course_id)
        const hasIncoming = courses.value.some((c: any) => c.next_course_id === courseId)
        
        if (hasOutgoing || hasIncoming) {
            showToast(t('course.management.delete_error_connected'), 'error')
            return
        }

        openModal({
            title: t('course.management.delete'),
            message: t('course.management.delete_confirm'),
            isDestructive: true,
            confirmText: t('course.management.delete'),
            onConfirm: async () => {
                modal.value.show = false
                const response = await apiRequest({
                    method: 'DELETE',
                    url: `/v1/admin/courses/${id}`
                })
                if (response.success) {
                    showToast(t('course.management.delete_success'), 'success')
                    fetchCourses()
                } else {
                    showToast(response.error?.message || t('common.error'), 'error')
                }
            }
        })
    }
}

const handleSaveConnections = async (connections: CourseConnectionUpdate[]) => {
    // 1. Update local state immediately for instant validation feedback
    connections.forEach(update => {
        const course = courses.value.find(c => c.id === update.id)
        if (course) {
            course.next_course_id = update.next_course_id
        }
    })

    // 2. Call API to save connections
    const response = await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-connections',
        body: { connections }
    })
    
    if (response.success) {
        if (import.meta.env.DEV) {
            console.log('Connections saved')
        }
    } else {
        showToast(response.error?.message || t('common.error'), 'error')
    }
}

const handlePositionChange = createDebouncer<FlowNodeChange>(async (payload) => {
    await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-positions',
        body: { positions: payload }
    })
    if (import.meta.env.DEV) {
        console.log('Positions saved')
    }
})

onMounted(() => {
    fetchCourses()
})

onUnmounted(() => {
    if (toastTimeout.value) clearTimeout(toastTimeout.value)
})
</script>

<template>
    <div class="p-6 h-screen flex flex-col bg-slate-50">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">{{ $t('course.management.title') }}</h1>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    {{ $t('course.management.new_course') }}
                </button>
            </div>
        </header>
        
        <div class="flex-1 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>
            
            <CourseFlowEditor 
                v-else 
                :courses="courses" 
                @save="handleSaveConnections"
                @position-change="handlePositionChange"
                @action="handleAction"
            />
        </div>

        <!-- Premium Notification Toast -->
        <Transition
            enter-active-class="transform ease-out duration-500 transition"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition ease-in duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 pb-10"
        >
            <div v-if="toast.show" class="fixed top-6 right-6 z-[9999] pointer-events-auto">
                <div 
                    class="min-w-[320px] max-w-md shadow-[0_20px_50px_rgba(0,0,0,0.15)] rounded-2xl pointer-events-auto flex border backdrop-blur-xl overflow-hidden"
                    :class="toast.type === 'error' ? 'bg-white/90 border-red-100' : 'bg-white/90 border-green-100'"
                >
                    <div class="flex-1 p-4">
                        <div class="flex items-center gap-4">
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                                :class="toast.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'"
                            >
                                <svg v-if="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 
                                    class="text-sm font-bold tracking-tight"
                                    :class="toast.type === 'error' ? 'text-red-900' : 'text-green-900'"
                                >
                                    {{ $t('toast.' + (toast.type === 'error' ? 'error_title' : 'success_title')) }}
                                </h4>
                                <p class="text-xs text-slate-600 font-medium leading-relaxed mt-0.5">
                                    {{ toast.message }}
                                </p>
                            </div>
                            <button 
                                @click="toast.show = false" 
                                class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Premium Confirmation Modal -->
        <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modal.show" class="fixed inset-0 z-[10000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="modal.show = false"></div>

                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <Transition
                        enter-active-class="ease-out duration-300 transition"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200 transition"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div 
                                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                        :class="modal.isDestructive ? 'bg-red-100 text-red-600' : 'bg-indigo-100 text-indigo-600'"
                                    >
                                        <svg v-if="modal.isDestructive" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                        <h3 class="text-lg font-bold leading-6 text-slate-800" id="modal-title">{{ modal.title }}</h3>
                                        <div class="mt-2 text-sm text-slate-500 font-medium leading-relaxed">
                                            {{ modal.message }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                                <button 
                                    type="button" 
                                    class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all sm:w-auto"
                                    :class="modal.isDestructive ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                                    @click="modal.onConfirm"
                                >
                                    {{ modal.confirmText }}
                                </button>
                                <button 
                                    type="button" 
                                    class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all sm:mt-0 sm:w-auto"
                                    @click="modal.show = false"
                                >
                                    {{ modal.cancelText || $t('common.cancel') || 'Cancelar' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </div>
</template>
