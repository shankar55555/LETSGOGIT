<script setup>
import { computed, onMounted, ref, watchEffect } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';

const route = useRoute()
const router = useRouter()
const purchaseBillId = route.params.id

// Loading states
const loading = ref(false)
const saving = ref(false)
const addingVendor = ref(false)

// Dialog states
const addVendorDialog = ref(false)
const imagePreviewDialog = ref(false)
const purchaseModeMenu = ref(false)

// New vendor form
const newVendor = ref({
  name: '',
  email: '',
  phone: '',
  gstin: '',
  address: '',
  city: '',
  state: '',
  zip_code: ''
})

// Purchase bill data
const purchaseBill = ref(null)

// Form fields
const vendorsList = ref([])
const selectedVendor = ref(null)
const vendorGSTIN = ref('')
const vendorState = ref('')
const placeOfSupply = ref('')
const billNumber = ref('')
const billDate = ref('')
const dueDate = ref('')
const paymentMode = ref('cash')
const discountAmount = ref(0)
const notes = ref('')
const billImage = ref(null)
const billImagePreview = ref(null)

// Payment modes
const paymentModes = [
  { title: 'Cash', value: 'cash' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Cheque', value: 'cheque' },
  { title: 'Credit Card', value: 'credit_card' },
  { title: 'UPI', value: 'upi' },
]

// Purchase Mode
const purchaseModes = [
  { label: 'Assets & Inventory', value: 'both' },
  { label: 'Assets Only', value: 'asset' },
  { label: 'Inventory Only', value: 'inventory' },
]
const selectedPurchaseMode = ref('both')

// Tabs
const activeTab = ref('inventory')

// Table headers
const inventoryHeaders = [
  { title: 'Item', value: 'item', width: '250px' },
  { title: 'SKU', value: 'sku', width: '100px' },
  { title: 'Qty', value: 'qty', width: '80px' },
  { title: 'Rate', value: 'rate', width: '100px' },
  { title: 'GST%', value: 'gst', width: '80px' },
  { title: 'Amount', value: 'amount', width: '100px' },
  { title: '', value: 'actions', width: '50px' },
]

const assetHeaders = [
  { title: 'Item', value: 'item', width: '300px' },
  { title: 'SKU', value: 'sku', width: '100px' },
  { title: 'Qty', value: 'qty', width: '80px' },
  { title: 'Rate', value: 'rate', width: '100px' },
  { title: 'GST%', value: 'gst', width: '80px' },
  { title: 'Amount', value: 'amount', width: '100px' },
  { title: '', value: 'actions', width: '50px' },
]

// Table data
function defaultInventoryRow() {
  return {
    item_name: '',
    account_id: '550e8400-e29b-41d4-a716-446655440001',
    product_id: null,
    variant_id: null,
    sku: '',
    quantity: 1,
    rate: 0,
    discount: 0,
    gst_percentage: 18,
    amount: 0,
    item_type: 'inventory'
  }
}

function defaultAssetRow() {
  return {
    item_name: '',
    product_id: null,
    variant_id: null,
    sku: '',
    quantity: 1,
    rate: 0,
    discount: 0,
    gst_percentage: 18,
    amount: 0,
    item_type: 'asset'
  }
}

const inventoryRows = ref([defaultInventoryRow()])
const assetRows = ref([defaultAssetRow()])
const productsList = ref([])
const productVariants = ref({})
const statesList = ref([])

// Watch vendor selection
watchEffect(() => {
  const vendor = vendorsList.value.find(v => v.id === selectedVendor.value)
  if (vendor) {
    vendorGSTIN.value = vendor.gstin
    placeOfSupply.value = vendor.state
    vendorState.value = vendor.state
  } else {
    vendorGSTIN.value = ''
    placeOfSupply.value = ''
    vendorState.value = ''
  }
})

// Calculate amounts
function recalcAmounts() {
  const rows = activeTab.value === 'inventory' ? inventoryRows.value : assetRows.value
  rows.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    const gst = parseFloat(row.gst_percentage) || 0
    const base = qty * rate - discount
    row.amount = base * (1 + gst / 100)
  })
  updateGstAmounts()
}

