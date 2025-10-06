<template>
  <section v-if="$can('status', 'view')">
    <VCard class="mb-6">
      <!-- Top Section: Search and Actions -->
      <div class="d-flex flex-wrap justify-space-between align-center pa-4">
        <div class="d-flex gap-2 ms-auto">
          <!-- <Filters :initial-show-search-filter="showSearchFilter" @update:filters="updateFilters"
            :searchFilter="true" /> -->

          <!-- Filter Header Btn FilterHeaderTableBtn -->
          <VBtn icon="tabler-table-options" size="small" variant="outlined" @click="showSyncHeader = !showSyncHeader" />

          <VBtn v-if="$can('status', 'create')" icon="tabler-plus"
            @click="isDialogVisible = !isDialogVisible; currentInfo = null;" color="primary" variant="elevated">
          </VBtn>
        </div>
      </div>

      <VDivider class="mb-3" />

      <!-- Bottom Section: Tabs -->
      <div class="pa-4">
        <div class="text-h5">
          <VTabs v-model="tabType" direction="horizontal" class="v-tabs-pill disable-tab-transition"
            density="comfortable" color="primary">
            <VTab v-for="page in pageList" :key="page" class="text-body-2 font-weight-medium">
              {{ page }}
            </VTab>
          </VTabs>
        </div>
      </div>

      <VCardText v-if="showSyncHeader">
        <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue"
          @close="showSyncHeader = false" />
      </VCardText>

      <!-- <div class="d-flex gap-2 justify-start" v-if="showSearchFilter">
        <AppTextField v-model="searchQuery" class="flex-grow-1 flex-md-grow-0 ml-5 mb-3 "
          style="max-inline-size: 280px; min-inline-size: 280px;" placeholder="Search" density="comfortable"
          variant="outlined" prepend-inner-icon="tabler-search" v-if="showSearchFilter" />
      </div> -->

      <VDivider v-if="showSearchFilter" class="mb-3" />

      <BaseSpinner class="d-flex" v-if="loading" />
      <VDataTableServer v-else v-model:items-per-page="pagination.per_page" :items="statusList"
        :items-length="statusList.length" :headers="headers.filter((header) => header.checked)" class="text-no-wrap"
        mobile-breakpoint="600" @update:options="updateTableSort">

        <!-- Status -->
        <template #item.status="{ item }">
          <div v-if="editingStatusId === item.id && item.is_predefined == 1">
            <VSelect v-model="item.status" @update:modelValue="(value) => updateStatus(item)"
              @blur="editingStatusId = null" :items="statusOptions" item-title="text" item-value="value">
            </VSelect>
          </div>
          <div v-else @dblclick="editingStatusId = item.id">
            <span :class="item.position > 0 ? 'text-success' : 'text-error'" style="cursor: pointer;">
              {{ item.position > 0 ? "Active" : "In-Active" }}
            </span>
          </div>
        </template>

        <!-- Trigger Action -->
        <template #item.trigger_action="{ item }">
          <div>{{ item.trigger_actions ? showPlatForm(item) : '' }}</div>
        </template>

        <!-- <template #item.trigger_action="{ item }">
          <div>
            {{ item.trigger_actions ? item.trigger_actions.map(action => {
                    if(SEND_PLAT_FROM.includes(action.value) && item.send_plat_forms) return `${action.title} (${item.send_plat_forms.join(', ')})`;
                    return action.title;
                  }).join(', ') : ''
            }}
          </div>
        </template> -->


        <!-- Send Plat Forms ['Sms', 'Email' ,WhatsApp]-->
        <!-- <template #item.send_plat_forms="{ item }">
          <div>{{ item.send_plat_forms ? item.send_plat_forms.join(', ') : '' }}</div>
        </template> -->

        <!-- Color -->
        <template #item.status_color="{ item }">
          <div v-if="editingColorId === item.id">
            <VTextField type="color" v-model="item.status_color" style="border: none; background: transparent;"
              @change="(e) => updateStatusColor(item, e.target.value)" @blur="editingColorId = null" />
          </div>
          <div v-else :style="{ backgroundColor: item.status_color }" class="color-circle"
            :title="`Double-click to edit.`" @dblclick="editingColorId = item.id"></div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn v-if="$can('status', 'edit')" @click="editBranch(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="$can('status', 'delete') && item.is_predefined == 1" @click="openDeleteDialog(item)"
            v-tooltip="'Delete Status'">
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
                  pageStatusList();
                }
              " style="inline-size: 6.25rem;" />

              <v-pagination v-model="pagination.current_page" :length="pagination.last_page" :total-visible="5" />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddEditStatusDrawer v-if="isDialogVisible" @submit="pageStatusList" :currentInfo="currentInfo"
      v-model:isDialogVisible="isDialogVisible" />

    <!-- 👉 Delete Dialog -->
    <DeleteDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete Status?" :currentItem="currentInfo" @submit="pageStatusList"
      :action="'force_delete'" :endpoint="`/settings/page-status-delete/${currentInfo?.id}`"
      @close="isDeleteDialogOpen = false" />
  </section>
