<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiRequest } from '../../api/apiClient'
import BaseButton from '../../components/common/BaseButton.vue'

interface Props {
  node: any
  course: any
}

const props = defineProps<Props>()
const router = useRouter()
const route = useRoute()
const loading = ref(false)
const formSubmitting = ref(false)
const formData = reactive<Record<string, any>>({})

// Initialize formData when node changes
watch(() => props.node, (newNode) => {
  if (newNode?.type === 'form') {
    // Clear previous data
    Object.keys(formData).forEach(key => delete formData[key])
    // Initialize defaults if any
    newNode.content?.fields?.forEach((field: any) => {
      formData[field.name] = field.type === 'checkbox' ? false : ''
    })
  }
}, { immediate: true })
const isFormValid = computed(() => {
  if (props.node?.type !== 'form') return true
  
  const fields: any[] = props.node.content?.fields || []
  
  for (const field of fields) {
    const val = formData[field.name]
    
    // Helper to get defaults if not present in field config (for existing nodes)
    const getConstraint = (name: string, type: 'min' | 'max') => {
      if (type === 'min') {
        switch (name) {
          case 'name': return 5
          case 'phone': return 5
          case 'city': return 3
          case 'country': return 5
          case 'amway_code': return 8
          default: return 0
        }
      } else {
        switch (name) {
          case 'name': return 100
          case 'email': return 100
          case 'phone': return 25
          case 'city': return 50
          case 'country': return 50
          case 'amway_code': return 15
          default: return 255
        }
      }
    }

    const min = field.min ?? getConstraint(field.name, 'min')
    const max = field.max ?? getConstraint(field.name, 'max')

    // Checkboxes (accept_terms): always mandatory if present in the form
    if (field.type === 'checkbox') {
      if (!val) return false
      continue
    }
    
    // Required base validation
    if (field.required || field.name === 'name' || field.name === 'email') {
      if (!val || (typeof val === 'string' && val.trim() === '')) return false
    }
    
    // Only validate content if it's not empty (it's either valid-empty-optional or non-empty-at-this-point)
    const stringVal = String(val || '').trim()
    if (stringVal.length > 0) {
      if (min && stringVal.length < min) return false
      if (max && stringVal.length > max) return false

      // Email format validation
      if (field.type === 'email' || field.name === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(stringVal)) return false
      }
    }
  }
  
  return true
})

const syncProgressWithBackend = async (data: Record<string, any>) => {
  // 1. Recopilar progreso de localStorage
  const progress: Record<string, number> = {}
  const historyRaw = localStorage.getItem('course_history')
  const history: string[] = historyRaw ? JSON.parse(historyRaw) : []
  
  history.forEach(slug => {
    const saved = localStorage.getItem(`course_progress_${slug}`)
    if (saved) {
      progress[slug] = parseInt(saved, 10)
    }
  })

  // Cursos completados
  const completedRaw = localStorage.getItem('completed_courses')
  const completed: string[] = completedRaw ? JSON.parse(completedRaw) : []

  // Obtener código de acceso
  const accessDataRaw = localStorage.getItem('course_access')
  let code = ''
  if (accessDataRaw) {
    try {
      const accessData = JSON.parse(accessDataRaw)
      code = accessData.accessCode
    } catch (e) {
      console.error('Error reading access code from localStorage', e)
    }
  }

  if (!code) {
    console.error('No access code found in localStorage. Cannot sync.')
    return null
  }

  // 2. Build a clean prospect payload (known prospect fields only)
  const prospectPayload: Record<string, any> = {
    code,
    progress,   // always send as object, even if empty {}
    completed,
  }
  const knownProspectFields = ['name', 'email', 'phone', 'city', 'country', 'amway_code']
  knownProspectFields.forEach(key => {
    if (data[key] !== undefined && data[key] !== '') {
      prospectPayload[key] = data[key]
    }
  })

  return await apiRequest({
    method: 'POST',
    url: '/v1/public/prospect/sync',
    body: prospectPayload,
  })
}

