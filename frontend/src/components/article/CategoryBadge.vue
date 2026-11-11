<script setup>
defineProps({
  category: { type: Object, default: null },
  small: { type: Boolean, default: false },
})
</script>

<template>
  <RouterLink
    v-if="category"
    :to="{ name: 'category', params: { slug: category.slug } }"
    class="inline-flex items-center font-bold uppercase tracking-wide transition hover:opacity-80"
    :class="small ? 'text-[11px]' : 'text-xs'"
    :style="{ color: category.color }"
  >
    <span class="mr-1.5 inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: category.color }" />
    {{ category.name }}
  </RouterLink>
</template>
