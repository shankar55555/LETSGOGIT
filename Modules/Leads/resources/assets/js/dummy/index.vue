<template>
  <div>
    <VRow class="mb-3">
      <VCol cols="12" lg="3" md="3" sm="12">
        <TotalLeadsChart />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="12">
        <ConvertedLeadsChart />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="12">
        <NewLeadsChart />
      </VCol>
      <VCol cols="12" lg="3" md="3" sm="12">
        <InProgressLeadsChart />
      </VCol>
    </VRow>

    <div v-if="$can('leads', 'view')">
      <VCard>
        <VCardText>
          <div class="d-flex justify-space-between flex-wrap">
            <div>
              <h4 class="text-h4 text-center">Leads</h4>
            </div>

            <div class="d-flex gap-3">
              <VMenu :close-on-content-click="false">
                <template v-slot:activator="{ props }">
                  <VBtn v-bind="props" variant="outlined" color="primary" icon="tabler-filter" size="small"
                    v-tooltip="'Lead Filters'" />
                </template>

                <VCard>
                  <VList>
                    <VListItem>
                      <VCheckbox v-model="showStatusFilter" label="Filter by Status" hide-details density="compact" />
                    </VListItem>
                    <VListItem>
                      <VCheckbox v-model="showDateFilter" label="Filter by Date" hide-details density="compact" />
                    </VListItem>
                    <VListItem>
                      <VCheckbox v-model="showSearchFilter" label="Search" hide-details density="compact" />
                    </VListItem>
                  </VList>
                </VCard>
              </VMenu>

              <VMenu>
                <template v-slot:activator="{ props }">
                  <VBtn v-bind="props" variant="outlined" color="primary" v-tooltip="'Lead Actions'" size="small"
                    icon="tabler-files" />
                </template>
                <VList class="box_shadow">
                  <VListItem class="mx-0" v-if="$can('leads', 'export-list')">
                    <VBtn prepend-icon="tabler-download" @click="exportLeads" variant="outlined" size="small"
                      color="primary">
                      Export
                    </VBtn>
                  </VListItem>
                  <VListItem class="mx-0" v-if="$can('leads', 'create')">
                    <VBtn prepend-icon="tabler-upload" @click="$refs.fileInput.click()" variant="outlined" size="small"
                      color="primary">
                      Upload</VBtn>
                    <input ref="fileInput" type="file" accept=".xls,.xlsx" style="display: none;"
                      @change="handleFileImport" />
                  </VListItem>
                  <VListItem class="mx-0" v-if="$can('leads', 'export-list')">
                    <VBtn prepend-icon="tabler-download" @click="downloadSampleExcel" variant="outlined" size="small"
                      color="secondary">
                      Sample</VBtn>
                  </VListItem>
                </VList>
              </VMenu>

              <VBtn v-if="$can('leads', 'create') && type != 'Not_Show'" @click="addLead()" icon="tabler-plus"
                size="small" />

              <VMenu>
                <template v-slot:activator="{ props }">
                  <VBtn v-bind="props" variant="outlined" color="primary" v-tooltip="'More Options'" size="small"
                    icon="tabler-dots-vertical">
                  </VBtn>
                </template>

                <VCard class="box_shadow" elevation="2">
                  <VList>
                    <VListItem class="mx-0">
                      <VBtn color="primary" variant="outlined" rounded="3"
                        @click="tableHeaderDragVisible = !tableHeaderDragVisible" size="small">
                        Arrange Column
                        <template #prepend>
                          <VIcon icon="tabler-columns-3" />
                        </template>
                      </VBtn>
                    </VListItem>
                    <VListItem class="mx-0">
                      <VBtn v-if="$can('leads', 'view')" variant="outlined" @click="refresh()" class="w-100"
                        size="small">
                        Refresh
                        <template #prepend>
                          <VIcon icon="tabler-refresh" />
                        </template>
                      </VBtn>
                    </VListItem>
                  </VList>
                </VCard>
              </VMenu>
            </div>
          </div>
        </VCardText>
        <VDivider />
        <VCardText v-if="showSearchFilter || showStatusFilter || showDateFilter">
          <div class="d-flex gap-2 justify-start">
            <AppTextField v-model="searchQuery" v-if="showSearchFilter"
              style="max-inline-size: 200px; min-inline-size: 200px;" @input="fetchLeads" placeholder="Search Name" />

            <VSelect v-model="searchStatus" v-if="showStatusFilter" @update:modelValue="(value) => fetchLeads()"
              label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;"
              :clearable="!!searchStatus" :items="statusList" item-title="status_text" item-value="slug">
            </VSelect>

            <el-date-picker v-if="showDateFilter" v-model="dateRangeSearch" type="daterange" unlink-panels
              range-separator="To" start-placeholder="Start date" end-placeholder="End date" :shortcuts="shortcuts"
              size="default" style="max-inline-size: 250px; min-inline-size: 250px;" />
          </div>
        </VCardText>
        <VCardText v-if="tableHeaderDragVisible">
          <FilterHeaderTableBtnCopy :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
            @tableHeaderDragVisible="CloseTableHeaderDragVisible" />
        </VCardText>

        <VDivider />
        <BaseSpinner class="d-flex" v-if="loading" />
        <VCardText v-else class="px-0">
          <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems"
            item-value="name" :headers="headers.filter((header) => header.checked)" :items-length="totalItems"
            class="text-no-wrap sticky-table" @update:options="updateOptions">

            <template #item.name="{ item }">
              <RouterLink :to="{ name: 'lead-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                {{ item.name }}
              </RouterLink>
            </template>

            <!-- creator -->
            <template #item.created_by="{ item }">
              {{ item.creator?.name || '—' }}
            </template>

            <!-- updater -->
            <template #item.last_updated_by="{ item }">
              {{ item.updater?.name || '-' }}
            </template>

            <!-- assigned_user -->
            <template #item.assigned_user="{ item }">
              {{ item.assigned_user?.name || '-' }}
            </template>

            <!--secondary number -->
            <template #item.secondary_phone="{ item }">
              {{ Array.isArray(item.secondary_phone) ? item.secondary_phone.join(', ') : (item.secondary_phone ?? '-')
              }}
            </template>

            <!-- Status Info -->
            <template #item.status="{ item }">
              <template v-if="item.editing && $can('leads', 'edit')">
                <VSelect v-model="item.newStatus" :items="statusFilterPosition(statusList, item.status)"
                  item-title="status_text" item-value="slug" dense hide-details label="Select Status"
                  @blur="item.editing = false" @update:modelValue="() => openStatusConfirmDialog(item)" />
              </template>
              <template v-else>
                <VChip @dblclick="() => startEditing(item)"
                  :color="$resolveStatusVariant(item.status, statusList).color" size="small" class="cursor-pointer">
                  {{ $resolveStatusVariant(item.status, statusList).text }}
                </VChip>
              </template>
            </template>

            <!-- Site Visit Status -->
            <template #item.last_site_visit_status="{ item }">
              <div v-if="item.last_site_visit_status?.title">
                <VChip :color="item.last_site_visit_status?.color || 'default'" size="small">
                  {{ item.last_site_visit_status.title }}
                </VChip>
              </div>
              <div v-else>-</div>
            </template>

            <!-- Follow-up Status -->
            <template #item.last_followup_status="{ item }">
              <div v-if="item.last_followup_status?.title">
                <VChip :color="item.last_followup_status?.color || 'default'" size="small">
                  {{ item.last_followup_status.title }}
                </VChip>
              </div>
              <div v-else>-</div>
            </template>

            <!-- Quotation Status -->
            <template #item.last_quotation_status="{ item }">
              <div v-if="item.last_quotation_status?.title">
                <VChip :color="item.last_quotation_status?.color || 'default'" size="small">
                  {{ item.last_quotation_status.title }}
                </VChip>
              </div>
              <div v-else>-</div>
            </template>


            <template #item.created_at="{ item }">
              {{ makeDateFormat(item.created_at) }}
            </template>

            <template #item.updated_at="{ item }">
              {{ item.updater ? makeDateFormat(item.updated_at) : '-' }}
            </template>

            <!-- City -->
            <template #item.city_id="{ item }">
              {{ item.city_name || '-' }}
            </template>

            <!-- Date of Birth -->
            <template #item.date_of_birth="{ item }">
              {{ item.date_of_birth ? formatAnniversaryDate(item.date_of_birth) : '-' }}
            </template>

            <!-- Anniversary Date -->
            <template #item.anniversary_date="{ item }">
              {{ item.anniversary_date ? formatAnniversaryDate(item.anniversary_date) : '-' }}
            </template>

            <!-- Actions Column -->
            <template #item.action="{ item }">
              <IconBtn :to="{ name: 'lead-details-id', params: { id: item.id } }" v-tooltip="'Lead View Info'">
                <VIcon icon="tabler-eye" />
              </IconBtn>
              <IconBtn v-if="$can('leads', 'edit')" v-tooltip="'Lead Update'" @click="editBranch(item)">
                <VIcon icon="tabler-pencil" />
              </IconBtn>
              <IconBtn v-if="$can('leads', 'delete')" v-tooltip="'Lead Delete'" @click="openDeleteDialog(item)">
                <VIcon icon="tabler-trash" />
              </IconBtn>

            </template>
            <template #bottom>
              <VRow>
                <VCol cols="10">
                  <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
                </VCol>
                <VCol cols="2">
                  <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" class="py-4 px-4" />
                </VCol>
              </VRow>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>

      <VCard class="mt-10">
        <VMenu :close-on-content-click="false">
          <template v-slot:activator="{ props }">
            <VBtn v-bind="props" variant="outlined" color="primary" text="Demo table" size="small"
              v-tooltip="'Lead Filters'" />
          </template>

          <VCard>
            <Demo />
          </VCard>
        </VMenu>
      </VCard>

      <!-- 👉 Confirm Dialog -->
      <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
        confirmation-question="Are you sure want to delete lead?" :currentItem="currentLead" @submit="refresh"
        :endpoint="`/leads/${currentLead?.id}`" @close="isDeleteDialogOpen = false" />

      <AddEditDrawer v-model:is-drawer-open="isAddEditDrawerOpen" :currentLead="currentLead" @submit="refresh"
        @close="isAddEditDrawerOpen = false" />

      <!-- 👉 Status Confirm Dialog -->
      <StatusConfirmDialog v-model:isStatusConfirmVisible="isStatusConfirmVisible" :currentItem="confirmStatus"
        :loader="statusLoader" :statusList="statusList" @updateStatusValue="updateStatusValue"
        @close="statusConfirmClear" />

      <!-- Import Results Modal -->
      <VDialog v-model="showImportModal" width="600">
        <VCard>
          <VCardTitle class="d-flex justify-space-between align-center pa-4">
            <span>Import Results</span>
            <VBtn icon variant="text" @click="closeImportModal">
              <VIcon>tabler-x</VIcon>
            </VBtn>
          </VCardTitle>
          <VDivider />
          <VCardText class="pa-4">
            <!-- Success Message -->
            <div v-if="importResults.success">
              <VAlert color="success" variant="tonal" class="mb-2">
                {{ importResults.success }}
              </VAlert>
            </div>

            <!-- Skipped Records -->
            <div
              v-if="importResults.skippedRecords && importResults.skippedRecords.filter(record => !record.includes('Successfully') && !record.includes('Skipped Records:')).length > 0">
              <VAlert color="warning" variant="tonal" class="mb-2">
                Skipped Records: {{importResults.skippedRecords.filter(record => !record.includes('Successfully') &&
                  !record.includes('Skipped Records:')).length}}
              </VAlert>
              <VList density="compact">
                <VListItem
                  v-for="(record, index) in importResults.skippedRecords.filter(r => !r.includes('Successfully') && !r.includes('Skipped Records:'))"
                  :key="index" class="text-warning">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-alert-circle" color="warning" class="mr-2" />
                    {{ record }}
                  </div>
                </VListItem>
              </VList>
            </div>
          </VCardText>
          <VCardActions class="pa-4">
            <VSpacer />
            <VBtn color="primary" @click="closeImportModal">
              Close
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </div>
</template>
<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import StatusConfirmDialog from '@/@core/components/StatusConfirmDialog.vue';

