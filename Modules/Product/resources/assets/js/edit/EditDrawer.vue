<script setup>
import { v4 as uuidv4 } from 'uuid'
import { nextTick, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { toast } from 'vue3-toastify'
import { VForm } from 'vuetify/components'


const route = useRoute()
const router = useRouter()
const productServiceId = route.params.id

const refForm = ref(null)
const valid = ref(true)
const isLoading = ref(false)
let isSubmitting = ref(false)
const fileInputRefs = ref({})

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
})

// Generate a new empty variant
const newVariant = () => ({
  id: uuidv4(),
  sku: "",
  mrp: null,
  stock_quantity: 0,
  low_stock_alert: null,
  images: [],
  selectedFiles: []
})

// Load existing product/service data
const loadProductService = async () => {
  try {
    isLoading.value = true
    const response = await $api(`/product/${productServiceId}`)

    const productService = response?.data

    if (!productService) {
      toast.error('Product not found.')
      return
    }

    // Transform variants to include images array format
    console.log(productService.variants);
    const transformedVariants = productService.variants?.map(variant => ({
      ...variant,
      images: variant.images?.length > 0 ? variant.images.map(img => ({
        id: img.id,
        url: img.url,
        name: img.name || 'existing-image',
        isExisting: true
      })) : (variant.image ? [{
        id: uuidv4(),
        url: variant.image,
        name: 'existing-image',
        isExisting: true
      }] : []),
      selectedFiles: []
    })) || []

    record.value = {
      ...productService,
      variants: transformedVariants
    }
  } catch (err) {
    console.error('Failed to load product:', err)
    toast.error('Failed to load product.')
  } finally {
    isLoading.value = false
  }
}

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

// Handle variant image upload
const handleVariantImageUpload = (event, variantId) => {
  const files = Array.from(event.target.files)
  const variant = record.value.variants.find(v => v.id === variantId)

  if (!variant) return

  files.forEach(file => {
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        variant.images.push({
          id: uuidv4(),
          url: e.target.result,
          file: file,
          name: file.name,
          isExisting: false
        })
      }
      reader.readAsDataURL(file)
      variant.selectedFiles.push(file)
    }
  })
}

// Remove variant image
const removeVariantImage = (variantId, imageId) => {
  const variant = record.value.variants.find(v => v.id === variantId)
  if (!variant) return

  const imageIndex = variant.images.findIndex(img => img.id === imageId)
  if (imageIndex > -1) {
    const image = variant.images[imageIndex]
    variant.images.splice(imageIndex, 1)

    // Only remove from selectedFiles if it's a new image (not existing)
    if (!image.isExisting) {
      const fileIndex = variant.selectedFiles.findIndex(file => file.name === image.name)
      if (fileIndex > -1) {
        variant.selectedFiles.splice(fileIndex, 1)
      }
    }
  }
}

// Trigger file input for variant image upload
const triggerFileInput = (variantId) => {
  const fileInput = fileInputRefs.value[`fileInput-${variantId}`]
  if (fileInput) {
    fileInput.click()
  }
}

// Update product/service
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
      variants: record.value.variants.map(({ isValid, errorMessage, selectedFiles, images, ...rest }) => ({
        ...rest,
        // Send the first image URL as the main image for backend compatibility
        image: images.length > 0 ? images[0].url : null,
        images: images.map(img => ({
          id: img.id,
          name: img.name,
          url: img.url,
          isExisting: img.isExisting || false
        }))
      }))
    }

    const res = await $api(`/product/${productServiceId}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Product updated successfully!')
      router.push({ name: 'product-list' })
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'An error occurred while updating.')
  } finally {
    isSubmitting.value = false
    isLoading.value = false
  }
}

function removeTag(index) {
  record.value.tags.splice(index, 1)
}

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    record.value.selectedFile = file
    const reader = new FileReader()
    reader.onload = (e) => {
      record.value.image_path = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeImage() {
  record.value.image_path = ''
  record.value.selectedFile = null
}

onMounted(() => { loadProductService() });
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
                    placeholder="Purchase number" readonly />
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

                <!-- Images are now handled in individual variants below -->

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
                    <!-- <span class="text-sm font-weight-medium">Variant Images</span> -->
                    <VBtn size="small" color="primary" variant="outlined" @click="triggerFileInput(variant.id)">
                      <VIcon icon="tabler-photo-plus" class="me-1" size="16" />
                      Add Images
                    </VBtn>
                  </div>

                  <input :ref="el => fileInputRefs[`fileInput-${variant.id}`] = el" type="file" multiple
                    accept="image/*" style="display: none;" @change="handleVariantImageUpload($event, variant.id)" />

                  <div v-if="variant.images.length > 0" class="d-flex flex-wrap gap-3">
                    <div v-for="image in variant.images" :key="image.id" class="position-relative">
                      <VImg :src="image.url" width="80" height="80" class="rounded border" cover />

                      <VBtn icon size="x-small" color="error" class="position-absolute"
                        style=" z-index: 2;inset-block-start: -8px; inset-inline-end: -8px;"
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
                Update
              </VBtn>
              <VBtn color="error" variant="tonal" @click="loadProductService">
                Reset
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </PerfectScrollbar>
  </VCard>
</template>
