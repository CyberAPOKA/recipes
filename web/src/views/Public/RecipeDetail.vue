<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { publicRecipeApi, recipeApi } from '@/api/recipe'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faStar, faComments, faTrash, faEdit, faDownload, faPrint } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Input from '@/components/daisyui/Input.vue'
import Textarea from '@/components/daisyui/Textarea.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()

const recipe = ref(null)
const loading = ref(false)
const comments = ref([])
const userRating = ref(null)
const averageRating = ref(0)
const ratingsCount = ref(0)

const newComment = ref('')
const submittingComment = ref(false)
const selectedRating = ref(0)
const submittingRating = ref(false)

const fetchRecipe = async () => {
  loading.value = true
  try {
    const recipeId = Number(route.params.id)
    const response = await publicRecipeApi.getById(recipeId)
    recipe.value = response.data.data
    comments.value = recipe.value.comments || []
    averageRating.value = recipe.value.average_rating || 0
    ratingsCount.value = recipe.value.ratings_count || 0

    // Fetch user's rating if authenticated
    if (authStore.isAuthenticated) {
      try {
        const ratingResponse = await publicRecipeApi.getRating(recipeId)
        if (ratingResponse.data.data) {
          userRating.value = ratingResponse.data.data.rating
          selectedRating.value = ratingResponse.data.data.rating
        }
      } catch (error) {
        // User hasn't rated yet
        userRating.value = null
      }
    }
  } catch (error) {
    console.error('Error fetching recipe:', error)
  } finally {
    loading.value = false
  }
}

const submitComment = async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  if (!newComment.value.trim()) {
    return
  }

  submittingComment.value = true
  try {
    const response = await publicRecipeApi.addComment(recipe.value.id, newComment.value.trim())
    comments.value.unshift(response.data.data)
    newComment.value = ''
  } catch (error) {
    console.error('Error submitting comment:', error)
    alert(t('recipe.addCommentError'))
  } finally {
    submittingComment.value = false
  }
}

const deleteComment = async (commentId) => {
  if (!confirm(t('recipe.deleteCommentConfirm'))) {
    return
  }

  try {
    await publicRecipeApi.deleteComment(recipe.value.id, commentId)
    comments.value = comments.value.filter(c => c.id !== commentId)
  } catch (error) {
    console.error('Error deleting comment:', error)
    alert(t('recipe.deleteCommentError'))
  }
}

const submitRating = async (rating) => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  if (recipe.value.user_id === authStore.user.id) {
    alert(t('recipe.rateOwnRecipeError'))
    return
  }

  submittingRating.value = true
  try {
    const response = await publicRecipeApi.addRating(recipe.value.id, rating)
    userRating.value = rating
    selectedRating.value = rating
    averageRating.value = response.data.data.average_rating
  } catch (error) {
    console.error('Error submitting rating:', error)
    if (error.response?.status === 403) {
      alert(t('recipe.rateOwnRecipeError'))
    } else {
      alert(t('recipe.rateError'))
    }
  } finally {
    submittingRating.value = false
  }
}

