<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseFlowEditor from '../../../components/admin/flow/BaseFlowEditor.vue'
import { apiRequest } from '../../../api/apiClient'
import { EDITOR_CONFIG } from '../../../config/constants'
import AppToast from '../../../components/common/AppToast.vue'
import ConfirmationModal from '../../../components/common/ConfirmationModal.vue'
import { useDebounce } from '../../../composables/useDebounce'
import NodeEditorModal, { type NodeData } from '../../../components/admin/course/NodeEditorModal.vue'
import type { FlowNodeChange } from '../../../types/CourseFlow'
import type { ModalConfig } from '../../../components/common/ConfirmationModal.vue'

const route = useRoute()
const courseId = route.params.id

const initialNodes = ref<any[]>([])
const initialEdges = ref<any[]>([])
const loading = ref(true)
const courseTitle = ref('')
const isSaving = ref(false)
const isEditingTitle = ref(false)
const tempCourseTitle = ref('')
const titleInputRef = ref<HTMLInputElement | null>(null)
const nodeModalRef = ref<InstanceType<typeof NodeEditorModal> | null>(null)

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
                        const edge: any = {
                            id: `e${opt.id || `${node.id}-${opt.next_node_id}`}`,
                            source: node.id.toString(),
                            target: opt.next_node_id.toString(),
                            label: opt.label?.startsWith('menu-btn-')
                                ? node.content?.buttons?.[parseInt(opt.label.replace('menu-btn-', ''))] || opt.label
                                : opt.label, // Always show the label
                            type: 'custom',
                            animated: true
                        }
                        
                        if (node.type === 'menu') {
                            edge.sourceHandle = opt.label?.startsWith('menu-btn-') ? opt.label : opt.label // Keep opt.label for fallback legacy string connections
                        }
                        
                        edges.push(edge)
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

const syncNodesData = (updatedNodes: any[]) => {
    initialNodes.value.forEach(node => {
        const serverNode = updatedNodes.find((n: any) => n.id.toString() === node.id)
        if (serverNode) {
            node.data = {
                ...node.data,
                isStart: serverNode.is_start,
                isEnd: serverNode.is_end,
                options: serverNode.options,
                title: serverNode.title,
                type: serverNode.type,
                video_url: serverNode.video_url,
                content: serverNode.content
            }
        }
    })
}

const _saveConnections = createStateDebouncer<{ edges: any[] }>(async ({ edges }) => {
    const connections = edges.map(edge => ({
        source_node_id: parseInt(edge.source),
        target_node_id: parseInt(edge.target),
        label: edge.sourceHandle || edge.label || t('course.editor.default_connection_label')
    }))

    const response = await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes/update-connections`,
        body: { connections }
    })
    
    if (response.success && response.data) {
        // Sync flags without re-initializing the flow
        syncNodesData(response.data.data)
        
        // Update local edges too to match the labels/ids from server if necessary
        // but edges are usually fine as is from the UI
        initialEdges.value = edges
    }
    
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

const openNodeModal = (data?: Partial<NodeData>) => {
    nodeModalRef.value?.open(data)
}

const handleEditTitle = () => {
    tempCourseTitle.value = courseTitle.value
    isEditingTitle.value = true
    nextTick(() => {
        titleInputRef.value?.focus()
    })
}

const saveTitle = async () => {
    if (!tempCourseTitle.value.trim() || tempCourseTitle.value === courseTitle.value) {
        isEditingTitle.value = false
        return
    }

    isSaving.value = true
    const response = await apiRequest({
        method: 'PUT',
        url: `/v1/admin/courses/${courseId}`,
        body: { title: tempCourseTitle.value.trim() }
    })

    if (response.success) {
        courseTitle.value = tempCourseTitle.value.trim()
        showToast(t('course.management.save_success'), 'success') // Using generic success toast
    } else {
        showToast(response.error?.message || t('common.error'), 'error')
    }
    
    isEditingTitle.value = false
    isSaving.value = false
}

const handleNodeSave = async (formData: NodeData) => {
    isSaving.value = true
    
    let response
    if (formData.id) {
        // Update existing
        response = await apiRequest({
            method: 'PUT',
            url: `/v1/admin/courses/${courseId}/nodes/${formData.id}`,
            body: formData
        })
    } else {
        // Create new
        response = await apiRequest({
            method: 'POST',
            url: `/v1/admin/courses/${courseId}/nodes`,
            body: {
                ...formData,
                pos_x: 100,
                pos_y: 100
            }
        })
    }

    if (response.success) {
        await fetchNodes()
        showToast(
            t('course.editor.save_success'),
            'success'
        )
    } else {
        showToast(response.error?.message || t('common.error'), 'error')
    }
    isSaving.value = false
}

const handleAction = async ({ type, id }: { type: string, id: string }) => {
    if (type === 'edit') {
        const node = initialNodes.value.find((n: any) => n.id === id)
        if (!node) return

        openNodeModal({
            id: parseInt(id),
            title: node.data.title,
            type: node.data.type,
            video_url: node.data.video_url,
            content: node.data.content
        })
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
                <div v-if="isEditingTitle" class="flex items-center">
                    <input 
                        ref="titleInputRef"
                        v-model="tempCourseTitle"
                        type="text"
                        class="px-2 py-1 border border-indigo-500 rounded outline-none font-medium text-slate-900 bg-indigo-50"
                        @blur="saveTitle"
                        @keyup.enter="saveTitle"
                        @keyup.esc="isEditingTitle = false"
                    />
                </div>
                <span 
                    v-else 
                    class="font-medium text-slate-900 cursor-pointer hover:text-indigo-600 hover:bg-slate-100 px-2 py-1 rounded transition-colors flex items-center gap-2 group"
                    @click="handleEditTitle"
                >
                    {{ courseTitle }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50 transition-opacity">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                </span>
            </div>
            <div class="flex gap-2">
                <button 
                    @click="openNodeModal()" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold text-sm shadow-sm transition-all flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ t('course.editor.modal.create_title') }}
                </button>
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
        <NodeEditorModal ref="nodeModalRef" @save="handleNodeSave" />
    </div>
</template>
