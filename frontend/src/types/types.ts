/**
 * Generic API types
 */
export interface ApiError {
  message: string
  code?: string | number
  details?: unknown
}

export interface ApiResponse<T = unknown> {
  success: boolean
  data: T | null
  error: ApiError | null
}

export interface RequestParams {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  url: string
  body?: unknown
  headers?: Record<string, string>
}

/**
 * Auth & User types
 */
export interface SocialLink {
  platform: string
  value: string
}

export interface User {
  id: number
  name: string
  last_name: string
  codigo_amway: string
  is_account_holder: boolean
  email?: string
  slug?: string
  is_active?: boolean
  profile_picture?: string | null
  social_links?: SocialLink[]
  abo_link?: string
  client_link?: string
  my_digital_store?: string
}

export interface AuthResponse {
  user: User
  token: string
}

/**
 * Course & Flow types
 */
export interface FlowPosition {
  x: number
  y: number
}

export interface Course {
  id: number
  title: string
  description?: string
  next_course_id: number | null
  next_course_label: string | null
  pos_x: number
  pos_y: number
}

export interface CourseNodeOption {
  id?: number
  label: string
  next_node_id: number | null
}

export interface AccessCode {
  id: number
  code: string
  course_id: number
  user_id: number
  expires_at: string | null
  is_active: boolean
  created_at: string
  course?: Course
  user?: { id: number; name: string; last_name: string }
}

export interface CourseNodeField {
  name: string
  label: string
  type: string
  required: boolean
  min?: number
  max?: number
  icon?: string
}

export interface NodeContent {
  description?: string
  fields?: CourseNodeField[]
  buttons?: string[]
  body?: string
  submit_label?: string
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

export interface CourseNodeData {
  id: number
  course_id: number
  title: string
  type: 'video' | 'form' | 'menu' | 'info' | 'action' | string
  content?: NodeContent
  video_url?: string | null
  playback_speed?: string | number | null
  meeting_link?: string | null
  show_description: boolean
  pos_x: number
  pos_y: number
  is_start: boolean
  is_end: boolean
  options: CourseNodeOption[]
  slug: string
  position?: number
}

export interface NodeData {
  id?: number
  title: string
  type: 'video' | 'form' | 'menu' | string
  video_url?: string | null
  playback_speed?: number | string
  meeting_link?: string | null
  show_description: boolean
  content?: NodeContent
}

export interface FlowNodeChange {
  id: number
  pos_x: number
  pos_y: number
}

export interface CourseConnectionUpdate {
  id: number
  next_course_id: number | null
  next_course_label: string | null
}

/**
 * Prospect types
 */
export interface Prospect {
  id: number
  name: string
  phone: string
  email: string
  city?: string
  country?: string
  is_reviewed: boolean
  created_at: string
}

/**
 * Other common types
 */
export type ToastType = 'success' | 'error' | 'info' | 'warning'
