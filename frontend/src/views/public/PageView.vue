<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { pages } from '@/api'

const route = useRoute()

const loading = ref(true)
const page = ref(null)
const notFound = ref(false)

async function load(slug) {
  loading.value = true
  notFound.value = false

  try {
    const { data } = await pages.show(slug)
    page.value = data.data
    document.title = `${page.value.title}`
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

onMounted(() => load(route.params.slug))
watch(() => route.params.slug, (slug) => slug && load(slug))
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
      <AppSpinner v-if="loading" />

      <div v-else-if="notFound" class="py-24 text-center">
        <p class="text-6xl font-black text-zinc-200 dark:text-zinc-700">404</p>
        <RouterLink :to="{ name: 'home' }" class="mt-4 inline-block font-bold text-brand-600 hover:underline">
          ← {{ $t('errors.backHome') }}
        </RouterLink>
      </div>

      <article v-else>
        <h1 class="text-3xl font-black tracking-tight dark:text-white sm:text-4xl">{{ page.title }}</h1>

        <p v-if="page.author" class="mt-3 text-sm text-zinc-400">
          {{ $t('common.by') }} {{ page.author.name }}
        </p>

        <div class="prose-article mt-8" v-html="page.body" />
      </article>
    </main>

    <PublicFooter />
  </div>
</template>
