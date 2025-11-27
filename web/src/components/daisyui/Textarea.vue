<template>
    <div class="form-control w-full">
        <label v-if="label" class="block mb-1">
            <span class="label-text">{{ label }}</span>
        </label>
        <textarea class="textarea textarea-bordered w-full" :placeholder="placeholder" :rows="rows" :value="modelValue"
            :disabled="disabled" :required="required" @input="$emit('update:modelValue', $event.target.value)" />
        <div v-if="hint" class="mt-1 text-xs opacity-70">
            {{ hint }}
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    rows: {
        type: Number,
        default: 4
    },
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    }
})

defineEmits(['update:modelValue'])
</script>