const handleFormSubmit = async () => {
  if (!isFormValid.value) return

  formSubmitting.value = true
  
  try {
    // Buscar un campo de email en el formData
    const emailKey = Object.keys(formData).find(key => 
      key.toLowerCase().includes('email') || 
      (props.node.content?.fields?.find((f: any) => f.name === key)?.type === 'email')
    )
    
    if (emailKey && formData[emailKey]) {
      const response = await syncProgressWithBackend(formData)
      if (response && !response.success) {
        throw new Error(response.error?.message || 'Error saving data')
      }
      // Guardar también en localStorage local para referencia
      localStorage.setItem('prospect_email', formData[emailKey])
    }
    
    handleContinue() // Continuar al siguiente nodo después de enviar
  } catch (error: any) {
    console.error('Error in form submission:', error)
    alert('Hubo un error al guardar tus datos. Por favor, inténtalo de nuevo.')
  } finally {
    formSubmitting.value = false
  }
}

const handleNavigate = (nextNodeId: number | null) => {
  if (!nextNodeId) {
    console.warn('No next node ID provided')
    return
  }

  loading.value = true
  const entrepreneurSlug = route.params.entrepreneurSlug as string
  const courseSlug = route.params.courseSlug as string

  // Find the slug of the next node in the course nodes
  const nextNode = props.course?.nodes?.find((n: any) => n.id === nextNodeId)
  
  if (nextNode) {
    router.push(`/cursos/${entrepreneurSlug}/${courseSlug}/${nextNode.slug}`)
  } else {
    console.error('Next node not found in course data')
  }
  
  loading.value = false
}