// Update GST amounts
function updateGstAmounts() {
  const userState = localStorage.getItem('userState') || 'Maharashtra'
  if (placeOfSupply.value === userState) {
    cgstAmount.value = totalGST.value / 2
    sgstAmount.value = totalGST.value / 2
    igstAmount.value = 0
  } else {
    cgstAmount.value = 0
    sgstAmount.value = 0
    igstAmount.value = totalGST.value
  }
}

watchEffect(recalcAmounts)

// Summary calculations
const subtotal = computed(() => {
  let total = 0
  inventoryRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    total += (qty * rate) - discount
  })
  assetRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    total += (qty * rate) - discount
  })
  return total
})

const totalGST = computed(() => {
  let total = 0
  inventoryRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    const gst = parseFloat(row.gst_percentage) || 0
    total += ((qty * rate) - discount) * (gst / 100)
  })
  assetRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    const gst = parseFloat(row.gst_percentage) || 0
    total += ((qty * rate) - discount) * (gst / 100)
  })
  return total
})

const cgstAmount = ref(0)
const sgstAmount = ref(0)
const igstAmount = ref(0)

const totalAmount = computed(() => {
  return subtotal.value + totalGST.value - parseFloat(discountAmount.value || 0)
})

// Row management
function addRow() {
  if (activeTab.value === 'inventory') inventoryRows.value.push(defaultInventoryRow())
  else assetRows.value.push(defaultAssetRow())
}

function removeRow(index) {
  if (activeTab.value === 'inventory') {
    if (inventoryRows.value.length > 1) inventoryRows.value.splice(index, 1)
  } else {
    if (assetRows.value.length > 1) assetRows.value.splice(index, 1)
  }
  recalcAmounts()
}

// Product handling
function handleProductSelection(index, productId) {
  const product = productsList.value.find(p => p.value === productId)
  if (product) {
    const rows = activeTab.value === 'inventory' ? inventoryRows.value : assetRows.value
    rows[index].item_name = product.title
    rows[index].sku = product.sku || ''
    rows[index].rate = product.price || 0
  }
  recalcAmounts()
}

function getProductVariants(productId) {
  return productVariants.value[productId] || []
}

function handleVariantSelection(index, variantId) {
  // Handle variant selection logic
  recalcAmounts()
}

// API functions
async function fetchPurchaseBill() {
  try {
    loading.value = true
    const response = await $api(`/v1/purchase-bills/${purchaseBillId}`)
    purchaseBill.value = response.data

    // Populate form fields
    selectedVendor.value = purchaseBill.value.vendor_id
    billNumber.value = purchaseBill.value.bill_number
    billDate.value = purchaseBill.value.bill_date
    dueDate.value = purchaseBill.value.due_date
    paymentMode.value = purchaseBill.value.payment_mode || 'cash'
    selectedPurchaseMode.value = purchaseBill.value.purchase_mode || 'both'
    discountAmount.value = purchaseBill.value.discount_amount || 0
    notes.value = purchaseBill.value.notes || ''
    placeOfSupply.value = purchaseBill.value.vendor_state

    // Populate items
    if (purchaseBill.value.items && purchaseBill.value.items.length > 0) {
      const inventoryItems = purchaseBill.value.items.filter(item => item.item_type === 'inventory')
      const assetItems = purchaseBill.value.items.filter(item => item.item_type === 'asset')

      if (inventoryItems.length > 0) {
        inventoryRows.value = inventoryItems
      }
      if (assetItems.length > 0) {
        assetRows.value = assetItems
      }
    }

    loading.value = false
  } catch (error) {
    console.error('Error fetching purchase bill:', error)
    toast.error('Failed to load purchase bill')
    loading.value = false
  }
}

async function fetchVendors() {
  try {
    const response = await $api('/vendors')
    vendorsList.value = response.data.map(vendor => ({
      id: vendor.id,
      title: vendor.company_name || `${vendor.first_name} ${vendor.last_name}`,
      value: vendor.id,
      gstin: vendor.gstin,
      state: vendor.state
    }))
  } catch (error) {
    console.error('Error fetching vendors:', error)
    toast.error('Failed to load vendors')
  }
}

async function fetchProducts() {
  try {
    const response = await $api('/product')
    productsList.value = response.data.map(product => ({
      title: product.name,
      value: product.id,
      sku: product.sku,
      price: product.price
    }))
  } catch (error) {
    console.error('Error fetching products:', error)
    toast.error('Failed to load products')
  }
}

