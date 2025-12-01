<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useRecipeStore } from '@/stores/recipe'
import { useI18n } from 'vue-i18n'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Modal from '@/components/daisyui/Modal.vue'

const route = useRoute()
const router = useRouter()
const recipeStore = useRecipeStore()
const { t } = useI18n()

const { currentRecipe: recipe, loading } = storeToRefs(recipeStore)
const showDeleteModal = ref(false)
const deleting = ref(false)

const fetchRecipeData = () => {
  const recipeId = Number(route.params.id)
  if (recipeId) {
    recipeStore.fetchRecipe(recipeId)
  }
}

const handlePrint = () => {
  window.print()
}

const openDeleteModal = () => {
  showDeleteModal.value = true
}

const handleDeleteConfirm = async () => {
  deleting.value = true
  const recipeId = Number(route.params.id)
  const result = await recipeStore.deleteRecipe(recipeId)
  deleting.value = false

  if (result.success) {
    showDeleteModal.value = false
    router.push('/')
  }
}

const handleDeleteCancel = () => {
  showDeleteModal.value = false
}

onMounted(() => {
  fetchRecipeData()
})

// Watch for route changes to reload recipe when navigating between recipes
watch(() => route.params.id, () => {
  fetchRecipeData()
})
</script>

<template>
  <div class="container mx-auto p-4 max-w-4xl">
    <div v-if="loading" class="text-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="recipe">
      <div class="flex justify-between items-start mb-6 print:hidden">
        <h1 class="text-3xl font-bold text-base-content">{{ recipe.name || $t('recipe.title') }}</h1>
        <div class="flex gap-2">
          <Button variant="primary" @click="handlePrint">
            {{ $t('recipe.print') }}
          </Button>
          <Button variant="secondary" @click="$router.push(`/recipes/${recipe.id}/edit`)">
            {{ $t('common.edit') }}
          </Button>
          <Button variant="error" @click="openDeleteModal">
            {{ $t('common.delete') }}
          </Button>
        </div>
      </div>

      <div class="space-y-4">
        <img
          v-if="recipe.image"
          :src="recipe.image"
          :alt="recipe.name || 'Recipe image'"
          class="w-full h-96 object-cover rounded-lg"
        />
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
          <h2 class="text-xl font-bold mb-2 text-base-content">{{ $t('recipe.ingredients') }}</h2>
          <div
            class="max-w-none [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h1]:text-base-content [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h2]:text-base-content [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_h3]:text-base-content [&_p]:my-2 [&_p]:text-base-content [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_li]:text-base-content [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_blockquote]:text-base-content [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
            v-html="recipe.ingredients"></div>
        </div>

        <div>
          <h2 class="text-xl font-bold mb-2 text-base-content">{{ $t('recipe.instructions') }}</h2>
          <div
            class="max-w-none [&_h1]:text-4xl [&_h1]:font-bold [&_h1]:my-4 [&_h1]:text-base-content [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:my-3 [&_h2]:text-base-content [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:my-2 [&_h3]:text-base-content [&_p]:my-2 [&_p]:text-base-content [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2 [&_li]:my-1 [&_li]:text-base-content [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-4 [&_blockquote]:my-4 [&_blockquote]:italic [&_blockquote]:text-base-content [&_hr]:border-t [&_hr]:border-base-300 [&_hr]:my-4 [&_a]:link [&_a]:link-primary"
            v-html="recipe.instructions"></div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <p>{{ $t('recipe.noRecipes') }}</p>
      <Button variant="primary" @click="$router.push('/')" class="mt-4">
        {{ $t('common.back') }}
      </Button>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal v-model="showDeleteModal" :title="$t('recipe.deleteConfirm')" :confirm-text="$t('common.delete')"
      :cancel-text="$t('common.cancel')" confirm-variant="error" :loading="deleting" @confirm="handleDeleteConfirm"
      @cancel="handleDeleteCancel">
      <p>{{ $t('recipe.deleteConfirmMessage') }}</p>
    </Modal>
  </div>
</template>