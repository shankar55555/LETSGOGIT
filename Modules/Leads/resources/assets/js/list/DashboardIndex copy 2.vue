<template>
  <div v-if="$can('leads', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
            @input="fetchLeads" placeholder="Search Name" />
          <VSelect v-model="searchStatus" class="mr-2" @update:modelValue="(value) => fetchLeads()"
            label="Filter by status" style="max-inline-size: 200px; min-inline-size: 200px;" :clearable="!!searchStatus"
            :items="statusList" item-title="status_text" item-value="slug">
          </VSelect>

          <!-- Date Range  -->
          <div>
            <DateRangePicker @update:dateRange="handleDateRangeUpdate" />
            <p class="mt-4">
              Selected Range:
              <strong v-if="selectedDateRange.length">
                {{ selectedDateRange[0] }} to {{ selectedDateRange[1] }}
              </strong>
            </p>
          </div>

          <!-- Filter Header Btn FilterHeaderTableBtn -->
          <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />
          <VBtn v-if="$can('leads', 'view')" variant="tonal" @click="refresh()">
            <VIcon icon="tabler-refresh" />
          </VBtn>
        </div>
      </VCardText>

      <VDivider />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
          :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
          class="text-no-wrap" @update:options="updateOptions">

          <template #item.name="{ item }">
            <RouterLink v-if="$can('leads', 'show')" :to="{ name: 'lead-details-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              {{ item.name }}
            </RouterLink>
            <div v-else> {{ item.name }} </div>
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

          <!-- Status Info -->
          <template #item.status="{ item }">
            <!-- <template v-if="item.editing && $can('leads', 'edit')">
              <VSelect v-model="item.newStatus" :items="statusFilterPosition(statusList, item.status)"
                item-title="status_text" item-value="slug" dense hide-details label="Select Status"
                @blur="item.editing = false" @update:modelValue="() => openStatusConfirmDialog(item)" />
            </template> -->
            <!-- <template v-else>  @dblclick="() => startEditing(item)" -->
            <VChip :color="$resolveStatusVariant(item.status, statusList).color" size="small" class="cursor-pointer">
              {{ $resolveStatusVariant(item.status, statusList).text }}
            </VChip>
            <!-- </template> -->
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
          <template #bottom>
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { useFetchStatusList } from '@/utils/common';
import moment from 'moment';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';

const searchQuery = ref('');
const searchStatus = ref('');
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref();
const orderBy = ref();
const dataItems = ref([]);
const totalItems = ref(0);
const loading = ref(false);

const { statusList, fetchStatusList } = useFetchStatusList();
const headers = ref([]);
const tableHeaderSlug = ref('dashboard-lead-list');
const getFilteredHeaderValue = async (headerList) => {
  headers.value = headerList;
};

// Format utilities
const makeDateFormat = (date, onlyDate = false) => {
  return onlyDate ? moment(date).format('DD-MM-YYYY') : moment(date).format('LLLL');
};

const formatAnniversaryDate = (date) => {
  return date ? moment(date).format('DD-MMM-YYYY') : '-';
};

const selectedDateRange = ref([]);
const handleDateRangeUpdate = (range) => {
  selectedDateRange.value = range;
  console.log('Selected Date Range:', selectedDateRange.value);
  fetchLeads();
};

// ✅ API call
const fetchLeads = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      page: page.value,
      sort_key: sortBy.value || '',
      sort_order: orderBy.value || '',
      per_page: itemsPerPage.value,
      status: searchStatus.value || '',
    });

    if (selectedDateRange.value.length > 0) {
      params.set('start_date', moment(selectedDateRange.value[0]).format('YYYY-MM-DD'));
      params.set('end_date', moment(selectedDateRange.value[1]).format('YYYY-MM-DD') ?? moment(selectedDateRange.value[0]).format('YYYY-MM-DD'));
    } else {
      params.set('start_date', moment().format('YYYY-MM-DD'));
      params.set('end_date', moment().format('YYYY-MM-DD'));
    }

    const response = await $api(`/dashboard-lead-list?${params.toString()}`);
    dataItems.value = response.data || [];
    totalItems.value = response.meta?.total || 0;
  } catch (err) {
    toast.error(err?.response?.data?.message || err?._data?.message || 'Error fetching leads.');
  } finally {
    loading.value = false;
  }
};

const refresh = () => fetchLeads();

// Initial data fetch and listeners
watch([sortBy, orderBy, page, itemsPerPage], fetchLeads);

watch(dataItems, (items) => {
  items?.forEach(item => { item.editing = false });
}, { deep: true });

onMounted(async () => {
  await fetchStatusList(MODULE_LEAD);
  await fetchLeads();
});
</script>
