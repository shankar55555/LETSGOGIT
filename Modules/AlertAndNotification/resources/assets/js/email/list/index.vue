<template>
  <section v-if="$can('emailLog', 'view')">
    <!-- <VBreadcrumbs class="app-breadcrumbs" color="primary" density="compact" :items="bread" /> -->
    <VCard class="mb-6">
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <div>
            <h4 class="text-h4 text-center">Email Log</h4>
          </div>

          <div class="d-flex flex-row gap-4 align-center flex-wrap">
            <Filters :initial-show-status-filter="showStatusFilter" :initial-show-search-filter="showSearchFilter"
              @update:filters="updateFilters" :statusFilter="true" :searchFilter="true" />

            <VBtn icon="tabler-table-options" size="small" variant="outlined"
              @click="showSyncHeader = !showSyncHeader" />
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

        <VSelect v-model="searchStatus" @update:modelValue="(value) => getMailLogList()" label="Select Status"
          :clearable="!!searchStatus" :items="statusList" style="max-inline-size: 150px; min-inline-size: 150px;"
          item-title="status_text" item-value="slug" v-if="showStatusFilter" class="ml-5" />
      </div>

      <VDivider class="mt-3" v-if="showSearchFilter || showStatusFilter || showSyncHeader" />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="pagination.per_page" :items="userList" :items-length="userList.length"
          :headers="headers.filter((header) => header.checked)" class="text-no-wrap" mobile-breakpoint="600"
          @update:options="updateTableSort">

          <!-- Content -->
          <template #item.content="{ item }">
            <VIcon v-if="item.is_read_by_user == 1" icon="tabler-eye" color="primary" variant="elevated" :size="20"
              class="me-3" @click="openContentDialog(item)" />
            <VIcon v-if="item.is_read_by_user == 0" icon="tabler-eye" color="secondary" variant="tonal" :size="20"
              class="me-3" @click="openContentDialog(item)" />
          </template>

          <!-- Status Info -->
          <template #item.status="{ item }">
            <template v-if="editingStatusId === item.id && $can('emailLog', 'edit')">
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

          <!-- Other Info -->
          <template #item.other_info="{ item }">
            <VIcon icon="tabler-info-circle" color="primary" variant="elevated" :size="20" class="me-3"
              @click="otherInfoDialog(item)" />
          </template>

          <!-- Sender User -->
          <template #item.sender_id="{ item }">
            {{ item.sender ? item.sender.name : '' }}
          </template>

          <!-- Receiver User -->
          <template #item.receiver_id="{ item }">
            <VTooltip location="top">
              <template #activator="{ props }">
                <div v-bind="props">
                  {{ item.receiver ? item.receiver.name :
                    item.receiver_b_to_b ? item.receiver_b_to_b.name :
                      item.receiver_lead ? item.receiver_lead.name :
                        item.receiver_client ? item.receiver_client.name : "" }}
                </div>
              </template>
              <template #default>
                Type:
                {{ item.receiver ? 'User' :
                  item.receiver_b_to_b ? 'B2B User' :
                    item.receiver_lead ? 'Lead' :
                      item.receiver_client ? 'Client' : "" }}
              </template>
            </VTooltip>
          </template>

          <!-- Section Type -->
          <template #item.section_type="{ item }">
            {{ item.is_notification ? "Only Notification" : item.section_type }}
          </template>

          <!-- Email Type Title -->
          <template #item.notification_type_id="{ item }">
            {{ item.notification_type ? item.notification_type.title : 'Manual' }}
          </template>

          <!-- Email Type Title -->
          <template #item.created_at="{ item }">
            {{ $typeAccordingDateFormatChange(item.created_at, 'custom_2') }}
          </template>

          <!-- module_id -->
          <template #item.module_id="{ item }">
            <!-- b_to_b_user -->
            <div v-if="item.b_to_b_user">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div class="d-flex align-center gap-x-4" v-bind="props" @click="handleItemClick(item)">
                    <VAvatar size="34" :variant="!item.b_to_b_user.avatar ? 'tonal' : undefined">
                      <VImg v-if="item.b_to_b_user.avatar" :src="item.b_to_b_user?.avatar" />
                      <span v-else>{{ item.b_to_b_user.name.charAt(0) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <h6 class="text-base">{{ item.b_to_b_user.name }}</h6>
                    </div>
                  </div>
                </template>
                <span>Type: B2B User</span>
              </VTooltip>
            </div>

            <!-- Lead -->
            <div v-else-if="item.lead">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div class="d-flex align-center gap-x-4" v-bind="props" @click="handleItemClick(item)">
                    <VAvatar size="34" :variant="!item.lead.avatar ? 'tonal' : undefined">
                      <VImg v-if="item.lead.avatar" :src="item.lead?.avatar" />
                      <span v-else>{{ item.lead.name.charAt(0) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <h6 class="text-base">{{ item.lead.name }}</h6>
                    </div>
                  </div>
                </template>
                <span>Type: Lead</span>
              </VTooltip>
            </div>

            <!-- Client -->
            <div v-else-if="item.client">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div class="d-flex align-center gap-x-4" v-bind="props" @click="handleItemClick(item)">
                    <VAvatar size="34" :variant="!item.client.avatar ? 'tonal' : undefined">
                      <VImg v-if="item.client.avatar" :src="item.client?.avatar" />
                      <span v-else>{{ item.client.name.charAt(0) }}</span>
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <h6 class="text-base">{{ item.client.name }}</h6>
                    </div>
                  </div>
                </template>
                <span>Type: Client</span>
              </VTooltip>
            </div>

            <!-- SRM -->
            <div v-else-if="item.srm">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div v-bind="props" @click="handleItemClick(item)">
                    {{ item.srm ? item.srm.visit_notes : '' }}
                  </div>
                </template>
                <span>Type: Site-Visit</span>
              </VTooltip>
            </div>

            <!-- Quotation -->
            <div v-else-if="item.quotation">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div v-bind="props" @click="handleItemClick(item)">
                    {{ item.quotation ? item.quotation.title + item.quotation.quotation_number : '' }}
                  </div>
                </template>
                <span>Type: Quotation</span>
              </VTooltip>
            </div>

            <!-- Contract -->
            <div v-else-if="item.contract">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div v-bind="props" @click="handleItemClick(item)">
                    {{ item.contract ? item.contract.title : '' }}
                  </div>
                </template>
                <span>Type: Contract</span>
              </VTooltip>
            </div>

            <!-- Follow Up -->
            <div v-else-if="item.follow_up">
              <VTooltip location="top">
                <template #activator="{ props }">
                  <div v-bind="props" @click="handleItemClick(item)">
                    {{ item.follow_up ? item.follow_up.lead_prospect : '' }}
                  </div>
                </template>
                <span>Type: Follow Up</span>
              </VTooltip>
            </div>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <VIcon v-if="$can('emailLog', 'delete')" icon="tabler-trash" color="primary" variant="elevated" :size="20"
              class="me-3" @click="openDeleteDialog(item)" />
          </template>

          <template #bottom>
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 px-6 py-3">
              <p class="text-disabled mb-0"> Showing {{ pagination.from }} to {{ pagination.to }} of {{
                pagination.total }} entries </p>
              <div class="d-flex flex-wrap gap-2 align-center">
                <AppSelect :model-value="pagination.per_page" :items="[10, 25, 50, 100]"
                  @update:model-value="val => { pagination.per_page = val; getMailLogList(); }"
                  style="inline-size: 6.25rem;" />

                <v-pagination v-model="pagination.current_page" :length="pagination.last_page" :total-visible="5" />
              </div>
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>
    <!-- 👉 Other Mail info Dialog -->
    <VDialog v-model="mailInfoDialog" width="650">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Email Other Info</span>
          <IconBtn @click="emailLogInfo = '', mailInfoDialog = false">
            <VIcon icon="tabler-x" />
          </IconBtn>
        </VCardTitle>
        <VDivider />
        <VCardText>
          <div v-if="emailLogInfo?.priority" class="mb-2">
            <h4 class="text-h6 ">Priority : </h4>
            <span>{{ emailLogInfo.priority }}</span>
          </div>

          <div v-if="emailLogInfo?.message" class="mb-2">
            <h4 class="text-h6 ">Message : </h4>
            <span>{{ emailLogInfo.message }}</span>
          </div>

          <div v-if="emailLogInfo?.email_body" class="mb-2">
            <h4 class="text-h6 ">Email Body : </h4>
            <vue-json-pretty :data="emailLogInfo.email_body" />
          </div>

          <div v-if="emailLogInfo?.additional_info" class="mb-2">
            <h4 class="text-h6 mt-4 mb-2">Additional Info : </h4>
            <vue-json-pretty :data="emailLogInfo.additional_info" />
          </div>

          <div v-if="emailLogInfo.is_delete" class="mb-2">
            <h4 class="text-h6">Soft Delete : </h4>
            <span>{{ emailLogInfo.is_delete ? "Soft Deleted" : 'No' }}</span>
          </div>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn color="primary" @click="emailLogInfo = '', mailInfoDialog = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Mail View Dialog -->
    <VDialog v-model="mailViewDialog" width="650">
      <VCard>
        <VCardTitle class="text-h5">
          {{ emailLogInfo.subject }}
        </VCardTitle>
        <VDivider />
        <VCardText>
          <div v-html="emailLogInfo.content" />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="black" text @click="emailLogInfo = '', mailViewDialog = false">
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Delete Dialog -->
    <DeleteDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to Soft delete Email Log?" :currentItem="currentInfo"
      @submit="getMailLogList" :action="'force_delete'" :endpoint="`/email/delete-notification/${currentInfo?.id}`"
      @close="isDeleteDialogOpen = false" />
  </section>
</template>

<script setup>
import { goToPage, useFetchStatusList } from "@/utils/common";
import { defineProps, ref } from 'vue';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import { toast } from "vue3-toastify";
import DeleteDialog from "../../DeleteDialog.vue";

import { useRoute, useRouter } from 'vue-router';
const router = useRouter();
const route = useRoute();

const props = defineProps({
  module_id: { type: String, required: false, default: null },
  log_type: { type: String, required: false, default: null },
});

const searchQuery = ref('');
const loading = ref(true);

const tableHeaderSlug = ref('email-log-header-list');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const userList = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 0, to: 0 });
const sortBy = ref();
const orderBy = ref();
const emailLogInfo = ref('');
const mailInfoDialog = ref(false);
const mailViewDialog = ref(false);
const isDeleteDialogOpen = ref(false)
const currentInfo = ref(null);

const showSyncHeader = ref(false);
const showStatusFilter = ref(false)
const showSearchFilter = ref(false)

// Update filters from LeadFilters component
const updateFilters = (filters) => {
  showStatusFilter.value = filters.showStatusFilter
  showSearchFilter.value = filters.showSearchFilter
}

// Fetching the data from the API.
const getMailLogList = async () => {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value || '',
      page: pagination.value.current_page,
      sort_key: sortBy.value || '',
      sort_order: orderBy.value || '',
      per_page: pagination.value.per_page,
      status: searchStatus.value ?? '',
    };
    if (props.module_id && props.log_type) {
      params.module_id = props.module_id;
      params.module_log_type = props.log_type;
    }
    const response = await $api('/email/log-list', { params });
    const { data, ...paginationData } = response.data;
    userList.value = data ?? [];
    pagination.value = { ...paginationData };
  } catch (error) {
    console.error('Error fetching user list:', error);
    toast.error(error?.response?.data?.message || 'Error fetching user list.');
  } finally {
    loading.value = false;
  }
}

