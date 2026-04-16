<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import BaseButton from '../../components/common/BaseButton.vue'
import LanguageSwitcher from '../../components/common/LanguageSwitcher.vue'
import type { ApiError } from '../../types'

const { t } = useI18n()
const authStore = useAuthStore()

// Form data
const codigoAmway = ref('')
const isTitular = ref(true)
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

// Validation errors
const errors = ref({
  codigoAmway: '',
  password: '',
})

// Form validation
const validateForm = (): boolean => {
  errors.value = {
    codigoAmway: '',
    password: '',
  }

  let isValid = true

  if (!codigoAmway.value.trim()) {
    errors.value.codigoAmway = t('auth.errors.required')
    isValid = false
  }

  if (!password.value) {
    errors.value.password = t('auth.errors.required')
    isValid = false
  } else if (password.value.length < 6) {
    errors.value.password = t('auth.errors.min_length')
    isValid = false
  }

  return isValid
}

// Handle login
const handleLogin = async () => {
  errorMessage.value = ''

  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    await authStore.login({
      amway_code: codigoAmway.value.trim(),
      is_account_holder: isTitular.value,
      password: password.value,
    })
  } catch (error: unknown) {
    const err = error as ApiError & { code?: number }
    console.error('Login error:', err)
    if (err.code === 401) {
      errorMessage.value = t('auth.errors.invalidCredentials')
    } else {
      errorMessage.value = err.message || t('auth.errors.serverError')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4"
  >
    <div class="w-full max-w-md">
      <!-- Language Switcher -->
      <div class="flex justify-end mb-4">
        <LanguageSwitcher />
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">
            {{ t('auth.login.title') }}
          </h1>
        </div>

        <!-- Error Message -->
        <div
          v-if="errorMessage"
          class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
        >
          {{ errorMessage }}
        </div>

        <form class="space-y-6" @submit.prevent="handleLogin">
          <!-- Código Amway -->
          <div>
            <label
              for="codigoAmway"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ t('auth.login.amwayCode') }}
            </label>
            <input
              id="codigoAmway"
              v-model="codigoAmway"
              type="text"
              :placeholder="t('auth.login.amwayCodePlaceholder')"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              :class="{ 'border-red-500': errors.codigoAmway }"
              :disabled="loading"
            />
            <p v-if="errors.codigoAmway" class="mt-1 text-sm text-red-600">
              {{ errors.codigoAmway }}
            </p>
          </div>

          <!-- Checkbox Titular/Cotitular -->
          <div class="flex items-center">
            <input
              id="isTitular"
              v-model="isTitular"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              :disabled="loading"
            />
            <label
              for="isTitular"
              class="ml-3 text-sm font-medium text-gray-700"
            >
              {{ t('auth.login.isTitular') }}
            </label>
          </div>

          <!-- Password -->
          <div>
            <label
              for="password"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ t('auth.login.password') }}
            </label>
            <input
              id="password"
              v-model="password"
              type="password"
              :placeholder="t('auth.login.passwordPlaceholder')"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              :class="{ 'border-red-500': errors.password }"
              :disabled="loading"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <!-- Forgot Password Link -->
          <div class="flex justify-end">
            <router-link
              to="/admin/forgot-password"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors"
            >
              {{ t('auth.login.forgotPassword') }}
            </router-link>
          </div>

          <!-- Submit Button -->
          <BaseButton
            :text="t('auth.login.submit')"
            :action="handleLogin"
            :extra-props="{
              loading: loading,
              loadingText: t('auth.login.loading'),
              type: 'submit',
            }"
          />
        </form>

        <!-- Register Link -->
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <p class="text-sm text-gray-600">
            {{ t('auth.login.noAccount') }}
            <router-link
              to="/admin/register"
              class="ml-2 font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
            >
              {{ t('auth.login.register') }}
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
