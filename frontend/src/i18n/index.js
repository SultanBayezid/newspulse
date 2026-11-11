import { createI18n } from 'vue-i18n'
import en from './en'
import bn from './bn'

const savedLocale = localStorage.getItem('newspulse_locale') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, bn },
})

// Exposed for the UI store.
window.i18n = i18n

export default i18n
