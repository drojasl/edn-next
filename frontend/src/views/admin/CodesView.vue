<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AppToast from '../../components/common/AppToast.vue'
import ConfirmationModal from '../../components/common/ConfirmationModal.vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminDataTable from '../../components/admin/AdminDataTable.vue'

import { type Course, type AccessCode } from '../../types/types'

const codes = ref<AccessCode[]>([])
const courses = ref<Course[]>([])
const isLoading = ref(true)

const { t, locale } = useI18n()
const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const modalRef = ref<InstanceType<typeof ConfirmationModal> | null>(null)

const showModal = ref(false)
const newCodeData = ref({
  course_id: '',
  code: '',
  expiration_days: '',
})

const codeAvailability = ref({
  checking: false,
  available: true,
  message: '',
})

const fetchCourses = async () => {
  try {
    const response = await apiRequest<{ data: Course[] }>({
      method: 'GET',
      url: '/v1/admin/courses',
    })
    if (response.success && response.data) {
      courses.value = response.data.data
    }
  } catch (e) {
    console.error('Error fetching courses:', e)
  }
}

const fetchCodes = async () => {
  isLoading.value = true
  try {
    const response = await apiRequest<{ data: AccessCode[] }>({
      method: 'GET',
      url: '/v1/admin/access-codes',
    })
    if (response.success && response.data) {
      codes.value = response.data.data
    }
  } catch (e) {
    console.error('Error fetching codes:', e)
  } finally {
    isLoading.value = false
  }
}

const generateRandomCode = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 6; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

const openGenerateModal = () => {
  newCodeData.value = {
    course_id: '',
    code: generateRandomCode(),
    expiration_days: '',
  }
  codeAvailability.value = { checking: false, available: true, message: '' }
  showModal.value = true
}

const checkCode = async () => {
  if (!newCodeData.value.code) {
    codeAvailability.value = {
      checking: false,
      available: false,
      message: t('admin.codes.check_required'),
    }
    return
  }

  codeAvailability.value.checking = true
  try {
    const result = await apiRequest<{ available: boolean }>({
      method: 'POST',
      url: '/v1/admin/access-codes/validate-code',
      body: { code: newCodeData.value.code },
    })

    if (result.success && result.data) {
      codeAvailability.value.available = result.data.available
      codeAvailability.value.message = result.data.available
        ? t('admin.codes.check_available')
        : t('admin.codes.check_taken')
    }
  } catch (error) {
    console.error('Error checking code:', error)
  } finally {
    codeAvailability.value.checking = false
  }
}

let codeTimeout: ReturnType<typeof setTimeout> | null = null
watch(
  () => newCodeData.value.code,
  () => {
    if (codeTimeout) clearTimeout(codeTimeout)
    codeTimeout = setTimeout(checkCode, 500)
  }
)

const handleGenerateCode = async () => {
  if (!newCodeData.value.course_id) {
    toastRef.value?.show(t('admin.codes.error_course'), 'error')
    return
  }
  if (!newCodeData.value.code || !codeAvailability.value.available) {
    toastRef.value?.show(t('admin.codes.error_invalid'), 'error')
    return
  }

  try {
    const response = await apiRequest<{ data: AccessCode }>({
      method: 'POST',
      url: '/v1/admin/access-codes',
      body: {
        course_id: newCodeData.value.course_id,
        code: newCodeData.value.code.toUpperCase(),
        expiration_days: newCodeData.value.expiration_days || null,
      },
    })
    if (response.success && response.data) {
      toastRef.value?.show(t('admin.codes.success_generate'), 'success')
      showModal.value = false
      fetchCodes()
    } else {
      toastRef.value?.show(
        response.error?.message || t('admin.codes.error_generate_fail'),
        'error'
      )
    }
  } catch (e: unknown) {
    const error = e as Error
    toastRef.value?.show(error.message || t('common.error'), 'error')
  }
}

const handleDeleteCode = (id: number) => {
  modalRef.value?.open({
    title: t('admin.codes.delete_title'),
    message: t('admin.codes.delete_confirm'),
    isDestructive: true,
    confirmText: t('admin.codes.delete_button'),
    onConfirm: async () => {
      try {
        const response = await apiRequest({
          method: 'DELETE',
          url: `/v1/admin/access-codes/${id}`,
        })
        if (response.success) {
          toastRef.value?.show(t('admin.codes.success_delete'), 'success')
          fetchCodes()
        } else {
          toastRef.value?.show(
            response.error?.message || t('admin.codes.error_delete'),
            'error'
          )
        }
      } catch (e) {
        console.error('Error deleting code:', e)
        toastRef.value?.show(t('common.error'), 'error')
      }
    },
  })
}

const copyToClipboard = async (text: string, successMsg: string) => {
  try {
    await navigator.clipboard.writeText(text)
    toastRef.value?.show(successMsg, 'success')
  } catch (err) {
    toastRef.value?.show(t('common.error'), 'error')
  }
}

const copyCode = (code: string) => {
  copyToClipboard(code, t('admin.codes.copied_code'))
}

const copyLink = (code: string) => {
  const url = `${window.location.origin}/cursos?cod=${code}`
  copyToClipboard(url, t('admin.codes.copied_link'))
}

