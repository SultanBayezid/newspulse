<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CategoryBadge from './CategoryBadge.vue'
import { formatRelative, compactNumber } from '@/utils/format'

const props = defineProps({
  article: { type: Object, required: true },
  variant: { type: String, default: 'default' }, // default | hero | compact | list
})

const { t } = useI18n()

const placeholderGradient = computed(() => {
  const colors = ['#dc2626', '#f59e0b', '#0ea5e9', '#8b5cf6', '#10b981', '#ec4899']
  const index = (props.article.id ?? 0) % colors.length

  return `linear-gradient(135deg, ${colors[index]}, ${colors[(index + 1) % colors.length]})`
})

const initials = computed(() =>
  (props.article.title ?? '')
    .split(' ')
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join(''),
)
</script>

<template>
  <!-- HERO variant -->
  <article v-if="variant === 'hero'" class="group">
    <RouterLink
      :to="{ name: 'article', params: { slug: article.slug } }"
      class="relative block aspect-[16/9] overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800 md:aspect-[2/1]"
    >
      <img
        v-if="article.cover_url"
        :src="article.cover_url"
        :alt="article.title"
        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        loading="lazy"
      />
      <div
        v-else
        class="grid h-full w-full place-items-center text-5xl font-black text-white/80 transition duration-500 group-hover:scale-105"
        :style="{ background: placeholderGradient }"
      >
        {{ initials }}
      </div>
    </RouterLink>

    <div class="mt-5">
      <div class="flex items-center gap-3">
        <CategoryBadge :category="article.category" />
        <span
          v-if="article.is_breaking"
          class="inline-flex items-center rounded-full bg-brand-600 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white"
        >
          {{ t('common.breaking') }}
        </span>
      </div>

      <RouterLink :to="{ name: 'article', params: { slug: article.slug } }">
        <h2 class="mt-3 text-2xl font-black leading-tight tracking-tight transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-400 sm:text-3xl lg:text-4xl">
          {{ article.title }}
        </h2>
      </RouterLink>

      <p class="mt-3 line-clamp-3 max-w-3xl text-zinc-600 dark:text-zinc-300">{{ article.excerpt }}</p>

      <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
        <span v-if="article.author">{{ t('common.by') }} <span class="font-semibold">{{ article.author.name }}</span></span>
        <span>{{ formatRelative(article.published_at) }}</span>
        <span>{{ t('article.readingTime', { minutes: article.reading_time }) }}</span>
      </div>
    </div>
  </article>

  <!-- LIST variant -->
  <article v-else-if="variant === 'list'" class="group flex gap-5 py-5">
    <RouterLink
      :to="{ name: 'article', params: { slug: article.slug } }"
      class="hidden h-28 w-44 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800 sm:block"
    >
      <img
        v-if="article.cover_url"
        :src="article.cover_url"
        :alt="article.title"
        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        loading="lazy"
      />
      <div
        v-else
        class="grid h-full w-full place-items-center text-2xl font-black text-white/80"
        :style="{ background: placeholderGradient }"
      >
        {{ initials }}
      </div>
    </RouterLink>

    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-3">
        <CategoryBadge :category="article.category" small />
        <span
          v-if="article.is_breaking"
          class="inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white"
        >
          {{ t('common.breaking') }}
        </span>
      </div>

      <RouterLink :to="{ name: 'article', params: { slug: article.slug } }">
        <h3 class="mt-1.5 text-lg font-bold leading-snug tracking-tight transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-400">
          {{ article.title }}
        </h3>
      </RouterLink>

      <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ article.excerpt }}</p>

      <div class="mt-2 flex flex-wrap items-center gap-x-3 text-xs text-zinc-500 dark:text-zinc-400">
        <span v-if="article.author">{{ t('common.by') }} {{ article.author.name }}</span>
        <span>{{ formatRelative(article.published_at) }}</span>
        <span>{{ compactNumber(article.views_count) }} {{ t('common.views') }}</span>
        <span v-if="article.comments_count > 0">{{ article.comments_count }} {{ t('common.comments').toLowerCase() }}</span>
      </div>
    </div>
  </article>

  <!-- COMPACT variant (sidebar) -->
  <article v-else-if="variant === 'compact'" class="group flex items-start gap-3">
    <span class="mt-1 w-6 shrink-0 text-lg font-black text-zinc-300 dark:text-zinc-600">
      {{ String(article.__index ?? '').padStart(2, '0') }}
    </span>
    <div class="min-w-0">
      <RouterLink :to="{ name: 'article', params: { slug: article.slug } }">
        <h4 class="line-clamp-3 text-sm font-bold leading-snug transition group-hover:text-brand-700 dark:text-zinc-100 dark:group-hover:text-brand-400">
          {{ article.title }}
        </h4>
      </RouterLink>
      <p class="mt-1 text-xs text-zinc-500">{{ compactNumber(article.views_count) }} {{ t('common.views') }}</p>
    </div>
  </article>

  <!-- DEFAULT variant (grid card) -->
  <article v-else class="group flex flex-col">
    <RouterLink
      :to="{ name: 'article', params: { slug: article.slug } }"
      class="relative block aspect-[16/10] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800"
    >
      <img
        v-if="article.cover_url"
        :src="article.cover_url"
        :alt="article.title"
        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        loading="lazy"
      />
      <div
        v-else
        class="grid h-full w-full place-items-center text-4xl font-black text-white/80 transition duration-500 group-hover:scale-105"
        :style="{ background: placeholderGradient }"
      >
        {{ initials }}
      </div>
    </RouterLink>

    <div class="mt-4 flex flex-1 flex-col">
      <CategoryBadge :category="article.category" small />

      <RouterLink :to="{ name: 'article', params: { slug: article.slug } }">
        <h3 class="mt-2 text-lg font-bold leading-snug tracking-tight transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-400">
          {{ article.title }}
        </h3>
      </RouterLink>

      <p class="mt-2 line-clamp-2 flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ article.excerpt }}</p>

      <div class="mt-3 flex flex-wrap items-center gap-x-3 text-xs text-zinc-500 dark:text-zinc-400">
        <span v-if="article.author">{{ t('common.by') }} {{ article.author.name }}</span>
        <span>{{ formatRelative(article.published_at) }}</span>
        <span>{{ t('article.readingTime', { minutes: article.reading_time }) }}</span>
      </div>
    </div>
  </article>
</template>
