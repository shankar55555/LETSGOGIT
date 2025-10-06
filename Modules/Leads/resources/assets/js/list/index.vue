<template>
  <div v-if="$can('leads', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap">
          <div>
            <h4 class="text-h4 text-center">Leads</h4>
          </div>

          <div class="d-flex gap-3">
            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-date-filter="showDateFilter"
              :initial-show-search-filter="showSearchFilter" @update:filters="updateFilters" :statusFilter="true"
              :searchFilter="true" :dateFilter="true" />

            <Actions @export-leads="exportLeads" @import-file="handleFileImport"
              @download-sample="downloadSampleExcel" />

            <VBtn icon="tabler-table-options" size="small" @click="showSyncHeader = !showSyncHeader"
              variant="outlined" />

            <VBtn v-if="$can('leads', 'create') && type != 'Not_Show'" @click="addLead()" icon="tabler-plus"
              size="small" />
          </div>
        </div>
      </VCardText>

      <VDivider class="mb-4" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter || showDateFilter">
        <AppTextField v-model="searchQuery" v-if="showSearchFilter"
          style="max-inline-size: 200px; min-inline-size: 200px;" @input="fetchLeads" placeholder="Search Name" />

        <VSelect v-model="searchStatus" v-if="showStatusFilter" @update:modelValue="(value) => fetchLeads()"
          label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;" :clearable="!!searchStatus"
          :items="statusList" item-title="status_text" item-value="slug">
        </VSelect>

        <el-date-picker v-if="showDateFilter" v-model="dateRangeSearch" type="daterange" unlink-panels
          range-separator="To" start-placeholder="Start date" end-placeholder="End date" :shortcuts="shortcuts"
          size="default" style="max-inline-size: 250px; min-inline-size: 250px;" />
      </div>

      <VDivider class="mt-4" v-if="showSearchFilter || showStatusFilter || showDateFilter || showSyncHeader" />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
          :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
          class="text-no-wrap" @update:options="updateOptions">

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
            {{ Array.isArray(item.secondary_phone) ? item.secondary_phone.join(', ') : (item.secondary_phone ?? '-') }}
          </template>

          <!-- Referral Detail -->
          <template #item.referral_detail="{ item }">
            {{ item.referral_detail || '-' }}
          </template>

          <!-- Status Info -->
          <template #item.status="{ item }">
            <template v-if="item.editing && $can('leads', 'edit')">
              <VSelect v-model="item.newStatus" :items="statusFilterPosition(statusList, item.status)"
                item-title="status_text" item-value="slug" dense hide-details label="Select Status"
                @blur="item.editing = false" @update:modelValue="() => openStatusConfirmDialog(item)" />
            </template>
            <template v-else>
              <VChip @dblclick="() => startEditing(item)" :color="$resolveStatusVariant(item.status, statusList).color"
                size="small" class="cursor-pointer">
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
            {{ dayjs(item.created_at).format('ddd, MMM D, h:mm A') }}
          </template>

          <template #item.updated_at="{ item }">
            {{ item.updater ? dayjs(item.updated_at).format('ddd, MMM D, h:mm A') : '-' }}
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
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          </template>
        </VDataTableServer>
      </VCardText>
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
</template>
<script setup>
import FilterHeaderTableBtn from '@/@core/components/cards/FilterHeaderTableBtn.vue';
import StatusConfirmDialog from '@/@core/components/StatusConfirmDialog.vue';
import { statusFilterPosition, useFetchStatusList } from "@/utils/common.js";
import dayjs from "dayjs";
import advancedFormat from "dayjs/plugin/advancedFormat";
import moment from 'moment';
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import AddEditDrawer from '../add/AddEditDrawer.vue';
import ConfirmDialog from '../dialog/ConfirmDialog.vue';


dayjs.extend(advancedFormat);
const route = useRoute()
const props = defineProps({
  type: { type: String, default: null },
})

const { statusList, fetchStatusList } = useFetchStatusList();
const searchQuery = ref('')
const searchStatus = ref('');

const isAddEditDrawerOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const loading = ref(false);
// Data table options

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentLead = ref(null);

// Data table Headers
const tableHeaderSlug = ref('lead-list');

// Filter options
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)
const showDateFilter = ref(false)
const dateRangeSearch = ref(null)
const tableHeaderDragVisible = ref(false)
const showSyncHeader = ref(false)

// Date picker shortcuts
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
  {
    text: 'Last month',
    value: () => {
      const end = new Date()
      const start = new Date()
      start.setMonth(start.getMonth() - 1)
      return [start, end]
    },
  },
  {
    text: 'Last 3 months',
    value: () => {
      const end = new Date()
      const start = new Date()
      start.setMonth(start.getMonth() - 3)
      return [start, end]
    },
  },
]
// Default headers in case API fails
const defaultHeaders = [
  { title: 'Name', key: 'name', sortable: true, checked: true },
  { title: 'Email', key: 'email', sortable: true, checked: true },
  { title: 'Phone', key: 'phone', sortable: true, checked: true },
  { title: 'Status', key: 'status', sortable: true, checked: true },
  { title: 'Created By', key: 'created_by', sortable: true, checked: true },
  { title: 'Created At', key: 'created_at', sortable: true, checked: true },
  { title: 'Action', key: 'action', sortable: false, checked: true }
];

const headers = ref(defaultHeaders);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const editBranch = (item) => {
  currentLead.value = JSON.parse(JSON.stringify(item));
  isAddEditDrawerOpen.value = true;
};

onMounted(async () => {
  await fetchStatusList(MODULE_LEAD);
  // Initialize headers from API
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({
        ...h,
        checked: typeof h.checked === 'boolean' ? h.checked : true,
      }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  await fetchLeads();
});

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}
// Now watch sortBy and orderBy to auto-fetch
watch([sortBy, orderBy], () => { fetchLeads(); });

// Add watch for page changes
watch(() => page.value, () => {
  fetchLeads();
});

// Add watch for items per page changes
watch(() => itemsPerPage.value, () => {
  page.value = 1; // Reset to first page when changing items per page
  fetchLeads();
});

const dataItems = ref([])
const totalItems = ref(0)
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
}

const openDeleteDialog = (item) => {
  currentLead.value = JSON.parse(JSON.stringify(item));
  isDeleteDialogOpen.value = true;
}

const refresh = () => {
  fetchLeads();
}

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showDateFilter.value = filters.showDateFilter
  showSearchFilter.value = filters.showSearchFilter
}

// Close table header drag visible
const closeTableHeaderDragVisible = () => {
  tableHeaderDragVisible.value = false
}

const makeDateFormat = (date, onlyDate = false) => {
  if (onlyDate)
    return moment(date).format('DD-MM-Y');
  else
    return moment(date).format('LLLL');
};

// Status Update  All function 
const isStatusConfirmVisible = ref(false);
const confirmStatus = ref(null);
const statusLoader = ref(false);

// Start editing and initialize newStatus
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

// Cancel confirmation and revert value
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

// Confirm update and save to server
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
    // if (item.status == 'convert_to_client') {
    fetchLeads();
    // }
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
  skippedRecords: []
});

const closeImportModal = () => {
  showImportModal.value = false;
  importResults.value = {
    success: '',
    warnings: [],
    skippedRecords: []
  };
};

const handleFileImport = async (file) => {
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
  }
};

</script>
