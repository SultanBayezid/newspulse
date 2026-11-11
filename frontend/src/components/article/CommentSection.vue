<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { comments, reactions } from '@/api'
import { apiError } from '@/api/client'
import { formatRelative, compactNumber } from '@/utils/format'
import CommentItem from './CommentItem.vue'

const props = defineProps({
  articleId: { type: Number, required: true },
})

const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const items = ref([])
const loading = ref(true)
const body = ref('')
const replyingTo = ref(null)
const busy = ref(false)

async function load() {
  loading.value = true

  try {
    const { data } = await comments.list(props.articleId)
    items.value = data.data
  } finally {
    loading.value = false
  }
}

load()

async function submit() {
  if (body.value.trim().length < 2 || busy.value) {
    return
  }

  busy.value = true

  try {
    const { data } = await comments.store({
      article_id: props.articleId,
      parent_id: replyingTo?.value ? replyingTo.value.id : null,
      body: body.value.trim(),
    })

    body.value = ''
    replyingTo.value = null

    ui.toast(
      data.message ?? (data.data?.status === 'approved' ? t('common.save') : t('comments.pendingModeration')),
    )

    if (data.data?.status === 'approved') {
      if (data.data.parent_id) {
        const parent = items.value.find((comment) => comment.id === data.data.parent_id)

        if (parent) {
          parent.replies = [...(parent.replies ?? []), data.data]
        }
      } else {
        items.value.push({ ...data.data, replies: [] })
      }
    }
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}

function startReply(comment) {
  replyingTo.value = comment
}

function cancelReply() {
  replyingTo.value = null
}

async function toggleLike(comment) {
  try {
    const { data } = await reactions.toggle('comment', comment.id)
    comment.likes_count = data.likes_count
    comment.liked = data.liked
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}
</script>

<template>
  <section id="comments" class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-800">
    <h2 class="text-xl font-black dark:text-white">
      {{ t('comments.title') }}
      <span class="ml-1 text-base font-semibold text-zinc-400">({{ items.length }})</span>
    </h2>

    <!-- Form -->
    <div class="mt-6">
      <div v-if="auth.isAuthenticated" class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
        <p v-if="replyingTo" class="mb-3 flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-800 dark:bg-brand-950/40 dark:text-brand-300">
          {{ t('comments.replyTo', { name: replyingTo.user?.name }) }}
          <button class="font-bold hover:underline" @click="cancelReply">✕</button>
        </p>

        <textarea
          v-model="body"
          rows="3"
          :placeholder="t('comments.placeholder')"
          class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />

        <div class="mt-3 flex items-center justify-between">
          <p class="text-xs text-zinc-400">{{ body.length }} / 2000</p>
          <button
            class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
            :disabled="body.trim().length < 2 || busy"
            @click="submit"
          >
            {{ busy ? t('common.saving') : t('comments.submit') }}
          </button>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ t('comments.signInToComment') }}</p>
        <RouterLink
          :to="{ name: 'login' }"
          class="mt-3 inline-block rounded-xl bg-brand-600 px-5 py-2 text-sm font-bold text-white hover:bg-brand-700"
        >
          {{ t('nav.login') }}
        </RouterLink>
      </div>
    </div>

    <!-- Thread -->
    <div v-if="loading" class="py-8 text-center text-sm text-zinc-400">{{ t('common.loading') }}</div>

    <div v-else-if="items.length === 0" class="py-10 text-center text-sm text-zinc-400">
      {{ t('comments.empty') }}
    </div>

    <div v-else class="mt-8 space-y-2">
      <CommentItem
        v-for="comment in items"
        :key="comment.id"
        :comment="comment"
        @reply="startReply"
        @like="toggleLike"
      />
    </div>
  </section>
</template>
