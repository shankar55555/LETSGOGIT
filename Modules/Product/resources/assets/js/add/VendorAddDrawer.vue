<script setup>
import { computed, ref, watch } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import { emailRule, requiredRule, validateGSTIN, validateMobileNumber } from '../validations/validationRules';

const valid = ref(true);
const refForm = ref(null);
const isLoading = ref(false);

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  editMode: {
    type: Boolean,
    default: false,
  },
  vendor: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:isDrawerOpen', 'submit', 'close']);

const vendor = ref({
  first_name: '',
  last_name: '',
  company_name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zip_code: '',
  gstin: '',
});

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val);
};

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false);
  emit('close');
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

const resetForm = () => {
  vendor.value = {
    first_name: '',
    last_name: '',
    company_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    gstin: '',
  };
};

watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val && props.editMode && props.vendor) {
      vendor.value = { ...props.vendor };
    } else if (val && !props.editMode) {
      resetForm();
    }
  }
);

const handleSubmitForm = async () => {
  const { valid } = await refForm.value.validate();

  if (!valid) return;

  isLoading.value = true;

  try {
    const endpoint = props.editMode ? `/vendors/${props.vendor.id}` : '/vendors';
    const method = props.editMode ? 'PUT' : 'POST';

    const response = await $api(endpoint, {
      method,
      body: vendor.value,
    });

    if (response) {
      toast.success(props.editMode ? 'Vendor updated successfully' : 'Vendor added successfully');
      emit('submit');
      closeNavigationDrawer();
    }
  } catch (error) {
    console.error('Error saving vendor:', error);
    toast.error(error?.response?.data?.message || 'An error occurred while saving the vendor');
  } finally {
    isLoading.value = false;
  }
};

const handleEmailInput = (event) => {
  vendor.value.email = event.target.value.toLowerCase();
};

const fullName = computed(() => {
  return `${vendor.value.first_name} ${vendor.value.last_name}`.trim();
});
</script>

<template>
  <div>
    <div v-if="isDrawerOpen" class="backdrop"></div>
    <VNavigationDrawer permanent :width="800" location="end" class="scrollable-content"
      :model-value="props.isDrawerOpen" @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection :title="editMode ? 'Edit Vendor' : 'Add Vendor'" @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="vendor_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="handleSubmitForm">
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.first_name" :rules="requiredRule" label="First Name"
                  placeholder="First Name" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.last_name" :rules="requiredRule" label="Last Name"
                  placeholder="Last Name" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.company_name" label="Company Name" placeholder="Company Name" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.email" :rules="emailRule" label="Email" placeholder="Email"
                  @input="handleEmailInput" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.phone" :rules="validateMobileNumber" label="Phone Number"
                  placeholder="Phone Number" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="vendor.gstin" :rules="validateGSTIN" label="GSTIN"
                  placeholder="15-digit GST Identification Number" />
              </VCol>
              <VCol cols="12">
                <AppTextField v-model="vendor.address" label="Street Address" placeholder="Street Address" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="vendor.city" label="City" placeholder="City" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="vendor.state" label="State" placeholder="State" />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="vendor.zip_code" label="ZIP Code" placeholder="ZIP Code" />
              </VCol>
              <VCol cols="12">
                <VBtn type="submit" class="me-3" :loading="isLoading">
                  {{ editMode ? 'Update' : 'Submit' }}
                </VBtn>
                <VBtn variant="tonal" color="secondary" @click="closeNavigationDrawer">
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCard>
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>

<style scoped>
.vendor_card {
  padding: 20px;
}

.backdrop {
  position: fixed;
  z-index: 999;
  background-color: rgba(0, 0, 0, 50%);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}
</style>
