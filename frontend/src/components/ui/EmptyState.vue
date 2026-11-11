<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="grid place-items-center rounded-2xl border border-dashed border-zinc-300 py-16 text-center dark:border-zinc-700">
    <svg class="mb-3 h-10 w-10 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
    <p class="font-semibold text-zinc-500 dark:text-zinc-400">{{ title }}</p>
    <p v-if="subtitle" class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ subtitle }}</p>
    <slot />
  </div>
</template>
