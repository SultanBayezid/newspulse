<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import BreakingTicker from '@/components/layout/BreakingTicker.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import PollWidget from '@/components/home/PollWidget.vue'
import NewsletterBox from '@/components/home/NewsletterBox.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { articles, categories } from '@/api'
import { compactNumber } from '@/utils/format'

const { t } = useI18n()

const loading = ref(true)
const featured = ref(null)
const latest = ref([])
const trending = ref([])
const categorySections = ref([])

onMounted(async () => {
  try {
    const [featuredResponse, latestResponse, trendingResponse, categoriesResponse] = await Promise.all([
      articles.list({ featured: 1, per_page: 5 }),
      articles.list({ per_page: 11 }),
      articles.trending(5),
      categories.list(),
    ])

    featured.value = featuredResponse.data.data[0] ?? null

    if (featured.value) {
      latest.value = latestResponse.data.data.filter((article) => article.id !== featured.value.id).slice(0, 6)
    } else {
      featured.value = latestResponse.data.data[0] ?? null
      latest.value = latestResponse.data.data.slice(1, 7)
    }

    trending.value = trendingResponse.data.data.map((article, index) => ({ ...article, __index: index + 1 }))

    // Build per-category strips from the categories that have articles.
    const list = categoriesResponse.data.data

    const sections = await Promise.all(
      list.slice(0, 3).map(async (category) => {
        const { data } = await articles.list({ category: category.slug, per_page: 4 })

        return data.data.length > 0 ? { category, articles: data.data } : null
      }),
    )

    categorySections.value = sections.filter(Boolean)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />
    <BreakingTicker />

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <AppSpinner v-if="loading" />

      <template v-else>
        <!-- Hero + sidebar -->
        <div class="grid gap-10 lg:grid-cols-3">
          <div class="lg:col-span-2">
            <ArticleCard v-if="featured" :article="featured" variant="hero" />

            <div class="mt-10">
              <div class="mb-4 flex items-center justify-between border-b-2 border-zinc-900 pb-2 dark:border-zinc-100">
                <h2 class="text-lg font-black uppercase tracking-tight dark:text-white">{{ t('common.latest') }}</h2>
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">●</span>
              </div>

              <div class="grid gap-x-8 sm:grid-cols-2">
                <ArticleCard v-for="article in latest" :key="article.id" :article="article" variant="list" />
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <aside class="space-y-8">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
              <h3 class="mb-4 flex items-center gap-2 text-sm font-black uppercase tracking-wider text-zinc-900 dark:text-white">
                <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                </svg>
                {{ t('common.trending') }}
              </h3>

              <div class="space-y-4">
                <ArticleCard
                  v-for="article in trending"
                  :key="article.id"
                  :article="article"
                  variant="compact"
                />
              </div>
            </div>

            <PollWidget />
          </aside>
        </div>

        <!-- Category sections -->
        <section v-for="section in categorySections" :key="section.category.id" class="mt-14">
          <div class="mb-6 flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-xl font-black uppercase tracking-tight dark:text-white">
              <span class="inline-block h-4 w-1.5 rounded-full" :style="{ backgroundColor: section.category.color }" />
              {{ section.category.name }}
            </h2>

            <RouterLink
              :to="{ name: 'category', params: { slug: section.category.slug } }"
              class="text-sm font-bold text-brand-600 hover:underline dark:text-brand-400"
            >
              {{ t('common.readMore') }} →
            </RouterLink>
          </div>

          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <ArticleCard v-for="article in section.articles" :key="article.id" :article="article" />
          </div>
        </section>

        <!-- Newsletter -->
        <div class="mt-16">
          <NewsletterBox />
        </div>
      </template>
    </main>

    <PublicFooter />
  </div>
</template>
