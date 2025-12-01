<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { publicRecipeApi } from '@/api/recipe'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faStar, faComments, faTrash } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Input from '@/components/daisyui/Input.vue'
import Textarea from '@/components/daisyui/Textarea.vue'

const route = useRoute()
const router = useRouter()
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
    alert('Erro ao adicionar comentário')
  } finally {
    submittingComment.value = false
  }
}

const deleteComment = async (commentId) => {
  if (!confirm('Tem certeza que deseja excluir este comentário?')) {
    return
  }

  try {
    await publicRecipeApi.deleteComment(recipe.value.id, commentId)
    comments.value = comments.value.filter(c => c.id !== commentId)
  } catch (error) {
    console.error('Error deleting comment:', error)
    alert('Erro ao excluir comentário')
  }
}

const submitRating = async (rating) => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  if (recipe.value.user_id === authStore.user.id) {
    alert('Você não pode avaliar sua própria receita')
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
      alert('Você não pode avaliar sua própria receita')
    } else {
      alert('Erro ao avaliar receita')
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
        <Button variant="ghost" @click="$router.push('/')" class="mb-4">
          ← Voltar
        </Button>
        <h1 class="text-3xl font-bold">{{ recipe.name || 'Receita sem nome' }}</h1>
        <div v-if="recipe.user" class="text-gray-500 mt-2">
          Por: {{ recipe.user.name }}
        </div>
      </div>

      <!-- Image -->
      <img
        v-if="recipe.image"
        :src="recipe.image"
        :alt="recipe.name || 'Recipe image'"
        class="w-full h-96 object-cover rounded-lg mb-4"
      />

      <!-- Category Badge -->
      <div v-if="recipe.category" class="mb-4">
        <span class="badge badge-primary badge-lg">{{ recipe.category.name }}</span>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div v-if="recipe.prep_time_minutes" class="stat">
          <div class="stat-title">Tempo de preparo</div>
          <div class="stat-value text-lg">{{ formatTime(recipe.prep_time_minutes) }}</div>
        </div>
        <div v-if="recipe.servings" class="stat">
          <div class="stat-title">Porções</div>
          <div class="stat-value text-lg">{{ recipe.servings }}</div>
        </div>
      </div>

      <!-- Rating Section -->
      <Card bordered class="mb-6">
        <template #title>
          <div class="flex items-center justify-between">
            <span>Avaliação</span>
            <div v-if="averageRating > 0" class="flex items-center gap-2">
              <div class="flex items-center gap-1">
                <FontAwesomeIcon
                  v-for="i in renderStars(averageRating).full"
                  :key="`full-${i}`"
                  :icon="faStar"
                  class="text-yellow-400"
                />
                <FontAwesomeIcon
                  v-if="renderStars(averageRating).half"
                  :icon="faStar"
                  class="text-yellow-400"
                  style="clip-path: inset(0 50% 0 0);"
                />
                <FontAwesomeIcon
                  v-for="i in renderStars(averageRating).empty"
                  :key="`empty-${i}`"
                  :icon="faStar"
                  class="text-gray-300"
                />
              </div>
              <span class="font-bold">{{ averageRating.toFixed(1) }}</span>
              <span v-if="ratingsCount" class="text-sm text-gray-500">
                ({{ ratingsCount }} avaliação{{ ratingsCount !== 1 ? 'ões' : '' }})
              </span>
            </div>
          </div>
        </template>
        <div v-if="authStore.isAuthenticated && recipe.user_id !== authStore.user.id">
          <p class="mb-2">Sua avaliação:</p>
          <div class="flex gap-1">
            <button
              v-for="i in 5"
              :key="i"
              @click="submitRating(i)"
              :disabled="submittingRating"
              class="text-2xl transition-colors"
              :class="i <= selectedRating ? 'text-yellow-400' : 'text-gray-300'"
            >
              <FontAwesomeIcon :icon="faStar" />
            </button>
          </div>
        </div>
        <div v-else-if="!authStore.isAuthenticated" class="text-gray-500">
          <a href="/login" class="link link-primary">Faça login</a> para avaliar esta receita
        </div>
        <div v-else class="text-gray-500">
          Você não pode avaliar sua própria receita
        </div>
      </Card>

      <!-- Ingredients -->
      <div v-if="recipe.ingredients" class="mb-6">
        <h2 class="text-xl font-bold mb-2">Ingredientes</h2>
        <div
          class="max-w-none prose prose-lg [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
          v-html="recipe.ingredients"
        ></div>
      </div>

      <!-- Instructions -->
      <div class="mb-6">
        <h2 class="text-xl font-bold mb-2">Modo de preparo</h2>
        <div
          class="max-w-none prose prose-lg [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
          v-html="recipe.instructions"
        ></div>
      </div>

      <!-- Comments Section -->
      <Card bordered>
        <template #title>
          <div class="flex items-center gap-2">
            <FontAwesomeIcon :icon="faComments" />
            <span>Comentários ({{ comments.length }})</span>
          </div>
        </template>

        <!-- Add Comment Form -->
        <div v-if="authStore.isAuthenticated" class="mb-6">
          <Textarea
            v-model="newComment"
            placeholder="Deixe um comentário..."
            :rows="4"
          />
          <Button
            variant="primary"
            class="mt-2"
            :loading="submittingComment"
            @click="submitComment"
          >
            Enviar comentário
          </Button>
        </div>
        <div v-else class="mb-6 text-gray-500">
          <a href="/login" class="link link-primary">Faça login</a> para deixar um comentário
        </div>

        <!-- Comments List -->
        <div v-if="comments.length === 0" class="text-center py-4 text-gray-500">
          Nenhum comentário ainda. Seja o primeiro a comentar!
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="comment in comments"
            :key="comment.id"
            class="border-b border-base-300 pb-4 last:border-0"
          >
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="font-bold">{{ comment.user.name }}</p>
                <p class="text-sm text-gray-500">
                  {{ new Date(comment.created_at).toLocaleDateString('pt-BR') }}
                </p>
              </div>
              <Button
                v-if="canDeleteComment(comment)"
                variant="ghost"
                size="sm"
                @click="deleteComment(comment.id)"
              >
                <FontAwesomeIcon :icon="faTrash" />
              </Button>
            </div>
            <p class="whitespace-pre-wrap">{{ comment.comment }}</p>
          </div>
        </div>
      </Card>
    </div>

    <div v-else class="text-center py-8">
      <p>Receita não encontrada</p>
      <Button variant="primary" @click="$router.push('/')" class="mt-4">
        Voltar
      </Button>
    </div>
  </div>
</template>

