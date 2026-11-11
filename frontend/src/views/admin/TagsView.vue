<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const search = ref('')
const page = ref(1)

const showModal = ref(false)
const editing = ref(null)
const busy = ref(false)
const name = ref('')

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.tags.list({
      page: page.value,
      q: search.value || undefined,
      per_page: 25,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editing.value = null
  name.value = ''
  showModal.value = true
}

function openEdit(tag) {
  editing.value = tag
  name.value = tag.name
  showModal.value = true
}

async function submit() {
  busy.value = true

  try {
    if (editing.value) {
      await adminApi.tags.update(editing.value.id, { name: name.value })
    } else {
      await adminApi.tags.store({ name: name.value })
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

async function remove(tag) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.tags.destroy(tag.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
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

      <button
        class="ml-auto rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
        @click="openCreate"
      >
        + {{ t('admin.newTag') }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tagName') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.articlesCount') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="tag in list" :key="tag.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="px-5 py-3">
                <span class="font-bold dark:text-zinc-100">#{{ tag.name }}</span>
                <span class="ml-2 font-mono text-xs text-zinc-400">/{{ tag.slug }}</span>
              </td>

              <td class="px-5 py-3 font-semibold text-zinc-500">{{ tag.articles_count }}</td>

              <td class="whitespace-nowrap px-5 py-3 text-right">
                <button class="mr-3 text-xs font-bold text-blue-600 hover:underline" @click="openEdit(tag)">
                  {{ t('common.edit') }}
                </button>
                <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(tag)">
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

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 grid place-items-center px-4">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="showModal = false" />

        <form
          class="relative w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h3 class="text-lg font-black dark:text-white">{{ editing ? t('common.edit') : t('admin.newTag') }}</h3>

          <input
            v-model="name"
            type="text"
            required
            maxlength="100"
            class="mt-5 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            :placeholder="t('admin.tagName')"
          />

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
              :disabled="busy"
            >
              {{ t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
