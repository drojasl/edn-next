<script setup lang="ts">
import { ref, onMounted, toRaw } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useI18n } from 'vue-i18n'
import EntrepreneurForm from '../../components/admin/user/EntrepreneurForm.vue'
import { apiRequest } from '../../api/apiClient'
import AppToast from '../../components/common/AppToast.vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'

const authStore = useAuthStore()
const { t } = useI18n()
const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const loading = ref(false)
const isUploadingImage = ref(false)

const profilePictureFile = ref<File | null>(null)
const profilePicturePreview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const getFullUrl = (path: string | null) => {
    if (!path) return ''
    if (path.startsWith('http')) return path
    const baseUrl = import.meta.env.VITE_API_BASE_URL?.replace('/api', '') || 'http://localhost:8000'
    return `${baseUrl}${path}`
}

const handleFileChange = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        profilePictureFile.value = file
        profilePicturePreview.value = URL.createObjectURL(file)
        
        // Auto upload when file is selected
        if (authStore.user) {
            isUploadingImage.value = true
            await handleUpdateProfile(authStore.user)
            isUploadingImage.value = false
        }
    }
}

const triggerFileUpload = () => {
    fileInput.value?.click()
}

const handleUpdateProfile = async (formData: any) => {
    if (!authStore.user?.id) return

    loading.value = true
    try {
        // Only take the fields that the form manages
        const allowedFields = ['name', 'last_name', 'email', 'password', 'codigo_amway', 'is_account_holder', 'slug', 'is_active', 'social_links'];
        const cleanData: any = {};
        allowedFields.forEach(field => {
            if (formData[field] !== undefined) {
                cleanData[field] = formData[field];
            }
        });

        let payload: any = cleanData;
        let method: 'POST' | 'PUT' = 'PUT';
        let headers: any = undefined;

        if (profilePictureFile.value) {
            method = 'POST';
            payload = new FormData();
            Object.keys(cleanData).forEach(key => {
                if (cleanData[key] !== null && cleanData[key] !== undefined) {
                    if (typeof cleanData[key] === 'boolean') {
                       payload.append(key, cleanData[key] ? '1' : '0'); 
                    } else if (Array.isArray(cleanData[key])) {
                        cleanData[key].forEach((item: any, index: number) => {
                            if (typeof item === 'object') {
                                Object.keys(item).forEach(subKey => {
                                    payload.append(`${key}[${index}][${subKey}]`, item[subKey]);
                                });
                            } else {
                                payload.append(`${key}[]`, item);
                            }
                        });
                    } else {
                       payload.append(key, cleanData[key]);
                    }
                }
            });
            const rawFile = toRaw(profilePictureFile.value);
            payload.append('profile_picture', rawFile);
            payload.append('_method', 'PUT');
            headers = { 'Content-Type': 'multipart/form-data' }; // Need explicitly here to overcome default application/json
        }

        const response = await apiRequest({
            method,
            url: `/v1/admin/entrepreneurs/${authStore.user.id}`,
            body: payload,
            headers
        })

        if (response.success) {
            toastRef.value?.show(t('profile.update_success'), 'success')
            // Refresh user data in store
            profilePictureFile.value = null
            profilePicturePreview.value = null
            await authStore.fetchUser()
        } else {
            toastRef.value?.show(response.error?.message || t('common.error'), 'error')
        }
    } catch (error: any) {
        toastRef.value?.show(error.message || t('common.error'), 'error')
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    // Ensure we have latest user data
    await authStore.fetchUser()
})
</script>

<template>
    <div class="max-w-4xl mx-auto">
        <AdminPageHeader 
            :title="t('profile.title')"
            :description="t('profile.subtitle')"
        />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center sticky top-24">
                    <div 
                        class="relative w-24 h-24 mx-auto mb-4 group cursor-pointer"
                        @click="triggerFileUpload"
                    >
                        <div class="w-full h-full bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center border-4 border-white shadow-lg overflow-hidden relative">
                            <div v-if="isUploadingImage" class="absolute inset-0 bg-white/60 flex items-center justify-center z-10">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            </div>
                            <img v-if="profilePicturePreview || authStore.user?.profile_picture" 
                                 :src="profilePicturePreview || getFullUrl(authStore.user?.profile_picture || '')" 
                                 alt="Avatar" class="w-full h-full object-cover" />
                            <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <input 
                            type="file" 
                            ref="fileInput" 
                            class="hidden" 
                            accept="image/*" 
                            @change="handleFileChange" 
                        />
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">{{ authStore.user?.name }} {{ authStore.user?.last_name }}</h2>
                    <p class="text-slate-500 text-sm mt-1">{{ t('profile.role') }}</p>
                    
                    <div class="mt-4 pt-4 border-t border-slate-50 text-left space-y-3">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('profile.amway_code') }}</p>
                            <p class="text-sm font-mono font-bold text-slate-700">{{ authStore.user?.codigo_amway }}</p>
                        </div>
                        <div v-if="authStore.user?.slug">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('profile.slug_label') }}</p>
                            <p class="text-sm font-medium text-indigo-600">/cursos/{{ authStore.user?.slug }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form Section -->
            <div class="md:col-span-2">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-50 pb-4">
                        {{ t('profile.personal_info') }}
                    </h3>
                    
                    <EntrepreneurForm 
                        v-if="authStore.user"
                        mode="edit"
                        :initial-data="authStore.user"
                        :loading="loading"
                        :is-profile="true"
                        @submit="handleUpdateProfile"
                    />
                </div>
            </div>
        </div>

        <AppToast ref="toastRef" />
    </div>
</template>
