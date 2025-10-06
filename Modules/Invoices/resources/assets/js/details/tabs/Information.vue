<script setup>
import dayjs from "dayjs";

const props = defineProps({
  InfoData: {
    type: null,
    required: true,
  },
})
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12" lg="6" md="6">

        <!-- Invoice Details Card -->
        <VCard class="mb-6">
          <VCardTitle>Invoice Details</VCardTitle>
          <VDivider />
          <VCardText>
            <div class="invoice_view_detail_grid">
              <div class="d-flex align-center gap-2">
                <div class="d-flex align-center gap-1">
                  <VIcon icon="tabler-circle-letter-t" color="secondary" size="20" />
                  <h6 class="text-h6">Title:</h6>
                </div>
                <p class="mb-0">{{ props.InfoData.title || '-' }}</p>
              </div>

              <div class="d-flex align-center gap-2">
                <div class="d-flex align-center gap-1">
                  <VIcon icon="tabler-circle-check" color="secondary" size="20" />
                  <h6 class="text-h6">Status:</h6>
                </div>
                <VChip :color="props.InfoData?.self_status?.color || 'default'" size="small">
                  {{ props.InfoData?.self_status?.title }}
                </VChip>
              </div>

              <div class="d-flex align-center gap-2">
                <div class="d-flex align-center gap-1">
                  <VIcon icon="tabler-calendar" color="secondary" size="20" />
                  <h6 class="text-h6">Due Date:</h6>
                </div>
                <p class="mb-0">{{ dayjs(props.InfoData?.due_date).format('DD-MM-YYYY') || '-' }}</p>
              </div>

              <div class="d-flex align-center gap-2">
                <div class="d-flex align-center gap-1">
                  <VIcon icon="tabler-user" color="secondary" size="20" />
                  <h6 class="text-h6">Created By:</h6>
                </div>
                <p class="mb-0">{{ props.InfoData.creator?.name || '-' }}</p>
              </div>

              <div class="d-flex align-center gap-2">
                <div class="d-flex align-center gap-1">
                  <VIcon icon="tabler-calendar" color="secondary" size="20" />
                  <h6 class="text-h6">Last Updated By:</h6>
                </div>
                <p class="mb-0">{{ props.InfoData.updater?.name || '-' }}</p>
              </div>
            </div>

            <!-- Invoice Description -->
            <div class="d-flex mt-3 align-start gap-2">
              <div class="d-flex align-center gap-1">
                <VIcon icon="tabler-align-justified" color="secondary" size="20" />
                <h6 class="text-h6">Description:</h6>
              </div>
              <p class="mb-0" style="font-style:italic">{{ props.InfoData.title || '-' }}</p>
            </div>
          </VCardText>
        </VCard>

        <!-- Invoice Items Card -->
        <VCard>
          <VCardTitle>Invoice Items</VCardTitle>
          <VDivider />
          <VCardText v-if="props.InfoData.items?.length">
            <VExpansionPanels variant="accordion" class="expansion-panels-width-border">
              <VExpansionPanel v-for="(item, index) in props.InfoData.items" :key="index" elevation="0">
                <VExpansionPanelTitle collapse-icon="tabler-minus" expand-icon="tabler-plus">
                  Item {{ index + 1 }}
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div class="invoice_view_detail_grid">
                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-tag" color="secondary" size="20" />
                        <h6 class="text-h6">Name:</h6>
                      </div>
                      <p class="mb-0">{{ item.name }}</p>
                    </div>

                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-basket-plus" color="secondary" size="20" />
                        <h6 class="text-h6">Quantity:</h6>
                      </div>
                      <p class="mb-0">{{ item.quantity }}</p>
                    </div>

                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-tax" color="secondary" size="20" />
                        <h6 class="text-h6">GST Rate:</h6>
                      </div>
                      <p class="mb-0">{{ item.tax_rate }}%</p>
                    </div>

                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-receipt-2" color="secondary" size="20" />
                        <h6 class="text-h6">Unit Price:</h6>
                      </div>
                      <p class="mb-0">₹. {{ Number(item.unit_price || 0).toFixed(2) }}</p>
                    </div>

                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-discount" color="secondary" size="20" />
                        <h6 class="text-h6">Discount:</h6>
                      </div>
                      <p class="mb-0">₹ {{ Number(item.discount_amount || 0).toFixed(2) }}</p>
                    </div>

                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-coin-rupee" color="secondary" size="20" />
                        <h6 class="text-h6">Total:</h6>
                      </div>
                      <p class="mb-0">₹ {{ Number(item.total || 0).toFixed(2) }}</p>
                    </div>
                  </div>

                  <!-- Invoice Item Description -->
                  <div class="d-flex my-3 align-start gap-2">
                    <div class="d-flex align-center gap-1">
                      <VIcon icon="tabler-align-justified" color="secondary" size="20" />
                      <h6 class="text-h6">Description:</h6>
                    </div>
                    <p style="font-style:italic" class="mb-0">{{ item.description || '—' }}</p>
                  </div>

                  <!-- ✅ Attribute Key-Value Loop -->
                  <div class="d-flex mt-3 mb-2 align-center gap-1">
                    <VIcon icon="tabler-list-details" color="primary" size="20" />
                    <h6 class="text-h6 text-primary">Attributes</h6>
                  </div>

                  <div v-for="(attribute, i) in item.attributes" :key="i" class="invoice_view_detail_grid mb-2">
                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-key" color="secondary" size="20" />
                        <h6 class="text-h6">Key:</h6>
                      </div>
                      <p class="mb-0">{{ attribute.key || '—' }}</p>
                    </div>
                    <div class="d-flex align-center gap-2">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-scale" color="secondary" size="20" />
                        <h6 class="text-h6">Value:</h6>
                      </div>
                      <p class="mb-0">{{ attribute.value || '—' }}</p>
                    </div>
                  </div>

                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </VCardText>
          <VCardText v-else>
            <VAlert type="info" variant="tonal" class="mt-4">
              No items found for this Invoice.
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Invoice Summary Card -->
      <VCol cols="12" lg="6" md="6">
        <VCard>
          <VCardTitle>
            Summary
            <span class="text-secondary">(₹)</span>
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="12">
                <div class="d-flex mb-2 align-center justify-space-between">
                  <p class="mb-0 invoice_summary_key">Subtotal:</p>
                  <p class="mb-0 invoice_summary_value">₹ {{ Number(props.InfoData.sub_total ?? 0).toFixed(2) }}</p>
                </div>
                <div class="d-flex mb-2 align-center justify-space-between">
                  <p class="mb-0 invoice_summary_key">Total GST:</p>
                  <p class="mb-0 invoice_summary_value">{{ Number(props.InfoData.tax ?? 0).toFixed(2) }}</p>
                </div>
                <div class="d-flex mb-2 align-center justify-space-between">
                  <p class="mb-0 invoice_summary_key">Total Discount:</p>
                  <p class="mb-0 invoice_summary_value">-{{ Number(props.InfoData.discount ?? 0).toFixed(2) }}</p>
                </div>
                <VDivider class="my-3" />
                <div class="d-flex align-center justify-space-between">
                  <p class="mb-0 invoice_summary_value">Total:</p>
                  <p class="mb-0 invoice_summary_value">₹ {{ Number(props.InfoData.total ?? 0).toFixed(2) }}</p>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.invoice_view_detail_grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.invoice_summary_key {
  font-size: 15px;
  color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
  font-weight: 400;
}

.invoice_summary_value {
  font-size: 15px;
  color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
  font-weight: 600;
}
</style>
