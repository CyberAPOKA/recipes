<template>
  <div class="min-h-screen bg-base-200">
    <nav class="navbar bg-base-100 shadow-lg">
      <div class="flex-1">
        <router-link to="/recipes" class="btn btn-ghost text-xl">
          {{ $t('recipe.title') }}
        </router-link>
      </div>
      <div class="flex-none gap-2">
        <ThemeSelector />
        <LanguageSelector />
        <div v-if="authStore.isAuthenticated" class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost">
            {{ authStore.user?.name }}
          </div>
          <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
            <li>
              <a @click="handleLogout">{{ $t('auth.logout') }}</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <main class="container mx-auto py-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'
import ThemeSelector from './ThemeSelector.vue'
import LanguageSelector from './LanguageSelector.vue'

const router = useRouter()
const authStore = useAuthStore()
const themeStore = useThemeStore()

const handleLogout = async () => {
  await authStore.logout()
}

onMounted(() => {
  // Initialize theme
  const savedTheme = localStorage.getItem('theme') || 'light'
  themeStore.changeTheme(savedTheme)
  
  // Initialize auth
  authStore.initAuth()
  if (authStore.token) {
    authStore.fetchUser()
  }
})
</script>

