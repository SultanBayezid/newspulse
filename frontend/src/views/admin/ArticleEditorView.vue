<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/api/client'
import RichTextEditor from '@/components/admin/RichTextEditor.vue'
import MediaPickerModal from '@/components/admin/MediaPickerModal.vue'
import TagInput from '@/components/admin/TagInput.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const isEdit = computed(() => route.params.id !== undefined)
const articleId = computed(() => route.params.id)

const loading = ref(isEdit.value)
const saving = ref(false)
const showMediaPicker = ref(false)
const rejectMode = ref(false)
const statusBusy = ref(false)

const categoryList = ref([])

const form = reactive({
  title: '',
  slug: '',
  excerpt: '',
  body: '',
  category_id: null,
  language: 'en',
  is_featured: false,
  is_breaking: false,
  cover_media_id: null,
  cover_url: null,
  tags: [],
  meta_title: '',
  meta_description: '',
  source_name: '',
  source_url: '',
  status: 'draft',
  published_at: '',
  review_note: null,
})

const currentStatus = ref('draft')

onMounted(async () => {
  try {
    const { data } = await adminApi.categories.list()
    categoryList.value = data.data
  } catch {
    // Category select degrades to empty.
  }

  if (isEdit.value) {
    try {
      const { data } = await adminApi.articles.show(articleId.value)

      const article = data.data

      form.title = article.title
      form.slug = article.slug
      form.excerpt = article.raw_excerpt ?? ''
      form.body = article.body
      form.category_id = article.category?.id ?? null
      form.language = article.language
      form.is_featured = article.is_featured
      form.is_breaking = article.is_breaking
      form.cover_media_id = article.cover_media_id ?? null
      form.cover_url = article.cover_url ?? null
      form.tags = (article.tags ?? []).map((tag) => tag.name)
      form.meta_title = article.meta_title ?? ''
      form.meta_description = article.meta_description ?? ''
      form.source_name = article.source_name ?? ''
      form.source_url = article.source_url ?? ''
      form.status = article.status
      currentStatus.value = article.status
      form.published_at = article.published_at ? article.published_at.slice(0, 16) : ''
    } catch (error) {
      ui.toast(apiError(error), 'error')
      router.push({ name: 'admin-articles' })
    } finally {
      loading.value = false
    }
  }
})

async function save(status = null) {
  if (saving.value) {
    return
  }

  saving.value = true

  const payload = {
    title: form.title,
    slug: form.slug || null,
    excerpt: form.excerpt || null,
    body: form.body,
    category_id: form.category_id,
    language: form.language,
    is_featured: form.is_featured,
    is_breaking: form.is_breaking,
    cover_media_id: form.cover_media_id,
    gallery: form.cover_media_id ? [form.cover_media_id] : [],
    tags: form.tags,
    meta_title: form.meta_title || null,
    meta_description: form.meta_description || null,
    source_name: form.source_name || null,
    source_url: form.source_url || null,
  }

  if (auth.isEditorial) {
    payload.published_at = form.published_at ? new Date(form.published_at).toISOString() : null
    if (status) {
      payload.status = status
    }
  }

  try {
    let response

    if (isEdit.value) {
      response = await adminApi.articles.update(articleId.value, payload)
    } else {
      if (!auth.isEditorial) {
        payload.status = status === 'review' ? 'review' : 'draft'
      }

      response = await adminApi.articles.store(payload)
    }

    currentStatus.value = response.data.data.status
    ui.toast(t('admin.saved'))

    if (!isEdit.value) {
      router.push({ name: 'admin-article-edit', params: { id: response.data.data.id } })
    }
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    saving.value = false
  }
}

async function changeStatus(status, note = null) {
  if (statusBusy.value) {
    return
  }

  statusBusy.value = true

  try {
    const payload = { status }

    if (note !== null) {
      payload.review_note = note
    }

    if (status === 'scheduled' && form.published_at) {
      payload.published_at = new Date(form.published_at).toISOString()
    }

    const { data } = await adminApi.articles.changeStatus(articleId.value, payload)

    currentStatus.value = data.data.status
    form.status = data.data.status
    rejectMode.value = false

    ui.toast(
      status === 'published' ? t('admin.published')
        : status === 'scheduled' ? t('admin.scheduled')
          : status === 'draft' ? t('admin.reverted')
            : t('admin.saved'),
    )
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    statusBusy.value = false
  }
}

function onCoverSelect(media) {
  form.cover_media_id = media.id
  form.cover_url = media.url
}

