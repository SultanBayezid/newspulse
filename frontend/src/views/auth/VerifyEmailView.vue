<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthCard from './AuthCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { auth } from '@/api'
import { apiError } from '@/api/client'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()
const ui = useUiStore()

const state = ref('idle') // idle | verifying | verified | invalid
const message = ref('')
const resending = ref(false)

const hasParams = computed(() =>
  ['id', 'hash', 'expires', 'signature'].every((key) => route.query[key]),
)

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    state.value = 'invalid'
    message.value = 'Please sign in first, then open the verification link again.'

    return
  }

  if (authStore.user?.email_verified_at) {
    state.value = 'verified'

    return
  }

  if (hasParams.value) {
    await verify()
  }
})

async function verify() {
  state.value = 'verifying'

  try {
    await auth.verifyEmail({
      id: route.query.id,
      hash: route.query.hash,
      expires: route.query.expires,
      signature: route.query.signature,
    })

    state.value = 'verified'
    await authStore.refresh()
    ui.toast(t('auth.verified'))
    setTimeout(() => router.push({ name: 'home' }), 1500)
  } catch (error) {
    state.value = 'invalid'
    message.value = apiError(error, t('newsletter.invalidLink'))
  }
}

async function resend() {
  resending.value = true

  try {
    const { data } = await auth.sendVerification()
    ui.toast(data.message)
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    resending.value = false
  }
}
</script>

<template>
  <AuthCard :title="t('auth.verificationTitle')" :subtitle="t('auth.verificationPending')">
    <div v-if="state === 'verified' || (authStore.user?.email_verified_at && state !== 'invalid')" class="rounded-xl bg-green-50 p-5 text-center dark:bg-green-950">
      <span class="text-4xl">✓</span>
      <p class="mt-2 font-bold text-green-700 dark:text-green-300">{{ t('auth.verified') }}</p>
    </div>

    <div v-else-if="state === 'invalid'" class="rounded-xl bg-red-50 p-5 text-center dark:bg-red-950">
      <span class="text-4xl">✕</span>
      <p class="mt-2 font-bold text-red-700 dark:text-red-300">{{ message || t('newsletter.invalidLink') }}</p>
    </div>

    <div v-else class="space-y-4">
      <div v-if="hasParams && state === 'verifying'" class="rounded-xl bg-zinc-50 p-5 text-center text-sm font-semibold text-zinc-500 dark:bg-zinc-900">
        {{ t('common.loading') }}
      </div>

      <div v-else class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700">
        <span class="text-4xl">📧</span>
        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
          {{ t('auth.verificationPending') }}
          <strong class="block text-zinc-800 dark:text-zinc-200">{{ authStore.user?.email }}</strong>
        </p>

        <button
          class="mt-5 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
          :disabled="resending"
          @click="resend"
        >
          {{ resending ? t('common.loading') : t('auth.resendVerification') }}
        </button>
      </div>
    </div>

    <p class="mt-6 text-center text-sm">
      <RouterLink :to="{ name: 'home' }" class="font-bold text-brand-600 hover:underline dark:text-brand-400">
        ← {{ t('errors.backHome') }}
      </RouterLink>
    </p>
  </AuthCard>
</template>
