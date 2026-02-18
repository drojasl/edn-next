<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseFlowEditor from './flow/BaseFlowEditor.vue'
import { EDITOR_CONFIG } from '../../config/constants'

interface Course {
    id: number
    title: string
    next_course_id: number | null
    next_course_label: string | null
    pos_x: number
    pos_y: number
}

const props = defineProps<{
    courses: Course[]
    isSaving?: boolean
}>()

const emit = defineEmits(['save', 'position-change', 'action'])

const initialNodes = ref<any[]>([])
const initialEdges = ref<any[]>([])

// Define extent from config
const nodeExtent = [[EDITOR_CONFIG.bounds.minX, EDITOR_CONFIG.bounds.minY], [EDITOR_CONFIG.bounds.maxX, EDITOR_CONFIG.bounds.maxY]] as [[number, number], [number, number]]

// Transform props to Vue Flow format
const initFlow = () => {
    initialNodes.value = props.courses.map((course, index) => {
        const hasPos = course.pos_x !== 0 || course.pos_y !== 0
        return {
            id: course.id.toString(),
            type: 'custom', // Use generic FlowNode
            position: { 
                x: hasPos ? course.pos_x : 50 + (index % 3) * 300, 
                y: hasPos ? course.pos_y : 50 + Math.floor(index / 3) * 200 
            },
            data: {
                ...course,
                isCourse: true // Flag for FlowNode to render single output
            }
        }
    })

    initialEdges.value = props.courses
        .filter(c => c.next_course_id)
        .map(c => ({
            id: `e-${c.id}-${c.next_course_id}`,
            source: c.id.toString(),
            target: c.next_course_id!.toString(),
            label: c.next_course_label || 'Next',
            type: 'custom',
            animated: true
        }))
}

watch(() => props.courses, initFlow, { immediate: true })

const handleNodeChange = (data: { id: number, pos_x: number, pos_y: number }) => {
    emit('position-change', data)
}

// Handle bulk save (connections)
const handleSave = ({ nodes, edges }: { nodes: any[], edges: any[] }) => {
    // Transform edges back to "next_course_id" map
    const nextCourseMap = new Map<number, { next_course_id: number, next_course_label: string }>()
    
    edges.forEach(edge => {
        const sourceId = parseInt(edge.source)
        const targetId = parseInt(edge.target)
        nextCourseMap.set(sourceId, { 
            next_course_id: targetId, 
            next_course_label: edge.label || 'Next' 
        })
    })

    const updates = nodes.map(node => {
        const id = parseInt(node.id)
        const connection = nextCourseMap.get(id)
        return {
            id,
            next_course_id: connection?.next_course_id || null,
            next_course_label: connection?.next_course_label || null
        }
    })

    emit('save', updates)
}
</script>

<template>
    <div class="w-full h-full bg-slate-50">
        <BaseFlowEditor
            :initialNodes="initialNodes"
            :initialEdges="initialEdges"
            :node-extent="nodeExtent"
            :is-saving="isSaving"
            @save="handleSave"
            @connection-change="handleSave"
            @node-change="handleNodeChange"
            @action="emit('action', $event)"
        />
    </div>
</template>
