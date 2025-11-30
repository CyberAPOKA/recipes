<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Card from '../components/daisyui/Card.vue'
import Input from '../components/daisyui/Input.vue'
import Button from '../components/daisyui/Button.vue'
import Alert from '../components/daisyui/Alert.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
})

const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  const result = await authStore.login(form.value)

  if (result.success) {
    router.push('/recipes')
  } else {
    error.value = result.message
  }

  loading.value = false
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <Card class="w-full max-w-md" bordered>
      <template #title>
        <h1 class="text-2xl font-bold">{{ $t('auth.login') }}</h1>
      </template>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <Alert v-if="error" type="error">{{ error }}</Alert>

        <Input v-model="form.email" type="email" :label="$t('auth.email')" :placeholder="$t('auth.email')" required />

        <Input v-model="form.password" type="password" :label="$t('auth.password')" :placeholder="$t('auth.password')"
          required />

        <Button type="submit" variant="primary" :loading="loading" block>
          {{ $t('auth.login') }}
        </Button>

        <div class="text-center">
          <router-link to="/register" class="link link-primary">
            {{ $t('auth.dontHaveAccount') }}
          </router-link>
        </div>
      </form>
    </Card>
  </div>
</template>
