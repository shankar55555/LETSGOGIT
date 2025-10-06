<script setup>
import { $api } from '@/utils/api';
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import moment from 'moment';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';

const route = useRoute();
const props = defineProps({
  InfoData: { type: null, required: true, },
})

const makeDateFormat = (date, onlyDate = false) => {
  const m = moment.utc(date);
  return onlyDate ? m.format('DD-MM-YYYY') : m.format('dddd, MMMM Do YYYY, h:mm A');
};

const baseUrl = ref(window.location.origin);
const statusEditing = ref(false);
const selectedStatus = ref(props.InfoData.status);
const { statusList, fetchStatusList } = useFetchStatusList();

const isStatusConfirmVisible = ref(false);
const confirmStatus = ref(null);
const statusLoader = ref(false);
const tempStatus = ref(null);

// Start editing and sync initial value
const startStatusEditing = () => {
  tempStatus.value = selectedStatus.value;
  statusEditing.value = true;
};

// Open confirm dialog if value changed
const openStatusConfirmDialog = (id, oldStatus, newStatus) => {
  if (!newStatus || oldStatus === newStatus) return;
  confirmStatus.value = { id, oldStatus, newStatus, };
  isStatusConfirmVisible.value = true;
};

// Called when the dialog closes without saving
const onDialogClose = () => {
  isStatusConfirmVisible.value = false;
  confirmStatus.value = null;
  statusLoader.value = false;
  tempStatus.value = confirmStatus.value?.oldStatus ?? selectedStatus.value;
};

// Confirm and update
const updateStatusValue = async (item) => {
  statusLoader.value = true;
  try {
    await $api(`/clients/${item.id}/status`, { method: 'PUT', body: { status: item.newStatus } });
    toast.success("Status updated successfully");
    selectedStatus.value = item.newStatus;
    tempStatus.value = item.newStatus;
    emit("backCallLeadInfo");
  } catch (error) {
    toast.error(error?.response?.data?.message || "Failed to update status");
  } finally {
    isStatusConfirmVisible.value = false;
    confirmStatus.value = null;
    statusLoader.value = false;
  }
};

watch(() => props.InfoData?.status, (newVal) => {
  selectedStatus.value = newVal;
  tempStatus.value = newVal;
});

// fetching atachements
const attachments = ref([]);
const fetchSharedAttachments = async () => {
  try {
    const res = await $api(`/client-attachments/${props.InfoData.id}`, { method: 'GET' });
    attachments.value = res.data;
  } catch (error) {
    toast.error(error?.response?.data?.message || "Failed to fetch Client data");
  }
}

// Document viewer dialog
const isDocViewerOpen = ref(false);
const currentDocument = ref(null);

const openDocumentViewer = (document) => {
  currentDocument.value = document;
  isDocViewerOpen.value = true;
};

const closeDocumentViewer = () => {
  isDocViewerOpen.value = false;
  currentDocument.value = null;
};

onMounted(() => {
  fetchStatusList(MODULE_CLIENT);
  selectedStatus.value = props.InfoData.status;
  fetchSharedAttachments();
});

