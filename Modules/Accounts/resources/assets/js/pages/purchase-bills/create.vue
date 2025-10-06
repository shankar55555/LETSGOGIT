<script setup>
import { IconSettings } from '@tabler/icons-vue';
import { computed, onMounted, ref, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';

const router = useRouter()

// Vendor select and Add Vendor dialog
const vendorsList = ref([])
const selectedVendor = ref(null)
const vendorGSTIN = ref('')
const vendorState = ref('')
const placeOfSupply = ref('')
const billNumber = ref('')
const billDate = ref(new Date().toISOString().substr(0, 10))
const dueDate = ref(new Date().toISOString().substr(0, 10))
const addVendorDialog = ref(false)
const loading = ref(false)
const billImage = ref(null)
const billImagePreview = ref(null)
const imagePreviewModal = ref(false)
const paymentMode = ref('cash')
const discountAmount = ref(0)

// Payment modes
const paymentModes = [
  { title: 'Cash', value: 'cash' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Cheque', value: 'cheque' },
  { title: 'Credit Card', value: 'credit_card' },
  { title: 'UPI', value: 'upi' },
]

// Watch vendor selection to autofill GSTIN and state
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

// Purchase Mode Menu
const purchaseModes = [
  { label: 'Assets & Inventory', value: 'both' },
  { label: 'Assets Only', value: 'asset' },
  { label: 'Inventory Only', value: 'inventory' },
]
const selectedPurchaseMode = ref('both')
const purchaseModeMenu = ref(false)

// Tabs
const activeTab = ref('inventory')

// Table headers
const inventoryHeaders = [
  { title: 'Item', value: 'item', width: '250px' },
  { title: 'SKU', value: 'sku', width: '100px' },
  // { title: 'HSN/SAC', value: 'hsn', width: '100px' },
  { title: 'Qty', value: 'qty', width: '80px' },
  { title: 'Rate', value: 'rate', width: '100px' },
  { title: 'GST%', value: 'gst', width: '80px' },
  { title: 'Amount', value: 'amount', width: '100px' },
  { title: '', value: 'actions', width: '50px' },
]
const assetHeaders = [
  { title: 'Item', value: 'item', width: '300px' },
  { title: 'SKU', value: 'sku', width: '100px' },
  // { title: 'HSN/SAC', value: 'hsn', width: '100px' },
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
    account_id: '550e8400-e29b-41d4-a716-446655440001', // Default to Purchase A/c
    product_id: null,
    variant_id: null,
    sku: '',
    // hsn_sac: '',
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
    // hsn_sac: '',
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
const productVariants = ref({}) // Store variants for each product

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

  // Update GST amounts based on place of supply
  updateGstAmounts()
}

// Update GST amounts based on place of supply
function updateGstAmounts() {
  // Get user's state from localStorage or a global store
  const userState = localStorage.getItem('userState') || 'Maharashtra' // Default state

  // If vendor state matches user state, split GST into CGST and SGST
  // Otherwise, apply IGST
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

// Summary
const subtotal = computed(() => {
  let total = 0

  // Add inventory rows
  inventoryRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    total += (qty * rate) - discount
  })

  // Add asset rows
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

  // Add GST from inventory rows
  inventoryRows.value.forEach(row => {
    const qty = parseFloat(row.quantity) || 0
    const rate = parseFloat(row.rate) || 0
    const discount = parseFloat(row.discount) || 0
    const gst = parseFloat(row.gst_percentage) || 0
    total += ((qty * rate) - discount) * (gst / 100)
  })

  // Add GST from asset rows
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

// Notes
const notes = ref('')

// Accounts for inventory
const accountsList = ref([
  { title: 'Purchase A/c', value: '550e8400-e29b-41d4-a716-446655440001' },
  { title: 'Expense A/c', value: '550e8400-e29b-41d4-a716-446655440002' },
])

// Add Vendor form fields
const vendorFormFields = ref([
  { label: 'First Name', key: 'firstName', visible: true },
  { label: 'Last Name', key: 'lastName', visible: true },
  { label: 'Company Name', key: 'companyName', visible: true },
  { label: 'Email Address', key: 'email', visible: true },
  { label: 'Phone Number', key: 'phone', visible: true },
  { label: 'Street Address', key: 'streetAddress', visible: true },
  { label: 'City', key: 'city', visible: true },
  { label: 'State', key: 'state', visible: true },
  { label: 'ZIP Code', key: 'zipCode', visible: true },
  { label: 'GSTIN (Optional)', key: 'gstin', visible: true },
])
const isVendorFieldVisible = key => vendorFormFields.value.find(f => f.key === key)?.visible

// Add Vendor form model
const newVendor = ref({
  first_name: '',
  last_name: '',
  company_name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zip_code: '',
  gstin: ''
})

// States list
const statesList = ref([])

// Fetch vendors, products, and states from API
onMounted(async () => {
  try {
    loading.value = true
    await Promise.all([
      fetchVendors(),
      fetchProducts(),
      fetchStates()
    ])
    loading.value = false
  } catch (error) {
    console.error('Error fetching data:', error)
    toast.error('Failed to load data. Please refresh the page.')
    loading.value = false
  }
})

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
      id: product.id,
      title: product.name,
      value: product.id,
      variants: product.variants || []
    }))

    // Store variants for each product
    response.data.forEach(product => {
      if (product.variants && product.variants.length > 0) {
        productVariants.value[product.id] = product.variants.map(variant => ({
          id: variant.id,
          title: `${variant.sku} - ₹${variant.mrp}`,
          value: variant.id,
          sku: variant.sku,
          mrp: variant.mrp,
          stock_quantity: variant.stock_quantity
        }))
      }
    })
  } catch (error) {
    console.error('Error fetching products:', error)
    toast.error('Failed to load products')
  }
}

