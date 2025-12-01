<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRecipeStore } from '@/stores/recipe'
import { useCategoryStore } from '@/stores/category'
import { useI18n } from 'vue-i18n'
import { translateValidationError } from '@/utils/validation'
import { generateRecipeWithAI } from '@/services/openai'
import { recipeApi } from '@/api/recipe'
import Card from '@/components/daisyui/Card.vue'
import Input from '@/components/daisyui/Input.vue'
import RichTextEditor from '@/components/daisyui/RichTextEditor.vue'
import Select from '@/components/daisyui/Select.vue'
import Button from '@/components/daisyui/Button.vue'
import Alert from '@/components/daisyui/Alert.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faWandMagicSparkles, faLink } from '@fortawesome/free-solid-svg-icons'

const route = useRoute()
const router = useRouter()
const recipeStore = useRecipeStore()
const categoryStore = useCategoryStore()
const { t } = useI18n()

const isEdit = computed(() => !!route.params.id)
const loading = computed(() => recipeStore.loading)
const generatingAI = ref(false)
const aiError = ref(null)
const aiSuccess = ref(false)
const scrapingUrl = ref('')
const scraping = ref(false)
const scrapingError = ref(null)
const scrapingSuccess = ref(false)

const form = ref({
  category_id: null,
  name: '',
  prep_time_minutes: null,
  servings: null,
  image: null,
  image_url: null, // URL da imagem do scraping
  instructions: '',
  ingredients: '',
})

const imagePreview = ref(null)
const currentImageUrl = ref(null)
const imageChanged = ref(false)
const hasScrapedImage = ref(false) // Flag para indicar se a imagem veio do scraping
const errors = ref({})

const categoryOptions = computed(() => {
  return [
    { value: null, label: 'Selecione uma categoria' },
    ...categoryStore.categories.map(cat => ({
      value: cat.id,
      label: cat.name,
    })),
  ]
})

const selectedCategory = computed(() => {
  if (!form.value.category_id) return null
  // Garantir comparação correta de IDs (pode ser string ou número)
  return categoryStore.categories.find(cat =>
    String(cat.id) === String(form.value.category_id) ||
    Number(cat.id) === Number(form.value.category_id)
  )
})

const canGenerateAI = computed(() => {
  return !isEdit.value && !!form.value.category_id && !generatingAI.value
})

const handleGenerateWithAI = async () => {
  if (!canGenerateAI.value) return

  generatingAI.value = true
  aiError.value = null
  aiSuccess.value = false

  try {
    // Garantir que as categorias foram carregadas
    if (categoryStore.categories.length === 0) {
      await categoryStore.fetchCategories()
    }

    // Buscar categoria com comparação segura de IDs
    const category = categoryStore.categories.find(cat =>
      String(cat.id) === String(form.value.category_id) ||
      Number(cat.id) === Number(form.value.category_id)
    )

    const categoryName = category?.name || ''
    const recipeName = form.value.name || ''

    // Debug: verificar valores
    console.log('Generating recipe with:', {
      categoryName,
      recipeName,
      category_id: form.value.category_id,
      category,
      allCategories: categoryStore.categories
    })

    if (!categoryName) {
      throw new Error('Categoria não encontrada. Por favor, selecione uma categoria válida.')
    }

    const result = await generateRecipeWithAI(categoryName, recipeName)

    if (result.success && result.data) {
      // Preencher o formulário com os dados gerados
      form.value.prep_time_minutes = result.data.prep_time_minutes
      form.value.servings = result.data.servings
      form.value.ingredients = result.data.ingredients
      form.value.instructions = result.data.instructions

      // Se não tinha nome, usar o nome gerado
      if (!form.value.name && result.data.name) {
        form.value.name = result.data.name
      }

      aiSuccess.value = true

      // Limpar mensagem de sucesso após 3 segundos
      setTimeout(() => {
        aiSuccess.value = false
      }, 3000)
    }
  } catch (error) {
    console.error('Error generating recipe:', error)
    aiError.value = error.message || t('recipe.generateRecipeError')

    // Limpar erro após 5 segundos
    setTimeout(() => {
      aiError.value = null
    }, 5000)
  } finally {
    generatingAI.value = false
  }
}

