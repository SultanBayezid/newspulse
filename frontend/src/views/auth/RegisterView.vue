<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthCard from './AuthCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'

const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const busy = ref(false)
const errors = ref({})

async function submit() {
  busy.value = true
  errors.value = {}

  try {
    await auth.register(form)
    ui.toast('Account created successfully.')
    router.push({ name: 'verify-email' })
  } catch (error) {
    errors.value = error.response?.data?.errors ?? {}
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthCard :title="t('auth.signUpTitle')" :subtitle="t('auth.signUpSubtitle')">
    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="name">{{ t('auth.name') }}</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          autocomplete="name"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          :class="{ 'border-red-500': errors.name }"
        />
        <p v-if="errors.name" class="mt-1 text-xs font-semibold text-red-500">{{ errors.name[0] }}</p>
      </div>

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
          minlength="8"
          autocomplete="new-password"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          :class="{ 'border-red-500': errors.password }"
        />
        <p v-if="errors.password" class="mt-1 text-xs font-semibold text-red-500">{{ errors.password[0] }}</p>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="password_confirmation">
          {{ t('auth.confirmPassword') }}
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
      </div>

      <button
        type="submit"
        class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
        :disabled="busy"
      >
        {{ busy ? t('common.loading') : t('auth.signUp') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
      {{ t('auth.backToLogin') }}?
      <RouterLink :to="{ name: 'login' }" class="font-bold text-brand-600 hover:underline dark:text-brand-400">
        {{ t('auth.signIn') }}
      </RouterLink>
    </p>
  </AuthCard>
</template>
