<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
import ArticleCard from '@/components/article/ArticleCard.vue'
import CategoryBadge from '@/components/article/CategoryBadge.vue'
import CommentSection from '@/components/article/CommentSection.vue'
import NewsletterBox from '@/components/home/NewsletterBox.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { articles, reactions, bookmarks } from '@/api'
import { apiError } from '@/api/client'
import { formatLongDate, formatDateTime, compactNumber } from '@/utils/format'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()

const loading = ref(true)
const notFound = ref(false)
const article = ref(null)
const related = ref([])
const progress = ref(0)
const copied = ref(false)

async function load(slug) {
  loading.value = true
  notFound.value = false

  try {
    const { data } = await articles.show(slug)
    article.value = data.data
    related.value = data.related.data
    document.title = `${article.value.title} - ${ui.setting('site_name', 'NewsPulse')}`
  } catch (error) {
    if (error.response?.status === 404) {
      notFound.value = true
    } else {
      ui.toast(apiError(error), 'error')
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => load(route.params.slug))
watch(() => route.params.slug, (slug) => slug && load(slug))

function onScroll() {
  if (!article.value) {
    return
  }

  const element = document.documentElement
  const total = element.scrollHeight - element.clientHeight

  progress.value = total > 0 ? Math.min(100, (element.scrollTop / total) * 100) : 0
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

const shareUrl = computed(() => window.location.href)

const socials = computed(() => [
  {
    label: 'Facebook',
    href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`,
    color: '#1877f2',
  },
  {
    label: 'X',
    href: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(article.value?.title ?? '')}`,
    color: '#0f172a',
  },
  {
    label: 'WhatsApp',
    href: `https://wa.me/?text=${encodeURIComponent((article.value?.title ?? '') + ' ' + shareUrl.value)}`,
    color: '#25d366',
  },
  {
    label: 'LinkedIn',
    href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}`,
    color: '#0a66c2',
  },
])

async function toggleLike() {
  if (!auth.isAuthenticated) {
    ui.toast(t('comments.signInToComment'), 'info')

    return
  }

  try {
    const { data } = await reactions.toggle('article', article.value.id)
    article.value.liked = data.liked
    article.value.likes_count = data.likes_count
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

async function toggleBookmark() {
  if (!auth.isAuthenticated) {
    ui.toast(t('comments.signInToComment'), 'info')

    return
  }

  try {
    const { data } = await bookmarks.toggle(article.value.id)
    article.value.bookmarked = data.bookmarked
    ui.toast(data.message)
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    ui.toast('Link copied to clipboard.')
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    ui.toast('Could not copy the link.', 'error')
  }
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Reading progress -->
    <div class="fixed left-0 top-0 z-50 h-1 w-full bg-transparent">
      <div class="h-full bg-brand-600 transition-all duration-150" :style="{ width: progress + '%' }" />
    </div>

    <PublicHeader />

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
      <AppSpinner v-if="loading" />

      <div v-else-if="notFound" class="py-24 text-center">
        <p class="text-6xl font-black text-zinc-200 dark:text-zinc-700">404</p>
        <p class="mt-4 text-lg font-bold dark:text-white">{{ t('article.noArticles') }}</p>
        <RouterLink :to="{ name: 'home' }" class="mt-4 inline-block font-bold text-brand-600 hover:underline">
          ← {{ t('errors.backHome') }}
        </RouterLink>
      </div>

      <article v-else-if="article">
        <!-- Headline block -->
        <div class="mb-6">
          <div class="flex flex-wrap items-center gap-3">
            <CategoryBadge :category="article.category" />
            <span
              v-if="article.is_breaking"
              class="inline-flex items-center rounded-full bg-brand-600 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white"
            >
              {{ t('common.breaking') }}
            </span>
          </div>

          <h1 class="mt-4 font-serif text-3xl font-black leading-tight tracking-tight dark:text-white sm:text-4xl lg:text-5xl">
            {{ article.title }}
          </h1>

          <p v-if="article.excerpt" class="mt-4 text-lg leading-relaxed text-zinc-500 dark:text-zinc-400">
            {{ article.excerpt }}
          </p>

          <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 border-y border-zinc-200 py-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            <div class="flex items-center gap-2">
              <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                {{ (article.author?.name ?? '?').charAt(0).toUpperCase() }}
              </span>
              <div>
                <RouterLink
                  v-if="article.author"
                  :to="{ name: 'author', params: { id: article.author.id } }"
                  class="block font-bold text-zinc-800 hover:text-brand-600 dark:text-zinc-200"
                >
                  {{ article.author.name }}
                </RouterLink>
                <span class="text-xs">{{ article.author?.role_label }}</span>
              </div>
            </div>

            <span>{{ formatLongDate(article.published_at) }}</span>
            <span>{{ t('article.readingTime', { minutes: article.reading_time }) }}</span>
            <span>{{ compactNumber(article.views_count) }} {{ t('common.views') }}</span>
          </div>
        </div>

        <!-- Cover -->
        <figure v-if="article.cover_url" class="mb-8">
          <img :src="article.cover_url" :alt="article.title" class="w-full rounded-2xl" />
          <figcaption v-if="article.gallery?.[0]?.caption" class="mt-2 text-center text-sm text-zinc-500">
            {{ article.gallery[0].caption }}
          </figcaption>
        </figure>

        <!-- Body -->
        <div class="prose-article" v-html="article.body" />

        <!-- Source -->
        <p v-if="article.source_url || article.source_name" class="mt-8 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
          {{ t('article.sources') }}:
          <a v-if="article.source_url" :href="article.source_url" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
            {{ article.source_name ?? article.source_url }}
          </a>
          <span v-else class="font-semibold">{{ article.source_name }}</span>
        </p>

        <!-- Tags -->
        <div v-if="article.tags?.length" class="mt-8 flex flex-wrap items-center gap-2">
          <span class="text-sm font-bold text-zinc-400">#</span>
          <RouterLink
            v-for="tag in article.tags"
            :key="tag.id"
            :to="{ name: 'tag', params: { slug: tag.slug } }"
            class="rounded-full border border-zinc-200 px-3 py-1 text-sm font-semibold text-zinc-600 transition hover:border-brand-500 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300"
          >
            {{ tag.name }}
          </RouterLink>
        </div>

        <!-- Engagement bar -->
        <div class="sticky bottom-4 z-30 mt-10">
          <div class="mx-auto flex max-w-xl items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white/95 p-2 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
            <button
              class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition"
              :class="article.liked
                ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
              @click="toggleLike"
            >
              <svg class="h-5 w-5" :fill="article.liked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
              </svg>
              {{ compactNumber(article.likes_count) }}
            </button>

            <button
              class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition"
              :class="article.bookmarked
                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
              @click="toggleBookmark"
            >
              <svg class="h-5 w-5" :fill="article.bookmarked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              {{ article.bookmarked ? t('common.bookmarked') : t('common.bookmark') }}
            </button>

            <a
              v-for="social in socials"
              :key="social.label"
              :href="social.href"
              target="_blank"
              rel="noopener noreferrer"
              class="grid h-10 w-10 place-items-center rounded-xl text-sm font-black text-white transition hover:opacity-85"
              :style="{ backgroundColor: social.color }"
              :title="`${t('common.share')} ${social.label}`"
            >
              {{ social.label.charAt(0) }}
            </a>

            <button
              class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
              @click="copyLink"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m9.172-3l1.5-1.5a4 4 0 00-5.656-5.656l-3 3a4 4 0 000 5.656" />
              </svg>
              {{ copied ? '✓' : t('common.share') }}
            </button>
          </div>
        </div>

        <!-- Comments -->
        <CommentSection :article-id="article.id" />
      </article>
    </main>

    <!-- Related -->
    <section v-if="related.length > 0" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
      <h2 class="mb-6 border-b-2 border-zinc-900 pb-2 text-lg font-black uppercase tracking-tight dark:border-zinc-100 dark:text-white">
        {{ t('common.related') }}
      </h2>

      <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <ArticleCard v-for="item in related" :key="item.id" :article="item" />
      </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
      <NewsletterBox />
    </div>

    <PublicFooter />
  </div>
</template>
