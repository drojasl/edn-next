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

export interface CourseConnectionUpdate {
  id: number
  next_course_id: number | null
  next_course_label: string | null
}
