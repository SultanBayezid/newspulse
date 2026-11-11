<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi } from '@/api'
import { useUiStore } from '@/stores/ui'
import { apiError } from '@/api/client'

const { t } = useI18n()
const ui = useUiStore()

const loading = ref(true)
const saving = ref(false)

const form = reactive({
  site_name: '',
  site_tagline: '',
  site_description: '',
  contact_email: '',
  footer_text: '',
  social_facebook: '',
  social_twitter: '',
  social_youtube: '',
  social_instagram: '',
  social_linkedin: '',
  articles_per_page: '12',
  comments_enabled: true,
  comments_moderation: true,
  registration_enabled: true,
  default_language: 'en',
})

onMounted(async () => {
  try {
    const { data } = await adminApi.settings.get()

    Object.keys(form).forEach((key) => {
      const value = data.data[key]

      if (typeof form[key] === 'boolean') {
        form[key] = ['1', 'true', 'on'].includes(String(value ?? ''))
      } else {
        form[key] = value ?? ''
      }
    })
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    loading.value = false
  }
})

async function submit() {
  saving.value = true

  const payload = {}

  Object.entries(form).forEach(([key, value]) => {
    payload[key] = typeof value === 'boolean' ? (value ? '1' : '0') : String(value)
  })

  try {
    await adminApi.settings.update({ settings: payload })
    ui.toast(t('admin.settingsSaved'))
    ui.loadSiteSettings(true)
  } catch (error) {
    ui.toast(apiError(error), 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl">
    <form v-if="!loading" class="space-y-6" @submit.prevent="submit">
      <!-- Identity -->
      <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-black uppercase tracking-wider text-zinc-400">{{ t('admin.settingsTitle') }}</h3>

        <div class="mt-5 space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsSiteName') }}</label>
              <input
                v-model="form.site_name"
                type="text"
                required
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsContactEmail') }}</label>
              <input
                v-model="form.contact_email"
                type="email"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsTagline') }}</label>
            <input
              v-model="form.site_tagline"
              type="text"
              maxlength="150"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsDescription') }}</label>
            <textarea
              v-model="form.site_description"
              rows="3"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsFooterText') }}</label>
            <input
              v-model="form.footer_text"
              type="text"
              maxlength="200"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
          </div>
        </div>
      </section>

      <!-- Social -->
      <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-black uppercase tracking-wider text-zinc-400">{{ t('admin.settingsSocial') }}</h3>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div v-for="key in ['social_facebook', 'social_twitter', 'social_youtube', 'social_instagram', 'social_linkedin']" :key="key">
            <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settings' + key.split('_')[1].charAt(0).toUpperCase() + key.split('_')[1].slice(1)) }}</label>
            <input
              v-model="form[key]"
              type="url"
              placeholder="https://…"
              class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
          </div>
        </div>
      </section>

      <!-- Reading & community -->
      <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-black uppercase tracking-wider text-zinc-400">
          {{ t('admin.settingsReading') }} & {{ t('admin.settingsCommunity') }}
        </h3>

        <div class="mt-5 space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsPerPage') }}</label>
              <input
                v-model.number="form.articles_per_page"
                type="number"
                min="1"
                max="50"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-bold dark:text-zinc-200">{{ t('admin.settingsDefaultLanguage') }}</label>
              <select
                v-model="form.default_language"
                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
              >
                <option value="en">{{ t('common.english') }}</option>
                <option value="bn">{{ t('common.bangla') }}</option>
              </select>
            </div>
          </div>

          <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3.5 dark:border-zinc-700">
            <input v-model="form.comments_enabled" type="checkbox" class="h-4 w-4 accent-brand-600" />
            <span class="text-sm font-semibold dark:text-zinc-200">{{ t('admin.settingsCommentsEnabled') }}</span>
          </label>

          <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3.5 dark:border-zinc-700">
            <input v-model="form.comments_moderation" type="checkbox" class="h-4 w-4 accent-brand-600" />
            <span class="text-sm font-semibold dark:text-zinc-200">{{ t('admin.settingsCommentsModeration') }}</span>
          </label>

          <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3.5 dark:border-zinc-700">
            <input v-model="form.registration_enabled" type="checkbox" class="h-4 w-4 accent-brand-600" />
            <span class="text-sm font-semibold dark:text-zinc-200">{{ t('admin.settingsRegistration') }}</span>
          </label>
        </div>
      </section>

      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-50"
        :disabled="saving"
      >
        {{ saving ? t('common.saving') : t('common.save') }}
      </button>
    </form>
  </div>
</template>
