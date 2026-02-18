<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'
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
}>(), {
    showSaveButton: false,
    nodeExtent: undefined
})

const emit = defineEmits(['save', 'node-change', 'connection-change', 'action'])

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

watch(() => [props.initialNodes, props.initialEdges] as const, async (newVal) => {
    const [newNodes, newEdges] = newVal
    
    // Check if nodes changed significantly
    const nodesChanged = JSON.stringify(newNodes) !== JSON.stringify(nodes.value)
    
    if (nodesChanged) {
        console.log('Nodes changed, re-initializing flow.')
        isInitializing.value = true
        nodes.value = newNodes
        edges.value = []
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 100)) // Give Vue Flow time to register new nodes
        edges.value = newEdges
        await nextTick()
        isInitializing.value = false
    } else if (JSON.stringify(newEdges) !== JSON.stringify(edges.value)) {
        console.log('Edges changed, updating edges.')
        // Just update edges if nodes are standard
        edges.value = newEdges
    }
}, { deep: true })

// Emit individual node changes (for position)
onNodeDragStop(({ node }) => {
    emit('node-change', {
        id: parseInt(node.id),
        pos_x: Math.round(node.position.x),
        pos_y: Math.round(node.position.y)
    })
})

// Handle new connections
onConnect(async (params: Connection) => {
    const customEdge = {
        ...params,
        type: 'custom',
        animated: true
    } as Edge
    addEdges([customEdge])
    await nextTick()
    emit('connection-change', { nodes: nodes.value, edges: edges.value })
})

// Handle edge updates (moving an existing connection)
onEdgeUpdate(async ({ edge, connection }) => {
    const updatedConnection = {
        ...connection,
        type: 'custom',
        animated: true
    } as Connection
    updateEdge(edge, updatedConnection)
    await nextTick()
    emit('connection-change', { nodes: nodes.value, edges: edges.value })
})

// Handle edge clicks (optional, currently not used)
onEdgesChange(async (changes) => {
    const hasRemoval = changes.some(c => c.type === 'remove')
    if (hasRemoval && !isInitializing.value) {
        await nextTick()
        emit('connection-change', { nodes: nodes.value, edges: edges.value })
    }
})

// Validation: Restricted to 1 outgoing connection
const isValidConnection = (connection: Connection) => {
    // During initialization, everything is valid (since we're loading from DB)
    if (isInitializing.value) return true

    // Check if source already has a DIFFERENT outgoing edge
    // We filter by source and check if any existing edge has a different target
    const sourceHasAnotherEdge = edges.value.some(edge => 
        edge.source === connection.source && edge.target !== connection.target
    )
    
    return !sourceHasAnotherEdge
}

const onSave = () => {
    emit('save', {
        nodes: nodes.value,
        edges: edges.value
    })
}

defineExpose({
    nodes,
    edges
})
</script>

<template>
    <div class="h-full w-full relative">
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
                <ConnectionEdge v-bind="edgeProps" />
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