import moment from 'moment';
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import AddEditDrawer from '../add/AddEditDrawer.vue';
import ConvertedLeadsChart from '../components/ConvertedLeadsChart.vue';
import Demo from "../components/Demo.vue";
import InProgressLeadsChart from '../components/InProgressLeadsChart.vue';
import NewLeadsChart from '../components/NewLeadsChart.vue';
import TotalLeadsChart from '../components/TotalLeadsChart.vue';

const route = useRoute();
const props = defineProps({
  type: { type: String, default: null },
});


const { statusList, fetchStatusList } = useFetchStatusList();
const searchQuery = ref('');
const searchStatus = ref('');
const tableHeaderDragVisible = ref(false);

const isAddEditDrawerOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const loading = ref(false);
const showStatusFilter = ref(false);
const showDateFilter = ref(false);
const showSearchFilter = ref(false)

const dateRangeSearch = ref();
const shortcuts = [
  {
    text: 'Last week',
    value: () => {
      const end = new Date()
      const start = new Date()
      start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
      return [start, end]
    },
  },
];

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref();
const orderBy = ref();
const currentLead = ref(null);

// // Data table headers
// const tableHeaderSlug = ref('lead-list');
// const headers = ref([
//   { title: 'Name', key: 'name', checked: true, sortable: true },
//   // { title: 'Created By', key: 'created_by', checked: true },
//   // { title: 'Last Updated By', key: 'last_updated_by', checked: true },
//   { title: 'Assigned User', key: 'assigned_user', checked: true },
//   // { title: 'Secondary Phone', key: 'secondary_phone', checked: true },
//   { title: 'Status', key: 'status', checked: true },
//   // { title: 'Last Site Visit', key: 'last_site_visit_status', checked: true },
//   { title: 'Last Follow-up', key: 'last_followup_status', checked: true },
//   // { title: 'Last Quotation', key: 'last_quotation_status', checked: true },
//   { title: 'Created At', key: 'created_at', checked: true },
//   // { title: 'Updated At', key: 'updated_at', checked: true },
//   { title: 'City', key: 'city_id', checked: true },
//   { title: 'Date of Birth', key: 'date_of_birth', checked: true },
//   { title: 'Anniversary', key: 'anniversary_date', checked: true },
//   { title: 'Actions', key: 'action', checked: true },
// ]);

