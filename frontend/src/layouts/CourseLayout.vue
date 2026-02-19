<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { apiRequest } from '../api/apiClient'
import LanguageSwitcher from '../components/common/LanguageSwitcher.vue'
import SocialIcon from '../components/common/SocialIcon.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const isLoading = ref(true)
const courseData = ref<any>(null)
const entrepreneurData = ref<any>(null)
const selectedNodeId = ref<number | null>(null)
const selectedNodeSlug = ref<string | null>(null)
const maxPositionReached = ref(0)
const courseHistory = ref<string[]>([])
const completedCourses = ref<string[]>([])

const entrepreneurSlug = computed(() => route.params.entrepreneurSlug as string)
const courseSlug = computed(() => route.params.courseSlug as string)

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const selectNode = (node: any) => {
  if (isNodeDisabled(node)) return
  router.push(`/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${node.slug}`)
  if (isSidebarOpen.value) toggleSidebar()
}

// Watch for URL changes to update state
watch(() => route.params.nodeSlug, (newNodeSlug) => {
  if (newNodeSlug) {
    selectedNodeSlug.value = newNodeSlug as string
    if (courseData.value?.nodes) {
      const node = courseData.value.nodes.find((n: any) => n.slug === newNodeSlug)
      if (node) selectedNodeId.value = node.id
    }
  }
}, { immediate: true })

const activeNode = computed(() => {
  const node = courseData.value?.nodes?.find((n: any) => n.id === selectedNodeId.value)
  if (node && node.position > maxPositionReached.value) {
    maxPositionReached.value = node.position
    localStorage.setItem(`course_progress_${courseSlug.value}`, node.position.toString())
    
    // Si es el nodo final, marcar el curso como completado
    if (node.is_end) {
      markCourseAsCompleted(courseSlug.value)
    }
  }
  return node
})

const markCourseAsCompleted = (slug: string) => {
  if (!completedCourses.value.includes(slug)) {
    completedCourses.value.push(slug)
    localStorage.setItem('completed_courses', JSON.stringify(completedCourses.value))
  }
}

const addToCourseHistory = (slug: string) => {
  if (!courseHistory.value.includes(slug)) {
    courseHistory.value.push(slug)
    localStorage.setItem('course_history', JSON.stringify(courseHistory.value))
  }
}

const switchCourse = (slug: string) => {
  if (slug === courseSlug.value) return
  
  // Actualizar acceso para el router/validación
  const accessData = localStorage.getItem('course_access')
  if (accessData) {
    const parsed = JSON.parse(accessData)
    parsed.courseSlug = slug
    localStorage.setItem('course_access', JSON.stringify(parsed))
  }
  
  router.push(`/cursos/${entrepreneurSlug.value}/${slug}`)
  if (isSidebarOpen.value) toggleSidebar()
}

const isNodeDisabled = (node: any) => {
  if (!activeNode.value) return true
  // Disable nodes from the current position onwards
  return node.position >= activeNode.value.position
}

const initializeCourse = () => {
  if (!validateAccess()) {
    router.push('/cursos');
    return;
  }

  // Cargar historial y completados
  const history = localStorage.getItem('course_history')
  if (history) courseHistory.value = JSON.parse(history)
  
  const completed = localStorage.getItem('completed_courses')
  if (completed) completedCourses.value = JSON.parse(completed)

  // Asegurar que el curso actual esté en el historial
  addToCourseHistory(courseSlug.value)
  
  const savedProgress = localStorage.getItem(`course_progress_${courseSlug.value}`)
  if (savedProgress) {
    maxPositionReached.value = parseInt(savedProgress, 10)
  } else {
    maxPositionReached.value = 0
  }
  
  fetchCourseData()
}

// Watch for course changes to re-initialize
watch(courseSlug, () => {
  initializeCourse()
})

const fetchCourseData = async () => {
  isLoading.value = true
  const result = await apiRequest({
    method: 'GET',
    url: `/v1/public/courses/${entrepreneurSlug.value}/${courseSlug.value}`
  });

  if (result.success && result.data) {
    courseData.value = result.data.course
    entrepreneurData.value = result.data.entrepreneur
    
    // Auto-select start node or use the one from URL
    const nodeSlugFromUrl = route.params.nodeSlug
    if (nodeSlugFromUrl) {
      selectedNodeSlug.value = nodeSlugFromUrl as string
      const node = courseData.value.nodes.find((n: any) => n.slug === nodeSlugFromUrl)
      if (node) selectedNodeId.value = node.id
    } else {
      const startNode = courseData.value.nodes.find((n: any) => n.is_start)
      if (startNode) {
        router.replace(`/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${startNode.slug}`)
      } else if (courseData.value.nodes.length > 0) {
        router.replace(`/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${courseData.value.nodes[0].slug}`)
      }
    }
  } else {
    console.error('Failed to fetch course data', result.error)
  }
  isLoading.value = false
}

const validateAccess = () => {
  const accessData = localStorage.getItem('course_access');
  if (!accessData) {
    return false;
  }

  try {
    const { entrepreneurSlug: storedEntrepreneur, courseSlug: storedCourse } = JSON.parse(accessData);
    return storedEntrepreneur === entrepreneurSlug.value && storedCourse === courseSlug.value;
  } catch (e) {
    return false;
  }
}

onMounted(() => {
  initializeCourse()
})

const getSocialUrl = (platform: string, value: string) => {
  const p = platform.toLowerCase()
  switch (p) {
    case 'whatsapp': return `https://wa.me/${value}`
    case 'telegram': return `https://t.me/${value}`
    case 'facebook': return `https://facebook.com/${value}`
    case 'instagram': return `https://instagram.com/${value}`
    case 'tiktok': return `https://tiktok.com/@${value}`
    case 'youtube': return `https://youtube.com/@${value}`
    case 'email_contact': return `mailto:${value}`
    default: return value.startsWith('http') ? value : `https://${value}`
  }
}

