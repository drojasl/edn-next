<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseFlowEditor from '../../../components/admin/flow/BaseFlowEditor.vue'
import { apiRequest } from '../../../api/apiClient'
import { EDITOR_CONFIG } from '../../../config/constants'
import AppToast from '../../../components/common/AppToast.vue'
import ConfirmationModal from '../../../components/common/ConfirmationModal.vue'
import { useDebounce } from '../../../composables/useDebounce'
import type { FlowNodeChange } from '../../../types/CourseFlow'
import type { ModalConfig } from '../../../components/common/ConfirmationModal.vue'

const route = useRoute()
const courseId = route.params.id

const initialNodes = ref<any[]>([])
const initialEdges = ref<any[]>([])
const loading = ref(true)
const courseTitle = ref('')
const isSaving = ref(false)

// Define extent from config
const nodeExtent = [[EDITOR_CONFIG.bounds.minX, EDITOR_CONFIG.bounds.minY], [EDITOR_CONFIG.bounds.maxX, EDITOR_CONFIG.bounds.maxY]] as [[number, number], [number, number]]

const fetchNodes = async () => {
    loading.value = true
    const response = await apiRequest({
        method: 'GET',
        url: `/v1/admin/courses/${courseId}/nodes`
    })

    if (response.success && response.data) {
        const nodes = response.data.data
        if (response.data.course) {
            courseTitle.value = response.data.course.title
        }

        // Transform Nodes
        initialNodes.value = nodes.map((node: any) => ({
            id: node.id.toString(),
            type: 'custom', 
            position: { x: node.pos_x || 0, y: node.pos_y || 0 },
            data: {
                ...node,
                isStart: node.is_start,
                isEnd: node.is_end,
                options: node.options
            }
        }))

        // Transform Options to Edges
        // Each option represents ONE connection: course_node_id -> next_node_id
        const edges: any[] = []
        nodes.forEach((node: any) => {
            if (node.options && node.options.length) {
                node.options.forEach((opt: any) => {
                    if (opt.next_node_id) {
                        edges.push({
                            id: `e${opt.id || `${node.id}-${opt.next_node_id}`}`,
                            source: node.id.toString(),
                            target: opt.next_node_id.toString(),
                            label: opt.label, // Always show the label
                            // Unified output: no sourceHandle needed anymore
                            type: 'custom',
                            animated: true
                        })
                    }
                })
            }
        })
        initialEdges.value = edges
    }
    loading.value = false
}

// Init composables
const { createDebouncer, createStateDebouncer } = useDebounce(1000)

const _saveConnections = createStateDebouncer<{ edges: any[] }>(async ({ edges }) => {
    const connections = edges.map(edge => ({
        source_node_id: parseInt(edge.source),
        target_node_id: parseInt(edge.target),
        label: edge.label || t('course.editor.default_connection_label')
    }))

    await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes/update-connections`,
        body: { connections }
    })
    
    // Sync local state to allow safe deletion check without full fetch
    initialEdges.value = edges
    isSaving.value = false
})

const handleConnectionChange = (data: { edges: any[] }) => {
    isSaving.value = true
    _saveConnections(data)
}

const _updatePositions = createDebouncer<FlowNodeChange>(async (payload) => {
    await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes/update-positions`,
        body: { positions: payload }
    })
    isSaving.value = false
})

const handlePositionChange = (payload: FlowNodeChange) => {
    isSaving.value = true
    _updatePositions(payload, 'id')

    // Sync local node positions for consistency
    const node = initialNodes.value.find(n => n.id === payload.id.toString())
    if (node) {
        node.position = { x: payload.pos_x, y: payload.pos_y }
    }
}


const { t } = useI18n()

const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const modalRef = ref<InstanceType<typeof ConfirmationModal> | null>(null)

const showToast = (message: string, type: 'success' | 'error' = 'error') => {
    toastRef.value?.show(message, type)
}

