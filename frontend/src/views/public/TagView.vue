<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { articles, tags } from '@/api'

const route = useRoute()
const { t } = useI18n()

const loading = ref(true)
const tag = ref(null)
const list = ref([])
const meta = ref(null)
const page = ref(1)

async function load() {
  loading.value = true

  try {
    const [tagResponse, listResponse] = await Promise.all([
      tags.show(route.params.slug),
      articles.list({ tag: route.params.slug, page: page.value }),
    ])

    tag.value = tagResponse.data.data
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

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div class="mb-10 border-b border-zinc-200 pb-8 dark:border-zinc-800">
        <p class="text-sm font-black uppercase tracking-widest text-zinc-400"># {{ t('admin.tags').toLowerCase() }}</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight dark:text-white sm:text-4xl">
          {{ tag?.name ?? '…' }}
        </h1>
        <p class="mt-2 text-sm text-zinc-500">{{ tag?.articles_count ?? 0 }} {{ t('admin.articles').toLowerCase() }}</p>
      </div>

      <AppSpinner v-if="loading" />

      <EmptyState v-else-if="list.length === 0" :title="t('article.noArticles')" />

      <template v-else>
        <div class="grid gap-x-8 lg:grid-cols-2">
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
