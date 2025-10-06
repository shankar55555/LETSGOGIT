<template>
  <section v-if="$can('client', 'view')">
    <VCard class="mb-6">
      <VCardText>
        <div class="d-flex justify-end">
          <div class="d-flex gap-3">
            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

            <!-- Filter Header Btn FilterHeaderTableBtn -->
            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />

            <VBtn v-if="$can('reachout', 'send-message')" class="mr-3" @click="reachoutSendMessage">Send Message</VBtn>
          </div>
        </div>
      </VCardText>
      <VDivider class="mb-3" />
      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter">
        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search Name" v-if="showSearchFilter" class="ml-5" />
        <div class="d-flex flex-row gap-4 align-center flex-wrap">
          <VSelect label="Client Status" class="ml-5" v-model="selectedStatus" :items="statusList"
            style="max-inline-size: 280px; min-inline-size: 280px;" item-title="status_text" item-value="slug" multiple
            clearable @update:modelValue="onStatusChange" v-if="showStatusFilter" />
        </div>
      </div>

      <VDivider v-if="showSearchFilter || showStatusFilter || showSyncHeader" class="mt-3" />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="pagination.per_page" :items="clientList"
          :items-length="clientList.length" :headers="headers.filter((header) => header.checked)" class="text-no-wrap"
          mobile-breakpoint="600" @update:options="updateTableSort" v-model="selectedIdList" show-select>

          <!-- Actions -->
          <template #item.action="{ item }">
            <VTooltip location="top">
              <template #activator="{ props }">
                <VIcon v-if="$can('reachout', 'send-message')" v-bind="props" icon="tabler-message" color="primary"
                  variant="elevated" :size="20" class="me-3" @click="openDialog(item)" />
              </template>
              <template #default>
                Send Message
              </template>
            </VTooltip>
          </template>

          <template #bottom>
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 px-6 py-3">
              <p class="text-disabled mb-0">
                Showing {{ pagination.from }} to {{ pagination.to }} of
                {{ pagination.total }} entries
              </p>
              <div class="d-flex flex-wrap gap-2 align-center">
                <AppSelect :model-value="pagination.per_page" :items="[10, 25, 50, 100]" @update:model-value="
                  (val) => {
                    pagination.per_page = val;
                    getList();
                  }
                " style="inline-size: 6.25rem;" />

                <v-pagination v-model="pagination.current_page" :length="pagination.last_page" :total-visible="5" />
              </div>
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>

    <!-- 👉 Send Message Dialog -->
    <WhatsAppSendMessage v-if="isDialogVisible" :currentInfo="currentInfo" :selectedIdList="selectedIdList"
      @submit="clearSearchFilter" v-model:isDialogVisible="isDialogVisible" :type="'Client'" />
  </section>
</template>

<script setup>
import { useFetchStatusList } from "@/utils/common";
import { ref } from "vue";
import "vue-json-pretty/lib/styles.css";
import { toast } from "vue3-toastify";
import { VTooltip } from "vuetify/components";
import WhatsAppSendMessage from "../add/WhatsAppSendMessage.vue";


const tableHeaderSlug = ref("reachout-client-header-list");
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => {
  headers.value = headerList;
};

const loading = ref(true);
const sortBy = ref();
const orderBy = ref();
const clientList = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
  from: 0,
  to: 0,
});
const searchQuery = ref("");
const selectedIdList = ref([]);
const selectedStatus = ref([]);
const currentInfo = ref("");
const isDialogVisible = ref(false);

const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

// Fetching the data from the API.
const getList = async () => {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value || "",
      page: pagination.value.current_page,
      sort_key: sortBy.value || "",
      sort_order: orderBy.value || "",
      per_page: pagination.value.per_page,
      'status_list[]': selectedStatus.value ?? [],
    };
    const response = await $api("/clients", { params });
    clientList.value = response.data ?? [];
    pagination.value = {
      current_page: response.meta.current_page,
      last_page: response.meta.last_page,
      total: response.meta.total,
      per_page: response.meta.per_page,
      from: response.meta.from,
      to: response.meta.to,
    };
  } catch (error) {
    console.error("Error fetching Lead list : ", error);
    toast.error(error?.response?.data?.message || "Error fetching Lead list.");
  } finally {
    loading.value = false;
  }
};

const reachoutSendMessage = (value) => {
  // if (selectedIdList.value.length <= 0) {
  //   toast.error("Please select at least one B2B user to send a message.");
  //   return;
  // }

  isDialogVisible.value = !isDialogVisible.value;
};

const { statusList, fetchStatusList } = useFetchStatusList();

// Update table sort options
const updateTableSort = (options) => {
  sortBy.value = options.sortBy[0]?.key || "";
  orderBy.value = options.sortBy[0]?.order || "";
};

// Watchers to handle pagination updates dynamically
watch([() => pagination.value.current_page, () => pagination.value.per_page, () => searchQuery.value,],
  (newValues, oldValues) => {
    const hasChanged = newValues.some((val, index) => val !== oldValues[index]);
    if (hasChanged) {
      getList();
    }
  }
);

const openDialog = (item) => {
  currentInfo.value = item;
  isDialogVisible.value = true;
};
const onStatusChange = async (val) => {
  selectedStatus.value = val;
  await getList();
};

const clearSearchFilter = (item) => {
  currentInfo.value = null;
  selectedStatus.value = [];
  selectedIdList.value = [];
}

onMounted(async () => {
  await fetchStatusList(MODULE_CLIENT);
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