const openModal = (config: ModalConfig) => {
    modalRef.value?.open(config)
}

const addNode = async (type: string) => {
    const title = prompt(t('course.editor.prompts.node_title'))
    if (!title) return

    const response = await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes`,
        body: {
            title,
            type,
            pos_x: 100,
            pos_y: 100
        }
    })

    if (response.success) {
        await fetchNodes()
    }
    isSaving.value = false
}

const handleAction = async ({ type, id }: { type: string, id: string }) => {
    if (type === 'edit') {
        const nodeId = parseInt(id)
        const node = initialNodes.value.find((n: any) => n.id === id)
        if (!node) return

        const newTitle = prompt(t('course.editor.prompts.node_title_edit'), node.data.title)
        if (!newTitle || newTitle === node.data.title) return

        isSaving.value = true
        const response = await apiRequest({
            method: 'PUT',
            url: `/v1/admin/courses/${courseId}/nodes/${nodeId}`,
            body: { title: newTitle }
        })

        if (response.success) {
            await fetchNodes()
        }
        isSaving.value = false
    } else if (type === 'delete') {
        // 1. Check for connections (incoming or outgoing) in initialEdges
        const hasConnections = initialEdges.value.some(edge =>
            edge.source === id || edge.target === id
        )

        if (hasConnections) {
            showToast(t('course.editor.delete_error_connected'), 'error')
            return
        }

        openModal({
            title: t('course.editor.delete'),
            message: t('course.editor.delete_confirm'),
            isDestructive: true,
            confirmText: t('course.management.delete'),
            onConfirm: async () => {
                isSaving.value = true
                const response = await apiRequest({
                    method: 'DELETE',
                    url: `/v1/admin/courses/${courseId}/nodes/${id}`
                })

                if (response.success) {
                    showToast(t('course.editor.delete_success'), 'success')
                    await fetchNodes()
                } else {
                    showToast(response.error?.message || t('common.error'), 'error')
                }
                isSaving.value = false
            }
        })
    }
}

onMounted(() => {
    fetchNodes()
})
</script>

<template>
    <div class="h-screen flex flex-col">
        <header class="bg-white border-b border-slate-200 p-4 flex justify-between items-center z-10">
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <span class="font-bold text-lg text-slate-800 mr-2">{{ $t('course.editor.title') }}</span>
                <span class="text-slate-400"> </span>
                <router-link to="/admin/cursos" class="text-lg font-bold hover:text-indigo-600 hover:underline">{{ $t('course.editor.breadcrumbs.courses') }}</router-link>
                <span class="text-slate-400"> > </span>
                <span class="font-medium text-slate-900">{{ courseTitle }}</span>
            </div>
            <div class="flex gap-2">
                <button @click="addNode('video')" class="px-3 py-1 bg-slate-100 rounded hover:bg-slate-200 text-sm">{{ $t('course.editor.tools.video') }}</button>
                <button @click="addNode('form')" class="px-3 py-1 bg-slate-100 rounded hover:bg-slate-200 text-sm">{{ $t('course.editor.tools.form') }}</button>
                <button @click="addNode('menu')" class="px-3 py-1 bg-slate-100 rounded hover:bg-slate-200 text-sm">{{ $t('course.editor.tools.menu') }}</button>
            </div>
        </header>

        <div class="flex-1 relative bg-slate-50">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <span class="text-slate-500 font-medium">{{ $t('course.editor.loading_map') }}</span>
                </div>
            </div>
            <BaseFlowEditor 
                v-else
                :initialNodes="initialNodes"
                :initialEdges="initialEdges"
                :node-extent="nodeExtent"
                :allowMultipleOutputs="true"
                :is-saving="isSaving"
                @node-change="handlePositionChange"
                @connection-change="handleConnectionChange"
                @action="handleAction"
            />
        </div>

        <!-- Reusable Components -->
        <AppToast ref="toastRef" />
        <ConfirmationModal ref="modalRef" />
    </div>
</template>
