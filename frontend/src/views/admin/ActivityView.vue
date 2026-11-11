<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { formatDateTime } from '@/utils/format'

const { t } = useI18n()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const page = ref(1)
const action = ref('')

const actionColors = {
  article: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  category: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
  comment: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  user: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
  media: 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300',
  settings: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
}

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.activity.list({
      page: page.value,
      action: action.value || undefined,
      per_page: 25,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

function actionGroup(entry) {
  return entry.action.split('.')[0]
}
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center gap-2">
      <button
        v-for="option in ['', 'article', 'category', 'comment', 'user', 'media', 'settings']"
        :key="option"
        class="rounded-xl px-3 py-2 text-xs font-bold transition"
        :class="action === option
          ? 'bg-brand-600 text-white'
          : 'border border-zinc-300 bg-white text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'"
        @click="action = option; page = 1; load()"
      >
        {{ option === '' ? t('admin.filterAll') : option }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableDate') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.activityUser') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.activityAction') }}</th>
              <th class="hidden px-5 py-3 font-bold md:table-cell">{{ t('admin.activityDescription') }}</th>
              <th class="hidden px-5 py-3 font-bold lg:table-cell">{{ t('admin.activityIp') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="entry in list" :key="entry.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="whitespace-nowrap px-5 py-3 text-xs text-zinc-400">
                {{ formatDateTime(entry.created_at) }}
              </td>

              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <span class="grid h-7 w-7 place-items-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-500 dark:bg-zinc-800">
                    {{ (entry.user?.name ?? '?').charAt(0) }}
                  </span>
                  <span class="font-semibold dark:text-zinc-200">{{ entry.user?.name ?? 'System' }}</span>
                </div>
              </td>

              <td class="px-5 py-3">
                <span
                  class="rounded-full px-2.5 py-0.5 font-mono text-xs font-bold"
                  :class="actionColors[actionGroup(entry)] ?? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800'"
                >
                  {{ entry.action }}
                </span>
              </td>

              <td class="hidden px-5 py-3 text-zinc-500 dark:text-zinc-400 md:table-cell">
                {{ entry.description ?? '—' }}
              </td>

              <td class="hidden px-5 py-3 font-mono text-xs text-zinc-400 lg:table-cell">
                {{ entry.ip ?? '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6">
      <AppPagination v-if="meta" :meta="meta" @page="(p) => { page = p; load() }" />
    </div>
  </div>
</template>
