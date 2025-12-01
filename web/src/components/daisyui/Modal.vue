<script setup>
import { computed } from 'vue'
import Button from './Button.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', 'full'].includes(value)
  },
  showActions: {
    type: Boolean,
    default: true
  },
  showCancel: {
    type: Boolean,
    default: true
  },
  showConfirm: {
    type: Boolean,
    default: true
  },
  cancelText: {
    type: String,
    default: 'Cancelar'
  },
  confirmText: {
    type: String,
    default: 'Confirmar'
  },
  confirmVariant: {
    type: String,
    default: 'primary'
  },
  loading: {
    type: Boolean,
    default: false
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const sizeClass = computed(() => {
  if (props.size === 'md') return ''
  return `max-w-${props.size}`
})

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
  emit('update:modelValue', false)
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    emit('update:modelValue', false)
  }
}
</script>

<template>
  <div :class="['modal', { 'modal-open': modelValue }]" @click.self="handleBackdropClick">
    <div class="modal-box" :class="sizeClass">
      <h3 v-if="title" class="font-bold text-lg mb-4">{{ title }}</h3>
      <slot />
      <div v-if="showActions" class="modal-action">
        <slot name="actions">
          <Button v-if="showCancel" variant="ghost" @click="handleCancel">
            {{ cancelText }}
          </Button>
          <Button v-if="showConfirm" :variant="confirmVariant" @click="handleConfirm" :loading="loading">
            {{ confirmText }}
          </Button>
        </slot>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop" @submit.prevent="handleBackdropClick">
      <button>close</button>
    </form>
  </div>
</template>
