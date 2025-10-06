<template>
  <VDialog max-width="800" :model-value="props.isDrawerOpen" @update:model-value="updateModelValue" scrollable
    persistent>
    <VCard>
      <!-- Header -->
      <VCardItem>
        <VRow align="center" justify="space-between">
          <VCol cols="6">
            <h4 class="text-h5 font-weight-medium">Create Notification</h4>
          </VCol>
          <VCol cols="6" class="d-flex justify-end">
            <IconBtn @click="updateModelValue(false)" icon="tabler-x" color="default" />
          </VCol>
        </VRow>
      </VCardItem>
      <VDivider class="my-1" />

      <VCardText class="pt-1">
        <VForm ref="refForm" v-model="valid" lazy-validation @submit.prevent="onSubmit">
          <!-- Basic Info -->
          <VRow class="mt-5">
            <VCol cols="12">
              <VTextField v-model="template.title" :rules="[requiredValidator]" placeholder="Title" outlined dense>
                <template #label>Title <span class="text-error">*</span></template>
              </VTextField>
            </VCol>
            <VCol cols="12">
              <VTextField v-model="template.email_subject" :rules="[requiredValidator]" placeholder="Enter Subject"
                outlined dense>
                <template #label>Subject <span class="text-error">*</span></template>
              </VTextField>
            </VCol>
            <!-- Category -->
            <VCol cols="12" v-if="!template.category">
              <VSelect v-model="template.notification_category_id" :items="categoryList" item-title="category"
                item-value="id" :rules="[requiredValidator]" @update:model-value="updateCategoryResetFiled"
                @input="template.category = ''">
                <template #label>Select Category <span class="text-error">*</span></template>
              </VSelect>
            </VCol>

            <VCol cols="12" v-if="!template.notification_type_id">
              <VTextField v-model="template.type_title" :rules="[requiredValidator]" placeholder="Enter Type Title"
                outlined dense @input="template.notification_type_id = ''">
                <template #label>New Type Title <span class="text-error">*</span></template>
              </VTextField>
            </VCol>
            <VCol cols="12" v-if="!template.notification_type_id">
              <VTextField v-model="template.description" :rules="[requiredValidator]" placeholder="Enter Description"
                outlined dense @input="template.notification_type_id = ''">
                <template #label>New Description <span class="text-error">*</span></template>
              </VTextField>
            </VCol>
          </VRow>

          <!-- Notification Sections -->
          <template v-for="{ label, field, type } in notificationFields" :key="type">
            <VRow>
              <VCol cols="12" v-if="variableList.length > 0" :class="type !== 'email' ? 'custom_top_margin' : ''">
                <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                  @click="setVariableContent(val, type)">
                  {{ val.variables }}
                </VChip>
              </VCol>
              <VCol cols="12" :class="type !== 'email' && variableList.length == 0 ? 'custom_top_margin' : ''">
                <VLabel>{{ label }} <span class="text-error">*</span></VLabel>
                <QuillEditor v-model:content="template[field]" content-type="html" theme="snow"
                  :rules="[requiredValidator]" @update:content="validateField(field, label)" />
                <span v-if="errors[field]" class="text-error text-caption mt-2 mb-2">{{ errors[field] }}</span>
              </VCol>
            </VRow>
          </template>

          <!-- Hidden Pre Header -->
          <VRow>
            <VCol cols="12" class="custom_top_margin">
              <VLabel>Hidden Pre Header <span class="text-error">*</span></VLabel>
              <VTextField v-model="template.hidden_pre_header" :rules="[requiredValidator]"
                placeholder="Pre Header Text" outlined dense hide-details />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <!-- Footer Actions -->
      <VCardActions class="mt-10 mb-3">
        <VSpacer />
        <VBtn v-if="$can('email', 'edit')" variant="elevated" @click="saveData" :loading="loadingSave"
          :disabled="loadingSave || !valid">
          Save Changes
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { onMounted, ref } from 'vue';
import { toast } from 'vue3-toastify';

// Props
const props = defineProps({
  module: { type: String, required: true },
  isDrawerOpen: { type: Boolean, required: true },
  currentInfo: { type: Object, default: null },
});

// Emits
const emit = defineEmits(['callToFunction', 'update:isDrawerOpen']);

// Refs
const valid = ref(true);
const refForm = ref(null);
const categoryList = ref([]);
const typeList = ref([]);
const variableList = ref([]);
const templatesList = ref([]);
const loadingSave = ref(false);
const errors = ref({});

