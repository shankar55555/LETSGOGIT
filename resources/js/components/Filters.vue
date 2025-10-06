<template>
  <VMenu :close-on-content-click="false">
    <template v-slot:activator="{ props }">
      <VBtn v-bind="props" variant="outlined" color="primary" icon="tabler-filter" size="small" v-tooltip="'Filters'" />
    </template>

    <VCard>
      <VList>
        <VListItem v-if="statusFilter">
          <VCheckbox v-model="showStatusFilter" label="Filter by Status" hide-details density="compact"
            @change="emitFilterChange" />
        </VListItem>

        <VListItem v-if="dateFilter">
          <VCheckbox v-model="showDateFilter" label="Filter by Date" hide-details density="compact"
            @change="emitFilterChange" />
        </VListItem>

        <VListItem v-if="searchFilter">
          <VCheckbox v-model="showSearchFilter" label="Search" hide-details density="compact"
            @change="emitFilterChange" />
        </VListItem>
        <VListItem v-if="showdateRangeOptionsVisible">
          <VCheckbox v-model="showdateRangeOptions" label="Date Range" hide-details density="compact"
            @change="emitFilterChange" />
        </VListItem>
      </VList>
    </VCard>
  </VMenu>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  initialShowStatusFilter: {
    type: Boolean,
    default: false
  },
  initialShowDateFilter: {
    type: Boolean,
    default: false
  },
  initialShowSearchFilter: {
    type: Boolean,
    default: false
  },
  initialShowdateRangeOptions: {
    type: Boolean,
    default: false
  },
  statusFilter: {
    type: Boolean,
    default: false
  },
  dateFilter: {
    type: Boolean,
    default: false
  },
  searchFilter: {
    type: Boolean,
    default: false
  },
  showdateRangeOptions: {
    type: Boolean,
    default: false
  },
})
console.log(props);

const emit = defineEmits(['update:filters'])

const showStatusFilter = ref(props.initialShowStatusFilter)
const showDateFilter = ref(props.initialShowDateFilter)
const showSearchFilter = ref(props.initialShowSearchFilter)
const showdateRangeOptions = ref(props.initialShowdateRangeOptions)
const showdateRangeOptionsVisible = ref(props.showdateRangeOptions)
const emitFilterChange = () => {
  emit('update:filters', {
    showStatusFilter: showStatusFilter.value,
    showDateFilter: showDateFilter.value,
    showSearchFilter: showSearchFilter.value,
    showdateRangeOptions: showdateRangeOptions.value
  })
}

// Watch for prop changes
watch(() => props.initialShowStatusFilter, (newVal) => {
  showStatusFilter.value = newVal
})

watch(() => props.initialShowDateFilter, (newVal) => {
  showDateFilter.value = newVal
})

watch(() => props.initialShowSearchFilter, (newVal) => {
  showSearchFilter.value = newVal
})

watch(() => props.initialShowdateRangeOptions, (newVal) => {
  showdateRangeOptions.value = newVal
})
</script>
