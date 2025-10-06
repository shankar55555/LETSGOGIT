<script setup>
import moment from 'moment';

const props = defineProps({
  InfoData: {
    type: null,
    required: true,
  },
})

const makeDateFormat = (date , onlyDate = false) => {
    if(onlyDate)
    return moment(date).format('DD-MM-Y');
    else
    return moment(date).format('LLLL');
};

</script>
<template>
  <VRow>
    <VCol cols="12">
      <VCard v-if="props.InfoData">
        <VCardText>
          <!-- SECTION Target Info -->
          <h5 class="text-h5 mb-4">Target Details</h5>
          <VRow dense>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Title:</strong>
                <span>{{ props.InfoData.title || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Target Type:</strong>
                <span>{{ props.InfoData.target_type || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Targets:</strong>
                <span>{{ props.InfoData.target_value || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Target Amount:</strong>
                <span>{{ props.InfoData.target_amount || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Incentive percentage:</strong>
                <span>{{ props.InfoData.incentive_percent }}%</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Start Date:</strong>
                <span>{{ makeDateFormat(props.InfoData.start_date) }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong> End Date:</strong>
                <span>{{ makeDateFormat(props.InfoData.end_date , true)}}</span>
              </div>
            </VCol>
             <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Created By:</strong>
                <span>{{ props.InfoData.creator?.name || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="4" lg="4">
              <div class="d-flex align-center gap-x-2 mt-1">
                <strong>Last Updated By:</strong>
                <span>{{ props.InfoData.updater?.name || '-' }}</span>
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
<style scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}
</style>
