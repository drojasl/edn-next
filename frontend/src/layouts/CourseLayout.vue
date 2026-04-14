<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { apiRequest } from '../api/apiClient'
import LanguageSwitcher from '../components/common/LanguageSwitcher.vue'
import SocialIcon from '../components/common/SocialIcon.vue'

import { type User, type Course, type CourseNodeData } from '../types'
import BaseButton from '../components/common/BaseButton.vue'

interface EntrepreneurPublic extends User {
  social?: Record<string, string>
}

interface CoursePublic extends Course {
  nodes: CourseNodeData[]
}

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

interface StartedCourse {
  slug: string
  title: string
  url: string
  entrepreneurSlug: string
}

const isSidebarOpen = ref(false)
const startedCourses = ref<StartedCourse[]>([])

const getCoursesFromCookie = (): StartedCourse[] => {
  const match = document.cookie.match(
    new RegExp('(^| )started_courses=([^;]+)')
  )
  if (match) {
    try {
      return JSON.parse(decodeURIComponent(match[2] as string))
    } catch (e) {
      return []
    }
  }
  return []
}

const saveCourseToCookie = (course: StartedCourse) => {
  const courses = getCoursesFromCookie()
  const existingIndex = courses.findIndex((c) => c.slug === course.slug)
  if (existingIndex >= 0) {
    courses[existingIndex] = course
  } else {
    courses.push(course)
  }
  const d = new Date()
  d.setTime(d.getTime() + 365 * 24 * 60 * 60 * 1000) // 1 year
  document.cookie = `started_courses=${encodeURIComponent(
    JSON.stringify(courses)
  )};expires=${d.toUTCString()};path=/`
  startedCourses.value = courses
}

const goToCourse = (c: StartedCourse) => {
  const accessData = localStorage.getItem('course_access')
  if (accessData) {
    try {
      const parsed = JSON.parse(accessData)
      parsed.courseSlug = c.slug
      parsed.entrepreneurSlug = c.entrepreneurSlug
      localStorage.setItem('course_access', JSON.stringify(parsed))
    } catch (e) {
      console.error(e)
    }
  }

  const targetUrl = {
    path: c.url,
    query: route.query,
  }

  // If we are already in the same course, we use replace to avoid duplicate history entry
  // especially if navigating back to the start node of the same course
  if (courseSlug.value === c.slug && route.path !== c.url) {
    router.replace(targetUrl)
  } else if (courseSlug.value !== c.slug) {
    router.push(targetUrl)
  }

  if (isSidebarOpen.value) toggleSidebar()
}
const isLoading = ref(true)
const courseData = ref<CoursePublic | null>(null)
const entrepreneurData = ref<EntrepreneurPublic | null>(null)
const selectedNodeId = ref<number | null>(null)
const selectedNodeSlug = ref<string | null>(null)
const maxPositionReached = ref(0)
const courseHistory = ref<string[]>([])
const completedCourses = ref<string[]>([])
const showRecoveryModal = ref(false)
const recoveryEmail = ref('')
const recovering = ref(false)
const recoveryError = ref('')

const entrepreneurSlug = computed(() => route.params.entrepreneurSlug as string)
const courseSlug = computed(() => route.params.courseSlug as string)

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

// Watch for URL changes to update state
watch(
  () => route.params.nodeSlug,
  (newNodeSlug) => {
    if (newNodeSlug && newNodeSlug !== selectedNodeSlug.value) {
      selectedNodeSlug.value = newNodeSlug as string
      if (courseData.value?.nodes) {
        const node = courseData.value.nodes.find(
          (n: CourseNodeData) => n.slug === newNodeSlug
        )
        if (node) selectedNodeId.value = node.id
      }
    }
  },
  { immediate: true }
)

const activeNode = computed(() => {
  if (selectedNodeId.value === null || !courseData.value?.nodes)
    return undefined
  return courseData.value.nodes.find(
    (n: CourseNodeData) => n.id == selectedNodeId.value
  )
})

