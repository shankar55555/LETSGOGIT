<script setup>
import moment from 'moment';
import { toast } from 'vue3-toastify';
import Information from './tabs/Information.vue';

const route = useRoute()
const vendorData = ref(null)
const tab = ref(null)
const loading = ref(true)

const tabs = [
  {
    title: 'Information',
    icon: 'tabler-user',
  }
]

onMounted(async () => {
  try {
    loading.value = true
    const { data } = await $api(`/vendors/${route.params.id}`)
    vendorData.value = data
  } catch (error) {
    console.error('Failed to fetch Vendor data:', error)
    toast.error(error?.response?.data?.message || 'Failed to load Vendor details.')
  } finally {
    loading.value = false
  }
})

const makeDateFormat = (date, onlyDate = false) => {
  if (!date) return '-';
  if (onlyDate)
    return moment(date).format('DD-MM-YYYY');
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
          Vendor {{ vendorData?.full_name }}
        </h5>
        <div class="text-body-1" v-if="vendorData?.created_at">
          {{ makeDateFormat(vendorData.created_at) }}
        </div>
      </div>
      <div class="d-flex gap-4">
        <VBtn variant="tonal" color="success" :to="{ name: 'product-service-vendor' }">
          Back
        </VBtn>
      </div>
    </div>

    <VRow v-if="vendorData">
      <VCol cols="12" md="12" lg="12">
        <VTabs v-model="tab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in tabs" :key="tab.title">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs>
        <VWindow v-model="tab" class="disable-tab-transition" :touch="false">
          <VWindowItem>
            <Information :vendorData="vendorData" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>

    <VOverlay :model-value="loading" class="align-center justify-center">
      <VProgressCircular indeterminate />
    </VOverlay>

    <div v-if="!loading && !vendorData">
      <VAlert type="error" variant="tonal">
        Vendor with ID {{ route.params.id }} not found!
      </VAlert>
    </div>
  </div>
</template>