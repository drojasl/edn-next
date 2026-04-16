<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import EntrepreneurForm from '../../components/admin/user/EntrepreneurForm.vue'
import LanguageSwitcher from '../../components/common/LanguageSwitcher.vue'

import { type User, type ApiError } from '../../types'

const { t, locale } = useI18n()
const authStore = useAuthStore()

const loading = ref(false)
const errorMessage = ref('')

const handleRegister = async (formData: User) => {
  loading.value = true
  errorMessage.value = ''

  try {
    // Map form data to what backend expects if necessary
    const userData = {
      ...formData,
      codigo_amway: formData.codigo_amway, // Maintain consistency with User interface
      locale: locale.value,
    }

    await authStore.register(userData)
    // Auth store handles redirect on success
  } catch (error: unknown) {
    const err = error as ApiError
    console.error('Registration error:', err)
    errorMessage.value = err.message || t('auth.errors.serverError')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 py-12 relative"
  >
    <!-- Language Switcher -->
    <div class="absolute top-6 right-6">
      <LanguageSwitcher />
    </div>

    <div class="w-full max-w-2xl">
      <!-- Register Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10">
        <div class="text-center mb-10">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">
            {{ t('auth.register.title') }}
          </h1>
          <p class="text-gray-600">
            {{ t('auth.register.subtitle') }}
          </p>
        </div>

        <!-- Error Message -->
        <div
          v-if="errorMessage"
          class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 mr-3"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          {{ errorMessage }}
        </div>

        <EntrepreneurForm
          mode="register"
          :loading="loading"
          @submit="handleRegister"
        />

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <p class="text-sm text-gray-600">
            {{ t('auth.register.hasAccount') }}
            <router-link
              to="/admin/login"
              class="ml-2 font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
            >
              {{ t('auth.login.submit') }}
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
