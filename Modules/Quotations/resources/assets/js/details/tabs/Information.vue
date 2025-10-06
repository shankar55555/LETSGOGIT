<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import { defineEmits, onMounted, ref } from 'vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
  InfoData: {
    type: null,
    required: true,
  },
})
const { statusList, fetchStatusList } = useFetchStatusList();

const emit = defineEmits(['status-updated'])

const statusEditing = ref(false);
const selectedStatus = ref('');
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
    const response = await $api(`/update-direct-quotation-status`, { method: 'POST', body: { id: item.id, status: item.newStatus } });
    toast.success(response.message);
    selectedStatus.value = item.newStatus;
    tempStatus.value = item.newStatus;
    emit("status-updated");
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

onMounted(() => {
  fetchStatusList(MODULE_QUOTATION);
  selectedStatus.value = props.InfoData.status;
});
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <VCard v-if="props.InfoData">
          <VCardText>
            <!-- Status Chip/Select UI -->
            <div v-if="props?.InfoData?.client_detail">
              <h5 class="text-h5 mb-4">Client Details</h5>

              <VRow dense>
                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Name:</strong>
                    <span>{{ props.InfoData.client_detail.name }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Contact Person</strong>
                    <span>{{ props.InfoData?.client_detail?.name ?? '--' }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Phone</strong>
                    <span>{{ props.InfoData?.client_detail?.phone ?? '--' }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Email:</strong>
                    <span>{{ props.InfoData?.client_detail?.email ?? '--' }}</span>
                  </div>
                </VCol>
              </VRow>

              <VDivider class="my-6" />
            </div>

            <div v-if="props?.InfoData?.lead_detail">
              <h5 class="text-h5 mb-4">Lead Details</h5>

              <VRow dense>
                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Name:</strong>
                    <span>{{ props.InfoData.lead_detail.name }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Phone</strong>
                    <span>{{ props.InfoData?.lead_detail?.phone ?? '--' }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="4" lg="4">
                  <div class="d-flex align-center gap-x-2 mt-1">
                    <strong>Email:</strong>
                    <span>{{ props.InfoData?.lead_detail?.email ?? '--' }}</span>
                  </div>
                </VCol>
              </VRow>

              <VDivider class="my-6" />
            </div>
            <!-- SECTION Quotation Info -->
            <h5 class="text-h5 mb-4">Quotation Details</h5>

            <VRow dense>
              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>title:</strong>
                  <span>{{ props.InfoData.title || '-' }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Valid Uptil:</strong>
                  <span>{{ props.InfoData.valid_uptil ? $typeAccordingDateFormatChange(props.InfoData.valid_uptil,
                    'd-m-y') : '-' }}</span>
                </div>
              </VCol>

              <VCol cols="12" md="4" lg="4">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Quotation Type:</strong>
                  <span>{{ props.InfoData.quotation_type || '-' }}</span>
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

              <h5 class="text-h5 mb-2">Quotation Items</h5>

              <VRow v-if="props.InfoData.items?.length">
                <VCol v-for="(item, index) in props.InfoData.items" :key="index" cols="12" md="6" lg="6">
                  <VCard class="mb-4" outlined>
                    <VCardText>
                      <VRow dense>
                        <VCol cols="12">
                          <strong>Name:</strong> {{ item.name }}
                        </VCol>
                        <VCol cols="6">
                          <strong>Quantity:</strong> {{ item.quantity }}
                        </VCol>
                        <VCol cols="6">
                          <strong>Unit Price:</strong> Rs. {{ Number(item.unit_price || 0).toFixed(2) }}
                        </VCol>
                        <VCol cols="6">
                          <strong>GST Rate:</strong> {{ item.tax_rate }}%
                        </VCol>
                        <VCol cols="6">
                          <strong>Subtotal:</strong> Rs. {{ Number(item.subtotal || 0).toFixed(2) }}
                        </VCol>
                        <VCol cols="6">
                          <strong>Discount:</strong> Rs. {{ Number(item.discount_amount || 0).toFixed(2) }}
                        </VCol>
                        <VCol cols="6">
                          <strong>Total:</strong> Rs. {{ Number(item.total || 0).toFixed(2) }}
                        </VCol>
                        <VCol cols="12">
                          <strong>Description:</strong> {{ item.description || '—' }}
                        </VCol>

                        <!-- ✅ Attribute Key-Value Loop -->
                        <VCol cols="12" v-if="item.attributes.length">
                          <strong class="text-primary">Attributes</strong>
                        </VCol>

                        <VCol cols="12" md="12" v-for="(attribute, i) in item.attributes" :key="i">
                          <VRow>
                            <VCol cols="12" lg="6" md="6">
                              <strong>Key:</strong> {{ attribute.key || '—' }}
                            </VCol>
                            <VCol cols="12" lg="6" md="6">
                              <strong>Value:</strong> {{ attribute.value || '—' }}
                            </VCol>
                          </VRow>
                        </VCol>
                      </VRow>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
              <VAlert v-else type="info" variant="tonal" class="mt-4">
                No items found for this quotation.
              </VAlert>

              <!-- <VCol cols="12" md="12" lg="12">
                <div class="d-flex align-center gap-x-2 mt-1">
                  <strong>Custom Header Text:</strong>
                  <span>{{ props.InfoData.custom_header_text }}</span>
                </div>
              </VCol> -->
              <VCol cols="12" md="12" lg="12" class="mt-5">
                <strong>Payment Terms:</strong>
                <div class="ml-5 mt-1">
                  <span v-html="props.InfoData.payment_terms || '-'"></span>
                </div>
              </VCol>
              <VCol cols="12" md="12" lg="12" class="mt-5">
                <strong>Terms & Conditions:</strong>
                <div class="ml-5 mt-1">
                  <span v-html="props.InfoData.terms_conditions || '-'"></span>
                </div>
              </VCol>
            </VRow>

            <VDivider class="my-6" />


            <!-- SECTION Items -->

          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="8">
      </VCol>
      <!-- Summary Card -->
      <VCol cols="12" md="4">
        <VCard>
          <VCardText>
            <div class="d-flex gap-2">
              <h5 class="text-h5 mb-4">Summary</h5>
              <p class="mt-1">(Rs.)</p>
            </div>

            <VRow dense>
              <VCol cols="12">
                <div class="d-flex justify-space-between">
                  <span>Subtotal:</span>
                  <strong>{{ Number(props.InfoData.sub_total ?? 0).toFixed(2) }}</strong>
                </div>
              </VCol>

              <VCol cols="12">
                <div class="d-flex justify-space-between">
                  <span>Total Discount:</span>
                  <strong>-{{ Number(props.InfoData.discount ?? 0).toFixed(2) }}</strong>
                </div>
              </VCol>

              <VCol cols="12">
                <div class="d-flex justify-space-between">
                  <span>Total GST:</span>
                  <strong>{{ Number(props.InfoData.tax ?? 0).toFixed(2) }}</strong>
                </div>
              </VCol>


              <VDivider class="my-2" />

              <VCol cols="12">
                <div class="d-flex justify-space-between">
                  <span><strong>Total:</strong></span>
                  <strong>Rs.{{ Number(props.InfoData.total ?? 0).toFixed(2) }}</strong>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Status Confirm Dialog -->
    <StatusConfirmDialog v-model:isStatusConfirmVisible="isStatusConfirmVisible" :currentItem="confirmStatus"
      :loader="statusLoader" :statusList="statusList" @updateStatusValue="updateStatusValue" @close="onDialogClose" />
  </div>
</template>

<style scoped>
.department_card {
  padding: 20px;
}

.file-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.file-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.pdf-viewer-dialog {
  border-radius: 8px;
  overflow: hidden;
}

.document-viewer {
  width: 100%;
  height: 100%;
  overflow: auto;
}

.pdf-thumbnail {
  cursor: pointer;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.pdf-not-available-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 600px;
  background-color: #f5f5f5;
  border-radius: 4px;
  text-align: center;
  padding: 2rem;
}

.pdf-not-available-message p {
  max-width: 400px;
  color: #666;
}
</style>
