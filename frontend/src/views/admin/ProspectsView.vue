<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminDataTable from '../../components/admin/AdminDataTable.vue'

import { type Prospect, type ApiError } from '../../types/types'

const prospects = ref<Prospect[]>([])
const isLoading = ref(true)
const activeMenuId = ref<number | null>(null)
const { t, locale } = useI18n()

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
  } finally {
    activeMenuId.value = null
  }
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

const exportCSV = () => {
  if (prospects.value.length === 0) return

  const headers = [
    t('admin.prospects.csv.headers.name'),
    t('admin.prospects.csv.headers.email'),
    t('admin.prospects.csv.headers.phone'),
    t('admin.prospects.csv.headers.city'),
    t('admin.prospects.csv.headers.country'),
    t('admin.prospects.csv.headers.date'),
    t('admin.prospects.csv.headers.reviewed'),
  ]
  const content = [
    headers.join(','),
    ...prospects.value.map((p) =>
      [
        `"${p.name || ''}"`,
        `"${p.email || ''}"`,
        `"${p.phone || ''}"`,
        `"${p.city || ''}"`,
        `"${p.country || ''}"`,
        `"${formatDate(p.created_at)}"`,
        `"${p.is_reviewed ? t('admin.prospects.csv.status.yes') : t('admin.prospects.csv.status.no')}"`,
      ].join(',')
    ),
  ].join('\n')

  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute(
    'download',
    `${t('admin.prospects.csv.filename')}_${new Date().toISOString().split('T')[0]}.csv`
  )
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const toggleMenu = (id: number) => {
  if (activeMenuId.value === id) {
    activeMenuId.value = null
  } else {
    activeMenuId.value = id
  }
}

// Close menu on click outside could be added here, but keeping it simple with the button toggle

onMounted(() => {
  fetchProspects()
})
</script>

<template>
  <div @click="activeMenuId = null">
    <AdminPageHeader
      :title="$t('admin.prospects.title')"
      :description="$t('admin.prospects.description')"
    >
      <template #actions>
        <button
          :disabled="prospects.length === 0"
          class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 disabled:bg-slate-300 disabled:shadow-none transition-all flex items-center justify-center gap-2"
          @click.stop="exportCSV"
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
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
            />
          </svg>
          <span class="hidden sm:block">{{
            $t('admin.prospects.export')
          }}</span>
        </button>
      </template>
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
        <td class="px-6 py-4 text-right relative whitespace-nowrap">
          <button
            class="text-slate-400 hover:text-indigo-600 transition-colors p-1"
            @click.stop="toggleMenu(prospect.id)"
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
                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
              />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div
            v-if="activeMenuId === prospect.id"
            class="absolute right-6 top-10 w-48 bg-white rounded-xl shadow-2xl border border-slate-100 z-[100] py-2"
          >
            <button
              class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 transition-colors flex items-center gap-2"
              :class="
                prospect.is_reviewed
                  ? 'text-slate-600'
                  : 'text-indigo-600 font-semibold'
              "
              @click.stop="toggleReview(prospect)"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  v-if="prospect.is_reviewed"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
                <path
                  v-else
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              {{
                prospect.is_reviewed
                  ? $t('admin.prospects.table.mark_new')
                  : $t('admin.prospects.table.mark_reviewed')
              }}
            </button>
          </div>
        </td>
      </tr>
    </AdminDataTable>
  </div>
</template>
