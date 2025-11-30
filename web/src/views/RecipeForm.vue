<template>
  <div class="container mx-auto p-4 max-w-2xl">
    <h1 class="text-3xl font-bold mb-6">
      {{ isEdit ? $t('recipe.edit') : $t('recipe.create') }}
    </h1>

    <Card bordered>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <Select
          v-model="form.category_id"
          :options="categoryOptions"
          :label="$t('recipe.category')"
          placeholder="Selecione uma categoria"
        />

        <Input
          v-model="form.name"
          type="text"
          :label="$t('recipe.name')"
          :placeholder="$t('recipe.name')"
        />

        <div class="grid grid-cols-2 gap-4">
          <Input
            v-model.number="form.prep_time_minutes"
            type="number"
            :label="$t('recipe.prepTime')"
            :placeholder="$t('recipe.prepTime')"
            min="0"
          />

          <Input
            v-model.number="form.servings"
            type="number"
            :label="$t('recipe.servings')"
            :placeholder="$t('recipe.servings')"
            min="1"
          />
        </div>

        <Textarea
          v-model="form.ingredients"
          :label="$t('recipe.ingredients')"
          :placeholder="$t('recipe.ingredients')"
          :rows="5"
        />

        <Textarea
          v-model="form.instructions"
          :label="$t('recipe.instructions')"
          :placeholder="$t('recipe.instructions')"
          :rows="8"
          required
        />

        <div class="flex gap-4">
          <Button
            type="submit"
            variant="primary"
            :loading="loading"
          >
            {{ $t('common.save') }}
          </Button>
          <Button
            type="button"
            variant="ghost"
            @click="$router.back()"
          >
            {{ $t('common.cancel') }}
          </Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRecipeStore } from '../stores/recipe'
import { useCategoryStore } from '../stores/category'
import Card from '../components/daisyui/Card.vue'
import Input from '../components/daisyui/Input.vue'
import Textarea from '../components/daisyui/Textarea.vue'
import Select from '../components/daisyui/Select.vue'
import Button from '../components/daisyui/Button.vue'

const route = useRoute()
const router = useRouter()
const recipeStore = useRecipeStore()
const categoryStore = useCategoryStore()

const isEdit = computed(() => !!route.params.id)
const loading = computed(() => recipeStore.loading)

const form = ref({
  category_id: null,
  name: '',
  prep_time_minutes: null,
  servings: null,
  instructions: '',
  ingredients: '',
})

const categoryOptions = computed(() => {
  return [
    { value: null, label: 'Selecione uma categoria' },
    ...categoryStore.categories.map(cat => ({
      value: cat.id,
      label: cat.name,
    })),
  ]
})

const handleSubmit = async () => {
  const data = {
    ...form.value,
    category_id: form.value.category_id || null,
  }

  let result
  if (isEdit.value) {
    result = await recipeStore.updateRecipe(route.params.id, data)
  } else {
    result = await recipeStore.createRecipe(data)
  }

  if (result.success) {
    router.push('/recipes')
  }
}

onMounted(async () => {
  await categoryStore.fetchCategories()

  if (isEdit.value) {
    const result = await recipeStore.fetchRecipe(route.params.id)
    if (result.success && recipeStore.currentRecipe) {
      const recipe = recipeStore.currentRecipe
      form.value = {
        category_id: recipe.category_id || null,
        name: recipe.name || '',
        prep_time_minutes: recipe.prep_time_minutes || null,
        servings: recipe.servings || null,
        instructions: recipe.instructions || '',
        ingredients: recipe.ingredients || '',
      }
    }
  }
})
</script>