const handleContinue = () => {
  if (props.node?.is_end) {
    if (props.course?.next_course?.slug) {
      loading.value = true
      const entrepreneurSlug = route.params.entrepreneurSlug
      const nextCourseSlug = props.course.next_course.slug
      
      // Update localStorage to allow access to next course
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
      
      router.push(`/cursos/${entrepreneurSlug}/${nextCourseSlug}`)
      loading.value = false
    } else {
      console.log('Course completed')
    }
    return
  }

  // Find next node ID from options (defaulting to first one if used via generic button)
  const nextNodeId = props.node?.options?.[0]?.next_node_id
  if (nextNodeId) {
    handleNavigate(nextNodeId)
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

const getFieldIcon = (fieldName: string) => {
  switch (fieldName) {
    case 'name': return 'user'
    case 'country': return 'globe'
    case 'city': return 'map-pin'
    case 'email': return 'envelope'
    case 'phone': return 'phone'
    case 'amway_code': return 'id-card'
    default: return null
  }
}
</script>

<template>
  <div v-if="!node" class="flex-1 flex items-center justify-center p-8 text-slate-400">
    {{ $t('course.noLesson') }}
  </div>
  
  <div v-else :class="['flex flex-col bg-white animate-fade-in', (node.type === 'menu' && !node.video_url) ? '' : 'h-full']">
    <!-- Header/Title section (hidden for form nodes) -->
    <div v-if="node.type !== 'form'" class="bg-slate-800 text-white px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-xs font-bold bg-white/10 px-2 py-1 rounded text-white/60">
            {{ node.type.toUpperCase() }}
        </span>
        <h1 class="font-bold text-lg">{{ node.title }}</h1>
      </div>
    </div>

    <!-- Video Player (if type is video or menu with video) -->
    <div v-if="node.type === 'video' || (node.type === 'menu' && node.video_url)" class="flex flex-col flex-1 overflow-y-auto">
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
      <div v-if="node.content?.description" class="px-12 py-3 prose max-w-none flex-1">
        <div class="text-slate-600 leading-relaxed whitespace-pre-wrap">
          {{ node.content.description }}
        </div>
      </div>
    </div>

    <!-- Form Content (if type is form) -->
    <div v-else-if="node.type === 'form'" class="flex-1 flex items-center justify-center overflow-y-auto">
      <div class="max-w-md mx-auto w-full">
        <div class="px-8 py-10">
          <h2 class="text-3xl font-bold text-slate-800 mb-8 text-center">
            {{ $t('course.form.title') || 'Introduce tus datos' }}
          </h2>
          
          <form @submit.prevent="handleFormSubmit" class="space-y-5">
            <template v-for="(field, index) in node.content?.fields" :key="index">
              <div v-if="field.type !== 'checkbox'" class="flex items-center gap-4">
                <div class="w-10 h-10 shrink-0 flex items-center justify-center text-slate-400">
                  <!-- Dynamic Icons -->
                  <svg v-if="getFieldIcon(field.name) === 'user'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                  <svg v-else-if="getFieldIcon(field.name) === 'globe'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                  <svg v-else-if="getFieldIcon(field.name) === 'map-pin'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                  <svg v-else-if="getFieldIcon(field.name) === 'envelope'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                  <svg v-else-if="getFieldIcon(field.name) === 'phone'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"/></svg>
                  <svg v-else-if="getFieldIcon(field.name) === 'id-card'" xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-8 h-8 opacity-70" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-7-2h5v-2h-5v2zm-4-2h2v-2H8v2zm0-4h2V9H8v2zm4 0h5V9h-5v2z"/></svg>
                  <div v-else class="w-8 h-8 bg-slate-100 rounded-full"></div>
                </div>
                <div class="flex-1">
                  <input 
                    :type="field.type || 'text'"
                    v-model="formData[field.name]"
                    :placeholder="$t(`course.form.fields.${field.name}`) || field.label"
                    class="w-full px-5 py-4 border-2 border-slate-100 bg-slate-50/50 rounded-xl focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-medium placeholder:text-slate-400"
                    :required="field.required"
                    :minlength="field.min"
                    :maxlength="field.max"
                  />
                </div>
              </div>
            </template>

            <!-- Bottom Separator -->
            <div class="border-t border-slate-100 pt-6 mt-4">
              <!-- Checkboxes (Terms, etc) -->
              <div v-for="field in node.content?.fields?.filter((f: any) => f.type === 'checkbox')" :key="field.name" class="flex items-center gap-3 mb-6">
                <input 
                  type="checkbox" 
                  :id="field.name" 
                  v-model="formData[field.name]"
                  class="w-5 h-5 rounded border-2 border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer"
                  :required="field.required"
                >
                <label :for="field.name" class="text-slate-700 font-bold text-lg cursor-pointer select-none">
                  {{ $t(`course.form.fields.${field.name}`) || field.label }}
                </label>
              </div>

              <button 
                type="submit"
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed disabled:transform-none text-white rounded-xl font-bold text-xl shadow-lg shadow-indigo-200 disabled:shadow-none transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2"
                :disabled="formSubmitting || !isFormValid"
              >
                <span v-if="formSubmitting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                <span v-else-if="!isFormValid" class="text-white/70 text-base">Completa los campos requeridos</span>
                <template v-else>
                  {{ node.content?.submit_label || $t('course.form.access') || 'Acceder' }}
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- HTML Content (if type is html) -->
    <div v-else-if="node.type === 'html'" class="flex-1 p-8 md:p-12 overflow-y-auto prose max-w-none">
        <div v-html="node.content?.body"></div>
    </div>

    <!-- Menu without video: show description if available -->
    <div v-else-if="node.type === 'menu'">
      <div v-if="node.content?.description" class="p-8 prose max-w-none">
        <div class="text-slate-600 leading-relaxed whitespace-pre-wrap">{{ node.content.description }}</div>
      </div>
    </div>

    <!-- Generic Content (meta, task, etc) -->
    <div v-else class="flex-1 p-8 flex flex-col items-center justify-center text-center">
        <p class="text-slate-500">{{ node.title }}</p>
    </div>

    <!-- Footer Action -->
    <div v-if="node.type !== 'form'" class="p-8 border-t border-slate-100 flex flex-col items-center gap-4">
      <div v-if="!node.is_end && node.options?.length > 0" :class="[
        'w-full gap-4',
        node.type === 'menu' 
          ? 'max-w-4xl grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3' 
          : 'max-w-sm flex flex-col'
      ]">
        <BaseButton 
          v-for="option in node.options" 
          :key="option.id"
          :text="(option.label?.startsWith('menu-btn-') ? node.content?.buttons?.[parseInt(option.label.replace('menu-btn-', ''))] : option.label) || $t('course.continue')"
          :action="() => handleNavigate(option.next_node_id)"
          class="shadow-lg h-full"
          :extra-props="{ loading: loading }"
        />
      </div>
      <div v-else-if="!node.is_end || course?.next_course_id" class="w-full max-w-sm">
        <BaseButton 
          :text="node.is_end ? (course.next_course_label || $t('course.next_course_default')) : $t('course.continue')"
          :action="handleContinue"
          :variant="'primary'"
          :extra-props="{
            loading: loading,
            loadingText: $t('course.preparing'),
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
