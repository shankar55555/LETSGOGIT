<script setup>
import moment from 'moment';
import { toast } from 'vue3-toastify';

import ConfirmDialog from '../dialog/ConfirmDialog.vue';
const searchQuery = ref('')
const isDeleteDialogOpen = ref(false)

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentProductService = ref(null);
const loader = ref(false);

const tableHeaderSlug = ref('product-list');
const headers = ref([]);
const showSyncHeader = ref(false);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}
const dataItems = ref([])
const totalItems = ref(0)

const fetchProductServices = async () => {
  loader.value = true;
  try {
    const response = await $api(
      `/product?search=${searchQuery.value ?? ""}&page=${page.value}&sort_key=${sortBy.value ?? ""}&sort_order=${orderBy.value ?? ""}&per_page=${itemsPerPage.value}`
    )

    dataItems.value = response.data
    totalItems.value = response.meta.total
    loader.value = false;

  } catch (err) {
    console.error('Failed to fetch Product:', err)
    // Optionally show a toast
    loader.value = false;

    toast.error('Failed to load Product')
  }
}

onMounted(async () => {
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  fetchProductServices();
})

const openDeleteDialog = (item) => {
  currentProductService.value = JSON.parse(JSON.stringify(item));
  isDeleteDialogOpen.value = true;
}

const refresh = () => {
  fetchProductServices();
}
const makeDateFormat = (date, onlyDate = false) => {
  if (onlyDate)
    return moment(date).format('DD-MM-Y');
  else
    return moment(date).format('LLLL');
};
</script>

<template>
  <div>
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <div>
            <h4 class="text-h4 text-center">Product</h4>
          </div>

          <div class="d-flex gap-2">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <Filters :initial-show-search-filter="showSearchFilter" @update:filters="updateFilters"
              :searchFilter="true" />

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />
            <VBtn :to="{ name: 'product-create' }" icon="tabler-plus" size="small">
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider class="mb-3" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter">
        <AppTextField v-model="searchQuery" @input="fetchProductServices"
          style="max-inline-size: 280px; min-inline-size: 280px;" placeholder="Search Name" class="ml-5" />
      </div>

      <VDivider class="mt-5" v-if="showSearchFilter || showSyncHeader" />

      <BaseSpinner class="d-flex" v-if="loader" />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
        :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
        class="text-no-wrap" @update:options="updateOptions" v-else>

        <template #item.title="{ item }">
          <RouterLink :to="{ name: 'product-details-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
            {{ item.title }}
          </RouterLink>
        </template>

        <!-- Actions Column -->
        <template #item.action="{ item }">

          <IconBtn :to="{ name: 'product-details-id', params: { id: item.id } }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn :to="{ name: 'product-edit', params: { id: item.id } }">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>

        <template #item.created_by="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <!-- updater -->
        <template #item.last_updated_by="{ item }">
          {{ item.updater?.name || '-' }}
        </template>
        <!-- created_at -->
        <template #item.created_at="{ item }">
          {{ makeDateFormat(item.created_at) }}
        </template>
        <!-- updated_at -->
        <template #item.updated_at="{ item }">
          {{ item.updater ? makeDateFormat(item.updated_at) : '-' }}

        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete product/service?" :currentItem="currentProductService"
      @submit="refresh" :endpoint="`/product/${currentProductService?.id}`" @close="isDeleteDialogOpen = false" />

  </div>
</template>
