<script setup>
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'

const props = defineProps({
  meta: { type: Object, required: true },
})

const emit = defineEmits(['page'])

const { t } = useI18n()

const pages = computed(() => {
  const { current_page: current, last_page: last } = props.meta
  const window = 2
  const result = []

  for (let page = 1; page <= last; page++) {
    if (page === 1 || page === last || Math.abs(page - current) <= window) {
      result.push(page)
    } else if (result[result.length - 1] !== '…') {
      result.push('…')
    }
  }

  return result
})

function go(page) {
  if (page === '…' || page === props.meta.current_page) {
    return
  }

  emit('page', page)
}
</script>

<template>
  <nav v-if="meta.last_page > 1" class="flex items-center justify-center gap-1" :aria-label="t('common.page')">
    <button
      class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
      :disabled="meta.current_page === 1"
      @click="go(meta.current_page - 1)"
    >
      ‹ {{ t('common.prev') }}
    </button>

    <template v-for="(page, index) in pages" :key="`${page}-${index}`">
      <button
        class="hidden h-10 min-w-10 rounded-lg px-3 text-sm font-semibold transition sm:block"
        :class="page === meta.current_page
          ? 'bg-brand-600 text-white'
          : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
        :disabled="page === '…'"
        @click="go(page)"
      >
        {{ page }}
      </button>
    </template>

    <button
      class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
      :disabled="meta.current_page === meta.last_page"
      @click="go(meta.current_page + 1)"
    >
      {{ t('common.next') }} ›
    </button>
  </nav>
</template>
