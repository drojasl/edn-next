<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import { TextStyle } from '@tiptap/extension-text-style'
import Underline from '@tiptap/extension-underline'
import { Extension, type CommandProps } from '@tiptap/core'
import { watch, ref, nextTick } from 'vue'

// ─── Custom FontSize extension ────────────────────────────────────────────────
const FontSize = Extension.create({
  name: 'fontSize',
  addOptions() {
    return { types: ['textStyle'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types as string[],
        attributes: {
          fontSize: {
            default: null,
            parseHTML: (el: HTMLElement) => el.style.fontSize || null,
            renderHTML: (attrs: Record<string, unknown>) => {
              if (!attrs.fontSize) return {}
              return { style: `font-size: ${attrs.fontSize}` }
            },
          },
        },
      },
    ]
  },
  addCommands() {
    return {
      setFontSize:
        (size: string) =>
        ({ chain }: CommandProps) => {
          return chain().setMark('textStyle', { fontSize: size }).run()
        },
      unsetFontSize:
        () =>
        ({ chain }: CommandProps) => {
          return chain().setMark('textStyle', { fontSize: null }).run()
        },
    }
  },
})
// ─────────────────────────────────────────────────────────────────────────────

const FONT_SIZES = [
  { label: 'Pequeño', value: '12px' },
  { label: 'Normal', value: '16px' },
  { label: 'Grande', value: '20px' },
  { label: 'Muy grande', value: '28px' },
]

interface Props {
  modelValue?: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Escribe aquí...',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

// ─── Link popover state ───────────────────────────────────────────────────────
const showLinkPopover = ref(false)
const linkInputValue = ref('')
const linkInputRef = ref<HTMLInputElement | null>(null)
const linkBtnRef = ref<HTMLButtonElement | null>(null)
const popoverPos = ref({ top: 0, left: 0 })

const openLinkPopover = () => {
  const current = editor.value?.getAttributes('link').href as string | undefined
  linkInputValue.value = current ?? ''
  // Calculate position from button
  if (linkBtnRef.value) {
    const rect = linkBtnRef.value.getBoundingClientRect()
    popoverPos.value = {
      top: rect.bottom + window.scrollY + 6,
      left: rect.left + window.scrollX,
    }
  }
  showLinkPopover.value = true
  nextTick(() => linkInputRef.value?.focus())
}

const closeLinkPopover = () => {
  showLinkPopover.value = false
  linkInputValue.value = ''
}

const confirmLink = () => {
  const url = linkInputValue.value.trim()
  if (!url) {
    editor.value?.chain().focus().unsetLink().run()
  } else {
    editor.value
      ?.chain()
      .focus()
      .setLink({ href: url, target: '_blank', rel: 'noopener noreferrer' })
      .run()
  }
  closeLinkPopover()
}

const onLinkKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter') confirmLink()
  if (e.key === 'Escape') closeLinkPopover()
}

const insertLinkVariable = (variableName: string) => {
  linkInputValue.value = `{{ ${variableName} }}`
}
// ─────────────────────────────────────────────────────────────────────────────

const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    StarterKit,
    Link.configure({
      openOnClick: false,
      defaultProtocol: 'https',
      HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer' },
    }),
    TextStyle,
    Underline,
    FontSize,
  ],
  editorProps: {
    attributes: {
      class:
        'prose prose-sm max-w-none focus:outline-none min-h-[80px] px-4 py-3',
    },
  },
  onUpdate() {
    emit('update:modelValue', editor.value?.getHTML() ?? '')
  },
})

watch(
  () => props.modelValue,
  (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
      editor.value.commands.setContent(val ?? '')
    }
  }
)

const setFontSize = (e: Event) => {
  const size = (e.target as HTMLSelectElement).value
  const editorChain = editor.value?.chain().focus()
  if (!editorChain) return
  if (!size) {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(editorChain as any).unsetFontSize().run()
  } else {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(editorChain as any).setFontSize(size).run()
  }
}
</script>

