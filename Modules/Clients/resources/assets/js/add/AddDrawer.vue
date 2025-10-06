<script setup>
import { useFetchStatusList } from "@/utils/common";
import _ from "lodash";
import { computed, onMounted, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import ConfirmDialog from '../../../../../../resources/js/components/dialogs/GstConfirmDialg.vue';
import { emailRule, inputNumberRestrict, onlyAlphabetsRule, optionalRequiredRule, requiredRule, validateGSTIN, validateMobileNumber } from "../validations/validationRules";


const valid = ref(true);
const refForm = ref(null);

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  clients: {
    type: Array,
    default: [],
  },
  currentClient: {
    type: Object,
    default: null,
  },
});
const client = ref({
  name: "",
  contact_person: "",
  contact_person_role: "",
  type: "customer",
  gst: "",
  email: "",
  phone: "",
  secondary_phone: [],
  assign_to: "",
  status: "active",
  assigned_user: null,
  date_of_birth: "",
  anniversary_date: "",
  city_id: null,
});
const userList = ref([]);
const cityList = ref([]);
const loading = ref(false);
const loadingCities = ref(false);
onMounted(() => {
  fetchStatusList(MODULE_CLIENT);
  fetchUserList();
  if (props.currentClient?.id) {
    client.value = _.cloneDeep(props.currentClient);
    client.value.city_id = "";
    fetchCityById(props.currentClient.city_id)
  } else {
    fetchCityList();
  }
});

const fetchUserList = async () => {
  try {
    const response = await $api("/dropdown-user-list");
    userList.value = response.data ?? [];
    const adminRole = userList.value.find(role => role.name === "Admin");
    if (adminRole) {
      client.value.assigned_user = adminRole.uuid;
    } else {
      // Handle case where "Admin" role is not found
      console.error("Admin role not found");
    }
  } catch (error) {
    toast.error(error?.response?.data?.message || "Error fetching user list.");
  }
};

// const fetchStatusList = async () => {
//   loading.value = true;
//   try {
//     const params = {
//       type: MODULE_CLIENT,
//     };
//     const response = await $api("/settings/status-list", { params });
//     const { data } = response.data;
//     statusList.value = data ?? [];
//   } catch (error) {
//     console.error("Error fetching status list:", error);
//     toast.error(
//       error?.response?.data?.message || "Error fetching status list."
//     );
//   } finally {
//     loading.value = false;
//   }
// };

const fetchCityList = async (search = '') => {
  loadingCities.value = true;
  try {
    const res = await $api('/city-list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ search }),
    });
    cityList.value = res.data ?? [];
  } catch (e) {
    toast.error('Failed to load cities');
  } finally {
    loadingCities.value = false;
  }
};

const fetchCityById = async (id) => {
  try {
    const res = await $api('/city-list/' + id)
    cityList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load city detail')
  } finally {
    client.value.city_id = id;
  }
}


const { statusList, fetchStatusList } = useFetchStatusList();
const emit = defineEmits(["update:isDrawerOpen", "submit"]);
// 👉 drawer close
const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};

const isLoading = ref(false);
let isSubmitting = false;
const gstDialog = ref(false);

const handleSubmitForm = async () => {
  if (!client.value.gst || client.value.gst.trim() === '') {
    gstDialog.value = true;
    return;
  }

  // If GST is provided, proceed with form submission
  await onSubmit();
};

const handleGstDialogConfirm = async (confirmed) => {
  gstDialog.value = false;

  if (confirmed) {
    // User clicked "Yes" - proceed without GST
    await onSubmit();
  }
  // If user clicked "No", just close the dialog (do nothing)
};



const onSubmit = async () => {
  if (isSubmitting) return;
  isSubmitting = true;
  let { valid, errors } = await refForm.value.validate();
  if (!valid) {
    isSubmitting = false;
    return false;
  }
  try {
    isLoading.value = true;
    const payload = {
      ...client.value,
      assigned_user: client.value.assigned_user,
    };
    const res = await $api(
      props.currentClient
        ? `/clients/${props.currentClient.id}?_method=PUT`
        : `/clients`,
      {
        method: "POST",
        body: payload,
      }
    );
    if (res?.status === 200) {
      toast.success(res?.message);
      // Close the modal and reset form
      emit("update:isDrawerOpen", false);
      emit("submit");
      // Reset form data
      client.value = {
        name: "",
        contact_person: "",
        contact_person_role: "",
        type: "customer",
        email: "",
        gst: "",
        phone: "",
        secondary_phone: [],
        assign_to: "",
        status: "active",
        assigned_user: null,
        date_of_birth: "",
        anniversary_date: "",
        city_id: null,
      };
      // Reset form validation
      await nextTick(() => {
        refForm.value?.reset();
      });
      isLoading.value = false;
    } else {
      toast.error(res?.message);
      isLoading.value = false;
    }
  } catch (err) {
    // Handle errors and show toast
    console.error("Error:", err);
    isLoading.value = false;
    toast.error(err?._data.message || "An unexpected error occurred");
  } finally {
    // Always unlock submitting state
    isSubmitting = false;
    isLoading.value = false;
  }
  isSubmitting = false;
};
const handleMobileInput = (event) => {
  client.value.phone = inputNumberRestrict(event.target.value, 10);
};
const convertToLowerCase = (event) => {
  client.value.email = event.target.value.toLowerCase();
};
const clientName = computed({
  get: () => (client.value.name ? client.value.name.split(" - (")[0] : ""), // Extract company name safely
  set: (newValue) => (client.value.name = newValue), // Allow editing and updating client.name
});