const getSocialLabel = (platform: string, value: string) => {
  const p = platform.toLowerCase()
  if (p === 'instagram' || p === 'tiktok' || p === 'youtube') return `@${value}`
  if (p === 'website') return value.replace('https://', '').replace('http://', '')
  return value
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-900">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-40 shadow-sm">
      <div class="flex items-center gap-4">
        <button 
          @click="toggleSidebar"
          class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-600"
          aria-label="Toggle Sidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">D</div>
          <span class="font-bold text-lg hidden md:block">{{ courseData?.title || t('course.loading') }}</span>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <LanguageSwitcher />
        <div v-if="authStore.user" class="bg-slate-100 px-3 py-1 rounded-full text-xs font-bold text-slate-500 uppercase">
          {{ authStore.user?.codigo_amway }}
        </div>
      </div>
    </header>

    <div class="flex flex-1 relative">
      <!-- Sidebar / Drawer -->
      <aside 
        class="fixed inset-y-0 left-0 z-50 w-80 bg-white shadow-2xl transform transition-transform duration-300 ease-in-out border-r border-slate-200"
        :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      >
        <div class="flex flex-col h-full">
          <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 flex-shrink-0">
            <span class="font-bold text-lg">{{ t('course.sidebarTitle') }}</span>
            <button @click="toggleSidebar" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="flex-1 overflow-y-auto p-4 space-y-6">
            <!-- Selector de Cursos (Historial) -->
            <div v-if="courseHistory.length > 1" class="space-y-2">
              <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">Mis Cursos</h3>
              <div class="grid gap-1">
                <button 
                  v-for="slug in courseHistory" 
                  :key="slug"
                  @click="switchCourse(slug)"
                  class="flex items-center gap-2 p-2 rounded-lg text-sm transition-all text-left"
                  :class="slug === courseSlug ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50'"
                >
                  <div 
                    class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="slug === courseSlug ? 'bg-white/20' : 'bg-slate-100'"
                  >
                    <svg v-if="completedCourses.includes(slug)" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span v-else class="text-[8px] font-bold">...</span>
                  </div>
                  <span class="truncate">{{ slug === courseSlug ? (courseData?.title || slug) : slug }}</span>
                </button>
              </div>
            </div>

            <!-- Nodos del Curso Actual -->
            <div class="space-y-2">
              <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">{{ courseData?.title }}</h3>
              <nav class="space-y-1">
                <div 
                  v-for="node in courseData?.nodes" 
                  :key="node.id"
                  @click="selectNode(node)"
                  class="group flex items-center justify-between p-3 rounded-xl transition-all border border-transparent"
                  :class="[
                    selectedNodeId === node.id ? 'bg-indigo-50 border-indigo-100' : 'hover:bg-slate-50 cursor-pointer hover:border-slate-100',
                    isNodeDisabled(node) ? 'opacity-50 grayscale pointer-events-none cursor-not-allowed bg-slate-50/50' : ''
                  ]"
                >
                  <div class="flex items-center gap-3">
                    <div 
                      class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold bg-slate-100 text-slate-400"
                    >
                      <span>{{ node.position }}</span>
                    </div>
                    <span class="text-sm font-medium text-slate-900">{{ node.title }}</span>
                  </div>
                  <div v-if="node.type === 'video'" class="text-slate-300">
                    <svg v-if="isNodeDisabled(node) && node.position > (activeNode?.position || 0)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    </svg>
                  </div>
                </div>
              </nav>
            </div>
          </div>
        </div>

      </aside>

      <!-- Overlay for Sidebar -->
      <div 
        v-if="isSidebarOpen" 
        @click="toggleSidebar"
        class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-40"
      ></div>

      <!-- Main Content Area -->
      <main class="flex-1 flex flex-col p-4 md:p-8 max-w-5xl mx-auto w-full">
        <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
            <p class="text-slate-400 font-medium">{{ t('course.loading') }}</p>
        </div>
        <div v-else class="flex-1 bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col border border-slate-200/50">
          <router-view :node="activeNode" :course="courseData"></router-view>
        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 px-6 py-4 sticky bottom-0 z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
      <div class="max-w-5xl mx-auto flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-full flex items-center justify-center overflow-hidden border-2 border-white shadow-sm">
            <img :src="`https://ui-avatars.com/api/?name=${entrepreneurData?.name}+${entrepreneurData?.last_name}&background=random`" alt="Avatar" class="w-full h-full object-cover" />
          </div>
          <div>
            <div class="font-bold text-slate-900 leading-tight">{{ entrepreneurData?.name }} {{ entrepreneurData?.last_name }}</div>
            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-1.5" v-if="entrepreneurData?.social">
              <a 
                v-for="(value, platform) in (entrepreneurData.social as Record<string, string>)"
                :key="platform"
                :href="getSocialUrl(platform, value)"
                target="_blank"
                class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 transition-colors group"
              >
                <SocialIcon :platform="platform" size="w-4 h-4" class="group-hover:scale-110" />
                {{ getSocialLabel(platform, value) }}
              </a>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <a 
            v-if="entrepreneurData?.social?.whatsapp"
            :href="`https://wa.me/${entrepreneurData.social.whatsapp}`"
            target="_blank"
            class="bg-gradient-to-r from-emerald-400 to-teal-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg hover:shadow-emerald-200/50 transition-all flex items-center gap-2 transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <SocialIcon platform="whatsapp" size="w-5 h-5" class="bg-transparent shadow-none" />
            {{ t('course.contact') }}
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Any additional custom styling */
</style>
