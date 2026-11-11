<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { newsletter } from '@/api'

const route = useRoute()
const { t } = useI18n()

const state = ref('loading') // loading | done | invalid

onMounted(async () => {
  try {
    await newsletter.unsubscribe(route.params.token)
    state.value = 'done'
  } catch {
    state.value = 'invalid'
  }
})
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto grid max-w-xl place-items-center px-4 py-24 text-center sm:px-6">
      <AppSpinner v-if="state === 'loading'" />

      <div v-else-if="state === 'done'">
        <span class="grid h-16 w-16 place-items-center rounded-full bg-zinc-100 text-3xl text-zinc-500 dark:bg-zinc-800">
          ✕
        </span>
        <h1 class="mt-6 text-2xl font-black dark:text-white">{{ t('newsletter.unsubscribedTitle') }}</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">{{ t('newsletter.unsubscribedText') }}</p>

        <RouterLink
          :to="{ name: 'home' }"
          class="mt-8 inline-block rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-700"
        >
          {{ t('errors.backHome') }}
        </RouterLink>
      </div>

      <div v-else>
        <h1 class="text-2xl font-black dark:text-white">{{ t('newsletter.invalidLink') }}</h1>
      </div>
    </main>

    <PublicFooter />
  </div>
</template>