// const getFilteredHeaderValue = async (headerList) => {
//   headers.value = headerList;
// };

const CloseTableHeaderDragVisible = (val) => {
  tableHeaderDragVisible.value = val;
}

const tableHeaderSlug = ref('lead-list');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => {
  if (headerList && headerList.length) {
    headers.value = headerList;
  }
};

const editBranch = (item) => {
  currentLead.value = JSON.parse(JSON.stringify(item));
  isAddEditDrawerOpen.value = true;
};

onMounted(async () => {
  await fetchStatusList(MODULE_LEAD);
  // Fetch headers
  try {
    const response = await $api("/table-header/get", { params: { slug: tableHeaderSlug.value } });
    headers.value = response.data?.headers ?? [];
  } catch (err) {
    toast.error(err.response?.data?.message || "Failed to load headers.");
  }
  await fetchLeads();
});

const updateOptions = (options) => {
  sortBy.value = options.sortBy[0]?.key;
  orderBy.value = options.sortBy[0]?.order;
};

// Watch sortBy, orderBy, page, and itemsPerPage to refetch data
watch([sortBy, orderBy], () => fetchLeads());
watch(() => page.value, () => fetchLeads());
watch(() => itemsPerPage.value, () => {
  page.value = 1; // Reset to first page
  fetchLeads();
});