// Update table sort options
const updateTableSort = (options) => {
  sortBy.value = options.sortBy[0]?.key || '';
  orderBy.value = options.sortBy[0]?.order || '';
};

const openContentDialog = item => {
  if (item.is_read_by_user == false) {
    $api('/email/update-read-status', { method: "POST", body: { id: item.id } }).then(res => {
      item.is_read_by_user = true
    }).catch(e => { })
  }
  emailLogInfo.value = item
  mailViewDialog.value = true
}

// Watchers to handle pagination updates dynamically
watch([() => pagination.value.current_page, () => pagination.value.per_page, () => searchQuery.value,],
  (newValues, oldValues) => {
    const hasChanged = newValues.some((val, index) => val !== oldValues[index]);
    if (hasChanged) {
      getMailLogList();
    }
  }
);

const otherInfoDialog = item => {
  emailLogInfo.value = item
  if (item.email_body && typeof item.email_body === 'string') {
    emailLogInfo.value.email_body = JSON.parse(item.email_body);
  } else {
    emailLogInfo.value.email_body = item.email_body;
  }

  if (item.additional_info && typeof item.additional_info === 'string') {
    emailLogInfo.value.additional_info = JSON.parse(item.additional_info);
  } else {
    emailLogInfo.value.additional_info = item.additional_info;
  }

  mailInfoDialog.value = true
}

