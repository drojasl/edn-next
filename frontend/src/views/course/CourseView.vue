<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiRequest } from '../../api/apiClient'
import {
  type CourseNodeData,
  type Course,
  type ApiError,
  type CourseNodeField,
  type MenuButton,
  type CourseNodeOption,
} from '../../types'
import BaseButton from '../../components/common/BaseButton.vue'

interface Props {
  node?: CourseNodeData
  course?: Course & {
    nodes?: CourseNodeData[]
    next_course?: { slug: string; id: number }
  }
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  entrepreneur?: Record<string, any>
}

const props = defineProps<Props>()
const router = useRouter()
const route = useRoute()

const processedDescription = computed(() => {
  if (!props.node?.content?.description) return ''
  let text = props.node.content.description
  const ent = props.entrepreneur || {}

  // Replace placeholders with entrepreneur data or fallback to '#'
  text = text.replace(
    /{{ ABO_LINK }}/g,
    ent.abo_link ? String(ent.abo_link) : '#'
  )
  text = text.replace(
    /{{ CLIENT_LINK }}/g,
    ent.client_link ? String(ent.client_link) : '#'
  )
  text = text.replace(
    /{{ MY_STORE_LINK }}/g,
    ent.my_digital_store ? String(ent.my_digital_store) : '#'
  )

  return text
})

const loading = ref(false)
const formSubmitting = ref(false)
const formData = reactive<Record<string, string | boolean>>({})

const trackNode = async (nodeId: number) => {
  // Check if already seen on this device to avoid double counting (as requested)
  const seenNodesRaw = localStorage.getItem('seen_nodes')
  const seenNodes: number[] = seenNodesRaw ? JSON.parse(seenNodesRaw) : []

  if (seenNodes.includes(nodeId)) {
    return
  }

  const accessDataRaw = localStorage.getItem('course_access')
  let code = ''
  if (accessDataRaw) {
    try {
      const accessData = JSON.parse(accessDataRaw)
      code = accessData.accessCode
    } catch (e) {
      console.error('Error reading access code', e)
    }
  }

  if (!code) return

  const sessionId = localStorage.getItem('prospect_session_id')
  const email = localStorage.getItem('prospect_email')

  const result = await apiRequest({
    method: 'POST',
    url: '/v1/public/prospect/track-node',
    body: {
      code,
      node_id: nodeId,
      session_id: sessionId,
      email: email || null,
    },
  })

  if (result.success) {
    seenNodes.push(nodeId)
    localStorage.setItem('seen_nodes', JSON.stringify(seenNodes))
  }
}

