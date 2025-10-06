<template>
  <div v-if="$can('quotation', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <div>
            <h4 class="text-h4 text-center">Quotations</h4>
          </div>
          <div class="d-flex gap-3">
            <!-- <VBtn v-if="$can('quotation', 'export-list')" prepend-icon="tabler-upload" variant="tonal"
              color="secondary">
              Export
            </VBtn> -->
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />

            <VBtn v-if="$can('quotation', 'create') && type !== 'Not_Show'" :to="{
              name: 'quotation-create',
              query: props.type && props.id ? { type: props.type, id: props.id } : {},
            }" icon="tabler-plus" size="small">
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider class="mb-4" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter || showDateFilter">
        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search Title" @input="fetchQuotations" v-if="showSearchFilter" class="ml-5" />
        <VSelect v-model="searchStatus" class="mr-2" @update:modelValue="(value) => fetchQuotations()"
          label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;" :clearable="!!searchStatus"
          :items="statusList" item-title="status_text" item-value="slug" v-if="showStatusFilter">
        </VSelect>
      </div>
      <VDivider class="mt-4" v-if="showSearchFilter || showStatusFilter || showSyncHeader" />
      <BaseSpinner class="d-flex" v-if="loading" />

      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems"
          :headers="filteredHeaders" :items-length="totalItems" item-value="name" show-select class="text-no-wrap"
          @update:options="updateOptions">

          <!-- Generated Name -->
          <template #item.generated_name="{ item }">
            <div class="d-flex align-center gap-x-4">
              <div class="d-flex flex-column">

                <RouterLink v-if="$can('quotation', 'show')"
                  :to="{ name: item.client_id ? 'client-details-id' : 'lead-details-id', params: { id: item.client_id ?? item.lead_id } }"
                  class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                  <h6 class="text-base">
                    {{ item.client_detail?.name ?? item.lead_detail?.name }}
                  </h6>
                  <div class="text-sm">
                    {{ item.client_detail?.phone ?? item.lead_detail?.phone }}
                  </div>
                </RouterLink>
                <span v-else class="font-weight-medium">
                  <h6 class="text-base">
                    {{ item.client_detail?.name ?? item.lead_detail?.name }}
                  </h6>
                  <div class="text-sm">
                    {{ item.client_detail?.phone ?? item.lead_detail?.phone }}
                  </div>
                </span>

              </div>
            </div>
          </template>

          <!-- Quotation Number -->
          <template #item.quotation_number="slotProps">
            <RouterLink :to="{ name: 'quotation-details-id', params: { id: slotProps.item.id } }"
              class="text-primary font-weight-medium d-inline-block " style="line-height: 1.375rem;">
              #{{ slotProps.item.quotation_number }}
            </RouterLink>
          </template>

          <!-- Status Info -->
          <template #item.status="{ item }">
            <template v-if="item.editing && $can('quotation', 'edit')">
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

          <!-- Columns -->
          <template #item.valid_uptil="{ item }">{{ $typeAccordingDateFormatChange(item.valid_uptil, 'd-m-y')
          }}</template>
          <template #item.quotation_type="{ item }">{{ item.quotation_type }}</template>
          <template #item.title="{ item }">
            <v-tooltip location="top">
              <template #activator="{ props }">
                <span v-bind="props">
                  {{ item.title.length > 20 ? item.title.slice(0, 20) + '...' : item.title }}
                </span>
              </template>
              <span>{{ item.title }}</span>
            </v-tooltip>
          </template>

          <template #item.sub_total="{ item }">{{ item.sub_total || 0 }}</template>
          <template #item.discount="{ item }">{{ item.discount || 0 }}</template>
          <template #item.tax="{ item }">{{ item.tax || 0 }}</template>
          <template #item.total="{ item }">{{ item.total || 0 }}</template>
          <template #item.amount_due="{ item }">
            <span :class="{ 'text-error': item.amount_due != 0.00 }">
              {{ item.amount_due || 0 }}
            </span>
          </template>
          <template #item.created_by="{ item }">{{ item.creator?.name || '—' }}</template>
          <template #item.last_updated_by="{ item }">{{ item.updater?.name || '—' }}</template>
          <template #item.created_at="{ item }">{{ $typeAccordingDateFormatChange(item.created_at, 'd-m-y')
          }}</template>
          <template #item.updated_at="{ item }">{{ item.updater ? $typeAccordingDateFormatChange(item.updated_at,
            'd-m-y') : '—' }}</template>

          <!-- Actions -->
          <template #item.action="{ item }">
            <VIcon v-if="$can('quotation', 'send-message') && !QUOTATION_NOT_IN.includes(item.status)" v-bind="props"
              icon="tabler-message" color="primary" variant="elevated" :size="20" class="me-3" @click="openDialog(item)"
              v-tooltip="'Send Message'" />

            <VIcon v-bind="props" icon="tabler-file-invoice" color="primary" variant="elevated" :size="20" class="me-3"
              @click="openGstChallanDialog(item)" v-tooltip="'GST Challan'" />

            <IconBtn v-if="$can('quotation', 'show')" :to="{ name: 'quotation-details-id', params: { id: item.id } }"
              v-tooltip="'view'">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <IconBtn v-if="$can('quotation', 'edit')" :to="{ name: 'quotation-edit', params: { id: item.id } }"
              v-tooltip="'Edit'">
              <VIcon icon="tabler-pencil" />
            </IconBtn>
            <IconBtn v-if="$can('quotation', 'delete')" @click="openDeleteDialog(item)" v-tooltip="'Delete'">
              <VIcon icon="tabler-trash" />
            </IconBtn>

          </template>
          <!-- Table Footer -->
          <template #bottom>
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          </template>

        </VDataTableServer>

        <p class="ml-5 text-caption" style="font-size: 12px;">All amounts are in Rs.</p>
      </VCardText>
    </VCard>

    <!-- Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete quotation?" :currentItem="currentQuotation"
      :endpoint="`/quotations/${currentQuotation?.id}`" @submit="fetchQuotations" @close="isDeleteDialogOpen = false" />

    <!-- 👉 Send Message Dialog -->
    <WhatsAppAndEmailSendMessage v-if="isSendMessageDialogVisible" :currentInfo="currentInfo"
      :selectedIdList="selectedIdList" @submit="clearSendMessageSearchFilter"
      v-model:isSendMessageDialogVisible="isSendMessageDialogVisible" :type="MODULE_QUOTATION" />

    <!-- GST Challan Dialog -->
    <v-dialog v-model="isGstChallanDialogOpen" max-width="900">
      <GstChallan v-if="isGstChallanDialogOpen" :quotationId="currentQuotation?.id" :currentInfo="currentInfo"
        @challan-generated="onChallanGenerated" @close="isGstChallanDialogOpen = false" />
    </v-dialog>

    <!-- 👉 Status Confirm Dialog -->
    <StatusConfirmDialog v-model:isStatusConfirmVisible="isStatusConfirmVisible" :currentItem="confirmStatus"
      :loader="statusLoader" :statusList="statusList" @updateStatusValue="updateStatusValue"
      @close="statusConfirmClear" />

  </div>
