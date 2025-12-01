import { API_BASE_URL, getAuthToken } from '@/constants/api';

export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface Recipe {
  id: number;
  user_id: number;
  user?: {
    id: number;
    name: string;
  };
  category_id: number | null;
  category?: {
    id: number;
    name: string;
  };
  name: string;
  prep_time_minutes: number | null;
  servings: number | null;
  image: string | null;
  instructions: string;
  ingredients: string | null;
  comments?: Array<{
    id: number;
    comment: string;
    user: {
      id: number;
      name: string;
    };
    created_at: string;
  }>;
  comments_count?: number;
  ratings_count?: number;
  average_rating?: number;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: number;
  name: string;
}

class ApiService {
  private baseURL: string;

  constructor() {
    this.baseURL = API_BASE_URL;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const token = await getAuthToken();
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Erro desconhecido' }));
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  // Auth endpoints
  async login(email: string, password: string): Promise<AuthResponse> {
    const response = await fetch(`${this.baseURL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Erro ao fazer login' }));
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  async register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string
  ): Promise<AuthResponse> {
    const response = await fetch(`${this.baseURL}/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ name, email, password, password_confirmation }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Erro ao registrar' }));
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  async logout(): Promise<void> {
    const token = await getAuthToken();
    if (!token) return;

    await fetch(`${this.baseURL}/logout`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });
  }

  async getCurrentUser(): Promise<ApiResponse<User>> {
    return this.request<User>('/user');
  }

  // Recipe endpoints
  async getRecipes(page: number = 1, search?: string): Promise<ApiResponse<Recipe[]>> {
    // Usar /recipes quando autenticado, /public/recipes quando não autenticado
    const token = await getAuthToken();
    const endpoint = token ? '/recipes' : '/public/recipes';
    const params = new URLSearchParams({ page: page.toString() });
    if (search) {
      params.append('search', search);
    }
    return this.request<Recipe[]>(`${endpoint}?${params.toString()}`);
  }

  async getRecipe(id: number): Promise<ApiResponse<Recipe>> {
    return this.request<Recipe>(`/public/recipes/${id}`);
  }

  async createRecipe(data: {
    name?: string;
    category_id?: number | null;
    prep_time_minutes?: number | null;
    servings?: number | null;
    instructions: string;
    ingredients?: string | null;
    image?: string | null;
  }): Promise<ApiResponse<Recipe>> {
    return this.request<Recipe>('/recipes', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async updateRecipe(
    id: number,
    data: {
      name?: string;
      category_id?: number | null;
      prep_time_minutes?: number | null;
      servings?: number | null;
      instructions?: string;
      ingredients?: string | null;
      image?: string | null;
    }
  ): Promise<ApiResponse<Recipe>> {
    return this.request<Recipe>(`/recipes/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteRecipe(id: number): Promise<ApiResponse<void>> {
    return this.request<void>(`/recipes/${id}`, {
      method: 'DELETE',
    });
  }

  // Category endpoints
  async getCategories(): Promise<ApiResponse<Category[]>> {
    return this.request<Category[]>('/categories');
  }
}

export const apiService = new ApiService();

