<template>
    <div :class="cardClasses">
        <div v-if="$slots.image" class="card-image">
            <slot name="image" />
        </div>
        <div class="card-body">
            <h2 v-if="title" class="card-title">
                {{ title }}
                <div v-if="$slots.actions" class="card-actions justify-end">
                    <slot name="actions" />
                </div>
            </h2>
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    bordered: {
        type: Boolean,
        default: false
    },
    imageFull: {
        type: Boolean,
        default: false
    },
    compact: {
        type: Boolean,
        default: false
    },
    side: {
        type: Boolean,
        default: false
    },
    glass: {
        type: Boolean,
        default: false
    }
})

const cardClasses = computed(() => {
    const classes = ['card']

    if (props.bordered) {
        classes.push('card-bordered')
    }

    if (props.imageFull) {
        classes.push('image-full')
    }

    if (props.compact) {
        classes.push('card-compact')
    }

    if (props.side) {
        classes.push('card-side')
    }

    if (props.glass) {
        classes.push('glass')
    }

    classes.push('bg-base-100', 'shadow-xl')

    return classes.join(' ')
})
</script>