const handleScrapeRecipe = async () => {
  if (!scrapingUrl.value.trim()) {
    scrapingError.value = 'Por favor, insira uma URL válida'
    return
  }

  scraping.value = true
  scrapingError.value = null
  scrapingSuccess.value = false

  try {
    const response = await recipeApi.scrape(scrapingUrl.value.trim())

    if (response.data.data) {
      const scrapedData = response.data.data

      // Preencher o formulário com os dados extraídos
      if (scrapedData.name) {
        form.value.name = scrapedData.name
      }

      if (scrapedData.prep_time_minutes) {
        form.value.prep_time_minutes = scrapedData.prep_time_minutes
      }

      if (scrapedData.servings) {
        form.value.servings = scrapedData.servings
      }

      if (scrapedData.ingredients) {
        form.value.ingredients = scrapedData.ingredients
      }

      if (scrapedData.instructions) {
        form.value.instructions = scrapedData.instructions
      }

      // Preencher URL da imagem se existir
      if (scrapedData.image_url) {
        form.value.image_url = scrapedData.image_url
        currentImageUrl.value = scrapedData.image_url
        imagePreview.value = scrapedData.image_url
        hasScrapedImage.value = true
        imageChanged.value = true
      }

      // Tentar encontrar e selecionar a categoria correspondente
      if (scrapedData.category_name && categoryStore.categories.length > 0) {
        const matchingCategory = categoryStore.categories.find(cat =>
          cat.name.toLowerCase().includes(scrapedData.category_name.toLowerCase()) ||
          scrapedData.category_name.toLowerCase().includes(cat.name.toLowerCase())
        )

        if (matchingCategory) {
          form.value.category_id = matchingCategory.id
        }
      }

      scrapingSuccess.value = true
      scrapingUrl.value = ''

      // Limpar mensagem de sucesso após 3 segundos
      setTimeout(() => {
        scrapingSuccess.value = false
      }, 3000)
    }
  } catch (error) {
    console.error('Error scraping recipe:', error)
    scrapingError.value = error.response?.data?.error || error.response?.data?.message || 'Erro ao fazer scraping da receita'

    // Limpar erro após 5 segundos
    setTimeout(() => {
      scrapingError.value = null
    }, 5000)
  } finally {
    scraping.value = false
  }
}

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.image = file
    form.value.image_url = null // Limpar URL se usuário fizer upload
    hasScrapedImage.value = false
    imageChanged.value = true
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removeImage = () => {
  form.value.image = null
  form.value.image_url = null
  imagePreview.value = null
  currentImageUrl.value = null
  hasScrapedImage.value = false
  imageChanged.value = true
  // Reset file input
  const fileInput = document.getElementById('recipe-image')
  if (fileInput) {
    fileInput.value = ''
  }
}

const getFieldError = (field) => {
  if (errors.value[field] && errors.value[field].length > 0) {
    return translateValidationError(errors.value[field][0], field)
  }
  return ''
}

const handleSubmit = async () => {
  // Limpar erros anteriores
  errors.value = {}

  const data = {
    ...form.value,
    category_id: form.value.category_id || null,
  }

  // Handle image: prioritize uploaded file over URL
  if (form.value.image instanceof File) {
    // User uploaded a file, use it and ignore URL
    data.image = form.value.image
    delete data.image_url
  } else if (form.value.image_url) {
    // Use scraped image URL
    data.image_url = form.value.image_url
    delete data.image
  } else {
    // No image
    if (isEdit.value && imageChanged.value) {
      // In edit mode, if image was removed, send null
      data.image = null
      delete data.image_url
    } else {
      // Don't send image fields if nothing changed
      delete data.image
      delete data.image_url
    }
  }

  let result
  if (isEdit.value) {
    result = await recipeStore.updateRecipe(route.params.id, data)
  } else {
    result = await recipeStore.createRecipe(data)
  }

  if (result.success) {
    errors.value = {}
    router.push('/')
  } else if (result.errors) {
    // Capturar erros de validação
    errors.value = result.errors
  }
}

onMounted(async () => {
  await categoryStore.fetchCategories()

  if (isEdit.value) {
    const result = await recipeStore.fetchRecipe(route.params.id)
    if (result.success && recipeStore.currentRecipe) {
      const recipe = recipeStore.currentRecipe
      // Verificar se a imagem é uma URL (do scraping) ou um arquivo local
      const isImageUrl = recipe.image && (recipe.image.startsWith('http://') || recipe.image.startsWith('https://'))

      form.value = {
        category_id: recipe.category_id || null,
        name: recipe.name || '',
        prep_time_minutes: recipe.prep_time_minutes || null,
        servings: recipe.servings || null,
        image: null, // Don't set image file, use URL for preview
        image_url: isImageUrl ? recipe.image : null,
        instructions: recipe.instructions || '',
        ingredients: recipe.ingredients || '',
      }
      currentImageUrl.value = recipe.image || null
      imagePreview.value = recipe.image || null
      hasScrapedImage.value = isImageUrl || false
      imageChanged.value = false
    }
  }
})
</script>

