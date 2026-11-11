<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/stores/ui'
import { pages } from '@/api'

const { t } = useI18n()
const ui = useUiStore()
const pageLinks = ref([])

onMounted(async () => {
  try {
    const { data } = await pages.list()
    pageLinks.value = data.data
  } catch {
    // Footer works without page links.
  }
})

const socials = [
  { key: 'social_facebook', label: 'Facebook', href: () => ui.setting('social_facebook') },
  { key: 'social_twitter', label: 'X', href: () => ui.setting('social_twitter') },
  { key: 'social_youtube', label: 'YouTube', href: () => ui.setting('social_youtube') },
  { key: 'social_instagram', label: 'Instagram', href: () => ui.setting('social_instagram') },
  { key: 'social_linkedin', label: 'LinkedIn', href: () => ui.setting('social_linkedin') },
]
</script>

<template>
  <footer class="mt-16 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
      <div class="grid gap-10 md:grid-cols-4">
        <div class="md:col-span-2">
          <div class="flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 font-black text-white">NP</span>
            <span class="text-xl font-black tracking-tight dark:text-white">
              {{ ui.setting('site_name', 'NewsPulse') }}
            </span>
          </div>

          <p class="mt-4 max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-400">
            {{ ui.setting('site_description', 'An open-source news publishing platform.') }}
          </p>

          <div class="mt-6 flex flex-wrap gap-2">
            <a
              v-for="social in socials"
              v-show="social.href()"
              :key="social.key"
              :href="social.href()"
              target="_blank"
              rel="noopener noreferrer"
              class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-600 transition hover:border-brand-500 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300"
            >
              {{ social.label }}
            </a>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
            {{ t('footer.about') }}
          </h3>
          <ul class="mt-4 space-y-2 text-sm">
            <li v-for="page in pageLinks" :key="page.id">
              <RouterLink
                :to="{ name: 'page', params: { slug: page.slug } }"
                class="text-zinc-600 transition hover:text-brand-600 dark:text-zinc-300 dark:hover:text-brand-400"
              >
                {{ page.title }}
              </RouterLink>
            </li>
            <li>
              <a
                href="/feed"
                class="text-zinc-600 transition hover:text-brand-600 dark:text-zinc-300 dark:hover:text-brand-400"
              >
                RSS feed
              </a>
            </li>
          </ul>
        </div>

        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
            {{ t('footer.rights') }}
          </h3>
          <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
            {{ ui.setting('footer_text', '') }}
          </p>
          <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-500">
            © {{ new Date().getFullYear() }}
            {{ ui.setting('site_name', 'NewsPulse') }}.
            {{ t('footer.allRightsReserved') }}
          </p>
        </div>
      </div>

      <p class="mt-10 border-t border-zinc-200 pt-6 text-center text-xs text-zinc-400 dark:border-zinc-800">
        {{ t('footer.builtWith') }}
      </p>
    </div>
  </footer>
</template>