async function fetchStates() {
  try {
    const response = await $api('/dropdown-state-list')
    statesList.value = response.data.map(state => ({
      title: state.name,
      value: state.name,
      id: state.id
    }))
  } catch (error) {
    console.error('Error fetching states:', error)
    toast.error('Failed to load states')
  }
}

// Save vendor
async function saveVendor() {
  try {
    loading.value = true
    const response = await $api('/vendors', {
      method: 'POST',
      body: JSON.stringify(newVendor.value),
    })

    // Add new vendor to the list
    const vendor = response.data
    vendorsList.value.push({
      id: vendor.id,
      title: vendor.company_name || `${vendor.first_name} ${vendor.last_name}`,
      value: vendor.id,
      gstin: vendor.gstin,
      state: vendor.state
    })

    // Select the newly created vendor
    selectedVendor.value = vendor.id

    // Close dialog and reset form
    addVendorDialog.value = false
    newVendor.value = {
      first_name: '',
      last_name: '',
      company_name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      zip_code: '',
      gstin: ''
    }

    toast.success('Vendor created successfully')
    loading.value = false
  } catch (error) {
    console.error('Error creating vendor:', error)
    toast.error('Failed to create vendor')
    loading.value = false
  }
}

// Handle product selection
function handleProductSelection(index, productId) {
  const rows = activeTab.value === 'inventory' ? inventoryRows.value : assetRows.value
  const product = productsList.value.find(p => p.id === productId)

  if (product) {
    // Set the item name from the selected product
    rows[index].item_name = product.title

    // Clear previous variant selection
    rows[index].variant_id = null
    rows[index].sku = ''
    rows[index].rate = 0
    rows[index].gst_percentage = 18

    // If product has variants, user needs to select one
    // If no variants, use product defaults (legacy support)
    if (!productVariants.value[productId] || productVariants.value[productId].length === 0) {
      rows[index].sku = 'No variants available'
      rows[index].rate = 0
    }

    recalcAmounts()
  }
}

// Handle variant selection
function handleVariantSelection(index, variantId) {
  const rows = activeTab.value === 'inventory' ? inventoryRows.value : assetRows.value
  const productId = rows[index].product_id

  if (productId && productVariants.value[productId]) {
    const variant = productVariants.value[productId].find(v => v.id === variantId)

    if (variant) {
      rows[index].variant_id = variant.id
      rows[index].sku = variant.sku
      rows[index].rate = variant.mrp
      recalcAmounts()
    }
  }
}