const formatTime = (minutes) => {
  if (!minutes) return '-'
  if (minutes < 60) return `${minutes} min`
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`
}

const renderStars = (rating, interactive = false) => {
  const fullStars = Math.floor(rating)
  const hasHalfStar = rating % 1 >= 0.5
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0)

  return {
    full: fullStars,
    half: hasHalfStar ? 1 : 0,
    empty: emptyStars,
  }
}

const canDeleteComment = (comment) => {
  return authStore.isAuthenticated && (
    comment.user.id === authStore.user.id ||
    recipe.value.user_id === authStore.user.id
  )
}

const isRecipeOwner = computed(() => {
  return authStore.isAuthenticated && recipe.value && recipe.value.user_id === authStore.user.id
})

const downloadPdf = async () => {
  if (!recipe.value) return

  try {
    const response = await publicRecipeApi.downloadPdf(recipe.value.id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${(recipe.value.name || t('recipe.noName')).replace(/[^a-z0-9]/gi, '_').toLowerCase()}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading PDF:', error)
    alert(t('recipe.downloadPdfError'))
  }
}

const printRecipe = () => {
  window.print()
}

const deleteRecipe = async () => {
  if (!confirm(t('recipe.deleteConfirmMessage'))) {
    return
  }

  try {
    await recipeApi.delete(recipe.value.id)
    alert(t('recipe.deleteSuccess'))
    router.push('/')
  } catch (error) {
    console.error('Error deleting recipe:', error)
    alert(t('recipe.deleteError'))
  }
}

onMounted(() => {
  fetchRecipe()
})

watch(() => route.params.id, () => {
  fetchRecipe()
})
</script>

<template>
  <div class="container mx-auto p-4 max-w-4xl">
    <div v-if="loading" class="text-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="recipe">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex justify-between items-start mb-4">
          <Button variant="ghost" class="no-print" @click="$router.push('/')">
            ← {{ $t('recipe.back') }}
          </Button>
          <div class="flex gap-2  no-print">
            <Button variant="secondary" @click="printRecipe">
              <FontAwesomeIcon :icon="faPrint" class="mr-2" />
              {{ $t('recipe.print') }}
            </Button>
            <Button variant="secondary" @click="downloadPdf">
              <FontAwesomeIcon :icon="faDownload" class="mr-2" />
              {{ $t('recipe.pdf') }}
            </Button>
            <Button v-if="isRecipeOwner" variant="primary" @click="$router.push(`/recipes/${recipe.id}/edit`)">
              <FontAwesomeIcon :icon="faEdit" class="mr-2" />
              {{ $t('recipe.edit') }}
            </Button>
            <Button v-if="isRecipeOwner" variant="error" @click="deleteRecipe">
              <FontAwesomeIcon :icon="faTrash" class="mr-2" />
              {{ $t('recipe.delete') }}
            </Button>
          </div>
        </div>
        <h1 class="text-3xl font-bold">{{ recipe.name || $t('recipe.noName') }}</h1>
        <div v-if="recipe.user" class="text-gray-500 mt-2">
          {{ $t('recipe.by') }} {{ recipe.user.name }}
        </div>
      </div>

      <!-- Image -->
      <img v-if="recipe.image" :src="recipe.image" :alt="recipe.name || 'Recipe image'"
        class="w-full h-96 object-cover rounded-lg mb-4" />

      <!-- Category Badge -->
      <div v-if="recipe.category" class="mb-4">
        <span class="badge badge-primary badge-lg">{{ recipe.category.name }}</span>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div v-if="recipe.prep_time_minutes" class="stat">
          <div class="stat-title">{{ $t('recipe.prepTimeLabel') }}</div>
          <div class="stat-value text-lg">{{ formatTime(recipe.prep_time_minutes) }}</div>
        </div>
        <div v-if="recipe.servings" class="stat">
          <div class="stat-title">{{ $t('recipe.servingsLabel') }}</div>
          <div class="stat-value text-lg">{{ recipe.servings }}</div>
        </div>
      </div>

      <!-- Ingredients -->
      <div v-if="recipe.ingredients" class="mb-6">
        <h2 class="text-xl font-bold mb-2">{{ $t('recipe.ingredientsLabel') }}</h2>
        <div
          class="max-w-none prose prose-lg [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
          v-html="recipe.ingredients"></div>
      </div>

      <!-- Instructions -->
      <div class="mb-6">
        <h2 class="text-xl font-bold mb-2">{{ $t('recipe.instructionsLabel') }}</h2>
        <div
          class="max-w-none prose prose-lg [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
          v-html="recipe.instructions"></div>
      </div>

      <!-- Rating Section - Only show if there are ratings or user can rate (not owner) -->
      <Card v-if="(Number(averageRating) > 0 || Number(ratingsCount) > 0) || !isRecipeOwner" bordered
        class="mb-6 no-print">

        <div class="flex items-center justify-between">
          <span>{{ $t('rating.title') }}</span>
          <div v-if="Number(averageRating) > 0 || Number(ratingsCount) > 0" class="flex items-center gap-2">
            <div class="flex items-center gap-1">
              <FontAwesomeIcon v-for="i in renderStars(averageRating).full" :key="`full-${i}`" :icon="faStar"
                class="text-yellow-400" />
              <FontAwesomeIcon v-if="renderStars(averageRating).half" :icon="faStar" class="text-yellow-400"
                style="clip-path: inset(0 50% 0 0);" />
              <FontAwesomeIcon v-for="i in renderStars(averageRating).empty" :key="`empty-${i}`" :icon="faStar"
                class="text-gray-300" />
            </div>
            <span class="font-bold">{{ averageRating.toFixed(1) }}</span>
            <span v-if="ratingsCount" class="text-sm text-gray-500">
              ({{ ratingsCount }} {{ ratingsCount !== 1 ? $t('rating.ratings') : $t('rating.rating') }})
            </span>
          </div>
        </div>

        <div v-if="authStore.isAuthenticated && recipe.user_id !== authStore.user.id">
          <p class="mb-2">{{ $t('rating.yourRating') }}</p>
          <div class="flex gap-1">
            <button v-for="i in 5" :key="i" @click="submitRating(i)" :disabled="submittingRating"
              class="text-2xl transition-colors" :class="i <= selectedRating ? 'text-yellow-400' : 'text-gray-300'">
              <FontAwesomeIcon :icon="faStar" />
            </button>
          </div>
        </div>
        <div v-else-if="!authStore.isAuthenticated" class="text-gray-500">
          <a href="/login" class="link link-primary">{{ $t('auth.login') }}</a> {{ $t('rating.loginToRate') }}
        </div>
      </Card>

      <!-- Comments Section -->
      <Card bordered class="no-print">
        <template #title>
          <div class="flex items-center gap-2">
            <FontAwesomeIcon :icon="faComments" />
            <span>{{ $t('comment.title') }} ({{ comments.length }})</span>
          </div>
        </template>

        <!-- Add Comment Form -->
        <div v-if="authStore.isAuthenticated" class="mb-6">
          <Textarea v-model="newComment" :placeholder="$t('comment.placeholder')" :rows="4" />
          <Button variant="primary" class="mt-2" :loading="submittingComment" @click="submitComment">
            {{ $t('comment.submit') }}
          </Button>
        </div>
        <div v-else class="mb-6 text-gray-500">
          <a href="/login" class="link link-primary">{{ $t('auth.login') }}</a> {{ $t('comment.loginToComment') }}
        </div>

        <!-- Comments List -->
        <div v-if="comments.length === 0" class="text-center py-4 text-gray-500">
          {{ $t('comment.none') }}
        </div>
        <div v-else class="space-y-4">
          <div v-for="comment in comments" :key="comment.id" class="border-b border-base-300 pb-4 last:border-0">
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="font-bold">{{ comment.user.name }}</p>
                <p class="text-sm text-gray-500">
                  {{ new Date(comment.created_at).toLocaleDateString('pt-BR') }}
                </p>
              </div>
              <Button v-if="canDeleteComment(comment)" variant="ghost" size="sm" @click="deleteComment(comment.id)">
                <FontAwesomeIcon :icon="faTrash" />
              </Button>
            </div>
            <p class="whitespace-pre-wrap">{{ comment.comment }}</p>
          </div>
        </div>
      </Card>
    </div>

    <div v-else class="text-center py-8">
      <p>{{ $t('recipe.notFound') }}</p>
      <Button variant="primary" @click="$router.push('/')" class="mt-4">
        {{ $t('recipe.back') }}
      </Button>
    </div>
  </div>
</template>

<style scoped>
/* Print Styles - Only print the recipe content */
@media print {

  /* Hide navigation, layout, buttons, and other non-recipe elements */
  nav,
  .navbar,
  .no-print,
  button,
  .btn,
  [class*="button"],
  [class*="Button"],
  [role="button"],
  input[type="button"],
  input[type="submit"],
  a[role="button"] {
    display: none !important;
    visibility: hidden !important;
  }

  /* Hide everything by default */
  body * {
    visibility: hidden;
  }

  /* Show only the recipe content */
  .container,
  .container * {
    visibility: visible;
  }

  /* Position the recipe at the top */
  .container {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    max-width: 100%;
    padding: 20px;
    margin: 0;
    background: white;
  }

  /* Ensure images print properly */
  img {
    max-width: 100%;
    height: auto;
    page-break-inside: avoid;
  }

  /* Page break control */
  h1,
  h2 {
    page-break-after: avoid;
  }

  /* Ensure content doesn't break awkwardly */
  .prose {
    page-break-inside: avoid;
  }

  /* Remove shadows and borders for cleaner print */
  .card,
  .shadow-lg,
  .shadow-xl {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }

  /* Ensure text is black for print */
  * {
    color: #000 !important;
    background: white !important;
  }
}

/* Ensure print button is visible on screen */
@media screen {
  .no-print {
    display: block;
  }
}
</style>
