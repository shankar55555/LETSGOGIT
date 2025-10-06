<script setup>
import { useFetchStatusList } from "@/utils/common";
import { computed, nextTick, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import { VForm } from "vuetify/components/VForm";
import { VBtn } from "vuetify/lib/components/index.mjs";

const valid = ref(true);
const refForm = ref(false);
const route = useRoute();

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

const followupItem = ref({
  lead_prospect: "",
  call_status: "",
  call_summary: "",
  next_call_datetime: "",
  lead_id: "",
  client_id: "",
  need_site_visit: false,
  site_visit_datetime: "",
  site_visit_user_id: ""
});

const resetForm = () => {
  followupItem.value = {
    lead_prospect: "",
    call_status: "",
    call_summary: "",
    next_call_datetime: "",
    lead_id: props.type === QUOTATION_LEAD ? route.params.id : "",
    client_id: props.type === QUOTATION_CLIENT ? route.params.id : "",
    need_site_visit: false,
    site_visit_datetime: "",
    site_visit_user_id: ""
  }
}


watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val) {
      if (props.currentItem?.id) {
        followupItem.value = JSON.parse(JSON.stringify(props.currentItem))
      } else {
        resetForm()
      }
    }
  }
)

const emit = defineEmits(["update:isDrawerOpen", "submit", "statusChange", "refresh"]);

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
watch(() => followupItem.value.call_status, (newStatus) => {
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

    const payload = {
      lead_prospect: followupItem.value.lead_prospect,
      call_status: followupItem.value.call_status,
      call_summary: followupItem.value.call_summary,
      next_call_datetime: followupItem.value.next_call_datetime,
      lead_id: followupItem.value.lead_id || (props.type === QUOTATION_LEAD ? route.params.id : ""),
      client_id: followupItem.value.client_id || (props.type === QUOTATION_CLIENT ? route.params.id : ""),
      need_site_visit: followupItem.value.need_site_visit,
      site_visit_datetime: followupItem.value.need_site_visit ? followupItem.value.site_visit_datetime : null,
      site_visit_user_id: followupItem.value.need_site_visit ? followupItem.value.site_visit_user_id : null
    };

    const res = await $api(
      props.currentItem
        ? `/followup/${props.currentItem.id}`
        : `/followup`,
      {
        method: props.currentItem ? "PUT" : "POST",
        body: payload,
      }
    );

    if (res?.status === 200) {
      toast.success(res?.message || (props.currentItem ? "Follow Up updated successfully" : "Follow Up created successfully"));

      // Emit submit event with the response data
      emit("submit", res.data);

      // Emit refresh event
      emit("refresh", true);

      // Close the drawer
      emit("update:isDrawerOpen", false);

      // Reset the form
      resetForm();
      fetchFollowups();
      // Reset form validation
      await nextTick(() => {
        refForm.value?.reset();
        refForm.value?.resetValidation();
      });
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

const users = ref([])

onMounted(() => {
  fetchStatusList(MODULE_FOLLOW_UP);
  fetchUsers();
  fetchFollowups();
});

const { statusList, fetchStatusList } = useFetchStatusList();

const fetchUsers = async () => {
  try {
    const response = await $api("/dropdown-user-list");
    users.value = response.data ?? [];
  } catch (error) {
    toast.error(error?.response?.data?.message || "Error fetching user list.");
  }
};

const buildFollowupUrl = () => {
  const id = route.params.id;
  if (props.type === QUOTATION_LEAD) return `/followup?lead_id=${id}`;
  if (props.type === QUOTATION_CLIENT) return `/followup?client_id=${id}`;
  return '/followup';
};

const followups = ref([]);
const uniqueProspects = ref([]);
const select = ref(true);
const searchText = ref('');

// Handle search input updates
const handleSearchUpdate = (value) => {
  searchText.value = value;
};

// Add new prospect to the list
const addNewProspect = () => {
  if (searchText.value && !uniqueProspects.value.includes(searchText.value)) {
    uniqueProspects.value.push(searchText.value);
    followupItem.value.lead_prospect = searchText.value;
    searchText.value = '';

    // Show success message
    toast.success(`New prospect "${followupItem.value.lead_prospect}" added successfully`);
  }
};

// Filter prospects based on search text
const filteredProspects = computed(() => {
  if (!searchText.value) return uniqueProspects.value;

  return uniqueProspects.value.filter(prospect =>
    prospect.toLowerCase().includes(searchText.value.toLowerCase())
  );
});
const fetchFollowups = async () => {
  try {
    const response = await $api(buildFollowupUrl());
    followups.value = response?.data || [];

    const data = response.data; // array of objects
    uniqueProspects.value = [...new Set(data.map(item => item.lead_prospect))];
    select.value = uniqueProspects?.value?.length > 0 ? true : false;
    console.log(uniqueProspects);

  } catch (err) {
    console.error('Failed to fetch Follow ups:', err);
    toast.error('Failed to load Follow ups');
  }
};




</script>

<template>
  <div>
    <div v-if="isDrawerOpen" class="backdrop"></div>
    <VNavigationDrawer permanent :width="500" location="end" class="scrollable-content"
      :model-value="props.isDrawerOpen" @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection :title="props.currentItem ? 'Edit Follow Up' : 'Add Follow Up'"
        @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VSelect v-model="followupItem.call_status" :items="statusList" :rules="[requiredValidator]"
                  label="Call Status" placeholder="Select Status *" item-title="status_text" item-value="slug"
                  clearable />
              </VCol>
              <VCol cols="12">
                <VAutocomplete v-model="followupItem.lead_prospect" :items="filteredProspects"
                  :rules="[requiredValidator]" density="comfortable" label="Lead Prospect"
                  placeholder="Select Lead Prospect *" clearable @update:search="handleSearchUpdate">
                  <template #no-data>
                    <div class="pa-4 text-center">
                      <VIcon icon="tabler-plus" class="mb-2" />
                      <div>
                        No matching prospect found
                      </div>
                      <VBtn v-if="searchText" variant="outlined" size="small" class="mt-2" @click="addNewProspect">
                        Add "{{ searchText }}"
                      </VBtn>
                    </div>
                  </template>
                </VAutocomplete>

                <!-- <AppTextField v-else v-model="followupItem.lead_prospect" label="Lead Prospect" /> -->
              </VCol>
              <VCol cols="12">
                <AppDateTimePicker v-model="followupItem.next_call_datetime" label="Next Call Date & Time"
                  placeholder="Next Call Date & Time"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i', minDate: 'today' }"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="followupItem.call_summary" label="Call Summary" placeholder="Call Summary"
                  autofocus />
              </VCol>
              <!-- <VCol cols="12">
                <VRadioGroup v-model="followupItem.need_site_visit" inline label="Need To Visit Site?" mandatory>
                  <VRadio label="No" :value="false" />
                  <VRadio label="Yes" :value="true" />
                </VRadioGroup>
              </VCol> -->

              <!-- <template v-if="followupItem.need_site_visit">
                <VCol cols="12">
                  <AppDateTimePicker
                    v-model="followupItem.site_visit_datetime"
                    label="Site Visit Date & Time"
                    placeholder="Select site visit date and time"
                    :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }"
                  
                  />
                </VCol>
                <VCol cols="12">
                  <VSelect
                    v-model="followupItem.site_visit_user_id"
                    :items="users"
                    label="Assign User for Site Visit"
                    placeholder="Select user"
                    item-title="name"
                    item-value="uuid"
                    :loading="loading"
                    clearable
                  >
                    <template #item="{ props, item }">
                      <VListItem v-bind="props" :title="item.raw.name" :subtitle="item.raw.email" />
                    </template>
</VSelect>
</VCol> -->
              <!-- </template> -->

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