function removeCover() {
  form.cover_media_id = null
  form.cover_url = null
}

const submitLabel = computed(() => {
  if (saving.value) {
    return t('common.saving')
  }

  return isEdit.value ? t('common.save') : t('common.create')
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center gap-3">
      <RouterLink
        :to="{ name: 'admin-articles' }"
        class="text-sm font-bold text-zinc-500 hover:text-brand-600 dark:text-zinc-400"
      >
        ← {{ t('admin.backToList') }}
      </RouterLink>

      <StatusBadge :status="currentStatus" />

      <span v-if="form.review_note" class="ml-2 truncate text-xs italic text-zinc-400">
        “{{ form.review_note }}”
      </span>
    </div>

    <AppSpinner v-if="loading" />

    <div v-else class="grid items-start gap-6 lg:grid-cols-3">
      <!-- Main column -->
      <div class="space-y-5 lg:col-span-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="title">{{ t('admin.titleField') }} *</label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            maxlength="250"
            class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-lg font-bold outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          />
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="excerpt">{{ t('admin.excerptField') }}</label>
          <textarea
            id="excerpt"
            v-model="form.excerpt"
            rows="2"
            maxlength="1000"
            class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-sm outline-none focus:border-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
          />
          <p class="mt-1 text-xs text-zinc-400">{{ t('admin.excerptHint') }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.bodyField') }} *</label>
          <RichTextEditor v-model="form.body" :placeholder="t('admin.bodyField') + '…'" />
        </div>

        <!-- SEO -->
        <details class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <summary class="cursor-pointer text-sm font-black uppercase tracking-wider text-zinc-500">
            {{ t('admin.seoSection') }}
          </summary>

          <div class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="meta_title">{{ t('admin.metaTitle') }}</label>
                <input
                  id="meta_title"
                  v-model="form.meta_title"
                  type="text"
                  maxlength="250"
                  class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="source_name">{{ t('admin.sourceName') }}</label>
                <input
                  id="source_name"
                  v-model="form.source_name"
                  type="text"
                  maxlength="200"
                  class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="meta_description">{{ t('admin.metaDescription') }}</label>
              <textarea
                id="meta_description"
                v-model="form.meta_description"
                rows="2"
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="source_url">{{ t('admin.sourceUrl') }}</label>
              <input
                id="source_url"
                v-model="form.source_url"
                type="url"
                placeholder="https://…"
                class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>
          </div>
        </details>
      </div>

      <!-- Sidebar column -->
      <div class="space-y-5">
        <!-- Publish box -->
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <!-- Workflow -->
          <div class="space-y-2">
            <button
              class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
              :disabled="saving || !form.title || !form.body"
              @click="save()"
            >
              {{ submitLabel }}
            </button>

            <template v-if="!isEdit">
              <button
                v-if="!auth.isEditorial"
                class="w-full rounded-xl border border-zinc-300 py-2.5 text-sm font-bold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                :disabled="saving || !form.title || !form.body"
                @click="save('review')"
              >
                {{ t('common.save') }} + {{ t('admin.submitReview') }}
              </button>
            </template>

            <template v-else>
              <!-- Author actions -->
              <button
                v-if="!auth.isEditorial && currentStatus === 'draft'"
                class="w-full rounded-xl border border-zinc-300 py-2.5 text-sm font-bold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                :disabled="statusBusy"
                @click="changeStatus('review')"
              >
                ⬆ {{ t('admin.submitReview') }}
              </button>

              <!-- Editor actions -->
              <button
                v-if="auth.isEditorial && currentStatus !== 'published'"
                class="w-full rounded-xl bg-green-600 py-2.5 text-sm font-bold text-white transition hover:bg-green-700 disabled:opacity-50"
                :disabled="statusBusy"
                @click="changeStatus('published')"
              >
                ⚡ {{ t('admin.publishNow') }}
              </button>

              <button
                v-if="auth.isEditorial && currentStatus === 'published'"
                class="w-full rounded-xl border border-zinc-300 py-2.5 text-sm font-bold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                :disabled="statusBusy"
                @click="changeStatus('draft')"
              >
                ↩ {{ t('admin.unpublish') }}
              </button>

              <button
                v-if="auth.isEditorial && currentStatus === 'published'"
                class="w-full rounded-xl border border-zinc-300 py-2.5 text-sm font-bold text-zinc-500 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800"
                :disabled="statusBusy"
                @click="changeStatus('archived')"
              >
                🗄 {{ t('admin.archive') }}
              </button>

              <button
                v-if="auth.isEditorial && currentStatus === 'review' && !rejectMode"
                class="w-full rounded-xl border border-red-300 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950/40"
                :disabled="statusBusy"
                @click="rejectMode = true"
              >
                ✕ {{ t('admin.reject') }}
              </button>

              <div v-if="rejectMode" class="rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/30">
                <textarea
                  v-model="form.review_note"
                  rows="2"
                  :placeholder="t('admin.rejectNotePlaceholder')"
                  class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm dark:border-red-700 dark:bg-zinc-900 dark:text-white"
                />
                <div class="mt-2 flex gap-2">
                  <button
                    class="flex-1 rounded-lg bg-red-600 py-2 text-xs font-bold text-white hover:bg-red-700"
                    :disabled="statusBusy || !form.review_note"
                    @click="changeStatus('draft', form.review_note)"
                  >
                    {{ t('admin.reject') }}
                  </button>
                  <button class="rounded-lg border border-zinc-300 px-3 text-xs font-bold dark:border-zinc-600 dark:text-zinc-300" @click="rejectMode = false">
                    {{ t('common.cancel') }}
                  </button>
                </div>
              </div>
            </template>
          </div>

          <p class="mt-3 text-center text-xs text-zinc-400">
            {{ t('admin.tableStatus') }}: <StatusBadge :status="currentStatus" />
          </p>
        </div>

        <!-- Cover -->
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <p class="mb-3 text-sm font-bold dark:text-zinc-200">{{ t('admin.coverField') }}</p>

          <div v-if="form.cover_url" class="relative overflow-hidden rounded-xl">
            <img :src="form.cover_url" alt="cover" class="w-full" />
            <button
              class="absolute right-2 top-2 rounded-lg bg-zinc-900/80 px-2.5 py-1 text-xs font-bold text-white backdrop-blur transition hover:bg-red-600"
              @click="removeCover"
            >
              {{ t('admin.removeImage') }}
            </button>
          </div>

          <button
            v-else
            class="w-full rounded-xl border-2 border-dashed border-zinc-300 py-8 text-sm font-semibold text-zinc-400 transition hover:border-brand-500 hover:text-brand-500 dark:border-zinc-700"
            @click="showMediaPicker = true"
          >
            🖼 {{ t('admin.selectCover') }}
          </button>

          <button
            v-if="form.cover_url"
            class="mt-3 w-full rounded-xl border border-zinc-300 py-2 text-xs font-bold text-zinc-600 dark:border-zinc-700 dark:text-zinc-300"
            @click="showMediaPicker = true"
          >
            {{ t('common.edit') }}
          </button>
        </div>

        <!-- Meta -->
        <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="category_id">{{ t('admin.categoryField') }}</label>
            <select
              id="category_id"
              v-model="form.category_id"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            >
              <option :value="null">—</option>
              <option v-for="category in categoryList" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="language">{{ t('admin.languageField') }}</label>
            <select
              id="language"
              v-model="form.language"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            >
              <option value="en">{{ t('common.english') }}</option>
              <option value="bn">{{ t('common.bangla') }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.tagsField') }}</label>
            <TagInput v-model="form.tags" />
          </div>

          <div v-if="auth.isEditorial">
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="published_at">{{ t('admin.publishedAtField') }}</label>
            <input
              id="published_at"
              v-model="form.published_at"
              type="datetime-local"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
            <p class="mt-1 text-xs text-zinc-400">{{ t('admin.publishedAtHint') }}</p>

            <label class="mt-3 flex items-center gap-2 text-sm font-semibold dark:text-zinc-200">
              <input v-model="form.is_featured" type="checkbox" class="h-4 w-4 accent-brand-600" />
              ★ {{ t('admin.featuredField') }}
            </label>

            <label class="mt-2 flex items-center gap-2 text-sm font-semibold dark:text-zinc-200">
              <input v-model="form.is_breaking" type="checkbox" class="h-4 w-4 accent-brand-600" />
              ⚡ {{ t('admin.breakingField') }}
            </label>
          </div>

          <div v-if="isEdit">
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200" for="slug">{{ t('admin.slugField') }}</label>
            <input
              id="slug"
              v-model="form.slug"
              type="text"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
            <p class="mt-1 text-xs text-zinc-400">{{ t('admin.slugHint') }}</p>
          </div>
        </div>
      </div>
    </div>

    <MediaPickerModal v-model="showMediaPicker" @select="onCoverSelect" />
  </div>
</template>