watch(
  activeNode,
  (node) => {
    // Actualizar título de la página para el historial del navegador
    if (node && courseData.value) {
      document.title = `${(node as CourseNodeData).title} | ${courseData.value.title}`
    } else if (courseData.value) {
      document.title = courseData.value.title
    }

    const n = node as CourseNodeData & { position: number }
    if (n && n.position > maxPositionReached.value) {
      maxPositionReached.value = n.position
      localStorage.setItem(
        `course_progress_${courseSlug.value}`,
        n.position.toString()
      )

      // Si ya tenemos el email del prospecto, sincronizar con el backend
      const prospectEmail = localStorage.getItem('prospect_email')
      if (prospectEmail) {
        syncCurrentProgress(prospectEmail)
      }

      // Si es el nodo final, marcar el curso como completado
      if ((node as CourseNodeData).is_end) {
        markCourseAsCompleted(courseSlug.value)
      }
    }
  },
  { immediate: true }
)

const syncCurrentProgress = async (email: string) => {
  const accessDataRaw = localStorage.getItem('course_access')
  let code = ''
  if (accessDataRaw) {
    try {
      const accessData = JSON.parse(accessDataRaw)
      code = accessData.accessCode
    } catch (e: unknown) {
      console.error(e)
    }
  }

  if (!code) return

  // Recopilar progreso actual
  const progress: Record<string, number> = {}
  courseHistory.value.forEach((slug) => {
    const saved = localStorage.getItem(`course_progress_${slug}`)
    if (saved) progress[slug] = parseInt(saved, 10)
  })

  await apiRequest({
    method: 'POST',
    url: '/v1/public/prospect/sync',
    body: {
      email,
      code,
      progress,
      completed: completedCourses.value,
    },
  })
}

const markCourseAsCompleted = (slug: string) => {
  if (!completedCourses.value.includes(slug)) {
    completedCourses.value.push(slug)
    localStorage.setItem(
      'completed_courses',
      JSON.stringify(completedCourses.value)
    )
  }
}

const addToCourseHistory = (slug: string) => {
  if (!courseHistory.value.includes(slug)) {
    courseHistory.value.push(slug)
    localStorage.setItem('course_history', JSON.stringify(courseHistory.value))
  }
}

const initializeCourse = () => {
  // Cargar historial y completados PRIMERO para que validateAccess pueda usarlos
  const history = localStorage.getItem('course_history')
  if (history) courseHistory.value = JSON.parse(history)

  const completed = localStorage.getItem('completed_courses')
  if (completed) completedCourses.value = JSON.parse(completed)

  if (!validateAccess()) {
    router.replace('/cursos')
    return
  }

  // Asegurar que el curso actual esté en el historial
  addToCourseHistory(courseSlug.value)

  const savedProgress = localStorage.getItem(
    `course_progress_${courseSlug.value}`
  )
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
    url: `/v1/public/courses/${entrepreneurSlug.value}/${courseSlug.value}`,
  })

  if (result.success && result.data) {
    const data = result.data as {
      course: CoursePublic
      entrepreneur: EntrepreneurPublic
    }
    courseData.value = data.course
    entrepreneurData.value = data.entrepreneur

    const firstOrStartNode =
      courseData.value.nodes.find((n: CourseNodeData) => n.is_start) ||
      courseData.value.nodes[0]

    if (firstOrStartNode) {
      saveCourseToCookie({
        slug: courseSlug.value,
        title: courseData.value.title,
        url: `/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${firstOrStartNode.slug}`,
        entrepreneurSlug: entrepreneurSlug.value,
      })
    }

    // Auto-select start node or use the one from URL
    const nodeSlugFromUrl = route.params.nodeSlug
    if (nodeSlugFromUrl && courseData.value) {
      selectedNodeSlug.value = nodeSlugFromUrl as string
      const node = courseData.value.nodes.find(
        (n: CourseNodeData) => n.slug === nodeSlugFromUrl
      )
      if (node) selectedNodeId.value = node.id
    } else if (courseData.value) {
      const startNode = courseData.value.nodes.find(
        (n: CourseNodeData) => n.is_start
      )
      if (startNode && route.params.nodeSlug !== startNode.slug) {
        router.replace({
          path: `/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${startNode.slug}`,
          query: route.query,
        })
      } else {
        const firstNode = courseData.value?.nodes?.[0]
        if (firstNode) {
          router.replace({
            path: `/cursos/${entrepreneurSlug.value}/${courseSlug.value}/${firstNode.slug}`,
            query: route.query,
          })
        }
      }
    }
  } else {
    console.error('Failed to fetch course data', result.error)
  }
  isLoading.value = false
}

