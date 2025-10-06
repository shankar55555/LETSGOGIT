<template>
  <VDialog v-model="isOpen" max-width="400" persistent>
    <VCard class="account_vcard_border">
      <VCardTitle class="d-flex align-center gap-2">
        <VIcon :icon="iconComponent" :color="iconColor" size="24" />
        {{ title }}
      </VCardTitle>
      
      <VCardText>
        <p class="text-body-1 mb-0">{{ message }}</p>
        <div v-if="details" class="mt-2 text-caption text-medium-emphasis">
          {{ details }}
        </div>
      </VCardText>
      
      <VCardActions class="px-6 pb-6">
        <VSpacer />
        <VBtn 
          @click="handleCancel" 
          variant="outlined" 
          class="account_v_btn_outlined"
          :disabled="loading"
        >
          {{ cancelText }}
        </VBtn>
        <VBtn 
          @click="handleConfirm" 
          :loading="loading"
          :color="confirmColor"
          variant="flat"
          class="account_v_btn_primary"
        >
          {{ confirmText }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed } from 'vue'
import { IconAlertTriangle, IconTrash, IconCheck, IconX } from '@tabler/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'warning', // warning, danger, success, info
    validator: (value) => ['warning', 'danger', 'success', 'info'].includes(value)
  },
  title: {
    type: String,
    default: 'Confirm Action'
  },
  message: {
    type: String,
    required: true
  },
  details: {
    type: String,
    default: ''
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const iconComponent = computed(() => {
  const icons = {
    warning: IconAlertTriangle,
    danger: IconTrash,
    success: IconCheck,
    info: IconAlertTriangle
  }
  return icons[props.type] || IconAlertTriangle
})

const iconColor = computed(() => {
  const colors = {
    warning: 'warning',
    danger: 'error',
    success: 'success',
    info: 'info'
  }
  return colors[props.type] || 'warning'
})

const confirmColor = computed(() => {
  const colors = {
    warning: 'warning',
    danger: 'error',
    success: 'success',
    info: 'primary'
  }
  return colors[props.type] || 'primary'
})

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
  isOpen.value = false
}
</script>