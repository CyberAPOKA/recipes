<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/category'
import { useRecipeStore } from '@/stores/recipe'
import { publicRecipeApi } from '@/api/recipe'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClock, faUsers, faFilter, faStar, faComments } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import RecipeFilters from '@/components/RecipeFilters.vue'

const router = useRouter()
const authStore = useAuthStore()
const categoryStore = useCategoryStore()
const recipeStore = useRecipeStore()

const showSidebar = ref(false)

// Filters state
const filters = ref({
  search: '',
  categoryId: null,
  servingsOperator: 'exact',
  servingsValue: null,
  prepTimeOperator: 'exact',
  prepTimeValue: null,
  myRecipes: false,
})

// For non-authenticated users, use local state
const publicRecipes = ref([])
const publicLoading = ref(false)
const publicPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
})

// Computed properties
const recipes = computed(() => {
  return authStore.isAuthenticated ? recipeStore.recipes : publicRecipes.value
})

const loading = computed(() => {
  return authStore.isAuthenticated ? recipeStore.loading : publicLoading.value
})

const pagination = computed(() => {
  return authStore.isAuthenticated ? recipeStore.pagination : publicPagination.value
})

const fetchRecipes = async (page = 1) => {
  if (authStore.isAuthenticated) {
    // Use recipeApi through store - it already returns only user's recipes
    await recipeStore.fetchRecipes('', page, filters.value)
  } else {
    // Use publicRecipeApi for non-authenticated users
    publicLoading.value = true
    try {
      const apiFilters = {}

      if (filters.value.categoryId) {
        apiFilters.category_id = filters.value.categoryId
      }

      if (filters.value.servingsValue) {
        apiFilters.servings_operator = filters.value.servingsOperator
        apiFilters.servings_value = filters.value.servingsValue
      }

      if (filters.value.prepTimeValue) {
        apiFilters.prep_time_operator = filters.value.prepTimeOperator
        apiFilters.prep_time_value = filters.value.prepTimeValue
      }

      if (filters.value.search?.trim()) {
        apiFilters.search = filters.value.search.trim()
      }

      const response = await publicRecipeApi.getAll(apiFilters, page)
      publicRecipes.value = response.data.data
      publicPagination.value = response.data.meta
    } catch (error) {
      console.error('Error fetching recipes:', error)
    } finally {
      publicLoading.value = false
    }
  }
}

const applyFilters = () => {
  if (authStore.isAuthenticated) {
    recipeStore.pagination.current_page = 1
  } else {
    publicPagination.value.current_page = 1
  }
  fetchRecipes(1)
}

const clearFilters = () => {
  filters.value = {
    search: '',
    categoryId: null,
    servingsOperator: 'exact',
    servingsValue: null,
    prepTimeOperator: 'exact',
    prepTimeValue: null,
    myRecipes: false,
  }
  applyFilters()
}

const changePage = (page) => {
  fetchRecipes(page)
}

