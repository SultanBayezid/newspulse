<script setup>
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto grid max-w-3xl place-items-center px-4 py-24 text-center sm:px-6">
      <div>
        <p class="text-7xl font-black text-zinc-200 dark:text-zinc-800">404</p>
        <h1 class="mt-6 text-2xl font-black dark:text-white">{{ t('errors.notFoundTitle') }}</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">{{ t('errors.notFoundText') }}</p>

        <RouterLink
          :to="{ name: 'home' }"
          class="mt-8 inline-block rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-700"
        >
          ← {{ t('errors.backHome') }}
        </RouterLink>
      </div>
    </main>

    <PublicFooter />
  </div>
</template>