async function fetchStates() {
  try {
    const response = await $api('/dropdown-state-list')
    statesList.value = response.data.map(state => state.name)
  } catch (error) {
    console.error('Error fetching states:', error)
    toast.error('Failed to load states')
  }
}

// Save function
async function updatePurchaseBill() {
  try {
    saving.value = true

    // Prepare data
    const allItems = [...inventoryRows.value, ...assetRows.value].filter(item =>
      item.item_name && item.item_name.trim() !== ''
    )

    const formData = {
      vendor_id: selectedVendor.value,
      bill_number: billNumber.value,
      bill_date: billDate.value,
      due_date: dueDate.value,
      payment_mode: paymentMode.value,
      purchase_mode: selectedPurchaseMode.value,
      vendor_state: placeOfSupply.value,
      sub_total: subtotal.value,
      tax_amount: totalGST.value,
      discount_amount: parseFloat(discountAmount.value) || 0,
      total_amount: totalAmount.value,
      cgst_amount: cgstAmount.value,
      sgst_amount: sgstAmount.value,
      igst_amount: igstAmount.value,
      notes: notes.value,
      items: allItems
    }

    await $api(`/v1/purchase-bills/${purchaseBillId}`, {
      method: 'PUT',
      body: formData
    })

    toast.success('Purchase bill updated successfully!')
    router.push({ name: 'account-pages-PurchaseBills', params: { id: purchaseBillId } })

  } catch (error) {
    console.error('Error updating purchase bill:', error)
    toast.error('Failed to update purchase bill')
  } finally {
    saving.value = false
  }
}

// Image handling
function handleImageUpload(event) {
  const file = event.target.files?.[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      billImagePreview.value = e.target?.result
    }
    reader.readAsDataURL(file)
  }
}

function openImagePreview() {
  imagePreviewDialog.value = true
}

// Vendor management
async function saveVendor() {
  try {
    addingVendor.value = true
    
    const vendorData = {
      company_name: newVendor.value.name,
      email: newVendor.value.email,
      phone: newVendor.value.phone,
      gstin: newVendor.value.gstin,
      address: newVendor.value.address,
      city: newVendor.value.city,
      state: newVendor.value.state,
      zip_code: newVendor.value.zip_code
    }

    const response = await $api('/vendors', {
      method: 'POST',
      body: vendorData
    })

    // Add new vendor to list
    const newVendorItem = {
      id: response.data.id,
      title: response.data.company_name,
      value: response.data.id,
      gstin: response.data.gstin,
      state: response.data.state
    }
    vendorsList.value.push(newVendorItem)
    selectedVendor.value = response.data.id

    // Reset form
    newVendor.value = {
      name: '',
      email: '',
      phone: '',
      gstin: '',
      address: '',
      city: '',
      state: '',
      zip_code: ''
    }
    
    addVendorDialog.value = false
    toast.success('Vendor added successfully!')
  } catch (error) {
    console.error('Error saving vendor:', error)
    toast.error('Failed to save vendor')
  } finally {
    addingVendor.value = false
  }
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR'
  }).format(amount || 0)
}

// Initialize
onMounted(async () => {
  try {
    await Promise.all([
      fetchPurchaseBill(),
      fetchVendors(),
      fetchProducts(),
      fetchStates()
    ])
  } catch (error) {
    console.error('Error initializing:', error)
  }
})
</script>

