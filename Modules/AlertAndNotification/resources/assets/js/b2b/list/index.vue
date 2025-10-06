<template>
  <section v-if="$can('b2b', 'view')">
    <VCard class="mb-6">
      <VCardText>
        <div class="d-flex justify-end">
          <div class="d-flex flex-row gap-2 align-center flex-wrap">
            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              :initial-show-showdateRangeOptions="showdateRangeOptions" @update:filters="updateFilters"
              :statusFilter="true" :searchFilter="true" :showdateRangeOptions="true" />

            <VMenu v-model="menuVisible" offset="12px" :close-on-content-click="false">
              <template #activator="{ props }">
                <VBtn v-bind="props" variant="outlined" color="primary" v-tooltip="'Actions'" size="small"
                  icon="tabler-files" />
              </template>

              <VCard class="ma-2" :style="{ width: '95vw', maxWidth: '500px', maxHeight: '90vh' }">
                <VCardTitle class="d-flex justify-space-between align-center flex-wrap">
                  <span class="text-h6">Import User</span>

                  <!-- Right side action buttons -->
                  <div class="d-flex align-center">
                    <VTooltip text="Download Dummy CSV" location="top">
                      <template #activator="{ props }">
                        <VIcon v-bind="props" @click="downloadDummyCsv" size="small" icon="tabler-download"
                          class="mr-2" />
                      </template>
                    </VTooltip>

                    <!-- Close Icon -->
                    <VIcon icon="tabler-x" @click="clearPropInfo(NO_CALL)" size="small" class="cursor-pointer" />
                  </div>
                </VCardTitle>

                <VDivider />

                <VCardText>
                  <VRow>
                    <VCol cols="12">
                      <VLabel>
                        Choose CSV File <span style="color: red;">*</span>
                      </VLabel>
                      <VFileInput placeholder="Choose CSV File" prepend-inner-icon="tabler-paperclip" prepend-icon=""
                        v-model="selectedFile" accept=".csv" show-size @change="handleFileChange" />
                    </VCol>
                  </VRow>
                </VCardText>

                <VDivider />

                <VCardActions>
                  <VSpacer />
                  <VBtn @click="menuVisible = false, selectedFile = null, menuLoader = false" variant="text">Cancel
                  </VBtn>
                  <VBtn :disabled="!selectedFile" variant="elevated" :loading="menuLoader" @click="importCsv"> Upload &
                    Import </VBtn>
                </VCardActions>
              </VCard>
            </VMenu>

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />

            <VBtn v-if="$can('reachout', 'send-message')" class="" variant="outlined" @click="reachoutSendMessage">Send
              Message</VBtn>

            <VBtn v-if="$can('b2b', 'create')" icon="tabler-plus" v-tooltip="'Add New'"
              @click="isCreateEditDrawer = !isCreateEditDrawer; currentInfo = null"> </VBtn>
            <!-- <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" /> -->
          </div>
        </div>
      </VCardText>
      <VDivider class="mb-3" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter || showdateRangeOptions">
        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search Name" v-if="showSearchFilter" class="ml-5" />

        <VSelect v-model="searchStatus" @update:modelValue="(value) => getList()" label="Select Status"
          :clearable="!!searchStatus" :items="statusList" style="max-inline-size: 150px; min-inline-size: 150px;"
          item-title="status_text" item-value="slug" v-if="showStatusFilter" class="ml-5" />

        <VSelect v-model="selectedDateTime" :items="dateRangeOptions" label="Last Upload User" item-title="name"
          item-value="name" clearable style="max-inline-size: 180px; min-inline-size: 180px;"
          v-if="showdateRangeOptions" class="ml-5" />
      </div>
      <VDivider v-if="showSearchFilter || showStatusFilter || showSyncHeader || showdateRangeOptions" class="mt-3" />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="pagination.per_page" :items="leadList" :items-length="leadList.length"
          :headers="headers.filter((header) => header.checked)" class="text-no-wrap" mobile-breakpoint="600"
          @update:options="updateTableSort" v-model="selectedIdList" show-select>
          <!-- Name -->
          <template #item.name="{ item }">
            <div class="d-flex align-center gap-x-4">
              <VAvatar size="34" :variant="!item.avatar ? 'tonal' : undefined"
                @click="getBigImagePreview(item.avatar, item.name)">
                <VImg v-if="item.avatar" :src="item?.avatar" />
                <span v-else>{{ item.name.charAt(0) }}</span>
              </VAvatar>
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  {{ item.name }}
                </h6>
              </div>
            </div>
          </template>
          <!-- Status -->
          <template #item.status="{ item }">
            <template v-if="editingStatusId === item.id && $can('b2b', 'edit')">
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

          <!-- created by -->
          <template #item.created_by="{ item }">
            {{ item.creator ? item.creator.name : '' }}
          </template>

          <!-- last updated by -->
          <template #item.last_updated_by="{ item }">
            {{ item.updater ? item.updater.name : '' }}
          </template>

          <!-- created at -->
          <template #item.created_at="{ item }">
            {{ $typeAccordingDateFormatChange(item.created_at, 'custom_2') }}
          </template>

          <!-- Actions -->
          <template #item.action="{ item }">
            <IconBtn v-if="$can('b2b', 'edit')" v-tooltip="'Edit'" @click="editBToBUser(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <VTooltip location="top">
              <template #activator="{ props }">
                <VIcon v-if="$can('reachout', 'send-message')" v-bind="props" icon="tabler-message" color="primary"
                  variant="elevated" :size="20" class="me-3" @click="openDialog(item)" />
              </template>
              <template #default>
                Send Message
              </template>
            </VTooltip>

            <!-- Delete Button -->
            <IconBtn v-if="$can('b2b', 'delete')" @click="openDeleteDialog(item)" v-tooltip="'Permanently Delete User'">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </template>

          <template #bottom>
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 px-6 py-3">
              <p class="text-disabled mb-0"> Showing {{ pagination.from }} to {{ pagination.to }} of {{
                pagination.total
                }} entries </p>
              <div class="d-flex flex-wrap gap-2 align-center">
                <AppSelect :model-value="pagination.per_page" :items="[50, 100, 200, 500]"
                  @update:model-value="val => { pagination.per_page = val; getList(); }"
                  style="inline-size: 6.25rem;" />

                <v-pagination v-model="pagination.current_page" :length="pagination.last_page" :total-visible="5" />
              </div>
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>

    <!-- 👉 Send Message Dialog -->
    <WhatsAppSendMessage v-if="isDialogVisible" :currentInfo="currentInfo" :selectedIdList="selectedIdList"
      @submit="clearSearchFilter" v-model:isDialogVisible="isDialogVisible" :type="'BToB-User'" />

    <!-- 👉Result Dialog -->
    <ResultDialog v-if="resultDialogVisible" :importResult="importResult" @clearImportResult="clearImportResult"
      v-model:resultDialogVisible="resultDialogVisible" />

    <!-- 👉 Create Edit Dialog -->
    <CreateEditDrawer v-if="isCreateEditDrawer" @clearPropInfo="clearPropInfo" :currentInfo="currentInfo"
      :statusList="statusList" v-model:isCreateEditDrawer="isCreateEditDrawer" />

    <!-- 👉 Delete Dialog -->
    <DeleteDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to Delete B2b User?" :currentItem="currentInfo" @submit="getList"
      :action="'force_delete'" :endpoint="`/b2b/delete/${currentInfo?.id}`" @close="isDeleteDialogOpen = false" />
  </section>
