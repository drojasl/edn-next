<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '../../common/BaseButton.vue'
import { apiRequest } from '../../../api/apiClient'

const props = defineProps<{
  mode: 'register' | 'create' | 'edit'
  initialData?: any
  loading?: boolean
  isProfile?: boolean
}>()

const emit = defineEmits(['submit', 'cancel'])

const { t } = useI18n()

// Form state
const form = ref({
  id: null,
  name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  codigo_amway: '',
  is_account_holder: true,
  slug: '',
  is_active: true,
  social_links: [] as any[]
})

const errors = ref<Record<string, string>>({})
const slugAvailability = ref({
  checking: false,
  available: true,
  message: ''
})

const manualSlug = ref(false)

// Populate initial data if editing
onMounted(() => {
  if (props.initialData) {
    form.value = { ...form.value, ...props.initialData }
    if (props.initialData.social_links) {
        form.value.social_links = [...props.initialData.social_links];
    }
    form.value.password = '' // Clear password field for security
    manualSlug.value = true // Don't auto-generate if we have data
  }
})

// Social Links Logic
const currentPlatform = ref('whatsapp')
const currentValue = ref('')
const currentSocialError = ref('')
const showPlatformDropdown = ref(false)

const hideDropdown = () => {
  setTimeout(() => showPlatformDropdown.value = false, 200)
}

const socialPlatforms = computed(() => [
  { id: 'cell_phone', name: t('admin.users.social.platforms.cell_phone.label'), placeholder: t('admin.users.social.platforms.cell_phone.placeholder'), color: 'text-slate-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>' },
  { id: 'whatsapp', name: t('admin.users.social.platforms.whatsapp.label'), placeholder: t('admin.users.social.platforms.whatsapp.placeholder'), color: 'text-green-500', icon: '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.878-.788-1.46-1.761-1.633-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>' },
  { id: 'telegram', name: t('admin.users.social.platforms.telegram.label'), placeholder: t('admin.users.social.platforms.telegram.placeholder'), color: 'text-sky-500', icon: '<path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.892-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>' },
  { id: 'instagram', name: t('admin.users.social.platforms.instagram.label'), placeholder: t('admin.users.social.platforms.instagram.placeholder'), color: 'text-pink-600', icon: '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>' },
  { id: 'facebook', name: t('admin.users.social.platforms.facebook.label'), placeholder: t('admin.users.social.platforms.facebook.placeholder'), color: 'text-blue-600', icon: '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>' },
  { id: 'tiktok', name: t('admin.users.social.platforms.tiktok.label'), placeholder: t('admin.users.social.platforms.tiktok.placeholder'), color: 'text-slate-800', icon: '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 2.33-1.19 4.53-3 6.01-1.76 1.43-4.14 2.18-6.42 2.05-2.4-.1-4.65-1.32-6.14-3.11-1.46-1.74-2.11-4.13-1.84-6.41.22-2.14 1.3-4.11 2.92-5.46 1.5-1.25 3.44-1.96 5.4-2.02V12c-1.17.03-2.31.39-3.27 1.05-.88.6-1.55 1.46-1.83 2.49-.24.84-.23 1.77.05 2.59.26.83.84 1.55 1.55 2.02.93.61 2.12.87 3.2.7.99-.14 1.88-.66 2.52-1.38.65-.7.98-1.65 1.02-2.61.03-6.1.01-12.2.03-18.3.01-.18.01-.36.03-.54z"/>' },
  { id: 'youtube', name: t('admin.users.social.platforms.youtube.label'), placeholder: t('admin.users.social.platforms.youtube.placeholder'), color: 'text-red-600', icon: '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.501 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.377.55 9.377.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>' }
])

const addSocialLink = () => {
  currentSocialError.value = ''
  
  if (!currentValue.value) return
  
  // Validation: Check if it looks like a URL
  if (/(http|https|www\.|\.com|\.net|\.org)/i.test(currentValue.value)) {
    currentSocialError.value = t('admin.users.social.error_url')
    return
  }

  form.value.social_links.push({
    platform: currentPlatform.value,
    value: currentValue.value.trim()
  })

  currentValue.value = ''
}

const removeSocialLink = (index: number) => {
  form.value.social_links.splice(index, 1)
}

const getPlatformInfo = (platformId: string) => {
  return socialPlatforms.value.find(p => p.id === platformId)
}


// Auto-generate slug from name and last name
watch([() => form.value.name, () => form.value.last_name], ([name, lastName]) => {
  if (!manualSlug.value && props.mode !== 'edit') {
    const combined = `${name} ${lastName}`.trim()
    form.value.slug = combined
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Remove accents
      .replace(/[^a-z0-9]/g, '-') // Replace non-alphanumeric with -
      .replace(/-+/g, '-') // Remove multiple -
      .replace(/^-|-$/g, '') // Trim -
  }
})

