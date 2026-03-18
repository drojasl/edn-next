<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  platform: string
  size?: string
}>()

const iconSize = computed(() => props.size || 'w-4 h-4')

// Mapping of platform to specific colors if needed, but we'll use classes for flexibility
const platformClass = computed(() => {
  const p = props.platform?.toLowerCase()
  switch (p) {
    case 'whatsapp': return 'bg-emerald-500'
    case 'telegram': return 'bg-sky-500'
    case 'facebook': return 'bg-blue-600'
    case 'instagram': return 'bg-gradient-to-tr from-orange-400 to-pink-600'
    case 'youtube': return 'bg-red-600'
    case 'cell_phone': return 'bg-slate-700'
    case 'email':
    case 'email_contact': return 'bg-slate-500'
    default: return 'bg-slate-400'
  }
})
</script>

<template>
  <div :class="[iconSize, platformClass, 'rounded-md flex items-center justify-center text-white shadow-sm transition-transform duration-300']">
    <!-- WhatsApp -->
    <svg v-if="platform === 'whatsapp'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
    </svg>

    <!-- Telegram -->
    <svg v-else-if="platform === 'telegram'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>
    </svg>

    <!-- Facebook -->
    <svg v-else-if="platform === 'facebook'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
    </svg>

    <!-- Instagram -->
    <svg v-else-if="platform === 'instagram'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
      <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
      <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
    </svg>

    <!-- TikTok -->
    <svg v-else-if="platform === 'tiktok'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>
    </svg>

    <!-- YouTube -->
    <svg v-else-if="platform === 'youtube'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 2-2h15a2 2 0 0 1 2 2 24.12 24.12 0 0 1 0 10 2 2 0 0 1-2 2h-15a2 2 0 0 1-2-2Z"/>
      <path d="m10 15 5-3-5-3z"/>
    </svg>

    <!-- Cell Phone -->
    <svg v-else-if="platform === 'cell_phone'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>

    <!-- Email -->
    <svg v-else-if="platform === 'email' || platform === 'email_contact'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>
    </svg>

    <!-- Generic Fallback (Link) -->
    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2/3 h-2/3">
      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
      <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
    </svg>
  </div>
</template>
