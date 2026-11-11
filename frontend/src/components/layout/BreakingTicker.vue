<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { articles } from '@/api'
import { formatRelative } from '@/utils/format'

const { t } = useI18n()
const items = ref([])
const offset = ref(0)

onMounted(async () => {
  try {
    const { data } = await articles.breaking()
    items.value = data.data
  } catch {
    // Ticker is decorative; fail silently.
  }
})

function slide() {
  if (items.value.length < 2) {
    return
  }

  offset.value = (offset.value + 1) % items.value.length
}
</script>

<template>
  <div
    v-if="items.length > 0"
    class="flex items-stretch overflow-hidden border-b border-brand-200 bg-brand-600 text-white dark:border-brand-900"
  >
    <div class="flex shrink-0 items-center gap-2 bg-brand-700 px-4 py-2">
      <span class="relative flex h-2.5 w-2.5">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-white"></span>
      </span>
      <span class="text-xs font-black uppercase tracking-widest">{{ t('common.breaking') }}</span>
    </div>

    <div class="relative flex-1 overflow-hidden" @click="slide">
      <TransitionGroup>
        <RouterLink
          :key="items[offset].id"
          :to="{ name: 'article', params: { slug: items[offset].slug } }"
          class="block truncate px-4 py-2 text-sm font-semibold hover:underline"
        >
          {{ items[offset].title }}
          <span class="ml-2 text-xs font-normal text-brand-100">{{ formatRelative(items[offset].published_at) }}</span>
        </RouterLink>
      </TransitionGroup>
    </div>
  </div>
</template>