<template>
  <div>
    <VRow class="justify-center">
      <VCol cols="12">
        <VCard class="account_ui_vcard account_vcard_border" title="Edit Purchase Bill"
          subtitle="Update the purchase bill details.">
          <template #append>
            <VMenu v-model="purchaseModeMenu" location="bottom end" offset-y transition="slide-y-transition"
              :close-on-content-click="false">
              <template #activator="{ props }">
                <VBtn v-bind="props" variant="text" size="x-small" rounded="">
                  <IconSettings size="20" />
                </VBtn>
              </template>
              <VCard class="account_vcard_menu account_vcard_border">
                <div class="account_vcard_menu_hdng">Purchase Mode</div>
                <VDivider class="my-1 mt-0" />
                <div class="account_vcard_menu_items py-1">
                  <div v-for="mode in purchaseModes" :key="mode.value" class="account_vcard_menu_item"
                    @click="selectedPurchaseMode = mode.value">
                    <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                      <IconCheck v-if="selectedPurchaseMode === mode.value" size="16" />
                      <span :class="selectedPurchaseMode === mode.value ? '' : 'field_list_dynamic_ml'">{{
                        mode.label
                        }}</span>
                    </div>
                  </div>
                </div>
              </VCard>
            </VMenu>
          </template>
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Vendor*</label>
                <VAutocomplete v-model="selectedVendor" :items="vendorsList" placeholder="Select a vendor"
                  variant="outlined" class="accouting_field accouting_active_field" :loading="loading"
                  :disabled="loading" :error="!selectedVendor && loading === false">
                  <template #append>
                    <VBtn class="account_v_btn_outlined" @click="addVendorDialog = true" rounded="2"
                      :disabled="loading">
                      <IconCirclePlus size="20" />
                    </VBtn>
                  </template>
                </VAutocomplete>
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Bill Number*</label>
                <VTextField v-model="billNumber" variant="outlined" class="accouting_field accouting_active_field"
                  :disabled="loading" :error="!billNumber && loading === false" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Vendor GSTIN</label>
                <VTextField v-model="vendorGSTIN" variant="outlined" readonly placeholder="15-digit GSTIN"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Bill Date*</label>
                <VTextField v-model="billDate" type="date" variant="outlined"
                  class="accouting_field accouting_active_field" :disabled="loading"
                  :error="!billDate && loading === false" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Place of Supply (Vendor's State)*</label>
                <VAutocomplete :items="statesList" v-model="placeOfSupply" variant="outlined" placeholder="Select state"
                  class="accouting_field accouting_active_field" :disabled="loading"
                  :error="!placeOfSupply && loading === false" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Due Date*</label>
                <VTextField v-model="dueDate" type="date" variant="outlined"
                  class="accouting_field accouting_active_field" :disabled="loading"
                  :error="!dueDate && loading === false" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Payment Mode</label>
                <VSelect v-model="paymentMode" :items="paymentModes" item-title="title" item-value="value"
                  variant="outlined" class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol cols="12" md="6">
                <label class="account_label mb-2">Bill Image</label>
                <VFileInput v-model="billImage" accept="image/*" variant="outlined"
                  class="accouting_field accouting_active_field" :disabled="loading" @change="handleImageUpload"
                  prepend-icon="mdi-camera" />
                <!-- Image Preview Thumbnail -->
                <div v-if="billImagePreview" class="mt-2">
                  <div class="d-flex align-center gap-2">
                    <VImg :src="billImagePreview" width="80" height="80" class="rounded cursor-pointer border"
                      @click="openImagePreview" cover />
                    <div class="text-caption text-medium-emphasis">
                      Click to view full size
                    </div>
                  </div>
                </div>
              </VCol>
            </VRow>
            <VDivider class="my-4" />
            <div v-if="selectedPurchaseMode === 'both'" class="d-flex gap-2 mb-2">
              <VBtn :class="activeTab === 'inventory' ? 'account_v_btn_primary' : 'account_v_btn_outlined'"
                @click="activeTab = 'inventory'" :disabled="loading">Inventory Purchase</VBtn>
              <VBtn :class="activeTab === 'asset' ? 'account_v_btn_primary' : 'account_v_btn_outlined'"
                @click="activeTab = 'asset'" :disabled="loading">Asset Purchase</VBtn>
            </div>
            <VDataTable
              v-if="selectedPurchaseMode === 'both' ? true : selectedPurchaseMode === 'asset' ? activeTab = 'asset' : activeTab = 'inventory'"
              :headers="selectedPurchaseMode === 'both' ? (activeTab === 'inventory' ? inventoryHeaders : assetHeaders) : selectedPurchaseMode === 'asset' ? assetHeaders : inventoryHeaders"
              :items="selectedPurchaseMode === 'both' ? (activeTab === 'inventory' ? inventoryRows : assetRows) : selectedPurchaseMode === 'asset' ? assetRows : inventoryRows"
              class="account_dynamic_table account_invoice_table" hide-default-footer>

              <template v-slot:[`item.item`]="{ index }">
                <VAutocomplete v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].product_id"
                  :items="productsList" placeholder="Select product" variant="outlined"
                  class="accouting_field accouting_active_field" :disabled="loading"
                  @update:model-value="handleProductSelection(index, (activeTab === 'inventory' ? inventoryRows : assetRows)[index].product_id)">
                  <template #no-data>
                    <div class="pa-2">No products found</div>
                  </template>
                </VAutocomplete>
              </template>
              <template v-slot:[`item.sku`]="{ index }">
                <div
                  v-if="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].product_id && getProductVariants((activeTab === 'inventory' ? inventoryRows : assetRows)[index].product_id).length > 0">
                  <VAutocomplete v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].variant_id"
                    :items="getProductVariants((activeTab === 'inventory' ? inventoryRows : assetRows)[index].product_id)"
                    placeholder="Select variant" variant="outlined" class="accouting_field accouting_active_field"
                    :disabled="loading"
                    @update:model-value="handleVariantSelection(index, (activeTab === 'inventory' ? inventoryRows : assetRows)[index].variant_id)">
                    <template #no-data>
                      <div class="pa-2">No variants available</div>
                    </template>
                  </VAutocomplete>
                </div>
                <VTextField v-else v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].sku"
                  placeholder="SKU" variant="outlined" class="accouting_field accouting_active_field"
                  :disabled="loading" readonly />
              </template>
              <template v-slot:[`item.qty`]="{ index }">
                <VTextField v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].quantity"
                  type="number" variant="outlined" class="accouting_field accouting_active_field" :disabled="loading"
                  @input="recalcAmounts" />
              </template>
              <template v-slot:[`item.rate`]="{ index }">
                <VTextField v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].rate" type="number"
                  variant="outlined" class="accouting_field accouting_active_field" :disabled="loading"
                  @input="recalcAmounts" />
              </template>
              <template v-slot:[`item.discount`]="{ index }">
                <VTextField v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].discount"
                  type="number" variant="outlined" class="accouting_field accouting_active_field" :disabled="loading"
                  @input="recalcAmounts" />
              </template>
              <template v-slot:[`item.gst`]="{ index }">
                <VSelect v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].gst_percentage"
                  :items="[0, 5, 12, 18, 28]" variant="outlined" class="accouting_field accouting_active_field"
                  :disabled="loading" @update:model-value="recalcAmounts" />
              </template>
              <template v-slot:[`item.amount`]="{ index }">
                <span>₹{{ (activeTab === 'inventory' ? inventoryRows : assetRows)[index].amount.toFixed(2) }}</span>
              </template>
              <template v-slot:[`item.actions`]="{ index }">
                <IconTrash class="text-error cursor-pointer table_row_icon" :class="{
                  'opacity-50': (activeTab === 'inventory' ? inventoryRows : assetRows).length === 1,
                  'cursor-not-allowed': (activeTab === 'inventory' ? inventoryRows : assetRows).length === 1
                }" :disabled="(activeTab === 'inventory' ? inventoryRows : assetRows).length === 1 || loading"
                  @click="removeRow(index)" size="20" />
              </template>
            </VDataTable>
            <VBtn class="account_v_btn_outlined mt-3" variant="text" @click="addRow" :disabled="loading">
              <template #prepend>
                <IconCirclePlus size="20" style="margin-inline-end: 6px;" />
              </template>
              Add Item
            </VBtn>
            <VDivider class="my-4" />
            <VRow>
              <VCol cols="12" md="6">
                <VTextarea v-model="notes" placeholder="Notes" variant="outlined" class="accounting_v_textarea"
                  :disabled="loading" />
              </VCol>
              <VCol cols="12" md="6">
                <div class="d-flex flex-column align-end">
                  <div class="d-flex justify-space-between w-100 mb-1"><span>Subtotal</span><span>₹{{
                    subtotal.toFixed(2)
                      }}</span></div>
                  <div class="d-flex justify-space-between w-100 mb-1">
                    <span>Discount</span>
                    <div class="d-flex align-center gap-2">
                      <VTextField v-model="discountAmount" type="number" variant="outlined" density="compact"
                        style="inline-size: 100px;" :disabled="loading" @input="recalcAmounts" />
                      <span>₹</span>
                    </div>
                  </div>
                  <div class="d-flex justify-space-between w-100 mb-1"><span>CGST</span><span>₹{{ cgstAmount.toFixed(2)
                      }}</span></div>
                  <div class="d-flex justify-space-between w-100 mb-1"><span>SGST</span><span>₹{{ sgstAmount.toFixed(2)
                      }}</span></div>
                  <div class="d-flex justify-space-between w-100 mb-1"><span>IGST</span><span>₹{{ igstAmount.toFixed(2)
                      }}</span></div>
                  <VDivider class="my-2" />
                  <div class="d-flex justify-space-between w-100 text-h6 font-weight-bold"><span>Total</span><span>₹{{
                    totalAmount.toFixed(2) }}</span></div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
          <VCardActions class="justify-end">
            <VBtn color="error" variant="text" @click="$router.push('/accounts/purchase-bills')" :disabled="loading">
              Cancel
            </VBtn>
            <VBtn color="success" class="account_v_btn_primary" @click="updatePurchaseBill" :loading="loading"
              :disabled="loading">
              <template #prepend>
                <IconDeviceFloppy size="20" style="margin-inline-end: 6px;" />
              </template>
              Update Purchase Bill
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Add Vendor Dialog -->
    <VDialog v-model="addVendorDialog" max-width="600px" persistent>
      <VCard class="account_ui_vcard account_vcard_border">
        <VCardTitle class="d-flex align-center justify-space-between">
          <span>Add New Vendor</span>
          <VBtn icon variant="text" @click="addVendorDialog = false" :disabled="addingVendor">
            <IconX size="20" />
          </VBtn>
        </VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <label class="account_label mb-2">Vendor Name*</label>
              <VTextField v-model="newVendor.name" variant="outlined" class="accouting_field accouting_active_field"
                :disabled="addingVendor" :error="!newVendor.name && addingVendor === false" />
            </VCol>
            <VCol cols="12" md="6">
              <label class="account_label mb-2">Email</label>
              <VTextField v-model="newVendor.email" type="email" variant="outlined"
                class="accouting_field accouting_active_field" :disabled="addingVendor" />
            </VCol>
            <VCol cols="12" md="6">
              <label class="account_label mb-2">Phone</label>
              <VTextField v-model="newVendor.phone" variant="outlined" class="accouting_field accouting_active_field"
                :disabled="addingVendor" />
            </VCol>
            <VCol cols="12" md="6">
              <label class="account_label mb-2">GSTIN</label>
              <VTextField v-model="newVendor.gstin" variant="outlined" placeholder="15-digit GSTIN"
                class="accouting_field accouting_active_field" :disabled="addingVendor" />
            </VCol>
            <VCol cols="12">
              <label class="account_label mb-2">Address</label>
              <VTextarea v-model="newVendor.address" variant="outlined" class="accounting_v_textarea"
                :disabled="addingVendor" />
            </VCol>
            <VCol cols="12" md="4">
              <label class="account_label mb-2">City</label>
              <VTextField v-model="newVendor.city" variant="outlined" class="accouting_field accouting_active_field"
                :disabled="addingVendor" />
            </VCol>
            <VCol cols="12" md="4">
              <label class="account_label mb-2">State</label>
              <VAutocomplete v-model="newVendor.state" :items="statesList" variant="outlined"
                placeholder="Select state" class="accouting_field accouting_active_field" :disabled="addingVendor" />
            </VCol>
            <VCol cols="12" md="4">
              <label class="account_label mb-2">Zip Code</label>
              <VTextField v-model="newVendor.zip_code" variant="outlined" class="accouting_field accouting_active_field"
                :disabled="addingVendor" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn color="error" variant="text" @click="addVendorDialog = false" :disabled="addingVendor">
            Cancel
          </VBtn>
          <VBtn color="success" class="account_v_btn_primary" @click="saveVendor" :loading="addingVendor"
            :disabled="addingVendor">
            <template #prepend>
              <IconDeviceFloppy size="20" style="margin-inline-end: 6px;" />
            </template>
            Save Vendor
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Bill Image Preview Modal -->
    <VDialog v-model="imagePreviewDialog" max-width="800px">
      <VCard class="account_ui_vcard account_vcard_border">
        <VCardTitle class="d-flex align-center justify-space-between">
          <span>Bill Image Preview</span>
          <VBtn icon variant="text" @click="imagePreviewDialog = false">
            <IconX size="20" />
          </VBtn>
        </VCardTitle>
        <VCardText class="pa-0">
          <VImg v-if="billImagePreview" :src="billImagePreview" class="w-100" contain />
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn color="primary" variant="text" @click="imagePreviewDialog = false">
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.border {
  border: 1px solid rgb(var(--v-theme-surface-variant));
}
</style>
