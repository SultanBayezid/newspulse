<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/api/client'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { formatDate } from '@/utils/format'

const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const loading = ref(true)
const list = ref([])
const meta = ref(null)
const search = ref('')
const role = ref('')
const page = ref(1)

const showModal = ref(false)
const editing = ref(null)
const busy = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'subscriber',
  bio: '',
})

const roles = [
  { value: 'super_admin', label: t('admin.roleSuperAdmin') },
  { value: 'editor', label: t('admin.roleEditor') },
  { value: 'author', label: t('admin.roleAuthor') },
  { value: 'subscriber', label: t('admin.roleSubscriber') },
]

async function load() {
  loading.value = true

  try {
    const { data } = await adminApi.users.list({
      page: page.value,
      q: search.value || undefined,
      role: role.value || undefined,
      per_page: 20,
    })

    list.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openCreate() {
  editing.value = null
  Object.assign(form, {
    name: '', email: '', password: '', password_confirmation: '', role: 'subscriber', bio: '',
  })
  showModal.value = true
}

function openEdit(user) {
  editing.value = user
  Object.assign(form, {
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    role: user.role,
    bio: user.bio ?? '',
  })
  showModal.value = true
}

async function submit() {
  busy.value = true

  try {
    if (editing.value) {
      const payload = {
        name: form.name,
        email: form.email,
        role: form.role,
        bio: form.bio || null,
      }

      if (form.password) {
        payload.password = form.password
        payload.password_confirmation = form.password_confirmation
      }

      await adminApi.users.update(editing.value.id, payload)
    } else {
      await adminApi.users.store(form)
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

async function toggleBan(user) {
  const confirmText = user.is_banned
    ? t('admin.unban') + '?'
    : t('admin.ban') + '? ' + t('common.confirmDelete')

  if (!window.confirm(confirmText)) {
    return
  }

  try {
    if (user.is_banned) {
      await adminApi.users.unban(user.id)
    } else {
      await adminApi.users.ban(user.id)
    }

    ui.toast(t('admin.saved'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}

async function remove(user) {
  if (!window.confirm(t('common.confirmDelete'))) {
    return
  }

  try {
    await adminApi.users.destroy(user.id)
    ui.toast(t('admin.deleted'))
    await load()
  } catch (error) {
    ui.toast(apiError(error), 'error')
  }
}
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center gap-3">
      <input
        v-model="search"
        type="search"
        :placeholder="t('admin.searchPlaceholder')"
        class="w-56 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @keyup.enter="page = 1; load()"
      />

      <select
        v-model="role"
        class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        @change="page = 1; load()"
      >
        <option value="">{{ t('admin.filterAll') }}</option>
        <option v-for="option in roles" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>

      <button
        class="ml-auto rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700"
        @click="openCreate"
      >
        + {{ t('admin.newUser') }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <AppSpinner v-if="loading" />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
            <tr>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableName') }}</th>
              <th class="hidden px-5 py-3 font-bold md:table-cell">{{ t('admin.tableEmail') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('admin.tableRole') }}</th>
              <th class="px-5 py-3 font-bold">{{ t('common.status') }}</th>
              <th class="hidden px-5 py-3 font-bold lg:table-cell">{{ t('admin.articlesCount') }}</th>
              <th class="px-5 py-3 text-right font-bold">{{ t('admin.tableActions') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="user in list" :key="user.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                  <div>
                    <p class="font-bold dark:text-zinc-100">{{ user.name }}</p>
                    <p class="text-xs text-zinc-400 md:hidden">{{ user.email }}</p>
                    <p class="hidden text-xs text-zinc-400 md:block">{{ t('admin.tableDate') }}: {{ formatDate(user.created_at) }}</p>
                  </div>
                </div>
              </td>

              <td class="hidden px-5 py-3.5 text-zinc-500 md:table-cell">
                {{ user.email }}
                <span v-if="user.email_verified_at" class="ml-1 text-green-500">✓</span>
              </td>

              <td class="px-5 py-3.5">
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                  {{ user.role_label }}
                </span>
              </td>

              <td class="px-5 py-3.5">
                <span
                  class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                  :class="user.is_banned
                    ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                    : 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'"
                >
                  {{ user.is_banned ? t('admin.banned') : t('admin.active') }}
                </span>
              </td>

              <td class="hidden px-5 py-3.5 font-semibold text-zinc-500 lg:table-cell">{{ user.articles_count ?? 0 }}</td>

              <td class="whitespace-nowrap px-5 py-3.5 text-right">
                <template v-if="user.id !== auth.user?.id">
                  <button class="mr-3 text-xs font-bold text-blue-600 hover:underline" @click="openEdit(user)">
                    {{ t('common.edit') }}
                  </button>

                  <button
                    class="mr-3 text-xs font-bold hover:underline"
                    :class="user.is_banned ? 'text-green-600' : 'text-amber-600'"
                    @click="toggleBan(user)"
                  >
                    {{ user.is_banned ? t('admin.unban') : t('admin.ban') }}
                  </button>

                  <button class="text-xs font-bold text-red-500 hover:underline" @click="remove(user)">
                    {{ t('common.delete') }}
                  </button>
                </template>

                <span v-else class="text-xs font-bold text-zinc-400">({{ t('nav.profile') }})</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6">
      <AppPagination v-if="meta" :meta="meta" @page="(p) => { page = p; load() }" />
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 grid place-items-center px-4">
        <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-sm" @click="showModal = false" />

        <form
          class="relative w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h3 class="text-lg font-black dark:text-white">
            {{ editing ? t('common.edit') : t('admin.newUser') }}
          </h3>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.tableName') }} *</label>
              <input
                v-model="form.name"
                type="text"
                required
                maxlength="100"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.tableEmail') }} *</label>
              <input
                v-model="form.email"
                type="email"
                required
                maxlength="150"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">
                {{ editing ? t('auth.newPassword') : t('auth.password') }} {{ editing ? `(${t('common.optional')})` : '*' }}
              </label>
              <input
                v-model="form.password"
                type="password"
                :required="!editing"
                minlength="8"
                autocomplete="new-password"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />

              <input
                v-if="form.password"
                v-model="form.password_confirmation"
                type="password"
                :placeholder="t('auth.confirmPassword')"
                autocomplete="new-password"
                class="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.tableRole') }} *</label>
              <select
                v-model="form.role"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              >
                <option v-for="option in roles" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('account.bio') }}</label>
              <textarea
                v-model="form.bio"
                rows="2"
                maxlength="500"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>
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
