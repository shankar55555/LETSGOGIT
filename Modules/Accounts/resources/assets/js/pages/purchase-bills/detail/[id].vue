<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const purchaseBill = ref(null)
const loading = ref(true)
const imagePreviewDialog = ref(false)

const fetchPurchaseBill = async () => {
  try {
    const response = await $api(`/v1/purchase-bills/${route.params.id}`)
    purchaseBill.value = response.data
    loading.value = false
  } catch (error) {
    console.error('Failed to fetch purchase bill:', error)
    loading.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-IN')
}

const openImagePreview = () => {
  if (purchaseBill.value?.bill_image) {
    imagePreviewDialog.value = true
  }
}

onMounted(() => {
  fetchPurchaseBill()
})
</script>

<template>
  <div class="pa-6">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1 text-grey-darken-2">Purchase Bill Details</h1>
        <p class="text-body-2 text-grey mb-0">
          Thursday, September 4, 2025 4:45 PM
        </p>
      </div>
      <div class="d-flex gap-2">
        <VBtn variant="outlined" size="small" color="grey" prepend-icon="tabler-pencil"
          :to="{ name: 'purchase-bills-edit-id', params: { id: route.params.id } }">
          Edit
        </VBtn>
        <VBtn variant="tonal" size="small" color="grey" prepend-icon="tabler-arrow-left" 
          :to="{ name: 'account-pages-PurchaseBills' }">
          Back To List
        </VBtn>
      </div>
    </div>

    <BaseSpinner v-if="loading" />

    <div v-else-if="purchaseBill">
      <!-- Information Tab -->
      <VCard class="mb-4" elevation="0" style="background: #6366f1; color: white;">
        <VCardText class="pa-3">
          <div class="d-flex align-center">
            <VIcon icon="tabler-info-circle" class="me-2" size="20" />
            <span class="text-body-1 font-weight-medium">INFORMATION</span>
          </div>
        </VCardText>
      </VCard>

      <!-- Main Content Card -->
      <VCard elevation="0" class="border">
        <VCardText class="pa-6">
          <!-- Product Header -->
          <div class="d-flex align-center justify-space-between mb-6">
            <div>
              <h2 class="text-h5 font-weight-medium mb-1">{{ purchaseBill.bill_number || 'Purchase Bill' }}</h2>
              <div class="text-body-2 text-grey mb-2">{{ formatCurrency(purchaseBill.total_amount) }}</div>
            </div>
            <VChip size="small" color="success" variant="tonal">
              {{ purchaseBill.status || 'active' }}
            </VChip>
          </div>

          <!-- Meta Information -->
          <VRow class="mb-6">
            <VCol cols="6">
              <div class="d-flex align-center mb-2">
                <VIcon icon="tabler-calendar" size="16" class="me-2 text-grey" />
                <span class="text-caption text-grey">Created by:</span>
                <span class="text-body-2 ms-1">{{ purchaseBill.creator?.name || 'System' }}</span>
              </div>
            </VCol>
            <VCol cols="6">
              <div class="d-flex align-center mb-2">
                <VIcon icon="tabler-calendar" size="16" class="me-2 text-grey" />
                <span class="text-caption text-grey">Updated by:</span>
                <span class="text-body-2 ms-1">{{ purchaseBill.updater?.name || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="6">
              <div class="d-flex align-center">
                <VIcon icon="tabler-calendar" size="16" class="me-2 text-grey" />
                <span class="text-caption text-grey">Created:</span>
                <span class="text-body-2 ms-1">{{ formatDate(purchaseBill.created_at) }}</span>
              </div>
            </VCol>
            <VCol cols="6">
              <div class="d-flex align-center">
                <VIcon icon="tabler-calendar" size="16" class="me-2 text-grey" />
                <span class="text-caption text-grey">Updated:</span>
                <span class="text-body-2 ms-1">{{ formatDate(purchaseBill.updated_at) }}</span>
              </div>
            </VCol>
          </VRow>

          <!-- Information Sections -->
          <VRow>
            <!-- Basic Information -->
            <VCol cols="12" md="6">
              <VCard elevation="0" class="bg-blue-lighten-5 mb-4">
                <VCardTitle class="pa-4 pb-2">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-info-circle" color="blue" class="me-2" size="20" />
                    <span class="text-body-1 font-weight-medium">Basic Information</span>
                  </div>
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Bill Number</div>
                    <div class="text-body-2">{{ purchaseBill.bill_number || '-' }}</div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Bill Date</div>
                    <div class="text-body-2">{{ formatDate(purchaseBill.bill_date) }}</div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Due Date</div>
                    <div class="text-body-2">{{ formatDate(purchaseBill.due_date) }}</div>
                  </div>
                  <div>
                    <div class="text-caption text-grey mb-1">Payment Mode</div>
                    <div class="text-body-2">{{ purchaseBill.payment_mode || 'cash' }}</div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Pricing & Tax -->
            <VCol cols="12" md="6">
              <VCard elevation="0" class="bg-blue-lighten-5 mb-4">
                <VCardTitle class="pa-4 pb-2">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-currency-rupee" color="blue" class="me-2" size="20" />
                    <span class="text-body-1 font-weight-medium">Pricing & Tax</span>
                  </div>
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Subtotal</div>
                    <div class="text-body-2 font-weight-medium">{{ formatCurrency(purchaseBill.sub_total) }}</div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Tax Amount</div>
                    <div class="text-body-2">{{ formatCurrency(purchaseBill.tax_amount) }}</div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">GST (%)</div>
                    <div class="text-body-2">18%</div>
                  </div>
                  <div>
                    <div class="text-caption text-grey mb-1">Total Amount</div>
                    <div class="text-h6 font-weight-bold text-primary">{{ formatCurrency(purchaseBill.total_amount) }}</div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- Vendor Information -->
          <VRow v-if="purchaseBill.vendor">
            <VCol cols="12" md="6">
              <VCard elevation="0" class="bg-blue-lighten-5 mb-4">
                <VCardTitle class="pa-4 pb-2">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-building-store" color="blue" class="me-2" size="20" />
                    <span class="text-body-1 font-weight-medium">Vendor Information</span>
                  </div>
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Vendor Name</div>
                    <div class="text-body-2">{{ purchaseBill.vendor.company_name || `${purchaseBill.vendor.first_name} ${purchaseBill.vendor.last_name}` }}</div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">GSTIN</div>
                    <div class="text-body-2">{{ purchaseBill.vendor.gstin || '-' }}</div>
                  </div>
                  <div>
                    <div class="text-caption text-grey mb-1">State</div>
                    <div class="text-body-2">{{ purchaseBill.vendor_state || purchaseBill.vendor.state || '-' }}</div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Purchase Details -->
            <VCol cols="12" md="6">
              <VCard elevation="0" class="bg-blue-lighten-5 mb-4">
                <VCardTitle class="pa-4 pb-2">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-shopping-cart" color="blue" class="me-2" size="20" />
                    <span class="text-body-1 font-weight-medium">Purchase Details</span>
                  </div>
                </VCardTitle>
                <VCardText class="pa-4 pt-2">
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Purchase Mode</div>
                    <VChip size="small" color="info" variant="tonal">
                      {{ purchaseBill.purchase_mode || 'both' }}
                    </VChip>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-grey mb-1">Status</div>
                    <VChip :color="purchaseBill.status === 'paid' ? 'success' : 'warning'" size="small" variant="tonal">
                      {{ purchaseBill.status || 'unpaid' }}
                    </VChip>
                  </div>
                  <div v-if="purchaseBill.notes">
                    <div class="text-caption text-grey mb-1">Notes</div>
                    <div class="text-body-2">{{ purchaseBill.notes }}</div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Items Section -->
      <VCard elevation="0" class="border mt-4" v-if="purchaseBill.items?.length">
        <VCardText class="pa-6">
          <div class="d-flex align-center mb-4">
            <VIcon icon="tabler-list" color="blue" class="me-2" size="20" />
            <span class="text-body-1 font-weight-medium">Items ({{ purchaseBill.items?.length || 0 }})</span>
          </div>
          <VTable class="border rounded">
            <thead>
              <tr class="bg-grey-lighten-4">
                <th class="text-caption font-weight-medium pa-3">ITEM NAME</th>
                <th class="text-caption font-weight-medium pa-3">SKU</th>
                <th class="text-caption font-weight-medium pa-3">HSN/SAC</th>
                <th class="text-caption font-weight-medium pa-3">QUANTITY</th>
                <th class="text-caption font-weight-medium pa-3">RATE</th>
                <th class="text-caption font-weight-medium pa-3">DISCOUNT</th>
                <th class="text-caption font-weight-medium pa-3">GST %</th>
                <th class="text-caption font-weight-medium pa-3">AMOUNT</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in purchaseBill.items" :key="item.id" class="border-b">
                <td class="pa-3">
                  <div class="text-body-2 font-weight-medium">{{ item.item_name }}</div>
                  <div class="text-caption text-grey" v-if="item.item_type">
                    Type: {{ item.item_type }}
                  </div>
                </td>
                <td class="pa-3 text-body-2">{{ item.sku || '-' }}</td>
                <td class="pa-3 text-body-2">{{ item.hsn_sac || '-' }}</td>
                <td class="pa-3 text-body-2">{{ item.quantity }}</td>
                <td class="pa-3 text-body-2">{{ formatCurrency(item.rate) }}</td>
                <td class="pa-3 text-body-2">{{ formatCurrency(item.discount) }}</td>
                <td class="pa-3 text-body-2">{{ item.gst_percentage }}%</td>
                <td class="pa-3 text-body-2 font-weight-medium">{{ formatCurrency(item.amount) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>

      <!-- Financial Summary -->
      <VCard elevation="0" class="border mt-4">
        <VCardText class="pa-6">
          <div class="d-flex align-center mb-4">
            <VIcon icon="tabler-calculator" color="orange" class="me-2" size="20" />
            <span class="text-body-1 font-weight-medium">Financial Summary</span>
          </div>
          <VRow>
            <VCol cols="12" md="8">
              <div class="text-body-2 text-grey mb-2">Total Amount</div>
              <div class="text-h4 font-weight-bold">{{ formatCurrency(purchaseBill.total_amount) }}</div>
            </VCol>
            <VCol cols="12" md="4">
              <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-space-between">
                  <span class="text-body-2 text-grey">Subtotal:</span>
                  <span class="text-body-2">{{ formatCurrency(purchaseBill.sub_total) }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span class="text-body-2 text-grey">Tax Amount:</span>
                  <span class="text-body-2">{{ formatCurrency(purchaseBill.tax_amount) }}</span>
                </div>
                <div class="d-flex justify-space-between" v-if="purchaseBill.discount_amount">
                  <span class="text-body-2 text-grey">Discount:</span>
                  <span class="text-body-2 text-success">{{ formatCurrency(purchaseBill.discount_amount) }}</span>
                </div>
                <VDivider class="my-1" />
                <div class="d-flex justify-space-between">
                  <span class="text-body-1 font-weight-medium">Total:</span>
                  <span class="text-body-1 font-weight-bold">{{ formatCurrency(purchaseBill.total_amount) }}</span>
                </div>
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Bill Image Section -->
      <VCard elevation="0" class="border mt-4" v-if="purchaseBill.bill_image">
        <VCardText class="pa-6">
          <div class="d-flex align-center mb-4">
            <VIcon icon="tabler-photo" color="warning" class="me-2" size="20" />
            <span class="text-body-1 font-weight-medium">Bill Image</span>
          </div>
          <div class="d-flex align-center gap-4">
            <VImg :src="`/storage/${purchaseBill.bill_image}`" width="120" height="120"
              class="rounded cursor-pointer border" @click="openImagePreview" cover />
            <div>
              <div class="text-body-2 mb-2">Click image to view full size</div>
              <VBtn size="small" variant="outlined" @click="openImagePreview" prepend-icon="tabler-eye">
                View Full Image
              </VBtn>
            </div>
          </div>
        </VCardText>
      </VCard>
    </div>


    <div v-else class="text-center py-12">
      <VIcon icon="tabler-file-x" size="64" class="mb-4 text-medium-emphasis" />
      <div class="text-h6 mb-2">Purchase Bill Not Found</div>
      <div class="text-body-1 text-medium-emphasis mb-4">
        The requested purchase bill could not be found.
      </div>
      <VBtn variant="outlined" :to="{ name: 'account-pages-PurchaseBills' }">
        Back to Purchase Bills
      </VBtn>
    </div>

    <!-- Image Preview Dialog -->
    <VDialog v-model="imagePreviewDialog" max-width="800">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between">
          <span>Bill Image Preview</span>
          <VBtn icon="tabler-x" variant="text" @click="imagePreviewDialog = false" />
        </VCardTitle>
        <VCardText class="pa-0">
          <VImg v-if="purchaseBill?.bill_image" :src="`/storage/${purchaseBill.bill_image}`" class="w-100" contain />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn class="account_v_btn_outlined" @click="imagePreviewDialog = false">
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.account_label {
  color: rgba(var(--v-theme-on-surface), 0.87);
  font-size: 0.875rem;
  font-weight: 500;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
