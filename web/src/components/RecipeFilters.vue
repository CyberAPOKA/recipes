<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCategoryStore } from '@/stores/category'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFilter } from '@fortawesome/free-solid-svg-icons'
import Card from '@/components/daisyui/Card.vue'
import Button from '@/components/daisyui/Button.vue'
import Select from '@/components/daisyui/Select.vue'
import Input from '@/components/daisyui/Input.vue'

const { t } = useI18n()

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

// Local search value for debouncing
const localSearch = ref(props.modelValue.search || '')

// Debounce timer for search input
let searchDebounceTimer = null
const DEBOUNCE_DELAY = 500 // milliseconds (1 second)

// Watch for external changes to search (e.g., when filters are cleared)
watch(() => props.modelValue.search, (newValue) => {
  if (newValue !== localSearch.value) {
    localSearch.value = newValue || ''
  }
})

// Debounced search update
watch(localSearch, (newValue) => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    // Update the modelValue with the debounced search value
    emit('update:modelValue', { ...props.modelValue, search: newValue })
    // Trigger the search
    emit('apply')
  }, DEBOUNCE_DELAY)
})

const categoryOptions = computed(() => {
  return [
    { value: null, label: t('filter.allCategories') },
    ...categoryStore.categories.map(cat => ({
      value: cat.id,
      label: cat.name,
    })),
  ]
})

const operatorOptions = computed(() => [
  { value: 'exact', label: t('filter.operator.exact') },
  { value: 'above', label: t('filter.operator.above') },
  { value: 'below', label: t('filter.operator.below') },
])

const sortOptions = computed(() => [
  { value: 'recent', label: t('filter.sort.recent') },
  { value: 'oldest', label: t('filter.sort.oldest') },
  { value: 'rating_desc', label: t('filter.sort.ratingDesc') },
  { value: 'rating_asc', label: t('filter.sort.ratingAsc') },
  { value: 'comments_desc', label: t('filter.sort.commentsDesc') },
  { value: 'comments_asc', label: t('filter.sort.commentsAsc') },
  { value: 'name_asc', label: t('filter.sort.nameAsc') },
  { value: 'name_desc', label: t('filter.sort.nameDesc') },
])

const updateFilter = (key, value) => {
  // For search, update local value (which will trigger debounced watch)
  if (key === 'search') {
    localSearch.value = value || ''
    return
  }

  // For other filters, update immediately
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const clearFilters = () => {
  // Clear any pending debounce timer
  clearTimeout(searchDebounceTimer)
  localSearch.value = ''
  emit('clear')
}

const applyFilters = () => {
  // Clear any pending debounce timer and apply immediately
  clearTimeout(searchDebounceTimer)
  // Update search value immediately
  emit('update:modelValue', { ...props.modelValue, search: localSearch.value })
  emit('apply')
}
</script>

<template>
  <Card bordered class="p-4">
    <template #title>
      <div class="flex justify-between items-center">
        <span class="flex items-center gap-2">
          <FontAwesomeIcon :icon="faFilter" />
          {{ $t('filter.title') }}
        </span>
        <Button variant="ghost" size="sm" @click="clearFilters">{{ $t('filter.clear') }}</Button>
      </div>
    </template>
    <div class="space-y-4">
      <!-- Search -->
      <div>
        <Input v-model="localSearch" type="text" :placeholder="$t('filter.search')" />
      </div>

      <!-- Category Filter -->
      <div>
        <Select :model-value="modelValue.categoryId || null" @update:model-value="updateFilter('categoryId', $event)"
          :options="categoryOptions" :label="$t('filter.category')" />
      </div>

      <!-- My Recipes Filter -->
      <div v-if="showMyRecipes" class="form-control">
        <label class="label cursor-pointer">
          <span class="label-text">{{ $t('filter.showMyRecipes') }}</span>
          <input :checked="modelValue.myRecipes || false" @change="updateFilter('myRecipes', $event.target.checked)"
            type="checkbox" class="checkbox checkbox-primary" />
        </label>
      </div>

      <!-- Servings Filter -->
      <div>
        <label class="label">
          <span class="label-text">{{ $t('filter.servings') }}</span>
        </label>
        <div class="flex gap-2">
          <Select :model-value="modelValue.servingsOperator || 'exact'"
            @update:model-value="updateFilter('servingsOperator', $event)" :options="operatorOptions" />
          <Input :model-value="modelValue.servingsValue || ''"
            @update:model-value="updateFilter('servingsValue', $event ? Number($event) : null)" type="number"
            :placeholder="$t('filter.quantity')" min="1" />
        </div>
      </div>

      <!-- Prep Time Filter -->
      <div>
        <label class="label">
          <span class="label-text">{{ $t('filter.prepTime') }}</span>
        </label>
        <div class="flex gap-2">
          <Select :model-value="modelValue.prepTimeOperator || 'exact'"
            @update:model-value="updateFilter('prepTimeOperator', $event)" :options="operatorOptions" />
          <Input :model-value="modelValue.prepTimeValue || ''"
            @update:model-value="updateFilter('prepTimeValue', $event ? Number($event) : null)" type="number"
            :placeholder="$t('filter.minutes')" min="1" />
        </div>
      </div>

      <!-- Rating Filter -->
      <div>
        <label class="label">
          <span class="label-text">{{ $t('filter.rating') }}</span>
        </label>
        <div class="flex gap-2">
          <Select :model-value="modelValue.ratingOperator || 'exact'"
            @update:model-value="updateFilter('ratingOperator', $event)" :options="operatorOptions" />
          <Input :model-value="modelValue.ratingValue || ''"
            @update:model-value="updateFilter('ratingValue', $event ? Number($event) : null)" type="number"
            :placeholder="$t('filter.note')" min="1" max="5" step="0.1" />
        </div>
      </div>

      <!-- Comments Filter -->
      <div>
        <label class="label">
          <span class="label-text">{{ $t('filter.comments') }}</span>
        </label>
        <div class="flex gap-2">
          <Select :model-value="modelValue.commentsOperator || 'exact'"
            @update:model-value="updateFilter('commentsOperator', $event)" :options="operatorOptions" />
          <Input :model-value="modelValue.commentsValue || ''"
            @update:model-value="updateFilter('commentsValue', $event ? Number($event) : null)" type="number"
            :placeholder="$t('filter.quantity')" min="0" />
        </div>
      </div>

      <!-- Sort By -->
      <div>
        <Select :model-value="modelValue.sortBy || 'recent'" @update:model-value="updateFilter('sortBy', $event)"
          :options="sortOptions" :label="$t('filter.sortBy')" />
      </div>

      <!-- Clear Filters Button -->
      <div class="pt-2">
        <Button variant="outline" class="w-full" @click="clearFilters">
          {{ $t('filter.clearFilters') }}
        </Button>
      </div>
    </div>
  </Card>
</template>
