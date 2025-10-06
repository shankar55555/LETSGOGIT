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
const fileInputRefs = ref({});
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
  purchase_no: "",
  variants: [],

  // Core Information
  category: "",
  collection: "",

  // Product Details
  material_fabric: "",
  care_instruction: "",
  season: "",

  // Media & Branding
  tags: [],
  status: "active",
  short_description: "",
  detail_description: "",
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

// const fetchCityById = async (id) => {
//   try {
//     const res = await $api('/city-list/' + id)
//     cityList.value = res.data ?? []
//   } catch (e) {
//     // toast.error('Failed to load city detail')
//   } finally {
//     lead.value.city_id = id;
//   }
// }

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
// Generate a new empty variant
const newVariant = () => ({
  id: uuidv4(),
  sku: "",
  mrp: null,
  stock_quantity: 0,
  low_stock_alert: null,
  images: []
});


const addVariant = () => {
  const variant = newVariant()
  record.value.variants.push(variant)

  // Validate after DOM update
  nextTick(() => {
    validateVariant(variant)
  })
}

const removeVariant = async index => {
  record.value.variants.splice(index, 1)
  await nextTick()
  refForm.value?.resetValidation()
}

// Validate single variant
const validateVariant = (variant) => {
  variant.isValid = true
  variant.errorMessage = ''

  // Required check for SKU
  if (!variant.sku?.trim()) {
    variant.isValid = false
    variant.errorMessage = 'SKU is required'
    return false
  }

  // Uniqueness check for SKU
  const duplicateCount = record.value.variants.filter(v =>
    v.id !== variant.id && v.sku === variant.sku
  ).length

  if (duplicateCount > 0) {
    variant.isValid = false
    variant.errorMessage = 'SKU must be unique'
    return false
  }

  // MRP validation
  if (variant.mrp && (isNaN(variant.mrp) || variant.mrp < 0)) {
    variant.isValid = false
    variant.errorMessage = 'MRP must be a valid positive number'
    return false
  }

  // Low stock alert validation
  if (variant.low_stock_alert && (isNaN(variant.low_stock_alert) || variant.low_stock_alert < 0)) {
    variant.isValid = false
    variant.errorMessage = 'Low stock alert must be a valid positive number'
    return false
  }

  return true
}

// Validate all variants
const validateAllVariants = () => {
  let allValid = true

  record.value.variants.forEach(variant => {
    if (!validateVariant(variant)) {
      toast.error(`Variant "${variant.sku || 'Untitled'}": ${variant.errorMessage}`)
      allValid = false
    }
  })

  return allValid
}

// Generate unique filename with timestamp and UUID
const generateUniqueFilename = (originalName) => {
  const timestamp = Date.now()
  const uuid = uuidv4().substring(0, 8)
  const extension = originalName.split('.').pop().toLowerCase()
  return `${timestamp}_${uuid}.${extension}`
}

// Upload image to filesystem and return file path
const uploadImageToFilesystem = async (file) => {
  try {
    const formData = new FormData()
    const uniqueFilename = generateUniqueFilename(file.name)

    // Create a new file with unique name
    const renamedFile = new File([file], uniqueFilename, { type: file.type })

    formData.append('image', renamedFile)
    formData.append('filename', uniqueFilename) // Required by backend validation
    formData.append('original_name', file.name) // Keep original name for reference

    const response = await $api('/upload-image', {
      method: 'POST',
      body: formData
    })

    if (response?.path) {
      return {
        success: true,
        path: response.path, // Relative path for database storage
        url: response.url || response.path, // Full URL for display
        filename: uniqueFilename
      }
    }

    throw new Error('Upload failed')
  } catch (error) {
    console.error('Image upload error:', error)
    return {
      success: false,
      error: error.message || 'Upload failed'
    }
  }
}

// Validate image file
const validateImageFile = (file) => {
  const maxSize = 5 * 1024 * 1024 // 5MB
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']

  if (!allowedTypes.includes(file.type)) {
    return { valid: false, error: 'Only JPEG, PNG, GIF, and WebP images are allowed' }
  }

  if (file.size > maxSize) {
    return { valid: false, error: 'Image size must be less than 5MB' }
  }

  return { valid: true }
}

