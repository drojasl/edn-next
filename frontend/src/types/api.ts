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
