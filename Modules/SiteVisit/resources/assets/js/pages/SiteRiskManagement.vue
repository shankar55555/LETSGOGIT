<script setup>
import { useFetchStatusList } from "@/utils/common";
import moment from "moment";
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue3-toastify";
import UploadFiles from "./UploadFiles.vue";
const route = useRoute();
const router = useRouter();
const isLoading = ref(false);
const isSubmitting = ref(false);
const hasRiskManagementData = ref(false);
const loading = ref(false);
const tab = ref(null)
const tabs = ref([
  {
    title: 'Information',
    icon: 'tabler-user',
    action: 'siteVisit',
    subject: 'view',
  },
  {
    title: 'Media',
    icon: 'tabler-files',
    action: 'siteVisit',
    subject: 'view',
  },
])

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;
const filterTabs = computed(() =>
  tabs.value.filter(({ action, subject }) => {
    if (!action || !subject) return true;
    return $can?.(action, subject);
  })
);

// Form state management
const formState = ref({
  customer: {
    name: "",
    phone: "",
    email: "",
    address: "",
    status: "",
  },
  site: {
    visit_assignee_id: "",
    building_type: "",
    roof_type: "",
    height_of_roof: "",
    service: "",
    visit_datetime: "",
    solution_recommended: "",
    status: "",
  },
});

// Add error state
const formErrors = ref({
  solution_recommended: "",
});

// Dynamic data for dropdowns
const dropdownData = ref({
  users: [],
  buildingTypes: [
    "Residential",
    "Commercial",
    "Industrial",
    "Educational",
    "Healthcare",
    "Retail",
  ],
  roofTypes: [
    "Flat Roof",
    "Gable Roof",
    "Hip Roof",
    "Mansard Roof",
    "Metal Roof",
    "Shingle Roof",
  ],
  services: [
    "Roof Inspection",
    "Maintenance",
    "Repair",
    "Installation",
    "Consultation",
    "Emergency Service",
  ],
});

// Form validation computed properties
const isCustomerValid = computed(() => {
  const { name, phone, email, address, status } = formState.value.customer;
  return name.trim() && phone.trim() && email.trim() && address.trim() && status;
});

const isSiteValid = computed(() => {
  const {
    visit_assignee_id,
    building_type,
    roof_type,
    height_of_roof,
    service,
    visit_datetime,
    solution_recommended,
    status,
  } = formState.value.site;
  return (
    visit_assignee_id &&
    building_type &&
    roof_type &&
    height_of_roof &&
    service &&
    visit_datetime &&
    solution_recommended.trim().length >= 20 &&
    status
  );
});

const isFormValid = computed(() => {
  return isCustomerValid.value && isSiteValid.value;
});


// API Status computed properties
const isLoadingAny = computed(() => {
  return isLoading.value || isSubmitting.value;
});

// API calls
const fetchUserList = async () => {
  try {
    const response = await $api("/dropdown-user-list");
    dropdownData.value.users = response.data ?? [];
  } catch (error) {
    toast.error(error?.response?.data?.message || "Error fetching user list.");
  }
};

const fetchSiteVisitDetails = async () => {
  try {
    isLoading.value = true;
    const response = await $api(`/site-visit/${route.params.id}`);

    const data = response.data;
    if (!data) return;

    const { client_data, lead_data, site_risk_management_data, ...siteData } = data;
    const riskData = site_risk_management_data || {};

    hasRiskManagementData.value = !!site_risk_management_data;

    const getFallback = (key) =>
      riskData[key] ?? client_data?.[key] ?? lead_data?.[key] ?? "";

    const getSiteField = (key) =>
      riskData[key] ?? (key === "visit_assignee_id" ? siteData.visit_assignee : "") ?? "";

    const formatRiskDate = (date) => moment.utc(date).format('YYYY-MM-DD HH:mm');
    formState.value = {
      customer: {
        name: getFallback("customer_name") || getFallback("name"),
        phone: getFallback("phone"),
        email: getFallback("email"),
        address: getFallback("address"),
        status: riskData.status ?? siteData.status ?? "",
      },
      site: {
        visit_assignee_id: getSiteField("visit_assignee_id"),
        building_type: riskData.building_type ?? "",
        roof_type: riskData.roof_type ?? "",
        height_of_roof: riskData.height_of_roof ?? "",
        service: riskData.service ?? "",
        visit_datetime: formatRiskDate(riskData.visit_datetime ?? siteData.visit_time ?? ""),
        solution_recommended: riskData.solution_recommended ?? "",
        status: riskData.status ?? siteData.status ?? "",
      },
    };

    // Ensure the selected user exists in the dropdown
    const selectedUserId = formState.value.site.visit_assignee_id;
    if (
      selectedUserId &&
      !dropdownData.value.users.some((user) => user.uuid === selectedUserId)
    ) {
      console.warn("Selected user not found in user list:", selectedUserId);
    }
  } catch (error) {
    console.error("Error fetching site visit details:", error);
    toast.error("Failed to load site visit details");
  } finally {
    isLoading.value = false;
  }
};

