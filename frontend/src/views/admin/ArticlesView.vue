<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/api/client'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatDate } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const loading = ref(true)
const list = ref([])
const meta = ref(null)

const filters = reactive({
  q: '',
  status: '',
  sort: 'latest',
})

const page = ref(1)

const statuses = ['draft', 'review', 'scheduled', 'published', 'archived']

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.articles.list({
      page: page.value,
      q: filters.q || undefined,
      status: filters.status || undefined,
      sort: filters.sort,
      per_page: 15,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function changePage(next) {
  page.value = next
  load()
}

function applyFilters() {
  page.value = 1
  load()
}

async function remove(article) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.articles.destroy(article.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

function canEdit(article) {
  return auth.isEditorial || article.author?.id === auth.user?.id
}

function canDelete(article) {
  return auth.isEditorial || (article.author?.id === auth.user?.id && article.status !== 'published')
}

onMounted(load)
</script>

<template>
  <div>
    <!-- Filters -->
    <div class="mb-5 flex flex-wrap items-center gap-3">
      <input
        v-model="filters.q"
        type="search"
        :placeholder="t('admin.searchPlaceholder')"
        class="w-56 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @keyup.enter="applyFilters"
      />

      <select
        v-model="filters.status"
        class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @change="applyFilters"
      >
        <option value="">{{ t('admin.filterAll') }}</option>
        <option v-for="status in statuses" :key="status" :value="status">
          {{ t('admin.status' + status.charAt(0).toUpperCase() + status.slice(1)) }}
        </option>
      </select>

      <RouterLink
        :to="{ name: 'admin-article-new' }"
        class="ml-auto rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
      >
        ✎ {{ t('admin.newArticle') }}
      </RouterLink>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <EmptyState v-else-if="list.length === 0" :title="t('article.noArticles')" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableTitle') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableAuthor') }}</th>
              <th class="hidden px-5 py-3 font-bold lg:table-cell">{{ t('admin.tableCategory') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableStatus') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.tableDate') }}</th>
              <th class="hidden px-5 py-3 text-right font-bold sm:table-cell">{{ t('admin.tableViews') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="article in list" :key="article.id" class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="max-w-xs px-5 py-3.5">
                <RouterLink
                  :to="{ name: 'admin-article-edit', params: { id: article.id } }"
                  class="line-clamp-1 font-bold text-zinc-800 hover:text-brand-600 dark:text-zinc-100"
                >
                  {{ article.title }}
                </RouterLink>
                <p class="mt-0.5 text-xs text-zinc-400">{{ article.language.toUpperCase() }} · /{{ article.slug }}</p>
              </td>

              <td class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400">
                {{ article.author?.name ?? '—' }}
              </td>

              <td class="hidden px-5 py-3.5 lg:table-cell">
                <span v-if="article.category" class="font-semibold" :style="{ color: article.category.color }">
                  {{ article.category.name }}
                </span>
                <span v-else class="text-zinc-400">—</span>
              </td>

              <td class="px-5 py-3.5">
                <StatusBadge :status="article.status" />
              </td>

              <td class="hidden px-5 py-3.5 text-zinc-500 dark:text-zinc-400 sm:table-cell">
                {{ formatDate(article.published_at ?? article.created_at) }}
              </td>

              <td class="hidden px-5 py-3.5 text-right font-semibold text-zinc-500 sm:table-cell">
                {{ article.views_count }}
              </td>

              <td class="whitespace-nowrap px-5 py-3.5 text-right">
                <RouterLink
                  v-if="article.status === 'published'"
                  :to="{ name: 'article', params: { slug: article.slug } }"
                  class="mr-3 text-xs font-bold text-zinc-500 hover:text-brand-600"
                  :title="t('admin.viewSite')"
                >
                  ↗
                </RouterLink>

                <RouterLink
                  v-if="canEdit(article)"
                  :to="{ name: 'admin-article-edit', params: { id: article.id } }"
                  class="mr-3 text-xs font-bold text-blue-600 hover:underline"
                >
                  {{ t('common.edit') }}
                </RouterLink>

                <button
                  v-if="canDelete(article)"
                  class="text-xs font-bold text-red-500 hover:underline"
                  @click="remove(article)"
                >
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6">
      <AppPagination v-if="meta" :meta="meta" @page="changePage" />
    </div>
  </div>
</template>
