import api from './axios'

export const categoryApi = {
  getAll: () => api.get('/categories'),
}

