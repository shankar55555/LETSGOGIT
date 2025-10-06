<script setup>
import { useFetchStatusList } from "@/utils/common"
import { v4 as uuidv4 } from 'uuid'
import { ref, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { toast } from 'vue3-toastify'
import { VForm } from 'vuetify/components'
const router = useRouter()
const route = useRoute()
const refForm = ref()
const valid = ref(true)
const isLoading = ref(false)
let isSubmitting = false
const showLeadClientFields = computed(() => !(id.value && type.value))
const isClientType = computed(() => type.value === QUOTATION_CLIENT)
const isLeadType = computed(() => type.value === QUOTATION_LEAD)
const type = ref(route.query.type || '')
const id = ref(route.query.id || null)
const clientName = ref('')
const leadName = ref('')

const record = ref({
  title: '',
  description: '',
  status: 'draft',
  items: [],
  client_id: '',
  contract_id: '',
})
// Generate a new empty item
const newItem = () => ({
  item_id: uuidv4(),
  name: '',
  description: '',
  quantity: 1,
  unit_price: 0,
  tax_rate: 0,
  tax_amount: 0,
  discount_rate: 0,
  discount_amount: 0,
  subtotal: 0,
  total: 0,
  attributes: [],
})
// Add new item row
const addItem = () => {
  record.value.items.push(newItem())
}
// Remove item by index
const removeItem = index => {
  record.value.items.splice(index, 1)
}
// Validate each item before submit
const validateItems = () => {
  for (const item of record.value.items) {
    if (!item.name || item.quantity <= 0 || item.unit_price <= 0) {
      toast.error('Each item must have Name, Quantity > 0, and Unit Price > 0.')
      return false
    }
  }
  return true
}
// Calculate dynamic fields for item
const calculateItemValues = item => {
  const quantity = parseFloat(item.quantity || 0)
  const unitPrice = parseFloat(item.unit_price || 0)
  const taxRate = parseFloat(item.tax_rate || 0)
  const discountRate = parseFloat(item.discount_rate || 0)
  const subtotal = quantity * unitPrice
  const taxAmount = (subtotal * taxRate) / 100
  const discountAmount = (subtotal * discountRate) / 100
  const total = subtotal + taxAmount - discountAmount
  item.subtotal = parseFloat(subtotal.toFixed(2))
  item.tax_amount = parseFloat(taxAmount.toFixed(2))
  item.discount_amount = parseFloat(discountAmount.toFixed(2))
  item.total = parseFloat(total.toFixed(2))
}
// Watch each item for real-time calculation
watch(
  () => record.value.items,
  items => {
    for (const item of items) {
      watchEffect(() => calculateItemValues(item))
    }
  },
  { deep: true }
)
// Submit form
const onSubmit = async () => {
  if (isSubmitting) return
  isSubmitting = true
  const { valid: isValid } = await refForm.value.validate()
  if (!isValid || !validateItems()) {
    isSubmitting = false
    return
  }
  try {
    isLoading.value = true
    const res = await $api('/invoices', {
      method: 'POST',
      body: JSON.stringify(record.value),
    })
    if (res?.data) {
      toast.success(res?.data?.message || 'Invoice created successfully!')
      // ✅ Redirect to invoice list
      router.push({ name: 'invoice-list' })
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'An error occurred while saving.')
  } finally {
    isSubmitting = false
    isLoading.value = false
  }
}
const loadingAttributes = ref(false)
const attributeItems = ref([])
const fetchAttributes = async (search = '') => {
  loadingAttributes.value = true
  try {
    const { data } = await $api('/products', {
      params: { search },
    })
    attributeItems.value = data // adapt if your API response is shaped differently
  } catch (e) {
    console.error('Failed to load attributeItems', e)
  } finally {
    loadingAttributes.value = false
  }
}
const onProductSelected = (product, item) => {
  if (!product) return
  // Set name and price
  item.name = product.name
  item.unit_price = parseFloat(product.price)
  // Set custom fields (attributes)
  item.attributes = product.attributes.map((val) => {
    return {
      key: val.key,
      value: val.value,
    }
  });
}
// Remove item attribute by index
const removeAttribute = (itemIndex, attributeIndex) => {
  record.value.items[itemIndex].attributes.splice(attributeIndex, 1)
}
const addAttribute = (itemIndex) => {
  record.value.items[itemIndex].attributes.push({
    key: '',
    value: '',
  })
}
// Add function to fetch client details
const fetchClientDetails = async () => {
  if (id.value && type.value === QUOTATION_CLIENT) {
    try {
      const { data } = await $api(`/clients/${id.value}`)
      clientName.value = data.name
      record.value.client_id = id.value
    } catch (error) {
      console.error('Failed to fetch client data:', error)
      toast.error(error?.response?.data?.message || 'Failed to load client details.')
    }
  }
}
// Add function to fetch lead details
const fetchLeadDetails = async () => {
  if (id.value && type.value === QUOTATION_LEAD) {
    try {
      const { data } = await $api(`/leads/${id.value}`)
      leadName.value = data.name
      record.value.lead_id = id.value
    } catch (error) {
      console.error('Failed to fetch lead data:', error)
      toast.error(error?.response?.data?.message || 'Failed to load lead details.')
    }
  }
}

const { statusList, fetchStatusList } = useFetchStatusList();

// Call fetchClientDetails when component mounts
onMounted(async () => {
  fetchStatusList(MODULE_INVOICE);
  if (id.value) {
    if (type.value === QUOTATION_CLIENT) {
      await fetchClientDetails()
    } else if (type.value === QUOTATION_LEAD) {
      await fetchLeadDetails()
    } else {
      try {
        const { data } = await $api(`/leads/${id.value}`)
        InfoData.value = data
      } catch (error) {
        console.error('Failed to fetch lead data:', error)
        toast.error(error?.response?.data?.message || 'Failed to load lead details.')
      }
    }
  }
})
const client_list = ref([]);
const optionClientList = async () => {
  const res = await $api('/option-client-list');
  client_list.value = res.data;
};
optionClientList();
</script>
<template>
  <VCard flat>
    <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
      <VCardText style="block-size: calc(100vh - 5rem);">
        <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" class="mt-4">
              <VRow>
                <VCol cols="12">
                  <strong class="text-primary">Basic</strong>
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="record.title" :rules="[requiredValidator]" label="Title" />
                </VCol>
                <VCol cols="12" md="4" v-show="false">
                  <AppSelect v-model="record.status" :rules="[requiredValidator]" label="Status*"
                    item-title="status_text" item-value="slug" :items="statusList" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppSelect v-model="record.client_id" label="Client" item-title="name" placeholder="Select Clients"
                    item-value="id" :items="client_list" v-if="showLeadClientFields && !isClientType" clearable />
                </VCol>
                <!-- <VCol cols="12" md="6">
                  <AppSelect v-model="record.contract_id" label="Contract" :items="[]"  v-if="showLeadClientFields && !isLeadType"/>
                </VCol> -->
                <VCol cols="12" md="6" v-if="isLeadType">
                  <AppTextField v-model="leadName" label="Lead Name" readonly />
                  <input type="hidden" v-model="record.lead_id" />
                </VCol>
                <VCol cols="12" md="6" v-if="isClientType">
                  <AppTextField v-model="clientName" label="Client Name" readonly />
                  <input type="hidden" v-model="record.client_id" />
                </VCol>
                <VCol cols="12">
                  <AppTextarea v-model="record.description" label="Description" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" v-if="record.items.length">
              <strong class="text-primary">Items</strong>
            </VCol>
            <VCol cols="12" v-for="(item, index) in record.items" :key="item.item_id">
              <VRow class="border rounded pa-3 mb-3">
                <VCol cols="12" md="12">
                  <AppAutocomplete label="Product/Service" :items="attributeItems" item-title="name"
                    :loading="loadingAttributes" :searchable="true" @update:search="fetchAttributes" return-object
                    v-model="item.product" @update:modelValue="val => onProductSelected(val, item)"
                    placeholder="Search Product Service " />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.name" label="Name*" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.quantity" label="Quantity*" type="number" min="1" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.unit_price" label="Unit Price*" type="number" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.tax_rate" label="GST Rate (%)" type="number" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.discount_rate" label="Discount Rate (%)" type="number" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.subtotal" label="Subtotal" type="number" readonly />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="item.total" label="Total" type="number" readonly />
                </VCol>
                <VCol cols="12" md="12">
                  <AppTextarea v-model="item.description" label="Description" row="3" />
                </VCol>
                <VCol cols="12">
                  <VRow align="center" class="mb-2">
                    <VCol cols="6">
                      <strong class="text-primary">Attributes</strong>
                    </VCol>
                    <VCol cols="6" class="d-flex justify-end">
                      <VBtn size="small" variant="tonal" color="primary" prepend-icon="tabler-plus"
                        @click="addAttribute(index)">
                        Add Attribute
                      </VBtn>
                    </VCol>
                  </VRow>
                </VCol>
                <VCol cols="12" md="12" v-for="(attribute, i) in item.attributes" :key="i">
                  <VRow>
                    <VCol cols="12" lg="6" md="6">
                      <AppTextField v-model="attribute.key" :label="`${i + 1}.Attribute Key`" />
                    </VCol>
                    <VCol cols="12" lg="6" md="6">
                      <AppTextField v-model="attribute.value" :label="`Attribute Value`">
                        <template #append>
                          <VBtn icon="tabler-trash" size="small" color="error" @click="removeAttribute(index, i)" />
                        </template>
                      </AppTextField>
                    </VCol>
                  </VRow>
                </VCol>
                <VCol cols="12" class="d-flex justify-end">
                  <VBtn color="error" @click="removeItem(index)" variant="tonal" prepend-icon="tabler-trash">
                    Delete Item
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" class="d-flex justify-end">
              <VBtn color="primary" @click="addItem" prepend-icon="tabler-plus">
                Add Item
              </VBtn>
            </VCol>
            <VCol cols="12" class="d-flex gap-4 justify-start pt-6 pb-10">
              <VBtn type="submit" color="primary" :loading="isLoading">
                Add
              </VBtn>
              <VBtn color="error" variant="tonal" :to="{ name: 'invoice-list' }">
                Discard
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </PerfectScrollbar>
  </VCard>
</template>
