<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { formatRelative } from '@/utils/format'

defineProps({
  comment: { type: Object, required: true },
})

const emit = defineEmits(['reply', 'like'])

const { t } = useI18n()
const auth = useAuthStore()
</script>

<template>
  <div>
    <div class="flex gap-4 rounded-2xl p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-900/60">
      <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-200">
        {{ (comment.user?.name ?? '?').charAt(0).toUpperCase() }}
      </span>

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-x-3">
          <span class="text-sm font-bold dark:text-white">{{ comment.user?.name }}</span>
          <span class="text-xs text-zinc-400">{{ formatRelative(comment.created_at) }}</span>
        </div>

        <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">
          {{ comment.body }}
        </p>

        <div class="mt-2 flex items-center gap-4 text-xs font-semibold text-zinc-500">
          <button
            v-if="auth.isAuthenticated"
            class="flex items-center gap-1 transition hover:text-brand-600"
            :class="{ 'text-brand-600': comment.liked }"
            @click="emit('like', comment)"
          >
            <svg class="h-4 w-4" :fill="comment.liked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
            </svg>
            {{ comment.likes_count }}
          </button>

          <button
            v-if="auth.isAuthenticated && !comment.parent_id"
            class="transition hover:text-brand-600"
            @click="emit('reply', comment)"
          >
            {{ t('common.reply') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Replies -->
    <div v-if="comment.replies?.length" class="ml-14 space-y-1 border-l-2 border-zinc-100 pl-4 dark:border-zinc-800">
      <CommentItem
        v-for="reply in comment.replies"
        :key="reply.id"
        :comment="reply"
        @reply="emit('reply', comment)"
        @like="emit('like', $event)"
      />
    </div>
  </div>
</template>
