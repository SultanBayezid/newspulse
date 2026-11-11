<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import LineChart from '@/components/admin/LineChart.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { compactNumber, formatRelative } from '@/utils/format'

const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const data = ref(null)

onMounted(async () => {
  try {
    const response = await adminApi.dashboard()
    data.value = response.data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <AppSpinner v-if="loading" />

    <template v-else-if="data">
      <p class="mb-6 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
        {{ t('admin.welcome', { name: auth.user?.name }) }}
      </p>

      <!-- Stat cards -->
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ t('admin.statViews30d') }}</p>
          <p class="mt-2 text-3xl font-black dark:text-white">{{ compactNumber(data.totals.views_30d) }}</p>
          <p class="mt-1 text-xs text-zinc-400">{{ t('admin.statViewsToday') }}: {{ compactNumber(data.totals.views_today) }}</p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ t('admin.statArticles') }}</p>
          <p class="mt-2 text-3xl font-black dark:text-white">{{ compactNumber(data.totals.articles) }}</p>
          <p class="mt-1 text-xs text-zinc-400">
            {{ t('admin.statPublished') }}: {{ data.totals.published }} ·
            {{ t('admin.statDrafts') }}: {{ data.totals.drafts }}
          </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ t('admin.statComments') }}</p>
          <p class="mt-2 text-3xl font-black dark:text-white">{{ compactNumber(data.totals.comments) }}</p>
          <p class="mt-1 text-xs font-amber-500">
            {{ t('admin.statPending') }}: {{ data.totals.pending_comments }}
          </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ t('admin.statSubscribers') }}</p>
          <p class="mt-2 text-3xl font-black dark:text-white">{{ compactNumber(data.totals.subscribers) }}</p>
          <p class="mt-1 text-xs text-zinc-400">{{ t('admin.statUsers') }}: {{ data.totals.users }}</p>
        </div>
      </div>

      <!-- Charts -->
      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-zinc-500">{{ t('admin.viewsChart') }}</h3>
          <LineChart :points="data.charts.views" color="#dc2626" />
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-zinc-500">{{ t('admin.articlesChart') }}</h3>
          <LineChart :points="data.charts.articles" color="#2563eb" />
        </div>
      </div>

      <!-- Top articles + activity -->
      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-zinc-500">{{ t('admin.topArticles') }}</h3>

          <ol class="space-y-3">
            <li v-for="(article, index) in data.top_articles" :key="article.id" class="flex items-start gap-3">
              <span class="text-sm font-black text-zinc-300 dark:text-zinc-600">{{ index + 1 }}</span>

              <div class="min-w-0 flex-1">
                <RouterLink
                  :to="{ name: 'article', params: { slug: article.slug } }"
                  class="line-clamp-1 text-sm font-bold hover:text-brand-600 dark:text-zinc-200"
                >
                  {{ article.title }}
                </RouterLink>
                <p class="text-xs text-zinc-400">
                  {{ compactNumber(article.views_count) }} {{ t('common.views') }}
                  <StatusBadge v-if="article.status" :status="article.status" class="ml-1" />
                </p>
              </div>
            </li>
          </ol>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-zinc-500">{{ t('admin.recentActivity') }}</h3>

          <ul class="space-y-3">
            <li v-for="activity in data.recent_activity" :key="activity.id" class="flex items-start gap-3 text-sm">
              <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-500 dark:bg-zinc-800">
                {{ (activity.user?.name ?? '?').charAt(0) }}
              </span>

              <div class="min-w-0 flex-1">
                <p class="text-zinc-700 dark:text-zinc-300">
                  <strong class="font-bold">{{ activity.user?.name ?? 'System' }}</strong>
                  {{ activity.description ?? activity.action }}
                </p>
                <p class="text-xs text-zinc-400">{{ formatRelative(activity.created_at) }}</p>
              </div>
            </li>

            <li v-if="data.recent_activity.length === 0" class="text-sm text-zinc-400">
              {{ t('common.noResults') }}
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>
