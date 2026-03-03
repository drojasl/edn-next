<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
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
  is_active: true
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
    form.value.password = '' // Clear password field for security
    manualSlug.value = true // Don't auto-generate if we have data
  }
})

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
        ? t('admin.users.slug_available') || 'Slug disponible'
        : t('admin.users.slug_taken') || 'Slug ya está en uso'
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
    errors.value.email = t('auth.errors.invalid_email') || 'Correo inválido'
    isValid = false
  }

  if (props.mode !== 'edit' || form.value.password) {
    if (!form.value.password) {
      errors.value.password = t('auth.errors.required')
      isValid = false
    } else if (form.value.password.length < 8) {
      errors.value.password = t('auth.errors.min_length_8') || 'Mínimo 8 caracteres'
      isValid = false
    }

    if (props.mode === 'register' && form.value.password !== form.value.password_confirmation) {
      errors.value.password_confirmation = t('auth.errors.password_mismatch') || 'Las contraseñas no coinciden'
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
           {{ t('admin.users.amway_code_readonly') || 'El código de empresario no se puede cambiar.' }}
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
          {{ t('admin.users.name') || 'Nombre' }}
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
          {{ t('admin.users.last_name') || 'Apellido' }}
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
        {{ t('admin.users.email') || 'Correo Electrónico' }}
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
        {{ t('admin.users.slug') || 'Slug del Perfil (ej: diego-perez)' }}
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
          {{ t('auth.register.confirmPassword') || 'Confirmar Contraseña' }}
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
            {{ t('admin.users.is_active') || 'Cuenta Activa' }}
          </span>
        </label>
      </div>
    </div>

    <div class="pt-4 flex gap-4">
      <BaseButton
        class="flex-1"
        :text="isProfile ? (t('profile.update_title') || 'Actualizar Perfil') : (mode === 'edit' ? (t('admin.users.update') || 'Actualizar Empresario') : (t('admin.users.submit') || 'Crear Empresario'))"
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
        {{ t('common.cancel') || 'Cancelar' }}
      </button>
    </div>
  </form>
</template>
