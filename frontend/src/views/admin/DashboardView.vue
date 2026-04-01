<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';

const { t } = useI18n();
const authStore = useAuthStore();

const userName = computed(() => authStore.user?.name || '');

const stats = computed(() => [
    { name: t('admin.dashboard.stats.total_prospects'), value: '128', change: '+12%', changeType: 'increase' },
    { name: t('admin.dashboard.stats.active_courses'), value: '4', change: '0', changeType: 'neutral' },
    { name: t('admin.dashboard.stats.assigned_codes'), value: '45', change: '+5%', changeType: 'increase' },
    { name: t('admin.dashboard.stats.conversion_rate'), value: '24.5%', change: '+2.1%', changeType: 'increase' },
]);
</script>

<template>
    <div>
        <AdminPageHeader 
            :title="$t('admin.dashboard.title')"
            :description="$t('admin.dashboard.welcome', { name: userName })"
        />

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div 
                v-for="stat in stats" 
                :key="stat.name"
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow"
            >
                <p class="text-sm font-medium text-slate-500">{{ stat.name }}</p>
                <div class="flex items-baseline justify-between mt-2">
                    <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                    <span 
                        :class="[
                            stat.changeType === 'increase' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-600',
                            'text-xs font-bold px-2 py-1 rounded-full'
                        ]"
                    >
                        {{ stat.change }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Placeholder Chart Area -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 h-96 flex items-center justify-center">
            <div class="text-center">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">{{ $t('admin.dashboard.preparing') }}</h3>
                <p class="text-slate-500 max-w-xs mx-auto mt-2">{{ $t('admin.dashboard.coming_soon') }}</p>
            </div>
        </div>
    </div>
</template>