const formatAnniversaryDate = (date) => {
  if (!date) return '';
  return moment(date).format('DD-MMM-YYYY');
};
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <!-- SECTION Client Info -->
        <VCard>
          <VCardText>
            <h5 class="text-h5 mb-4">Client Details</h5>
            <VRow v-if="props.InfoData" dense>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Name:</strong>
                  <span>{{ props.InfoData.name }}</span>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Email:</strong>
                  <span>{{ props.InfoData.email }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Phone:</strong>
                  <span>{{ props.InfoData.phone }}</span>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Secondary Phone:</strong>
                  <span>{{ Array.isArray(props.InfoData.secondary_phone) ? props.InfoData.secondary_phone.join(', ') :
                    (props.InfoData.secondary_phone ?? '-')
                  }}</span>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Assigned To:</strong>
                  <span>{{ props.InfoData.assigned_user?.name || '-' }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Created By:</strong>
                  <span>{{ props.InfoData.creator?.name || '-' }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Last Updated By:</strong>
                  <span>{{ props.InfoData.updater?.name || '-' }}</span>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Created At:</strong>
                  <span>{{ makeDateFormat(props.InfoData.created_at) }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Last Updated At:</strong>
                  <span>{{ props.InfoData.updater ? makeDateFormat(props.InfoData.updated_at) : '-' }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Status:</strong>
                  <span style="min-inline-size: 200px;">
                    <VSelect v-if="statusEditing && $can('leads', 'edit')" v-model="tempStatus"
                      :items="statusFilterPosition(statusList, tempStatus)" item-title="status_text" item-value="slug"
                      dense hide-details label="Select Status" @blur="statusEditing = false"
                      @change="statusEditing = false"
                      @update:modelValue="() => openStatusConfirmDialog(props.InfoData.id, props.InfoData.status, tempStatus)" />
                    <VChip v-else @dblclick="startStatusEditing"
                      :color="$resolveStatusVariant(selectedStatus, statusList).color" size="small"
                      class="cursor-pointer">
                      {{ $resolveStatusVariant(selectedStatus, statusList).text }}
                    </VChip>
                  </span>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Last SiteVisit Status:</strong>
                  <VChip :color="props.InfoData?.last_site_visit_status?.color || 'default'" size="small">
                    {{ props.InfoData?.last_site_visit_status?.title ?? '--' }}
                  </VChip>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Last Followup status:</strong>
                  <VChip :color="props.InfoData?.last_followup_status?.color || 'default'" size="small">
                    {{ props.InfoData?.last_followup_status?.title ?? '--' }}
                  </VChip>
                </div>
              </VCol>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Last Quotation Status:</strong>
                  <VChip :color="props.InfoData?.last_quotation_status?.color || 'default'" size="small">
                    {{ props.InfoData?.last_quotation_status?.title ?? '--' }}
                  </VChip>
                </div>
              </VCol>

              <!-- City -->
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>City:</strong>
                  <span>{{ props.InfoData.city_name || '-' }}</span>
                </div>
              </VCol>

              <!-- Date of Birth -->
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Date of Birth:</strong>
                  <span>{{ formatAnniversaryDate(props.InfoData.date_of_birth) || '-' }}</span>
                </div>
              </VCol>

              <!-- Anniversary Date -->
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Anniversary Date:</strong>
                  <span>{{ formatAnniversaryDate(props.InfoData.anniversary_date) || '-' }}</span>
                </div>
              </VCol>
            </VRow>
            <VRow v-else cols="12">
              <VAlert type="info" variant="tonal">
                No items found for this Client.
              </VAlert>
            </VRow>
          </VCardText>
        </VCard>

        <!-- SECTION Shared Attachments -->
        <VCard class="mt-4">
          <VCardText>
            <h5 class="text-h5 mb-4">Shared Attachments</h5>
            <VRow v-if="attachments && attachments.length" dense>
              <div class="d-flex align-center justify-space-between pdf-thumbnail" @click="openDocumentViewer(file)"
                v-for="(file, index) in attachments" :key="index" style="margin-right: 15px;">
                <div class=" d-flex align-center border border-primary pa-3">
                  <VIcon size=" 48"
                    :icon="file.file_path.toLowerCase().endsWith('.pdf') ? 'tabler-file-type-pdf' : 'tabler-file'"
                    :color="'primary'" />
                  <div>
                    <div class="text-body-1">{{ file.file_name }}</div>
                    <div class="text-caption">Click to view PDF</div>
                    <div class="text-caption">Send At : {{ moment(file.created_at).format('DD-MMM-YYYY') }}</div>
                  </div>
                </div>
              </div>
            </VRow>
            <VRow v-else cols="12">
              <VAlert type="info" variant="tonal">
                No attachments found for this quotation.
              </VAlert>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- PDF Viewer dialog box -->
    <VDialog v-model="isDocViewerOpen" max-width="90%" scrollable>
      <VCard class="pdf-viewer-dialog">
        <VCardText class="pa-0">
          <div v-if="currentDocument" class="d-flex align-center px-4 py-2">
            <div class="d-flex align-center">
              <VIcon color="error" icon="mdi-file-pdf-box" class="me-2" />
              <span class="text-subtitle-1">{{ currentDocument?.file_name }}</span>
            </div>
            <VSpacer />
            <div class="d-flex gap-3">
              <VBtn size="small" variant="tonal" @click="closeDocumentViewer">Close</VBtn>
            </div>
          </div>

          <VDivider />
          <div v-if="currentDocument" class="document-viewer">
            <div v-if="currentDocument.file_path.toLowerCase().endsWith('.pdf')" class="pdf-not-available-message">
              <iframe :src="`${baseUrl}/storage/${currentDocument.file_path}`" width="100%" height="600"
                frameborder="0"></iframe>
            </div>
            <div v-else>
              <img :src="`${baseUrl}/storage/${currentDocument.file_path}`" width="100%" height="600"
                frameborder="0"></img>
            </div>
          </div>
          <div v-else>

          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Status Confirm Dialog -->
    <StatusConfirmDialog v-model:isStatusConfirmVisible="isStatusConfirmVisible" :currentItem="confirmStatus"
      :loader="statusLoader" :statusList="statusList" @updateStatusValue="updateStatusValue" @close="onDialogClose" />
  </div>
</template>
