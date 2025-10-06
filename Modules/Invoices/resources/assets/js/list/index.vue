<script setup>
import { useFetchStatusList } from "@/utils/common";
import dayjs from "dayjs";
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';

const route = useRoute();
const props = defineProps({
  type: {
    type: [String, null],
    default: ''
  },
  id: {
    type: [String, Number],
    default: null
  }
})
const selectedIdList = ref([]);

const searchStatus = ref('');
const searchQuery = ref('')
const isDeleteDialogOpen = ref(false)
// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentInvoice = ref(null);
const tableHeaderSlug = ref('invoice-list');
const headers = ref([]);
const loading = ref(false);

const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const resolveStatusVariant = status => {

  const found = statusList.value.find(s => s.slug === status)
  // console.log('Status:', status, 'Found:', found, 'Status List:', statusList.value)
  if (found) {
    return {
      color: found.status_color || 'info',
      text: found.status_text || '—',
    }
  }

  return { color: 'info', text: status } // fallback
}

const { statusList, fetchStatusList } = useFetchStatusList();

onMounted(async () => {
  fetchStatusList(MODULE_INVOICE);
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  await fetchInvoices();
});

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchInvoices();
}
const dataItems = ref([]);
const totalItems = ref(0);
const fetchInvoices = async () => {
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

    // Append based on props.type
    if (props.type === QUOTATION_LEAD) {
      params.append('lead_id', props.id);
    } else if (props.type === QUOTATION_CLIENT) {
      params.append('client_id', props.id);
    } else if (props.type === 'quotations') {
      params.append('quotation_id', props.id);
    }

    if (route.name === USER_VIEW_ID) {
      params.append('user_view_id', route.params.id);
    }

    const response = await $api(`/invoices?${params.toString()}`);
    dataItems.value = response.data || [];
    totalItems.value = response.meta?.total || 0;
  } catch (err) {
    console.error('Failed to fetch Invoices:', err);
    toast.error('Failed to load Invoices');
  } finally {
    loading.value = false;
  }
};

const editingStatusId = ref(null);
const updateStatusValue = async (item) => {
  try {
    const res = await $api(`/invoice/status-update`, {
      method: 'POST',
      body: { invoice_id: item.id, status: item.status }
    });
    toast.success(res?.message || "Invoice Status updated successfully");
  } catch (err) {
    toast.error(err?._data?.message || "Error updating Invoice Status");
  } finally {
    editingStatusId.value = null;
  }
};

const currentInfo = ref(null);
const isSendMessageDialogVisible = ref(false);
const openDialog = (item) => {
  currentInfo.value = item;
  isSendMessageDialogVisible.value = true;
}

const clearSendMessageSearchFilter = (item) => {
  if (currentInfo.value.status == 'created') {
    fetchInvoices();
  }
  currentInfo.value = null;
  selectedIdList.value = [];
  isSendMessageDialogVisible.value = false;
}

const refresh = () => {
  fetchInvoices();
}

