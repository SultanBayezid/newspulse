<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const input = ref('')

function add() {
  const value = input.value.trim()

  if (value && !props.modelValue.includes(value)) {
    emit('update:modelValue', [...props.modelValue, value])
  }

  input.value = ''
}

function remove(tag) {
  emit('update:modelValue', props.modelValue.filter((item) => item !== tag))
}
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-2 rounded-xl border border-zinc-300 p-2 dark:border-zinc-700">
      <span
        v-for="tag in modelValue"
        :key="tag"
        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-100 px-2.5 py-1 text-sm font-semibold text-brand-800 dark:bg-brand-950 dark:text-brand-300"
      >
        {{ tag }}
        <button type="button" class="text-brand-400 hover:text-brand-700" @click="remove(tag)">✕</button>
      </span>

      <input
        v-model="input"
        type="text"
        :placeholder="modelValue.length === 0 ? t('admin.tagsHint') : '…'"
        class="min-w-32 flex-1 bg-transparent px-1.5 py-1 text-sm outline-none dark:text-white"
        @keydown.enter.prevent="add"
        @keydown.tab.prevent="add"
        @blur="add"
      />
    </div>

    <p class="mt-1.5 text-xs text-zinc-400">{{ t('admin.tagsHint') }}</p>
  </div>
</template>