const syncProgressWithBackend = async (
  data: Record<string, string | boolean>
) => {
  // 1. Recopilar progreso de localStorage
  const progress: Record<string, number> = {}
  const historyRaw = localStorage.getItem('course_history')
  const history: string[] = historyRaw ? JSON.parse(historyRaw) : []

  history.forEach((slug) => {
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
  const prospectPayload: Record<string, unknown> = {
    code,
    progress, // always send as object, even if empty {}
    completed,
    session_id: localStorage.getItem('prospect_session_id'),
  }
  const knownProspectFields = [
    'name',
    'email',
    'phone',
    'city',
    'country',
    'amway_code',
  ]
  knownProspectFields.forEach((key) => {
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

// Initialize formData when node changes
// NOTE: watch must come AFTER trackNode to avoid TDZ (Temporal Dead Zone) errors
// since { immediate: true } fires synchronously during setup.
watch(
  () => props.node,
  (newNode) => {
    if (newNode?.id) {
      trackNode(newNode.id)
    }

    if (newNode?.type === 'form') {
      // Clear previous data
      Object.keys(formData).forEach((key) => delete formData[key])
      // Initialize defaults if any
      newNode.content?.fields?.forEach((field: CourseNodeField) => {
        formData[field.name] = field.type === 'checkbox' ? false : ''
      })
    }
  },
  { immediate: true }
)

const isFormValid = computed(() => {
  if (props.node?.type !== 'form') return true

  const fields: CourseNodeField[] = props.node.content?.fields || []

  for (const field of fields) {
    const val = formData[field.name]

    // Helper to get defaults if not present in field config (for existing nodes)
    const getConstraint = (name: string, type: 'min' | 'max') => {
      if (type === 'min') {
        switch (name) {
          case 'name':
            return 5
          case 'phone':
            return 5
          case 'city':
            return 3
          case 'country':
            return 5
          case 'amway_code':
            return 8
          default:
            return 0
        }
      } else {
        switch (name) {
          case 'name':
            return 100
          case 'email':
            return 100
          case 'phone':
            return 25
          case 'city':
            return 50
          case 'country':
            return 50
          case 'amway_code':
            return 15
          default:
            return 255
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

const handleFormSubmit = async () => {
  if (!isFormValid.value) return

  formSubmitting.value = true

  try {
    // Buscar un campo de email en el formData
    const emailKey = Object.keys(formData).find(
      (key) =>
        key.toLowerCase().includes('email') ||
        props.node?.content?.fields?.find(
          (f: CourseNodeField) => f.name === key
        )?.type === 'email'
    )

    if (emailKey && formData[emailKey]) {
      const response = await syncProgressWithBackend(formData)
      if (response && !response.success) {
        throw new Error(response.error?.message || 'Error saving data')
      }
      // Guardar también en localStorage local para referencia
      localStorage.setItem('prospect_email', String(formData[emailKey]))
    }

    handleContinue() // Continuar al siguiente nodo después de enviar
  } catch (error: unknown) {
    const err = error as ApiError
    console.error('Error in form submission:', err)
    alert(
      err.message ||
        'Hubo un error al guardar tus datos. Por favor, inténtalo de nuevo.'
    )
  } finally {
    formSubmitting.value = false
  }
}

const handleNavigate = async (
  nextNodeId: number | null,
  replace: boolean = false
) => {
  if (!nextNodeId || loading.value) {
    return
  }

  loading.value = true
  try {
    const entrepreneurSlug = route.params.entrepreneurSlug as string
    const courseSlug = route.params.courseSlug as string
    const nextNode = props.course?.nodes?.find(
      (n: CourseNodeData) => n.id === nextNodeId
    )

    if (nextNode) {
      // Preserve current query parameters (like ?cod=...)
      const targetUrl = {
        path: `/cursos/${entrepreneurSlug}/${courseSlug}/${nextNode.slug}`,
        query: route.query,
      }

      // Only navigate if we are truly moving to a different path
      if (route.path !== targetUrl.path) {
        if (replace) {
          await router.replace(targetUrl)
        } else {
          await router.push(targetUrl)
        }
      }
    } else {
      console.error('Next node not found in course data')
    }
  } finally {
    loading.value = false
  }
}

interface DisplayButton {
  id: string
  label: string
  url?: string
  next_node_id?: number | null
  isExternal: boolean
}

const displayButtons = computed<DisplayButton[]>(() => {
  if (!props.node) return []
  const buttons: DisplayButton[] = []

  const externalButtonIndices = new Set<number>()

  // 1. Add all external buttons from node.content.buttons
  if (props.node.content?.buttons?.length) {
    props.node.content.buttons.forEach(
      (btn: string | MenuButton, idx: number) => {
        if (typeof btn === 'object' && btn.url) {
          externalButtonIndices.add(idx)

          let processedUrl = btn.url
          const ent = props.entrepreneur || {}
          processedUrl = processedUrl.replace(
            /{{ ABO_LINK }}/g,
            ent.abo_link ? String(ent.abo_link) : '#'
          )
          processedUrl = processedUrl.replace(
            /{{ CLIENT_LINK }}/g,
            ent.client_link ? String(ent.client_link) : '#'
          )
          processedUrl = processedUrl.replace(
            /{{ MY_STORE_LINK }}/g,
            ent.my_digital_store ? String(ent.my_digital_store) : '#'
          )

          buttons.push({
            id: `ext-${idx}`,
            label: btn.label || `Button ${idx + 1}`,
            url: processedUrl,
            isExternal: true,
          })
        }
      }
    )
  }

  // 2. Add all connected options from node.options
  if (props.node.options?.length) {
    props.node.options.forEach((opt: CourseNodeOption) => {
      let btnIdx = -1

      let label = opt.label
      if (label?.startsWith('menu-btn-')) {
        btnIdx = parseInt(label.replace('menu-btn-', ''))

        // Skip if this option was already rendered as an external button
        if (externalButtonIndices.has(btnIdx)) {
          return
        }

        const btnConf = props.node?.content?.buttons?.[btnIdx]
        if (typeof btnConf === 'string') {
          label = btnConf
        } else if (typeof btnConf === 'object' && btnConf.label) {
          label = btnConf.label
        }
      }

      buttons.push({
        id: `opt-${opt.id || opt.next_node_id}`,
        // We use empty string if we want fallback translation downstream
        label: label || '',
        next_node_id: opt.next_node_id,
        isExternal: false,
      })
    })
  }

  return buttons
})

const handleExternalClick = (url?: string) => {
  if (url && url !== '#') {
    let finalUrl = url
    if (!finalUrl.startsWith('http://') && !finalUrl.startsWith('https://')) {
      finalUrl = 'https://' + finalUrl
    }
    window.open(finalUrl, '_blank')
  }
}

const menuGridClasses = computed(() => {
  const count = displayButtons.value.length
  if (count === 0) return ''

  const base = Math.min(count, 2)
  const md = Math.min(count, 3)
  const lg = Math.min(count, 4)

  return `grid-cols-${base} md:grid-cols-${md} lg:grid-cols-${lg}`
})

const handleContinue = async () => {
  if (loading.value) return

  if (props.node?.is_end) {
    if (props.course?.next_course?.slug) {
      loading.value = true
      try {
        const entrepreneurSlug = route.params.entrepreneurSlug
        const nextCourseSlug = props.course.next_course.slug
        const accessData = localStorage.getItem('course_access')
        if (accessData) {
          const parsed = JSON.parse(accessData)
          parsed.courseSlug = nextCourseSlug
          localStorage.setItem('course_access', JSON.stringify(parsed))
        }

        await router.replace({
          path: `/cursos/${entrepreneurSlug}/${nextCourseSlug}`,
          query: route.query,
        })
      } finally {
        loading.value = false
      }
    } else {
      console.log('Course completed')
    }
    return
  }

  // Find next node ID from options (defaulting to first one if used via generic button)
  const nextNodeId = props.node?.options?.[0]?.next_node_id
  if (nextNodeId) {
    await handleNavigate(nextNodeId)
  } else {
    console.warn('No next node found in options')
  }
}

const getYoutubeId = (url: string) => {
  if (!url) return null
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
  const match = url.match(regExp)
  const videoId = match?.[2]
  return videoId && videoId.length === 11 ? videoId : null
}

const getFieldIcon = (fieldName: string) => {
  switch (fieldName) {
    case 'name':
      return 'user'
    case 'country':
      return 'globe'
    case 'city':
      return 'map-pin'
    case 'email':
      return 'envelope'
    case 'phone':
      return 'phone'
    case 'amway_code':
      return 'id-card'
    default:
      return null
  }
}

const handleVideoLoad = (event: Event) => {
  const iframe = event.target as HTMLIFrameElement
  const speed = props.node?.playback_speed || 1.0

  if (speed !== 1.0) {
    try {
      iframe.contentWindow?.postMessage(
        JSON.stringify({
          event: 'command',
          func: 'setPlaybackRate',
          args: [speed, true],
        }),
        '*'
      )
    } catch (e) {
      console.error('Error setting playback rate:', e)
    }
  }
}
</script>

<!-- eslint-disable vue/no-v-html -->
<template>
  <div
    v-if="!node"
    class="flex-1 flex items-center justify-center p-8 text-slate-400"
  >
    {{ $t('course.noLesson') }}
  </div>

  <div
    v-else
    :class="[
      'flex flex-col bg-white animate-fade-in flex-1',
      node.type === 'menu' && !node.video_url ? '' : 'h-full',
    ]"
  >
    <!-- Header/Title section (hidden for form nodes) -->
    <div
      v-if="node.type !== 'form'"
      class="bg-slate-800 text-white px-8 py-4 flex items-center justify-between"
    >
      <div class="flex items-center gap-3">
        <span
          class="text-xs font-bold bg-white/10 px-2 py-1 rounded text-white/60"
        >
          {{ node.type.toUpperCase() }}
        </span>
        <h1 class="font-bold text-lg">{{ node.title }}</h1>
      </div>
    </div>

    <!-- Video Player (if type is video or menu with video) -->
    <div
      v-if="node.type === 'video' || (node.type === 'menu' && node.video_url)"
      class="flex flex-col flex-1 overflow-y-auto"
    >
      <div class="aspect-video bg-slate-900 shadow-inner">
        <iframe
          v-if="getYoutubeId(node.video_url || '')"
          class="w-full h-full"
          :src="`https://www.youtube.com/embed/${getYoutubeId(node.video_url || '')}?rel=0&modestbranding=1&enablejsapi=1`"
          frameborder="0"
          allow="
            accelerometer;
            autoplay;
            clipboard-write;
            encrypted-media;
            gyroscope;
            picture-in-picture;
          "
          allowfullscreen
          @load="handleVideoLoad"
        ></iframe>
        <div
          v-else
          class="w-full h-full flex items-center justify-center text-slate-500 italic"
        >
          {{ $t('course.no_video_available') }}
        </div>
      </div>

      <div
        v-if="node.content?.description && node.show_description !== false"
        class="px-2 md:px-3 lg:px-12 pt-1 text-sm md:text-base prose max-w-none flex-1"
      >
        <div
          class="text-slate-600 leading-relaxed rich-content"
          v-html="processedDescription"
        />
      </div>
    </div>

    <!-- Form Content (if type is form) -->
    <div
      v-else-if="node.type === 'form'"
      class="flex-1 flex flex-col items-center justify-center overflow-y-auto"
    >
      <div
        v-if="node.content?.description && node.show_description !== false"
        class="w-full max-w-md px-8 pt-8 text-center text-slate-600 leading-relaxed"
        v-html="processedDescription"
      />
      <div class="max-w-md mx-auto w-full">
        <div class="px-8 py-10">
          <h2 class="text-3xl font-bold text-slate-800 mb-8 text-center">
            {{ $t('course.form.title') }}
          </h2>

          <form class="space-y-5" @submit.prevent="handleFormSubmit">
            <template
              v-for="(field, index) in node.content?.fields"
              :key="index"
            >
              <div
                v-if="field.type !== 'checkbox'"
                class="flex items-center gap-4"
              >
                <div
                  class="w-10 h-10 shrink-0 flex items-center justify-center text-slate-400"
                >
                  <!-- Dynamic Icons -->
                  <svg
                    v-if="getFieldIcon(field.name) === 'user'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
                    />
                  </svg>
                  <svg
                    v-else-if="getFieldIcon(field.name) === 'globe'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"
                    />
                  </svg>
                  <svg
                    v-else-if="getFieldIcon(field.name) === 'map-pin'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"
                    />
                  </svg>
                  <svg
                    v-else-if="getFieldIcon(field.name) === 'envelope'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"
                    />
                  </svg>
                  <svg
                    v-else-if="getFieldIcon(field.name) === 'phone'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"
                    />
                  </svg>
                  <svg
                    v-else-if="getFieldIcon(field.name) === 'id-card'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    class="w-8 h-8 opacity-70"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-7-2h5v-2h-5v2zm-4-2h2v-2H8v2zm0-4h2V9H8v2zm4 0h5V9h-5v2z"
                    />
                  </svg>
                  <div v-else class="w-8 h-8 bg-slate-100 rounded-full"></div>
                </div>
                <div class="flex-1">
                  <input
                    v-model="formData[field.name]"
                    :type="field.type || 'text'"
                    :placeholder="
                      $t(`course.form.fields.${field.name}`) || field.label
                    "
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
              <div
                v-for="field in node.content?.fields?.filter(
                  (f: CourseNodeField) => f.type === 'checkbox'
                )"
                :key="field.name"
                class="flex items-center gap-3 mb-6"
              >
                <input
                  :id="field.name"
                  v-model="formData[field.name]"
                  type="checkbox"
                  class="w-5 h-5 rounded border-2 border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer"
                  :required="field.required"
                />
                <label
                  :for="field.name"
                  class="text-slate-700 font-bold text-lg cursor-pointer select-none"
                >
                  {{ $t(`course.form.fields.${field.name}`) || field.label }}
                </label>
              </div>

              <button
                type="submit"
                class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed disabled:transform-none text-white rounded-xl font-bold text-xl shadow-lg shadow-indigo-200 disabled:shadow-none transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2"
                :disabled="formSubmitting || !isFormValid"
              >
                <span
                  v-if="formSubmitting"
                  class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"
                ></span>
                <span v-else-if="!isFormValid" class="text-white/70 text-base"
                  >Completa los campos requeridos</span
                >
                <template v-else>
                  {{ node.content?.submit_label || $t('course.form.access') }}
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- HTML Content (if type is html) -->
    <div
      v-else-if="node.type === 'html'"
      class="flex-1 p-8 md:p-12 overflow-y-auto prose max-w-none"
    >
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="node.content?.body"></div>
    </div>

    <!-- Menu without video: show description if available -->
    <div v-else-if="node.type === 'menu'">
      <div
        v-if="node.content?.description && node.show_description !== false"
        class="p-8 prose max-w-none"
      >
        <div
          class="text-slate-600 leading-relaxed rich-content"
          v-html="processedDescription"
        />
      </div>
    </div>

    <!-- Generic Content (meta, task, etc) -->
    <div
      v-else
      class="flex-1 p-8 flex flex-col items-center justify-center text-center"
    >
      <p class="text-slate-500">{{ node.title }}</p>
    </div>

    <div
      v-if="node.type !== 'form'"
      :class="[
        'p-4 md:p-8 flex flex-col items-center gap-4',
        node.type === 'menu' && !node.video_url
          ? 'flex-1 justify-center'
          : 'border-t border-slate-100',
      ]"
    >
      <div
        v-if="displayButtons.length > 0"
        :class="[
          'w-full max-w-6xl grid gap-3 md:gap-4',
          node.type === 'menu' ? menuGridClasses : 'max-w-sm flex flex-col',
        ]"
      >
        <BaseButton
          v-for="btn in displayButtons"
          :key="btn.id"
          :text="btn.label || $t('course.continue')"
          :action="
            () =>
              btn.isExternal
                ? handleExternalClick(btn.url)
                : handleNavigate(btn.next_node_id || null)
          "
          class="shadow-lg h-full w-full"
          :extra-props="{ loading: loading }"
        />
      </div>
      <div
        v-else-if="
          node.type !== 'form' && (!node.is_end || course?.next_course_id)
        "
        class="w-full max-w-sm"
      >
        <BaseButton
          :text="
            node.is_end
              ? course?.next_course_label || $t('course.next_course_default')
              : $t('course.continue')
          "
          :action="handleContinue"
          :variant="'primary'"
          :extra-props="{
            loading: loading,
            loadingText: $t('course.preparing'),
            class: 'shadow-lg',
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
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
