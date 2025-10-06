<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import dayjs from "dayjs";
import advancedFormat from "dayjs/plugin/advancedFormat";
import utc from 'dayjs/plugin/utc';
import moment from 'moment';
import { computed, ref, watch } from 'vue';
import { useRoute } from "vue-router";
import { toast } from 'vue3-toastify';
import AddDrawer from '../add/AddDrawer.vue';
import ConfirmDialog from '../dialog/ConfirmDialog.vue';
dayjs.extend(utc);
dayjs.extend(advancedFormat);


const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}
const route = useRoute();
// State Management
const state = ref({
  searchQuery: '',
  isAddEditDrawerOpen: false,
  isDeleteDialogOpen: false,
  itemsPerPage: 10,
  page: 1,
  sortBy: null,
  orderBy: null,
  currentSiteVisit: null,
  tableHeaderSlug: 'site-visit',
  headers: [],
  dataItems: [],
  totalItems: 0,
  isLoading: false
});
// Computed Properties
const filteredHeaders = computed(() => state.value.headers.filter(header => header.checked));
const paginationParams = computed(() => ({
  page: state.value.page,
  per_page: state.value.itemsPerPage,
  sort_key: state.value.sortBy,
  sort_order: state.value.orderBy
}));

// Props with TypeScript-like validation
const props = defineProps({
  type: {
    type: String,
    default: null,
    validator: (value) => [QUOTATION_LEAD, QUOTATION_CLIENT].includes(value)
  }
});
const searchByType = ref("");
const Types = [{ 'title': 'Inspection', 'value': 'inspection' }, { 'title': 'Installation', 'value': 'installation' }, { 'title': 'Other', 'value': 'other' },]
// Optimized data fetching
const fetchSiteVisits = async () => {
  try {
    state.value.isLoading = true;
    const baseUrl = '/site-visit';
    const queryParams = new URLSearchParams({
      search: state.value.searchQuery,
      ...paginationParams.value
    });
    if (props.type === QUOTATION_LEAD) {
      queryParams.append('lead_id', route.params.id);
    } else if (props.type === QUOTATION_CLIENT) {
      queryParams.append('client_id', route.params.id);
    }

    if (searchByType.value) {
      queryParams.append('visit_type', searchByType.value);
    }
    const response = await $api(`${baseUrl}?${queryParams.toString()}`);
    state.value.dataItems = response.data;
    state.value.totalItems = response.meta?.total ?? 0;
  } catch (err) {
    console.error('Failed to fetch site visits:', err);
    toast.error('Failed to load site visits');
  } finally {
    state.value.isLoading = false;
  }
};
// Optimized methods
const editBranch = (item) => {
  state.value.currentSiteVisit = { ...item };
  state.value.isAddEditDrawerOpen = true;
};
const addSiteVisit = () => {
  state.value.currentSiteVisit = null;
  state.value.isAddEditDrawerOpen = true;
};
const openDeleteDialog = (item) => {
  state.value.currentSiteVisit = { ...item };
  state.value.isDeleteDialogOpen = true;
};
const refresh = () => {
  fetchSiteVisits();
};

// Optimized date formatting
const makeDateFormat = (date, onlyDate = false) => {
  const m = moment.utc(date); // Use UTC explicitly
  return onlyDate
    ? m.format('DD-MM-YYYY')
    : m.format('dddd, MMMM Do YYYY, h:mm A');
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
    await $api('/update-direct-visitSite-status', {
      method: 'POST',
      body: {
        id: item.id,
        status: confirmStatus.value.newStatus,
      },
    });

    toast.success('Status updated successfully');
    item.status = confirmStatus.value.newStatus;
    item.editing = false;
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to update status');
  } finally {
    isStatusConfirmVisible.value = false;
    confirmStatus.value = null;
    statusLoader.value = false;
  }
};

// Watchers for pagination changes
watch([() => state.value.page, () => state.value.itemsPerPage], () => {
  fetchSiteVisits();
});

// Initial data fetch
fetchSiteVisits();

const challanLoading = ref(false);
const generateChallan = async (item) => {
  try {
    challanLoading.value = true;

    const response = await $api("/generate-challan", {
      params: { id: item.id },
      responseType: 'blob', // Important: Tell Axios to expect binary data
    });

    const url = window.URL.createObjectURL(new Blob([response]));
    const link = document.createElement('a');
    link.href = url;

    const filename = `challan_${item.id || 'download'}.pdf`;
    link.setAttribute('download', filename);

    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error generating challan:", error);
    toast.error(
      error?.response?.data?.message || "Failed to generate Checklist."
    );
  } finally {
    challanLoading.value = false;
  }
};

