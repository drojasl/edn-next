import type { CourseNodeData } from './courses'

export interface FlowPosition {
  x: number
  y: number
}

export interface FlowNode {
  id: string
  type: string
  position: FlowPosition
  data: CourseNodeData & {
    isStart: boolean
    isEnd: boolean
  }
}

export interface FlowEdge {
  id: string
  source: string
  target: string
  label?: string
  type?: string
  animated?: boolean
  sourceHandle?: string | null
}

export interface FlowNodeChange {
  id: number
  pos_x: number
  pos_y: number
}
