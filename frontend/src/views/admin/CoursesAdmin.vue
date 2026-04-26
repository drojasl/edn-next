<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import CourseCreateModal from '../../components/admin/CourseCreateModal.vue'
import CourseExportModal from '../../components/admin/CourseExportModal.vue'
import { useDebounce } from '../../composables/useDebounce'
import AppToast from '../../components/common/AppToast.vue'
import ConfirmationModal from '../../components/common/ConfirmationModal.vue'
import CourseFlowEditor from '../../components/admin/CourseFlowEditor.vue'
import {
  type FlowNodeChange,
  type CourseConnectionUpdate,
  type Course,
  type ApiError,
} from '../../types'
import type { ModalConfig } from '../../components/common/ConfirmationModal.vue'

const courses = ref<Course[]>([])
const loading = ref(true)
const router = useRouter()
const { t } = useI18n()
const isSaving = ref(false)

const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const modalRef = ref<InstanceType<typeof ConfirmationModal> | null>(null)
const createModalRef = ref<InstanceType<typeof CourseCreateModal> | null>(null)
const exportModalRef = ref<InstanceType<typeof CourseExportModal> | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const showToast = (message: string, type: 'success' | 'error' = 'error') => {
  toastRef.value?.show(message, type)
}

const openModal = (config: ModalConfig) => {
  modalRef.value?.open(config)
}

const openCreateModal = () => {
  createModalRef.value?.open()
}

const handleCreateCourse = async (data: {
  title: string
  description: string
}) => {
  isSaving.value = true
  const response = await apiRequest<{ data: { id: number } }>({
    method: 'POST',
    url: '/v1/admin/courses',
    body: data,
  })

  if (response.success && response.data) {
    showToast(t('course.editor.modal.success'), 'success')
    router.push(`/admin/cursos/${response.data.data.id}/edit`)
  } else {
    showToast(response.error?.message || t('common.error'), 'error')
  }
  isSaving.value = false
}

const openExportModal = () => {
  exportModalRef.value?.open()
}

const handleExportCourses = async (ids: number[]) => {
  isSaving.value = true
  for (const id of ids) {
    try {
      const response = await apiRequest<{
        data: {
          course?: { slug?: string }
          nodes?: unknown[]
          version?: string
        }
      }>({
        method: 'GET',
        url: `/v1/admin/courses/${id}/export`,
      })
      if (response.success && response.data) {
        const dataStr = JSON.stringify(response.data.data, null, 2)
        const blob = new Blob([dataStr], { type: 'application/json' })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url

        // Use slug if available, otherwise fallback to id
        const courseData = response.data.data.course || {}
        link.download = `curso_${courseData.slug || id}.json`

        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
      } else {
        showToast(response.error?.message || t('common.error'), 'error')
      }
    } catch (error) {
      const err = error as ApiError
      showToast(err.message || t('common.error'), 'error')
    }
  }
  isSaving.value = false
  showToast('Exportación completada', 'success')
}

const triggerImport = () => {
  fileInputRef.value?.click()
}

const handleImportFile = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return

  isSaving.value = true
  let successCount = 0

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    if (!file) continue

    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await apiRequest({
        method: 'POST',
        url: '/v1/admin/courses-batch/import',
        body: formData,
      })
      if (response.success) {
        successCount++
      } else {
        showToast(
          response.error?.message || `Error importing ${file.name}`,
          'error'
        )
      }
    } catch (error) {
      const err = error as ApiError
      showToast(err.message || `Error importing ${file.name}`, 'error')
    }
  }

  target.value = ''

  if (successCount > 0) {
    showToast(`Se importaron ${successCount} cursos con éxito`, 'success')
    await fetchCourses()
  }
  isSaving.value = false
}

const { createDebouncer, createStateDebouncer } = useDebounce(1000)

const fetchCourses = async () => {
  loading.value = true
  const response = await apiRequest<{ data: Course[] }>({
    method: 'GET',
    url: '/v1/admin/courses',
  })

  if (response.success && response.data) {
    courses.value = response.data.data
  }
  loading.value = false
}

const handleAction = async ({ type, id }: { type: string; id: string }) => {
  if (type === 'edit') {
    router.push(`/admin/cursos/${id}/edit`)
  } else if (type === 'delete') {
    const courseId = parseInt(id)
    const course = courses.value.find((c: Course) => c.id === courseId)

    // 1. Check outgoing connection (the course has a next_course_id)
    const hasOutgoing = course && course.next_course_id !== null

    // 2. Check incoming connection (any other course has this course as next_course_id)
    const hasIncoming = courses.value.some(
      (c: Course) => c.next_course_id === courseId
    )

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
        try {
          const response = await apiRequest({
            method: 'DELETE',
            url: `/v1/admin/courses/${id}`,
          })
          if (response.success) {
            showToast(t('course.management.delete_success'), 'success')
            await fetchCourses()
          } else {
            showToast(response.error?.message || t('common.error'), 'error')
          }
        } catch (error: unknown) {
          const err = error as ApiError
          showToast(err.message || t('common.error'), 'error')
        } finally {
          isSaving.value = false
        }
      },
    })
  }
}

const _saveConnections = createStateDebouncer<CourseConnectionUpdate[]>(
  async (connections) => {
    await apiRequest({
      method: 'POST',
      url: '/v1/admin/courses/update-connections',
      body: { connections },
    })
    isSaving.value = false
  }
)

const handleSaveConnections = (connections: CourseConnectionUpdate[]) => {
  isSaving.value = true
  // 1. Update local state immediately for instant validation feedback
  connections.forEach((update) => {
    const course = courses.value.find((c) => c.id === update.id)
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
    body: { positions: payload },
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
  <div class="flex flex-col gap-6">
    <AdminPageHeader :title="$t('course.management.title')">
      <template #actions>
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <input
            ref="fileInputRef"
            type="file"
            accept=".json"
            multiple
            class="hidden"
            @change="handleImportFile"
          />
          <button
            class="flex-1 sm:flex-none px-4 py-2.5 bg-white text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 font-bold transition-all flex items-center justify-center gap-2"
            @click="triggerImport"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
              />
            </svg>
            Importar
          </button>

          <button
            class="flex-1 sm:flex-none px-4 py-2.5 bg-white text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 font-bold transition-all flex items-center justify-center gap-2"
            @click="openExportModal"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              />
            </svg>
            Exportar
          </button>

          <button
            class="flex-1 sm:flex-none px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2"
            @click="openCreateModal"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
              />
            </svg>
            <span class="hidden sm:inline">{{
              $t('course.management.new_course')
            }}</span>
          </button>
        </div>
      </template>
    </AdminPageHeader>

    <div
      class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative min-h-[400px] h-[calc(100vh-16rem)]"
    >
      <div
        v-if="loading"
        class="absolute inset-0 flex items-center justify-center bg-white/80 z-10"
      >
        <div
          class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"
        ></div>
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
    <CourseExportModal
      ref="exportModalRef"
      :courses="courses"
      @export="handleExportCourses"
    />
  </div>
</template>
