<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/category'
import { publicRecipeApi } from '@/api/recipe'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClock, faUsers, faFilter, faStar, faComments, faTh, faList } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Select from '@/components/daisyui/Select.vue'
import RecipeFilters from '@/components/RecipeFilters.vue'

const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()
const categoryStore = useCategoryStore()

const showSidebar = ref(false)

// Filters state
const filters = ref({
  search: '',
  categoryId: null,
  servingsOperator: 'exact',
  servingsValue: null,
  prepTimeOperator: 'exact',
  prepTimeValue: null,
  ratingOperator: 'exact',
  ratingValue: null,
  commentsOperator: 'exact',
  commentsValue: null,
  sortBy: 'recent',
  myRecipes: false,
})

// Layout state (grid or list)
const layoutMode = ref('grid') // 'grid' or 'list'

// Pagination options based on layout
const gridPerPageOptions = [9, 12, 15, 24, 30]
const listPerPageOptions = [10, 15, 20, 25, 30]
const perPage = ref(15)

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
  return publicRecipes.value
})

const loading = computed(() => {
  return publicLoading.value
})

const pagination = computed(() => {
  return publicPagination.value
})

// Prevent duplicate API calls
let isFetching = false

const fetchRecipes = async (page = 1) => {
  // Prevent duplicate calls
  if (isFetching) {
    return
  }

  isFetching = true

  // Always use publicRecipeApi for consistency (works for both authenticated and non-authenticated users)
  publicLoading.value = true
  try {
    const apiFilters = {
      per_page: Number(perPage.value),
      sort_by: filters.value.sortBy || 'recent',
    }

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

    if (filters.value.ratingValue !== null && filters.value.ratingValue !== undefined) {
      apiFilters.rating_operator = filters.value.ratingOperator
      apiFilters.rating_value = filters.value.ratingValue
    }

    if (filters.value.commentsValue !== null && filters.value.commentsValue !== undefined) {
      apiFilters.comments_operator = filters.value.commentsOperator
      apiFilters.comments_value = filters.value.commentsValue
    }

    if (filters.value.search?.trim()) {
      apiFilters.search = filters.value.search.trim()
    }

    // Add my_recipes filter (always pass it when user is authenticated)
    if (authStore.isAuthenticated) {
      apiFilters.my_recipes = filters.value.myRecipes ? '1' : '0'
    }

    const response = await publicRecipeApi.getAll(apiFilters, page)
    publicRecipes.value = response.data.data
    publicPagination.value = response.data.meta
  } catch (error) {
    console.error('Error fetching recipes:', error)
  } finally {
    publicLoading.value = false
    isFetching = false
  }
}

const applyFilters = () => {
  publicPagination.value.current_page = 1
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
    ratingOperator: 'exact',
    ratingValue: null,
    commentsOperator: 'exact',
    commentsValue: null,
    sortBy: 'recent',
    myRecipes: false,
  }
  applyFilters()
}

const toggleLayout = () => {
  const newLayout = layoutMode.value === 'grid' ? 'list' : 'grid'
  layoutMode.value = newLayout

  // When switching layouts, set perPage to a valid value for the new layout
  // If current perPage is valid for new layout, keep it; otherwise use default (15)
  const validOptions = newLayout === 'grid' ? gridPerPageOptions : listPerPageOptions
  if (!validOptions.includes(perPage.value)) {
    // Use 15 if available, otherwise use first option
    perPage.value = validOptions.includes(15) ? 15 : validOptions[0]
  }

  applyFilters()
}

