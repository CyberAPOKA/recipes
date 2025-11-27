import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import i18n from '@/i18n.js'

export const useLanguageStore = defineStore('language', () => {
    const currentCode = ref(i18n.global.locale.value)

    // Idades disponíveis
    // Resolve imagens via Vite (URL hash para evitar cache incorreto)
    const flagPt = new URL('../assets/flags/flag-pt-circle.png', import.meta.url).href
    const flagEn = new URL('../assets/flags/flag-en-circle.png', import.meta.url).href
    const flagEs = new URL('../assets/flags/flag-es-circle.png', import.meta.url).href

    const availableLocales = [
        { code: 'pt-BR', name: 'Português', flag: '🇧🇷', img: flagPt },
        { code: 'en-US', name: 'English', flag: '🇺🇸', img: flagEn },
        { code: 'es-ES', name: 'Español', flag: '🇪🇸', img: flagEs }
    ]

    // Idioma atual (computed)
    const currentLocale = computed(() => {
        return availableLocales.find(l => l.code === currentCode.value)
    })

    // Função para alterar idioma
    const setLocale = (newLocale) => {
        if (availableLocales.some(l => l.code === newLocale)) {
            i18n.global.locale.value = newLocale
            currentCode.value = newLocale
            localStorage.setItem('preferred-locale', newLocale)
        }
    }

    // Função para alternar idioma
    const toggleLocale = () => {
        const currentIndex = availableLocales.findIndex(l => l.code === currentCode.value)
        const nextIndex = (currentIndex + 1) % availableLocales.length
        setLocale(availableLocales[nextIndex].code)
    }

    // Inicializar idioma do localStorage
    const initializeLocale = () => {
        const savedLocale = localStorage.getItem('preferred-locale')
        if (savedLocale && availableLocales.some(l => l.code === savedLocale)) {
            i18n.global.locale.value = savedLocale
            currentCode.value = savedLocale
        }
    }

    // Sincroniza quando i18n muda por fora
    watch(() => i18n.global.locale.value, (val) => {
        currentCode.value = val
    })

    return {
        availableLocales,
        currentLocale,
        setLocale,
        toggleLocale,
        initializeLocale
    }
})