</template>

<script setup>
import "@vuepic/vue-datepicker/dist/main.css";
import { debounce } from "lodash";
import { onMounted, ref } from "vue";
import { toast } from "vue3-toastify";
import AddEditStatusDrawer from "./AddEditStatusDrawer.vue";
// Data table Headers
const tableHeaderSlug = ref("setting-status-list");
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => {
  headers.value = headerList;
};

const loading = ref(true);
const searchQuery = ref("");
const statusList = ref([]);
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
const tabType = ref(0);
const tabPage = ref("All");

const showSyncHeader = ref(false);
const showSearchFilter = ref(false);
// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showSearchFilter.value = filters.showSearchFilter
}


const statusOptions = ref([
  { text: "Active", value: "1" },
  { text: "In-Active", value: "0" },
]);

const editingStatusId = ref(null);
const editingColorId = ref(null);

// Update table sort options
const updateTableSort = (options) => {
  sortBy.value = options.sortBy[0]?.key || "";
  orderBy.value = options.sortBy[0]?.order || "";
};

const editBranch = (item) => {
  currentInfo.value = item;
  isDialogVisible.value = true;
};

const openDeleteDialog = (item) => {
  currentInfo.value = item;
  isDeleteDialogOpen.value = true;
};

const pageList = ref([]);
const fetchPageList = async () => {
  try {
    const params = { type: "list" };
    const response = await $api("/settings/page", { params });
    // ✅ Prepend "All" to the page list
    pageList.value = ["All", ...response.data.data];
  } catch (error) {
    console.error("Error fetching status list:", error);
    toast.error(
      error?.response?.data?.message || "Error fetching status list."
    );
  }
};

const showPlatForm = (item) => {
  return item.trigger_actions.map(action => {
    if (SEND_PLAT_FROM.includes(action.value) && item.send_plat_forms) {
      return `${action.title} (${item.send_plat_forms.join(', ')})`;
    }
    return action.title;
  }).join(', ');
};

const debouncedFetchStatus = debounce(() => {
  pageStatusList();
}, 500);

watch(searchQuery, (newValue) => {
  if (newValue || newValue === "") {
    debouncedFetchStatus();
  }
});

const pageStatusList = async () => {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value || "",
      type: tabPage.value || "All",
      page: pagination.value.current_page,
      sort_key: sortBy.value || "",
      sort_order: orderBy.value || "",
      per_page: pagination.value.per_page,
    };

    const response = await $api("/settings/status-list", { params });
    const { data, ...paginationData } = response.data;
    statusList.value = data ?? [];
    pagination.value = { ...paginationData };
    isDialogVisible.value = false;
  } catch (error) {
    console.error("Error fetching status list:", error);
    toast.error(
      error?.response?.data?.message || "Error fetching status list."
    );
  } finally {
    loading.value = false;
  }
};

const copyColorCode = (color) => {
  return;
  navigator.clipboard
    .writeText(color)
    .then(() => toast.success(`Copied: ${color}`))
    .catch(() => toast.error("Failed to copy color"));
};

const updateStatus = async (item) => {
  try {
    const res = await $api(`/settings/status-update/${item.id}`, {
      method: 'POST',
      body: { status: parseInt(item.status) == 0 ? 0 : 999, status_for: item.status_for }
    });
    item.position = parseInt(item.status) == 0 ? 0 : 999
    toast.success(res?.message || "Status updated successfully");
  } catch (err) {
    toast.error(err?._data?.message || "Error updating status");
  } finally {
    editingStatusId.value = null;
  }
};

const updateStatusColor = async (item, newColor) => {
  item.status_color = newColor;
  try {
    const res = await $api(`/settings/change-color-status/${item.id}`, {
      method: 'POST',
      body: { status_color: newColor, status_for: item.status_for }
    });
    toast.success(res?.message || "Color updated successfully");
    await pageStatusList();
  } catch (err) {
    toast.error(err?._data?.message || "Error updating color");
  } finally {
    editingColorId.value = null;
  }
};

// Watchers to handle pagination updates dynamically
watch([() => pagination.value.current_page, () => pagination.value.per_page],
  (newValues, oldValues) => {
    const hasChanged = newValues.some((val, index) => val !== oldValues[index]);
    if (hasChanged) {
      pageStatusList();
    }
  }
);

watch(tabType, (newVal) => {
  tabPage.value = pageList.value[newVal];
  pagination.value.current_page = 1;
  pageStatusList();
});

onMounted(async () => {
  fetchPageList(), pageStatusList();
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
