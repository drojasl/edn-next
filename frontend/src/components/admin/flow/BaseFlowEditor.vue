<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { VueFlow, useVueFlow, type Node, type Edge, type Connection } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import FlowNode from './FlowNode.vue'
import ConnectionEdge from './ConnectionEdge.vue'

// Import styles
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

const props = withDefaults(defineProps<{
    initialNodes: Node[]
    initialEdges: Edge[]
    showSaveButton?: boolean
    nodeExtent?: [[number, number], [number, number]]
    allowMultipleOutputs?: boolean
    isSaving?: boolean
}>(), {
    showSaveButton: false,
    nodeExtent: undefined,
    allowMultipleOutputs: false,
    isSaving: false
})

const emit = defineEmits(['save', 'node-change', 'connection-change', 'action'])

const { t } = useI18n()

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const isInitializing = ref(true)

const { onConnect, addEdges, onNodeDragStop, onEdgeUpdate, onEdgesChange, updateEdge } = useVueFlow()

onMounted(async () => {
    // 1. Set nodes first
    nodes.value = props.initialNodes
    
    // 2. Wait for Vue to update DOM and Vue Flow to register nodes
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 50))
    
    // 3. Set edges only after nodes are ready
    edges.value = props.initialEdges
    await nextTick()
    isInitializing.value = false
})

const isInternalUpdate = ref(false)

watch(() => [props.initialNodes, props.initialEdges] as const, async (newVal) => {
    if (isInternalUpdate.value) return

    const [newNodes, newEdges] = newVal
    
    // More precise change detection
    const oldIds = nodes.value.map(n => n.id).sort().join(',')
    const newIds = newNodes.map(n => n.id).sort().join(',')
    
    const structuralChange = oldIds !== newIds || 
                           nodes.value.length !== newNodes.length ||
                           newNodes.some((n, i) => n.type !== nodes.value[i]?.type)

    if (structuralChange) {
        console.log('Nodes structure changed, re-initializing flow.')
        isInitializing.value = true
        nodes.value = JSON.parse(JSON.stringify(newNodes))
        edges.value = []
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 100))
        edges.value = JSON.parse(JSON.stringify(newEdges))
        await nextTick()
        isInitializing.value = false
    } else {
        // Just update metadata in place
        newNodes.forEach(newNode => {
            const existingNode = nodes.value.find(n => n.id === newNode.id)
            if (existingNode) {
                // Update position only if it's significantly different
                if (Math.abs(existingNode.position.x - newNode.position.x) > 1 || 
                    Math.abs(existingNode.position.y - newNode.position.y) > 1) {
                    existingNode.position = { ...newNode.position }
                }
                // Update data (flags, options, etc)
                existingNode.data = { ...newNode.data }
            }
        })

        // Edge comparison: simplified to avoid recursive triggers 
        // from Vue Flow's internal reactive additions
        const simplifiedOldEdges = edges.value.map(e => `${e.source}-${e.target}-${e.label}`)
        const simplifiedNewEdges = newEdges.map(e => `${e.source}-${e.target}-${e.label}`)
        
        if (JSON.stringify(simplifiedOldEdges) !== JSON.stringify(simplifiedNewEdges)) {
            edges.value = JSON.parse(JSON.stringify(newEdges))
        }
    }
}, { deep: true })

// Helper to emit changes without triggering the watcher loop
const wrapInternalUpdate = async (fn: () => Promise<void> | void) => {
    isInternalUpdate.value = true
    try {
        await fn()
    } finally {
        // Use timeout to ensure the watcher's microtask queue is cleared
        await nextTick()
        setTimeout(() => {
            isInternalUpdate.value = false
        }, 50)
    }
}

// Emit individual node changes (for position)
onNodeDragStop(({ node }) => {
    wrapInternalUpdate(() => {
        emit('node-change', {
            id: parseInt(node.id),
            pos_x: Math.round(node.position.x),
            pos_y: Math.round(node.position.y)
        })
    })
})

