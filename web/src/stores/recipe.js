import { defineStore } from 'pinia'
import { ref } from 'vue'
import { recipeApi } from '../api/recipe'

export const useRecipeStore = defineStore('recipe', () => {
  const recipes = ref([])
  const currentRecipe = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  })

  // Fetch all recipes
  const fetchRecipes = async (search = '', page = 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await recipeApi.getAll(search)
      recipes.value = response.data.data || []
      pagination.value = response.data.meta || {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: recipes.value.length,
      }
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error fetching recipes'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Fetch single recipe
  const fetchRecipe = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await recipeApi.getById(id)
      currentRecipe.value = response.data.data
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error fetching recipe'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Create recipe
  const createRecipe = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await recipeApi.create(data)
      recipes.value.unshift(response.data.data)
      return { success: true, data: response.data.data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error creating recipe'
      const validationErrors = err.response?.data?.errors || {}
      return { success: false, message: error.value, errors: validationErrors }
    } finally {
      loading.value = false
    }
  }

  // Update recipe
  const updateRecipe = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await recipeApi.update(id, data)
      const index = recipes.value.findIndex(r => r.id === id)
      if (index !== -1) {
        recipes.value[index] = response.data.data
      }
      if (currentRecipe.value?.id === id) {
        currentRecipe.value = response.data.data
      }
      return { success: true, data: response.data.data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error updating recipe'
      const validationErrors = err.response?.data?.errors || {}
      return { success: false, message: error.value, errors: validationErrors }
    } finally {
      loading.value = false
    }
  }

  // Delete recipe
  const deleteRecipe = async (id) => {
    loading.value = true
    error.value = null
    try {
      await recipeApi.delete(id)
      recipes.value = recipes.value.filter(r => r.id !== id)
      if (currentRecipe.value?.id === id) {
        currentRecipe.value = null
      }
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error deleting recipe'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    recipes,
    currentRecipe,
    loading,
    error,
    pagination,
    fetchRecipes,
    fetchRecipe,
    createRecipe,
    updateRecipe,
    deleteRecipe,
  }
})

