<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => [
            'primary', 'secondary', 'accent', 'neutral', 'ghost', 'link',
            'info', 'success', 'warning', 'error'
        ].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    shape: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'square', 'circle'].includes(value)
    },
    outline: {
        type: Boolean,
        default: false
    },
    active: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    wide: {
        type: Boolean,
        default: false
    },
    block: {
        type: Boolean,
        default: false
    },
    fancy: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
    const variant = props.outline ? `btn-outline btn-${props.variant}` : `btn-${props.variant}`
    const size = props.size !== 'md' ? `btn-${props.size}` : ''
    const shape = props.shape === 'square' ? 'btn-square' : props.shape === 'circle' ? 'btn-circle' : ''
    const active = props.active ? 'btn-active' : ''
    const loading = props.loading ? 'loading' : ''
    const wide = props.wide ? 'btn-wide' : ''
    const block = props.block ? 'btn-block' : ''
    const fancy = props.fancy
        ? 'relative overflow-hidden transition-transform duration-300 hover:scale-[1.02] active:scale-[0.98] ring-2 ring-current/30 hover:ring-current/50 shadow-lg hover:shadow-xl before:content-[""] before:absolute before:inset-0 before:bg-gradient-to-r before:from-transparent before:via-white/10 before:to-transparent before:-translate-x-full hover:before:translate-x-full before:transition-transform before:duration-700'
        : ''

    return `btn ${variant} ${size} ${shape} ${active} ${loading} ${wide} ${block} ${fancy}`.replace(/\s+/g, ' ').trim()
})
</script>
<template>
    <button :class="buttonClasses" :disabled="disabled" @click="$emit('click', $event)">
        <slot name="icon" />
        <slot />
    </button>
</template>
