<script setup>
import moment from 'moment';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import ConfirmDialog from '../dialog/ConfirmDialog.vue';
const route = useRoute();
const searchQuery = ref('')
const isDeleteDialogOpen = ref(false)
const props = defineProps({
  type: { type: String, default: null },
})
// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentContract = ref(null);


const tableHeaderSlug = ref('contract-list');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };



const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchContracts();
}

const dataItems = ref([])
const totalItems = ref(0)
const fetchContracts = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      page: page.value,
      sort_key: sortBy.value,
      sort_order: orderBy.value,
      per_page: itemsPerPage.value,
      status: searchStatus?.value ?? "",
    });

    if (route.name === USER_VIEW_ID) {
      params.append('user_view_id', route.params.id);
    }

    const response = await $api(`/contracts?${params.toString()}`);
    dataItems.value = response.data || [];
    totalItems.value = response.meta?.total || 0;
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to load Contracts');
    console.error('Failed to fetch Contracts:', error);
  } finally {
    loading.value = false;
  }
};

const openDeleteDialog = (item) => {
  currentContract.value = JSON.parse(JSON.stringify(item));
  isDeleteDialogOpen.value = true;
}

const refresh = () => {
  fetchContracts();
}
 
const makeDateFormat = (date , onlyDate = false) => {
    if(onlyDate)
    return moment(date).format('DD-MM-Y');
    else
    return moment(date).format('LLLL');
};
</script>

<template>
  <div v-if="$can('contract', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
            @input="fetchContracts"
            placeholder="Search Title" />
          <div class="d-flex flex-row gap-4 align-center flex-wrap">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <VBtn v-if="$can('contract', 'export-list')" prepend-icon="tabler-upload" variant="tonal" color="secondary">
              Export
            </VBtn>
            <VBtn v-if="$can('contract', 'create') && type != 'Not_Show'" :to="{ name: 'contract-create' }" prepend-icon="tabler-plus">
              Add New
            </VBtn>
            <!-- Filter Header Btn FilterHeaderTableBtn -->
            <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />
          </div>
        </div>
      </VCardText>

      <VDivider />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
        :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
        class="text-no-wrap" @update:options="updateOptions">

        <template #item.title="{ item }">
        <RouterLink :to="{ name: 'contract-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                {{ item.title }}
        </RouterLink>
        </template>

        <!-- Actions Column -->
        <template #item.action="{ item }">

          <IconBtn :to="{ name: 'contract-details-id', params: { id: item.id } }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn :to="{ name: 'contract-edit', params: { id: item.id } }">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="$can('contract', 'delete')" @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>

        <!-- status -->
        <template #item.status="{ item }">
          <VChip :color="item.status_data?.status_color??'primary'" size="small">
            {{ item.status_data?.status_text ?? item.status }}
          </VChip>
        </template>

        <!-- sub_total -->
        <template #item.sub_total="{ item }">
          ${{ item.sub_total || 0 }}
        </template>
        <!-- discount -->
        <template #item.discount="{ item }">
          ${{ item.discount || 0 }}
        </template>
        <!-- total -->
        <template #item.tax="{ item }">
          ${{ item.tax || 0 }}
        </template>
        <!-- total -->
        <template #item.total="{ item }">
          ${{ item.total || 0 }}
        </template>
        <!-- creator -->
        <template #item.created_by="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <!-- updater -->
        <template #item.last_updated_by="{ item }">
          {{ item.updater?.name || '-' }}
        </template>
        <!-- start_date -->
        <template #item.start_date="{ item }">
          {{ item.start_date ? makeDateFormat(item.start_date, true) : '-'}}
        </template>
        <!-- end_date -->
        <template #item.end_date="{ item }">
          {{ item.end_date ? makeDateFormat(item.end_date, true) : '-'}}
        </template>
        <!-- created_at -->
        <template #item.created_at="{ item }">
          {{ makeDateFormat(item.created_at )}}
        </template>
        <!-- updated_at -->
        <template #item.updated_at="{ item }">
          {{ item.updater ? makeDateFormat(item.updated_at ) : '-'}}

        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete contract?" :currentItem="currentContract"
      @submit="refresh" :endpoint="`/contracts/${currentContract?.id}`" @close="isDeleteDialogOpen = false" />

  </div>
</template>
