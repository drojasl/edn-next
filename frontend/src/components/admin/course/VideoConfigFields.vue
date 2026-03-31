<script setup lang="ts">
import { computed } from 'vue'

// Multi-v-model for parent synchronization
const videoUrl = defineModel<string | undefined>('videoUrl')
const playbackSpeed = defineModel<number | undefined>('playbackSpeed')

// Utility to extract ID for preview
const getYoutubeId = (url: string | undefined) => {
    if (!url) return null
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
    const match = url.match(regExp)
    const videoId = match?.[2]
    return (videoId && videoId.length === 11) ? videoId : null
}

const videoPreviewId = computed(() => getYoutubeId(videoUrl.value))

interface Props {
    label?: string
}
defineProps<Props>()

</script>

<template>
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label v-if="label" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                {{ label }}
            </label>
            <input 
                v-model="videoUrl"
                type="text" 
                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                placeholder="https://youtube.com/..."
            />
        </div>

        <div class="w-1/6 shrink-0">
            <select 
                v-model="playbackSpeed"
                class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold text-sm h-[46px]"
            >
                <option :value="1.0">1x</option>
                <option :value="1.1">1.1x</option>
                <option :value="1.2">1.2x</option>
                <option :value="1.25">1.25x</option>
                <option :value="1.5">1.5x</option>
                <option :value="2.0">2x</option>
            </select>
        </div>

        <div v-if="videoPreviewId" class="w-24 shrink-0 rounded-lg overflow-hidden border border-slate-200 shadow-sm">
            <div class="aspect-video bg-slate-900">
                <iframe 
                    class="w-full h-full"
                    :src="`https://www.youtube.com/embed/${videoPreviewId}?rel=0&modestbranding=1`"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    </div>
</template>
