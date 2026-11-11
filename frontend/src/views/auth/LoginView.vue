<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthCard from './AuthCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const form = reactive({
  email: '',
  password: '',
})

const busy = ref(false)
const errors = ref({})

async function submit() {
  busy.value = true
  errors.value = {}

  try {
    await auth.login(form)
    ui.toast(t('nav.login') + ' ✓')

    const redirect = route.query.redirect

    if (redirect && typeof redirect === 'string') {
      router.push(redirect)
    } else if (auth.isStaff) {
      router.push({ name: 'admin-dashboard' })
    } else {
      router.push({ name: 'home' })
    }
  } catch (error) {
    errors.value = error.response?.data?.errors ?? {}
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthCard :title="t('auth.signInTitle')" :subtitle="t('auth.signInSubtitle')">
    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="email">{{ t('auth.email') }}</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          :class="{ 'border-red-500': errors.email }"
        />
        <p v-if="errors.email" class="mt-1 text-xs font-semibold text-red-500">{{ errors.email[0] }}</p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="password">{{ t('auth.password') }}</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          :class="{ 'border-red-500': errors.password }"
        />
      </div>

      <button
        type="submit"
        class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
        :disabled="busy"
      >
        {{ busy ? t('common.loading') : t('auth.signIn') }}
      </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
      <RouterLink :to="{ name: 'forgot-password' }" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
        {{ t('auth.forgotPassword') }}
      </RouterLink>

      <RouterLink :to="{ name: 'register' }" class="font-semibold text-zinc-600 hover:underline dark:text-zinc-300">
        {{ t('auth.signUp') }}
      </RouterLink>
    </div>

    <p class="mt-8 rounded-xl bg-zinc-50 px-4 py-3 text-xs leading-5 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
      {{ t('auth.rememberHint') }}
    </p>
  </AuthCard>
</template>