// Handle new connections
onConnect(async (params: Connection) => {
    // Validate connection before adding
    if (!isValidConnection(params)) {
        return
    }

    const customEdge = {
        ...params,
        label: t('course.editor.default_connection_label'),
        type: 'custom',
        animated: true
    } as Edge
    
    addEdges([customEdge])
    await nextTick()
    
    wrapInternalUpdate(() => {
        emit('connection-change', { 
            nodes: JSON.parse(JSON.stringify(nodes.value)), 
            edges: JSON.parse(JSON.stringify(edges.value)) 
        })
    })
})

// Handle edge updates
onEdgeUpdate(async ({ edge, connection }) => {
    const updatedConnection = {
        ...connection,
        type: 'custom',
        animated: true
    } as Connection
    
    updateEdge(edge, updatedConnection)
    await nextTick()
    
    wrapInternalUpdate(() => {
        emit('connection-change', { 
            nodes: JSON.parse(JSON.stringify(nodes.value)), 
            edges: JSON.parse(JSON.stringify(edges.value)) 
        })
    })
})

// Handle edge removal
onEdgesChange(async (changes) => {
    const hasRemoval = changes.some(c => c.type === 'remove')
    if (hasRemoval && !isInitializing.value) {
        await nextTick()
        wrapInternalUpdate(() => {
            emit('connection-change', { 
                nodes: JSON.parse(JSON.stringify(nodes.value)), 
                edges: JSON.parse(JSON.stringify(edges.value)) 
            })
        })
    }
})

// Validation
const isValidConnection = (connection: Connection) => {
    if (isInitializing.value) return true
    if (props.allowMultipleOutputs) return true
    const sourceHasAnotherEdge = edges.value.some(edge => 
        edge.source === connection.source && edge.target !== connection.target
    )
    return !sourceHasAnotherEdge
}

const onSave = () => {
    emit('save', {
        nodes: JSON.parse(JSON.stringify(nodes.value)),
        edges: JSON.parse(JSON.stringify(edges.value))
    })
}

const handleLabelChange = async (edgeId: string, newLabel: string) => {
    const edge = edges.value.find(e => e.id === edgeId)
    if (edge && edge.label !== newLabel) {
        edge.label = newLabel
        await nextTick()
        emit('connection-change', { 
            nodes: JSON.parse(JSON.stringify(nodes.value)), 
            edges: JSON.parse(JSON.stringify(edges.value)) 
        })
    }
}

defineExpose({
    nodes,
    edges
})
</script>

<template>
    <div class="h-full w-full relative">
        <!-- Blocking Overlay -->
        <div 
            v-if="isSaving" 
            class="absolute inset-0 bg-white/20 backdrop-blur-[1px] z-50 flex items-center justify-center cursor-wait"
            @click.stop
            @mousedown.stop
            @touchstart.stop
        >
            <div class="bg-white/80 p-3 rounded-full shadow-lg border border-slate-200">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
            </div>
        </div>

        <VueFlow
            v-model:nodes="nodes"
            v-model:edges="edges"
            :default-viewport="{ zoom: 1 }"
            :min-zoom="0.2"
            :max-zoom="4"
            :node-extent="nodeExtent"
            :is-valid-connection="isValidConnection"
            fit-view-on-init
        >
            <Background />
            <Controls />
            <MiniMap />

            <template #node-custom="nodeProps">
                <FlowNode v-bind="nodeProps" @action="emit('action', $event)" />
            </template>

            <template #edge-custom="edgeProps">
                <ConnectionEdge 
                    v-bind="edgeProps" 
                    @label-change="(label: string) => handleLabelChange(edgeProps.id, label)"
                />
            </template>
        </VueFlow>

        <!-- Optional Manual Save -->
        <button 
            v-if="showSaveButton"
            @click="onSave"
            class="absolute top-4 right-4 z-10 px-4 py-2 bg-indigo-600 text-white rounded shadow-lg hover:bg-indigo-700 transition"
        >
            Guardar Cambios
        </button>
    </div>
</template>

<style>
.vue-flow__edge-path {
    stroke: #6366f1;
    stroke-width: 2;
}
</style>
