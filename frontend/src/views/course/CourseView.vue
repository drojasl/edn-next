<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../components/common/BaseButton.vue'

interface Props {
  node: any
  course: any
}

const props = defineProps<Props>()
const router = useRouter()
const route = useRoute()
const loading = ref(false)

const handleContinue = () => {
  if (props.node?.is_end) {
    if (props.course?.next_course?.slug) {
      loading.value = true
      const entrepreneurSlug = route.params.entrepreneurSlug
      const nextCourseSlug = props.course.next_course.slug
      
      // Actualizar localStorage para permitir el acceso al siguiente curso
      const accessData = localStorage.getItem('course_access')
      if (accessData) {
        try {
          const parsed = JSON.parse(accessData)
          parsed.courseSlug = nextCourseSlug
          localStorage.setItem('course_access', JSON.stringify(parsed))
        } catch (e) {
          console.error('Error updating access data', e)
        }
      }
      
      // Redirigir al siguiente curso
      // Nota: El backend ya devuelve el objeto nextCourse con su slug gracias al controller
      router.push(`/cursos/${entrepreneurSlug}/${nextCourseSlug}`)
      loading.value = false
    } else {
      // Handle course completion without next course
      console.log('Course completed')
    }
    return
  }

  // Find next node ID from options
  const nextNodeId = props.node?.options?.[0]?.next_node_id

  if (nextNodeId) {
    loading.value = true
    const entrepreneurSlug = route.params.entrepreneurSlug
    const courseSlug = route.params.courseSlug

    // Find the slug of the next node in the course nodes
    const nextNode = props.course?.nodes?.find((n: any) => n.id === nextNodeId)
    
    if (nextNode) {
      router.push(`/cursos/${entrepreneurSlug}/${courseSlug}/${nextNode.slug}`)
    } else {
      console.error('Next node not found in course data')
    }
    
    loading.value = false
  } else {
    console.warn('No next node found in options')
  }
}

const getYoutubeId = (url: string) => {
  if (!url) return null
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
  const match = url.match(regExp)
  const videoId = match?.[2]
  return (videoId && videoId.length === 11) ? videoId : null
}
</script>

<template>
  <div v-if="!node" class="flex-1 flex items-center justify-center p-8 text-slate-400">
    Selecciona una lección para comenzar
  </div>
  
  <div v-else class="flex flex-col h-full bg-white animate-fade-in">
    <!-- Header/Title section -->
    <div class="bg-slate-800 text-white px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-xs font-bold bg-white/10 px-2 py-1 rounded text-white/60">
            {{ node.type.toUpperCase() }}
        </span>
        <h1 class="font-bold text-lg">{{ node.title }}</h1>
      </div>
    </div>

    <!-- Video Player (if type is video) -->
    <div v-if="node.type === 'video'" class="flex flex-col flex-1 overflow-y-auto">
      <div class="aspect-video bg-slate-900 shadow-inner">
        <iframe 
          v-if="getYoutubeId(node.video_url)"
          class="w-full h-full"
          :src="`https://www.youtube.com/embed/${getYoutubeId(node.video_url)}?rel=0&modestbranding=1`"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>
        <div v-else class="w-full h-full flex items-center justify-center text-slate-500 italic">
          {{ $t('course.no_video_available') || 'Video no disponible' }}
        </div>
      </div>

      <!-- Description Section -->
      <div v-if="node.content?.description" class="p-8 md:p-12 prose max-w-none flex-1">
        <h3 class="text-slate-800 font-bold mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-indigo-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
          </svg>
          {{ $t('course.node.description') || 'Descripción de la lección' }}
        </h3>
        <div class="text-slate-600 leading-relaxed whitespace-pre-wrap">
          {{ node.content.description }}
        </div>
      </div>
    </div>

    <!-- HTML Content (if type is html) -->
    <div v-else-if="node.type === 'html'" class="flex-1 p-8 md:p-12 overflow-y-auto prose max-w-none">
        <div v-html="node.content?.body"></div>
    </div>

    <!-- Generic Content (meta, task, etc) -->
    <div v-else class="flex-1 p-8 flex flex-col items-center justify-center text-center">
        <p class="text-slate-500">{{ node.title }}</p>
    </div>

    <!-- Footer Action -->
    <div class="p-8 border-t border-slate-100 flex justify-center">
      <div class="w-full max-w-sm">
        <BaseButton 
          :text="node.is_end ? (course?.next_course_id ? (course.next_course_label || $t('course.next_course_default')) : $t('course.finish')) : $t('course.continue')"
          :action="handleContinue"
          :variant="node.is_end ? 'primary' : 'primary'"
          :extra-props="{
            loading: loading,
            loadingText: 'Preparando...',
            class: 'shadow-lg'
          }"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
