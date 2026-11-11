<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'
import { formatRelative } from '@/utils/format'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  selectMode: { type: Boolean, default: true }, // pick existing media
})

const emit = defineEmits(['update:modelValue', 'select'])

const { t } = useI18n()
const ui = useUiStore()

const list = ref([])
const loading = ref(true)
const uploading = ref(false)
const search = ref('')
const page = ref(1)
const meta = ref(null)

function close() {
  emit('update:modelValue', false)
}

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.media.list({
      page: page.value,
      q: search.value || undefined,
      per_page: 24,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function onUpload(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  uploading.value = true

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await adminApi.media.store(formData)
    ui.toast('Image uploaded.')

    if (props.selectMode) {
      emit('select', data.data)
      close()

      return
    }

    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

async function remove(media) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.media.destroy(media.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

function changePage(next) {
  page.value = next
  load()
}
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="close" />

      <div class="relative mx-auto mt-10 w-full max-w-5xl px-4 pb-10">
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
          <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h3 class="text-lg font-black dark:text-white">{{ t('admin.mediaLibrary') }}</h3>

            <div class="flex items-center gap-2">
              <label
                class="cursor-pointer rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
                :class="{ 'opacity-50': uploading }"
              >
                {{ uploading ? t('admin.uploading') : '⬆ ' + t('admin.uploadImage') }}
                <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onUpload" />
              </label>

              <button class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800" @click="close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
            <input
              v-model="search"
              type="search"
              :placeholder="t('admin.searchPlaceholder')"
              class="w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
              @keyup.enter="page = 1; load()"
            />
          </div>

          <div v-if="loading" class="py-16 text-center text-sm text-zinc-400">{{ t('common.loading') }}</div>

          <div v-else-if="list.length === 0" class="py-16 text-center text-sm text-zinc-400">
            {{ t('admin.noMedia') }}
          </div>

          <div v-else class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 lg:grid-cols-4">
            <div
              v-for="media in list"
              :key="media.id"
              class="group overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"
            >
              <button
                class="block aspect-video w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800"
                @click="emit('select', media); close()"
              >
                <img :src="media.url" :alt="media.alt ?? media.file_name" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
              </button>

              <div class="p-2.5">
                <p class="truncate text-xs font-semibold dark:text-zinc-200" :title="media.file_name">
                  {{ media.file_name }}
                </p>
                <div class="mt-1 flex items-center justify-between">
                  <span class="text-[10px] text-zinc-400">{{ media.size_human }} · {{ formatRelative(media.created_at) }}</span>

                  <button
                    class="text-[10px] font-bold text-red-500 hover:underline"
                    @click="remove(media)"
                  >
                    {{ t('common.delete') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="meta?.last_page > 1" class="flex items-center justify-center gap-2 border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">
            <button
              v-for="p in meta.last_page"
              :key="p"
              class="h-8 w-8 rounded-lg text-sm font-bold"
              :class="p === meta.current_page
                ? 'bg-brand-600 text-white'
                : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
              @click="changePage(p)"
            >
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
