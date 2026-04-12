<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import { apiRequest } from '../../api/apiClient'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'

import { type Course } from '../../types'

interface FunnelNode {
  id: number
  title: string
  views: number
}

interface FunnelStat {
  id: number
  code: string
  visits: number
  registered: number
  course: Course
  funnel: FunnelNode[]
}

const { t } = useI18n()
const authStore = useAuthStore()
const funnelData = ref<FunnelStat[]>([])
const loading = ref(true)
const expandedCodes = ref<Record<number, boolean>>({})

const toggleCode = (id: number) => {
  expandedCodes.value[id] = !expandedCodes.value[id]
}

const userName = computed(() => authStore.user?.name || '')

const fetchStats = async () => {
  loading.value = true
  const response = await apiRequest<FunnelStat[]>({
    method: 'GET',
    url: '/v1/admin/stats/funnel',
  })
  if (response.success && response.data) {
    funnelData.value = response.data
  }
  loading.value = false
}

onMounted(fetchStats)

const globalStats = computed(() => {
  let totalVisits = 0
  let totalRegistered = 0
  funnelData.value.forEach((item) => {
    totalVisits += item.visits
    totalRegistered += item.registered
  })

  const convRate =
    totalVisits > 0 ? ((totalRegistered / totalVisits) * 100).toFixed(1) : '0'

  return [
    {
      name: t('admin.dashboard.stats.total_visits'),
      value: totalVisits.toString(),
      change: '',
      changeType: 'neutral',
    },
    {
      name: t('admin.dashboard.stats.total_prospects'),
      value: totalRegistered.toString(),
      change: '',
      changeType: 'neutral',
    },
    {
      name: t('admin.dashboard.stats.active_codes'),
      value: funnelData.value.length.toString(),
      change: '',
      changeType: 'neutral',
    },
    {
      name: t('admin.dashboard.stats.conversion_rate'),
      value: `${convRate}%`,
      change: '',
      changeType: 'neutral',
    },
  ]
})
</script>

<template>
  <div class="space-y-8">
    <AdminPageHeader
      :title="$t('admin.dashboard.title')"
      :description="$t('admin.dashboard.welcome', { name: userName })"
    />

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <div
        v-for="stat in globalStats"
        :key="stat.name"
        class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100"
      >
        <p class="text-sm font-medium text-slate-500">{{ stat.name }}</p>
        <div class="flex items-baseline justify-between mt-2">
          <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- Funnel Section -->
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">
          {{ $t('admin.dashboard.funnel_title') || 'Rendimiento por Código' }}
        </h2>
        <button
          class="text-indigo-600 hover:text-indigo-700 font-medium text-sm flex items-center gap-2"
          @click="fetchStats"
        >
          <svg
            v-if="loading"
            class="animate-spin h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span>{{ loading ? 'Actualizando...' : 'Refrescar datos' }}</span>
        </button>
      </div>

      <div
        v-if="loading && funnelData.length === 0"
        class="grid grid-cols-1 lg:grid-cols-2 gap-6"
      >
        <div
          v-for="i in 2"
          :key="i"
          class="bg-white h-96 rounded-2xl border border-slate-100 animate-pulse"
        ></div>
      </div>

      <div
        v-else-if="funnelData.length === 0"
        class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-500"
      >
        Aún no hay datos de interacción para mostrar.
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="codeStat in funnelData"
          :key="codeStat.id"
          class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
        >
          <!-- Accordion Header -->
          <button
            class="w-full flex items-center justify-between px-5 sm:px-8 py-5 hover:bg-slate-50/50 transition-all text-left group"
            @click="toggleCode(codeStat.id)"
          >
            <div class="flex-1 min-w-0 pr-4">
              <span
                class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-1"
              >
                {{ codeStat.course.title }}
              </span>
              <h3 class="text-xl font-black text-indigo-600 truncate">
                {{ codeStat.code }}
              </h3>
            </div>

            <div class="flex items-center gap-3 sm:gap-6 shrink-0">
              <!-- Mobile Stats Summary -->
              <div class="md:hidden flex gap-4 text-left sm:text-right mr-2">
                <div class="flex flex-col">
                  <span
                    class="text-sm font-black text-slate-900 leading-tight"
                    >{{ codeStat.visits }}</span
                  >
                  <span
                    class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter"
                    >Visitas</span
                  >
                </div>
                <div class="flex flex-col border-l border-slate-100 pl-4">
                  <span
                    class="text-sm font-black text-emerald-600 leading-tight"
                    >{{ codeStat.registered }}</span
                  >
                  <span
                    class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter"
                    >Reg.</span
                  >
                </div>
                <div class="flex flex-col border-l border-slate-100 pl-4">
                  <span class="text-sm font-black text-indigo-600 leading-tight"
                    >{{
                      codeStat.visits > 0
                        ? (
                            (codeStat.registered / codeStat.visits) *
                            100
                          ).toFixed(0)
                        : 0
                    }}%</span
                  >
                  <span
                    class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter"
                    >Cierre</span
                  >
                </div>
              </div>

              <!-- Desktop Stats Summary -->
              <div
                class="hidden md:flex gap-8 border-l border-slate-200 pl-8 mr-4"
              >
                <div class="text-center">
                  <div class="text-xl font-black text-slate-900">
                    {{ codeStat.visits }}
                  </div>
                  <div
                    class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"
                  >
                    Visitas
                  </div>
                </div>
                <div class="text-center">
                  <div class="text-xl font-black text-emerald-600">
                    {{ codeStat.registered }}
                  </div>
                  <div
                    class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"
                  >
                    Registros
                  </div>
                </div>
                <div class="text-center">
                  <div class="text-xl font-black text-indigo-600 text-right">
                    {{
                      codeStat.visits > 0
                        ? (
                            (codeStat.registered / codeStat.visits) *
                            100
                          ).toFixed(1)
                        : 0
                    }}%
                  </div>
                  <div
                    class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"
                  >
                    Cierre
                  </div>
                </div>
              </div>

              <!-- Accordion Arrow Icon -->
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 bg-slate-50 group-hover:bg-indigo-50 group-hover:text-indigo-600',
                  expandedCodes[codeStat.id]
                    ? 'rotate-180 bg-indigo-50 text-indigo-600'
                    : 'text-slate-400',
                ]"
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
                    stroke-width="2.5"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>
            </div>
          </button>

          <!-- Accordion Content (Funnel Details) -->
          <div
            v-if="expandedCodes[codeStat.id]"
            class="border-t border-slate-100 bg-white p-8 animate-fade-in"
          >
            <div
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-12 gap-y-8"
            >
              <div
                v-for="(node, idx) in codeStat.funnel"
                :key="node.id"
                class="space-y-2"
              >
                <div class="flex justify-between text-sm font-bold">
                  <span class="text-slate-700 truncate pr-2"
                    >{{ Number(idx) + 1 }}. {{ node.title }}</span
                  >
                  <span class="text-slate-900 shrink-0"
                    >{{ node.views }}
                    <span class="text-slate-400 font-medium"
                      >({{
                        codeStat.visits > 0
                          ? ((node.views / codeStat.visits) * 100).toFixed(0)
                          : 0
                      }}%)</span
                    ></span
                  >
                </div>
                <div
                  class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden flex"
                >
                  <div
                    class="h-full bg-indigo-500 transition-all duration-1000 ease-out"
                    :style="{
                      width: `${codeStat.visits > 0 ? (node.views / codeStat.visits) * 100 : 0}%`,
                    }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
