import axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  AxiosError,
} from 'axios'

/**
 * Interface for the API response
 */
export interface ApiResponse<T = any> {
  success: boolean
  data: T | null
  error: {
    message: string
    code?: string | number
    details?: any
  } | null
}

/**
 * Interface for request parameters
 */
interface RequestParams {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  url: string
  body?: any
  headers?: Record<string, string>
}

// Create axios instance
const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Request Interceptor: Add Authorization header if token exists
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Let the browser set the Content-Type with boundary for FormData
    if (config.data instanceof FormData && config.headers) {
      delete config.headers['Content-Type']
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Global error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Here we can handle specific global errors (e.g., 401 Unauthorized)
    if (error.response?.status === 401) {
      console.warn('Unauthorized access - potential token expiration')
      // Optional: Clear token and redirect to login
      // localStorage.removeItem('token');
      // window.location.href = '/admin/login';
    }

    return Promise.reject(error)
  }
)

/**
 * Centralized request function
 */
export const apiRequest = async <T = any>({
  method,
  url,
  body,
  headers,
}: RequestParams): Promise<ApiResponse<T>> => {
  try {
    const config: AxiosRequestConfig = {
      method,
      url,
      data: body,
      headers: headers,
    }

    const response = await apiClient.request<T>(config)

    return {
      success: true,
      data: response.data,
      error: null,
    }
  } catch (error: any) {
    const axiosError = error as AxiosError<any>

    return {
      success: false,
      data: null,
      error: {
        message:
          axiosError.response?.data?.message ||
          axiosError.message ||
          'Unknown error occurred',
        code: axiosError.response?.status,
        details: axiosError.response?.data,
      },
    }
  }
}

export default apiClient
