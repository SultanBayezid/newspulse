<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { categories } from '@/api'
import { formatRelative } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()
const notifications = useNotificationStore()

const mobileOpen = ref(false)
const searchQuery = ref('')
const categoryList = ref([])
const showNotifications = ref(false)
const notificationPanel = ref(null)

const isAdminRoute = computed(() => route.path.startsWith('/admin'))

async function loadCategories() {
  try {
    const { data } = await categories.list()
    categoryList.value = data.data
  } catch {
    // The nav falls back to a static link.
  }
}

function submitSearch() {
  const query = searchQuery.value.trim()

  if (query.length === 0) {
    return
  }

  mobileOpen.value = false
  router.push({ name: 'search', query: { q: query } })
  searchQuery.value = ''
}

async function handleLogout() {
  await auth.logout()
  ui.toast(t('auth.signedOut'))
  router.push({ name: 'home' })
}

function toggleLocale() {
  ui.setLocale(locale.value === 'en' ? 'bn' : 'en')
}

function onNotificationClick() {
  showNotifications.value = !showNotifications.value

  if (showNotifications.value) {
    notifications.load()
  }
}

function notificationText(item) {
  const data = item.data ?? {}

  switch (item.type) {
    case 'article_status_changed':
      if (data.action === 'approved') {
        return `Your article "${data.article_title}" has been approved and published.`
      }

      if (data.action === 'rejected') {
        return `Your article "${data.article_title}" was sent back${data.note ? `: ${data.note}` : '.'}`
      }

      if (data.action === 'scheduled') {
        return `Your article "${data.article_title}" has been scheduled.`
      }

      return `Your article "${data.article_title}" was submitted for review.`
    case 'comment_submitted':
      return `A new comment is ${data.status === 'pending' ? 'awaiting moderation' : 'live'} on "${data.article_title}".`
    default:
      return item.data?.message ?? 'New notification'
  }
}

function onDocumentClick(event) {
  if (notificationPanel.value && !notificationPanel.value.contains(event.target)) {
    showNotifications.value = false
  }
}

