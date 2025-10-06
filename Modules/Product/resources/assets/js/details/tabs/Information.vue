<script setup>
import moment from 'moment';
const props = defineProps({
  InfoData: {
    type: null,
    required: true,
  },
})
const makeDateFormat = (date, onlyDate = false) => {
  if (!date) return '-';
  if (onlyDate)
    return moment(date).format('DD-MM-YYYY');
  else
    return moment(date).format('LLLL');
};
const formatPrice = (price) => {
  if (!price) return '-';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price);
};
const getStatusColor = (status) => {
  switch (status?.toLowerCase()) {
    case 'active': return 'success';
    case 'inactive': return 'error';
    case 'draft': return 'warning';
    default: return 'info';
  }
};
</script>
<template>
  <div v-if="props.InfoData" class="product-details">
    <!-- Header Section with Image and Basic Info -->
    <VCard class="mb-6" elevation="2">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4" lg="3">
            <div class="product-image-container">
              <VImg v-if="props.InfoData.image_path" :src="props.InfoData.image_path" :alt="props.InfoData.name"
                class="product-image" cover />
              <div v-else class="no-image-placeholder">
                <VIcon size="64" color="grey-lighten-2">mdi-image-outline</VIcon>
                <p class="text-grey-lighten-1 mt-2">No Image</p>
              </div>
            </div>
          </VCol>
          <VCol cols="12" md="8" lg="9">
            <div class="product-header">
              <div class="d-flex align-center justify-space-between mb-3">
                <h3 class="text-h3 font-weight-bold">{{ props.InfoData.name || 'Unnamed Product' }}</h3>
                <VChip v-if="props.InfoData.status" color="grey-darken-1" variant="outlined" size="large">
                  {{ props.InfoData.status }}
                </VChip>
              </div>
              <div class="price-section mb-4">
                <div class="d-flex align-center gap-4">
                  <div>
                    <p class="text-caption text-grey mb-1">Purchase Number</p>
                    <h4 class="text-h4 text-primary font-weight-medium">{{ props.InfoData.purchase_no || '-' }}</h4>
                  </div>
                  <div v-if="props.InfoData.variants?.length">
                    <p class="text-caption text-grey mb-1">Total Variants</p>
                    <h5 class="text-h5 text-grey-darken-1">{{ props.InfoData.variants.length }}</h5>
                  </div>
                </div>
              </div>
              <div v-if="props.InfoData.short_description" class="mb-3">
                <p class="text-body-1 text-grey-darken-1">{{ props.InfoData.short_description }}</p>
              </div>
              <div class="meta-info">
                <VRow dense>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-account-plus</VIcon>
                      <span class="text-caption text-grey">Created by:</span>
                      <span class="text-body-2">{{ props.InfoData.creator?.name || '-' }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-account-edit</VIcon>
                      <span class="text-caption text-grey">Updated by:</span>
                      <span class="text-body-2">{{ props.InfoData.updater?.name || '-' }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-calendar-plus</VIcon>
                      <span class="text-caption text-grey">Created:</span>
                      <span class="text-body-2">{{ makeDateFormat(props.InfoData.created_at, true) }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-calendar-edit</VIcon>
                      <span class="text-caption text-grey">Updated:</span>
                      <span class="text-body-2">{{ makeDateFormat(props.InfoData.updated_at, true) }}</span>
                    </div>
                  </VCol>
                </VRow>
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <!-- Product Information Sections -->
    <VRow>
      <!-- Core Information -->
      <VCol cols="12" lg="6">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-information-outline</VIcon>
            Core Information
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Purchase Number</p>
                  <p class="text-body-1 font-weight-medium text-primary">{{ props.InfoData.purchase_no || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Category</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.category || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Collection</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.collection || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Season</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.season || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Tags</p>

                  <div class="d-flex flex-wrap gap-2">
                    <VChip v-for="(tag, index) in props.InfoData.tags" :key="index" color="grey-darken-1"
                      variant="outlined" size="small">
                      {{ tag }}
                    </VChip>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <!-- Media & Branding -->
      <!-- <VCol cols="12" lg="6">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-image-multiple</VIcon>
            Media & Branding
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Brand</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.brand || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Model</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.model || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Main Product Image</p>
                  <div v-if="props.InfoData.image_path" class="mt-2">
                    <VImg :src="props.InfoData.image_path" :alt="props.InfoData.name" width="120" height="120"
                      class="rounded border" cover />
                  </div>
                  <p v-else class="text-body-2 text-grey">No main image uploaded</p>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol> -->
      <!-- Product Details -->
      <VCol cols="12" lg="6">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-text-box-outline</VIcon>
            Product Details
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Material/Fabric</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.material_fabric || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Care Instructions</p>
                  <p class="text-body-1 font-weight-medium">{{ props.InfoData.care_instruction || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" v-if="props.InfoData.detail_description">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Detailed Description</p>
                  <p class="text-body-1">{{ props.InfoData.detail_description }}</p>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Product Variants Section -->
    <VCard v-if="props.InfoData.variants?.length" class="mb-6" elevation="1">
      <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
        <VIcon size="20" color="grey-darken-2">mdi-package-variant</VIcon>
        Product Variants ({{ props.InfoData.variants.length }})
      </VCardTitle>
      <VCardText>
        <VRow>
          <VCol v-for="(variant, index) in props.InfoData.variants" :key="variant.id" cols="12" md="6" lg="4">
            <VCard variant="outlined" class="h-100 variant-card">
              <VCardText>
                <div class="variant-header mb-3">
                  <h6 class="text-h6 font-weight-medium text-primary">{{ variant.sku }}</h6>
                  <VChip size="small" :color="variant.stock_quantity > 0 ? 'success' : 'error'" variant="tonal">
                    {{ variant.stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}
                  </VChip>
                </div>

                <!-- Variant Images -->
                <div v-if="variant.images?.length" class="variant-images mb-3">
                  <p class="text-caption text-grey mb-2">Images ({{ variant.images.length }})</p>
                  <div class="d-flex flex-wrap gap-2">
                    <VImg v-for="image in variant.images.slice(0, 3)" :key="image.id" :src="image.url" width="60"
                      height="60" class="rounded border" cover />
                    <div v-if="variant.images.length > 3" class="d-flex align-center justify-center rounded border"
                      style=" background: #f5f5f5; block-size: 60px;inline-size: 60px;">
                      <span class="text-caption text-grey">+{{ variant.images.length - 3 }}</span>
                    </div>
                  </div>
                </div>
                <div v-else class="variant-images mb-3">
                  <p class="text-caption text-grey mb-2">Images</p>
                  <div class="d-flex align-center justify-center rounded border"
                    style=" background: #f8f8f8; block-size: 60px;inline-size: 60px;">
                    <VIcon size="24" color="grey-lighten-1">mdi-image-off</VIcon>
                  </div>
                </div>

                <!-- Variant Details -->
                <div class="variant-details">
                  <VRow dense>
                    <VCol cols="6">
                      <p class="text-caption text-grey mb-1">MRP</p>
                      <p class="text-body-2 font-weight-medium">{{ formatPrice(variant.mrp) }}</p>
                    </VCol>
                    <VCol cols="6">
                      <p class="text-caption text-grey mb-1">Stock</p>
                      <p class="text-body-2 font-weight-medium"
                        :class="variant.stock_quantity > 0 ? 'text-success' : 'text-error'">
                        {{ variant.stock_quantity || 0 }}
                      </p>
                    </VCol>
                    <VCol cols="12" v-if="variant.low_stock_alert">
                      <p class="text-caption text-grey mb-1">Low Stock Alert</p>
                      <p class="text-body-2 font-weight-medium">{{ variant.low_stock_alert }}</p>
                    </VCol>
                  </VRow>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VAlert v-else-if="!props.InfoData.variants?.length" variant="outlined" color="info" class="mb-6">
      <VIcon start>mdi-information-outline</VIcon>
      No variants found for this product.
    </VAlert>

    <!-- Attributes Section -->
    <VCard v-if="props.InfoData.attributes?.length" class="mb-6" elevation="1">
      <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
        <VIcon size="20" color="grey-darken-2">mdi-format-list-bulleted</VIcon>
        Product Attributes
      </VCardTitle>
      <VCardText>
        <VRow>
          <VCol v-for="(attribute, index) in props.InfoData.attributes" :key="index" cols="12" sm="6" md="4">
            <VCard variant="outlined" class="h-100 attribute-card">
              <VCardText>
                <div class="text-center">
                  <p class="text-caption text-grey-darken-1 mb-2">{{ attribute.key }}</p>
                  <p class="text-h6 font-weight-medium text-grey-darken-3">{{ attribute.value }}</p>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
  <!-- Loading/Error State -->
  <VCard v-else class="text-center pa-8">
    <VIcon size="64" color="grey-lighten-2">mdi-package-variant-closed</VIcon>
    <p class="text-h6 text-grey mt-4">No product information available</p>
  </VCard>
</template>
<style scoped>
.product-details {
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 100%;
}

.product-image-container {
  position: relative;
  overflow: hidden;
  border-radius: 12px;
  block-size: 250px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 10%);
  inline-size: 100%;
}

.product-image {
  border-radius: 12px;
  block-size: 100%;
  inline-size: 100%;
  transition: transform 0.3s ease;
}

.product-image:hover {
  transform: scale(1.02);
}

.no-image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px dashed #bdbdbd;
  border-radius: 8px;
  background: #f8f8f8;
  block-size: 100%;
}

.product-header {
  padding-block: 8px;
}

.price-section {
  padding: 16px;
  border-radius: 8px;
  background: #fafafa;
  border-inline-start: 3px solid #9e9e9e;
}

.info-item {
  border-block-end: 1px solid rgba(0, 0, 0, 5%);
  padding-block: 12px;
  transition: background-color 0.2s ease;
}

.info-item:last-child {
  border-block-end: none;
}

.info-item:hover {
  border-radius: 6px;
  background-color: #f8f8f8;
  padding-inline: 8px;
}

.v-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px !important;
  transition: all 0.2s ease;
}

.v-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 8%) !important;
  transform: translateY(-1px);
}

.v-card-title {
  border-block-end: 1px solid #e0e0e0;
  border-end-end-radius: 0;
  border-end-start-radius: 0;
  border-start-end-radius: 8px;
  border-start-start-radius: 8px;
  font-weight: 500;
  letter-spacing: 0.25px;
  padding-block: 16px;
  padding-inline: 20px;
}

.v-card-text {
  padding: 16px;
}

.meta-info .v-col {
  margin-block-end: 8px;
}

.meta-info .d-flex {
  background-color: #f8f8f8;
  padding-block: 8px;
  padding-inline: 12px;
  transition: background-color 0.2s ease;
}

.meta-info .d-flex:hover {
  background-color: #f0f0f0;
}

.v-chip {
  border-radius: 4px;
  font-weight: 400;
  letter-spacing: 0.1px;
}

.v-list-item {
  border-radius: 6px;
  margin-block-end: 2px;
  transition: background-color 0.2s ease;
}

.v-list-item:hover {
  background-color: #f8f8f8;
}

.v-alert {
  border-radius: 6px;
  box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
  .product-image-container {
    block-size: 200px;
  }

  .price-section .d-flex {
    flex-direction: column;
    gap: 16px;
  }

  .product-header h3 {
    font-size: 1.5rem;
  }

  .v-card-title {
    font-size: 1rem;
    padding-block: 12px;
    padding-inline: 16px;
  }

  .v-card-text {
    padding: 12px;
  }
}

@media (max-width: 480px) {
  .product-image-container {
    block-size: 180px;
  }

  .product-header h3 {
    font-size: 1.3rem;
  }

  .meta-info .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 4px;
  }
}

@keyframes fade-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

.attribute-card {
  border-color: #e0e0e0;
  transition: border-color 0.2s ease;
}

.attribute-card:hover {
  border-color: #bdbdbd;
}

.variant-card {
  border-color: #e0e0e0;
  transition: all 0.2s ease;
}

.variant-card:hover {
  border-color: #bdbdbd;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  transform: translateY(-1px);
}

.variant-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid #f0f0f0;
  padding-block-end: 8px;
}

.variant-images {
  border-block-end: 1px solid #f0f0f0;
  padding-block-end: 12px;
}

.variant-details {
  padding-block-start: 8px;
}

/* Custom scrollbar for long content */
.v-card-text::-webkit-scrollbar {
  inline-size: 6px;
}

.v-card-text::-webkit-scrollbar-track {
  border-radius: 3px;
  background: #f1f1f1;
}

.v-card-text::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background: #c1c1c1;
}

.v-card-text::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
