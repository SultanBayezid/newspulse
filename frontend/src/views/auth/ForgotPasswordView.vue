<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthCard from './AuthCard.vue'
import { auth } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'

const { t } = useI18n()
const ui = useUiStore()

const form = reactive({ email: '' })
const busy = ref(false)
const done = ref(false)

async function submit() {
  busy.value = true

  try {
    await auth.forgotPassword(form)
    done.value = true
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthCard :title="t('auth.forgotPassword')" :subtitle="t('auth.signInSubtitle')">
    <div v-if="done" class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
      {{ t('auth.sendResetLink') }} ✓
      <p class="mt-2 font-normal">If an account exists for that email, a reset link has been sent.</p>
    </div>

    <form v-else class="space-y-5" @submit.prevent="submit">
      <div>
        <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="email">{{ t('auth.email') }}</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
      </div>

      <button
        type="submit"
        class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
        :disabled="busy"
      >
        {{ busy ? t('common.loading') : t('auth.sendResetLink') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm">
      <RouterLink :to="{ name: 'login' }" class="font-bold text-brand-600 hover:underline dark:text-brand-400">
        ← {{ t('auth.backToLogin') }}
      </RouterLink>
    </p>
  </AuthCard>
</template>
