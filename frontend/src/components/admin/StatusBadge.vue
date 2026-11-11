<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  status: { type: String, required: true },
})

const { t } = useI18n()

const config = {
  draft: { labelKey: 'admin.statusDraft', classes: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' },
  review: { labelKey: 'admin.statusReview', classes: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' },
  scheduled: { labelKey: 'admin.statusScheduled', classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' },
  published: { labelKey: 'admin.statusPublished', classes: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' },
  archived: { labelKey: 'admin.statusArchived', classes: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
  pending: { labelKey: 'admin.commentsPending', classes: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' },
  approved: { labelKey: 'admin.commentsApproved', classes: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' },
  rejected: { labelKey: 'admin.commentsRejected', classes: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
  subscribed: { labelKey: 'admin.subscriberSubscribed', classes: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' },
}

const entry = computed(() => config[props.status] ?? { label: props.status, classes: 'bg-zinc-100 text-zinc-600' })
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
    :class="entry.classes"
  >
    {{ entry.labelKey ? t(entry.labelKey) : entry.label }}
  </span>
</template>