// Add watcher for solution_recommended
watch(
  () => formState.value.site.solution_recommended,
  (newVal) => {
    if (newVal.length < 20) {
      formErrors.value.solution_recommended =
        "Solution recommendation should be at least 20 characters";
    } else {
      formErrors.value.solution_recommended = "";
    }
  }
);
const refForm = ref(null);

// Update onSubmit function
const onSubmit = async () => {

  let { valid, errors } = await refForm.value.validate();
  if (!valid) {
    isSubmitting.value = false;
    return false;
  }

  try {
    isSubmitting.value = true;
    const formData = {
      customer_name: formState.value.customer.name,
      phone: formState.value.customer.phone,
      email: formState.value.customer.email,
      address: formState.value.customer.address,
      status: formState.value.site.status || formState.value.customer.status,
      ...formState.value.site,
    };

    const res = await $api(`/site-visit/${route.params.id}/risk-management`, {
      method: "POST",
      body: formData,
    });

    if (res) {
      toast.success("Inspection report submitted successfully");
      hasRiskManagementData.value = true;
    }
  } catch (error) {
    console.error("Error submitting inspection report:", error);
    if (error?.response?.data?.errors?.solution_recommended) {
      formErrors.value.solution_recommended =
        error.response.data.errors.solution_recommended[0];
    }
    toast.error(error?._data?.message || "Failed to submit inspection report");
  } finally {
    isSubmitting.value = false;
  }
};

const { statusList, fetchStatusList } = useFetchStatusList();

