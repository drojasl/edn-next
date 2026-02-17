export interface FlowPosition {
    x: number
    y: number
}

export interface CourseNodeOption {
    label: string
    next_node_id: number | null
}

export interface CourseNodeData {
    id: number
    course_id: number
    title: string
    type: 'video' | 'form' | 'menu' | 'info' | 'action'
    content: any
    video_url?: string
    pos_x: number
    pos_y: number
    is_start: boolean
    is_end: boolean
    options: CourseNodeOption[]
    slug: string
}

export interface FlowNodeChange {
    id: number
    pos_x: number
    pos_y: number
}

export interface CourseConnectionUpdate {
    id: number
    next_course_id: number | null
}
