<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBars, faTimes } from '@fortawesome/free-solid-svg-icons'
import ThemeSelector from './ThemeSelector.vue'
import LanguageSelector from './LanguageSelector.vue'
import Button from './daisyui/Button.vue'

const router = useRouter()
const authStore = useAuthStore()
const themeStore = useThemeStore()

const mobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const handleLogout = async () => {
  await authStore.logout()
  closeMobileMenu()
}

const navigateTo = (path) => {
  router.push(path)
  closeMobileMenu()
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

<template>
  <div class="min-h-screen bg-base-200">
    <nav class="navbar bg-base-100 shadow-lg px-2 sm:px-4 no-print">
      <!-- Mobile Hamburger Button -->
      <div class="flex-none lg:hidden">
        <button class="btn btn-square btn-ghost" @click="toggleMobileMenu">
          <FontAwesomeIcon :icon="mobileMenuOpen ? faTimes : faBars" class="text-xl" />
        </button>
      </div>

      <!-- Logo/Brand -->
      <div class="flex-1 min-w-0">
        <router-link to="/" class="btn btn-ghost text-base sm:text-xl px-2 sm:px-4" @click="closeMobileMenu">
          <span class="truncate">{{ $t('recipe.title') }}</span>
        </router-link>
      </div>

      <!-- Desktop Menu Items -->
      <div class="flex-none gap-1 sm:gap-2 hidden lg:flex">
        <Button v-if="authStore.isAuthenticated" variant="primary" @click="$router.push('/recipes/create')">
          {{ $t('recipe.create') }}
        </Button>
        <Button v-else variant="primary" @click="$router.push('/login')">
          {{ $t('auth.login') }}
        </Button>
        <ThemeSelector />
        <LanguageSelector />
        <div v-if="authStore.isAuthenticated" class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost text-base px-4">
            <span class="truncate max-w-[150px]">{{ authStore.user?.name }}</span>
          </div>
          <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
            <li>
              <a @click="handleLogout">{{ $t('auth.logout') }}</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Mobile Menu Drawer -->
    <div v-if="mobileMenuOpen" class="lg:hidden fixed inset-0 z-50 no-print" @click="closeMobileMenu">
      <!-- Overlay -->
      <div class="fixed inset-0 bg-black bg-opacity-50"></div>
      <!-- Drawer -->
      <div class="fixed inset-y-0 left-0 w-64 bg-base-100 shadow-xl overflow-y-auto" @click.stop>
        <div class="p-4">
          <!-- Close Button -->
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">{{ $t('recipe.title') }}</h2>
            <button class="btn btn-square btn-ghost btn-sm" @click="closeMobileMenu">
              <FontAwesomeIcon :icon="faTimes" />
            </button>
          </div>

          <!-- Menu Items -->
          <ul class="menu menu-vertical w-full gap-2">
            <li v-if="authStore.isAuthenticated">
              <a @click="navigateTo('/recipes/create')" class="flex items-center gap-2">
                <span>{{ $t('recipe.create') }}</span>
              </a>
            </li>
            <li v-else>
              <a @click="navigateTo('/login')" class="flex items-center gap-2">
                <span>{{ $t('auth.login') }}</span>
              </a>
            </li>

            <!-- Theme Selector -->
            <li>
              <div class="flex flex-col gap-2 w-full py-2">
                <span class="text-sm font-semibold">{{ $t('theme.title') }}</span>
                <ThemeSelector />
              </div>
            </li>

            <!-- Language Selector -->
            <li>
              <div class="flex flex-col gap-2 w-full py-2">
                <span class="text-sm font-semibold">Idioma</span>
                <LanguageSelector />
              </div>
            </li>

            <!-- User Info & Logout -->
            <li v-if="authStore.isAuthenticated" class="mt-4 pt-4 border-t">
              <div class="px-4 py-2">
                <p class="text-sm font-semibold">{{ authStore.user?.name }}</p>
                <p class="text-xs text-gray-500">{{ authStore.user?.email }}</p>
              </div>
            </li>
            <li v-if="authStore.isAuthenticated">
              <a @click="handleLogout" class="text-error">
                {{ $t('auth.logout') }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <main class="container mx-auto px-2 sm:px-4 lg:px-6 py-4 sm:py-6">
      <router-view />
    </main>
  </div>
</template>