</template>

<script setup>
import { selectDateRange, useFetchStatusList } from "@/utils/common";
import moment from "moment";
import { ref } from 'vue';
import 'vue-json-pretty/lib/styles.css';
import { toast } from "vue3-toastify";
import { VCardTitle, VRow } from 'vuetify/components';
import DeleteDialog from "../../DeleteDialog.vue";
import WhatsAppSendMessage from "../../reachout/add/WhatsAppSendMessage.vue";
import CreateEditDrawer from "../dialog/CreateEditDrawer.vue";
import ResultDialog from "../dialog/ResultDialog.vue";

const tableHeaderSlug = ref('b-to-b-user-header-list');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const loading = ref(false);
const sortBy = ref();
const orderBy = ref();
const leadList = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 50, from: 0, to: 0 });
const searchQuery = ref('');
const searchStatus = ref('active');
const selectedIdList = ref([]);
const currentInfo = ref('');
const isDialogVisible = ref(false);
const isCreateEditDrawer = ref(false);
const isDeleteDialogOpen = ref(false);

const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)
const showdateRangeOptions = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
  showdateRangeOptions.value = filters.showdateRangeOptions
}

const menuVisible = ref(false);
const menuLoader = ref(false);
const selectedFile = ref(null);
const resultDialogVisible = ref(false);
const importResult = ref({
  failed: [],
  duplicates: [],
});

const selectedDateTime = ref(null)
const dateRangeOptions = selectDateRange().map((item) => ({
  name: item.text,
  value: item.value(),
}))

const reachoutSendMessage = (value) => {
  // if (selectedIdList.value.length <= 0) {
  //   toast.error("Please select at least one B2B user to send a message.");
  //   return;
  // }

  isDialogVisible.value = !isDialogVisible.value;
};

