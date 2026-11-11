<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { polls } from '@/api'
import { apiError } from '@/api/client'
import { formatDateTime } from '@/utils/format'

const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const poll = ref(null)
const loading = ref(true)
const voting = ref(false)
const selectedOption = ref(null)

onMounted(load)

async function load() {
  loading.value = true

  try {
    const { data } = await polls.active()
    poll.value = data.data
  } catch {
    poll.value = null
  } finally {
    loading.value = false
  }
}

const showResults = computed(() => poll.value === null || poll.value.has_voted || !auth.isAuthenticated || !poll.value.is_active)

async function vote() {
  if (!selectedOption.value || voting.value) {
    return
  }

  voting.value = true

  try {
    const { data } = await polls.vote(poll.value.id, selectedOption.value)
    poll.value = data.data
    ui.toast(t('poll.thanks'))
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    voting.value = false
  }
}
</script>

<template>
  <div
    v-if="!loading && poll"
    class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
  >
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-black uppercase tracking-wider text-brand-600 dark:text-brand-400">
        {{ t('poll.title') }}
      </h3>
      <span v-if="poll.ends_at" class="text-xs text-zinc-400">
        {{ t('poll.endsIn', { date: formatDateTime(poll.ends_at) }) }}
      </span>
    </div>

    <p class="mt-3 text-lg font-bold leading-snug dark:text-white">{{ poll.question }}</p>

    <div v-if="showResults" class="mt-4 space-y-3">
      <div v-for="option in poll.options" :key="option.id">
        <div class="mb-1 flex items-baseline justify-between text-sm">
          <span class="font-medium dark:text-zinc-200" :class="{ 'font-bold': option.id === poll.user_vote }">
            {{ option.label }}
            <span v-if="option.id === poll.user_vote" class="text-brand-600 dark:text-brand-400">✓</span>
          </span>
          <span class="text-zinc-500">{{ option.percent }}%</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
          <div
            class="h-full rounded-full transition-all duration-700"
            :class="option.id === poll.user_vote ? 'bg-brand-600' : 'bg-zinc-400 dark:bg-zinc-500'"
            :style="{ width: option.percent + '%' }"
          />
        </div>
      </div>

      <p class="pt-1 text-xs font-semibold text-zinc-400">
        {{ t('poll.totalVotes', { count: poll.total_votes }) }}
      </p>
    </div>

    <div v-else class="mt-4 space-y-2">
      <label
        v-for="option in poll.options"
        :key="option.id"
        class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 p-3 text-sm font-medium transition hover:border-brand-500 hover:bg-brand-50/50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-brand-500 dark:hover:bg-brand-950/20"
        :class="{ 'border-brand-600 bg-brand-50 dark:border-brand-500 dark:bg-brand-950/30': selectedOption === option.id }"
      >
        <input v-model="selectedOption" type="radio" :value="option.id" class="accent-brand-600" />
        {{ option.label }}
      </label>

      <button
        class="mt-2 w-full rounded-xl bg-brand-600 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
        :disabled="!selectedOption || voting"
        @click="vote"
      >
        {{ voting ? t('common.saving') : t('poll.vote') }}
      </button>

      <p class="text-center text-xs text-zinc-400">{{ t('poll.resultsWillShow') }}</p>
    </div>
  </div>
</template>
