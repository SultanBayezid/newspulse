<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const sidebarOpen = ref(false)

const navigation = computed(() => {
  const items = [
    { name: 'admin-dashboard', label: t('admin.dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'admin-articles', label: t('admin.articles'), icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 01-2 2h-2m0-13v13' },
    { name: 'admin-media', label: t('admin.media'), icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'admin-categories', label: t('admin.categories'), icon: 'M7 7h.01M7 3v4m0 0v10m0-6h10a2 2 0 002-2V5a2 2 0 00-2-2H7m0 12a2 2 0 01-2-2V5a2 2 0 012-2' },
    { name: 'admin-tags', label: t('admin.tags'), icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V5a2 2 0 012-2h2z' },
    { name: 'admin-comments', label: t('admin.comments'), icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
    { name: 'admin-pages', label: t('admin.pages'), icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'admin-polls', label: t('admin.polls'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
  ]

  if (auth.isEditorial) {
    items.push(
      { name: 'admin-subscribers', label: t('admin.subscribers'), icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'admin-activity', label: t('admin.activity'), icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    )
  }

  if (auth.isAdmin) {
    items.push(
      { name: 'admin-users', label: t('admin.users'), icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
      { name: 'admin-settings', label: t('admin.settings'), icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    )
  }

  return items
})

watch(() => route.fullPath, () => {
  sidebarOpen.value = false
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'home' })
}

const pageTitle = computed(() => {
  const item = navigation.value.find((entry) => entry.name === route.name)

  return item?.label ?? t('admin.dashboard')
})
</script>

<template>
  <div class="min-h-screen bg-zinc-100 dark:bg-zinc-950">
    <!-- Top bar -->
    <header class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-zinc-200 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900 sm:px-6">
      <div class="flex items-center gap-4">
        <button
          class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 lg:hidden"
          @click="sidebarOpen = !sidebarOpen"
        >
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 font-black text-white">NP</span>
          <span class="hidden text-lg font-black tracking-tight dark:text-white sm:block">
            {{ ui.setting('site_name', 'NewsPulse') }}
            <span class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800">
              admin
            </span>
          </span>
        </RouterLink>
      </div>

      <div class="flex items-center gap-2">
        <span class="hidden rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 sm:block">
          {{ auth.user?.name }} · {{ auth.user?.role_label }}
        </span>

        <RouterLink
          :to="{ name: 'home' }"
          class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
          :title="t('admin.viewSite')"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
        </RouterLink>

        <button
          class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
          @click="handleLogout"
          :title="t('nav.logout')"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>
    </header>

    <div class="flex">
      <!-- Sidebar -->
      <aside
        class="fixed inset-y-16 left-0 z-30 w-64 transform overflow-y-auto border-r border-zinc-200 bg-white transition-transform dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      >
        <nav class="space-y-1 p-3">
          <RouterLink
            v-for="item in navigation"
            :key="item.name"
            :to="{ name: item.name }"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
            :class="route.name === item.name
              ? 'bg-brand-600 text-white'
              : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
          >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
            </svg>
            {{ item.label }}
          </RouterLink>
        </nav>

        <div class="mx-3 mt-6 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white">
          <p class="text-xs font-bold uppercase tracking-wider text-brand-200">NewsPulse</p>
          <p class="mt-1 text-sm font-semibold leading-5">Open-source publishing platform v1.0</p>
          <a
            href="https://github.com/SultanBayezid/newspulse"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-3 inline-block rounded-lg bg-white/20 px-3 py-1.5 text-xs font-bold backdrop-blur transition hover:bg-white/30"
          >
            GitHub ↗
          </a>
        </div>
      </aside>

      <!-- Mobile backdrop -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-20 bg-zinc-900/50 lg:hidden"
        @click="sidebarOpen = false"
      />

      <!-- Content -->
      <main class="min-w-0 flex-1 lg:ml-64">
        <div class="p-4 sm:p-8">
          <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-black tracking-tight dark:text-white">{{ pageTitle }}</h1>

            <slot name="actions" />
          </div>

          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>
