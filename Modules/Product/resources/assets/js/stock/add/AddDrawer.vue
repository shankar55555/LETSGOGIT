<script setup>
import { v4 as uuidv4 } from 'uuid'
import { nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { toast } from 'vue3-toastify'
import { VForm } from 'vuetify/components'

const router = useRouter();
const cityList = ref([]);
const loadingCities = ref(false)
const countryList = ref([])
const stateList = ref([])
const isCreateCityDialog = ref(false)
const citySearchText = ref('')
const refForm = ref(null);
const valid = ref(true);
const previewImage = ref(null);
const companyLogFile = ref(null);

const isLoading = ref(false);
const newCity = ref({
  name: '',
  country_id: null,
  state_id: null,
  latitude: '0',
  longitude: '0',
  city_type: 'no',
})
let isSubmitting = ref(false);

const record = ref({
  name: "",
  price: null,
  site_inspection_checklist: [
    "Verify site address and location details",
    "Check access points to the installation area",
    "Confirm availability of power/water (if needed)",
    "Inspect for any obstructions or hazards"
  ],
  site_installation_checklist: [
    "Unpack and inspect the product for damage",
    "Confirm required tools and materials are available",
    "Install product as per manufacturer's guidelines",
    "Secure all fixtures and fittings",
  ],
  attributes: [],
});

const fetchCityList = async (search = '') => {
  loadingCities.value = true
  try {
    const res = await $api('/city-list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ search }),
    })
    cityList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load cities')
  } finally {
    loadingCities.value = false
  }
}

const fetchCityById = async (id) => {
  try {
    const res = await $api('/city-list/' + id)
    cityList.value = res.data ?? []
  } catch (e) {
    // toast.error('Failed to load city detail')
  } finally {
    lead.value.city_id = id;
  }
}

const handleCitySearchUpdate = (value) => {
  citySearchText.value = value || ''
  fetchCityList(value || '')
}

const openCreateCityDialog = async () => {
  newCity.value = {
    name: citySearchText.value || '',
    country_id: null,
    state_id: null,
    latitude: '0',
    longitude: '0',
    city_type: 'no',
  }
  await fetchDropdownCountries()
  stateList.value = []
  isCreateCityDialog.value = true
}

const fetchDropdownCountries = async () => {
  try {
    const res = await $api('/dropdown-country-list')
    countryList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load countries')
  }
}

const fetchDropdownStates = async (countryId) => {
  if (!countryId) { stateList.value = []; return }
  try {
    const res = await $api(`/dropdown-state-list`, { params: { country_id: countryId } })
    stateList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load states')
  }
}

const createCity = async () => {
  if (!newCity.value.name || !newCity.value.country_id || !newCity.value.state_id) {
    toast.error('Please fill city name, country and state')
    return
  }
  try {
    loadingCities.value = true
    const res = await $api('/city-create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newCity.value),
    })
    const created = res.data
    if (created) {
      // Add to list and select
      const already = cityList.value.find(c => c.id === created.id)
      if (!already) cityList.value.unshift(created)
      lead.value.city_id = created.id
      toast.success(res?.message || 'City created successfully')
      isCreateCityDialog.value = false
    }
  } catch (e) {
    toast.error(e?._data?.message || 'Failed to create city')
  } finally {
    loadingCities.value = false
  }
}
// Attribute types for the dropdown
const attributeTypes = ref([
  { title: "Text", value: "text" },
  { title: "Number", value: "number" },
  { title: "Date", value: "date" },
]);

// Generate a new empty attribute
const newAttribute = () => ({
  id: uuidv4(),
  key: "",
  type: "text",
  value: "",
});


const addAttribute = () => {
  const attr = newAttribute()
  record.value.attributes.push(attr)

  // Validate after DOM update
  nextTick(() => {
    validateAttribute(attr)
  })
}

const removeAttribute = async index => {
  record.value.attributes.splice(index, 1)
  await nextTick()
  refForm.value?.resetValidation()
}

// Validate single attribute
const validateAttribute = (attr) => {
  attr.isValid = true
  attr.errorMessage = ''

  // Required check
  if (!attr.key?.trim()) {
    attr.isValid = false
    attr.errorMessage = 'Attribute key is required'
    return false
  }

  // Uniqueness check
  const duplicateCount = record.value.attributes.filter(a =>
    a.id !== attr.id && a.key === attr.key
  ).length

  if (duplicateCount > 0) {
    attr.isValid = false
    attr.errorMessage = 'Attribute key must be unique'
    return false
  }

  // Type-specific validation
  if (attr.type === 'number' && attr.value && isNaN(attr.value)) {
    attr.isValid = false
    attr.errorMessage = 'Must be a valid number'
    return false
  }

  return true
}

