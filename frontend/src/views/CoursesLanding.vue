<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '../components/common/BaseButton.vue'
import LanguageSwitcher from '../components/common/LanguageSwitcher.vue'

import { useRouter, useRoute } from 'vue-router'
import { apiRequest } from '../api/apiClient'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const accessCode = ref('')
const loading = ref(false)
const errorMessage = ref('')

const rotateDegrees = ref(0)

const getSessionId = () => {
  let sessionId = localStorage.getItem('prospect_session_id')
  if (!sessionId) {
    sessionId = crypto.randomUUID()
    localStorage.setItem('prospect_session_id', sessionId)
  }
  return sessionId
}

const handleSubmit = async () => {
  if (!accessCode.value) return
  loading.value = true
  errorMessage.value = ''

  const result = await apiRequest({
    method: 'POST',
    url: '/v1/public/access-codes/validate',
    body: {
      code: accessCode.value,
      session_id: getSessionId(),
    },
  })

  if (result.success && result.data) {
    const { entrepreneurSlug, courseSlug } = result.data

    // Guardar información de acceso para validación posterior
    localStorage.setItem(
      'course_access',
      JSON.stringify({
        accessCode: accessCode.value,
        entrepreneurSlug,
        courseSlug,
      })
    )

    // Redirect to the course
    router.push(`/cursos/${entrepreneurSlug}/${courseSlug}`)
  } else {
    console.error('Validation error:', result.error)
    const status = result.error?.code
    if (status === 404) {
      errorMessage.value = t('landing.errors.invalid')
    } else if (status === 403) {
      errorMessage.value = t('landing.errors.expired')
    } else {
      errorMessage.value = t('landing.errors.generic')
    }
    loading.value = false
  }
}

onMounted(() => {
  const cod = route.query.cod as string
  if (cod) {
    accessCode.value = cod.toUpperCase()
    handleSubmit()
  }
})
</script>

<template>
  <div
    class="min-h-screen relative flex items-center justify-center overflow-hidden bg-slate-900"
  >
    <!-- Animated background gradient -->
    <div class="absolute inset-0 z-0">
      <div
        class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-pink-500/20 animate-pulse"
      ></div>
      <div
        class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600/30 rounded-full blur-[120px] animate-blob"
      ></div>
      <div
        class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/30 rounded-full blur-[120px] animate-blob animation-delay-2000"
      ></div>
    </div>

    <!-- Language Selector Floating -->
    <div class="absolute top-6 right-6 z-20">
      <LanguageSwitcher />
    </div>

    <!-- Content -->
    <div class="relative z-10 w-full max-w-lg px-6">
      <div
        class="backdrop-blur-2xl bg-white/10 border border-white/20 rounded-[2.5rem] p-10 shadow-2xl space-y-10 text-center"
      >
        <!-- Logo or Icon -->
        <div class="flex justify-center">
          <div
            class="w-24 h-24 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 rounded-3xl flex items-center justify-center shadow-2xl transition-transform duration-700"
            :style="{ transform: `rotate(${rotateDegrees}deg)` }"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-12 w-12 text-white"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.246 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253"
              />
            </svg>
          </div>
        </div>

        <div class="space-y-3">
          <h1
            class="text-4xl md:text-5xl font-black text-white tracking-tight drop-shadow-md"
          >
            {{ t('landing.title') }}
          </h1>
          <p class="text-indigo-100 text-lg font-medium opacity-80">
            {{ t('landing.subtitle') }}
          </p>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
          <div class="relative group">
            <input
              v-model="accessCode"
              type="text"
              :placeholder="t('landing.placeholder')"
              class="w-full bg-white/5 border border-white/10 text-white placeholder-white/20 rounded-2xl px-6 py-5 text-center text-2xl font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white/10 transition-all group-hover:bg-white/10"
              autocomplete="off"
            />
            <div
              v-if="errorMessage"
              class="absolute -bottom-8 left-0 right-0 text-rose-400 text-sm font-bold animate-shake"
            >
              {{ errorMessage }}
            </div>
          </div>

          <BaseButton
            :text="t('landing.button')"
            :action="handleSubmit"
            :extra-props="{
              disabled: !accessCode,
              loading: loading,
              loadingText: t('landing.verifying'),
              type: 'submit',
            }"
          />
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}

@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-5px);
  }
  75% {
    transform: translateX(5px);
  }
}
.animate-shake {
  animation: shake 0.2s ease-in-out infinite;
  animation-iteration-count: 2;
}
</style>
