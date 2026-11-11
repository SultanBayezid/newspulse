<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatDateTime } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const list = ref([])

const showModal = ref(false)
const editing = ref(null)
const busy = ref(false)

const form = reactive({
  question: '',
  description: '',
  is_active: true,
  ends_at: '',
  options: [{ label: '' }, { label: '' }],
})

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.polls.list({ per_page: 50 })
    list.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editing.value = null
  Object.assign(form, {
    question: '',
    description: '',
    is_active: true,
    ends_at: '',
    options: [{ label: '' }, { label: '' }],
  })
  showModal.value = true
}

function openEdit(poll) {
  editing.value = poll
  Object.assign(form, {
    question: poll.question,
    description: poll.description ?? '',
    is_active: poll.is_active,
    ends_at: poll.ends_at ? poll.ends_at.slice(0, 16) : '',
    options: poll.options.map((option) => ({ id: option.id, label: option.label })),
  })
  showModal.value = true
}

function addOption() {
  if (form.options.length < 10) {
    form.options.push({ label: '' })
  }
}

function removeOption(index) {
  if (form.options.length > 2) {
    form.options.splice(index, 1)
  }
}

async function submit() {
  busy.value = true

  const payload = {
    question: form.question,
    description: form.description || null,
    is_active: form.is_active,
    ends_at: form.ends_at ? new Date(form.ends_at).toISOString() : null,
    options: form.options.filter((option) => option.label.trim() !== ''),
  }

  try {
    if (editing.value) {
      await adminApi.polls.update(editing.value.id, payload)
    } else {
      await adminApi.polls.store(payload)
    }

    showModal.value = false
    ui.toast(t('admin.saved'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}

async function remove(poll) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.polls.destroy(poll.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}
</script>

<template>
  <div>
    <div class="mb-5 flex justify-end">
      <button
        class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
        @click="openCreate"
      >
        + {{ t('admin.newPoll') }}
      </button>
    </div>

    <AppSpinner v-if="loading" />

    <EmptyState v-else-if="list.length === 0" :title="t('admin.newPoll') + '…'" />

    <div v-else class="grid gap-5 lg:grid-cols-2">
      <div
        v-for="poll in list"
        :key="poll.id"
        class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-black leading-snug dark:text-white">{{ poll.question }}</h3>
            <p class="mt-1 text-xs text-zinc-400">
              {{ poll.total_votes }} {{ t('admin.votes') }}
              <template v-if="poll.ends_at"> · {{ t('admin.pollEndsAt') }}: {{ formatDateTime(poll.ends_at) }}</template>
            </p>
          </div>

          <span
            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold"
            :class="poll.is_active
              ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
              : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800'"
          >
            {{ poll.is_active ? t('admin.pollActive') : '—' }}
          </span>
        </div>

        <div class="mt-4 space-y-2">
          <div v-for="option in poll.options" :key="option.id">
            <div class="mb-1 flex justify-between text-xs">
              <span class="font-semibold dark:text-zinc-300">{{ option.label }}</span>
              <span class="text-zinc-400">{{ option.votes_count }} ({{ option.percent }}%)</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
              <div class="h-full rounded-full bg-brand-600" :style="{ width: option.percent + '%' }" />
            </div>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2">
          <button class="rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" @click="openEdit(poll)">
            {{ t('common.edit') }}
          </button>
          <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-500 dark:border-red-900" @click="remove(poll)">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="showModal = false" />

        <form
          class="relative mx-auto my-8 w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h3 class="text-lg font-black dark:text-white">{{ editing ? t('common.edit') : t('admin.newPoll') }}</h3>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.pollQuestion') }} *</label>
              <input
                v-model="form.question"
                type="text"
                required
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.categoryDescription') }}</label>
              <textarea
                v-model="form.description"
                rows="2"
                maxlength="1000"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.pollOptions') }} *</label>

              <div class="space-y-2">
                <div v-for="(option, index) in form.options" :key="index" class="flex gap-2">
                  <input
                    v-model="option.label"
                    type="text"
                    required
                    maxlength="250"
                    :placeholder="t('admin.pollOptions') + ' ' + (index + 1)"
                    class="flex-1 rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                  />

                  <button
                    v-if="form.options.length > 2"
                    type="button"
                    class="rounded-xl border border-zinc-300 px-3 text-sm font-bold text-red-500 dark:border-zinc-700"
                    @click="removeOption(index)"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <button
                v-if="form.options.length < 10"
                type="button"
                class="mt-2 text-sm font-bold text-brand-600 hover:underline dark:text-brand-400"
                @click="addOption"
              >
                + {{ t('admin.addOption') }}
              </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.pollEndsAt') }}</label>
                <input
                  v-model="form.ends_at"
                  type="datetime-local"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <label class="flex items-end gap-2 pb-2.5 text-sm font-semibold dark:text-zinc-200">
                <input v-model="form.is_active" type="checkbox" class="mb-0.5 h-4 w-4 accent-brand-600" />
                {{ t('admin.pollActive') }}
              </label>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-bold text-zinc-600 dark:border-zinc-700 dark:text-zinc-300"
              @click="showModal = false"
            >
              {{ t('common.cancel') }}
            </button>

            <button
              type="submit"
              class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-bold text-white hover:bg-brand-700 disabled:opacity-50"
              :disabled="busy || form.options.filter((o) => o.label.trim()).length < 2"
            >
              {{ busy ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
