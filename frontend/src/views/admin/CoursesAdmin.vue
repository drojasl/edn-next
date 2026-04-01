<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import CourseCreateModal from '../../components/admin/CourseCreateModal.vue'
import { useDebounce } from '../../composables/useDebounce'
import AppToast from '../../components/common/AppToast.vue'
import ConfirmationModal from '../../components/common/ConfirmationModal.vue'
import CourseFlowEditor from '../../components/admin/CourseFlowEditor.vue'
import type { FlowNodeChange, CourseConnectionUpdate } from '../../types/CourseFlow'

interface Course {
    id: number
    title: string
    next_course_id: number | null
    next_course_label: string | null
    pos_x: number
    pos_y: number
}

const courses = ref<Course[]>([])
const loading = ref(true)
const router = useRouter()
const { t } = useI18n()
const isSaving = ref(false)

const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const modalRef = ref<InstanceType<typeof ConfirmationModal> | null>(null)
const createModalRef = ref<InstanceType<typeof CourseCreateModal> | null>(null)

const showToast = (message: string, type: 'success' | 'error' = 'error') => {
    toastRef.value?.show(message, type)
}

const openModal = (config: any) => {
    modalRef.value?.open(config)
}

const openCreateModal = () => {
    createModalRef.value?.open()
}

const handleCreateCourse = async (data: { title: string, description: string }) => {
    isSaving.value = true
    const response = await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses',
        body: data
    })

    if (response.success && response.data) {
        showToast(t('course.editor.modal.success'), 'success')
        router.push(`/admin/cursos/${response.data.data.id}/edit`)
    } else {
        showToast(response.error?.message || t('common.error'), 'error')
    }
    isSaving.value = false
}

const { createDebouncer, createStateDebouncer } = useDebounce(1000)

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
                isSaving.value = true
                const response = await apiRequest({
                    method: 'DELETE',
                    url: `/v1/admin/courses/${id}`
                })
                if (response.success) {
                    showToast(t('course.management.delete_success'), 'success')
                    await fetchCourses()
                } else {
                    showToast(response.error?.message || t('common.error'), 'error')
                }
                isSaving.value = false
            }
        })
    }
}

const _saveConnections = createStateDebouncer<CourseConnectionUpdate[]>(async (connections) => {
    await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-connections',
        body: { connections }
    })
    isSaving.value = false
})

const handleSaveConnections = (connections: CourseConnectionUpdate[]) => {
    isSaving.value = true
    // 1. Update local state immediately for instant validation feedback
    connections.forEach(update => {
        const course = courses.value.find(c => c.id === update.id)
        if (course) {
            course.next_course_id = update.next_course_id
            course.next_course_label = update.next_course_label
        }
    })
    _saveConnections(connections)
}

const _updatePositions = createDebouncer<FlowNodeChange>(async (payload) => {
    await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-positions',
        body: { positions: payload }
    })
    isSaving.value = false
})

const handlePositionChange = (payload: FlowNodeChange) => {
    isSaving.value = true
    _updatePositions(payload, 'id')
}

onMounted(() => {
    fetchCourses()
})

onUnmounted(() => {
    // Clean up
})
</script>

<template>
    <div class="p-6 h-screen flex flex-col bg-slate-50">
        <AdminPageHeader 
            :title="$t('course.management.title')"
        >
            <template #actions>
                <button 
                    @click="openCreateModal"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold shadow-sm transition-all whitespace-nowrap"
                >
                    {{ $t('course.management.new_course') }}
                </button>
            </template>
        </AdminPageHeader>
        
        <div class="flex-1 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>
            
            <CourseFlowEditor 
                v-else 
                :courses="courses" 
                :is-saving="isSaving"
                @save="handleSaveConnections"
                @position-change="handlePositionChange"
                @action="handleAction"
            />
        </div>


        <!-- Reusable Components -->
        <AppToast ref="toastRef" />
        <ConfirmationModal ref="modalRef" />
        <CourseCreateModal ref="createModalRef" @create="handleCreateCourse" />
    </div>
</template>
