<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'

const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const form = reactive({
  name: '',
  bio: '',
  current_password: '',
  password: '',
  password_confirmation: '',
})

const busy = ref(false)

onMounted(() => {
  form.name = auth.user?.name ?? ''
  form.bio = auth.user?.bio ?? ''
})

async function submit() {
  busy.value = true

  const payload = {
    name: form.name,
    bio: form.bio || null,
  }

  if (form.password) {
    payload.current_password = form.current_password
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  try {
    await auth.updateProfile(payload)
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
    ui.toast(t('admin.saved'))
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="min-h-screen">
    <PublicHeader />

    <main class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
      <h1 class="text-3xl font-black tracking-tight dark:text-white">{{ t('account.profileTitle') }}</h1>
      <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ t('account.profileSubtitle') }}</p>

      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="flex items-center gap-4">
          <span class="grid h-16 w-16 place-items-center rounded-full bg-brand-100 text-2xl font-black text-brand-700 dark:bg-brand-900 dark:text-brand-200">
            {{ (auth.user?.name ?? '?').charAt(0).toUpperCase() }}
          </span>

          <div>
            <p class="font-bold dark:text-white">{{ auth.user?.name }}</p>
            <p class="text-sm text-zinc-500">{{ auth.user?.email }}</p>
            <p v-if="auth.user?.email_verified_at" class="mt-0.5 text-xs font-semibold text-green-600">✓ {{ t('auth.verified') }}</p>
            <RouterLink v-else :to="{ name: 'verify-email' }" class="mt-0.5 block text-xs font-semibold text-amber-600 hover:underline">
              {{ t('auth.verificationTitle') }} →
            </RouterLink>
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="name">{{ t('auth.name') }}</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="bio">{{ t('account.bio') }}</label>
          <textarea
            id="bio"
            v-model="form.bio"
            rows="3"
            maxlength="500"
            class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          />
        </div>

        <fieldset class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
          <legend class="px-2 text-sm font-black uppercase tracking-wider text-zinc-400">
            {{ t('account.passwordSection') }}
          </legend>

          <div class="space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="current_password">
                {{ t('auth.currentPassword') }}
              </label>
              <input
                id="current_password"
                v-model="form.current_password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="password">
                  {{ t('auth.newPassword') }}
                </label>
                <input
                  id="password"
                  v-model="form.password"
                  type="password"
                  minlength="8"
                  autocomplete="new-password"
                  class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="password_confirmation">
                  {{ t('auth.confirmPassword') }}
                </label>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  autocomplete="new-password"
                  class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>
          </div>
        </fieldset>

        <button
          type="submit"
          class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
          :disabled="busy"
        >
          {{ busy ? t('common.saving') : t('account.updateProfile') }}
        </button>
      </form>
    </main>

    <PublicFooter />
  </div>
</template>