const dataItems = ref([]);
const totalItems = ref(0);

// Fetch data from dummy API
const fetchLeads = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      search: searchQuery.value ?? '',
      page: page.value,
      sort_key: sortBy.value ?? '',
      sort_order: orderBy.value ?? '',
      per_page: itemsPerPage.value,
      status: searchStatus.value ?? ''
    });

    if (route.name === USER_VIEW_ID) params.append('user_view_id', route.params.id);
    const response = await $api(`/leads?${params.toString()}`);
    dataItems.value = response.data || [];
    totalItems.value = response.meta?.total || 0;
  } catch (err) {
    toast.error(err?.response?.data?.message || err?._data?.message || 'Error fetching leads.');
  } finally {
    loading.value = false;
  }
};

const addLead = (item) => {
  currentLead.value = null;
  isAddEditDrawerOpen.value = true;
};

const openDeleteDialog = (item) => {
  currentLead.value = JSON.parse(JSON.stringify(item));
  isDeleteDialogOpen.value = true;
};

const refresh = () => {
  fetchLeads();
};

const makeDateFormat = (date, onlyDate = false) => {
  if (onlyDate)
    return moment(date).format('DD-MM-Y');
  else
    return moment(date).format('LLLL');
};

// Status Update Functions
const isStatusConfirmVisible = ref(false);
const confirmStatus = ref(null);
const statusLoader = ref(false);

