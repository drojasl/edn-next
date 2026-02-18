<script setup lang="ts">
import { BaseEdge, EdgeLabelRenderer, getBezierPath, useVueFlow, type EdgeProps } from '@vue-flow/core'
import { computed } from 'vue'

const props = defineProps<EdgeProps>()

const { removeEdges } = useVueFlow()

const path = computed(() => getBezierPath(props))

const onRemove = () => {
    removeEdges([props.id])
}
</script>

<template>
    <!-- Use BaseEdge for the actual line -->
    <BaseEdge 
        :id="props.id" 
        :path="path[0]" 
        :marker-end="props.markerEnd" 
        :style="props.style"
        :class="[
            'vue-flow__edge-path transition-all duration-200',
            props.selected ? '!stroke-indigo-600 !stroke-[3px]' : 'stroke-slate-400'
        ]"
    />

    <!-- Use EdgeLabelRenderer for the "X" button so it stays in the middle and is interactive -->
    <EdgeLabelRenderer>
        <div
            v-if="props.selected"
            :style="{
                position: 'absolute',
                transform: `translate(-50%, -50%) translate(${path[1]}px, ${path[2]}px)`,
                pointerEvents: 'all',
            }"
            class="nodrag nopan"
        >
            <button
                class="w-6 h-6 bg-white border-2 border-red-500 text-red-500 rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-all transform hover:scale-110 shadow-md group"
                @click.stop="onRemove"
                title="Eliminar conexión"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </EdgeLabelRenderer>
</template>

<style scoped>
.vue-flow__edge-path {
    stroke-dasharray: 5;
    animation: dash 20s linear infinite;
}

@keyframes dash {
    from {
        stroke-dashoffset: 1000;
    }
    to {
        stroke-dashoffset: 0;
    }
}
</style>
