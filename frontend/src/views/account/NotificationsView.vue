<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useNotificationStore } from '@/stores/notifications'
import { formatRelative } from '@/utils/format'

const { t } = useI18n()
const store = useNotificationStore()

onMounted(() => store.load(true))

function notificationText(item) {
  const data = item.data ?? {}

  switch (item.type) {
    case 'article_status_changed':
      if (data.action === 'approved') {
        return `Your article "${data.article_title}" has been approved and published.`
      }

      if (data.action === 'rejected') {
        return `Your article "${data.article_title}" was sent back${data.note ? `: ${data.note}` : '.'}`
      }

      if (data.action === 'scheduled') {
        return `Your article "${data.article_title}" has been scheduled.`
      }

      return `Your article "${data.article_title}" was submitted for review.`
    case 'comment_submitted':
      return `A new comment is ${data.status === 'pending' ? 'awaiting moderation' : 'live'} on "${data.article_title}".`
    default:
      return 'New notification'
  }
}
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-black tracking-tight dark:text-white">{{ t('account.notificationsTitle') }}</h1>

        <button
          v-if="store.unreadCount > 0"
          class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-bold text-zinc-600 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
          @click="store.markAllRead()"
        >
          {{ t('account.markAllRead') }}
        </button>
      </div>

      <div class="mt-8">
        <EmptyState v-if="store.items.length === 0" :title="t('account.noNotifications')" />

        <div v-else class="space-y-3">
          <RouterLink
            v-for="item in store.items"
            :key="item.id"
            :to="item.data?.article_slug ? { name: 'article', params: { slug: item.data.article_slug } } : { name: 'home' }"
            class="block rounded-2xl border p-5 transition hover:border-brand-400"
            :class="item.read_at
              ? 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900'
              : 'border-brand-300 bg-brand-50/50 dark:border-brand-700 dark:bg-brand-950/20'"
            @click="store.markRead(item.id)"
          >
            <p class="text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ notificationText(item) }}</p>
            <p class="mt-2 text-xs text-zinc-400">{{ formatRelative(item.created_at) }}</p>
          </RouterLink>
        </div>
      </div>
    </main>

    <PublicFooter />
  </div>
</template>