const validateAccess = () => {
  const accessDataRaw = localStorage.getItem('course_access')
  if (!accessDataRaw) {
    return false
  }

  try {
    const accessData = JSON.parse(accessDataRaw)
    const { entrepreneurSlug: storedEntrepreneur, courseSlug: storedCourse } =
      accessData

    // Caso 1: Coincidencia directa
    if (
      storedEntrepreneur === entrepreneurSlug.value &&
      storedCourse === courseSlug.value
    ) {
      return true
    }

    // Caso 2: El curso está en el historial (el usuario está volviendo atrás o navegando entre sus cursos)
    if (
      storedEntrepreneur === entrepreneurSlug.value &&
      courseHistory.value.includes(courseSlug.value)
    ) {
      // Sincronizamos el course_access para que coincida con la URL actual
      accessData.courseSlug = courseSlug.value
      localStorage.setItem('course_access', JSON.stringify(accessData))
      return true
    }

    return false
  } catch (e) {
    return false
  }
}

onMounted(() => {
  startedCourses.value = getCoursesFromCookie()
  initializeCourse()
})

const handleRecoverProgress = async () => {
  if (!recoveryEmail.value) return
  recovering.value = true
  recoveryError.value = ''

  const accessDataRaw = localStorage.getItem('course_access')
  let code = ''
  if (accessDataRaw) {
    try {
      const accessData = JSON.parse(accessDataRaw)
      code = accessData.accessCode
    } catch (e) {
      console.error('Error parsing access data', e)
    }
  }

  if (!code) {
    recoveryError.value = t('course.recovery.error_no_code')
    recovering.value = false
    return
  }

  const result = await apiRequest({
    method: 'POST',
    url: '/v1/public/prospect/recover',
    body: {
      email: recoveryEmail.value,
      code,
    },
  })

  if (result.success && result.data) {
    const data = result.data as {
      progress: Record<string, number>
      completed: string[]
      history: string[]
    }
    const { progress, completed, history } = data

    // Nodes progress
    if (progress) {
      Object.entries(progress).forEach(([slug, pos]) => {
        localStorage.setItem(`course_progress_${slug}`, String(pos))
      })
    }

    // Completed courses
    if (completed) {
      localStorage.setItem('completed_courses', JSON.stringify(completed))
    }

    // History
    if (history && history.length > 0) {
      localStorage.setItem('course_history', JSON.stringify(history))
    }

    // Guardar email para futuras sincronizaciones
    localStorage.setItem('prospect_email', recoveryEmail.value)

    showRecoveryModal.value = false
    recoveryEmail.value = ''

    // Recargar todo el estado
    initializeCourse()
  } else {
    recoveryError.value =
      result.error?.message || t('course.recovery.error_not_found')
  }
  recovering.value = false
}

const getSocialUrl = (platform: string, value: string) => {
  const p = platform.toLowerCase()
  switch (p) {
    case 'whatsapp':
      return `https://wa.me/${value}`
    case 'telegram':
      return `https://t.me/${value}`
    case 'facebook':
      return `https://facebook.com/${value}`
    case 'instagram':
      return `https://instagram.com/${value}`
    case 'tiktok':
      return `https://tiktok.com/@${value}`
    case 'youtube':
      return `https://youtube.com/@${value}`
    case 'email_contact':
      return `mailto:${value}`
    case 'cell_phone':
      return `tel:${value.replace(/\s+/g, '')}`
    default:
      return value.startsWith('http') ? value : `https://${value}`
  }
}

const getSocialLabel = (platform: string, value: string) => {
  const p = platform.toLowerCase()
  if (p === 'instagram' || p === 'tiktok' || p === 'youtube') return `@${value}`
  if (p === 'website')
    return value.replace('https://', '').replace('http://', '')
  return value
}

