<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { apiRequest } from '../../../api/apiClient'
import BaseButton from '../../../components/common/BaseButton.vue'
import AdminPageHeader from '../../../components/admin/AdminPageHeader.vue'
import AdminDataTable from '../../../components/admin/AdminDataTable.vue'

import { type User, type ApiError } from '../../../types'

const { t } = useI18n()
const router = useRouter()

const users = ref<User[]>([])
const loading = ref(true)
const errorMessage = ref('')

const fetchUsers = async () => {
  loading.value = true
  try {
    const result = await apiRequest<User[]>({
      method: 'GET',
      url: '/v1/admin/entrepreneurs',
    })
    if (result.success && result.data) {
      users.value = result.data
    } else {
      errorMessage.value = result.error?.message || t('common.error')
    }
  } catch (error: unknown) {
    const err = error as ApiError
    errorMessage.value = err.message
  } finally {
    loading.value = false
  }
}

const deleteUser = async (id: number) => {
  if (!confirm(t('admin.users.confirm_delete'))) return

  try {
    const result = await apiRequest({
      method: 'DELETE',
      url: `/v1/admin/entrepreneurs/${id}`,
    })
    if (result.success) {
      users.value = users.value.filter((u) => u.id !== id)
    }
  } catch (error: unknown) {
    const err = error as ApiError
    alert(err.message)
  }
}

onMounted(fetchUsers)
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto">
    <AdminPageHeader
      :title="$t('admin.users.title')"
      :description="$t('admin.users.subtitle')"
    >
      <template #actions>
        <BaseButton
          :text="$t('admin.users.add_new')"
          :action="() => router.push('/admin/users/create')"
        />
      </template>
    </AdminPageHeader>

    <!-- Error Message -->
    <div
      v-if="errorMessage"
      class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"
    >
      {{ errorMessage }}
    </div>

    <!-- Users Table -->
    <AdminDataTable
      :headers="[
        { label: $t('admin.users.table.entrepreneur') },
        { label: $t('admin.users.table.amway_code') },
        { label: $t('admin.users.table.email') },
        { label: $t('admin.users.table.status') },
        { label: $t('admin.users.table.actions'), class: 'text-right' },
      ]"
      :loading="loading"
      :has-data="users.length > 0"
      :empty-message="$t('admin.users.no_users')"
    >
      <tr
        v-for="user in users"
        :key="user.id"
        class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0"
      >
        <td class="px-6 py-4">
          <div class="flex flex-col">
            <span class="font-bold text-slate-900"
              >{{ user.name }} {{ user.last_name }}</span
            >
            <span class="text-xs text-slate-500 font-medium tracking-tight"
              >slug: {{ user.slug }}</span
            >
          </div>
        </td>
        <td class="px-6 py-4">
          <div class="flex flex-col">
            <span class="text-slate-700 font-medium">{{
              user.codigo_amway
            }}</span>
            <span class="text-xs text-slate-500">{{
              user.is_account_holder
                ? $t('admin.users.role.holder')
                : $t('admin.users.role.coholder')
            }}</span>
          </div>
        </td>
        <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
          {{ user.email }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span
            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold tracking-wide"
            :class="
              user.is_active
                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'
                : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
            "
          >
            {{
              user.is_active
                ? $t('admin.users.status.active')
                : $t('admin.users.status.inactive')
            }}
          </span>
        </td>
        <td class="px-6 py-4 text-right whitespace-nowrap">
          <div class="flex justify-end gap-2">
            <button
              class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all focus:outline-none"
              :aria-label="$t('common.edit')"
              @click="router.push(`/admin/users/${user.id}/edit`)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
            </button>
            <button
              class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all focus:outline-none"
              :aria-label="$t('common.delete')"
              @click="deleteUser(user.id)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </td>
      </tr>
    </AdminDataTable>
  </div>
</template>
