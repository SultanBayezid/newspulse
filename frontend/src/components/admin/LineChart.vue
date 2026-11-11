<script setup>
import { computed } from 'vue'

const props = defineProps({
  points: { type: Array, required: true }, // [{date, count}]
  color: { type: String, default: '#dc2626' },
  height: { type: Number, default: 180 },
})

const width = 560
const padding = { top: 10, right: 8, bottom: 22, left: 34 }

const max = computed(() => Math.max(1, ...props.points.map((point) => point.count)))

const plotWidth = width - padding.left - padding.right
const plotHeight = props.height - padding.top - padding.bottom

const path = computed(() => {
  if (props.points.length === 0) {
    return ''
  }

  const step = props.points.length > 1 ? plotWidth / (props.points.length - 1) : 0

  return props.points
    .map((point, index) => {
      const x = padding.left + index * step
      const y = padding.top + plotHeight - (point.count / max.value) * plotHeight

      return `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`
    })
    .join(' ')
})

const area = computed(() => {
  if (!path.value) {
    return ''
  }

  const baseY = padding.top + plotHeight

  return `${path.value} L${(padding.left + plotWidth).toFixed(1)},${baseY} L${padding.left},${baseY} Z`
})

const yTicks = computed(() => {
  const ticks = []

  for (let i = 0; i <= 4; i++) {
    const value = Math.round((max.value / 4) * i)
    const y = padding.top + plotHeight - (value / max.value) * plotHeight

    ticks.push({ value, y })
  }

  return ticks
})

const xTicks = computed(() => {
  if (props.points.length === 0) {
    return []
  }

  const indexes = [0, Math.floor(props.points.length / 2), props.points.length - 1]

  return [...new Set(indexes)].map((index) => ({
    label: props.points[index].date.slice(5),
    x: padding.left + (props.points.length > 1 ? (plotWidth / (props.points.length - 1)) * index : 0),
  }))
})
</script>

<template>
  <svg :viewBox="`0 0 ${width} ${height}`" class="w-full" role="img">
    <!-- Grid -->
    <g v-for="tick in yTicks" :key="tick.value">
      <line
        :x1="padding.left"
        :x2="width - padding.right"
        :y1="tick.y"
        :y2="tick.y"
        class="stroke-zinc-200 dark:stroke-zinc-800"
        stroke-dasharray="3 3"
        stroke-width="1"
      />
      <text
        :x="padding.left - 6"
        :y="tick.y + 3"
        text-anchor="end"
        class="fill-zinc-400 text-[9px]"
      >
        {{ tick.value }}
      </text>
    </g>

    <!-- Area + line -->
    <path :d="area" :fill="color" opacity="0.1" />
    <path :d="path" :stroke="color" fill="none" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

    <!-- X labels -->
    <text
      v-for="tick in xTicks"
      :key="tick.x + tick.label"
      :x="tick.x"
      :y="height - 6"
      text-anchor="middle"
      class="fill-zinc-400 text-[9px]"
    >
      {{ tick.label }}
    </text>
  </svg>
</template>