// Handle variant image upload
const handleVariantImageUpload = async (event, variantId) => {
  const files = Array.from(event.target.files)
  const variant = record.value.variants.find(v => v.id === variantId)

  if (!variant) return

  // Show loading state
  isLoading.value = true

  try {
    let successCount = 0
    let errorCount = 0

    for (const file of files) {
      // Validate file
      const validation = validateImageFile(file)
      if (!validation.valid) {
        toast.error(`${file.name}: ${validation.error}`)
        errorCount++
        continue
      }

      // Upload to filesystem
      const uploadResult = await uploadImageToFilesystem(file)

      if (uploadResult.success) {
        variant.images.push({
          id: uuidv4(),
          url: uploadResult.url, // URL for display
          path: uploadResult.path, // Path for database storage
          filename: uploadResult.filename, // Unique filename
          name: file.name, // Original filename
          size: file.size
        })
        successCount++
      } else {
        toast.error(`Failed to upload ${file.name}: ${uploadResult.error}`)
        errorCount++
      }
    }

    // Show summary message
    if (successCount > 0) {
      toast.success(`Successfully uploaded ${successCount} image(s)`)
    }
    if (errorCount > 0 && successCount === 0) {
      toast.error(`Failed to upload ${errorCount} image(s)`)
    }

  } catch (error) {
    toast.error('Error uploading images')
    console.error(error)
  } finally {
    isLoading.value = false
    // Clear the file input
    event.target.value = ''
  }
}

// Remove variant image
const removeVariantImage = async (variantId, imageId) => {
  const variant = record.value.variants.find(v => v.id === variantId)
  if (!variant) return

  const imageIndex = variant.images.findIndex(img => img.id === imageId)
  if (imageIndex > -1) {
    const image = variant.images[imageIndex]

    // Optionally delete file from filesystem
    try {
      if (image.path) {
        await $api('/delete-image', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ path: image.path })
        })
      }
    } catch (error) {
      console.warn('Failed to delete image file:', error)
    }

    variant.images.splice(imageIndex, 1)
  }
}

// Trigger file input click
const triggerFileInput = (variantId) => {
  const fileInput = fileInputRefs.value[variantId]
  if (fileInput) {
    fileInput.click()
  }
}

// Generate sequential purchase number
const generatePurchaseNo = async () => {
  try {
    // Get the last purchase number from the server
    const res = await $api('/product/last-purchase-number')
    const lastNumber = res.data?.last_number || 0
    const nextNumber = (lastNumber + 1).toString().padStart(4, '0')
    console.log(lastNumber, nextNumber);
    return `PUR-${nextNumber}`
  } catch (error) {
    // Fallback to timestamp-based if API fails
    const timestamp = Date.now().toString().slice(-6)
    return `PUR-${timestamp}`
  }
}

const onSubmit = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true

  // Reset validation states
  record.value.variants.forEach(variant => {
    variant.isValid = null
    variant.errorMessage = ''
  })

  // Validate Vuetify form
  const { valid: formValid } = await refForm.value.validate()

  // Validate variants
  const variantsValid = validateAllVariants()

  if (!formValid || !variantsValid) {
    isSubmitting.value = false
    return
  }

  try {
    isLoading.value = true
    const payload = {
      ...record.value,
      variants: record.value.variants.map(({ id, isValid, errorMessage, selectedFiles, images, ...rest }) => ({
        ...rest,
        images: images.map(img => ({
          id: img.id,
          name: img.name,
          path: img.path, // Store file path for database
          url: img.url    // Keep URL for reference
        }))
      }))
    }

    const res = await $api('/product', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Created successfully!')
      router.push({ name: 'product-list' })
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'An error occurred')
  } finally {
    isSubmitting.value = false
    isLoading.value = false
  }
}

// Remove image handling functions as they are no longer needed

