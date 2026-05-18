<template>
  <div class="rich-editor rounded-md border border-input bg-background focus-within:ring-2 focus-within:ring-ring">
    <div class="flex items-center gap-0.5 border-b border-input px-2 py-1.5 flex-wrap">
      <button
        v-for="btn in toolbar"
        :key="btn.label"
        type="button"
        :title="btn.label"
        :class="[
          'px-2 py-1 rounded text-sm transition-colors',
          btn.active() ? 'bg-muted font-semibold' : 'hover:bg-muted text-muted-foreground hover:text-foreground'
        ]"
        @mousedown.prevent="btn.action"
      >{{ btn.icon }}</button>
    </div>
    <EditorContent :editor="editor" class="prose-editor px-3 py-2 text-sm min-h-[120px] outline-none" />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({ modelValue: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [StarterKit],
  onUpdate({ editor }) {
    emit('update:modelValue', editor.getHTML())
  },
})

watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    editor.value.commands.setContent(val, false)
  }
})

onBeforeUnmount(() => editor.value?.destroy())

const toolbar = [
  { label: 'Жирный', icon: 'B', active: () => editor.value?.isActive('bold'), action: () => editor.value?.chain().focus().toggleBold().run() },
  { label: 'Курсив', icon: 'I', active: () => editor.value?.isActive('italic'), action: () => editor.value?.chain().focus().toggleItalic().run() },
  { label: 'Заголовок 2', icon: 'H2', active: () => editor.value?.isActive('heading', { level: 2 }), action: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run() },
  { label: 'Заголовок 3', icon: 'H3', active: () => editor.value?.isActive('heading', { level: 3 }), action: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run() },
  { label: 'Маркированный список', icon: '• —', active: () => editor.value?.isActive('bulletList'), action: () => editor.value?.chain().focus().toggleBulletList().run() },
  { label: 'Нумерованный список', icon: '1.', active: () => editor.value?.isActive('orderedList'), action: () => editor.value?.chain().focus().toggleOrderedList().run() },
  { label: 'Цитата', icon: '❝', active: () => editor.value?.isActive('blockquote'), action: () => editor.value?.chain().focus().toggleBlockquote().run() },
  { label: 'Разделитель', icon: '—', active: () => false, action: () => editor.value?.chain().focus().setHorizontalRule().run() },
]
</script>

<style>
.prose-editor:focus-within { outline: none; }
.prose-editor p { margin: 0 0 0.5em; }
.prose-editor p:last-child { margin-bottom: 0; }
.prose-editor h2 { font-size: 1.1em; font-weight: 700; margin: 0.75em 0 0.25em; }
.prose-editor h3 { font-size: 1em; font-weight: 600; margin: 0.5em 0 0.25em; }
.prose-editor ul { list-style: disc; padding-left: 1.25em; margin: 0.25em 0; }
.prose-editor ol { list-style: decimal; padding-left: 1.25em; margin: 0.25em 0; }
.prose-editor blockquote { border-left: 3px solid var(--border); padding-left: 0.75em; color: var(--muted-foreground); margin: 0.25em 0; }
.prose-editor hr { border: none; border-top: 1px solid var(--border); margin: 0.5em 0; }
.prose-editor strong { font-weight: 700; }
.prose-editor em { font-style: italic; }
.prose-editor .ProseMirror { outline: none; }
</style>
