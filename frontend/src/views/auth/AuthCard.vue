<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="grid min-h-screen lg:grid-cols-2">
    <!-- Brand panel -->
    <div class="relative hidden overflow-hidden bg-zinc-900 lg:block">
      <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 30% 20%, #dc2626 0, transparent 45%), radial-gradient(circle at 70% 80%, #f59e0b 0, transparent 40%)" />

      <div class="relative flex h-full flex-col justify-between p-12">
        <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2">
          <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 font-black text-white">NP</span>
          <span class="text-2xl font-black tracking-tight text-white">NewsPulse</span>
        </RouterLink>

        <div>
          <h2 class="max-w-md font-serif text-4xl font-black leading-tight text-white">
            Fast, fair and fearless journalism.
          </h2>
          <p class="mt-4 max-w-md text-zinc-300">
            An open-source newsroom platform with a real editorial workflow, moderation tools and reader analytics.
          </p>
        </div>

        <p class="text-xs text-zinc-500">© {{ new Date().getFullYear() }} NewsPulse - MIT License</p>
      </div>
    </div>

    <!-- Form panel -->
    <div class="flex items-center justify-center px-4 py-12 sm:px-6">
      <div class="w-full max-w-md">
        <RouterLink :to="{ name: 'home' }" class="mb-8 flex items-center gap-2 lg:hidden">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 font-black text-white">NP</span>
          <span class="text-xl font-black tracking-tight dark:text-white">NewsPulse</span>
        </RouterLink>

        <h1 class="text-2xl font-black tracking-tight dark:text-white">{{ title }}</h1>
        <p v-if="subtitle" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ subtitle }}</p>

        <div class="mt-8">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
