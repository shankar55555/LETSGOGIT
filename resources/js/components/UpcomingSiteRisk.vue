<script setup>
import { resolveStatusVariant, useFetchStatusList } from "@/utils/common";
import dayjs from 'dayjs';
import { ref } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true
  }
});

const tableHeaderSlug = ref('upcoming-site-visit');
const headers = ref([]);
// const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };
const getFilteredHeaderValue = async (headerList) => {
  if (!Array.isArray(headerList)) {
    console.error('getFilteredHeaderValue: Expected an array, received:', headerList);
    headers.value = [];
    return;
  }
  headers.value = headerList;
};

const searchQuery = ref('');
const upcomingSiteData = ref([]);

const totalItems = ref(0)
const itemsPerPage = ref(10)
const page = ref(1)

const validatedPage = computed(() => {
  const maxPage = Math.ceil(totalItems.value / itemsPerPage.value);
  return Math.min(Math.max(page.value, 1), maxPage || 1);
});

const { statusList, fetchStatusList } = useFetchStatusList();

const fetchUpcomingSite = async () => {
  const data = await $api('/upcoming-srm');
  upcomingSiteData.value = data.data ?? [];
  totalItems.value = upcomingSiteData.value.length;
}

const refreshData = async () => {
  await fetchUpcomingSite()
}
onMounted(async () => {
  await fetchStatusList(MODULE_SITE_VISIT);
  fetchUpcomingSite();
});

</script>
<template>
  <div v-if="$can('siteVisit', 'view')">
    <div class="d-flex justify-space-between align-center mt-2 mb-2">
      <div class="text-subtitle-1 font-weight-medium">{{ title }}</div>
      <div class="mx-2 my-3"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
            placeholder="Search By Visit Note" @input="refreshData" />

          <div class="d-flex flex-row gap-4 align-center flex-wrap">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />
            <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />

            <VBtn v-if="$can('siteVisit', 'view')" variant="tonal" @click="refreshData">
              <VIcon icon="tabler-refresh" />
            </VBtn>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="upcomingSiteData"
        item-value="name" :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
        class="text-no-wrap">

        <template #item.visit_type="slotProps">
          <RouterLink :to="{ name: slotProps.item.route_name, params: { id: slotProps.item.route_id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
            {{ slotProps.item.visit_type }}
          </RouterLink>
        </template>

        <template #item.visit_time="{ item }">
          {{ item.visit_time != null && dayjs(item.visit_time).isValid() ? dayjs(item.visit_time).format('DD-MM-YYYY') :
            '-' }}
        </template>

        <template #item.upcoming_time="{ item }">
          {{ item.upcoming_time != null && dayjs(item.upcoming_time).isValid() ?
            dayjs(item.upcoming_time).format('DD-MM-YYYY') : '-' }}
        </template>

        <template #item.status="{ item }">
          <VChip :color="resolveStatusVariant(item.status, statusList).color" size="small" class="cursor-pointer">
            {{ resolveStatusVariant(item.status, statusList).text }}
          </VChip>
        </template>

        <template #item.visit_notes="{ item }">
          {{ item.visit_notes ?? '-' }}
        </template>

        <template #item.items="{ item }">
          {{ Array.isArray(item.items) ? item.items.join(', ') : (item.items ?? '-') }}
        </template>


        <template #bottom>
          <TablePagination v-model:page="validatedPage" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
