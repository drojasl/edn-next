<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '../../components/common/BaseButton.vue'
import LanguageSwitcher from '../../components/common/LanguageSwitcher.vue'
import { apiRequest } from '../../api/apiClient'

const { t, locale } = useI18n()

const email = ref('')
const loading = ref(false)
const success = ref(false)
const errorMessage = ref('')

const handleForgotPassword = async () => {
  if (!email.value) return

  loading.value = true
  errorMessage.value = ''

  try {
    const result = await apiRequest({
      method: 'POST',
      url: '/auth/forgot-password',
      body: {
        email: email.value,
        locale: locale.value,
      },
    })

    if (result.success) {
      success.value = true
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
            {{ t('auth.forgotPassword.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ t('auth.forgotPassword.subtitle') }}
          </p>
        </div>

        <div
          v-if="success"
          class="bg-green-50 border border-green-200 rounded-lg p-6 text-center"
        >
          <div class="text-green-600 text-4xl mb-4">📧</div>
          <p class="text-green-800 font-medium mb-4">
            {{ t('auth.forgotPassword.success') }}
          </p>
          <router-link
            to="/admin/login"
            class="text-indigo-600 font-semibold hover:text-indigo-500"
          >
            {{ t('auth.forgotPassword.backToLogin') }}
          </router-link>
        </div>

        <form v-else class="space-y-6" @submit.prevent="handleForgotPassword">
          <div
            v-if="errorMessage"
            class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
          >
            {{ errorMessage }}
          </div>

          <div>
            <label
              for="email"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              {{ t('admin.users.email') }}
            </label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              placeholder="ejemplo@correo.com"
              :disabled="loading"
            />
          </div>

          <BaseButton
            :text="t('auth.forgotPassword.submit')"
            :action="handleForgotPassword"
            :extra-props="{
              loading: loading,
              type: 'submit',
            }"
          />

          <div class="text-center mt-4">
            <router-link
              to="/admin/login"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
              {{ t('auth.forgotPassword.backToLogin') }}
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