// Validate all attributes
const validateAllAttributes = () => {
  let allValid = true

  record.value.attributes.forEach(attr => {
    if (!validateAttribute(attr)) {
      toast.error(`Attribute "${attr.key || 'Untitled'}": ${attr.errorMessage}`)
      allValid = false
    }
  })

  return allValid
}

const onSubmit = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true

  // Reset validation states
  record.value.attributes.forEach(attr => {
    attr.isValid = null
    attr.errorMessage = ''
  })

  // Validate Vuetify form
  const { valid: formValid } = await refForm.value.validate()

  // Validate attributes
  const attributesValid = validateAllAttributes()

  if (!formValid || !attributesValid) {
    isSubmitting.value = false
    return
  }

  try {
    isLoading.value = true
    const payload = {
      ...record.value,
      attributes: record.value.attributes.map(({ id, isValid, errorMessage, ...rest }) => rest)
    }

    const res = await $api('/product', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Created successfully!')
      router.push({ name: 'product-service-list' })
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'An error occurred')
  } finally {
    isSubmitting.value = false
    isLoading.value = false
  }
}

function removeInspectionTag(index) {
  record.value.site_inspection_checklist.splice(index, 1);
}

function removeInstallationTag(index) {
  record.value.site_installation_checklist.splice(index, 1);
}

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
    companyLogFile.value = file;
  }
};

const removeImage = () => {
  companyLogFile.value = null
  previewImage.value = null;
  selectedFile.value = null;
};

