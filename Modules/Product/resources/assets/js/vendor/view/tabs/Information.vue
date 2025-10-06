<script setup>
import moment from 'moment';

const props = defineProps({
  vendorData: {
    type: Object,
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
</script>

<template>
  <div v-if="props.vendorData" class="vendor-details">
    <!-- Header Section with Basic Info -->
    <VCard class="mb-6" elevation="2">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <div class="vendor-header">
              <div class="d-flex align-center justify-space-between mb-3">
                <h3 class="text-h3 font-weight-bold">{{ props.vendorData.full_name || 'Unnamed Vendor' }}</h3>
                <VChip v-if="props.vendorData.status"
                  :color="props.vendorData.status === 'active' ? 'success' : 'error'" variant="outlined" size="large">
                  {{ props.vendorData.status }}
                </VChip>
              </div>
              <div v-if="props.vendorData.company_name" class="mb-3">
                <h5 class="text-h5 text-grey-darken-1">{{ props.vendorData.company_name }}</h5>
              </div>
              <div class="meta-info">
                <VRow dense>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-account-plus</VIcon>
                      <span class="text-caption text-grey">Created by:</span>
                      <span class="text-body-2">{{ props.vendorData.creator?.name || '-' }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-account-edit</VIcon>
                      <span class="text-caption text-grey">Updated by:</span>
                      <span class="text-body-2">{{ props.vendorData.updater?.name || '-' }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-calendar-plus</VIcon>
                      <span class="text-caption text-grey">Created:</span>
                      <span class="text-body-2">{{ makeDateFormat(props.vendorData.created_at, true) }}</span>
                    </div>
                  </VCol>
                  <VCol cols="12" sm="6">
                    <div class="d-flex align-center gap-2">
                      <VIcon size="16" color="grey">mdi-calendar-edit</VIcon>
                      <span class="text-caption text-grey">Updated:</span>
                      <span class="text-body-2">{{ makeDateFormat(props.vendorData.updated_at, true) }}</span>
                    </div>
                  </VCol>
                </VRow>
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Vendor Information Sections -->
    <VRow>
      <!-- Contact Information -->
      <VCol cols="12" lg="6">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-card-account-phone</VIcon>
            Contact Information
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">First Name</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.first_name || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Last Name</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.last_name || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Email</p>
                  <p class="text-body-1 font-weight-medium">
                    <a :href="`mailto:${props.vendorData.email}`" class="text-decoration-none">
                      {{ props.vendorData.email || '-' }}
                    </a>
                  </p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Phone</p>
                  <p class="text-body-1 font-weight-medium">
                    <a :href="`tel:${props.vendorData.phone}`" class="text-decoration-none">
                      {{ props.vendorData.phone || '-' }}
                    </a>
                  </p>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Company Information -->
      <VCol cols="12" lg="6">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-domain</VIcon>
            Company Information
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Company Name</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.company_name || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">GSTIN</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.gstin || '-' }}</p>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Address Information -->
      <VCol cols="12">
        <VCard class="mb-6" elevation="1">
          <VCardTitle class="d-flex align-center gap-2 bg-grey-lighten-5 text-grey-darken-3 bg-primary">
            <VIcon size="20" color="grey-darken-2">mdi-map-marker</VIcon>
            Address Information
          </VCardTitle>
          <VCardText>
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">Street Address</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.address || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">City</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.city || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">State</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.state || '-' }}</p>
                </div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="info-item">
                  <p class="text-caption text-grey mb-1">ZIP Code</p>
                  <p class="text-body-1 font-weight-medium">{{ props.vendorData.zip_code || '-' }}</p>
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
.info-item {
  margin-block: 18px 0;
}

.vendor-header {
  padding-inline: 0.5rem 0;
}
</style>
