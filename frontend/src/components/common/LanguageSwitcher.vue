<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const currentLanguage = computed(() => locale.value)

const switchLanguage = (lang: string) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
  // Dispatch a custom event if needed for legacy logic,
  // but standard i18n should pick it up.
}
</script>

<template>
  <div
    class="backdrop-blur-md bg-white/5 border border-white/10 rounded-2xl p-1 flex shadow-xl"
  >
    <button
      :class="[
        'px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300',
        currentLanguage === 'es'
          ? 'bg-white text-slate-900 shadow-lg'
          : 'text-white/60 hover:text-white',
      ]"
      @click="switchLanguage('es')"
    >
      ES
    </button>
    <button
      :class="[
        'px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300',
        currentLanguage === 'en'
          ? 'bg-white text-slate-900 shadow-lg'
          : 'text-white/60 hover:text-white',
      ]"
      @click="switchLanguage('en')"
    >
      EN
    </button>
  </div>
</template>
