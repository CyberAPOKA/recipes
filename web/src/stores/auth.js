import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api/auth'
import { useI18n } from 'vue-i18n'

export const useAuthStore = defineStore('auth', () => {
  const { t } = useI18n()
  
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Initialize user from localStorage
  const initAuth = () => {
    const savedUser = localStorage.getItem('user')
    if (savedUser) {
      try {
        user.value = JSON.parse(savedUser)
      } catch (e) {
        console.error('Error parsing user from localStorage:', e)
      }
    }
  }

  // Register
  const register = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await authApi.register(data)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('auth_token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      return { success: true, message: t('auth.registerSuccess') }
    } catch (err) {
      error.value = err.response?.data?.message || t('common.error')
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Login
  const login = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await authApi.login(data)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('auth_token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      return { success: true, message: t('auth.loginSuccess') }
    } catch (err) {
      error.value = err.response?.data?.message || t('auth.invalidCredentials')
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Logout
  const logout = async () => {
    loading.value = true
    try {
      await authApi.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      loading.value = false
    }
  }

  // Get current user
  const fetchUser = async () => {
    if (!token.value) return
    try {
      const response = await authApi.getUser()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (err) {
      console.error('Error fetching user:', err)
      logout()
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    initAuth,
    register,
    login,
    logout,
    fetchUser,
  }
})

