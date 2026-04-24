<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useI18n } from 'vue-i18n'
import { apiRequest } from '../../api/apiClient'
import AppToast from '../../components/common/AppToast.vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import BaseButton from '../../components/common/BaseButton.vue'

const authStore = useAuthStore()
const { t } = useI18n()
const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const loading = ref(false)

const form = ref({
  slug: '',
  password: '',
  password_confirmation: '',
})

const errors = ref<Record<string, string>>({})
const slugAvailability = ref({
  checking: false,
  available: true,
  message: '',
})

onMounted(async () => {
  await authStore.fetchUser()
  if (authStore.user) {
    form.value.slug = authStore.user.slug || ''
  }
})

// Validate slug uniqueness
const checkSlug = async () => {
  if (!form.value.slug) {
    slugAvailability.value = { checking: false, available: true, message: '' }
    return
  }

  if (authStore.user && form.value.slug === authStore.user.slug) {
    slugAvailability.value = {
      checking: false,
      available: true,
      message: t('admin.users.slug_available'),
    }
    return
  }

  slugAvailability.value.checking = true
  try {
    const result = await apiRequest<{ available: boolean }>({
      method: 'POST',
      url: '/auth/validate-slug',
      body: {
        slug: form.value.slug,
        exclude_id: authStore.user?.id,
      },
    })

    if (result.success && result.data) {
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

let slugTimeout: number | null = null
watch(
  () => form.value.slug,
  () => {
    if (slugTimeout) clearTimeout(slugTimeout)
    slugTimeout = setTimeout(checkSlug, 500)
  }
)

const validate = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.slug) {
    errors.value.slug = t('auth.errors.required')
    isValid = false
  } else if (!slugAvailability.value.available) {
    errors.value.slug = slugAvailability.value.message
    isValid = false
  }

  if (form.value.password) {
    if (form.value.password.length < 8) {
      errors.value.password = t('auth.errors.min_length_8')
      isValid = false
    }
    if (form.value.password !== form.value.password_confirmation) {
      errors.value.password_confirmation = t('auth.errors.password_mismatch')
      isValid = false
    }
  }

  return isValid
}

const handleUpdateConfig = async () => {
  if (!authStore.user?.id) return
  if (!validate()) return

  loading.value = true
  try {
    const payload: Record<string, unknown> = {
      name: authStore.user.name,
      last_name: authStore.user.last_name,
      email: authStore.user.email,
      codigo_amway: authStore.user.codigo_amway,
      is_account_holder: authStore.user.is_account_holder,
      is_active: authStore.user.is_active,
      slug: form.value.slug,
    }

    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }

    const response = await apiRequest({
      method: 'PUT',
      url: `/v1/admin/entrepreneurs/${authStore.user.id}`,
      body: payload,
    })

    if (response.success) {
      toastRef.value?.show(t('admin.settings.success'), 'success')
      form.value.password = ''
      form.value.password_confirmation = ''
      await authStore.fetchUser()
    } else {
      toastRef.value?.show(
        response.error?.message || t('common.error'),
        'error'
      )
    }
  } catch (error: unknown) {
    const e = error as Error
    toastRef.value?.show(e.message || t('common.error'), 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <AdminPageHeader
      :title="t('admin.nav.settings')"
      :description="t('admin.settings.subtitle')"
    />

    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
      <h3
        class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-50 pb-4"
      >
        {{ t('admin.settings.title') }}
      </h3>

      <form class="space-y-6" @submit.prevent="handleUpdateConfig">
        <!-- Slug -->
        <div>
          <label
            for="slug"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            {{ t('admin.settings.slug_label') }}
          </label>
          <div class="relative">
            <input
              id="slug"
              v-model="form.slug"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              :class="{
                'border-red-500':
                  errors.slug || (!slugAvailability.available && form.slug),
                'border-green-500':
                  slugAvailability.available &&
                  form.slug &&
                  !slugAvailability.checking &&
                  !errors.slug,
              }"
              :disabled="loading"
            />
            <div
              v-if="slugAvailability.checking"
              class="absolute right-3 top-3.5"
            >
              <div
                class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600"
              ></div>
            </div>
          </div>
          <p
            v-if="form.slug && !slugAvailability.checking"
            class="mt-1 text-sm"
            :class="
              slugAvailability.available ? 'text-green-600' : 'text-red-600'
            "
          >
            {{ slugAvailability.message }}
          </p>
          <p v-if="errors.slug" class="mt-1 text-sm text-red-600">
            {{ errors.slug }}
          </p>
        </div>

        <div class="pt-4 pb-2">
          <h4
            class="text-md font-semibold text-slate-800 border-b border-slate-50 pb-2 mb-4"
          >
            {{ t('admin.settings.change_password') }}
          </h4>
          <p class="text-sm text-slate-500 mb-4">
            {{ t('admin.settings.password_hint') }}
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Password -->
            <div>
              <label
                for="password"
                class="block text-sm font-medium text-gray-700 mb-2"
              >
                {{ t('admin.settings.new_password') }}
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

            <!-- Password Confirmation -->
            <div>
              <label
                for="password_confirmation"
                class="block text-sm font-medium text-gray-700 mb-2"
              >
                {{ t('admin.settings.confirm_password') }}
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                :class="{ 'border-red-500': errors.password_confirmation }"
                :disabled="loading"
              />
              <p
                v-if="errors.password_confirmation"
                class="mt-1 text-sm text-red-600"
              >
                {{ errors.password_confirmation }}
              </p>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-50 flex gap-4">
          <BaseButton
            class="flex-1"
            :text="t('admin.settings.save')"
            :action="() => handleUpdateConfig()"
            :extra-props="{
              loading: loading,
              type: 'submit',
            }"
          />
        </div>
      </form>
    </div>

    <AppToast ref="toastRef" />
  </div>
</template>
