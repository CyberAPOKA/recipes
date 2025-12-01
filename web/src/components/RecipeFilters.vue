<script setup>
import { computed } from 'vue'
import { useCategoryStore } from '@/stores/category'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFilter } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Select from '@/components/daisyui/Select.vue'
import Input from '@/components/daisyui/Input.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  showMyRecipes: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'clear', 'apply'])

const categoryStore = useCategoryStore()

const categoryOptions = computed(() => {
  return [
    { value: null, label: 'Todas as categorias' },
    ...categoryStore.categories.map(cat => ({
      value: cat.id,
      label: cat.name,
    })),
  ]
})

const operatorOptions = [
  { value: 'exact', label: 'Exato' },
  { value: 'above', label: 'Acima de' },
  { value: 'below', label: 'Abaixo de' },
]

const updateFilter = (key, value) => {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const clearFilters = () => {
  emit('clear')
}

const applyFilters = () => {
  emit('apply')
}
</script>

<template>
  <Card bordered class="p-4">
    <template #title>
      <div class="flex justify-between items-center">
        <span class="flex items-center gap-2">
          <FontAwesomeIcon :icon="faFilter" />
          Filtros
        </span>
        <Button variant="ghost" size="sm" @click="clearFilters">Limpar</Button>
      </div>
    </template>
    <div class="space-y-4">
      <!-- Search -->
      <div>
        <Input 
          :model-value="modelValue.search || ''" 
          @update:model-value="updateFilter('search', $event)"
          type="text" 
          placeholder="Buscar receitas..." 
          @input="applyFilters" 
        />
      </div>

      <!-- Category Filter -->
      <div>
        <Select 
          :model-value="modelValue.categoryId || null" 
          @update:model-value="updateFilter('categoryId', $event)"
          :options="categoryOptions" 
          label="Categoria" 
        />
      </div>

      <!-- My Recipes Filter -->
      <div v-if="showMyRecipes" class="form-control">
        <label class="label cursor-pointer">
          <span class="label-text">Mostrar apenas minhas receitas</span>
          <input 
            :checked="modelValue.myRecipes || false"
            @change="updateFilter('myRecipes', $event.target.checked)"
            type="checkbox" 
            class="checkbox checkbox-primary" 
          />
        </label>
      </div>

      <!-- Servings Filter -->
      <div>
        <label class="label">
          <span class="label-text">Porções</span>
        </label>
        <div class="flex gap-2">
          <Select 
            :model-value="modelValue.servingsOperator || 'exact'" 
            @update:model-value="updateFilter('servingsOperator', $event)"
            :options="operatorOptions" 
            class="w-fit" 
          />
          <Input 
            :model-value="modelValue.servingsValue || ''" 
            @update:model-value="updateFilter('servingsValue', $event ? Number($event) : null)"
            type="number" 
            placeholder="Qtd" 
            min="1" 
            class="flex-1" 
          />
        </div>
      </div>

      <!-- Prep Time Filter -->
      <div>
        <label class="label">
          <span class="label-text">Tempo (min)</span>
        </label>
        <div class="flex gap-2">
          <Select 
            :model-value="modelValue.prepTimeOperator || 'exact'" 
            @update:model-value="updateFilter('prepTimeOperator', $event)"
            :options="operatorOptions" 
            class="w-fit" 
          />
          <Input 
            :model-value="modelValue.prepTimeValue || ''" 
            @update:model-value="updateFilter('prepTimeValue', $event ? Number($event) : null)"
            type="number" 
            placeholder="Min" 
            min="1" 
            class="flex-1" 
          />
        </div>
      </div>
    </div>
  </Card>
</template>

