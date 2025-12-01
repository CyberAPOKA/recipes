<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useRecipeStore } from '@/stores/recipe'
import { useI18n } from 'vue-i18n'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClock, faUsers } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Input from '@/components/daisyui/Input.vue'
import Button from '@/components/daisyui/Button.vue'

const router = useRouter()
const recipeStore = useRecipeStore()
const { t } = useI18n()

const { recipes, loading, pagination } = storeToRefs(recipeStore)
const search = ref('')
let searchTimeout = null

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    recipeStore.fetchRecipes(search.value)
  }, 500)
}

const changePage = async (page) => {
  await recipeStore.fetchRecipes(search.value)
  // Note: Pagination would need backend support for page parameter
  // For now, this just refreshes the current page
}

const handleDelete = async (id) => {
  if (confirm(t('recipe.deleteConfirm'))) {
    const result = await recipeStore.deleteRecipe(id)
    if (result.success) {
      recipeStore.fetchRecipes(search.value)
    }
  }
}

onMounted(() => {
  recipeStore.fetchRecipes()
})
</script>

<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">{{ $t('recipe.myRecipes') }}</h1>
      <Button variant="primary" @click="$router.push('/recipes/create')">
        {{ $t('recipe.create') }}
      </Button>
    </div>

    <div class="mb-4">
      <Input v-model="search" type="text" :placeholder="$t('recipe.search')" @input="handleSearch" />
    </div>

    <div v-if="loading" class="text-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="recipes.length === 0" class="text-center py-8">
      <p class="text-lg">{{ $t('recipe.noRecipes') }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card v-for="recipe in recipes" :key="recipe.id" class="hover:shadow-2xl transition-shadow cursor-pointer"
        bordered>
        <template #title>
          {{ recipe.name || $t('recipe.title') }}
        </template>
        <div class="space-y-2" @click="$router.push(`/recipes/${recipe.id}`)">
          <img v-if="recipe.image" :src="recipe.image" :alt="recipe.name || 'Recipe image'"
            class="w-full h-48 object-cover rounded-lg mb-2" />
          <p v-if="recipe.category" class="badge badge-primary">
            {{ recipe.category.name }}
          </p>
          <div class="flex items-center justify-between gap-4">
            <p v-if="recipe.prep_time_minutes" class="text-sm flex items-center gap-2">
              <FontAwesomeIcon :icon="faClock" /> {{ recipe.prep_time_minutes }} min
            </p>
            <p v-if="recipe.servings" class="text-sm flex items-center gap-2">
              <FontAwesomeIcon :icon="faUsers" /> {{ recipe.servings }} {{ $t('recipe.servings') }}
            </p>
          </div>
          <div
            class="text-sm prose prose-sm max-w-none overflow-hidden [&_h1]:text-lg [&_h1]:font-bold [&_h1]:my-1 [&_h2]:text-base [&_h2]:font-bold [&_h2]:my-1 [&_h3]:text-sm [&_h3]:font-bold [&_h3]:my-1 [&_p]:my-1 [&_ul]:list-disc [&_ul]:pl-4 [&_ul]:my-1 [&_ol]:list-decimal [&_ol]:pl-4 [&_ol]:my-1 [&_li]:my-0.5 recipe-preview"
            v-html="recipe.instructions"></div>
        </div>
        <template #actions>
          <Button variant="ghost" size="sm" @click.stop="$router.push(`/recipes/${recipe.id}/edit`)">
            {{ $t('common.edit') }}
          </Button>
          <Button variant="error" size="sm" @click.stop="handleDelete(recipe.id)">
            {{ $t('common.delete') }}
          </Button>
        </template>
      </Card>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center mt-6">
      <div class="join">
        <Button variant="ghost" :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)">
          «
        </Button>
        <Button variant="ghost" class="join-item">
          {{ pagination.current_page }} / {{ pagination.last_page }}
        </Button>
        <Button variant="ghost" :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)">
          »
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.recipe-preview {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 4.5rem;
}
</style>