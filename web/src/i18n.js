import { createI18n } from 'vue-i18n'
import ptBR from './locales/pt-BR.json'
import enUS from './locales/en-US.json'
import esES from './locales/es-ES.json'

const messages = {
  'pt-BR': ptBR,
  'en-US': enUS,
  'es-ES': esES,
}

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('preferred-locale') || 'pt-BR',
  fallbackLocale: 'pt-BR',
  messages,
})

export default i18n

