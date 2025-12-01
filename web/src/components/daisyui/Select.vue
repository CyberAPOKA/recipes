<template>
  <div class="form-control w-full">
    <label v-if="label" class="block mb-1">
      <span class="label-text">{{ label }}</span>
    </label>
    <select
      class="select select-bordered w-full"
      :class="{ 'select-error': error }"
      :value="modelValue === null ? '' : modelValue"
      :disabled="disabled"
      :required="required"
      @change="handleChange($event)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value === null ? '' : option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <div v-if="error" class="mt-1 text-xs text-error">
      {{ error }}
    </div>
    <div v-else-if="hint" class="mt-1 text-xs opacity-70">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const handleChange = (event) => {
  let value = event.target.value === '' ? null : event.target.value
  // Try to convert to number if it's a numeric string
  if (value !== null && !isNaN(value) && !isNaN(parseFloat(value))) {
    value = Number(value)
  }
  emit('update:modelValue', value)
}
</script>

