<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import client, { apiError } from '@/api/client'
import { useUiStore } from '@/stores/ui'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { formatDate } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const search = ref('')
const status = ref('')
const page = ref(1)

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.subscribers.list({
      page: page.value,
      q: search.value || undefined,
      status: status.value || undefined,
      per_page: 25,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

const exporting = ref(false)

async function exportCsv() {
  if (exporting.value) {
    return
  }

  exporting.value = true

  try {
    const response = await client.get('/admin/newsletter/subscribers/export', {
      responseType: 'blob',
    })

    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `newspulse-subscribers-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    exporting.value = false
  }
}

async function remove(subscriber) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.subscribers.destroy(subscriber.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

const statusLabels = {
  pending: t('admin.subscriberPending'),
  subscribed: t('admin.subscriberSubscribed'),
  unsubscribed: t('admin.subscriberUnsubscribed'),
}
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center gap-3">
      <input
        v-model="search"
        type="search"
        :placeholder="t('admin.searchPlaceholder')"
        class="w-56 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @keyup.enter="page = 1; load()"
      />

      <select
        v-model="status"
        class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @change="page = 1; load()"
      >
        <option value="">{{ t('admin.filterAll') }}</option>
        <option value="subscribed">{{ t('admin.subscriberSubscribed') }}</option>
        <option value="pending">{{ t('admin.subscriberPending') }}</option>
        <option value="unsubscribed">{{ t('admin.subscriberUnsubscribed') }}</option>
      </select>

      <a
        href="#"
        class="ml-auto rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-bold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
        @click.prevent="exportCsv"
      >
        {{ exporting ? t('common.loading') : '⬇ ' + t('admin.exportCsv') }}
      </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableEmail') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.tableName') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('common.status') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.tableDate') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="subscriber in list" :key="subscriber.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="px-5 py-3.5 font-semibold dark:text-zinc-200">{{ subscriber.email }}</td>
              <td class="hidden px-5 py-3.5 text-zinc-500 sm:table-cell">{{ subscriber.name ?? '—' }}</td>

              <td class="px-5 py-3.5">
                <span
                  class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                  :class="{
                    'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300': subscriber.status === 'subscribed',
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': subscriber.status === 'pending',
                    'bg-zinc-100 text-zinc-500 dark:bg-zinc-800': subscriber.status === 'unsubscribed',
                  }"
                >
                  {{ statusLabels[subscriber.status] ?? subscriber.status }}
                </span>
              </td>

              <td class="hidden px-5 py-3.5 text-zinc-500 sm:table-cell">
                {{ formatDate(subscriber.subscribed_at ?? subscriber.created_at) }}
              </td>

              <td class="px-5 py-3.5 text-right">
                <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(subscriber)">
                  {{ t('common.delete') }}
                </button>
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
