<script setup>
import { useThemeStore } from '@/stores/theme'
import Button from '@/components/daisyui/Button.vue'

const themes = [
    'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate',
    'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween', 'garden',
    'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black',
    'luxury', 'dracula', 'cmyk', 'autumn', 'business', 'acid', 'lemonade',
    'night', 'coffee', 'winter', 'dim', 'nord', 'sunset', 'caramellatte',
    'abyss', 'silk'
]

const themeStore = useThemeStore()
</script>

<template>
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
            {{ $t('theme.title') }}
        </div>
        <ul tabindex="0"
            class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-screen max-w-md lg:max-w-3xl max-h-96 overflow-y-auto">
            <li v-for="theme in themes" :key="theme">
                <Button @click="themeStore.changeTheme(theme)" variant="ghost"
                    :class="['gap-3 px-2 w-full text-left', { 'btn-active': themeStore.currentTheme === theme }]">
                    <div :data-theme="theme"
                        class="bg-base-100 grid shrink-0 grid-cols-2 gap-0.5 rounded-md p-1 shadow-sm">
                        <div class="bg-base-content size-1 rounded-full"></div>
                        <div class="bg-primary size-1 rounded-full"></div>
                        <div class="bg-secondary size-1 rounded-full"></div>
                        <div class="bg-accent size-1 rounded-full"></div>
                    </div>
                    <div class="w-32 truncate">{{ theme }}</div>
                </Button>
            </li>
        </ul>
    </div>
</template>
