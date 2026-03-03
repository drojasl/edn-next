<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { apiRequest } from '../../../api/apiClient'
import EntrepreneurForm from '../../../components/admin/user/EntrepreneurForm.vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const userId = route.params.id
const loading = ref(false)
const fetching = ref(true)
const entrepreneur = ref<any>(null)
const errorMessage = ref('')

const fetchEntrepreneur = async () => {
  fetching.value = true
  try {
    const result = await apiRequest({
      method: 'GET',
      url: `/v1/admin/entrepreneurs/${userId}`
    })
    
    if (result.success) {
      entrepreneur.value = result.data
    } else {
      errorMessage.value = result.error?.message || 'Error fetching entrepreneur'
    }
  } catch (error: any) {
    errorMessage.value = error.message
  } finally {
    fetching.value = false
  }
}

const handleUpdate = async (formData: any) => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const result = await apiRequest({
      method: 'PUT',
      url: `/v1/admin/entrepreneurs/${userId}`,
      body: formData
    })
    
    if (result.success) {
      router.push('/admin/users')
    } else {
      errorMessage.value = result.error?.message || 'Error updating entrepreneur'
    }
  } catch (error: any) {
    console.error('Update error:', error)
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchEntrepreneur)
</script>

<template>
  <div class="p-8 max-w-4xl mx-auto">
    <div class="mb-10">
      <button 
        @click="router.push('/admin/users')"
        class="flex items-center text-gray-500 hover:text-indigo-600 transition-colors mb-4"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        {{ t('common.back_to_list') || 'Volver al listado' }}
      </button>
      <h1 class="text-3xl font-bold text-gray-900">
        {{ t('admin.users.edit_title') || 'Editar Empresario' }}
      </h1>
    </div>

    <!-- Loading State -->
    <div v-if="fetching" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-20 flex justify-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error Message -->
    <div v-else-if="errorMessage" class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
      {{ errorMessage }}
    </div>

    <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <EntrepreneurForm 
        mode="edit" 
        :initial-data="entrepreneur"
        :loading="loading" 
        @submit="handleUpdate" 
        @cancel="router.push('/admin/users')"
      />
    </div>
  </div>
</template>
