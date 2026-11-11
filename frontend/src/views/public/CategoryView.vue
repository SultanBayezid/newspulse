<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import BreakingTicker from '@/components/layout/BreakingTicker.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { articles, categories } from '@/api'

const route = useRoute()
const { t } = useI18n()

const loading = ref(true)
const category = ref(null)
const list = ref([])
const meta = ref(null)
const page = ref(1)

async function load() {
  loading.value = true

  try {
    const [categoryResponse, listResponse] = await Promise.all([
      categories.show(route.params.slug),
      articles.list({ category: route.params.slug, page: page.value }),
    ])

    category.value = categoryResponse.data.data
    list.value = listResponse.data.data
    meta.value = listResponse.data.meta
  } finally {
    loading.value = false
  }
}

function changePage(next) {
  page.value = next
  load()
}

onMounted(load)
watch(() => route.params.slug, () => {
  page.value = 1
  load()
})
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />
    <BreakingTicker />

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <!-- Header -->
      <div class="mb-10 border-b border-zinc-200 pb-8 dark:border-zinc-800">
        <div class="flex items-center gap-3">
          <span class="inline-block h-6 w-2 rounded-full" :style="{ backgroundColor: category?.color ?? '#dc2626' }" />
          <p class="text-sm font-black uppercase tracking-widest text-zinc-400">{{ t('common.allCategories') }}</p>
        </div>

        <h1 class="mt-2 text-3xl font-black tracking-tight dark:text-white sm:text-4xl" :style="{ color: category?.color }">
          {{ category?.name ?? '…' }}
        </h1>

        <p v-if="category?.description" class="mt-3 max-w-2xl text-zinc-500 dark:text-zinc-400">
          {{ category.description }}
        </p>

        <div v-if="category?.children?.length" class="mt-4 flex flex-wrap gap-2">
          <RouterLink
            v-for="child in category.children"
            :key="child.id"
            :to="{ name: 'category', params: { slug: child.slug } }"
            class="rounded-full border border-zinc-200 px-3 py-1 text-sm font-semibold text-zinc-600 transition hover:border-brand-500 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300"
          >
            {{ child.name }}
            <span class="text-zinc-400">({{ child.articles_count }})</span>
          </RouterLink>
        </div>
      </div>

      <AppSpinner v-if="loading" />

      <EmptyState
        v-else-if="list.length === 0"
        :title="t('article.noArticles')"
        :subtitle="t('common.noResults')"
      />

      <template v-else>
        <div class="grid gap-x-8 lg:grid-cols-2">
          <ArticleCard
            v-for="item in list"
            :key="item.id"
            :article="item"
            variant="list"
          />
        </div>

        <div class="mt-10">
          <AppPagination v-if="meta" :meta="meta" @page="changePage" />
        </div>
      </template>
    </main>

    <PublicFooter />
  </div>
</template>
