import api from './axios'

export const recipeApi = {
  getAll: (search = '', page = 1) => api.get('/recipes', { params: { search, page } }),
  getById: (id) => api.get(`/recipes/${id}`),
  create: (data) => api.post('/recipes', data),
  update: (id, data) => api.put(`/recipes/${id}`, data),
  delete: (id) => api.delete(`/recipes/${id}`),
}