onMounted(() => {
  loadCategories()
  document.addEventListener('click', onDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<template>
  <header
    v-if="!isAdminRoute"
    class="sticky top-0 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <div class="flex h-16 items-center justify-between gap-4">
        <!-- Logo -->
        <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 font-black text-white">NP</span>
          <span class="text-xl font-black tracking-tight dark:text-white">
            {{ ui.setting('site_name', 'NewsPulse') }}
          </span>
        </RouterLink>

        <!-- Desktop nav -->
        <nav class="hidden items-center gap-1 lg:flex">
          <RouterLink
            v-for="category in categoryList.slice(0, 6)"
            :key="category.id"
            :to="{ name: 'category', params: { slug: category.slug } }"
            class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
            active-class="!bg-brand-50 !text-brand-700 dark:!bg-brand-950 dark:!text-brand-300"
          >
            {{ category.name }}
          </RouterLink>
        </nav>

        <div class="flex items-center gap-2">
          <!-- Search -->
          <form class="hidden items-center md:flex" @submit.prevent="submitSearch">
            <input
              v-model="searchQuery"
              type="search"
              :placeholder="t('common.searchPlaceholder')"
              class="w-44 rounded-l-lg border border-r-0 border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white xl:w-56"
            />
            <button
              type="submit"
              class="rounded-r-lg border border-brand-600 bg-brand-600 px-3 py-2 text-white transition hover:bg-brand-700"
              :aria-label="t('common.search')"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
              </svg>
            </button>
          </form>

          <!-- Language -->
          <button
            class="hidden rounded-lg border border-zinc-300 px-2.5 py-2 text-xs font-bold text-zinc-600 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 sm:block"
            :title="t('common.language')"
            @click="toggleLocale"
          >
            {{ locale === 'en' ? 'EN' : 'বাং' }}
          </button>

          <!-- Theme -->
          <button
            class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
            :title="ui.theme === 'dark' ? t('common.lightMode') : t('common.darkMode')"
            @click="ui.toggleTheme()"
          >
            <svg v-if="ui.theme === 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Notifications (signed in) -->
          <div v-if="auth.isAuthenticated" class="relative" ref="notificationPanel">
            <button
              class="relative rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
              :title="t('nav.notifications')"
              @click="onNotificationClick"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1h6z" />
              </svg>
              <span
                v-if="notifications.unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white"
              >
                {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
              </span>
            </button>

            <div
              v-if="showNotifications"
              class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
            >
              <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                <p class="text-sm font-bold dark:text-white">{{ t('nav.notifications') }}</p>
                <button
                  class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                  @click="notifications.markAllRead()"
                >
                  {{ t('account.markAllRead') }}
                </button>
              </div>

              <div class="max-h-80 overflow-y-auto">
                <p v-if="notifications.items.length === 0" class="px-4 py-6 text-center text-sm text-zinc-500">
                  {{ t('account.noNotifications') }}
                </p>

                <RouterLink
                  v-for="item in notifications.items"
                  :key="item.id"
                  :to="item.data?.article_slug ? { name: 'article', params: { slug: item.data.article_slug } } : { name: 'notifications' }"
                  class="block border-b border-zinc-50 px-4 py-3 transition hover:bg-zinc-50 dark:border-zinc-800/50 dark:hover:bg-zinc-800/50"
                  :class="{ 'bg-brand-50/50 dark:bg-brand-950/20': !item.read_at }"
                  @click="showNotifications = false; notifications.markRead(item.id)"
                >
                  <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ notificationText(item) }}</p>
                  <p class="mt-1 text-xs text-zinc-400">{{ formatRelative(item.created_at) }}</p>
                </RouterLink>
              </div>

              <RouterLink
                :to="{ name: 'notifications' }"
                class="block border-t border-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-brand-600 hover:underline dark:border-zinc-800 dark:text-brand-400"
                @click="showNotifications = false"
              >
                {{ t('nav.notifications') }} →
              </RouterLink>
            </div>
          </div>

          <!-- Auth links -->
          <template v-if="!auth.isAuthenticated">
            <RouterLink
              :to="{ name: 'login' }"
              class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 sm:block"
            >
              {{ t('nav.login') }}
            </RouterLink>
            <RouterLink
              :to="{ name: 'register' }"
              class="hidden rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 sm:block"
            >
              {{ t('nav.register') }}
            </RouterLink>
          </template>

          <template v-else>
            <RouterLink
              v-if="auth.isStaff"
              :to="{ name: 'admin-dashboard' }"
              class="hidden rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 sm:block"
            >
              {{ t('nav.adminPanel') }}
            </RouterLink>
            <RouterLink
              :to="{ name: 'profile' }"
              class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-200"
              :title="auth.user?.name"
            >
              {{ (auth.user?.name ?? '?').charAt(0).toUpperCase() }}
            </RouterLink>
            <button
              class="rounded-lg px-2 py-2 text-sm font-semibold text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
              :title="t('nav.logout')"
              @click="handleLogout"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </template>

          <!-- Mobile toggle -->
          <button
            class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden"
            :aria-label="t('nav.menu')"
            @click="mobileOpen = !mobileOpen"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileOpen" class="border-t border-zinc-200 bg-white px-4 pb-4 pt-2 dark:border-zinc-800 dark:bg-zinc-950 lg:hidden">
      <form class="mb-3 flex" @submit.prevent="submitSearch">
        <input
          v-model="searchQuery"
          type="search"
          :placeholder="t('common.searchPlaceholder')"
          class="flex-1 rounded-l-lg border border-r-0 border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
        <button type="submit" class="rounded-r-lg bg-brand-600 px-4 text-sm font-semibold text-white">
          {{ t('common.search') }}
        </button>
      </form>

      <nav class="grid gap-1">
        <RouterLink
          v-for="category in categoryList"
          :key="category.id"
          :to="{ name: 'category', params: { slug: category.slug } }"
          class="rounded-lg px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
          @click="mobileOpen = false"
        >
          {{ category.name }}
        </RouterLink>

        <div class="mt-2 flex gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
          <button
            class="flex-1 rounded-lg border border-zinc-300 py-2 text-sm font-semibold dark:border-zinc-700"
            @click="toggleLocale"
          >
            {{ locale === 'en' ? 'বাংলা' : 'English' }}
          </button>
          <RouterLink
            v-if="auth.isAuthenticated && auth.isStaff"
            :to="{ name: 'admin-dashboard' }"
            class="flex-1 rounded-lg bg-zinc-900 py-2 text-center text-sm font-semibold text-white dark:bg-white dark:text-zinc-900"
            @click="mobileOpen = false"
          >
            {{ t('nav.adminPanel') }}
          </RouterLink>
        </div>
      </nav>
    </div>
  </header>
</template>