const formatTime = (minutes) => {
  if (!minutes) return '-'
  if (minutes < 60) return `${minutes} min`
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`
}

const renderStars = (rating) => {
  const fullStars = Math.floor(rating)
  const hasHalfStar = rating % 1 >= 0.5
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0)

  return {
    full: fullStars,
    half: hasHalfStar ? 1 : 0,
    empty: emptyStars,
  }
}

onMounted(async () => {
  await categoryStore.fetchCategories()
  await fetchRecipes()
})

// Watch for filter changes
watch(() => filters.value, () => {
  applyFilters()
}, { deep: true })
</script>

<template>
  <div class="flex flex-col lg:flex-row gap-4 min-h-screen">
    <!-- Mobile Filter Toggle -->
    <div class="lg:hidden flex justify-between items-center">
      <h1 class="text-3xl font-bold">Receitas</h1>
      <div class="flex gap-2">
        <Button variant="secondary" @click="showSidebar = !showSidebar">
          <FontAwesomeIcon :icon="faFilter" class="mr-2" />
          Filtros
        </Button>
        <Button v-if="authStore.isAuthenticated" variant="primary" @click="$router.push('/recipes/create')">
          Criar Receita
        </Button>
        <Button v-else variant="primary" @click="$router.push('/login')">
          Entrar
        </Button>
      </div>
    </div>

    <!-- Sidebar Filters -->
    <aside :class="[
      'w-full lg:w-96 flex-shrink-0 lg:sticky lg:top-4 h-fit',
      'lg:block',
      showSidebar ? 'block' : 'hidden'
    ]">
      <RecipeFilters v-model="filters" :show-my-recipes="authStore.isAuthenticated" @clear="clearFilters"
        @apply="applyFilters" />
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0">
      <div class="hidden lg:flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Receitas</h1>
        <div class="flex gap-2">
          <Button v-if="authStore.isAuthenticated" variant="primary" @click="$router.push('/recipes/create')">
            Criar Receita
          </Button>
          <Button v-else variant="primary" @click="$router.push('/login')">
            Entrar
          </Button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <!-- Empty State -->
      <div v-else-if="recipes.length === 0" class="text-center py-8">
        <p class="text-lg">Nenhuma receita encontrada</p>
      </div>

      <!-- Recipes Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="recipe in recipes" :key="recipe.id" class="hover:shadow-2xl transition-shadow cursor-pointer"
          bordered @click="$router.push(`/public/recipes/${recipe.id}`)">
          <div class="space-y-2">
            <img v-if="recipe.image" :src="recipe.image" :alt="recipe.name || 'Recipe image'"
              class="w-full h-48 object-cover rounded-lg mb-2" />
            <div class="flex items-start justify-between gap-2">
              <h3 class="text-lg font-bold flex-1">{{ recipe.name || 'Receita sem nome' }}</h3>
              <div v-if="recipe.category" class="badge badge-primary">
                {{ recipe.category.name }}
              </div>
            </div>

            <div class="flex items-center gap-4 text-sm">
              <span v-if="recipe.prep_time_minutes" class="flex items-center gap-1">
                <FontAwesomeIcon :icon="faClock" />
                {{ formatTime(recipe.prep_time_minutes) }}
              </span>
              <span v-if="recipe.servings" class="flex items-center gap-1">
                <FontAwesomeIcon :icon="faUsers" />
                {{ recipe.servings }} porções
              </span>
            </div>

            <!-- Rating -->
            <div v-if="recipe.average_rating" class="flex items-center gap-2">
              <div class="flex items-center gap-1">
                <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).full" :key="`full-${i}`" :icon="faStar"
                  class="text-yellow-400" />
                <FontAwesomeIcon v-if="renderStars(recipe.average_rating).half" :icon="faStar" class="text-yellow-400"
                  style="clip-path: inset(0 50% 0 0);" />
                <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).empty" :key="`empty-${i}`"
                  :icon="faStar" class="text-gray-300" />
              </div>
              <span class="text-sm">{{ recipe.average_rating.toFixed(1) }}</span>
              <span v-if="recipe.ratings_count" class="text-sm text-gray-500">
                ({{ recipe.ratings_count }})
              </span>
            </div>

            <!-- Comments Count -->
            <div v-if="recipe.comments_count" class="flex items-center gap-1 text-sm text-gray-500">
              <FontAwesomeIcon :icon="faComments" />
              <span>{{ recipe.comments_count }} comentário{{ recipe.comments_count !== 1 ? 's' : '' }}</span>
            </div>

            <!-- Author -->
            <div v-if="recipe.user" class="text-sm text-gray-500">
              Por: {{ recipe.user.name }}
            </div>
          </div>
        </Card>
      </div>

      <!-- Pagination -->
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
    </main>
  </div>
</template>

<style scoped>
aside {
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
}

@media (max-width: 1024px) {
  aside {
    max-height: none;
    overflow-y: visible;
  }
}
</style>