// Notification fields configuration
const notificationFields = [
  { label: 'Email Body Content', field: 'email_body', type: 'email' },
  { label: 'Whats App Content', field: 'whats_app_message', type: 'whats_app' },
  { label: 'Bell Notification Content', field: 'bell_notification_message', type: 'bell' },
];

// Template model
const template = ref({
  id: '',
  notification_category_id: '',
  category: '',
  notification_type_id: '',
  type_title: '',
  description: '',
  title: '',
  is_enable: true,
  email_subject: '',
  hidden_pre_header: '',
  priority: 'High',
  email_body: '',
  whats_app_message: '',
  bell_notification_message: '',
  sms_message: '',
  app_message: '',
  variables: [],
});

// Validation rule
const requiredValidator = (value) => {
  if (!value || value.trim() === '' || value === '<p><br></p>') {
    return 'This field is required';
  }
  return true;
};

// Validate a single field
const validateField = (field, label) => {
  const result = requiredValidator(template.value[field], label);
  if (result !== true) {
    errors.value[field] = result;
  } else {
    delete errors.value[field];
  }
};

// Methods
const updateModelValue = (val) => emit('update:isDrawerOpen', val);

const updateCategoryResetFiled = () => {
  template.value.category = '';
  template.value.notification_type_id = '';
  template.value.type_title = '';
  template.value.description = '';
  template.value.hidden_pre_header = '';
  template.value.email_body = '';
  template.value.whats_app_message = '';
  template.value.bell_notification_message = '';
  template.value.sms_message = '';
  template.value.app_message = '';
  template.value.variables = [];

  dropdownNoficationList();
}

const dropdownNoficationList = async () => {
  try {
    const payload = { notification_category_id: template.value.notification_category_id }
    const response = await $api('/dropdown-nofication-list', { method: 'POST', body: payload })
    categoryList.value = template.value.notification_category_id ? categoryList.value : response.data.categories || []
    typeList.value = response.data.notification_types || []
    variableList.value = response.data.variables || []
    templatesList.value = response.data.categories || []
  } catch (err) {
    toast.error(err._data?.message ?? 'Request failed')
  }
}

const setVariableContent = (item, type) => {
  const keyMap = {
    email: 'email_body',
    whats_app: 'whats_app_message',
    bell: 'bell_notification_message',
    sms: 'sms_message',
    app: 'app_message',
  };
  const field = keyMap[type];
  if (!field) return;

  let variable = item.variables;
  const templateText = variable.includes('_link') ? '[[***name***]]' : '[[**name**]]';
  variable = variable.replace(/^copy_/, '');

  const message = `${template.value[field] || ''} ${templateText.replace('name', variable)}`.trim();
  template.value[field] = message;
};

const onSubmit = async () => {
  if (loadingSave.value) return;

  // Reset errors
  errors.value = {};

  // Validate QuillEditor fields
  notificationFields.forEach(({ field, label }) => {
    validateField(field, label);
  });

  // Vuetify form validation
  const { valid: isValid } = await refForm.value.validate();
  if (!isValid || Object.keys(errors.value).length) return;

  loadingSave.value = true;
  try {
    const payload = { ...template.value };
    const response = await $api('/create-notification', { method: 'POST', body: payload });
    toast.success(response.message || 'Operation successful!');
    emit('update:isDrawerOpen', false);
    emit('callToFunction', payload);
    resetForm();
  } catch (error) {
    const apiErrors = error._data?.errors || error?.errors || {};
    Object.assign(errors.value, apiErrors);
    toast.error(error._data?.message ?? 'Submission failed.');
  } finally {
    loadingSave.value = false;
  }
};

const resetForm = () => {
  Object.assign(template.value, {
    id: '',
    notification_category_id: '',
    category: '',
    notification_type_id: '',
    type_title: '',
    description: '',
    title: '',
    is_enable: false,
    email_subject: '',
    hidden_pre_header: '',
    priority: 'High',
    email_body: '',
    whats_app_message: '',
    bell_notification_message: '',
    sms_message: '',
    app_message: '',
    variables: [],
  });
  refForm.value?.reset();
  refForm.value?.resetValidation();
  errors.value = {};
};

const saveData = () => onSubmit();

onMounted(dropdownNoficationList);
</script>

<style scoped>
.text-error {
  color: red;
}
</style>
