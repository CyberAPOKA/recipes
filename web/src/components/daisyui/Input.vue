<template>
    <div class="form-control w-full">
        <label v-if="label" class="block mb-1">
            <span class="label-text">{{ label }}</span>
        </label>
        <input :type="type" class="input input-bordered w-full" :placeholder="placeholder" :value="modelValue"
            :disabled="disabled" :required="required" @input="$emit('update:modelValue', $event.target.value)" />
        <div v-if="hint" class="mt-1 text-xs opacity-70">
            {{ hint }}
        </div>
    </div>


</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text'
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
