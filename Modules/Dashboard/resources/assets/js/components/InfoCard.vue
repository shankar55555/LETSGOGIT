<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  quotationData: {
    type: Array,
    default: () => [
      {
        icon: 'tabler-file-text',
        color: 'primary',
        title: 'Quotations',
        percentage: '+3.5%',
        percentageColor: 'success',
        value: 0,
        isHover: false,
      },
      {
        icon: 'tabler-hourglass',
        color: 'warning',
        title: 'Pending Quotations',
        percentage: '-15%',
        percentageColor: 'error',
        value: 0,
        isHover: false,
      },
      {
        icon: 'tabler-circle-check',
        color: 'success',
        title: 'Invoice Total',
        percentage: '0%',
        value: 0,
        isHover: false,
      },
      {
        icon: 'tabler-file-text',
        color: 'primary',
        title: 'Total Leads',
        percentage: '2%',
        percentageColor: 'success',
        value: dashboardInfo.value.lead_count || 0,
        isHover: false,
      },
    ],
  },
});
// Local reactive data with hover state
const localData = ref(props.quotationData.map(item => ({ ...item })))

watch(
  () => props.quotationData,
  newData => {
    localData.value = newData.map(item => ({ ...item }))
  },
  { deep: true },
)
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12" class="pb-0">
        <!-- Header line with Quotation Info and Refresh Button -->
        <div class="d-flex justify-space-between align-center">
          <div class="text-subtitle-1 font-weight-medium">Quotation Info : </div>
          <div class="ml-2 mr-2"
            style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
          </div>
        </div>
      </VCol>
    </VRow>
    <VRow class="d-flex flex-nowrap overflow-x-auto">
      <VCol v-for="(data, index) in localData" :key="index" class="flex-shrink-0" cols="12" sm="6" md="3"
        style="max-width: 280px">
        <VCard class="logistics-card-statistics cursor-pointer pa-4" :style="data.isHover
          ? `border-block-end-color: rgb(var(--v-theme-${data.color}))`
          : `border-block-end-color: rgba(var(--v-theme-${data.color}), 0.38)`" @mouseenter="data.isHover = true"
          @mouseleave="data.isHover = false" tabindex="0" role="button" :aria-label="`View ${data.title} details`">
          <div class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h5 font-weight-bold mb-1">{{ data.value }}</h4>
              <div class="text-body-2 d-flex align-center">
                <div class="me-2 text-muted">{{ data.title }}</div>
                <div :class="['font-weight-medium', `text-${data.percentageColor || 'secondary'}`]">
                  {{ data.percentage }}
                </div>
              </div>

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

<style lang="scss" scoped>
@use "@core-scss/base/mixins" as mixins;

$border-width: 2px;
$border-width-hover: 3px;

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
