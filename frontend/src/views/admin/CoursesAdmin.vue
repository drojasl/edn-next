<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CourseFlowEditor from '../../components/admin/CourseFlowEditor.vue'
import { apiRequest } from '../../api/apiClient'
import { useDebounce } from '../../composables/useDebounce'
import type { FlowNodeChange, CourseConnectionUpdate } from '../../types/CourseFlow'

const courses = ref([])
const loading = ref(true)
const router = useRouter()
const { t } = useI18n()

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
        if (confirm(t('course.management.delete_confirm'))) {
            const response = await apiRequest({
                method: 'DELETE',
                url: `/v1/admin/courses/${id}`
            })
            if (response.success) {
                fetchCourses()
            }
        }
    }
}

const handleSaveConnections = async (connections: CourseConnectionUpdate[]) => {
    // Call API to save connections
    const response = await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-connections',
        body: { connections }
    })
    
    if (response.success) {
        console.log('Connections saved')
    }
}

const handlePositionChange = createDebouncer<FlowNodeChange>(async (payload) => {
    await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses/update-positions',
        body: { positions: payload }
    })
    console.log('Positions saved')
})

onMounted(() => {
    fetchCourses()
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
    </div>
</template>
