<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import StatusBadge from '@/components/admin/StatusBadge.vue'
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
const status = ref('')
const page = ref(1)

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.comments.list({
      page: page.value,
      status: status.value || undefined,
      q: search.value || undefined,
      per_page: 20,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function changeStatus(comment, next) {
  try {
    await adminApi.comments.changeStatus(comment.id, next)
    comment.status = next
    ui.toast(t('admin.saved'))
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

async function remove(comment) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.comments.destroy(comment.id)
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

      <div class="flex gap-1 rounded-xl border border-zinc-300 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900">
        <button
          v-for="option in ['', 'pending', 'approved', 'rejected']"
          :key="option"
          class="rounded-lg px-3 py-1.5 text-xs font-bold transition"
          :class="status === option
            ? 'bg-brand-600 text-white'
            : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
          @click="status = option; page = 1; load()"
        >
          {{ option === '' ? t('admin.filterAll') : t('admin.comments' + option.charAt(0).toUpperCase() + option.slice(1)) }}
        </button>
      </div>
    </div>

    <AppSpinner v-if="loading" />

    <EmptyState v-else-if="list.length === 0" :title="t('common.noResults')" />

    <template v-else>
      <div class="space-y-3">
        <div
          v-for="comment in list"
          :key="comment.id"
          class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                {{ (comment.user?.name ?? '?').charAt(0) }}
              </span>

              <div>
                <p class="text-sm font-bold dark:text-zinc-100">{{ comment.user?.name }}</p>
                <p class="text-xs text-zinc-400">{{ formatRelative(comment.created_at) }}</p>
              </div>
            </div>

            <StatusBadge :status="comment.status" />
          </div>

          <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">
            {{ comment.body }}
          </p>

          <p v-if="comment.article" class="mt-3 text-xs font-semibold text-zinc-400">
            {{ t('admin.onArticle') }}:
            <RouterLink
              :to="{ name: 'article', params: { slug: comment.article.slug } }"
              class="text-brand-600 hover:underline dark:text-brand-400"
            >
              {{ comment.article.title }}
            </RouterLink>
          </p>

          <div class="mt-4 flex flex-wrap gap-2">
            <button
              v-if="comment.status !== 'approved'"
              class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-green-700"
              @click="changeStatus(comment, 'approved')"
            >
              ✓ {{ t('admin.approve') }}
            </button>

            <button
              v-if="comment.status === 'pending'"
              class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-bold text-zinc-600 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
              @click="changeStatus(comment, 'rejected')"
            >
              ✕ {{ t('admin.rejectComment') }}
            </button>

            <button
              class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-500 transition hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/40"
              @click="remove(comment)"
            >
              🗑 {{ t('admin.deleteComment') }}
            </button>
          </div>
        </div>
      </div>

      <div class="mt-8">
        <AppPagination v-if="meta" :meta="meta" @page="(p) => { page = p; load() }" />
      </div>
    </template>
  </div>
</template>