const getFullUrl = (path: string | null) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const baseUrl =
    import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ||
    'http://localhost:8000'
  return `${baseUrl}${path}`
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-900">
    <!-- Header -->
    <header
      class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-40 shadow-sm"
    >
      <div class="flex items-center gap-4">
        <button
          class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-600"
          aria-label="Toggle Sidebar"
          @click="toggleSidebar"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
        <div class="flex items-center gap-2">
          <div
            class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold overflow-hidden"
          >
            <img
              v-if="entrepreneurData?.profile_picture"
              :src="getFullUrl(entrepreneurData.profile_picture)"
              class="w-full h-full object-cover"
              alt="Avatar"
            />
            <span v-else>{{
              entrepreneurData?.name?.charAt(0) ||
              courseData?.title?.charAt(0) ||
              'C'
            }}</span>
          </div>
          <span class="font-bold text-lg hidden md:block">{{
            courseData?.title || t('course.loading')
          }}</span>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <LanguageSwitcher />
        <div
          v-if="authStore.user"
          class="bg-slate-100 px-3 py-1 rounded-full text-xs font-bold text-slate-500 uppercase"
        >
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
          <div
            class="h-16 flex items-center justify-between px-6 border-b border-slate-100 flex-shrink-0"
          >
            <span class="font-bold text-lg">Mis Cursos</span>
            <button
              class="p-2 hover:bg-slate-100 rounded-lg text-slate-400"
              @click="toggleSidebar"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-4 flex flex-col">
            <!-- Cursos Iniciados -->
            <div class="space-y-3 flex-1">
              <nav class="space-y-2">
                <button
                  v-for="c in startedCourses"
                  :key="c.slug"
                  class="w-full group flex items-center justify-between p-3 rounded-xl transition-all border text-left"
                  :class="
                    c.slug === courseSlug
                      ? 'bg-indigo-50 border-indigo-100 shadow-sm'
                      : 'border-transparent hover:bg-slate-50 hover:border-slate-100'
                  "
                  @click="goToCourse(c)"
                >
                  <div class="flex items-center gap-3 w-full">
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                      :class="
                        c.slug === courseSlug
                          ? 'bg-indigo-100 text-indigo-600'
                          : 'bg-slate-100 text-slate-400'
                      "
                    >
                      <!-- Progress or Course Icon -->
                      <svg
                        v-if="completedCourses.includes(c.slug)"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-emerald-500"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                        />
                      </svg>
                    </div>
                    <span
                      class="text-sm font-medium line-clamp-2"
                      :class="
                        c.slug === courseSlug
                          ? 'text-indigo-900 font-bold'
                          : 'text-slate-700 group-hover:text-slate-900'
                      "
                    >
                      {{ c.title }}
                    </span>
                  </div>
                </button>
              </nav>

              <div
                v-if="startedCourses.length === 0"
                class="text-center py-8 px-4"
              >
                <div
                  class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-slate-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    />
                  </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">
                  Aún no has iniciado cursos.
                </p>
              </div>
            </div>

            <!-- Botón de Retomar Progreso -->
            <div class="pt-4 border-t border-slate-100">
              <button
                class="w-full flex items-center justify-center gap-2 p-3 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-transparent hover:border-indigo-100 group"
                @click="showRecoveryModal = true"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 transition-transform group-hover:rotate-12"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                  />
                </svg>
                {{ t('course.recovery.button') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Recovery Modal -->
        <div
          v-if="showRecoveryModal"
          class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
          <div
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            @click="showRecoveryModal = false"
          ></div>
          <div
            class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in"
          >
            <div class="p-6 border-b border-slate-100">
              <h3 class="text-xl font-bold text-slate-900">
                {{ t('course.recovery.title') }}
              </h3>
              <p class="text-sm text-slate-500 mt-1">
                {{ t('course.recovery.subtitle') }}
              </p>
            </div>

            <form class="p-6 space-y-4" @submit.prevent="handleRecoverProgress">
              <div class="space-y-2">
                <label
                  for="recovery-email"
                  class="block text-sm font-bold text-slate-700 ml-1"
                  >{{ t('course.recovery.email_label') }}</label
                >
                <input
                  id="recovery-email"
                  v-model="recoveryEmail"
                  type="email"
                  :placeholder="t('course.recovery.email_placeholder')"
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none"
                  required
                />
              </div>

              <div
                v-if="recoveryError"
                class="p-3 bg-rose-50 text-rose-600 rounded-xl text-xs font-medium border border-rose-100"
              >
                {{ recoveryError }}
              </div>

              <div class="flex gap-3 pt-2">
                <button
                  type="button"
                  class="flex-1 px-4 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-50 transition-all"
                  @click="showRecoveryModal = false"
                >
                  {{ t('common.cancel') }}
                </button>
                <BaseButton
                  :text="t('course.recovery.button')"
                  type="submit"
                  :extra-props="{
                    loading: recovering,
                    class: 'flex-[2] shadow-lg shadow-indigo-100',
                  }"
                />
              </div>
            </form>
          </div>
        </div>
      </aside>

      <!-- Overlay for Sidebar -->
      <div
        v-if="isSidebarOpen"
        class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-40"
        @click="toggleSidebar"
      ></div>

      <!-- Main Content Area -->
      <main
        class="flex-1 flex flex-col justify-center p-4 md:p-8 max-w-5xl mx-auto w-full"
      >
        <div
          v-if="isLoading"
          class="flex-1 flex flex-col items-center justify-center"
        >
          <div
            class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"
          ></div>
          <p class="text-slate-400 font-medium">{{ t('course.loading') }}</p>
        </div>
        <div
          v-else
          class="bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col border border-slate-200/50 flex-1"
        >
          <router-view
            :key="route.path"
            :node="activeNode"
            :course="courseData"
            :entrepreneur="entrepreneurData"
          ></router-view>
        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer
      class="bg-white border-t border-slate-200 px-6 py-4 sticky bottom-0 z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]"
    >
      <div
        class="max-w-5xl mx-auto flex items-center justify-between flex-wrap gap-4"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-full flex items-center justify-center overflow-hidden border-2 border-white shadow-sm"
          >
            <img
              v-if="entrepreneurData?.profile_picture"
              :src="getFullUrl(entrepreneurData.profile_picture)"
              alt="Avatar"
              class="w-full h-full object-cover"
            />
            <img
              v-else
              :src="`https://ui-avatars.com/api/?name=${entrepreneurData?.name}+${entrepreneurData?.last_name}&background=random`"
              alt="Avatar"
              class="w-full h-full object-cover"
            />
          </div>
          <div>
            <div class="font-bold text-slate-900 leading-tight">
              {{ entrepreneurData?.name }} {{ entrepreneurData?.last_name }}
            </div>
            <div
              v-if="entrepreneurData?.social || entrepreneurData?.email"
              class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-1.5"
            >
              <a
                v-if="entrepreneurData?.email"
                :href="'mailto:' + entrepreneurData.email"
                class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 transition-colors group"
                :title="entrepreneurData.email"
              >
                <SocialIcon
                  platform="email"
                  size="w-4 h-4"
                  class="group-hover:scale-110"
                />
                <span>{{ entrepreneurData.email }}</span>
              </a>
              <a
                v-for="(value, platform) in entrepreneurData.social as Record<
                  string,
                  string
                >"
                :key="platform"
                :href="getSocialUrl(platform, value)"
                target="_blank"
                class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 transition-colors group"
                :title="getSocialLabel(platform, value)"
              >
                <SocialIcon
                  :platform="platform"
                  size="w-4 h-4"
                  class="group-hover:scale-110"
                />
                <span v-if="['whatsapp', 'cell_phone'].includes(platform)">{{
                  getSocialLabel(platform, value)
                }}</span>
              </a>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <a
            v-if="activeNode?.meeting_link"
            :href="activeNode.meeting_link"
            target="_blank"
            class="bg-gradient-to-r from-emerald-400 to-teal-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg hover:shadow-emerald-200/50 transition-all flex items-center gap-2 transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke="currentColor"
              class="w-5 h-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
              />
            </svg>
            {{ t('course.schedule_meeting') }}
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Any additional custom styling */
</style>
