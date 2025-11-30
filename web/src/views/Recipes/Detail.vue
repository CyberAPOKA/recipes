<script setup>
import { onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useRecipeStore } from '@/stores/recipe'
import { useI18n } from 'vue-i18n'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'

const route = useRoute()
const router = useRouter()
const recipeStore = useRecipeStore()
const { t } = useI18n()

const { currentRecipe: recipe, loading } = storeToRefs(recipeStore)

const fetchRecipeData = () => {
  const recipeId = Number(route.params.id)
  if (recipeId) {
    recipeStore.fetchRecipe(recipeId)
  }
}

const handlePrint = () => {
  window.print()
}

const handleDelete = async () => {
  if (confirm(t('recipe.deleteConfirm'))) {
    const recipeId = Number(route.params.id)
    const result = await recipeStore.deleteRecipe(recipeId)
    if (result.success) {
      router.push('/recipes')
    }
  }
}

onMounted(() => {
  fetchRecipeData()
})

// Watch for route changes to reload recipe when navigating between recipes
watch(() => route.params.id, () => {
  fetchRecipeData()
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>


<template>
  <div class="container mx-auto p-4 max-w-4xl">
    <div v-if="loading" class="text-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="recipe">
      <div class="flex justify-between items-start mb-6 no-print">
        <h1 class="text-3xl font-bold">{{ recipe.name || $t('recipe.title') }}</h1>
        <div class="flex gap-2">
          <Button variant="primary" @click="handlePrint">
            {{ $t('recipe.print') }}
          </Button>
          <Button variant="ghost" @click="$router.push(`/recipes/${recipe.id}/edit`)">
            {{ $t('common.edit') }}
          </Button>
          <Button variant="error" @click="handleDelete">
            {{ $t('common.delete') }}
          </Button>
        </div>
      </div>
      <div class="print:hidden">
        <h1 class="text-3xl font-bold mb-6">{{ recipe.name || $t('recipe.title') }}</h1>
      </div>

      <Card bordered class="print:shadow-none">
        <div class="space-y-4">
          <div v-if="recipe.category" class="badge badge-primary badge-lg">
            {{ recipe.category.name }}
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div v-if="recipe.prep_time_minutes" class="stat">
              <div class="stat-title">{{ $t('recipe.prepTime') }}</div>
              <div class="stat-value text-lg">{{ recipe.prep_time_minutes }} min</div>
            </div>
            <div v-if="recipe.servings" class="stat">
              <div class="stat-title">{{ $t('recipe.servings') }}</div>
              <div class="stat-value text-lg">{{ recipe.servings }}</div>
            </div>
          </div>

          <div v-if="recipe.ingredients">
            <h2 class="text-xl font-bold mb-2">{{ $t('recipe.ingredients') }}</h2>
            <div class="whitespace-pre-wrap">{{ recipe.ingredients }}</div>
          </div>

          <div>
            <h2 class="text-xl font-bold mb-2">{{ $t('recipe.instructions') }}</h2>
            <div class="whitespace-pre-wrap">{{ recipe.instructions }}</div>
          </div>
        </div>
      </Card>
    </div>

    <div v-else class="text-center py-8">
      <p>{{ $t('recipe.noRecipes') }}</p>
      <Button variant="primary" @click="$router.push('/recipes')" class="mt-4">
        {{ $t('common.back') }}
      </Button>
    </div>
  </div>
</template>