const startEditing = (item) => {
  item.newStatus = item.status;
  item.editing = true;
};

const openStatusConfirmDialog = (item) => {
  if (!item.newStatus || item.newStatus === item.status) return;

  confirmStatus.value = {
    id: item.id,
    oldStatus: item.status,
    newStatus: item.newStatus,
  };

  isStatusConfirmVisible.value = true;
};

const formatAnniversaryDate = (date) => {
  if (!date) return '';
  return moment(date).format('DD-MMM-YYYY');
};

const statusConfirmClear = () => {
  const item = dataItems.value.find((i) => i.id === confirmStatus.value.id);
  if (item) {
    item.newStatus = confirmStatus.value.oldStatus;
    item.status = confirmStatus.value.oldStatus;
    item.editing = false;
  }
  isStatusConfirmVisible.value = false;
  confirmStatus.value = null;
  statusLoader.value = false;
};

const updateStatusValue = async () => {
  const item = dataItems.value.find((i) => i.id === confirmStatus.value.id);
  if (!item) return;

  statusLoader.value = true;
  try {
    await $api('/update-direct-lead-status', {
      method: 'POST',
      body: {
        id: item.id,
        status: confirmStatus.value.newStatus,
      },
    });

    toast.success('Status updated successfully');
    item.status = confirmStatus.value.newStatus;
    item.editing = false;
    if (item.status == 'convert_to_client') {
      fetchLeads();
    }
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to update status');
  } finally {
    isStatusConfirmVisible.value = false;
    confirmStatus.value = null;
    statusLoader.value = false;
  }
};

watch(dataItems, (items) => {
  if (items) {
    items.forEach(item => {
      if (!item.editing) {
        item.editing = false;
      }
    });
  }
}, { deep: true });

// Excel Import/Export Functions
const fileInput = ref(null);

const downloadSampleExcel = async () => {
  try {
    const response = await $api('/leads/download-sample', {
      method: 'GET',
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'leads-sample.xlsx');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    toast.error('Failed to download sample file');
  }
};

const exportLeads = async () => {
  try {
    const response = await $api('/leads/export', {
      method: 'GET',
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'leads-export.xlsx');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    toast.error('Failed to export leads');
  }
};

const showImportModal = ref(false);
const importResults = ref({
  success: '',
  warnings: [],
  skippedRecords: [],
});

const closeImportModal = () => {
  showImportModal.value = false;
  importResults.value = {
    success: '',
    warnings: [],
    skippedRecords: [],
  };
};

const handleFileImport = async (event) => {
  const file = event.target.files[0];
  if (!file) {
    toast.error('Please select a file');
    return;
  }

  const formData = new FormData();
  formData.append('file', file);

  try {
    loading.value = true;
    const response = await $api('/leads/import', {
      method: 'POST',
      body: formData,
    });

    // Prepare modal content
    importResults.value = {
      success: response.message,
      skippedRecords: response.dialog_messages || []
    };

    // Show the modal
    showImportModal.value = true;

    fetchLeads();
  } catch (error) {
    console.error('Upload error:', error);
    const errorMessages = error?._data?.dialog_messages || [error?._data?.message || 'Failed to import leads'];
    importResults.value = {
      success: '',
      skippedRecords: errorMessages
    };
    showImportModal.value = true;
  } finally {
    loading.value = false;
    event.target.value = ''; // Reset file input
  }
};
</script>
