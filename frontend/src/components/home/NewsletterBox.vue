<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/stores/ui'
import { newsletter } from '@/api'
import { apiError } from '@/api/client'

const { t } = useI18n()
const ui = useUiStore()

const email = ref('')
const busy = ref(false)
const done = ref(false)

async function subscribe() {
  if (busy.value || email.value.trim() === '') {
    return
  }

  busy.value = true

  try {
    await newsletter.subscribe({ email: email.value.trim() })
    done.value = true
    email.value = ''
    ui.toast(t('newsletter.success'))
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="relative overflow-hidden rounded-3xl bg-zinc-900 px-6 py-12 text-center text-white dark:bg-zinc-900 sm:px-12">
    <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #dc2626 0, transparent 40%), radial-gradient(circle at 80% 80%, #f59e0b 0, transparent 40%)" />

    <div class="relative mx-auto max-w-xl">
      <h2 class="text-2xl font-black tracking-tight sm:text-3xl">{{ t('newsletter.title') }}</h2>
      <p class="mt-3 text-sm text-zinc-300 sm:text-base">{{ t('newsletter.subtitle') }}</p>

      <form v-if="!done" class="mt-6 flex flex-col gap-3 sm:flex-row" @submit.prevent="subscribe">
        <input
          v-model="email"
          type="email"
          required
          :placeholder="t('newsletter.placeholder')"
          class="flex-1 rounded-xl border border-zinc-700 bg-zinc-800/80 px-4 py-3 text-sm text-white placeholder-zinc-500 outline-none focus:border-brand-500"
        />
        <button
          type="submit"
          class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold transition hover:bg-brand-500 disabled:opacity-50"
          :disabled="busy"
        >
          {{ busy ? t('newsletter.subscribing') : t('newsletter.subscribe') }}
        </button>
      </form>

      <p v-else class="mt-6 rounded-xl bg-green-500/10 px-4 py-3 text-sm font-semibold text-green-300">
        {{ t('newsletter.success') }}
      </p>
    </div>
  </section>
</template>