const updatePerPage = (value) => {
  perPage.value = Number(value)
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

// Watch for filter changes (excluding search, which has debounce in RecipeFilters)
let watchDebounceTimer = null
watch(() => [
  filters.value.categoryId,
  filters.value.servingsOperator,
  filters.value.servingsValue,
  filters.value.prepTimeOperator,
  filters.value.prepTimeValue,
  filters.value.ratingOperator,
  filters.value.ratingValue,
  filters.value.commentsOperator,
  filters.value.commentsValue,
  filters.value.sortBy,
  filters.value.myRecipes,
  perPage.value,
], () => {
  // Small debounce to prevent duplicate calls
  clearTimeout(watchDebounceTimer)
  watchDebounceTimer = setTimeout(() => {
    applyFilters()
  }, 100)
}, { deep: false })
</script>

<template>
  <div class="flex flex-col lg:flex-row gap-2 sm:gap-4 min-h-screen">
    <!-- Mobile Filter Toggle -->
    <div
      class="lg:hidden flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0 mb-2 sm:mb-0">
      <h1 class="text-2xl sm:text-3xl font-bold">{{ $t('recipe.title') }}</h1>
      <div class="flex gap-2 w-full sm:w-auto">
        <Button variant="secondary" class="flex-1 sm:flex-none" @click="showSidebar = !showSidebar">
          <FontAwesomeIcon :icon="faFilter" class="sm:mr-2" />
          <span class="hidden sm:inline">{{ $t('filter.title') }}</span>
        </Button>
        <Button v-if="authStore.isAuthenticated" variant="primary" class="flex-1 sm:flex-none text-xs sm:text-base"
          @click="$router.push('/recipes/create')">
          <span class="hidden sm:inline">{{ $t('recipe.create') }}</span>
          <span class="sm:hidden">{{ $t('common.save') }}</span>
        </Button>
        <Button v-else variant="primary" class="flex-1 sm:flex-none text-xs sm:text-base"
          @click="$router.push('/login')">
          {{ $t('auth.login') }}
        </Button>
      </div>
    </div>

    <!-- Sidebar Filters - Mobile Overlay -->
    <div v-if="showSidebar" class="lg:hidden fixed inset-0 z-50 bg-black bg-opacity-50" @click="showSidebar = false">
    </div>
    <aside :class="[
      'w-full lg:w-96 flex-shrink-0 lg:sticky lg:top-4 h-fit',
      'lg:block',
      showSidebar ? 'fixed lg:relative inset-y-0 left-0 z-50 lg:z-auto overflow-y-auto bg-base-100 lg:bg-transparent p-4 lg:p-0' : 'hidden lg:block'
    ]">
      <div class="lg:hidden flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">{{ $t('filter.title') }}</h2>
        <Button variant="ghost" size="sm" @click="showSidebar = false">
          ✕
        </Button>
      </div>
      <RecipeFilters v-model="filters" :show-my-recipes="authStore.isAuthenticated" @clear="clearFilters"
        @apply="applyFilters" />
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0">
      <div class="hidden lg:flex justify-between items-center mb-4 lg:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ $t('recipe.title') }}</h1>
        <div class="flex gap-2 items-center flex-wrap">
          <!-- Layout Toggle -->
          <div class="join">
            <Button :variant="layoutMode === 'grid' ? 'primary' : 'ghost'" @click="toggleLayout" class="join-item">
              <FontAwesomeIcon :icon="faTh" />
            </Button>
            <Button :variant="layoutMode === 'list' ? 'primary' : 'ghost'" @click="toggleLayout" class="join-item">
              <FontAwesomeIcon :icon="faList" />
            </Button>
          </div>
          <!-- Per Page Selector -->
          <Select :model-value="perPage" @update:model-value="updatePerPage" :options="layoutMode === 'grid'
            ? gridPerPageOptions.map(v => ({ value: Number(v), label: `${v} ${t('layout.perPage')}` }))
            : listPerPageOptions.map(v => ({ value: Number(v), label: `${v} ${t('layout.perPage')}` }))"
            class="w-32 sm:w-40" />
          <Button v-if="authStore.isAuthenticated" variant="primary" class="hidden lg:inline-flex"
            @click="$router.push('/recipes/create')">
            {{ $t('recipe.create') }}
          </Button>
          <Button v-else variant="primary" class="hidden lg:inline-flex" @click="$router.push('/login')">
            {{ $t('auth.login') }}
          </Button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <!-- Empty State -->
      <div v-else-if="recipes.length === 0" class="text-center py-8">
        <p class="text-lg">{{ $t('recipe.noRecipes') }}</p>
      </div>

      <!-- Recipes Grid Layout -->
      <div v-else-if="layoutMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <Card v-for="recipe in recipes" :key="recipe.id"
          class="hover:shadow-xl sm:hover:shadow-2xl transition-shadow cursor-pointer" bordered
          @click="$router.push(`/public/recipes/${recipe.id}`)">
          <div class="space-y-2">
            <img v-if="recipe.image" :src="recipe.image" :alt="recipe.name || 'Recipe image'"
              class="w-full h-40 sm:h-48 object-cover rounded-lg mb-2" />
            <div class="flex items-start justify-between gap-2">
              <h3 class="text-base sm:text-lg font-bold flex-1">{{ recipe.name || $t('recipe.noName') }}</h3>
              <div v-if="recipe.category" class="badge badge-primary text-xs">
                {{ recipe.category.name }}
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm">
              <span v-if="recipe.prep_time_minutes" class="flex items-center gap-1">
                <FontAwesomeIcon :icon="faClock" class="text-xs" />
                {{ formatTime(recipe.prep_time_minutes) }}
              </span>
              <span v-if="recipe.servings" class="flex items-center gap-1">
                <FontAwesomeIcon :icon="faUsers" class="text-xs" />
                {{ recipe.servings }} {{ $t('recipe.servingsUnit') }}
              </span>
            </div>

            <!-- Rating -->
            <div v-if="recipe.average_rating" class="flex items-center gap-2 text-xs sm:text-sm">
              <div class="flex items-center gap-0.5 sm:gap-1">
                <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).full" :key="`full-${i}`" :icon="faStar"
                  class="text-yellow-400 text-xs sm:text-sm" />
                <FontAwesomeIcon v-if="renderStars(recipe.average_rating).half" :icon="faStar"
                  class="text-yellow-400 text-xs sm:text-sm" style="clip-path: inset(0 50% 0 0);" />
                <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).empty" :key="`empty-${i}`"
                  :icon="faStar" class="text-gray-300 text-xs sm:text-sm" />
              </div>
              <span>{{ recipe.average_rating.toFixed(1) }}</span>
              <span v-if="recipe.ratings_count" class="text-gray-500">
                ({{ recipe.ratings_count }})
              </span>
            </div>

            <!-- Comments Count -->
            <div v-if="recipe.comments_count" class="flex items-center gap-1 text-xs sm:text-sm text-gray-500">
              <FontAwesomeIcon :icon="faComments" class="text-xs" />
              <span>{{ recipe.comments_count }} {{ recipe.comments_count !== 1 ? $t('recipe.comments') :
                $t('recipe.comment') }}</span>
            </div>

            <!-- Author -->
            <div v-if="recipe.user" class="text-xs sm:text-sm text-gray-500">
              {{ $t('recipe.by') }} {{ recipe.user.name }}
            </div>
          </div>
        </Card>
      </div>

      <!-- Recipes List Layout -->
      <div v-else class="space-y-3 sm:space-y-4">
        <Card v-for="recipe in recipes" :key="recipe.id" class="hover:shadow-lg transition-shadow cursor-pointer"
          bordered @click="$router.push(`/public/recipes/${recipe.id}`)">
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <!-- Image -->
            <div class="flex-shrink-0 w-full sm:w-24 md:w-32">
              <img v-if="recipe.image" :src="recipe.image" :alt="recipe.name || 'Recipe image'"
                class="w-full h-40 sm:h-24 md:h-32 object-cover rounded-lg" />
              <div v-else class="w-full h-40 sm:h-24 md:h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                <span class="text-gray-400 text-xs sm:text-sm">Sem imagem</span>
              </div>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0 space-y-2">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <h3 class="text-lg sm:text-xl font-bold flex-1">{{ recipe.name || $t('recipe.noName') }}</h3>
                <!-- Category -->
                <div class="flex-shrink-0">
                  <div v-if="recipe.category" class="badge badge-primary text-xs">
                    {{ recipe.category.name }}
                  </div>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm">
                <span v-if="recipe.prep_time_minutes" class="flex items-center gap-1">
                  <FontAwesomeIcon :icon="faClock" class="text-xs" />
                  {{ formatTime(recipe.prep_time_minutes) }}
                </span>
                <span v-if="recipe.servings" class="flex items-center gap-1">
                  <FontAwesomeIcon :icon="faUsers" class="text-xs" />
                  {{ recipe.servings }} {{ $t('recipe.servingsUnit') }}
                </span>
              </div>

              <!-- Rating -->
              <div v-if="recipe.average_rating" class="flex items-center gap-2 text-xs sm:text-sm">
                <div class="flex items-center gap-0.5 sm:gap-1">
                  <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).full" :key="`full-${i}`"
                    :icon="faStar" class="text-yellow-400 text-xs sm:text-sm" />
                  <FontAwesomeIcon v-if="renderStars(recipe.average_rating).half" :icon="faStar"
                    class="text-yellow-400 text-xs sm:text-sm" style="clip-path: inset(0 50% 0 0);" />
                  <FontAwesomeIcon v-for="i in renderStars(recipe.average_rating).empty" :key="`empty-${i}`"
                    :icon="faStar" class="text-gray-300 text-xs sm:text-sm" />
                </div>
                <span>{{ recipe.average_rating.toFixed(1) }}</span>
                <span v-if="recipe.ratings_count" class="text-gray-500">
                  ({{ recipe.ratings_count }})
                </span>
              </div>

              <!-- Comments Count -->
              <div v-if="recipe.comments_count" class="flex items-center gap-1 text-xs sm:text-sm text-gray-500">
                <FontAwesomeIcon :icon="faComments" class="text-xs" />
                <span>{{ recipe.comments_count }} {{ recipe.comments_count !== 1 ? $t('recipe.comments') :
                  $t('recipe.comment') }}</span>
              </div>

              <!-- Author -->
              <div v-if="recipe.user" class="text-xs sm:text-sm text-gray-500">
                {{ $t('recipe.by') }} {{ recipe.user.name }}
              </div>

              <!-- Instructions Preview -->
              <div v-if="recipe.instructions"
                class="text-xs sm:text-sm text-base-content line-clamp-3 sm:line-clamp-5 prose prose-sm max-w-none overflow-hidden [&_h1]:text-xs sm:[&_h1]:text-sm [&_h1]:font-bold [&_h1]:my-1 [&_h2]:text-xs [&_h2]:font-bold [&_h2]:my-1 [&_h3]:text-xs [&_h3]:font-bold [&_h3]:my-1 [&_p]:my-1 [&_ul]:list-disc [&_ul]:pl-3 sm:[&_ul]:pl-4 [&_ul]:my-1 [&_ol]:list-decimal [&_ol]:pl-3 sm:[&_ol]:pl-4 [&_ol]:my-1 [&_li]:my-0.5"
                v-html="recipe.instructions"></div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center mt-4 sm:mt-6">
        <div class="join">
          <Button variant="ghost" size="sm" class="text-xs sm:text-base" :disabled="pagination.current_page === 1"
            @click="changePage(pagination.current_page - 1)">
            «
          </Button>
          <Button variant="ghost" size="sm" class="join-item text-xs sm:text-base">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </Button>
          <Button variant="ghost" size="sm" class="text-xs sm:text-base"
            :disabled="pagination.current_page === pagination.last_page"
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

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-5 {
  display: -webkit-box;
  -webkit-line-clamp: 5;
  line-clamp: 5;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