onMounted(async () => {
  fetchCityList()
  // Generate purchase number on component mount
  record.value.purchase_no = await generatePurchaseNo()
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
                  <strong class="text-primary">Core Information</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.name" label="Name*" :rules="[requiredValidator]"
                    placeholder="Enter product name" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField v-model="record.purchase_no" label="Purchase No*" :rules="[requiredValidator]"
                    placeholder="Auto-generated purchase number" readonly />
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
                  <strong class="text-primary">Product Details</strong>
                </VCol>

                <VCol cols="12" md="6">
                  <AppAutocomplete v-model="record.material_fabric" label="Material / Fabric" item-title="name"
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
                  <label for="">Tags*</label>
                  <VCombobox v-model="record.tags" multiple :items="[]" chips placeholder="Enter title and press enter"
                    hint="Enter title and press enter" :rules="[requiredValidator]">
                    <template v-slot:chip="{ item, index }">
                      <VChip class="ma-1" color="primary">
                        {{ item.raw }}
                        <v-icon @click="record.tags.splice(index, 1)" class="ml-1" size="large"
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
              <strong class="text-primary">Variants</strong>
              <p class="text-sm text-disabled">
                Add product variants with different SKUs and pricing
              </p>
            </VCol>

            <VCol cols="12" v-for="(variant, index) in record.variants" :key="variant.id">
              <VRow class="border rounded pa-3 mb-3">
                <VCol cols="12" md="3">
                  <AppTextField v-model="variant.sku" label="SKU*" placeholder="Enter SKU" :error-messages="variant.errorMessage ? [variant.errorMessage] : []
                    " @blur="validateVariant(variant)" />
                </VCol>

                <VCol cols="12" md="3">
                  <AppTextField v-model="variant.mrp" label="MRP*" type="number" min="0" step="0.01"
                    placeholder="Enter MRP" />
                </VCol>

                <VCol cols="12" md="2">
                  <AppTextField v-model="variant.stock_quantity" label="Stock Quantity" type="number" placeholder="0"
                    readonly />
                </VCol>

                <VCol cols="12" md="3">
                  <AppTextField v-model="variant.low_stock_alert" label="Low Stock Alert" type="number" min="0"
                    placeholder="Enter alert number" />
                </VCol>

                <VCol cols="12" md="1" class="d-flex align-center justify-end">
                  <VBtn icon color="error" variant="text" @click="removeVariant(index)">
                    <VIcon icon="tabler-trash" size="20" />
                  </VBtn>
                </VCol>

                <!-- Variant Images Section -->
                <VCol cols="12">
                  <VDivider class="my-3" />
                  <div class="d-flex align-center justify-between mb-3">
                    <span class="text-sm font-weight-medium">Variant Images</span>
                    <VBtn size="small" color="primary" variant="outlined" @click="triggerFileInput(variant.id)">
                      <VIcon icon="tabler-photo-plus" class="me-1" size="16" />
                      Add Images
                    </VBtn>
                  </div>

                  <input :ref="el => fileInputRefs[variant.id] = el" type="file" multiple accept="image/*"
                    style="display: none;" @change="handleVariantImageUpload($event, variant.id)" />

                  <div v-if="variant.images.length > 0" class="d-flex flex-wrap gap-3">
                    <div v-for="image in variant.images" :key="image.id" class="position-relative">
                      <VImg :src="image.url" width="80" height="80" class="rounded border" cover />
                      <VBtn icon size="x-small" color="error" class="position-absolute"
                        style="inset-block-start: -8px; inset-inline-end: -8px;"
                        @click="removeVariantImage(variant.id, image.id)">
                        <VIcon icon="tabler-x" size="12" />
                      </VBtn>
                    </div>
                  </div>

                  <div v-else class="text-center py-4 text-disabled">
                    <VIcon icon="tabler-photo" size="24" class="mb-2" />
                    <div class="text-sm">No images uploaded</div>
                  </div>
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12" class="d-flex justify-end">
              <VBtn color="primary" variant="tonal" @click="addVariant" prepend-icon="tabler-plus">
                Add Variants
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
