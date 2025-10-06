<template>
  <section v-if="$can('rule', 'view')">
    <VCard class="mb-6">
      <div class="d-flex justify-lg-space-between align-center" style="padding: 30px;">
        <h4 class="text-h4 text-center">Bell Notification</h4>

        <div class="d-flex gap-3">
          <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
            @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

          <!-- Filter Header Btn FilterHeaderTableBtn -->
          <VBtn icon="tabler-table-options" size="small" variant="outlined" @click="showSyncHeader = !showSyncHeader" />

          <VBtn v-if="$can('rule', 'create')" icon="tabler-plus"
            @click="isDialogVisible = !isDialogVisible; currentInfo = null;" size="small">
          </VBtn>
        </div>
      </div>

      <VDivider class="mb-3" />

      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <div class="d-flex gap-2 justify-start" v-if="showSearchFilter || showStatusFilter">
        <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          placeholder="Search " />

        <VBtn v-if="$can('rule', 'create')" icon="tabler-plus"
          @click="isDialogVisible = !isDialogVisible; currentInfo = null;" si>
        </VBtn>
      </div>
      <VDivider class="mt-3" v-if="showSearchFilter || showStatusFilter || showSyncHeader" />

      <BaseSpinner class="d-flex" v-if="loading" />
      <VDataTableServer v-else v-model:items-per-page="pagination.per_page" :items="ruleData"
        :items-length="ruleData.length" :headers="headers.filter((header) => header.checked)" class="text-no-wrap"
        mobile-breakpoint="600" @update:options="updateTableSort">

        <template #item.conditions="{ item }">
          <IconBtn @click="viewDetails(item, 'conditions')">
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="viewDetails(item, 'actions')">
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </template>

        <template #item.created_by="{ item }">
          {{ item.creator ? item.creator.name : "" }}
        </template>

        <template #item.last_updated_by="{ item }">
          {{ item.updater ? item.updater.name : "" }}
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <div v-if="editingStatusId === item.id && $can('rule', 'edit')">
            <VSelect v-model="item.status" @update:modelValue="(value) => updateStatus(item)"
              @blur="editingStatusId = null" :items="statusList" item-title="status_text" item-value="slug">
            </VSelect>
          </div>
          <div v-else @dblclick="editingStatusId = item.id">
            <!-- <span :class="item.status == 'active' ? 'text-success' : 'text-error'" style="cursor: pointer;">
              {{ item.status }}
            </span> -->

            <VChip :color="$resolveStatusVariant(item.status, statusList).color" size="small" class="cursor-pointer">
              {{ $resolveStatusVariant(item.status, statusList).text }}
            </VChip>
          </div>
        </template>

        <!-- Actions -->
        <template #item.action="{ item }">
          <IconBtn v-if="$can('rule', 'edit')" @click="editBranch(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="$can('rule', 'delete')" @click="openDeleteDialog(item)" v-tooltip="'Delete Status'">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>

        <!-- pagination -->
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
                  pageRuleList();
                }
              " style="inline-size: 6.25rem;" />
              <v-pagination v-model="pagination.current_page" :length="pagination.last_page" :total-visible="5" />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <VDialog v-model="showDetailDialog" max-width="1000px" scrollable>
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Rule Details</span>
          <IconBtn @click="showDetailDialog = false">
            <VIcon icon="tabler-x" />
          </IconBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <div v-if="selectedRule?.conditions && contentType == 'conditions'">
            <h4 class="text-h6 mb-2">Conditions:</h4>
            <vue-json-pretty :data="selectedRule.conditions" />
          </div>

          <div v-else-if="selectedRule?.actions && contentType == 'actions'">
            <h4 class="text-h6 mt-4 mb-2">Actions:</h4>
            <vue-json-pretty :data="selectedRule.actions" />
          </div>

          <div v-else>
            <h4 class="text-h6 mt-4 mb-2">No Data found:</h4>
          </div>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn color="primary" @click="showDetailDialog = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <AddEditDrawer v-if="isDialogVisible" @submit="pageRuleList" :currentInfo="currentInfo"
      v-model:is-drawer-open="isDialogVisible" />

    <!-- 👉 Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete rule?" :currentItem="currentInfo" @submit="pageRuleList"
      :endpoint="`/rules/${currentInfo?.id}`" @close="isDeleteDialogOpen = false" />
  </section>