const clearPropInfo = (value) => {
  menuVisible.value = false;
  selectedFile.value = null;
  currentInfo.value = null;
  if (value != NO_CALL) getList();
};

// Fetch Quotation Status List
const { statusList, fetchStatusList } = useFetchStatusList();

const editingStatusId = ref(null);
const updateStatusValue = async (item) => {
  try {
    const res = await $api(`/b2b/status-update/${item.id}`, {
      method: 'POST',
      body: { status: item.status }
    });
    toast.success(res?.message || "User Status updated successfully");
  } catch (err) {
    toast.error(err?._data?.message || "Error updating User Status");
  } finally {
    editingStatusId.value = null;
  }
};

// Fetching the data from the API.
const getList = async () => {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value || '',
      page: pagination.value.current_page,
      sort_key: sortBy.value || '',
      sort_order: orderBy.value || '',
      per_page: pagination.value.per_page,
      status: searchStatus.value ?? ''
    };

    if (selectedDateTime.value) {
      const date = dateRangeOptions.find((item) => item.name === selectedDateTime.value);
      if (date) {
        params.start_date = moment(date.value[0]).format('YYYY-MM-DD HH:mm:ss');
        params.end_date = moment(date.value[1]).format('YYYY-MM-DD HH:mm:ss');
      }
    }
    const response = await $api('/b2b/list', { params });
    const { data, ...paginationData } = response.data;
    leadList.value = data ?? [];
    pagination.value = { ...paginationData };
  } catch (error) {
    console.error('Error fetching Lead list : ', error);
    toast.error(error?.response?.data?.message || 'Error fetching Lead list.');
  } finally {
    loading.value = false;
  }
}

// Update table sort options
const updateTableSort = (options) => {
  sortBy.value = options.sortBy[0]?.key || '';
  orderBy.value = options.sortBy[0]?.order || '';
};

// Watchers to handle pagination updates dynamically
watch([() => pagination.value.current_page, () => pagination.value.per_page, () => searchQuery.value, () => selectedDateTime.value],
  (newValues, oldValues) => {
    const hasChanged = newValues.some((val, index) => val !== oldValues[index]);
    if (hasChanged) {
      // selectedDateTime.value = null;
      getList();
    }
  }
);

const openDialog = (item) => {
  currentInfo.value = item;
  isDialogVisible.value = true;
}

const clearSearchFilter = (item) => {
  currentInfo.value = null;
  selectedIdList.value = [];
}

const downloadDummyCsv = () => {
  const headers = ['Name', 'Company', 'Email', 'Country Code', 'Contact No', 'Status', 'Role', 'Address'];
  const dummyData = [
    ['John Doe One', 'Tech Corp', 'john.doe@example.com', '91', '1234567890', ACTIVE, 'Manager', '123 Silicon Valley, CA'],
    ['John Doe Two', 'Tech Corp', 'john.doe2@example.com', '91', '1234567900', ACTIVE, 'Manager', '123 Silicon Valley, CA']
  ];

  const content = [
    headers.join(','), // header row
    ...dummyData.map(row => row.map(field => `"${field}"`).join(',')) // quoted values for safety
  ].join('\n');

  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'dummy_b2b_users.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const handleFileChange = (event) => {
  selectedFile.value = event?.target?.files?.[0] || event;
};

const importCsv = async () => {
  if (!selectedFile.value) return;
  menuLoader.value = true;
  const formData = new FormData();
  formData.append('file', selectedFile.value);

  try {
    const res = await $api('/b2b/user-import', { method: 'POST', body: formData, }, { headers: { 'Content-Type': 'multipart/form-data' } });
    const data = res?.data || {};
    const { not_created_list = [], not_duplicate_list = [] } = data;
    toast.success('CSV imported successfully!');
    menuVisible.value = false;
    clearPropInfo(NO_CALL);
    getList();
    if (not_created_list.length || not_duplicate_list.length) {
      importResult.value = {
        failed: not_created_list,
        duplicates: not_duplicate_list,
      };
      resultDialogVisible.value = true;
    }
  } catch (error) {
    toast.error(error?._data?.message || "An unexpected error occurred");
  } finally {
    menuLoader.value = false;
  }
};

const clearImportResult = () => {
  importResult.value = {
    failed: [],
    duplicates: [],
  };
}
const editBToBUser = (item) => {
  currentInfo.value = item;
  isCreateEditDrawer.value = true;
};

const openDeleteDialog = (item) => {
  currentInfo.value = item;
  isDeleteDialogOpen.value = true;
}

onMounted(async () => {
  await fetchStatusList(B_TO_B);
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
  await getList();
});
</script>
