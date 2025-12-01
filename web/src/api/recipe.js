import api from './axios'

const prepareFormData = (data) => {
  const formData = new FormData()

  Object.keys(data).forEach(key => {
    if (data[key] !== null && data[key] !== undefined) {
      if (key === 'image' && data[key] instanceof File) {
        formData.append(key, data[key])
      } else if (typeof data[key] === 'object') {
        formData.append(key, JSON.stringify(data[key]))
      } else {
        formData.append(key, data[key])
      }
    }
  })

  return formData
}

export const recipeApi = {
  getAll: (search = '', page = 1, filters = {}) => {
    const params = { page }
    if (search) {
      params.search = search
    }
    // Add filters if provided
    if (filters.categoryId) {
      params.category_id = filters.categoryId
    }
    if (filters.servingsValue) {
      params.servings_operator = filters.servingsOperator || 'exact'
      params.servings_value = filters.servingsValue
    }
    if (filters.prepTimeValue) {
      params.prep_time_operator = filters.prepTimeOperator || 'exact'
      params.prep_time_value = filters.prepTimeValue
    }
    if (filters.search) {
      params.search = filters.search
    }
    return api.get('/recipes', { params })
  },
  getById: (id) => api.get(`/recipes/${id}`),
  create: (data) => {
    const hasFile = data.image instanceof File
    if (hasFile) {
      return api.post('/recipes', prepareFormData(data), {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }
    return api.post('/recipes', data)
  },
  update: (id, data) => {
    const hasFile = data.image instanceof File
    if (hasFile) {
      return api.put(`/recipes/${id}`, prepareFormData(data), {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }
    return api.put(`/recipes/${id}`, data)
  },
  delete: (id) => api.delete(`/recipes/${id}`),
  scrape: (url) => api.post('/recipes/scrape', { url }),
}

export const publicRecipeApi = {
  getAll: (filters = {}, page = 1) => {
    const params = { page, ...filters }
    return api.get('/public/recipes', { params })
  },
  getById: (id) => api.get(`/public/recipes/${id}`),
  addComment: (recipeId, comment) => api.post(`/public/recipes/${recipeId}/comments`, { comment }),
  deleteComment: (recipeId, commentId) => api.delete(`/public/recipes/${recipeId}/comments/${commentId}`),
  addRating: (recipeId, rating) => api.post(`/public/recipes/${recipeId}/ratings`, { rating }),
  getRating: (recipeId) => api.get(`/public/recipes/${recipeId}/ratings`),
}

