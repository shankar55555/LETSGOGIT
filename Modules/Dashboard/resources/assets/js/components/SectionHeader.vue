<template>
  <div class="d-flex justify-space-between align-center mt-2 mb-2">
    <div class="text-subtitle-1 font-weight-medium">{{ title }}</div>
    <div class="ml-2 mr-2" style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  }
})
</script>
