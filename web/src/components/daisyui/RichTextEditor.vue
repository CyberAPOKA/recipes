<script setup>
import { ref, watch, onBeforeUnmount, computed } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  minHeight: {
    type: String,
    default: '200px'
  },
  error: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const convertTextToHTML = (text) => {
  if (!text) return ''
  // If it's already HTML (contains tags), return as is
  if (/<[a-z][\s\S]*>/i.test(text)) {
    return text
  }
  // Convert plain text to HTML, preserving line breaks
  return text
    .split('\n')
    .map(line => line.trim() ? `<p>${line}</p>` : '<p><br></p>')
    .join('')
}

const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    Placeholder.configure({
      placeholder: props.placeholder
    })
  ],
  content: convertTextToHTML(props.modelValue),
  editable: !props.disabled,
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

const hasContent = computed(() => {
  return editor.value && !editor.value.isEmpty
})

watch(() => props.modelValue, (value) => {
  if (!editor.value) return
  const currentContent = editor.value.getHTML()
  const newContent = convertTextToHTML(value || '')

  // Only update if content actually changed
  if (currentContent !== newContent) {
    editor.value.commands.setContent(newContent, false)
  }
})

watch(() => props.disabled, (disabled) => {
  if (editor.value) {
    editor.value.setEditable(!disabled)
  }
})

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})
</script>

<template>
  <div class="form-control w-full">
    <label v-if="label" class="block mb-1">
      <span class="label-text">
        {{ label }}
        <span v-if="required" class="text-error">*</span>
      </span>
    </label>

    <div class="border rounded-lg overflow-hidden" :class="error ? 'border-error' : 'border-base-300'"
      v-if="editor">
      <!-- Toolbar -->
      <div class="flex flex-wrap gap-1 p-2 bg-base-200 border-b border-base-300">
        <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('bold') ? 'btn-active' : ''
        ]" :disabled="disabled">
          <strong>B</strong>
        </button>

        <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('italic') ? 'btn-active' : ''
        ]" :disabled="disabled">
          <em>I</em>
        </button>

        <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('underline') ? 'btn-active' : ''
        ]" :disabled="disabled">
          <u>U</u>
        </button>

        <div class="divider divider-horizontal mx-0"></div>

        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('heading', { level: 1 }) ? 'btn-active' : ''
        ]" :disabled="disabled">
          H1
        </button>

        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('heading', { level: 2 }) ? 'btn-active' : ''
        ]" :disabled="disabled">
          H2
        </button>

        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('heading', { level: 3 }) ? 'btn-active' : ''
        ]" :disabled="disabled">
          H3
        </button>

        <div class="divider divider-horizontal mx-0"></div>

        <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('bulletList') ? 'btn-active' : ''
        ]" :disabled="disabled">
          •
        </button>

        <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('orderedList') ? 'btn-active' : ''
        ]" :disabled="disabled">
          1.
        </button>

        <div class="divider divider-horizontal mx-0"></div>

        <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="[
          'btn btn-sm btn-ghost',
          editor.isActive('blockquote') ? 'btn-active' : ''
        ]" :disabled="disabled">
          "
        </button>

        <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="btn btn-sm btn-ghost"
          :disabled="disabled">
          ─
        </button>

        <div class="divider divider-horizontal mx-0"></div>

        <button type="button" @click="editor.chain().focus().undo().run()" class="btn btn-sm btn-ghost"
          :disabled="disabled || !editor.can().undo()">
          ↶
        </button>

        <button type="button" @click="editor.chain().focus().redo().run()" class="btn btn-sm btn-ghost"
          :disabled="disabled || !editor.can().redo()">
          ↷
        </button>
      </div>

      <!-- Editor Content -->
      <div class="prose prose-sm max-w-none p-4 min-h-[200px] focus-within:outline-none">
        <EditorContent :editor="editor" />
      </div>
    </div>

    <div v-else class="border border-base-300 rounded-lg p-4 min-h-[200px] flex items-center justify-center">
      <span class="loading loading-spinner loading-md"></span>
    </div>

    <div v-if="error" class="mt-1 text-xs text-error">
      {{ error }}
    </div>
    <div v-else-if="hint" class="mt-1 text-xs opacity-70">
      {{ hint }}
    </div>
  </div>
</template>

<style>
.ProseMirror {
  outline: none;
  min-height: 200px;
}

.ProseMirror p.is-editor-empty:first-child::before {
  color: #9ca3af;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.ProseMirror ul,
.ProseMirror ol {
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}

.ProseMirror ul {
  list-style-type: disc;
}

.ProseMirror ol {
  list-style-type: decimal;
}

.ProseMirror h1 {
  font-size: 2em;
  font-weight: bold;
  margin: 0.5em 0;
}

.ProseMirror h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin: 0.5em 0;
}

.ProseMirror h3 {
  font-size: 1.25em;
  font-weight: bold;
  margin: 0.5em 0;
}

.ProseMirror blockquote {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
}

.ProseMirror hr {
  border: none;
  border-top: 2px solid #e5e7eb;
  margin: 1rem 0;
}
</style>