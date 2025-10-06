<script setup>
import { onMounted, ref } from 'vue';

const dashboardInfo = ref({})
const getDashboard = async () => {
  try {
    const response = await $api('/dashboard')
    console.log("the dashboard response is :", response)
    dashboardInfo.value = response.data
  } catch (err) {
  }
}
getDashboard();

onMounted(() => {
  getDashboard();
})

</script>

<template>
  <VRow>
    <VCol cols="12" class="pb-0">
      <!-- Header line with User Attendance Info and Refresh Button -->
      <div class="d-flex justify-space-between align-center">
        <div class="text-subtitle-1 font-weight-medium">User Attendance Info : </div>
        <div class="ml-2 mr-2"
          style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
        </div>
      </div>
    </VCol>
    <VCol cols="12" md="4" sm="6">
      <VCard class="logistics-card-statistics cursor-pointer"
        :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
        <VCardText>
          <div class="d-flex justify-space-between align-center mb-2">
            <div class="text-center">
              <h4 class="text-h4 mb-1">
                {{ dashboardInfo.last_month_salary > 0 ? parseInt(dashboardInfo.last_month_salary) : '' }} / {{
                  dashboardInfo.user_salary ?? 0 }}
              </h4>
              <div class="text-caption">Last Month Salary</div>
            </div>

            <div class="text-center">
              <h4 class="text-h4 mb-1">
                {{ dashboardInfo.last_month_total_attendance > 0 ? dashboardInfo.last_month_present_attendance :
                  '-'
                }} / {{ dashboardInfo.last_month_total_attendance }}
              </h4>
              <div class="text-caption">Last Month Attendance</div>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="3" sm="6">
      <VCard class="logistics-card-statistics cursor-pointer"
        :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
        <VCardText>
          <div class="d-flex align-center gap-x-4 mb-1">
            <h4 class="text-h4"> {{ dashboardInfo.this_month_present_attendance }} / {{
              dashboardInfo.this_month_total_attendance }}</h4>
          </div>
          <div class="text-body-1 mb-1">This Month Attendance</div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

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