</template>

<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import GstChallan from '../components/GstChallan.vue';
import ConfirmDialog from '../dialog/ConfirmDialog.vue';

const route = useRoute();

const props = defineProps({
  type: { type: [String, null], default: '' },
  id: { type: [String, Number], default: null }
});

// State  
const searchStatus = ref('');
const searchQuery = ref('');
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref();
const orderBy = ref();
const loading = ref(false);
const headers = ref([]);
const showSyncHeader = ref(false);
const dataItems = ref([]);
const totalItems = ref(0);
const isDeleteDialogOpen = ref(false);
const currentQuotation = ref(null);
const tableHeaderSlug = ref(route.name === "quotation-list" ? "quotation-page-list" : "quotation-list");
const filteredHeaders = computed(() => headers.value.filter(h => h.checked));

const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

// Fetch Quotation Status List
const { statusList, fetchStatusList } = useFetchStatusList();

// Resolve Status for Chip
const resolveStatusVariant = status => {
  const found = statusList.value.find(s => s.slug === status);
  return {
    color: found?.status_color || 'info',
    text: found?.status_text || status,
  };
};

const filterStatusList = (status) => {
  let allowedStatuses = [];
  if (status === QUOTATION_DRAFT) {
    allowedStatuses = [QUOTATION_DRAFT, QUOTATION_REJECTED, QUOTATION_EXPIRED];
  } else if (status === QUOTATION_CREATED) {
    allowedStatuses = [QUOTATION_CREATED, QUOTATION_SENT, QUOTATION_ACCEPTED, QUOTATION_REJECTED, QUOTATION_EXPIRED];
  } else if (status === QUOTATION_SENT) {
    allowedStatuses = [QUOTATION_SENT, QUOTATION_ACCEPTED, QUOTATION_REJECTED, QUOTATION_EXPIRED];
  } else if (status === QUOTATION_ACCEPTED) {
    allowedStatuses = [QUOTATION_ACCEPTED, QUOTATION_REJECTED, QUOTATION_EXPIRED];
  } else if (status === QUOTATION_REJECTED) {
    allowedStatuses = [QUOTATION_REJECTED, QUOTATION_EXPIRED];
  } else if (status === QUOTATION_EXPIRED) {
    allowedStatuses = [QUOTATION_EXPIRED];
  }

  return statusList.value.filter(s => allowedStatuses.includes(s.slug));
};