onMounted(() => {
  fetchCityList()

})
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
                  <strong class="text-primary">Basic Information</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.name" label="Name*" :rules="[requiredValidator]"
                    placeholder="Enter product name" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.sku" label="SKU" min="0" step="0.01" placeholder="Stock Keeping Unit" />
                </VCol>

                <VCol cols="6">
                  <AppAutocomplete v-model="record.category" label="Category*"
                    :items="['Hoodie', 'Jacket', 'Sweater', 'Accessories']" :rules="[requiredValidator]" />
                </VCol>

                <VCol cols="6">
                  <AppAutocomplete v-model="record.collection" label="Collection"
                    :items="['Winter 2025', 'Handwoven Line']" />
                </VCol>

                <VCol cols="12">
                  <strong class="text-primary">Pricing & Cost</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.price" label="Base Price (Retail)*" type="number" min="0"
                    placeholder="Enter price" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.price" label="Cost Price (for margin tracking)*" type="number" min="0"
                    step="0.01" placeholder="Enter price" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.gst" label="GST (%)" type="number" min="0"
                    :items="['5%', '10%', '18%', '12%']" placeholder="Enter price" />
                </VCol>

                <VCol cols="12">
                  <strong class="text-primary">Inventory & Stock</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.stock_quantity" label="Quantity*" type="number" min="0" step="1"
                    placeholder="Enter quantity" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.size_varient" label="Size Varient*" :items="['S', 'M', 'L', 'XL']"
                    placeholder="Enter size varient" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.color_varient" label="Color Variants*"
                    placeholder="Enter color varient" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.warehouse_location" label="Warehouse Location*"
                    placeholder="Enter warehouse location" :items="cityList" item-title="name" item-value="id"
                    :loading="loadingCities" :searchable="true" density="comfortable"
                    @update:search="handleCitySearchUpdate" clearable :rules="[requiredValidator]">
                    <template #no-data>
                      <div class="pa-4 text-center">
                        <VIcon icon="tabler-map-pin-plus" class="mb-2" />
                        <div>
                          No matching city found
                        </div>
                        <VBtn v-if="citySearchText" variant="outlined" size="small" class="mt-2"
                          @click="openCreateCityDialog">
                          Add "{{ citySearchText }}"
                        </VBtn>
                      </div>
                    </template>
                  </AppAutocomplete>
                </VCol>

                <VCol cols="12">
                  <strong class="text-primary">Product Details</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.MaterialFabric" label="Material / Fabric" item-title="name"
                    item-value="name"
                    :items="[{ name: '100% cotton' }, { name: 'wool blend' }, { name: 'handwoven wool' }, { name: '100% polyester' }]"
                    placeholder="Enter material / fabric" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.care_instruction" label="Care Instruction"
                    placeholder="Enter care instruction" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.season" label="Season"
                    :items="['Winter', 'Summer', 'Rainy', 'Spring']" placeholder="Season" />
                </VCol>

                <VCol cols="12">
                  <strong class="text-primary">Media & Branding</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <VLabel>Product Image</VLabel>
                  <VFileInput v-model="record.selectedFile" prepend-inner-icon="tabler-camera" prepend-icon=""
                    @change="handleFileChange" accept="image/*" show-size @click:clear="removeImage"
                    :clearable="!!previewImage || !!companyLogFile" />
                </VCol>

                <!-- Image Preview -->
                <VCol cols="12" md="6">
                  <div v-if="previewImage" class="d-flex">
                    <img :src="previewImage" alt="Preview" class="preview-img"
                      style=" border-radius: 10px;inline-size: 150px;" />
                    <div class="cutBtn" @click="removeImage">X</div>
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <label for="">Tags*</label>
                  <VCombobox v-model="record.tags" multiple :items="[]" chips placeholder="Enter title and press enter"
                    hint="Enter title and press enter" :rules="[requiredValidator]">
                    <template v-slot:chip="{ item, index }">
                      <VChip class="ma-1" color="primary">
                        {{ item.raw }}
                        <v-icon @click="removeInspectionTag(index)" class="ml-1" size="large"
                          icon="tabler-circle-letter-x"></v-icon>
                      </VChip>
                    </template>
                  </VCombobox>
                </VCol>
                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.status" label="Status" :items="['active', 'inactive']"
                    placeholder="Status" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextarea v-model="record.short_description" label="Short Description"
                    placeholder="Enter short description" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextarea v-model="record.detail_description" label="Detail Description"
                    placeholder="Enter Detail description" />
                </VCol>


              </VRow>
            </VCol>

            <VCol cols="12">
              <strong class="text-primary">Attributes</strong>
              <p class="text-sm text-disabled">
                Add custom attributes to your product/service
              </p>
            </VCol>

            <VCol cols="12" v-for="(attr, index) in record.attributes" :key="attr.id">
              <VRow class="border rounded pa-3 mb-3">
                <VCol cols="12" md="3">
                  <AppTextField v-model="attr.key" label="Key*" placeholder="Attribute name" :error-messages="attr.errorMessage ? [attr.errorMessage] : []
                    " @blur="validateAttribute(attr)" />
                </VCol>

                <VCol cols="12" md="3">
                  <AppSelect v-model="attr.type" label="Type*" :items="attributeTypes" />
                </VCol>

                <VCol cols="12" md="5">
                  <AppTextField v-model="attr.value" :label="`Value (${attr.type})`" :type="attr.type"
                    :placeholder="`Enter ${attr.type} value`" />
                </VCol>

                <VCol cols="12" md="1" class="d-flex align-center justify-end">
                  <VBtn icon color="error" variant="text" @click="removeAttribute(index)">
                    <VIcon icon="tabler-trash" size="20" />
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12" class="d-flex justify-end">
              <VBtn color="primary" variant="tonal" @click="addAttribute" prepend-icon="tabler-plus">
                Add Attribute
              </VBtn>
            </VCol>

            <VCol cols="12" class="d-flex gap-4 justify-start pt-6 pb-10">
              <VBtn type="submit" color="primary" :loading="isLoading">
                Save
              </VBtn>
              <VBtn color="error" variant="tonal" @click="router.push({ name: 'product-service-list' })">
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </PerfectScrollbar>
  </VCard>

  <!-- Create City Dialog -->
  <VDialog max-width="600" :model-value="isCreateCityDialog" @update:model-value="val => isCreateCityDialog = val">
    <VCard>
      <VCardTitle class="pa-4">Add City</VCardTitle>
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppTextField v-model="newCity.name" label="City Name" placeholder="Enter city name" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.country_id" :items="countryList" item-title="name" item-value="id"
              label="Country" placeholder="Select Country" @update:modelValue="fetchDropdownStates" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.state_id" :items="stateList" item-title="name" item-value="id" label="State"
              placeholder="Select State" />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField v-model="newCity.latitude" label="Latitude" placeholder="0" />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField v-model="newCity.longitude" label="Longitude" placeholder="0" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.city_type" :items="['no', 'yes']" label="City Type" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="tonal" color="secondary" @click="isCreateCityDialog = false">Cancel</VBtn>
        <VBtn color="primary" :loading="loadingCities" @click="createCity">Create</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.chip_clear_icon {
  block-size: 13px !important;
}
</style>
