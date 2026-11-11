<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import RichTextEditor from '@/components/admin/RichTextEditor.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { formatDate } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const list = ref([])

const showModal = ref(false)
const editing = ref(null)
const busy = ref(false)

const form = reactive({
  title: '',
  slug: '',
  body: '',
  is_published: true,
  meta_title: '',
  meta_description: '',
})

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.pages.list()
    list.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editing.value = null
  Object.assign(form, {
    title: '', slug: '', body: '', is_published: true, meta_title: '', meta_description: '',
  })
  showModal.value = true
}

function openEdit(page) {
  editing.value = page
  Object.assign(form, {
    title: page.title,
    slug: page.slug,
    body: page.body,
    is_published: page.is_published,
    meta_title: page.meta_title ?? '',
    meta_description: page.meta_description ?? '',
  })
  showModal.value = true
}

async function submit() {
  busy.value = true

  try {
    if (editing.value) {
      await adminApi.pages.update(editing.value.id, { ...form, slug: form.slug || undefined })
    } else {
      await adminApi.pages.store(form)
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

async function remove(page) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.pages.destroy(page.id)
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
        + {{ t('admin.newPage') }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableTitle') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.tableAuthor') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('common.status') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.tableDate') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="page in list" :key="page.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="px-5 py-3.5">
                <RouterLink
                  :to="{ name: 'page', params: { slug: page.slug } }"
                  class="font-bold text-zinc-800 hover:text-brand-600 dark:text-zinc-100"
                >
                  {{ page.title }}
                </RouterLink>
                <p class="mt-0.5 font-mono text-xs text-zinc-400">/{{ page.slug }}</p>
              </td>

              <td class="hidden px-5 py-3.5 text-zinc-500 sm:table-cell">{{ page.author?.name ?? '—' }}</td>

              <td class="px-5 py-3.5">
                <span
                  class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                  :class="page.is_published
                    ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                    : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800'"
                >
                  {{ page.is_published ? t('admin.pagePublished') : t('admin.pageDraft') }}
                </span>
              </td>

              <td class="hidden px-5 py-3.5 text-zinc-500 sm:table-cell">{{ formatDate(page.updated_at) }}</td>

              <td class="whitespace-nowrap px-5 py-3.5 text-right">
                <button class="mr-3 text-xs font-bold text-blue-600 hover:underline" @click="openEdit(page)">
                  {{ t('common.edit') }}
                </button>
                <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(page)">
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Editor modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="showModal = false" />

        <form
          class="relative mx-auto my-8 w-full max-w-3xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h3 class="text-lg font-black dark:text-white">
            {{ editing ? t('common.edit') : t('admin.newPage') }}
          </h3>

          <div class="mt-5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.tableTitle') }} *</label>
                <input
                  v-model="form.title"
                  type="text"
                  required
                  maxlength="250"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.slugField') }}</label>
                <input
                  v-model="form.slug"
                  type="text"
                  maxlength="250"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>

            <RichTextEditor v-model="form.body" placeholder="…" />

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.metaTitle') }}</label>
                <input
                  v-model="form.meta_title"
                  type="text"
                  maxlength="250"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.metaDescription') }}</label>
                <input
                  v-model="form.meta_description"
                  type="text"
                  maxlength="500"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold dark:text-zinc-200">
              <input v-model="form.is_published" type="checkbox" class="h-4 w-4 accent-brand-600" />
              {{ t('admin.pagePublished') }}
            </label>
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
              :disabled="busy || !form.title || !form.body"
            >
              {{ busy ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
