<template>
  <div>
    <VNavigationDrawer :permanent="true" :width="500" location="end" class="scrollable-content"
      :model-value="props.isDialogVisible" @update:model-value="handleDrawerModelValueUpdate" :scrim="false"
      :close-on-back="false" disable-resize-watcher>
      <AppDrawerHeaderSection :title="currentInfo ? 'Edit Status' : 'Add Status'" @cancel="closeNavigationDrawer" />
      <VDivider />

      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Name -->
              <VCol cols="12">
                <AppTextField v-model="page_status.status_text" :rules="requiredRule" label="Name *" placeholder="Name"
                  autofocus :readonly="is_predefined" :disabled="is_predefined" />
              </VCol>

              <!-- Pages -->
              <VCol cols="12">
                <AppSelect :items="pageList" v-model="page_status.status_for" modelChange label="Select Page"
                  placeholder="Select pages" closable :rules="requiredRule" :disabled="props.currentInfo !== null" />
              </VCol>

              <!-- triggers -->
              <VCol cols="12" v-if="page_status.status_for.length > 0">
                <VSelect :items="filterTriggers" v-model="page_status.trigger_action" label="Select Trigger Action"
                  multiple placeholder="Select trigger Action" :clearable="!!page_status.trigger_action" />
              </VCol>

              <!-- Send Plat Forms -->
              <VCol cols="12"
                v-if="page_status.trigger_action.length > 0 && page_status.trigger_action.some(trigger => SEND_PLAT_FROM.includes(trigger))">
                <VSelect :items="[EMAIL, WHATSAPP]" v-model="page_status.send_plat_forms" label="Select Plat Form"
                  multiple placeholder="Trigger Send File" :clearable="!!page_status.send_plat_forms" />
              </VCol>

              <!-- Position -->
              <VCol cols="12">
                <AppTextField v-model="page_status.position" :rules="[requiredRule, nonZeroPositionRule]"
                  label="Position" type="number" placeholder="Enter position" :readonly="is_predefined"
                  :disabled="is_predefined" />
              </VCol>

              <!-- Color -->
              <VCol cols="12">
                <AppTextField v-model="page_status.status_color" :rules="requiredRule" label="Color" type="color" />
              </VCol>

              <!-- Submit -->
              <VCol cols="12">
                <VBtn type="submit" class="me-3" :loading="isLoading"> {{ currentInfo ? "Update" : "Submit" }} </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCard>
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>
<script setup>
import AppTextField from "@/@core/components/app-form-elements/AppTextField.vue";
import { nonZeroPositionRule, requiredRule } from "@/validations/validationRules";
import { nextTick, onMounted, ref } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import { VForm } from "vuetify/components/VForm";

const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  currentInfo: { type: Object, default: null },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const refForm = ref(null);
const valid = ref(true);
const pageList = ref([]);
const triggers = ref({}) // filled from API
const isLoading = ref(false);
let isSubmitting = false;
const errors = ref({});
const is_predefined = ref(false);

const page_status = ref({
  id: null,
  status_for: '',
  status_text: '',
  status: true,
  position: 1,
  invoice_footer_text: '',
  contract_footer_text: '',
  status_color: '#b7c8bd',
  trigger_action: [],
  send_plat_forms: [],
  is_predefined: true
});


// This computed will return an array of actions for the selected page
const filterTriggers = computed(() => {
  return triggers.value[page_status.value.status_for] || []
})

onMounted(() => {
  errors.value = {};
  if (props.currentInfo) {
    const { currentInfo } = props;
    page_status.value = {
      id: currentInfo.id,
      status_for: currentInfo.status_for,
      status_text: currentInfo.status_text,
      status: currentInfo.position > 0 ? true : false,  // true -> active , false -> in-active
      position: currentInfo.position,
      status_color: currentInfo.status_color || '#b7c8bd',
      invoice_footer_text: currentInfo.invoice_footer_text,
      contract_footer_text: currentInfo.contract_footer_text,
      trigger_action: currentInfo.trigger_action,
      send_plat_forms: currentInfo.send_plat_forms,
      is_predefined: currentInfo.is_predefined
    };
    is_predefined.value = currentInfo.is_predefined == 1 ? false : true;
  } else {
    resetFieldInfo();
    page_status.value.status = true;
    is_predefined.value = false;
  }
  fetchPageList();
});

watch(() => page_status.value.status_for, (newValue, oldValue) => {
  page_status.value.trigger_action = [];
  page_status.value.send_plat_forms = [];
});

watch(() => page_status.value.trigger_action, (newValue) => {
  const hasValidTrigger = newValue && newValue.some(trigger => SEND_PLAT_FROM.includes(trigger));
  if (!hasValidTrigger) {
    page_status.value.send_plat_forms = [];
  }
});

const fetchPageList = async () => {
  try {
    const response = await $api('/settings/page', { params: { type: 'list' } });
    const pages = response.data.data;
    const excludedPages = ['B2B', 'Notification Log', 'Export Log', 'Attendance', 'Rule'];
    pageList.value = pages.filter(page => !excludedPages.includes(page));
    triggers.value = response.data.triggers;
  } catch (error) {
    console.error(error);
    // toast.error(error?.response?.data?.message || 'Failed to fetch pages');
  }
};

const resetFieldInfo = () => {
  page_status.value = {
    id: null,
    status_for: '',
    status_text: '',
    status: true,
    position: 1,
    invoice_footer_text: '',
    contract_footer_text: '',
    status_color: '#b7c8bd',
    trigger_action: [],
    send_plat_forms: [],
    is_predefined: true
  };
  is_predefined.value = false;
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

const closeNavigationDrawer = () => {
  emit("update:isDialogVisible", false);
  resetFieldInfo();
};

const handleDrawerModelValueUpdate = (val) => {
  if (!val) return;
  emit("update:isDialogVisible", val);
};

const onSubmit = async () => {
  if (isSubmitting) return;
  isSubmitting = true;

  const { valid } = await refForm.value.validate();
  if (!valid) {
    isSubmitting = false;
    return;
  }

  isLoading.value = true;

  try {
    const url = props.currentInfo ? `/settings/page-status-update/${props.currentInfo.id}?_method=PUT` : `/settings/page-status-create`;

    let obj = {
      id: page_status.value.id,
      status_for: props.currentInfo ? [props.currentInfo.status_for] : [page_status.value.status_for],
      status_text: props.currentInfo ? props.currentInfo.status_text : page_status.value.status_text,
      status: page_status.value.position > 0 ? true : false,
      position: props.currentInfo ? props.currentInfo.position : page_status.value.position,
      status_color: page_status.value.status_color || '#b7c8bd',
      invoice_footer_text: page_status.value.invoice_footer_text,
      contract_footer_text: page_status.value.contract_footer_text,
      trigger_action: page_status.value.trigger_action,
      send_plat_forms: page_status.value.send_plat_forms,
      is_predefined: page_status.value.is_predefined
    };

    const res = await $api(url, { method: 'POST', body: obj, });
    toast.success(res.message);
    emit('submit');
    closeNavigationDrawer();
  } catch (err) {
    console.error(err);
    toast.error(err?._data?.message || 'An unexpected error occurred');
  } finally {
    isLoading.value = false;
    isSubmitting = false;
  }
};

</script>

<style scoped>
.department_card {
  padding: 20px;
}
</style>
