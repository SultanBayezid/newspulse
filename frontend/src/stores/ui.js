import { defineStore } from 'pinia'
import { settings } from '@/api'

const THEME_KEY = 'newspulse_theme'
const LOCALE_KEY = 'newspulse_locale'

export const useUiStore = defineStore('ui', {
  state: () => ({
    theme: localStorage.getItem(THEME_KEY) || 'light',
    locale: localStorage.getItem(LOCALE_KEY) || 'en',
    toasts: [],
    siteSettings: {},
    siteSettingsLoaded: false,
  }),

  actions: {
    applyTheme() {
      document.documentElement.classList.toggle('dark', this.theme === 'dark')
    },

    toggleTheme() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark'
      localStorage.setItem(THEME_KEY, this.theme)
      this.applyTheme()
    },

    setLocale(locale) {
      this.locale = locale
      localStorage.setItem(LOCALE_KEY, locale)

      if (window.i18n) {
        window.i18n.global.locale.value = locale
      }
    },

    toast(message, type = 'success', duration = 3500) {
      const id = Date.now() + Math.random()

      this.toasts.push({ id, message, type })

      setTimeout(() => this.dismissToast(id), duration)
    },

    dismissToast(id) {
      this.toasts = this.toasts.filter((toast) => toast.id !== id)
    },

    setting(key, fallback = null) {
      return this.siteSettings[key] ?? fallback
    },

    async loadSiteSettings(force = false) {
      if (this.siteSettingsLoaded && !force) {
        return
      }

      try {
        const { data } = await settings.public()
        this.siteSettings = data.data
        this.siteSettingsLoaded = true
      } catch {
        // The interface falls back to defaults when offline.
      }
    },
  },
})