const handleStatusDoubleClick = (item) => {
  if (item.status === QUOTATION_DRAFT) {
    toast.warning(`Please add a quotation to the product before editing the status.`);
  }

  if (item.status === QUOTATION_CREATED) {
    toast.warning(`Please send the quotation PDF to the user. The status will update automatically.`);
  }

  item.editing = true;
};

watch(dataItems, items => {
  items?.forEach(item => { item.editing = item.editing || false });
}, { deep: true });

// Fetch Quotations
const fetchQuotations = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      page: page.value,
      sort_key: sortBy.value,
      sort_order: orderBy.value,
      per_page: itemsPerPage.value,
      status: searchStatus.value ?? "",
    });

    if (route.name == USER_VIEW_ID) params.append('user_view_id', route.params.id);
    if (props.type === QUOTATION_LEAD) params.append('lead_id', props.id);
    if (props.type === QUOTATION_CLIENT) params.append('client_id', props.id);

    const response = await $api(`/quotations?${params.toString()}`);
    dataItems.value = response.data;
    totalItems.value = response.meta.total;
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to load Quotations');
  } finally {
    loading.value = false;
  }
};

// Open Delete Dialog
const openDeleteDialog = item => {
  currentQuotation.value = { ...item };
  isDeleteDialogOpen.value = true;
};

// Update Quotation Status
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
    const response = await $api(`/update-direct-quotation-status`, { method: 'POST', body: JSON.stringify({ id: item.id, status: confirmStatus.value.newStatus }), });
    toast.success(response.message);
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

// Table Header Filter
const getFilteredHeaderValue = headerList => {
  headers.value = headerList;
};

// Table Options Update
const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key;
  orderBy.value = options.sortBy[0]?.order;
};

const currentInfo = ref(null);
const selectedIdList = ref([]);
const isSendMessageDialogVisible = ref(false);
const openDialog = (item) => {
  currentInfo.value = item;
  isSendMessageDialogVisible.value = true;
}

const clearSendMessageSearchFilter = (item) => {
  currentInfo.value = null;
  selectedIdList.value = [];
  isSendMessageDialogVisible.value = false;
}

watch([sortBy, orderBy], fetchQuotations);

// Add watchers for pagination changes
watch([page, itemsPerPage], () => {
  fetchQuotations();
});

onMounted(async () => {
  fetchStatusList(MODULE_QUOTATION);
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  await fetchQuotations();
});

const isGstChallanDialogOpen = ref(false);
const selectedQuotation = ref(null);
const openGstChallanDialog = (item) => {
  console.log('Opening GST Challan Dialog with item:', item);
  selectedQuotation.value = item;
  currentQuotation.value = item;
  currentInfo.value = item;
  isGstChallanDialogOpen.value = true;
};
const onChallanGenerated = (challan) => {
  isGstChallanDialogOpen.value = false;
  // Optionally, show a toast or refresh data
  toast.success('GST Challan generated!');
};
</script>