// Lifecycle hooks
onMounted(async () => {
  fetchStatusList(MODULE_SITE_VISIT);
  try {
    await fetchUserList();
    await fetchSiteVisitDetails();
  } catch (error) {
    console.error("Error in initialization:", error);
    toast.error("Failed to initialize form data");
  }
});

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <div class="d-flex">
          <h5 class="text-h5 mb-1 mr-3">
            Site Assessment & Installation</h5>
        </div>
      </div>
      <div class="d-flex gap-4">
        <VBtn variant="tonal" color="primary" @click="router.go(-1)">
          <VIcon icon="tabler-arrow-back-up" class="mr-2" />Back
        </VBtn>
      </div>
    </div>

    <VCard>
      <VCardText>
        <div v-if="isLoading" class="d-flex justify-center align-center py-8">
          <VProgressCircular indeterminate />
        </div>
        <div v-else>
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" md="12" lg="12">
                <VTabs v-model="tab" class="v-tabs-pill mb-3 disable-tab-transition">
                  <VTab v-for="tab in filterTabs" :key="tab.title">
                    <VIcon size="20" start :icon="tab.icon" />
                    {{ tab.title }}
                  </VTab>
                </VTabs>
                <VWindow v-model="tab" class="disable-tab-transition" :touch="false">
                  <VWindowItem>
                    <!-- Customer Details Section -->
                    <h2 class="text-h5 mb-6">Customer Details</h2>
                    <VRow>
                      <VCol cols="12" md="6">
                        <VTextField v-model="formState.customer.name" label="Customer Name" :rules="[requiredValidator]"
                          placeholder="Enter customer name" variant="outlined" density="comfortable"
                          :error="!formState.customer.name && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VTextField v-model="formState.customer.phone" label="Phone" :rules="[requiredValidator]"
                          placeholder="Enter phone number" variant="outlined" density="comfortable"
                          :error="!formState.customer.phone && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VTextField v-model="formState.customer.email" label="Email" :rules="[requiredValidator]"
                          placeholder="Enter email address" variant="outlined" density="comfortable"
                          :error="!formState.customer.email && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <AppTextarea v-model="formState.customer.address" label="Address" placeholder="Enter address"
                          variant="outlined" density="comfortable"
                          :error="!formState.customer.address && isFormValid === false" :rules="[
                            (v) => !!v || 'Address is required',
                            (v) =>
                              (v && v.length >= 10) ||
                              'Address should be at least 10 characters',
                          ]" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <VSelect v-model="formState.site.status" label="Status" :items="statusList"
                          :rules="[requiredValidator]" item-title="status_text" item-value="slug" variant="outlined"
                          density="comfortable" :error="!formState.site.status && isFormValid === false" clearable />
                      </VCol>
                    </VRow>

                    <!-- Site Info Section -->
                    <h2 class="text-h5 mb-6 mt-6">Site Info</h2>
                    <VRow>
                      <VCol cols="12" md="6">
                        <VSelect v-model="formState.site.visit_assignee_id" label="Select User to Visit"
                          :rules="[requiredValidator]" :items="dropdownData.users" item-title="name" item-value="uuid"
                          variant="outlined" density="comfortable"
                          :error="!formState.site.visit_assignee_id && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VSelect v-model="formState.site.building_type" label="Building Type"
                          :rules="[requiredValidator]" :items="dropdownData.buildingTypes" variant="outlined"
                          density="comfortable" :error="!formState.site.building_type && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VSelect v-model="formState.site.roof_type" label="Roof Type" :items="dropdownData.roofTypes"
                          :rules="[requiredValidator]" variant="outlined" density="comfortable"
                          :error="!formState.site.roof_type && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VTextField v-model="formState.site.height_of_roof" label="Height of Roof" type="number"
                          :rules="[requiredValidator]" variant="outlined" density="comfortable"
                          :error="!formState.site.height_of_roof && isFormValid === false" />
                      </VCol>
                      <VCol cols="12" md="6">
                        <VSelect v-model="formState.site.service" label="Select Service" :items="dropdownData.services"
                          :rules="[requiredValidator]" variant="outlined" density="comfortable"
                          :error="!formState.site.service && isFormValid === false" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <AppDateTimePicker v-model="formState.site.visit_datetime" placeholder="Select Visit DateTime"
                          :rules="[requiredValidator]" variant="outlined" density="comfortable" :config="{
                            enableTime: true,
                            dateFormat: 'Y-m-d H:i',

                          }" :error="!formState.site.visit_datetime && isFormValid === false" />
                      </VCol>
                      <VCol cols="12">
                        <VTextarea v-model="formState.site.solution_recommended" label="Solution Recommended"
                          variant="outlined" rows="3" :error="!!formErrors.solution_recommended"
                          :error-messages="formErrors.solution_recommended" :rules="[
                            (v) => !!v || 'Solution recommendation is required',
                            (v) =>
                              (v && v.length >= 20) ||
                              'Solution recommendation should be at least 20 characters',
                          ]" />
                      </VCol>
                    </VRow>

                    <div class="mt-6" v-if="$can('siteVisit', 'create')">
                      <VBtn type="submit" :color="hasRiskManagementData ? 'success' : 'primary'" :loading="isSubmitting"
                        :disabled="isLoadingAny" :class="hasRiskManagementData ? '' : 'submit-inspection-btn'">
                        {{ hasRiskManagementData ? 'Save Changes' : 'Submit Inspection Report' }}
                      </VBtn>
                    </div>
                  </VWindowItem>
                  <VWindowItem>
                    <UploadFiles />

                  </VWindowItem>
                </VWindow>

              </VCol>
            </VRow>
          </VForm>


        </div>
      </VCardText>

    </VCard>


  </div>
</template>

<style scoped>
.submit-inspection-btn {
  background-color: #7c3aed !important;
  color: white;
}
</style>
