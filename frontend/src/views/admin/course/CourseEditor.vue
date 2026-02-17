<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseFlowEditor from '../../../components/admin/flow/BaseFlowEditor.vue'
import { apiRequest } from '../../../api/apiClient'
import { EDITOR_CONFIG } from '../../../config/constants'

const route = useRoute()
const courseId = route.params.id

const initialNodes = ref<any[]>([])
const initialEdges = ref<any[]>([])
const loading = ref(true)
const courseTitle = ref('')

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

        // Transform Edges based on Options
        const edges: any[] = []
        nodes.forEach((node: any) => {
            if (node.options && node.options.length) {
                node.options.forEach((opt: any, index: number) => {
                    if (opt.next_node_id) {
                        edges.push({
                            id: `e-${node.id}-${opt.next_node_id}`,
                            source: node.id.toString(),
                            target: opt.next_node_id.toString(),
                            sourceHandle: `opt-${index}`,
                            label: opt.label
                        })
                    }
                })
            }
        })
        initialEdges.value = edges
    }
    loading.value = false
}

import { useDebounce } from '../../../composables/useDebounce'
import type { FlowNodeChange } from '../../../types/CourseFlow'

// Init composables
const { createDebouncer, createStateDebouncer } = useDebounce(1000)

const handleConnectionChange = createStateDebouncer<{ nodes: any[], edges: any[] }>(async ({ nodes, edges }) => {
    // Group connections by source node
    const connectionsMap = new Map<string, any[]>()
    
    edges.forEach(edge => {
        if (!connectionsMap.has(edge.source)) {
            connectionsMap.set(edge.source, [])
        }
        connectionsMap.get(edge.source)?.push(edge)

    })

    const connectionsPayload = nodes.filter(n => n.type === 'custom').map(node => {
        const nodeEdges = connectionsMap.get(node.id) || []
        const originalOptions = node.data.options || []
        
        const updatedOptions = originalOptions.map((opt: any, index: number) => {
            const edge = nodeEdges.find((e: any) => e.sourceHandle === `opt-${index}`)
            return {
                ...opt,
                next_node_id: edge ? parseInt(edge.target) : null
            }
        })

        return {
            node_id: parseInt(node.id),
            options: updatedOptions
        }
    })

    await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes/update-connections`,
        body: { connections: connectionsPayload }
    })
    console.log('Connections saved')
})

const handlePositionChange = createDebouncer<FlowNodeChange>(async (payload) => {
    await apiRequest({
        method: 'POST',
        url: `/v1/admin/courses/${courseId}/nodes/update-positions`,
        body: { positions: payload }
    })
    console.log('Positions saved')
})


const { t } = useI18n()

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
        fetchNodes()
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
                @node-change="handlePositionChange"
                @connection-change="handleConnectionChange"
            />
        </div>
    </div>
</template>
