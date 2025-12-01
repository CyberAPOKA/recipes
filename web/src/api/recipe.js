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
  getAll: (search = '', page = 1) => api.get('/recipes', { params: { search, page } }),
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
}

