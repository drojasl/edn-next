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
    // Handle course completion
    console.log('Course completed')
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
    <div v-if="node.type === 'video'" class="aspect-video bg-slate-900 flex items-center justify-center">
      <div class="relative w-full h-full flex items-center justify-center group cursor-pointer">
        <!-- Placeholder for actual player (YouTube/Vimeo/etc) -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center">
             <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center border border-white/20 shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white fill-current" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
             </div>
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
          :text="node.is_end ? 'Finalizar Curso' : 'Continuar'"
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
