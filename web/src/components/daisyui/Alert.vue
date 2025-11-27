<template>
    <div :class="alertClasses" role="alert">
        <slot name="icon" />
        <div>
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['info', 'success', 'warning', 'error'].includes(value)
    }
})

const alertClasses = computed(() => {
    return `alert alert-${props.type}`
})
</script>
