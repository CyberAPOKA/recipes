import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Auth/Login.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Auth/Register.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/recipes',
    name: 'Recipes',
    component: () => import('../views/Recipes/Index.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/recipes/create',
    name: 'RecipeCreate',
    component: () => import('../views/Recipes/Form.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/recipes/:id',
    name: 'RecipeDetail',
    component: () => import('../views/Recipes/Detail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/recipes/:id/edit',
    name: 'RecipeEdit',
    component: () => import('../views/Recipes/Form.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Public/Recipes.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/public/recipes/:id',
    name: 'PublicRecipeDetail',
    component: () => import('../views/Public/RecipeDetail.vue'),
    meta: { requiresAuth: false },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  authStore.initAuth()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if ((to.path === '/login' || to.path === '/register') && authStore.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router

