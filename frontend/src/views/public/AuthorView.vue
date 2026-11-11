<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { authors } from '@/api'

const route = useRoute()
const { t } = useI18n()

const loading = ref(true)
const author = ref(null)
const list = ref([])
const meta = ref(null)

onMounted(load)

async function load() {
  loading.value = true

  try {
    const { data } = await authors.show(route.params.id)
    author.value = data.data
    list.value = data.articles.data
    meta.value = data.articles.meta
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  load(page)
}
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
      <AppSpinner v-if="loading" />

      <template v-else-if="author">
        <!-- Profile header -->
        <div class="flex flex-col items-center border-b border-zinc-200 pb-10 text-center dark:border-zinc-800 sm:flex-row sm:items-start sm:text-left">
          <span class="grid h-24 w-24 shrink-0 place-items-center rounded-full bg-brand-100 text-3xl font-black text-brand-700 dark:bg-brand-900 dark:text-brand-200">
            {{ author.name.charAt(0).toUpperCase() }}
          </span>

          <div class="mt-6 sm:ml-6 sm:mt-0">
            <h1 class="text-3xl font-black tracking-tight dark:text-white">{{ author.name }}</h1>
            <p class="mt-1 text-sm font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
              {{ author.role_label }}
            </p>
            <p v-if="author.bio" class="mt-3 max-w-2xl leading-relaxed text-zinc-600 dark:text-zinc-400">
              {{ author.bio }}
            </p>
          </div>
        </div>

        <h2 class="mb-6 mt-10 text-lg font-black uppercase tracking-tight dark:text-white">
          {{ t('admin.articles') }}
          <span class="text-zinc-400">({{ meta?.total ?? 0 }})</span>
        </h2>

        <EmptyState v-if="list.length === 0" :title="t('article.noArticles')" />

        <template v-else>
          <div class="grid gap-x-8 lg:grid-cols-2">
            <ArticleCard v-for="item in list" :key="item.id" :article="item" variant="list" />
          </div>

          <div class="mt-10">
            <AppPagination v-if="meta" :meta="meta" @page="changePage" />
          </div>
        </template>
      </template>
    </main>

    <PublicFooter />
  </div>
</template>
