<template>
  <div>
    <VRow>
      <VCol cols="12" class="pb-0">
        <!-- Header line with Target Info and Refresh Button -->
        <div class="d-flex justify-space-between align-center">
          <div class="text-subtitle-1 font-weight-medium">User Target-Incentive Info : </div>
          <div class="ml-2 mr-2"
            style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
          </div>
        </div>
      </VCol>
      <VCol v-for="(data, index) in localData" :key="index" class="flex-shrink-0" cols="12" sm="6" md="3"
        style="max-width: 280px">
        <VCard class="logistics-card-statistics cursor-pointer pa-4" :style="data.isHover
          ? `border-block-end-color: rgb(var(--v-theme-${data.color}))`
          : `border-block-end-color: rgba(var(--v-theme-${data.color}), 0.38)`" @mouseenter="data.isHover = true"
          @mouseleave="data.isHover = false" tabindex="0" role="button" :aria-label="`View ${data.title} details`">
          <div class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h5 font-weight-bold mb-1">{{ data.value }}</h4>
              <div class="text-body-2 text-muted">{{ data.title }}</div>
            </div>
            <VAvatar variant="tonal" :color="data.color" size="38">
              <VIcon :icon="data.icon" size="24" :aria-label="data.title" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { VCard, VCol, VRow } from 'vuetify/components';

const props = defineProps({
  incentiveData: {
    type: Array,
    default: () => [
      {
        icon: 'tabler-file-invoice',
        color: 'primary',
        title: 'Last Month Incentive',
        value: 0,
        isHover: false,
      },
      {
        icon: 'tabler-circle-x',
        color: 'error',
        title: 'This Month Target',
        value: 0,
        isHover: false,
      },
    ],
  },
});

// Local reactive data to manage hover state
const localData = ref(props.incentiveData.map(item => ({ ...item, isHover: false })));
console.log("the incentive local data is :", localData)

// Watch for changes in incentiveData prop to update localData
watch(
  () => props.incentiveData,
  (newData) => {
    localData.value = newData.map(item => ({ ...item, isHover: false }));
  },
  { deep: true }
);

</script>

<style lang="scss" scoped>
@use "@core-scss/base/mixins" as mixins;

$border-width: 2px;
$border-width-hover: 3px;
$border-opacity: 0.38;

.logistics-card-statistics {
  border-block-end-style: solid;
  border-block-end-width: $border-width;
  border-radius: 12px;
  background: white;
  transition: all 0.2s ease;

  &:hover {
    border-block-end-width: $border-width-hover;
    margin-block-end: -1px;
    transform: translateY(-2px);
    @include mixins.elevation(6);
  }
}

.skin--bordered .logistics-card-statistics {
  border-block-end-width: $border-width;

  &:hover {
    border-block-end-width: $border-width-hover;
    margin-block-end: -2px;
  }
}
</style>