// Validate slug uniqueness
const checkSlug = async () => {
  if (!form.value.slug) {
    slugAvailability.value = { checking: false, available: true, message: '' }
    return
  }

  slugAvailability.value.checking = true
  try {
    const result = await apiRequest({
      method: 'POST',
      url: '/auth/validate-slug',
      body: {
        slug: form.value.slug,
        exclude_id: form.value.id
      }
    })

    if (result.success) {
      slugAvailability.value.available = result.data.available
      slugAvailability.value.message = result.data.available 
        ? t('admin.users.slug_available')
        : t('admin.users.slug_taken')
    }
  } catch (error) {
    console.error('Error checking slug:', error)
  } finally {
    slugAvailability.value.checking = false
  }
}

// Watch slug changes and check availability (debounced)
let slugTimeout: any = null
watch(() => form.value.slug, () => {
  if (slugTimeout) clearTimeout(slugTimeout)
  slugTimeout = setTimeout(checkSlug, 500)
})

const validate = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.name) {
    errors.value.name = t('auth.errors.required')
    isValid = false
  }

  if (!form.value.last_name) {
    errors.value.last_name = t('auth.errors.required')
    isValid = false
  }

  if (!form.value.email) {
    errors.value.email = t('auth.errors.required')
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = t('auth.errors.invalid_email')
    isValid = false
  }

  if (props.mode !== 'edit' || form.value.password) {
    if (!form.value.password) {
      errors.value.password = t('auth.errors.required')
      isValid = false
    } else if (form.value.password.length < 8) {
      errors.value.password = t('auth.errors.min_length_8')
      isValid = false
    }

    if (props.mode === 'register' && form.value.password !== form.value.password_confirmation) {
      errors.value.password_confirmation = t('auth.errors.password_mismatch')
      isValid = false
    }
  }

  if (!form.value.codigo_amway) {
    errors.value.codigo_amway = t('auth.errors.required')
    isValid = false
  }

  if (!form.value.slug) {
    errors.value.slug = t('auth.errors.required')
    isValid = false
  } else if (!slugAvailability.value.available) {
    errors.value.slug = slugAvailability.value.message
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
  if (validate()) {
    emit('submit', { ...form.value })
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Amway Code -->
      <div>
        <label for="codigo_amway" class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('auth.login.amwayCode') }}
        </label>
        <input
          id="codigo_amway"
          v-model="form.codigo_amway"
          type="text"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 'border-red-500': errors.codigo_amway }"
          :disabled="loading || isProfile"
        />
        <p v-if="isProfile" class="mt-1 text-xs text-gray-400">
           {{ t('admin.users.amway_code_readonly') }}
        </p>
        <p v-if="errors.codigo_amway" class="mt-1 text-sm text-red-600">
          {{ errors.codigo_amway }}
        </p>
      </div>

      <!-- Account Holder -->
      <div class="flex flex-col justify-center pt-2">
        <label class="inline-flex items-center">
          <input
            v-model="form.is_account_holder"
            type="checkbox"
            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all"
            :disabled="loading"
          />
          <span class="ml-3 text-sm font-medium text-gray-700">
            {{ t('auth.login.isTitular') }}
          </span>
        </label>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('admin.users.name') }}
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 'border-red-500': errors.name }"
          :disabled="loading"
        />
        <p v-if="errors.name" class="mt-1 text-sm text-red-600">
          {{ errors.name }}
        </p>
      </div>

      <!-- Last Name -->
      <div>
        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('admin.users.last_name') }}
        </label>
        <input
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 'border-red-500': errors.last_name }"
          :disabled="loading"
        />
        <p v-if="errors.last_name" class="mt-1 text-sm text-red-600">
          {{ errors.last_name }}
        </p>
      </div>
    </div>


    <!-- Email -->
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('admin.users.email') }}
      </label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
        :class="{ 'border-red-500': errors.email }"
        :disabled="loading"
      />
      <p v-if="errors.email" class="mt-1 text-sm text-red-600">
        {{ errors.email }}
      </p>
    </div>

    <!-- Slug -->
    <div>
      <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('admin.users.slug') }}
      </label>
      <div class="relative">
        <input
          id="slug"
          v-model="form.slug"
          type="text"
          @input="manualSlug = true"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 
            'border-red-500': errors.slug || (!slugAvailability.available && form.slug),
            'border-green-500': slugAvailability.available && form.slug && !slugAvailability.checking && !errors.slug
          }"
          :disabled="loading"
        />
        <div v-if="slugAvailability.checking" class="absolute right-3 top-3.5">
          <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600"></div>
        </div>
      </div>
      <p 
        v-if="form.slug && !slugAvailability.checking" 
        class="mt-1 text-sm"
        :class="slugAvailability.available ? 'text-green-600' : 'text-red-600'"
      >
        {{ slugAvailability.message }}
      </p>
      <p v-if="errors.slug" class="mt-1 text-sm text-red-600">
        {{ errors.slug }}
      </p>
    </div>

    <!-- Social Links Section -->
    <div v-if="mode !== 'register'" class="bg-slate-50 p-6 rounded-xl border border-slate-100 space-y-4">
      <h4 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2">{{ $t('admin.users.social.title') }}</h4>
      
      <!-- Add new link inputs -->
      <div class="flex flex-col md:flex-row gap-3 items-start">
         <div class="relative w-full md:w-1/3">
            <button 
              type="button" 
              @click="showPlatformDropdown = !showPlatformDropdown"
              @blur="hideDropdown"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white flex items-center justify-between transition-all"
              :class="{ 'ring-2 ring-indigo-500 border-transparent': showPlatformDropdown }"
            >
              <div class="flex items-center gap-2">
                 <svg v-if="currentPlatform === 'cell_phone'" class="w-5 h-5 flex-shrink-0" :class="getPlatformInfo(currentPlatform)?.color" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getPlatformInfo(currentPlatform)?.icon"></svg>
                 <svg v-else class="w-5 h-5 flex-shrink-0" :class="getPlatformInfo(currentPlatform)?.color" fill="currentColor" viewBox="0 0 24 24" v-html="getPlatformInfo(currentPlatform)?.icon"></svg>
                 <span class="truncate max-w-[100px] md:max-w-[70px] lg:max-w-[120px] text-left">{{ getPlatformInfo(currentPlatform)?.name }}</span>
              </div>
              <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div v-show="showPlatformDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
              <button 
                v-for="platform in socialPlatforms" :key="platform.id" 
                type="button"
                @mousedown.prevent="currentPlatform = platform.id; showPlatformDropdown = false"
                class="w-full text-left flex items-center gap-2 px-4 py-3 hover:bg-slate-50 transition-colors"
                :class="{ 'bg-slate-100': currentPlatform === platform.id }"
              >
                 <svg v-if="platform.id === 'cell_phone'" class="w-5 h-5 flex-shrink-0" :class="platform.color" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="platform.icon"></svg>
                 <svg v-else class="w-5 h-5 flex-shrink-0" :class="platform.color" fill="currentColor" viewBox="0 0 24 24" v-html="platform.icon"></svg>
                 <span>{{ platform.name }}</span>
              </button>
            </div>
         </div>
         
         <div class="w-full flex-1">
           <div class="flex gap-2">
             <input
                v-model="currentValue"
                type="text"
                :placeholder="getPlatformInfo(currentPlatform)?.placeholder"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                :class="{ 'border-red-500': currentSocialError }"
                @keydown.enter.prevent="addSocialLink"
             />
             <button type="button" @click="addSocialLink" class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition flex-shrink-0" :disabled="!currentValue">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
             </button>
           </div>
           <p v-if="currentSocialError" class="mt-1 text-xs text-red-500 font-medium">{{ currentSocialError }}</p>
         </div>
      </div>

      <!-- List of existing social links -->
      <div v-if="form.social_links.length > 0" class="space-y-2 mb-4">
        <div v-for="(link, index) in form.social_links" :key="index" class="flex items-center justify-between bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
          <div class="flex items-center space-x-3">
             <div class="w-6 h-6 flex items-center justify-center">
                 <svg v-if="link.platform === 'cell_phone'" class="w-5 h-5" :class="socialPlatforms.find(p => p.id === 'cell_phone')?.color" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="socialPlatforms.find(p => p.id === 'cell_phone')?.icon"></svg>
                 <svg v-else class="w-5 h-5" :class="socialPlatforms.find(p => p.id === link.platform)?.color" fill="currentColor" viewBox="0 0 24 24" v-html="socialPlatforms.find(p => p.id === link.platform)?.icon"></svg>
             </div>
             <span class="text-sm font-medium text-slate-700">{{ link.value }}</span>
          </div>
          <button type="button" @click="removeSocialLink(index)" class="text-red-400 hover:text-red-600 transition-colors p-1" title="Eliminar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Password -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('auth.login.password') }}
          <span v-if="mode === 'edit'" class="text-xs text-gray-500 font-normal">(Dejar en blanco para no cambiar)</span>
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 'border-red-500': errors.password }"
          :disabled="loading"
        />
        <p v-if="errors.password" class="mt-1 text-sm text-red-600">
          {{ errors.password }}
        </p>
      </div>

      <!-- Password Confirmation (Only for public registration) -->
      <div v-if="mode === 'register'">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('auth.register.confirmPassword') }}
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          :class="{ 'border-red-500': errors.password_confirmation }"
          :disabled="loading"
        />
        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
          {{ errors.password_confirmation }}
        </p>
      </div>

      <!-- Active Status (Only for Admin) -->
      <div v-if="mode !== 'register' && !isProfile" class="flex flex-col justify-center pt-2">
        <label class="inline-flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all"
            :disabled="loading"
          />
          <span class="ml-3 text-sm font-medium text-gray-700">
            {{ t('admin.users.is_active') }}
          </span>
        </label>
      </div>
    </div>

    <div class="pt-4 flex gap-4">
      <BaseButton
        class="flex-1"
        :text="isProfile ? t('profile.update_title') : (mode === 'edit' ? t('admin.users.update') : t('admin.users.submit'))"
        :action="handleSubmit"
        :extra-props="{
          loading: loading,
          type: 'submit'
        }"
      />
      
      <button
        v-if="mode !== 'register'"
        type="button"
        @click="emit('cancel')"
        class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
        :disabled="loading"
      >
        {{ t('common.cancel') }}
      </button>
    </div>
  </form>
</template>
