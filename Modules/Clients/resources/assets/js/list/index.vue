<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import dayjs from "dayjs";
import advancedFormat from "dayjs/plugin/advancedFormat";
import moment from 'moment';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import Actions from '../../../../../../resources/js/components/Actions.vue';
import Filters from '../../../../../../resources/js/components/Filters.vue';

import AddDrawer from '../add/AddDrawer.vue';
import ConfirmDialog from '../dialog/ConfirmDialog.vue';

dayjs.extend(advancedFormat);
const route = useRoute()

const props = defineProps({
  type: { type: String, default: null },
})
const searchStatus = ref('');
const showSearchFilter = ref(false)
const searchQuery = ref('')
const isDeleteDialogOpen = ref(false)
const openClientModal = ref(false)
const currentClient = ref(null)
const showStatusFilter = ref(false)

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const loading = ref(false)
const tableHeaderSlug = ref('client-list')
const headers = ref([])
const showSyncHeader = ref(false)
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList }

// Client data
const dataItems = ref([])
const totalItems = ref(0)
const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchClients()
}
const fetchClients = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      search: searchQuery.value ?? '',
      page: page.value,
      sort_key: sortBy.value ?? '',
      sort_order: orderBy.value ?? '',
      per_page: itemsPerPage.value,
      status: searchStatus.value ?? '',
    });

    if (route.name === USER_VIEW_ID) params.append('user_view_id', route.params.id);
    const response = await $api(`/clients?${params.toString()}`);
    dataItems.value = response.data || [];
    totalItems.value = response.meta?.total || 0;
  } catch (err) {
    console.error('Failed to fetch Clients:', err);
    toast.error('Failed to load Clients');
  } finally {
    loading.value = false;
  }
};
const openDeleteDialog = (item) => {
  currentClient.value = JSON.parse(JSON.stringify(item))
  isDeleteDialogOpen.value = true
}
const editClient = (item) => {
  currentClient.value = JSON.parse(JSON.stringify(item))
  openClientModal.value = true
}
const refresh = async () => {
  await fetchClients()
  isDeleteDialogOpen.value = false
}
const makeDateFormat = (date, onlyDate = false) => {
  if (onlyDate)
    return moment(date).format('DD-MM-Y')
  else
    return moment(date).format('LLLL')
}

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

// Open confirmation dialog only if new status is different
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
    await $api(`/clients/${item.id}/status`, { method: 'PUT', body: { status: confirmStatus.value.newStatus } })
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

const formatAnniversaryDate = (date) => {
  if (!date) return '';
  return moment(date).format('DD-MMM-YYYY');
};

const editableStatus = ref({})
const { statusList, fetchStatusList } = useFetchStatusList();

function removeToolChecklistTag(index) {
  lead.value.secondary_phone.splice(index, 1)
}

onMounted(async () => {
  fetchStatusList(MODULE_CLIENT);
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  await fetchClients();
})
</script>
<template>
  <div v-if="$can('client', 'view')">
    <VCard>
      <VCardText class="d-flex justify-space-between flex-wrap" title="Client">
        <h4 class="text-h4 text-center">Clients</h4>

        <div class="d-flex flex-row gap-3">
          <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
            :statusFilter="true" :searchFilter="true" @update:filters="updateFilters" />

          <Actions v-if="$can('client', 'export-list')" @export-leads="exportLeads" @import-file="handleFileImport"
            @download-sample="downloadSampleExcel" />

          <VBtn icon="tabler-table-options" size="small" variant="outlined" @click="showSyncHeader = !showSyncHeader" />

          <VBtn v-if="$can('client', 'create') && type != 'Not_Show'" icon="tabler-plus"
            @click="openClientModal = true; currentClient = null" size="small">
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VCardText v-if="showSyncHeader" v-tooltip="'Filters'">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" title="Actions" />
      </VCardText>

      <VCardText v-if="showSearchFilter || showStatusFilter" class="d-flex gap-3">
        <VSelect v-model="searchStatus" class="mr-2" @update:modelValue="(value) => fetchClients()"
          label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;" :clearable="!!searchStatus"
          :items="statusList" item-title="status_text" item-value="slug" v-if="showStatusFilter">
        </VSelect>

        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search Name" @input="fetchClients" v-if="showSearchFilter" />
      </VCardText>

      <VDivider v-if="showSearchFilter || showStatusFilter || showSyncHeader" />

      <!-- <VProgressLinear v-if="loading" indeterminate color="primary"></VProgressLinear> -->
      <BaseSpinner class="d-flex" v-if="loading" />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
        :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
        class="text-no-wrap" @update:options="updateOptions">
        <!-- Name Column -->
        <template #item.name="{ item }">
          <RouterLink v-if="$can('client', 'show')" :to="{ name: 'client-details-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
            {{ item.name }}
          </RouterLink>
          <span v-else class="font-weight-medium">
            {{ item.name }}
          </span>
        </template>
        <!-- Phone Column -->
        <template #item.phone="{ item }">
          {{ item.phone ? item.phone.substring(0, 5) + "-" + item.phone.substring(5) : "—" }}
        </template>

        <template #item.secondary_phone="{ item }">
          {{ Array.isArray(item.secondary_phone) ? item.secondary_phone.join(', ') : (item.secondary_phone ?? '-') }}
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

        <!-- Status Info -->
        <template #item.status="{ item }">
          <template v-if="item.editing && $can('client', 'edit')">
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

        <!-- assigned_user -->
        <template #item.assigned_user="{ item }">
          {{ item.assigned_user?.name || '—' }}
        </template>
        <!-- creator -->
        <template #item.created_by="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <!-- updater -->
        <template #item.last_updated_by="{ item }">
          {{ item.updater?.name || '-' }}
        </template>
        <!-- Created At Column -->
        <template #item.created_at="{ item }">
          {{ dayjs(item.created_at).format('ddd, MMM D, h:mm A') }}
        </template>
        <!-- updated_at -->
        <template #item.updated_at="{ item }">
          {{ item.updater ? dayjs(item.updated_at).format('ddd, MMM D, h:mm A') : '-' }}
        </template>
        <!-- Actions Column -->
        <template #item.action="{ item }">
          <IconBtn @click="editClient(item)" v-if="$can('client', 'edit')">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <RouterLink v-if="$can('client', 'show')" :to="{ name: 'client-details-id', params: { id: item.id } }">
            <VIcon color="secondary" icon="tabler-eye" />
          </RouterLink>
          <IconBtn v-if="$can('client', 'delete')" @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          <!-- <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" @update:modelValue="fetchClients" /> -->
        </template>
      </VDataTableServer>
    </VCard>
    <!-- Confirm Delete Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete this client?" :currentItem="currentClient" @submit="refresh"
      :endpoint="`/clients/${currentClient?.id}`" @close="isDeleteDialogOpen = false" />
    <!-- Add/Edit Client Drawer -->
    <AddDrawer v-model:isDrawerOpen="openClientModal" :currentClient="currentClient" @submit="refresh"
      v-if="openClientModal" :clients="dataItems" />

    <!-- 👉 Status Confirm Dialog -->
    <StatusConfirmDialog v-model:isStatusConfirmVisible="isStatusConfirmVisible" :currentItem="confirmStatus"
      :loader="statusLoader" :statusList="statusList" @updateStatusValue="updateStatusValue"
      @close="statusConfirmClear" />
  </div>
</template>
<style scoped>
.text-link {
  color: rgba(var(--v-theme-primary), var(--v-high-emphasis-opacity));
  text-decoration: underline;
}
</style>
