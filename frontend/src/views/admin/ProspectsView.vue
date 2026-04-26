<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminDataTable from '../../components/admin/AdminDataTable.vue'
import ConfirmationModal from '../../components/common/ConfirmationModal.vue'
import { type Prospect, type ApiError } from '../../types'

const prospects = ref<Prospect[]>([])
const isLoading = ref(true)
const { t, locale } = useI18n()
const confirmDeleteModal = ref<InstanceType<typeof ConfirmationModal> | null>(
  null
)

const fetchProspects = async () => {
  isLoading.value = true
  try {
    const response = await apiRequest<Prospect[]>({
      method: 'GET',
      url: '/v1/admin/prospects',
    })
    if (response.success && response.data) {
      prospects.value = response.data
    }
  } catch (error: unknown) {
    console.error('Error fetching prospects:', error as ApiError)
  } finally {
    isLoading.value = false
  }
}

const toggleReview = async (prospect: Prospect) => {
  try {
    const response = await apiRequest({
      method: 'PATCH',
      url: `/v1/admin/prospects/${prospect.id}/toggle-review`,
    })
    if (response.success) {
      prospect.is_reviewed = !prospect.is_reviewed
    }
  } catch (error: unknown) {
    console.error('Error toggling review status:', error as ApiError)
  }
}

const deleteProspect = (prospect: Prospect) => {
  confirmDeleteModal.value?.open({
    title: t('admin.prospects.table.delete'),
    message: t('admin.prospects.table.delete_confirm'),
    isDestructive: true,
    confirmText: t('admin.prospects.table.delete'),
    onConfirm: async () => {
      try {
        const response = await apiRequest({
          method: 'DELETE',
          url: `/v1/admin/prospects/${prospect.id}`,
        })
        if (response.success) {
          prospects.value = prospects.value.filter((p) => p.id !== prospect.id)
        }
      } catch (error: unknown) {
        console.error('Error deleting prospect:', error as ApiError)
      }
    },
  })
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(
    locale.value === 'en' ? 'en-US' : 'es-ES',
    {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }
  )
}

onMounted(() => {
  fetchProspects()
})
</script>

<template>
  <div>
    <AdminPageHeader
      :title="$t('admin.prospects.title')"
      :description="$t('admin.prospects.description')"
    >
    </AdminPageHeader>

    <AdminDataTable
      :headers="[
        { label: $t('admin.prospects.table.name') },
        { label: $t('admin.prospects.table.email') },
        { label: $t('admin.prospects.table.phone') },
        { label: $t('admin.prospects.table.location') },
        { label: $t('admin.prospects.table.date') },
        { label: '' },
      ]"
      :loading="isLoading"
      :has-data="prospects.length > 0"
      :empty-message="$t('admin.prospects.table.no_prospects')"
    >
      <tr
        v-for="prospect in prospects"
        :key="prospect.id"
        class="transition-all duration-200 border-l-4 group"
        :class="[
          prospect.is_reviewed
            ? 'bg-white border-transparent grayscale-[0.5] opacity-80'
            : 'bg-indigo-50/60 border-l-indigo-600 hover:bg-indigo-50/80 shadow-[inset_0_0_0_1px_rgba(79,70,229,0.05)]',
        ]"
      >
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="flex items-center gap-3">
            <div
              v-if="!prospect.is_reviewed"
              class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-indigo-600 shadow-lg shadow-indigo-300 ring-4 ring-indigo-50"
            ></div>
            <div
              v-else
              class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-slate-200"
            ></div>
            <span
              :class="[
                'text-sm tracking-tight',
                prospect.is_reviewed
                  ? 'text-slate-500 font-normal'
                  : 'text-slate-900 font-bold',
              ]"
            >
              {{ prospect.name || '-' }}
            </span>
          </div>
        </td>
        <td
          class="px-6 py-4 text-slate-500 whitespace-nowrap"
          :class="{ 'font-bold text-slate-900': !prospect.is_reviewed }"
        >
          {{ prospect.email }}
        </td>
        <td
          class="px-6 py-4 text-slate-500 whitespace-nowrap"
          :class="{ 'font-bold text-slate-900': !prospect.is_reviewed }"
        >
          {{ prospect.phone || '-' }}
        </td>
        <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
          <template v-if="prospect.city || prospect.country">
            {{ prospect.city
            }}{{
              prospect.city && prospect.country
                ? ` (${prospect.country})`
                : prospect.country
            }}
          </template>
          <span v-else class="text-slate-400 italic">{{
            $t('admin.prospects.table.unspecified')
          }}</span>
        </td>
        <td
          class="px-6 py-4 text-slate-400 text-xs font-medium whitespace-nowrap"
        >
          {{ formatDate(prospect.created_at) }}
        </td>
        <td class="px-6 py-4 text-right whitespace-nowrap">
          <div class="flex items-center justify-end gap-2">
            <!-- Toggle Review Button -->
            <button
              class="p-2 rounded-lg transition-all"
              :class="[
                prospect.is_reviewed
                  ? 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'
                  : 'text-indigo-600 hover:text-indigo-700 hover:bg-indigo-100/50',
              ]"
              :title="
                prospect.is_reviewed
                  ? $t('admin.prospects.table.mark_new')
                  : $t('admin.prospects.table.mark_reviewed')
              "
              @click.stop="toggleReview(prospect)"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  v-if="prospect.is_reviewed"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  v-if="prospect.is_reviewed"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                />
                <path
                  v-else
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </button>

            <!-- Delete Button -->
            <button
              class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all font-medium"
              :title="$t('admin.prospects.table.delete')"
              @click.stop="deleteProspect(prospect)"
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

    <ConfirmationModal ref="confirmDeleteModal" />
  </div>
</template>
