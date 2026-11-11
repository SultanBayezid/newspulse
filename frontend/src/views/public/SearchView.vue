<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { articles, categories } from '@/api'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const loading = ref(false)
const searched = ref(false)
const list = ref([])
const meta = ref(null)
const categoryList = ref([])

const filters = reactive({
  q: '',
  category: '',
  language: '',
  sort: 'latest',
})

const page = ref(1)

onMounted(async () => {
  filters.q = route.query.q ?? ''

  try {
    const { data } = await categories.list()
    categoryList.value = data.data
  } catch {
    // Filters degrade gracefully.
  }

  if (filters.q) {
    search()
  }
})

watch(() => route.query.q, (q) => {
  filters.q = q ?? ''

  if (q) {
    search()
  }
})

async function search() {
  if (!filters.q.trim()) {
    return
  }

  loading.value = true
  searched.value = true

  try {
    const { data } = await articles.list({
      q: filters.q.trim(),
      category: filters.category || undefined,
      language: filters.language || undefined,
      sort: filters.sort,
      page: page.value,
    })

    list.value = data.data
    meta.value = data.meta

    if (route.query.q !== filters.q.trim() || page.value > 1) {
      router.replace({ query: { q: filters.q.trim() } })
    }
  } finally {
    loading.value = false
  }
}

function submit() {
  page.value = 1
  search()
}

function changePage(next) {
  page.value = next
  search()
}
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <h1 class="text-3xl font-black tracking-tight dark:text-white">{{ t('search.title') }}</h1>

      <form class="mt-6 flex" @submit.prevent="submit">
        <input
          v-model="filters.q"
          type="search"
          :placeholder="t('common.searchPlaceholder')"
          class="flex-1 rounded-l-xl border border-r-0 border-zinc-300 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
        <button type="submit" class="rounded-r-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white hover:bg-brand-700">
          {{ t('search.submit') }}
        </button>
      </form>

      <!-- Filters -->
      <div class="mt-4 flex flex-wrap items-center gap-3">
        <select
          v-model="filters.category"
          class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          @change="submit"
        >
          <option value="">{{ t('common.allCategories') }}</option>
          <option v-for="category in categoryList" :key="category.id" :value="category.slug">
            {{ category.name }}
          </option>
        </select>

        <select
          v-model="filters.language"
          class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          @change="submit"
        >
          <option value="">{{ t('search.anyLanguage') }}</option>
          <option value="en">{{ t('common.english') }}</option>
          <option value="bn">{{ t('common.bangla') }}</option>
        </select>

        <select
          v-model="filters.sort"
          class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          @change="submit"
        >
          <option value="latest">{{ t('search.sortLatest') }}</option>
          <option value="oldest">{{ t('search.sortOldest') }}</option>
          <option value="popular">{{ t('search.sortPopular') }}</option>
        </select>
      </div>

      <p v-if="searched && !loading" class="mt-6 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
        {{ t('search.resultsCount', { count: meta?.total ?? 0 }) }}
      </p>

      <AppSpinner v-if="loading" />

      <EmptyState
        v-else-if="searched && list.length === 0"
        :title="t('article.noArticles')"
        :subtitle="t('common.noResults')"
      />

      <p v-else-if="!searched" class="py-16 text-center text-sm text-zinc-400">
        {{ t('common.searchPlaceholder') }}
      </p>

      <template v-else>
        <div class="mt-4 grid gap-x-8 lg:grid-cols-2">
          <ArticleCard v-for="item in list" :key="item.id" :article="item" variant="list" />
        </div>

        <div class="mt-10">
          <AppPagination v-if="meta" :meta="meta" @page="changePage" />
        </div>
      </template>
    </main>

    <PublicFooter />
  </div>
</template>
