<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { bookmarks } from '@/api'

const { t } = useI18n()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const page = ref(1)

async function load() {
  loading.value = true

  try {
    const { data } = await bookmarks.list({ page: page.value })
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

onMounted(load)
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
      <h1 class="text-3xl font-black tracking-tight dark:text-white">{{ t('account.bookmarksTitle') }}</h1>
      <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ t('account.bookmarksSubtitle') }}</p>

      <div class="mt-8">
        <AppSpinner v-if="loading" />

        <EmptyState v-else-if="list.length === 0" :title="t('account.emptyBookmarks')">
          <RouterLink
            :to="{ name: 'home' }"
            class="mt-4 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700"
          >
            {{ t('errors.backHome') }}
          </RouterLink>
        </EmptyState>

        <template v-else>
          <div class="grid gap-x-8 lg:grid-cols-2">
            <ArticleCard v-for="item in list" :key="item.id" :article="item" variant="list" />
          </div>

          <div class="mt-10">
            <AppPagination v-if="meta" :meta="meta" @page="changePage" />
          </div>
        </template>
      </div>
    </main>

    <PublicFooter />
  </div>
</template>
