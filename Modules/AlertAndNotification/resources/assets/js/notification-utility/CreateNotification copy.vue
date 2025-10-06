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
        <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
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

            <!-- <VCol cols="12">
              <VSwitch v-model="template.is_enable" label="Template Active" />
            </VCol> -->

            <!-- Category -->
            <VCol cols="12" v-if="!template.category">
              <VSelect v-model="template.notification_category_id" :items="categoryList" item-title="category"
                item-value="id" :rules="[requiredValidator]" @update:model-value="updateCategoryResetFiled"
                @input="template.category = ''">
                <template #label>Select Category <span class="text-error">*</span></template>
              </VSelect>
            </VCol>
            <!-- <div v-if="!template.category && !template.notification_category_id"
              style="margin-inline-start: 14px !important;">
              Or
            </div>
            <VCol cols="12" v-if="!template.notification_category_id">
              <VTextField v-model="template.category" @input="template.notification_category_id = ''"
                :rules="[requiredValidator]" placeholder="Enter Category" outlined dense>
                <template #label>New Category <span class="text-error">*</span></template>
              </VTextField>
            </VCol> -->

            <!-- Notification Type -->
            <!-- <VCol cols="12" v-if="!template.type_title && !template.description">
              <VSelect v-model="template.notification_type_id" :items="typeList" item-title="title" item-value="id"
                :rules="[requiredValidator]" @input="() => { template.type_title = ''; template.description = ''; }">
                <template #label>Select Type <span class="text-error">*</span></template>
              </VSelect>
            </VCol>
            <div v-if="!template.type_title && !template.description && !template.notification_type_id"
              style="margin-inline-start: 14px !important;"> Or
            </div> -->
            <VCol cols="12" v-if="!template.notification_type_id">
              <VTextField v-model="template.type_title" :rules="[requiredValidator]" placeholder="Enter Type Title"
                outlined dense @input="template.notification_type_id = ''">
                <template #label>New Type Title <span class="text-error">*</span></template>
              </VTextField>
            </VCol>
            <VCol cols="12" v-if="!template.notification_type_id">
              <VTextField v-model="template.description" :rules="[requiredValidator]" placeholder="Enter Description"
                @input="template.notification_type_id = ''" outlined dense><template #label>New Description <span
                    class="text-error">*</span></template></VTextField>
            </VCol>
          </VRow>

          <!-- Email Notification -->
          <VRow>
            <VCol cols="12">
              <VLabel>Hidden Pre Header <span style="color: red;">*</span></VLabel>
              <VTextField v-model="template.hidden_pre_header" :rules="[requiredValidator]"
                placeholder="Pre Header Text" outlined dense hide-details />
            </VCol>

            <VCol cols="12" v-if="variableList.length > 0">
              <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                @click="setVariableContent(val, 'email')">
                {{ val.variables }}
              </VChip>
            </VCol>

            <VCol cols="12">
              <VLabel>Email Body Content <span style="color: red;">*</span></VLabel>
              <QuillEditor v-model:content="template.email_body" content-type="html" theme="snow" />
              <span v-if="emailBodyError" class="text-error text-caption">Email body is required.</span>
            </VCol>
          </VRow>

          <!-- Whats App Notification-->
          <VRow>
            <VCol class="custom_top_margin" cols="12">
              <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                @click="setVariableContent(val, 'whats_app')">
                {{ val.variables }}
              </VChip>
            </VCol>

            <VCol cols="12">
              <VLabel>Whats App Content <span style="color: red;">*</span></VLabel>
              <QuillEditor v-model:content="template.whats_app_message" content-type="html" theme="snow" />
              <span v-if="whatsAppError" class="text-error text-caption">WhatsApp content is required.</span>
            </VCol>
          </VRow>

          <!-- Bell Notification Content -->
          <VRow>
            <VCol class="custom_top_margin" cols="12">
              <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                @click="setVariableContent(val, 'bell')">
                {{ val.variables }}
              </VChip>
            </VCol>

            <VCol cols="12">
              <VLabel>Bell Notification Content <span style="color: red;">*</span></VLabel>
              <QuillEditor v-model:content="template.bell_notification_message" content-type="html" theme="snow" />
              <span v-if="bellContentError" class="text-error text-caption">Bell notification is required.</span>
            </VCol>
          </VRow>

          <!-- Sms Notification-->
          <!-- <VRow class="mt-5">
            <VCol class="custom_top_margin" cols="12" v-if="variableList.length > 0">
              <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                @click="setVariableContent(val, 'sms')">
                {{ val.variables }}
              </VChip>
            </VCol>

            <VCol cols="12">
              <VLabel>Sms Content <span style="color: red;">*</span></VLabel>
              <QuillEditor v-model:content="template.sms_message" :rules="[requiredValidator]" content-type="html"
                theme="snow">
              </QuillEditor>
            </VCol>
          </VRow> -->

          <!-- App Notification-->
          <!-- <VRow class="mt-5" >
            <VCol class="custom_top_margin" cols="12" v-if="variableList.length > 0">
              <VChip v-for="val in variableList" :key="`template-${val.id}`" class="ma-2"
                @click="setVariableContent(val, 'app')">
                {{ val.variables }}
              </VChip>
            </VCol>

            <VCol cols="12">
              <VLabel>App Content <span style="color: red;">*</span></VLabel>
              <QuillEditor v-model:content="template.app_message" :rules="[requiredValidator]" content-type="html"
                theme="snow" />
            </VCol>
          </VRow> -->
        </VForm>
      </VCardText>

      <!-- Footer Actions -->
      <VCardActions class="mt-10 mb-3">
        <VSpacer />
        <VBtn v-if="$can('email', 'edit')" variant="elevated" @click="saveData" :loading="loadingSave"
          :disabled="loadingSave">
          Save Changes
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
<script setup>
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { onMounted, ref } from 'vue'
import { toast } from 'vue3-toastify'

