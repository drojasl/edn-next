<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../../api/apiClient'
import AdminPageHeader from '../../../components/admin/AdminPageHeader.vue'
import EntrepreneurForm from '../../../components/admin/user/EntrepreneurForm.vue'

import { type User, type ApiError } from '../../../types'

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const errorMessage = ref('')

const handleCreate = async (formData: User) => {
  loading.value = true
  errorMessage.value = ''

  try {
    const result = await apiRequest({
      method: 'POST',
      url: '/v1/admin/entrepreneurs',
      body: formData,
    })

    if (result.success) {
      router.push('/admin/users')
    } else {
      errorMessage.value = result.error?.message || t('common.error')
    }
  } catch (error: unknown) {
    const err = error as ApiError
    console.error('Creation error:', err)
    errorMessage.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto py-4 sm:py-0">
    <div class="mb-6">
      <button
        class="group flex items-center text-slate-500 hover:text-indigo-600 transition-colors font-medium text-sm"
        @click="router.push('/admin/users')"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 mr-1.5 transition-transform group-hover:-translate-x-1"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
            clip-rule="evenodd"
          />
        </svg>
        {{ $t('common.back_to_list') }}
      </button>
    </div>

    <AdminPageHeader :title="$t('admin.users.create_title')" class="mb-8" />

    <!-- Error Message -->
    <div
      v-if="errorMessage"
      class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-start gap-3 fade-in"
    >
      <svg
        class="w-5 h-5 mt-0.5 shrink-0 text-red-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <span>{{ errorMessage }}</span>
    </div>

    <div
      class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-8"
    >
      <EntrepreneurForm
        mode="create"
        :loading="loading"
        @submit="handleCreate"
        @cancel="router.push('/admin/users')"
      />
    </div>
  </div>
</template>