</template>

<script setup>
import { useFetchStatusList } from "@/utils/common";
import "@vuepic/vue-datepicker/dist/main.css";
import { onMounted, ref } from "vue";

import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import { toast } from "vue3-toastify";
import AddEditDrawer from "../add/AddEditDrawer.vue";
import ConfirmDialog from "../dialog/ConfirmDialog.vue";

// Data table Headers
const tableHeaderSlug = ref("header-rule-list");
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => {
  headers.value = headerList;
};
const { statusList, fetchStatusList } = useFetchStatusList();

const showDetailDialog = ref(false);
const selectedRule = ref(null);

const loading = ref(true);
const searchQuery = ref("");
const ruleData = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
  from: 0,
  to: 0,
});
const sortBy = ref();
const orderBy = ref();
const isDialogVisible = ref(false);
const isDeleteDialogOpen = ref(false);
const currentInfo = ref(null);

const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

const statusOptions = ref([
  { text: "Active", value: "active" },
  { text: "In-Active", value: "in-active" },
  { text: "Draft", value: "draft" },
]);

const editingStatusId = ref(null);
const editingColorId = ref(null);

// Update table sort options
const updateTableSort = (options) => {
  sortBy.value = options.sortBy[0]?.key || "";
  orderBy.value = options.sortBy[0]?.order || "";
};

const editBranch = (item) => {
  currentInfo.value = JSON.parse(JSON.stringify(item));
  isDialogVisible.value = true;
};
const contentType = ref(null);
const viewDetails = (item, type) => {
  selectedRule.value = { ...item };
  if (item.actions)
    selectedRule.value.actions = JSON.parse(item.actions);
  if (item.conditions)
    selectedRule.value.conditions = JSON.parse(item.conditions);
  showDetailDialog.value = true;
  contentType.value = type;
};

const openDeleteDialog = (item) => {
  currentInfo.value = item;
  isDeleteDialogOpen.value = true;
};

const pageRuleList = async () => {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value || "",
      page: pagination.value.current_page,
      sort_key: sortBy.value || "",
      sort_order: orderBy.value || "",
      per_page: pagination.value.per_page,
    };

    const response = await $api("/rules", { params });
    const { data, ...paginationData } = response.data;
    ruleData.value = data ?? [];
    pagination.value = { ...paginationData };
    isDialogVisible.value = false;
  } catch (error) {
    console.error("Error fetching Rule list:", error);
    toast.error(
      error?.response?.data?.message || "Error fetching Rule list."
    );
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (item) => {
  try {
    const res = await $api(`/rule-status-update/${item.id}`, {
      method: 'POST',
      body: { status: item.status }
    });
    toast.success(res?.message || "Rule updated successfully");
  } catch (err) {
    toast.error(err?._data?.message || "Error updating Rule");
  } finally {
    editingStatusId.value = null;
  }
};

// Watchers to handle pagination updates dynamically
watch([() => pagination.value.current_page, () => pagination.value.per_page, () => searchQuery.value,],
  (newValues, oldValues) => {
    const hasChanged = newValues.some((val, index) => val !== oldValues[index]);
    if (hasChanged) {
      pageRuleList();
    }
  }
);

onMounted(async () => {
  pageRuleList();
  await fetchStatusList('Rule');
  try {
    const response = await $api(`/table-header/get?slug=${tableHeaderSlug.value}`);
    const serverHeaders = response?.data?.headers ?? response?.data ?? null;
    if (Array.isArray(serverHeaders) && serverHeaders.length) {
      headers.value = serverHeaders.map(h => ({ ...h, checked: typeof h.checked === 'boolean' ? h.checked : true }));
    }
  } catch (error) {
    console.error('Error fetching table headers:', error);
  }
});
</script>
<style scoped>
.text-success {
  color: green;
  font-weight: 600;
}

.text-error {
  color: red;
  font-weight: 600;
}

.color-circle {
  display: inline-block;
  border: 1px solid #ccc;
  border-radius: 50%;
  block-size: 16px;
  cursor: pointer;
  inline-size: 16px;
}
</style>