const openDeleteDialog = (item) => {
  currentInfo.value = item;
  isDeleteDialogOpen.value = true;
}

const handleItemClick = async (item) => {
  if (item.is_read_by_user == false) {
    await $api('/email/update-read-status', { method: "POST", body: { id: item.id } }).then(res => {
      item.is_read_by_user = true
    }).catch(e => { })
  }

  const typeMap = TYPE_MAP_NOTIFICATION_LIST;
  const type = typeMap.find(t => item[t]);

  const url = goToPage(item, type);
  if (url) {
    router.push(url);
  } else {
    // toast.error("You do not have permission to view this page.");
  }
};

// Fetch Quotation Status List
const searchStatus = ref('');
const { statusList, fetchStatusList } = useFetchStatusList();

const editingStatusId = ref(null);
const updateStatusValue = async (item) => {
  try {
    const res = await $api(`/email/status-update`, {
      method: 'POST',
      body: { notification_log_id: item.id, status: item.status }
    });
    toast.success(res?.message || "Notification log Status updated successfully");
  } catch (err) {
    toast.error(err?._data?.message || "Error updating Notification log  Status");
  } finally {
    editingStatusId.value = null;
  }
};

onMounted(async () => {
  await fetchStatusList(NOTIFICATION_LOG);
  await getMailLogList();
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
