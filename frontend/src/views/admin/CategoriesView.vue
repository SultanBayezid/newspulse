<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/api/client'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const loading = ref(true)
const list = ref([])
const showModal = ref(false)
const editing = ref(null)
const busy = ref(false)

const form = reactive({
  name: '',
  slug: '',
  description: '',
  color: '#2563eb',
  parent_id: null,
  position: 0,
  is_active: true,
})

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.categories.list()
    list.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editing.value = null
  Object.assign(form, {
    name: '', slug: '', description: '', color: '#2563eb', parent_id: null, position: 0, is_active: true,
  })
  showModal.value = true
}

function openEdit(category) {
  editing.value = category
  Object.assign(form, {
    name: category.name,
    slug: category.slug,
    description: category.description ?? '',
    color: category.color,
    parent_id: category.parent_id,
    position: category.position,
    is_active: category.is_active,
  })
  showModal.value = true
}

async function submit() {
  busy.value = true

  try {
    if (editing.value) {
      await adminApi.categories.update(editing.value.id, { ...form, slug: form.slug || undefined })
    } else {
      await adminApi.categories.store(form)
    }

    showModal.value = false
    ui.toast(t('admin.saved'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    busy.value = false
  }
}

async function remove(category) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.categories.destroy(category.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}
</script>

<template>
  <div>
    <div class="mb-5 flex justify-end">
      <button
        class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
        @click="openCreate"
      >
        + {{ t('admin.newCategory') }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.categoryName') }}</th>
              <th class="hidden px-5 py-3 font-bold sm:table-cell">{{ t('admin.parentCategory') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.articlesCount') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('common.status') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <template v-for="category in list" :key="category.id">
              <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="px-5 py-3.5">
                  <span class="mr-2 inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: category.color }" />
                  <span class="font-bold dark:text-zinc-100">{{ category.name }}</span>
                  <span class="ml-2 font-mono text-xs text-zinc-400">/{{ category.slug }}</span>
                </td>

                <td class="hidden px-5 py-3.5 text-zinc-500 sm:table-cell">—</td>
                <td class="px-5 py-3.5 font-semibold text-zinc-500">{{ category.articles_count }}</td>

                <td class="px-5 py-3.5">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                    :class="category.is_active
                      ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                      : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800'"
                  >
                    {{ category.is_active ? t('admin.categoryActive') : t('admin.categoryInactive') }}
                  </span>
                </td>

                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                  <button class="mr-3 text-xs font-bold text-blue-600 hover:underline" @click="openEdit(category)">
                    {{ t('common.edit') }}
                  </button>
                  <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(category)">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>

              <tr
                v-for="child in category.children ?? []"
                :key="child.id"
                class="bg-zinc-50/50 hover:bg-zinc-100/50 dark:bg-zinc-800/25"
              >
                <td class="px-5 py-2.5 pl-10 text-zinc-600 dark:text-zinc-300">
                  <span class="mr-2 inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: child.color }" />
                  {{ child.name }}
                </td>
                <td class="hidden px-5 py-2.5 text-zinc-400 sm:table-cell">{{ category.name }}</td>
                <td class="px-5 py-2.5 text-zinc-500">{{ child.articles_count }}</td>
                <td class="px-5 py-2.5" />
                <td class="whitespace-nowrap px-5 py-2.5 text-right">
                  <button class="mr-3 text-xs font-bold text-blue-600 hover:underline" @click="openEdit(child)">
                    {{ t('common.edit') }}
                  </button>
                  <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(child)">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 grid place-items-center px-4">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="showModal = false" />

        <form
          class="relative w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h3 class="text-lg font-black dark:text-white">
            {{ editing ? t('common.edit') : t('admin.newCategory') }}
          </h3>

          <div class="mt-5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.categoryName') }} *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  maxlength="100"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.slugField') }}</label>
                <input
                  v-model="form.slug"
                  type="text"
                  maxlength="100"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.categoryDescription') }}</label>
              <textarea
                v-model="form.description"
                rows="2"
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.categoryColor') }}</label>
                <input v-model="form.color" type="color" class="h-11 w-full rounded-xl border border-zinc-300 dark:border-zinc-700" />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.parentCategory') }}</label>
                <select
                  v-model="form.parent_id"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                >
                  <option :value="null">{{ t('admin.noParent') }}</option>
                  <option v-for="category in list" :key="category.id" :value="category.id" :disabled="category.id === editing?.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.categoryPosition') }}</label>
                <input
                  v-model.number="form.position"
                  type="number"
                  min="0"
                  max="999"
                  class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
              </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold dark:text-zinc-200">
              <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-brand-600" />
              {{ t('admin.categoryActive') }}
            </label>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-bold text-zinc-600 dark:border-zinc-700 dark:text-zinc-300"
              @click="showModal = false"
            >
              {{ t('common.cancel') }}
            </button>

            <button
              type="submit"
              class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-bold text-white hover:bg-brand-700 disabled:opacity-50"
              :disabled="busy"
            >
              {{ busy ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