</script>
<template>
  <div v-if="$can('invoice', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <div>
            <h4 class="text-h4 text-center">Invoices</h4>
          </div>
          <div class="d-flex gap-2">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />

            <VBtn v-if="$can('invoice', 'create') && type != 'Not_Show' && !props.id" :to="{
              name: 'invoice-create', query: props.type && props.id ? {
                type: props.type,
                id: props.id
              } : {}
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
      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter">
        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search Title" @input="fetchInvoices" v-if="showSearchFilter" class="ml-5" />

        <VSelect v-model="searchStatus" class="mr-2 ml-5" @update:modelValue="(value) => fetchInvoices()"
          label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;" clearable :items="statusList"
          item-title="status_text" item-value="slug" v-if="showStatusFilter">
        </VSelect>
      </div>

      <VDivider class="mt-4" v-if="showSearchFilter || showStatusFilter || showSyncHeader" />

      <BaseSpinner class="d-flex" v-if="loading" />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
        :headers="headers.filter((header) => header.checked)" :items-length="totalItems" class="text-no-wrap"
        @update:options="updateOptions" v-model="selectedIdList" show-select>

        <!-- Generated Name -->
        <template #item.generated_name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.client?.name || item.quotation.client_detail?.name || item.quotation.lead_detail?.name }}
              </h6>
              <div class="text-sm">
                {{ item.client?.phone || item.quotation.client_detail?.phone || item.quotation.lead_detail?.phone }}
              </div>
            </div>
          </div>
        </template>

        <!-- <template v-slot:item="{ item, columns }">
          <tr>
            <td v-for="column in columns" :key="column.key"> -->
        <template #item.invoice_number="{ item }">
          <RouterLink :to="{ name: 'invoice-details-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
            #{{ item.invoice_number }}
          </RouterLink>
        </template>

        <template #item.sub_total="{ item }">
          {{ item.sub_total || 0 }}
        </template>
        <template #item.discount="{ item }">
          {{ item.discount || 0 }}
        </template>
        <template #item.tax="{ item }">
          {{ item.tax || 0 }}
        </template>
        <template #item.total="{ item }">
          {{ item.total || 0 }}
        </template>

        <template #item.created_by="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <template #item.last_updated_by="{ item }">
          {{ item.updater?.name || '—' }}
        </template>
        <template #item.created_at="{ item }">
          {{ $typeAccordingDateFormatChange(date, 'full_date_1') }}
        </template>
        <template #item.due_date="{ item }">
          {{ dayjs(item.due_date).format('DD-MM-YYYY') }}
        </template>

        <!-- <template #item.status="{ item }">
          <VChip :color="resolveStatusVariant(item.status).color" size="small">
            {{ resolveStatusVariant(item.status).text }}
          </VChip>
        </template> -->

        <template #item.status="{ item }">
          <template v-if="false && editingStatusId === item.id && $can('invoice', 'edit')">
            <VSelect v-model="item.status" :items="statusList" item-title="status_text" item-value="slug" dense
              hide-details label="Select Status" @blur="editingStatusId = null" @change="editingStatusId = null"
              @update:modelValue="() => updateStatusValue(item)" />
          </template>
          <template v-else>
            <VChip @dblclick="editingStatusId = item.id" :color="$resolveStatusVariant(item.status, statusList).color"
              size="small" class="cursor-pointer">
              {{ $resolveStatusVariant(item.status, statusList).text }}
            </VChip>
          </template>
        </template>

        <template #item.quotation_number="{ item }">
          <RouterLink :to="{ name: 'quotation-details-id', params: { id: item.quotation_number?.id } }"
            class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;"
            v-if="item.quotation_number?.id">
            #{{ item.quotation_number?.quotation_number ?? '-' }}
          </RouterLink>
          <span v-else>-</span>
        </template>

        <template #item.contract_number="{ item }">
          {{ item.contract_number?.contract_number ?? '-' }}
        </template>

        <template #item.action="{ item }">
          <div class="d-flex align-center gap-x-2 justify-end">
            <!-- Send Message -->
            <VIcon v-if="$can('invoice', 'send-message') && item.status != 'draft'" icon="tabler-message"
              color="primary" variant="elevated" :size="20" class="me-3" v-tooltip="'Send Message'"
              @click="openDialog(item)" />

            <!-- Edit Invoice -->
            <IconBtn v-if="$can('invoice', 'edit') && item.status !== 'paid' && !item.quotation_id"
              :to="{ name: 'invoice-edit', params: { id: item.id } }" v-tooltip="'Edit'">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <!-- View Invoice -->
            <IconBtn v-if="$can('invoice', 'show')" :to="{ name: 'invoice-details-id', params: { id: item.id } }"
              v-tooltip="'View'">
              <VIcon icon="tabler-eye" />
            </IconBtn>

            <!-- Uncomment when delete is enabled
    <IconBtn
      v-if="$can('invoice', 'delete') && item.status !== 'Paid'"
      v-tooltip="'Delete'"
      @click="openDeleteDialog(item)"
    >
      <VIcon icon="tabler-trash" />
    </IconBtn>
    -->
          </div>
        </template>

        <template v-slot:bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>

      <p style="font-size: 12px;" class="ml-5 text-caption">All amounts are in Rs.</p>
    </VCard>
    <!-- 👉 Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete invoice?" :currentItem="currentInvoice" @submit="refresh"
      :endpoint="`/invoices/${currentInvoice?.id}`" @close="isDeleteDialogOpen = false" />
    <!-- 👉 Send Message Dialog -->
    <WhatsAppAndEmailSendMessage v-if="isSendMessageDialogVisible" :currentInfo="currentInfo"
      :selectedIdList="selectedIdList" @submit="clearSendMessageSearchFilter"
      v-model:isSendMessageDialogVisible="isSendMessageDialogVisible" :type="MODULE_INVOICE" />
  </div>
</template>
