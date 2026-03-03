<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { apiRequest } from '../../../api/apiClient'
import BaseButton from '../../../components/common/BaseButton.vue'

const { t } = useI18n()
const router = useRouter()

const users = ref<any[]>([])
const loading = ref(true)
const errorMessage = ref('')

const fetchUsers = async () => {
  loading.value = true
  try {
    const result = await apiRequest({
      method: 'GET',
      url: '/v1/admin/entrepreneurs'
    })
    if (result.success) {
      users.value = result.data
    } else {
      errorMessage.value = result.error?.message || 'Error fetching users'
    }
  } catch (error: any) {
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}

const deleteUser = async (id: number) => {
  if (!confirm(t('admin.users.confirm_delete') || '¿Estás seguro de eliminar este empresario?')) return

  try {
    const result = await apiRequest({
      method: 'DELETE',
      url: `/v1/admin/entrepreneurs/${id}`
    })
    if (result.success) {
      users.value = users.value.filter(u => u.id !== id)
    }
  } catch (error: any) {
    alert(error.message)
  }
}

onMounted(fetchUsers)
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-10">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ t('admin.users.title') || 'Gestión de Empresarios' }}
        </h1>
        <p class="text-gray-600 mt-2">
          {{ t('admin.users.subtitle') || 'Administra las cuentas de empresarios en la plataforma' }}
        </p>
      </div>
      <BaseButton 
        :text="t('admin.users.add_new') || 'Nuevo Empresario'" 
        :action="() => router.push('/admin/users/create')" 
      />
    </div>

    <!-- Error Message -->
    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
      {{ errorMessage }}
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="p-20 flex justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else-if="users.length === 0" class="p-20 text-center">
        <p class="text-gray-500 text-lg">
          {{ t('admin.users.no_users') || 'No hay empresarios registrados aún.' }}
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Empresario</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Código Amway</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="font-medium text-gray-900">{{ user.name }} {{ user.last_name }}</span>
                  <span class="text-xs text-gray-500">slug: {{ user.slug }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="text-gray-700">{{ user.codigo_amway }}</span>
                  <span class="text-xs text-gray-500">{{ user.is_account_holder ? 'Titular' : 'Cotitular' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-700">{{ user.email }}</td>
              <td class="px-6 py-4">
                <span 
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ user.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-3">
                  <button 
                    @click="router.push(`/admin/users/${user.id}/edit`)"
                    class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button 
                    @click="deleteUser(user.id)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
