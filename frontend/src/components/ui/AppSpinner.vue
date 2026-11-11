<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  size: { type: String, default: 'h-8 w-8' },
  label: { type: String, default: null },
})

const { t } = useI18n()
</script>

<template>
  <div class="flex items-center justify-center gap-3 py-12 text-zinc-400" role="status">
    <svg :class="size" class="animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <span class="text-sm font-medium">{{ label ?? t('common.loading') }}</span>
  </div>
</template>
