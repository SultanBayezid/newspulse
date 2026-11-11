<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatRelative } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const search = ref('')
const page = ref(1)
const uploading = ref(false)
const progress = ref(0)

const editModal = ref(false)
const editing = ref(null)
const busy = ref(false)

const form = reactive({
  alt: '',
  caption: '',
  credit: '',
})

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.media.list({
      page: page.value,
      q: search.value || undefined,
      per_page: 24,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function onUpload(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  uploading.value = true
  progress.value = 0

  const formData = new FormData()
  formData.append('file', file)

  try {
    await adminApi.media.store(formData, (event) => {
      progress.value = Math.round((event.loaded / event.total) * 100)
    })

    ui.toast('Uploaded.')
    page.value = 1
    search.value = ''
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

function openEdit(media) {
  editing.value = media
  form.alt = media.alt ?? ''
  form.caption = media.caption ?? ''
  form.credit = media.credit ?? ''
  editModal.value = true
}

async function submitEdit() {
  busy.value = true

  try {
    await adminApi.media.update(editing.value.id, form)
    editModal.value = false
    ui.toast(t('admin.saved'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}

async function remove(media) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.media.destroy(media.id)
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

      <label
        class="ml-auto cursor-pointer rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
        :class="{ 'opacity-50': uploading }"
      >
        {{ uploading ? t('admin.uploading') + ' ' + progress + '%' : '⬆ ' + t('admin.uploadImage') }}
        <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onUpload" />
      </label>
    </div>

    <AppSpinner v-if="loading" />

    <EmptyState v-else-if="list.length === 0" :title="t('admin.noMedia')" />

    <template v-else>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <div
          v-for="media in list"
          :key="media.id"
          class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div class="aspect-video overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            <img :src="media.url" :alt="media.alt ?? media.file_name" class="h-full w-full object-cover" loading="lazy" />
          </div>

          <div class="p-3">
            <p class="truncate text-xs font-bold dark:text-zinc-200" :title="media.file_name">{{ media.file_name }}</p>
            <p class="mt-0.5 text-[10px] text-zinc-400">
              {{ media.size_human }}
              <template v-if="media.width">· {{ media.width }}×{{ media.height }}</template>
              · {{ formatRelative(media.created_at) }}
            </p>

            <div class="mt-2 flex gap-2">
              <button class="flex-1 rounded-lg bg-zinc-100 py-1.5 text-xs font-bold text-zinc-600 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300" @click="openEdit(media)">
                {{ t('common.edit') }}
              </button>
              <button class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-bold text-red-500 transition hover:bg-red-100 dark:bg-red-950/40" @click="remove(media)">
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8">
        <AppPagination v-if="meta" :meta="meta" @page="(p) => { page = p; load() }" />
      </div>
    </template>

    <!-- Edit modal -->
    <Teleport to="body">
      <div v-if="editModal" class="fixed inset-0 z-50 grid place-items-center px-4">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="editModal = false" />

        <form
          class="relative w-full max-w-md overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submitEdit"
        >
          <img :src="editing.url" :alt="editing.alt ?? editing.file_name" class="max-h-56 w-full object-cover" />

          <div class="space-y-4 p-6">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.altText') }}</label>
              <input
                v-model="form.alt"
                type="text"
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.caption') }}</label>
              <input
                v-model="form.caption"
                type="text"
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.credit') }}</label>
              <input
                v-model="form.credit"
                type="text"
                maxlength="200"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-bold text-zinc-600 dark:border-zinc-700 dark:text-zinc-300"
                @click="editModal = false"
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
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
