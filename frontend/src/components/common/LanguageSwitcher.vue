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
  <div class="bg-white border border-gray-200 rounded-xl p-1 flex shadow-sm">
    <button
      :class="[
        'px-4 py-2 rounded-lg text-sm font-bold transition-all duration-300',
        currentLanguage === 'es'
          ? 'bg-indigo-600 text-white shadow-md'
          : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50',
      ]"
      @click="switchLanguage('es')"
    >
      ES
    </button>
    <button
      :class="[
        'px-4 py-2 rounded-lg text-sm font-bold transition-all duration-300',
        currentLanguage === 'en'
          ? 'bg-indigo-600 text-white shadow-md'
          : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50',
      ]"
      @click="switchLanguage('en')"
    >
      EN
    </button>
  </div>
</template>
