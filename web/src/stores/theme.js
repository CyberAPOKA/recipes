// stores/theme.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
    const savedTheme = localStorage.getItem('theme') || 'light'
    const currentTheme = ref(savedTheme)

    function changeTheme(theme) {
        currentTheme.value = theme
        document.documentElement.setAttribute('data-theme', theme)
        document.body.setAttribute('data-theme', theme)
        localStorage.setItem('theme', theme)
    }

    // Initialize theme on store creation
    changeTheme(savedTheme)

    return { currentTheme, changeTheme }
})
