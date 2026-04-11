import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiRequest } from '../api/apiClient'
import type { User, AuthResponse, ApiError } from '../types/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref(localStorage.getItem('token') || '')
  const router = useRouter()

  async function login(credentials: Record<string, unknown>) {
    const result = await apiRequest<AuthResponse>({
      method: 'POST',
      url: '/auth/login',
      body: credentials,
    })

    if (result.success && result.data) {
      token.value = result.data.token
      user.value = result.data.user
      localStorage.setItem('token', token.value)
      router.push('/admin')
    } else {
      console.error('Login failed', result.error)
      const error = new Error(
        result.error?.message || 'Login failed'
      ) as Error & ApiError
      error.code = result.error?.code
      throw error
    }
  }

  async function register(userData: Record<string, unknown>) {
    const result = await apiRequest<AuthResponse>({
      method: 'POST',
      url: '/register',
      body: userData,
    })

    if (result.success && result.data) {
      token.value = result.data.token
      user.value = result.data.user
      localStorage.setItem('token', token.value)
      router.push('/admin')
    } else {
      console.error('Registration failed', result.error)
      throw new Error(result.error?.message || 'Registration failed')
    }
  }

  async function logout() {
    await apiRequest({
      method: 'POST',
      url: '/logout',
    })

    token.value = ''
    user.value = null
    localStorage.removeItem('token')
    router.push('/admin/login')
  }

  async function fetchUser() {
    if (!token.value) return

    const result = await apiRequest<User>({
      method: 'GET',
      url: '/user',
    })

    if (result.success) {
      user.value = result.data
    } else {
      token.value = ''
      user.value = null
      localStorage.removeItem('token')
    }
  }

  return { user, token, login, register, logout, fetchUser }
})