// Props
const props = defineProps({
  module: { type: String, required: true },
  isDrawerOpen: { type: Boolean, required: true },
  currentInfo: { type: Object, default: null }
})

// Emits
const emit = defineEmits(['callToFunction', 'update:isDrawerOpen'])

// Refs
const valid = ref(true)
const refForm = ref(null)
const categoryList = ref([])
const typeList = ref([])
const variableList = ref([])
const templatesList = ref([])

const loadingPreview = ref(false)
const loadingSave = ref(false)

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
  variables: []
})

// Methods
const updateModelValue = val => emit('update:isDrawerOpen', val)

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
    app: 'app_message'
  }

  if (!keyMap[type]) return

  let variable = item.variables
  let templateText = '[[**name**]]'

  if (variable.includes('copy_')) {
    variable = variable.replace('copy_', '')
  } else if (variable.includes('_link')) {
    templateText = '[[***name***]]'
  }

  const message = `${template.value[keyMap[type]] || ''} ${templateText.replace('name', variable)}`
  template.value[keyMap[type]] = message.trim()
}

const emailBodyError = ref(false)
const whatsAppError = ref(false)
const bellContentError = ref(false)

const onSubmit = async () => {
  if (loadingSave.value) return

  // Frontend manual validations
  emailBodyError.value = !template.value.email_body || template.value.email_body.trim() === '<p><br></p>'
  whatsAppError.value = !template.value.whats_app_message || template.value.whats_app_message.trim() === '<p><br></p>'
  bellContentError.value = !template.value.bell_notification_message || template.value.bell_notification_message.trim() === '<p><br></p>'

  const { valid: isValid } = await refForm.value.validate()
  if (!isValid) return
  loadingSave.value = true
  try {
    const payload = { ...template.value }
    const response = await $api('/create-notification', { method: 'POST', body: payload })
    toast.success(response.message || 'Operation successful!')
    emit('update:isDrawerOpen', false)
    emit('callToFunction', payload)
    resetForm()
  } catch (error) {
    const errors = error._data?.errors || error?.errors || error._data?.message || {}
    // Assign API validation errors if available
    if (errors.email_body) emailBodyError.value = true
    if (errors.whats_app_message) whatsAppError.value = true
    if (errors.bell_notification_message) bellContentError.value = true
    toast.error(error._data?.message ?? 'Submission failed.')
  } finally {
    loadingSave.value = false
  }
}

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
    variables: []
  })

  refForm.value?.reset()
  refForm.value?.resetValidation()
}

const preview = () => {
  loadingPreview.value = true
  setTimeout(() => (loadingPreview.value = false), 1000)
}

const saveData = async () => {
  loadingSave.value = true
  await onSubmit()
  loadingSave.value = false
}

onMounted(dropdownNoficationList)
</script>