<template>
  <div class="container mx-auto p-4 max-w-2xl">
    <h1 class="text-3xl font-bold mb-6">
      {{ isEdit ? $t('recipe.edit') : $t('recipe.create') }}
    </h1>

    <Card bordered>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="!isEdit" class="form-control w-full">
          <label class="label">
            <span class="label-text">{{ $t('recipe.scrapeFromUrl') }}</span>
          </label>
          <div class="flex gap-2">
            <input v-model="scrapingUrl" type="url" :placeholder="$t('recipe.urlPlaceholder')"
              class="input input-bordered flex-1" :disabled="scraping" />
            <Button type="button" variant="secondary" :disabled="!scrapingUrl.trim() || scraping" :loading="scraping"
              @click="handleScrapeRecipe">
              <FontAwesomeIcon v-if="!scraping" :icon="faLink" class="mr-2" />
              {{ scraping ? $t('recipe.scrapingRecipe') : $t('recipe.scrapeFromUrl') }}
            </Button>
          </div>
          <Alert v-if="scrapingError" type="error" class="mt-2">{{ scrapingError }}</Alert>
          <Alert v-if="scrapingSuccess" type="success" class="mt-2">{{ $t('recipe.scrapeRecipeSuccess') }}</Alert>
        </div>

        <Select v-model="form.category_id" :options="categoryOptions" :label="$t('recipe.category')"
          placeholder="Selecione uma categoria" :error="getFieldError('category_id')" />

        <div class="flex gap-2 items-end">
          <div class="flex-1">
            <Input v-model="form.name" type="text" :label="$t('recipe.name')" :placeholder="$t('recipe.name')"
              :error="getFieldError('name')" />
          </div>
          <Button v-if="!isEdit" type="button" variant="secondary" :disabled="!canGenerateAI" :loading="generatingAI"
            @click="handleGenerateWithAI" class="mb-0">
            <FontAwesomeIcon v-if="!generatingAI" :icon="faWandMagicSparkles" class="mr-2" />
            {{ generatingAI ? $t('recipe.generatingRecipe') : $t('recipe.generateWithAI') }}
          </Button>
        </div>

        <Alert v-if="aiError" type="error">{{ aiError }}</Alert>
        <Alert v-if="aiSuccess" type="success">{{ $t('recipe.generateRecipeSuccess') }}</Alert>

        <div class="grid grid-cols-2 gap-4">
          <Input v-model.number="form.prep_time_minutes" type="number" :label="$t('recipe.prepTime')"
            :placeholder="$t('recipe.prepTime')" min="0" :error="getFieldError('prep_time_minutes')" />

          <Input v-model.number="form.servings" type="number" :label="$t('recipe.servings')"
            :placeholder="$t('recipe.servings')" min="1" :error="getFieldError('servings')" />
        </div>

        <div class="form-control w-full">
          <label class="label">
            <span class="label-text">{{ $t('recipe.image') }}</span>
          </label>

          <!-- Input de upload (oculto quando há imagem do scraping) -->
          <input v-if="!hasScrapedImage" id="recipe-image" type="file" accept="image/*" @change="handleImageChange"
            class="file-input file-input-bordered w-full" :class="{ 'file-input-error': getFieldError('image') }" />
          <div v-if="getFieldError('image')" class="mt-1 text-xs text-error">
            {{ getFieldError('image') }}
          </div>

          <!-- Preview da imagem -->
          <div v-if="imagePreview || currentImageUrl" class="mt-4 relative">
            <img :src="imagePreview || currentImageUrl" alt="Recipe preview"
              class="w-full h-64 object-cover rounded-lg" />
            <button type="button" @click="removeImage" class="btn btn-sm btn-error absolute top-2 right-2">
              ✕
            </button>
          </div>
        </div>

        <RichTextEditor v-model="form.ingredients" :label="$t('recipe.ingredients')"
          :placeholder="$t('recipe.ingredients')" :error="getFieldError('ingredients')" />

        <RichTextEditor v-model="form.instructions" :label="$t('recipe.instructions')"
          :placeholder="$t('recipe.instructions')" required :error="getFieldError('instructions')" />

        <div class="flex gap-4">
          <Button type="submit" variant="primary" :loading="loading">
            {{ $t('common.save') }}
          </Button>
          <Button type="button" variant="ghost" @click="$router.back()">
            {{ $t('common.cancel') }}
          </Button>
        </div>
      </form>
    </Card>
  </div>
</template>