function removeToolChecklistTag(index) {
  client.value.secondary_phone.splice(index, 1)
}

// Add watcher to fetch city list when drawer opens
watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val) {
      fetchCityList();
    }
  }
);
const typeList = ref([
  {
    type_text: 'D to C',
    slug: 'customer'
  },
  {
    type_text: 'B to B',
    slug: 'business'
  }
])
</script>
<template>
  <div>
    <div v-if="isDrawerOpen" class="backdrop"></div>
    <VNavigationDrawer permanent :width="800" location="end" class="scrollable-content"
      :model-value="props.isDrawerOpen" @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection :title="currentClient ? 'Edit Client' : 'Add Client'" @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="handleSubmitForm">
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField v-model="clientName" :rules="requiredRule" label="Name" placeholder="Name" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="client.type" :items="typeList" label="Type" placeholder="Select Type *"
                  item-title="type_text" item-value="slug" clearable />
              </VCol>
              <VCol cols="12" md="6" v-if="client.type === 'business'">
                <AppTextField :rules="onlyAlphabetsRule" v-model="client.contact_person" label="Contact Person"
                  placeholder="Contact Person" />
              </VCol>
              <VCol cols="12" md="6" v-if="client.type === 'business'">
                <AppTextField v-model="client.contact_person_role" label="Contact Person Role"
                  placeholder="Contact Person Role" autofocus />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="client.gst" label="GST" placeholder="GST Number" autofocus
                  :rules="validateGSTIN" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="client.email" :rules="emailRule" @input="convertToLowerCase" label="Email"
                  placeholder="Email" type="email" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="client.phone" :rules="[optionalRequiredRule, ...validateMobileNumber]"
                  @input="handleMobileInput" label="Phone *" placeholder="Phone" />
              </VCol>

              <VCol cols="12" md="6">
                <label for="">Secondary phone</label>
                <VCombobox v-model="client.secondary_phone" multiple :items="[]" chips placeholder="Add multiple number"
                  hint="Enter number and press enter">
                  <template v-slot:chip="{ item, index }">
                    <VChip class="ma-1" color="primary">
                      {{ item.raw }}
                      <v-icon @click="removeToolChecklistTag(index)" class="ml-1" size="large"
                        icon="tabler-circle-letter-x"></v-icon>
                    </VChip>
                  </template>
                </VCombobox>
              </VCol>


              <VCol cols="12" md="6" v-show="false">
                <VSelect v-model="client.status" :items="statusList" label="Status" placeholder="Select Status *"
                  item-title="status_text" item-value="slug" clearable />
              </VCol>



              <!-- <VCol cols="12" md="6">
                <AppSelect v-model="client.assigned_user" :items="userList" item-title="name" item-value="uuid"
                  label="Assigned To*" placeholder="Select User" />
              </VCol> -->

              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="client.date_of_birth" label="Date of Birth"
                  placeholder="Select Date of Birth" :config="{
                    enableTime: false,
                    dateFormat: 'Y-m-d',
                  }" />
              </VCol>

              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="client.anniversary_date" label="Anniversary Date"
                  placeholder="Select Anniversary Date" :config="{
                    enableTime: false,
                    dateFormat: 'Y-m-d'
                  }" />
              </VCol>

              <VCol cols="12" md="6">
                <VLabel>City <span style="color: red;">*</span></VLabel>
                <AppAutocomplete v-model="client.city_id" :items="cityList" item-title="name" item-value="id"
                  :loading="loadingCities" :searchable="true" @update:search="fetchCityList" placeholder="Search City"
                  :rules="requiredRule" />
              </VCol>

              <VCol cols="12">
                <VBtn type="submit" class="me-3" color="primary" :loading="isLoading">
                  {{ currentClient ? "Update" : "Submit" }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCard>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <ConfirmDialog v-model:isDialogVisible="gstDialog" confirm-title="Continue without GST"
      confirmation-question="Are you sure you want to continue without adding GST?"
      confirm-msg="Your settings will be saved without GST number." cancel-title="Add GST"
      cancel-msg="Please add GST number before saving." @confirm="handleGstDialogConfirm" />
  </div>
</template>
<style scoped>
.department_card {
  padding: 20px;
}
</style>