const { statusList, fetchStatusList } = useFetchStatusList();

watch(state.value.dataItems, (items) => {
  if (items) {
    items.forEach(item => {
      if (!item.editing) {
        item.editing = false;
      }
    });
  }
}, { deep: true });


onMounted(async () => {
  fetchStatusList(MODULE_SITE_VISIT);
  try {
    const response = await $api(`/table-header/get?slug=${state.value.tableHeaderSlug}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      state.value.headers = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
});

</script>
<template>
  <div v-if="$can('siteVisit', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-end">
          <div class="d-flex gap-2 ">
            <AppSelect v-model="state.itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />

            <VBtn v-if="$can('siteVisit', 'create')" icon="tabler-plus" @click="addSiteVisit" />

            <!-- <FilterHeaderTableBtn :slug="state.tableHeaderSlug"
              @filterHeaderValue="(headerList) => state.headers = headerList" /> -->
            <!-- <VBtn v-if="$can('siteVisit', 'view')" variant="tonal" @click="refresh()">
              <VIcon icon="tabler-refresh" />
            </VBtn> -->
          </div>
        </div>
      </VCardText>
      <VDivider class="mb-3" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="state.tableHeaderSlug"
          @filterHeaderValue="(headerList) => state.headers = headerList" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter">
        <AppTextField v-model="state.searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search By Visit Note" @input="refresh" v-if="showSearchFilter" class="ml-5" />
        <VSelect v-model="searchByType" class="ml-5" @update:modelValue="(value) => fetchSiteVisits()"
          label="Filter by Type" style="max-inline-size: 200px; min-inline-size: 200px;" :clearable="!!searchByType"
          :items="Types" v-if="showStatusFilter">
        </VSelect>
      </div>
      <VDivider v-if="showSearchFilter || showStatusFilter || showSyncHeader" class="mt-3" />

      <VDataTableServer v-model:items-per-page="state.itemsPerPage" v-model:page="state.page" :items="state.dataItems"
        item-value="name" :headers="filteredHeaders" :items-length="state.totalItems" show-select class="text-no-wrap"
        @update:options="(options) => {
          state.sortBy = options.sortBy[0]?.key;
          state.orderBy = options.sortBy[0]?.order;
          fetchSiteVisits();
        }">
        <template #[`item.created_by`]="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <template #[`item.last_updated_by`]="{ item }">
          {{ item.updater?.name || '-' }}
        </template>
        <template #[`item.assignee_name`]="{ item }">
          {{ item.assignee?.name || '-' }}
        </template>

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

        <template #[`item.visit_time`]="{ item }">
          {{ dayjs.utc(item.visit_time).format('ddd, MMM D, h:mm A') }}
        </template>
        <template #[`item.created_at`]="{ item }">
          {{ dayjs(item.created_at).format('ddd, MMM D, h:mm A') }}
        </template>
        <template #[`item.updated_at`]="{ item }">
          {{ dayjs(item.updated_at).format('ddd, MMM D, h:mm A') }}
        </template>
        <template #[`item.action`]="{ item }">
          <VBtn @click="generateChallan(item)" :disabled="challanLoading">
            Checklist
          </VBtn>
          <IconBtn v-if="$can('siteVisit', 'edit')" @click="editBranch(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="$can('siteVisit', 'delete')" @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
          <IconBtn v-if="$can('siteVisit', 'view')"
            :to="{ name: route.name == 'client-details-id' ? 'client-site-risk-management' : 'lead-site-risk-management', params: { id: item.id } }"
            color="warning" variant="tonal" v-tooltip="'Risk Management'">
            <VIcon icon="tabler-shield-check" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="state.page" :items-per-page="state.itemsPerPage"
            :total-items="state.totalItems" />
        </template>
      </VDataTableServer>
    </VCard>
    <ConfirmDialog v-model:isDialogVisible="state.isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete lead?" :currentItem="state.currentSiteVisit" @submit="refresh"
      :endpoint="`/site-visit/${state.currentSiteVisit?.id}`" @close="state.isDeleteDialogOpen = false" />
    <AddDrawer v-model:is-drawer-open="state.isAddEditDrawerOpen" :currentItem="state.currentSiteVisit"
      :type="props.type" @submit="refresh" @close="state.isAddEditDrawerOpen = false" />
  </div>
</template>
