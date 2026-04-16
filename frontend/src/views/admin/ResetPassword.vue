<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseButton from '../../components/common/BaseButton.vue'
import LanguageSwitcher from '../../components/common/LanguageSwitcher.vue'
import { apiRequest } from '../../api/apiClient'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const email = ref('')
const token = ref('')
const password = ref('')
const password_confirmation = ref('')
const loading = ref(false)
const success = ref(false)
const errorMessage = ref('')

onMounted(() => {
  email.value = (route.query.email as string) || ''
  token.value = (route.query.token as string) || ''

  if (!email.value || !token.value) {
    errorMessage.value = 'Enlace de recuperación inválido.'
  }
})

const handleResetPassword = async () => {
  if (password.value !== password_confirmation.value) {
    errorMessage.value = t('auth.errors.password_mismatch')
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const result = await apiRequest({
      method: 'POST',
      url: '/auth/reset-password',
      body: {
        token: route.query.token,
        email: route.query.email,
        password: password.value,
        password_confirmation: password_confirmation.value,
      },
    })

    if (result.success) {
      success.value = true
      setTimeout(() => router.push('/admin/login'), 3000)
    } else {
      errorMessage.value = result.error?.message || t('auth.errors.serverError')
    }
  } catch (error) {
    errorMessage.value = t('auth.errors.serverError')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 relative"
  >
    <!-- Language Switcher -->
    <div class="absolute top-6 right-6">
      <LanguageSwitcher />
    </div>

    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">
            {{ t('auth.resetPassword.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ t('auth.resetPassword.subtitle') }}
          </p>
        </div>

        <div
          v-if="success"
          class="bg-green-50 border border-green-200 rounded-lg p-6 text-center"
        >
          <div class="text-green-600 text-4xl mb-4">✅</div>
          <p class="text-green-800 font-medium mb-4">
            {{ t('auth.resetPassword.success') }}
          </p>
          <p class="text-sm text-gray-600">
            Redirigiendo al inicio de sesión...
          </p>
        </div>

        <form v-else class="space-y-6" @submit.prevent="handleResetPassword">
          <div
            v-if="errorMessage"
            class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
          >
            {{ errorMessage }}
          </div>

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
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              :placeholder="t('auth.login.passwordPlaceholder')"
              :disabled="loading"
            />
          </div>

          <div>
            <label
              for="password_confirmation"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ t('auth.register.confirmPassword') }}
            </label>
            <input
              id="password_confirmation"
              v-model="password_confirmation"
              type="password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              :placeholder="t('auth.register.confirmPassword')"
              :disabled="loading"
            />
          </div>

          <BaseButton
            :text="t('auth.resetPassword.submit')"
            :action="handleResetPassword"
            :extra-props="{
              loading: loading,
              type: 'submit',
            }"
          />
        </form>
      </div>
    </div>
  </div>
</template>
