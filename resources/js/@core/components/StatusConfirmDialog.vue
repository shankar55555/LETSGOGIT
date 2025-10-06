<template>
  <VDialog
    max-width="500"
    :model-value="isStatusConfirmVisible"
    @update:model-value="emit('update:isStatusConfirmVisible', $event)"
    scrollable persistent
  >
    <VCard class="text-center px-10 py-6" v-if="props.currentItem">
      <VCardText>
        <VBtn icon variant="outlined" color="warning" class="my-4" style="block-size: 88px; inline-size: 88px;" disabled>
          <span class="text-5xl">!</span>
        </VBtn>

        <h6 class="text-lg font-weight-medium">
          Are you sure you want to change the status from <strong>({{ $resolveStatusVariant(props.currentItem.oldStatus , props.statusList).text }})</strong> To
          <strong>({{ $resolveStatusVariant(props.currentItem.newStatus , props.statusList).text }})</strong>?<br />
          You won't be able to revert this change.
        </h6>
      </VCardText>

      <VCardText class="d-flex align-center justify-center gap-2">
        <VBtn v-if="props.currentItem" variant="elevated" @click="onConfirm" :loading="loader" :disabled="loader">
          Confirm
        </VBtn>
        <VBtn color="secondary" variant="tonal" @click="onCancel">
          Cancel
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
const props = defineProps({
  currentItem: { type: Object, required: true },
  isStatusConfirmVisible: { type: Boolean, required: true },
  loader: { type: Boolean, required: true },
  statusList: { type: Array, default: () => []},
});
const emit = defineEmits(['update:isStatusConfirmVisible', 'updateStatusValue', 'close']);

const onConfirm = () => emit('updateStatusValue', props.currentItem);
const onCancel = () => {
  emit('close');
  emit('update:isStatusConfirmVisible', false);
};
</script>
