<script setup>
import moment from 'moment';
import { toast } from 'vue3-toastify';
import Information from './tabs/Information.vue';
const route = useRoute()
const InfoData = ref()
const tab = ref(null)
const tabs = ref([
  {
    title: 'Information',
    icon: 'tabler-user',
  },
])
// Define the desired order of tabs with their component references
const tabConfig = [
  { name: 'Users', title: 'Invoices', icon: 'tabler-message' }
]
try {
  const { data } = await $api(`/targets/${route.params.id}`)
  InfoData.value = data
} catch (error) {
  console.error('Failed to fetch target data:', error)
  toast.error(error?.response?.data?.message || 'Failed to load target details.')
}
// Dynamically import modules if available
const components = import.meta.glob('@modules/*/resources/assets/js/list/index.vue')
// Process components in the desired order
tabConfig.forEach(config => {
  for (const path in components) {
    if (path.includes(`/${config.name}/`) && !eval(config.name).value) {
      eval(config.name).value = defineAsyncComponent(components[path])
      tabs.value.push({
        title: config.title,
        icon: config.icon,
      })
    }
  }
})
const makeDateFormat = (date , onlyDate = false) => {
    if(onlyDate)
    return moment(date).format('DD-MM-Y');
    else
    return moment(date).format('LLLL');
};
</script>
<template>
  <div>
     <!-- 👉 Header  -->
     <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h5 class="text-h5 mb-1">
          Target {{ InfoData.title }}
        </h5>
        <div class="text-body-1">
          {{ makeDateFormat(InfoData.created_at )}}
        </div>
      </div>
      <div class="d-flex gap-4">
        <VBtn variant="tonal" color="success" :to="{ name: 'target-list' }">
          Back
        </VBtn>
      </div>
    </div>
    <VRow v-if="InfoData">
      <VCol cols="12" md="12" lg="12">
        <VTabs v-model="tab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in tabs" :key="tab.title">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs>
        <VWindow v-model="tab" class="disable-tab-transition" :touch="false">
          <VWindowItem>
            <Information :InfoData="InfoData" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        Target with ID {{ route.params.id }} not found!
      </VAlert>
    </div>
  </div>
</template>