onMounted(() => {
  fetchCourses()
  fetchCodes()
})

const formatDate = (dateString: string | null) => {
  if (!dateString) return t('common.never')
  return new Date(dateString).toLocaleDateString(
    locale.value === 'en' ? 'en-US' : 'es-ES',
    {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }
  )
}
</script>

<template>
  <div>
    <AdminPageHeader
      :title="$t('admin.codes.title')"
      :description="$t('admin.codes.description')"
    >
      <template #actions>
        <button
          class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2"
          @click="openGenerateModal"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          {{ $t('admin.codes.generate_new') }}
        </button>
      </template>
    </AdminPageHeader>

    <!-- Generate Code Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold text-slate-900">
            {{ $t('admin.codes.generate') }}
          </h2>
          <button
            class="text-slate-400 hover:text-slate-600"
            @click="showModal = false"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{
              $t('admin.codes.code_label')
            }}</label>
            <div class="relative">
              <input
                v-model="newCodeData.code"
                type="text"
                maxlength="6"
                class="w-full px-4 py-2 uppercase border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{
                  'border-red-500':
                    !codeAvailability.available && newCodeData.code,
                  'border-green-500':
                    codeAvailability.available &&
                    newCodeData.code &&
                    !codeAvailability.checking,
                }"
              />
              <div
                v-if="codeAvailability.checking"
                class="absolute right-3 top-2.5"
              >
                <div
                  class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600"
                ></div>
              </div>
            </div>
            <p
              v-if="newCodeData.code && !codeAvailability.checking"
              class="mt-1 text-sm inline-block"
              :class="
                codeAvailability.available ? 'text-green-600' : 'text-red-600'
              "
            >
              {{ codeAvailability.message }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{
              $t('admin.codes.course_assigned')
            }}</label>
            <select
              v-model="newCodeData.course_id"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('admin.codes.select_course') }}</option>
              <option
                v-for="course in courses"
                :key="course.id"
                :value="course.id"
              >
                {{ course.title }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{
              $t('admin.codes.expiration')
            }}</label>
            <select
              v-model="newCodeData.expiration_days"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('admin.codes.no_expire') }}</option>
              <option value="2">{{ $t('admin.codes.2_days') }}</option>
              <option value="7">{{ $t('admin.codes.7_days') }}</option>
              <option value="15">{{ $t('admin.codes.15_days') }}</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-8">
          <button
            class="px-4 py-2 text-slate-600 font-medium hover:bg-slate-50 rounded-lg transition-colors"
            @click="showModal = false"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="
              !codeAvailability.available ||
              codeAvailability.checking ||
              !newCodeData.code
            "
            @click="handleGenerateCode"
          >
            {{ $t('admin.codes.generate') }}
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Table Section -->
      <div class="lg:col-span-2">
        <AdminDataTable
          :headers="[
            { label: $t('admin.codes.table.code') },
            { label: $t('admin.codes.table.course') },
            { label: $t('admin.codes.table.expires') },
            { label: $t('admin.codes.table.actions'), class: 'text-right' },
          ]"
          :loading="isLoading"
          :has-data="codes.length > 0"
          :empty-message="$t('admin.codes.table.no_codes')"
        >
          <tr
            v-for="item in codes"
            :key="item.id"
            class="hover:bg-slate-50/30 transition-colors"
          >
            <td class="px-6 py-4 font-mono font-bold text-indigo-600 uppercase">
              {{ item.code }}
            </td>
            <td class="px-6 py-4 text-slate-700">
              {{ item.course?.title || $t('admin.codes.error_course_deleted') }}
            </td>
            <td class="px-6 py-4 text-slate-600">
              {{ formatDate(item.expires_at) }}
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                  :title="$t('admin.codes.copied_code')"
                  @click="copyCode(item.code)"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                    />
                  </svg>
                </button>
                <button
                  class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all"
                  :title="$t('admin.codes.copied_link')"
                  @click="copyLink(item.code)"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                    />
                  </svg>
                </button>
                <button
                  class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                  :title="$t('admin.management.delete')"
                  @click="handleDeleteCode(item.id)"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
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

      <!-- Stats/Info Sidebar -->
      <div class="space-y-6">
        <div class="bg-indigo-900 rounded-2xl p-6 text-white shadow-xl">
          <h3 class="font-bold text-lg mb-4">
            {{ $t('admin.codes.usage_info') }}
          </h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center opacity-80">
              <span>{{ $t('admin.codes.total_generated') }}</span>
              <span class="font-bold">{{ codes.length }}</span>
            </div>
            <div class="flex justify-between items-center opacity-80">
              <span>{{ $t('admin.codes.available') }}</span>
              <span class="font-bold">{{
                codes.filter((c) => c.is_active).length
              }}</span>
            </div>
            <div
              v-if="codes.length > 0"
              class="w-full bg-indigo-800 h-2 rounded-full overflow-hidden"
            >
              <div
                class="bg-indigo-400 h-full"
                :style="`width: ${(codes.filter((c) => c.is_active).length / codes.length) * 100}%`"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <AppToast ref="toastRef" />
    <ConfirmationModal ref="modalRef" />
  </div>
</template>
