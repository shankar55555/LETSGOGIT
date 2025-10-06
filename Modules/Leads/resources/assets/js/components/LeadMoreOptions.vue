<template>
  <VMenu>
    <template v-slot:activator="{ props }">
      <VBtn v-bind="props" variant="outlined" color="primary" v-tooltip="'More Options'" size="small"
        icon="tabler-dots-vertical">
      </VBtn>
    </template>

    <VCard class="box_shadow" elevation="2">
      <VList>
        <VListItem class="mx-0">
          <VBtn color="primary" variant="outlined" rounded="3"
            @click="toggleTableHeaderDrag" size="small">
            Arrange Column
            <template #prepend>
              <VIcon icon="tabler-columns-3" />
            </template>
          </VBtn>
        </VListItem>
        <VListItem class="mx-0">
          <VBtn v-if="$can('leads', 'view')" variant="outlined" @click="refresh" class="w-100"
            size="small">
            Refresh
            <template #prepend>
              <VIcon icon="tabler-refresh" />
            </template>
          </VBtn>
        </VListItem>
      </VList>
    </VCard>
  </VMenu>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  tableHeaderDragVisible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:tableHeaderDragVisible', 'refresh'])

const toggleTableHeaderDrag = () => {
  emit('update:tableHeaderDragVisible', !props.tableHeaderDragVisible)
}

const refresh = () => {
  emit('refresh')
}
</script>