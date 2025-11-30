import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryApi } from '../api/category'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Fetch all categories
  const fetchCategories = async () => {
    if (categories.value.length > 0) return categories.value
    
    loading.value = true
    error.value = null
    try {
      const response = await categoryApi.getAll()
      categories.value = response.data.data
      return categories.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error fetching categories'
      return []
    } finally {
      loading.value = false
    }
  }

  return {
    categories,
    loading,
    error,
    fetchCategories,
  }
})