<template>
  <div
    class="rich-editor border border-slate-200 rounded-xl overflow-hidden bg-slate-50"
  >
    <!-- Toolbar -->
    <div
      class="flex items-center gap-1 px-3 py-2 bg-white border-b border-slate-200 flex-wrap"
    >
      <!-- Bold -->
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor?.isActive('bold') }"
        title="Negrita (Ctrl+B)"
        @click="editor?.chain().focus().toggleBold().run()"
      >
        <strong>B</strong>
      </button>

      <!-- Italic -->
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor?.isActive('italic') }"
        title="Cursiva (Ctrl+I)"
        @click="editor?.chain().focus().toggleItalic().run()"
      >
        <em>I</em>
      </button>

      <!-- Underline -->
      <button
        type="button"
        class="toolbar-btn underline-btn"
        :class="{ 'is-active': editor?.isActive('underline') }"
        title="Subrayado (Ctrl+U)"
        @click="editor?.chain().focus().toggleUnderline().run()"
      >
        U
      </button>

      <!-- Divider -->
      <span class="w-px h-5 bg-slate-200 mx-1" />

      <!-- Link button -->
      <button
        ref="linkBtnRef"
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor?.isActive('link') }"
        title="Insertar enlace"
        @click="openLinkPopover"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-3.5 w-3.5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
          />
        </svg>
      </button>

      <!-- Divider -->
      <span class="w-px h-5 bg-slate-200 mx-1" />

      <!-- Font Size -->
      <select
        class="text-xs px-2 py-1 border border-slate-200 rounded-lg bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-400 cursor-pointer"
        title="Tamaño de texto"
        @change="setFontSize"
      >
        <option value="">Tamaño</option>
        <option v-for="s in FONT_SIZES" :key="s.value" :value="s.value">
          {{ s.label }}
        </option>
      </select>
    </div>

    <!-- Link Popover (Teleported to body to escape overflow:hidden) -->
    <Teleport to="body">
      <!-- Click outside overlay -->
      <div
        v-if="showLinkPopover"
        class="fixed inset-0 z-[10001]"
        @click="closeLinkPopover"
      />
      <Transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="showLinkPopover"
          class="fixed z-[10002] bg-white rounded-xl shadow-2xl border border-slate-200 p-4 w-72"
          :style="{ top: popoverPos.top + 'px', left: popoverPos.left + 'px' }"
        >
          <p
            class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3"
          >
            Insertar enlace
          </p>
          <div class="flex gap-2">
            <input
              ref="linkInputRef"
              v-model="linkInputValue"
              type="text"
              placeholder="https://ejemplo.com"
              class="flex-1 text-sm px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-slate-50"
              @keydown="onLinkKeydown"
            />
          </div>

          <!-- Variables Dinamicas -->
          <div class="mt-2 text-[10px] text-slate-500 font-medium">
            Variables rápidas:
          </div>
          <div class="flex gap-1 mt-1 flex-wrap">
            <button
              type="button"
              class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[10px] tracking-wide transition-colors"
              @click="insertLinkVariable('ABO_LINK')"
            >
              <span v-pre>{{ ABO_LINK }}</span>
            </button>
            <button
              type="button"
              class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[10px] tracking-wide transition-colors"
              @click="insertLinkVariable('CLIENT_LINK')"
            >
              <span v-pre>{{ CLIENT_LINK }}</span>
            </button>
            <button
              type="button"
              class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[10px] tracking-wide transition-colors"
              @click="insertLinkVariable('MY_STORE_LINK')"
            >
              <span v-pre>{{ MY_STORE_LINK }}</span>
            </button>
          </div>
          <div class="flex gap-2 mt-3">
            <button
              type="button"
              class="flex-1 text-xs font-bold py-1.5 px-3 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
              @click="confirmLink"
            >
              Aplicar
            </button>
            <button
              v-if="editor?.isActive('link')"
              type="button"
              class="text-xs font-bold py-1.5 px-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
              @click="
                () => {
                  editor?.chain().focus().unsetLink().run()
                  closeLinkPopover()
                }
              "
            >
              Quitar
            </button>
            <button
              type="button"
              class="text-xs font-bold py-1.5 px-3 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors"
              @click="closeLinkPopover"
            >
              Cancelar
            </button>
          </div>
          <p class="text-[10px] text-slate-400 mt-2">
            Todos los enlaces se abren en una nueva pestaña ↗
          </p>
        </div>
      </Transition>
    </Teleport>

    <!-- Editor area -->
    <EditorContent :editor="editor" />
  </div>
</template>

<style scoped>
.toolbar-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  font-size: 13px;
  color: #475569;
  background: transparent;
  transition:
    background 0.15s,
    color 0.15s;
  cursor: pointer;
  border: none;
}

.toolbar-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.toolbar-btn.is-active {
  background: #e0e7ff;
  color: #4f46e5;
}

.underline-btn {
  text-decoration: underline;
}

/* Prose styles for editor content */
:deep(.ProseMirror) {
  outline: none;
  min-height: 80px;
  padding: 12px 16px;
  font-size: 14px;
  color: #1e293b;
  line-height: 1.6;
}

:deep(.ProseMirror p) {
  margin: 0 0 0.5em;
}

:deep(.ProseMirror a) {
  color: #4f46e5;
  text-decoration: underline;
  cursor: pointer;
}

:deep(.ProseMirror strong) {
  font-weight: 700;
}

:deep(.ProseMirror em) {
  font-style: italic;
}

:deep(.ProseMirror u) {
  text-decoration: underline;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: #94a3b8;
  pointer-events: none;
  float: left;
  height: 0;
}
</style>
