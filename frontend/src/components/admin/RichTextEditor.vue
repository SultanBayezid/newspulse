<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const editor = ref(null)
const sourceMode = ref(false)

onMounted(() => {
  if (editor.value && props.modelValue) {
    editor.value.innerHTML = props.modelValue
  }
})

watch(
  () => props.modelValue,
  (value) => {
    if (editor.value && !sourceMode.value && editor.value.innerHTML !== value) {
      editor.value.innerHTML = value ?? ''
    }
  },
)

function sync() {
  if (sourceMode.value) {
    return
  }

  emit('update:modelValue', editor.value?.innerHTML ?? '')
}

function exec(command, value = null) {
  editor.value?.focus()
  document.execCommand(command, false, value)
  sync()
}

function setBlock(block) {
  document.execCommand('formatBlock', false, block)
  sync()
}

function insertLink() {
  const url = prompt('Link URL (https://…)')

  if (url) {
    exec('createLink', url)
  }
}

function insertImage() {
  const url = prompt('Image URL')

  if (url) {
    exec('insertImage', url)
  }
}

const buttons = [
  { label: 'B', title: 'Bold', action: () => exec('bold'), class: 'font-black' },
  { label: 'I', title: 'Italic', action: () => exec('italic'), class: 'italic font-serif' },
  { label: 'U', title: 'Underline', action: () => exec('underline'), class: 'underline' },
  { label: 'S', title: 'Strikethrough', action: () => exec('strikeThrough'), class: 'line-through' },
]

const blocks = [
  { label: t('common.preview') + ' H2', value: 'h2' },
  { label: 'H3', value: 'h3' },
  { label: '¶ P', value: 'p' },
]
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-zinc-300 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 dark:border-zinc-700">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-800/60">
      <button
        v-for="button in buttons"
        :key="button.title"
        type="button"
        :title="button.title"
        class="h-8 w-8 rounded-lg text-sm text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        :class="button.class"
        @mousedown.prevent
        @click="button.action"
      >
        {{ button.label }}
      </button>

      <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" />

      <button
        type="button"
        title="Heading 2"
        class="h-8 rounded-lg px-2 text-xs font-bold text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="setBlock('h2')"
      >
        H2
      </button>

      <button
        type="button"
        title="Heading 3"
        class="h-8 rounded-lg px-2 text-xs font-bold text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="setBlock('h3')"
      >
        H3
      </button>

      <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" />

      <button
        type="button"
        title="Bulleted list"
        class="h-8 w-8 rounded-lg text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="exec('insertUnorderedList')"
      >
        •≡
      </button>

      <button
        type="button"
        title="Numbered list"
        class="h-8 w-8 rounded-lg text-xs font-bold text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="exec('insertOrderedList')"
      >
        1≡
      </button>

      <button
        type="button"
        title="Quote"
        class="h-8 w-8 rounded-lg text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="setBlock('blockquote')"
      >
        ❝
      </button>

      <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" />

      <button
        type="button"
        title="Link"
        class="h-8 w-8 rounded-lg text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="insertLink"
      >
        🔗
      </button>

      <button
        type="button"
        title="Image URL"
        class="h-8 w-8 rounded-lg text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="insertImage"
      >
        🖼
      </button>

      <button
        type="button"
        title="Code"
        class="h-8 rounded-lg px-2 font-mono text-xs text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="exec('formatBlock', 'pre')"
      >
        &lt;/&gt;
      </button>

      <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" />

      <button
        type="button"
        title="Horizontal rule"
        class="h-8 rounded-lg px-2 text-xs text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="exec('insertHorizontalRule')"
      >
        ―
      </button>

      <button
        type="button"
        title="Clear formatting"
        class="h-8 rounded-lg px-2 text-xs text-zinc-700 transition hover:bg-white hover:shadow-sm dark:text-zinc-200 dark:hover:bg-zinc-700"
        @mousedown.prevent
        @click="exec('removeFormat')"
      >
        ⌫F
      </button>

      <span class="flex-1" />

      <button
        type="button"
        class="h-8 rounded-lg px-3 text-xs font-bold transition"
        :class="sourceMode
          ? 'bg-brand-600 text-white'
          : 'text-zinc-500 hover:bg-white hover:shadow-sm dark:hover:bg-zinc-700'"
        @mousedown.prevent
        @click="sourceMode = !sourceMode"
      >
        HTML
      </button>
    </div>

    <!-- Editable area -->
    <div
      v-show="!sourceMode"
      ref="editor"
      contenteditable="true"
      class="prose-article min-h-[300px] max-w-none bg-white px-5 py-4 outline-none dark:bg-zinc-900"
      :data-placeholder="placeholder"
      @input="sync"
      @blur="sync"
    />

    <!-- Source mode -->
    <textarea
      v-show="sourceMode"
      :value="modelValue"
      class="min-h-[300px] w-full bg-zinc-950 px-5 py-4 font-mono text-sm text-green-300 outline-none"
      @input="emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>
