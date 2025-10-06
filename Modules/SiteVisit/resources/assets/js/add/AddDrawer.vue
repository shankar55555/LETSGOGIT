<script setup>
import { statusFilterPosition, useFetchStatusList } from "@/utils/common";
import { ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import { VForm } from "vuetify/components/VForm";
import { VBtn, VRadioGroup } from "vuetify/lib/components/index.mjs";
const valid = ref(true);
const refForm = ref(false);
const date = ref('')
const route = useRoute();
const userList = ref([])
const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  currentItem: {
    type: [Object, null],
    default: null,
    required: true,
  },
  type: {
    type: String,
    default: null,
    validator: (value) => [QUOTATION_LEAD, QUOTATION_CLIENT].includes(value)
  }
});
const siteVisitItem = ref({
  visit_type: 'inspection',
  products: [],
  visit_time: "",
  visit_assignee: "",
  status: "",
  visit_notes: "",
  lead_id: "",
  client_id: "",
});
const resetForm = () => {
  siteVisitItem.value = {
    visit_type: 'inspection',
    products: [],
    visit_time: "",
    visit_assignee: "",
    status: "",
    visit_notes: "",
    lead_id: "",
    client_id: "",
  }
}
watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val) {
      if (props.currentItem?.id) {
        siteVisitItem.value = JSON.parse(JSON.stringify(props.currentItem))
        date.value = siteVisitItem.value.visit_time;
      } else {
        resetForm()
      }
    }
  }
)
const emit = defineEmits(["update:isDrawerOpen", "submit", "statusChange"]);
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
// Add watch on status changes
watch(() => siteVisitItem.value.status, (newStatus) => {
  if (newStatus) {
    // Emit status change event to parent
    emit("statusChange", newStatus);
  }
});
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
    // Get the ID from the route based on type
    const routeId = route.params.id;
    const payload = {
      visit_time: date.value,
      visit_type: siteVisitItem.value.visit_type,
      products: siteVisitItem.value.products,
      visit_assignee: siteVisitItem.value.visit_assignee,
      status: siteVisitItem.value.status,
      visit_notes: siteVisitItem.value.visit_notes,
    };

    // Add the appropriate ID based on type
    if (props.type === QUOTATION_LEAD) {
      payload.lead_id = routeId;
    } else if (props.type === QUOTATION_CLIENT) {
      payload.client_id = routeId;
    }
    const res = await $api(
      props.currentItem
        ? `/site-visit/${props.currentItem.id}`
        : `/site-visit`,
      {
        method: props.currentItem ? "PUT" : "POST",
        body: payload,
      }
    );
    if (res?.status === 200) {
      toast.success(res?.message || (props.currentItem ? "Site visit updated successfully" : "Site visit created successfully"));
      // Emit submit event with the response data for reload
      emit("submit", res.data);
      // Close the drawer first
      emit("update:isDrawerOpen", false);
      // Only reset form if it's a new entry, not an update
      if (!props.currentItem) {
        siteVisitItem.value = {
          visit_type: 'inspection',
          products: [],
          visit_time: "",
          visit_assignee: "",
          status: "",
          visit_notes: "",
          lead_id: "",
          client_id: "",
        };
        date.value = "";
        // Reset form validation
        await nextTick(() => {
          refForm.value?.reset();
          refForm.value?.resetValidation();
        });
      }
    } else {
      toast.error(res?.message || "An error occurred");
    }
  } catch (err) {
    console.error("Error:", err);
    toast.error(err?._data?.message || "An unexpected error occurred");
  } finally {
    isSubmitting = false;
    isLoading.value = false;
  }
};
onMounted(() => {
  fetchStatusList(MODULE_SITE_VISIT);
  fetchUserList();
  fetchProductServices();
});

const fetchUserList = async () => {
  try {
    const response = await $api("/dropdown-user-list");
    userList.value = response.data ?? [];
  } catch (error) {
    toast.error(error?.response?.data?.message || "Error fetching user list.");
  }
};

const { statusList, fetchStatusList } = useFetchStatusList();

const attributeItems = ref([])
const fetchProductServices = async (search = '') => {
  try {
    const { data } = await $api('/product', {
      params: { search },
    })
    attributeItems.value = data // adapt if your API response is shaped differently
  } catch (e) {
    console.error('Failed to load attributeItems', e)
  }
}

</script>
<template>
  <div>
    <div v-if="isDrawerOpen" class="backdrop"></div>
    <VNavigationDrawer permanent :width="600" location="end" class="scrollable-content"
      :model-value="props.isDrawerOpen" @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection :title="props.currentItem ? 'Edit Site Visit' : 'Add Site Visit'"
        @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VRadioGroup v-model="siteVisitItem.visit_type" inline label="Visit Type">
                  <VRadio v-for="type in ['inspection', 'installation', 'other']" :key="type"
                    :label="type.charAt(0).toUpperCase() + type.slice(1)" :value="type" color="primary" />
                </VRadioGroup>
              </VCol>

              <VCol cols="12" md="12" v-if="siteVisitItem.visit_type != 'other'">
                <AppAutocomplete label="Product/Service" :items="attributeItems" item-title="name" item-value="id"
                  v-model="siteVisitItem.products" placeholder="Choose Multiple Product/Service" multiple clearable />
              </VCol>

              <VCol cols="12">
                <AppDateTimePicker v-model="date" label="Date & Time" placeholder="Select date and time"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i', minDate: 'today' }"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12">
                <VSelect v-model="siteVisitItem.status"
                  :items="props.currentItem ? statusFilterPosition(statusList, props.currentItem?.status) : statusList"
                  :rules="[requiredValidator]" label="Status" placeholder="Select Status *" item-title="status_text"
                  item-value="slug" clearable />
              </VCol>
              <VCol cols="12">
                <AppSelect v-model="siteVisitItem.visit_assignee" :items="userList" item-title="name" item-value="uuid"
                  label="Assigned To*" placeholder="Select User" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="siteVisitItem.visit_notes" label="Visit Notes" placeholder="Visit Notes"
                  autofocus />
              </VCol>
              <VCol cols="12">
                <VBtn type="submit" class="me-3" :loading="isLoading">
                  {{ props.currentItem ? "Update" : "Submit" }}
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
.department_card {
  padding: 20px;
}

.small-textarea {
  flex-grow: 1;
}

.addresAdd {
  padding: 4px;
  cursor: pointer;
}
</style>
