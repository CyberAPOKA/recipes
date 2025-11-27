// stores/theme.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
    const currentTheme = ref('light')

    function changeTheme(theme) {
        currentTheme.value = theme
        document.documentElement.setAttribute('data-theme', theme)
        document.body.setAttribute('data-theme', theme)
    }

    return { currentTheme, changeTheme }
})