// Get available variants for a product
function getProductVariants(productId) {
  return productVariants.value[productId] || []
}

// Handle bill image upload
function handleImageUpload(event) {
  const file = event.target.files[0]
  if (file) {
    billImage.value = file

    // Create preview
    const reader = new FileReader()
    reader.onload = e => {
      billImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

// Open image preview modal
function openImagePreview() {
  if (billImagePreview.value) {
    imagePreviewModal.value = true
  }
}

// Save purchase bill
async function savePurchaseBill() {
  if (!selectedVendor.value) {
    toast.error('Please select a vendor')
    return
  }

  if (!billNumber.value) {
    toast.error('Please enter a bill number')
    return
  }

  if (!billDate.value) {
    toast.error('Please select a bill date')
    return
  }

  if (!dueDate.value) {
    toast.error('Please select a due date')
    return
  }

  try {
    loading.value = true

    // Prepare items array
    let items = []

    if (selectedPurchaseMode.value === 'inventory' || selectedPurchaseMode.value === 'both') {
      // Filter out empty inventory rows
      const validInventoryRows = inventoryRows.value.filter(item =>
        item.item_name && item.item_name.trim() !== '' && item.quantity > 0
      )
      items = [...items, ...validInventoryRows.map(item => ({
        ...item,
        variant_id: item.variant_id || null
      }))]
    }

    if (selectedPurchaseMode.value === 'asset' || selectedPurchaseMode.value === 'both') {
      // Filter out empty asset rows
      const validAssetRows = assetRows.value.filter(item =>
        item.item_name && item.item_name.trim() !== '' && item.quantity > 0
      )
      items = [...items, ...validAssetRows.map(item => ({
        ...item,
        variant_id: item.variant_id || null
      }))]
    }

    // Create form data for file upload
    const formData = new FormData()
    formData.append('bill_number', billNumber.value)
    formData.append('bill_date', billDate.value)
    formData.append('due_date', dueDate.value)
    formData.append('vendor_id', selectedVendor.value)
    formData.append('vendor_state', vendorState.value)
    formData.append('purchase_mode', selectedPurchaseMode.value)
    formData.append('payment_mode', paymentMode.value)
    formData.append('notes', notes.value)
    formData.append('sub_total', subtotal.value)
    formData.append('discount_amount', discountAmount.value || 0)
    formData.append('cgst_amount', cgstAmount.value)
    formData.append('sgst_amount', sgstAmount.value)
    formData.append('igst_amount', igstAmount.value)
    formData.append('tax_amount', totalGST.value)
    formData.append('total_amount', totalAmount.value)

    // Add items as JSON
    formData.append('items', JSON.stringify(items))

    // Add bill image if exists
    if (billImage.value) {
      formData.append('bill_image', billImage.value)
    }

    const response = await $api('/v1/purchase-bills', {
      method: 'POST',
      body: formData
    })
    console.log(response);
    toast.success('Purchase bill created successfully')
    loading.value = false
    router.push('/accounts/purchase-bills')
  } catch (error) {
    console.error('Error creating purchase bill:', error)
    toast.error('Failed to create purchase bill')
    loading.value = false
  }
}
</script>

<template>
  <div>
    <VRow class="justify-center">
      <VCol cols="12">
        <VCard class="account_ui_vcard account_vcard_border" title="New Purchase Bill"
          subtitle="Enter the details from your vendor's bill.">
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
                      <span :class="selectedPurchaseMode === mode.value ? '' : 'field_list_dynamic_ml'">{{ mode.label
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
              <!-- <template v-slot:[`item.hsn`]="{ index }">
                <VTextField v-model="(activeTab === 'inventory' ? inventoryRows : assetRows)[index].hsn_sac"
                  placeholder="HSN Code" variant="outlined" class="accouting_field accouting_active_field"
                  :disabled="loading" />
              </template> -->
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
                </div>
                <VDivider class="my-2" />
                <div class="d-flex justify-space-between w-100 font-weight-bold"><span>Total Amount</span><span>₹{{
                  totalAmount.toFixed(2) }}</span></div>
              </VCol>
            </VRow>
            <VDivider class="my-4" />
            <VRow>
              <VCol cols="12" class="d-flex align-center justify-end">
                <VBtn class="account_v_btn_primary" @click="savePurchaseBill" :loading="loading" :disabled="loading">
                  <template #prepend>
                    <IconDeviceFloppy size="20" style="margin-inline-end: 6px;" />
                  </template>
                  Save Purchase Bill
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <!-- Add Vendor Dialog -->
    <v-dialog max-width="700" v-model="addVendorDialog">
      <div v-if="addVendorDialog" class="account_ui_vcard">
        <VCard title="Create a New Vendor" class="pa-2 account_vcard_border shadow-none"
          subtitle="Fill in the details below to add a new vendor to your records.">
          <template #append>
            <VBtn @click="addVendorDialog = false" variant="text" size="x-small" rounded=""
              class="account_vcard_close_btn" :disabled="loading">
              <IconX size="20" />
            </VBtn>
          </template>
          <VCardText>
            <VRow>
              <VCol v-if="isVendorFieldVisible('firstName')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">First Name</label>
                <VTextField v-model="newVendor.first_name" variant="outlined" density="compact" placeholder="John"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('lastName')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">Last Name</label>
                <VTextField v-model="newVendor.last_name" variant="outlined" density="compact" placeholder="Doe"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('companyName')" cols="12" lg="12" md="12">
                <label class="account_label mb-2">Company Name</label>
                <VTextField v-model="newVendor.company_name" variant="outlined" density="compact"
                  placeholder="Innovate Inc." class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('email')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">Email Address</label>
                <VTextField v-model="newVendor.email" variant="outlined" density="compact"
                  placeholder="john.doe@example.com" class="accouting_field accouting_active_field"
                  :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('phone')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">Phone Number</label>
                <VTextField v-model="newVendor.phone" variant="outlined" density="compact" placeholder="(123) 456-7890"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('streetAddress')" cols="12" lg="12" md="12">
                <label class="account_label mb-2">Street Address</label>
                <VTextField v-model="newVendor.address" variant="outlined" density="compact" placeholder="123 Main St"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('city')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">City</label>
                <VTextField v-model="newVendor.city" variant="outlined" density="compact" placeholder="Mumbai"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('state')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">State</label>
                <VSelect v-model="newVendor.state" :items="statesList" item-title="title" item-value="value"
                  variant="outlined" density="compact" placeholder="Select a state"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('zipCode')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">ZIP Code</label>
                <VTextField v-model="newVendor.zip_code" variant="outlined" density="compact" placeholder="400001"
                  class="accouting_field accouting_active_field" :disabled="loading" />
              </VCol>
              <VCol v-if="isVendorFieldVisible('gstin')" cols="12" lg="6" md="6">
                <label class="account_label mb-2">GSTIN (Optional)</label>
                <VTextField v-model="newVendor.gstin" variant="outlined" density="compact"
                  placeholder="15-digit GST Identification Number" class="accouting_field accouting_active_field"
                  :disabled="loading" />
              </VCol>
            </VRow>
          </VCardText>
          <VCardActions class="justify-end">
            <VBtn color="error" variant="text" @click="addVendorDialog = false" :disabled="loading">Cancel</VBtn>
            <VBtn color="success" class="account_v_btn_primary" @click="saveVendor" :loading="loading"
              :disabled="loading">
              <template #prepend>
                <IconDeviceFloppy size="20" style="margin-inline-end: 6px;" />
              </template>
              Save Vendor
            </VBtn>
          </VCardActions>
        </VCard>
      </div>
    </v-dialog>

    <!-- Bill Image Preview Modal -->
    <VDialog v-model="imagePreviewModal" max-width="800">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Bill Image Preview</span>
          <VBtn icon="mdi-close" variant="text" size="small" @click="imagePreviewModal = false" />
        </VCardTitle>
        <VCardText class="pa-0">
          <VImg v-if="billImagePreview" :src="billImagePreview" class="w-100" max-height="600" contain />
        </VCardText>
        <VCardActions class="justify-center">
          <VBtn color="primary" variant="outlined" @click="imagePreviewModal = false">
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
