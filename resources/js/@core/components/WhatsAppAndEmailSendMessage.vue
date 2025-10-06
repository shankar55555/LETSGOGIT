<template>
  <div
    v-if="($can('quotation', 'send-message') && props.type === MODULE_QUOTATION) || ($can('invoice', 'send-message') && props.type === MODULE_INVOICE)">
    <div v-if="isSendMessageDialogVisible" class="backdrop"></div>
    <VDialog v-model="isPdfPreviewOpen" width="800">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center pa-4">
          <span>PDF Preview</span>
          <VBtn icon variant="text" @click="isPdfPreviewOpen = false">
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <iframe v-if="currentPdfPreview" :src="currentPdfPreview" width="100%" height="600" class="pdf-preview">
          </iframe>
        </VCardText>
      </VCard>
    </VDialog>
    <VNavigationDrawer permanent :width="500" location="end" class="scrollable-content"
      :model-value="isSendMessageDialogVisible" @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection title="Send Message" @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VLabel>Send To Name<span class="text-error">*</span></VLabel>
                <VTextField v-model="send.name" :rules="requiredRule" readonly placeholder="Send To Name" />
              </VCol>
              <VCol cols="12" v-if="showEmail">
                <VLabel>Send To Email<span class="text-error">*</span></VLabel>
                <VTextField v-model="send.email" readonly placeholder="Send To Email" />
              </VCol>
              <VCol cols="12">
                <VLabel>Send To Phone<span class="text-error">*</span></VLabel>
                <VTextField v-model="send.phone" :rules="requiredRule" readonly placeholder="Send To Phone" />
              </VCol>

              <VCol cols="12">
                <VLabel>Send From Way<span class="text-error">*</span></VLabel>
                <VSelect v-model="send.socialPlatform" :items="SEND_NOTIFICATION_PLATFORM" :rules="requiredRule"
                  placeholder="Select Platform" />
              </VCol>

              <VCol cols="12">
                <VLabel>Write Message Here<span class="text-error">*</span></VLabel>
                <app-textarea v-model="send.message" :rules="requiredRule" placeholder="Enter Message" rows="2"
                  auto-grow />
              </VCol>

              <VCol cols="12">
                <VLabel>Send Attachment Type<span class="text-error">*</span></VLabel>
                <VSelect v-model="send.sendAttachmentType" :items="SEND_ATTACHMENT_TYPE_LIST" :rules="requiredRule"
                  placeholder="Select Attachment Type" />
              </VCol>

              <VCol cols="12" v-if="send.sendAttachmentType === 'Select File'">
                <VLabel>Upload Files</VLabel>
                <VFileInput v-model="selectedFiles" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" show-size multiple
                  placeholder="Select Files" prepend-inner-icon="tabler-paperclip" prepend-icon=""
                  @change="handleFileChange" />

                <!-- Document Preview Section -->
                <div v-if="filePreviews.length > 0" class="mt-2">
                  <VCard>
                    <VCardTitle class="text-subtitle-1">Files Preview</VCardTitle>
                    <VDivider />

                    <div class="pa-4">
                      <div v-for="(preview, index) in filePreviews" :key="index" class="mb-4">
                        <!-- PDF Preview Thumbnail -->
                        <div v-if="preview.type === 'pdf'" class="pdf-thumbnail" @click="showPdfPreviewDialog(index)">
                          <div class="d-flex align-center justify-space-between">
                            <div class="d-flex align-center">
                              <VIcon size="40" color="error" class="me-2">tabler-file-type-pdf</VIcon>
                              <div>
                                <div class="text-body-1">{{ preview.name }}</div>
                                <div class="text-caption">Click to view PDF</div>
                              </div>
                            </div>
                            <VBtn icon="tabler-x" variant="text" size="small" @click.stop="removeFile(index)" />
                          </div>
                        </div>

                        <!-- Image Preview -->
                        <div v-else-if="preview.type === 'image'" class="image-preview">
                          <div class="d-flex align-center justify-space-between">
                            <VImg :src="preview.url" max-height="200" contain class="mb-2" />
                            <VBtn icon="tabler-x" variant="text" size="small" @click.stop="removeFile(index)"
                              class="float-right" />
                          </div>
                        </div>

                        <!-- Doc/Docx Preview -->
                        <div v-else-if="preview.type === 'doc'" class="doc-preview">
                          <div class="d-flex align-center justify-space-between">
                            <div class="d-flex align-center">
                              <VIcon size="40" icon="tabler-file-text" class="me-2" />
                              <div>
                                <div class="text-body-1">{{ preview.name }}</div>
                                <div class="text-caption">Size: {{ formatFileSize(preview.size) }}</div>
                              </div>
                            </div>
                            <VBtn icon="tabler-x" variant="text" size="small" @click.stop="removeFile(index)" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </VCard>
                </div>
              </VCol>

              <VCol cols="12">
                <VBtn v-if="!isLoading" type="submit" class="me-3"> Send </VBtn>
                <VBtn v-else class="me-3" disabled style="cursor: not-allowed;"> <v-progress-circular color="light"
                    :width="4" :size="20" indeterminate class="mr-2" /> Send </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCard>
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>

<script setup>
import { requiredRule } from '@/validations/validationRules';
import { nextTick, onMounted, ref } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';

const props = defineProps({
  isSendMessageDialogVisible: Boolean,
  currentInfo: { type: Object, default: () => ({}) },
  selectedIdList: { type: Array, default: () => [] },
  type: String,
});
const emit = defineEmits(['update:isSendMessageDialogVisible', 'submit']);

const valid = ref(true);
const refForm = ref(null);
const isLoading = ref(false);
const selectedFiles = ref([]);
const filePreviews = ref([]);
const isPdfPreviewOpen = ref(false);
const currentPdfPreview = ref(null);
const selectedPdfIndex = ref(null);

const send = ref({
  module_id: '',
  receiver_id: '',
  name: '',
  email: '',
  phone: '',
  message: '',
  socialPlatform: WHATSAPP,
  module_name: props.type,
  sendAttachmentType: AUTO_SEND_FILE,
  files: [],
  image_url: null,
});

const handleDrawerModelValueUpdate = (val) => emit('update:isSendMessageDialogVisible', val);

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const handleFileChange = async (event) => {
  const files = event.target.files;
  filePreviews.value = [];
  send.value.files = [];

  if (files) {
    const allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];

    for (const file of files) {
      const fileExt = file.name.split('.').pop().toLowerCase();

      if (!allowedExtensions.includes(fileExt)) {
        toast.error(`Unsupported file type for ${file.name}. Only PDF, DOC, PNG, JPG allowed.`);
        continue;
      }

      let fileType;
      if (fileExt === 'pdf') {
        fileType = 'pdf';
      } else if (['doc', 'docx'].includes(fileExt)) {
        fileType = 'doc';
      } else if (['png', 'jpg', 'jpeg'].includes(fileExt)) {
        fileType = 'image';
      }

      // Generate preview URL
      const reader = new FileReader();
      reader.onload = (e) => {
        filePreviews.value.push({
          type: fileType,
          url: e.target.result,
          name: file.name,
          size: file.size
        });
      };
      reader.readAsDataURL(file);
      send.value.files = [...(send.value.files || []), file];
    }
  }
};

const removeFile = (index) => {
  filePreviews.value.splice(index, 1);
  const newFiles = [...(send.value.files || [])];
  newFiles.splice(index, 1);
  send.value.files = newFiles;
  if (selectedFiles.value) {
    const dt = new DataTransfer();
    for (let i = 0; i < selectedFiles.value.length; i++) {
      if (i !== index) {
        dt.items.add(selectedFiles.value[i]);
      }
    }
    selectedFiles.value = dt.files;
  }
};

const resetForm = () => {
  refForm.value?.reset();
  refForm.value?.resetValidation();
  selectedFiles.value = [];
  send.value.files = [];
  filePreviews.value = [];
  isPdfPreviewOpen.value = false;
  currentPdfPreview.value = null;
  selectedPdfIndex.value = null;
};

const closeNavigationDrawer = () => {
  emit('update:isSendMessageDialogVisible', false);
  nextTick(resetForm);
};

watch(() => send.value.socialPlatform, (newVal) => {
  if (newVal === EMAIL) {
    showEmail.value = true;
  } else {
    showEmail.value = false;
  }
});

const showEmail = ref(false);
const onSubmit = async () => {
  if (send.value.socialPlatform === EMAIL) {
    if (!send.value.email) {
      toast.error('Email is required');
      return;
    }
  }

  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;

  const formData = new FormData();
  Object.entries(send.value).forEach(([key, val]) => {
    if (val !== null && val !== undefined) {
      if (key === 'files' && val.length) {
        val.forEach((file, index) => {
          formData.append(`files[${index}]`, file);
        });
      } else if (key !== 'image_url') {
        formData.append(key, val);
      }
    }
  });

  const apiUrl = props.type === MODULE_QUOTATION ? '/quotation/send-message' : props.type === MODULE_INVOICE ? '/invoice/send-message' : '';

  try {
    const res = await $api(apiUrl, { method: 'POST', body: formData }, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res) {
      toast.success('Message Sent Successfully');
      emit('submit');
      emit('update:isSendMessageDialogVisible', false);
      await nextTick(resetForm);
    } else {
      toast.error(res?.message || 'Something went wrong');
    }
  } catch (err) {
    toast.error(err?._data?.message || 'An unexpected error occurred');
  } finally {
    isLoading.value = false;
  }
};

const initSendData = () => {
  if (props.type === MODULE_QUOTATION && props.currentInfo?.id) {
    const info = props.currentInfo?.client_detail || props.currentInfo?.lead_detail || {};
    send.value = {
      module_id: props.currentInfo.id,
      receiver_id: info.id || '',
      receiver_column: props.currentInfo?.client_detail ? "client_id" : "lead_id",
      name: info.name || '',
      email: info.email || '',
      phone: info.phone || '',
    };
  } else if (props.type === MODULE_INVOICE && props.currentInfo?.id) {
    const info = props.currentInfo?.client || props.currentInfo?.quotation?.client_detail || props.currentInfo?.quotation?.lead_detail || {};
    send.value = {
      module_id: props.currentInfo.id,
      receiver_id: info.id || '',
      receiver_column: props.currentInfo?.client || props.currentInfo?.quotation?.client_detail ? "client_id" : "lead_id",
      name: info.name || '',
      email: info.email || '',
      phone: info.phone || '',
    };
  }
  send.value.socialPlatform = WHATSAPP;
  send.value.module_name = props.type;
  send.value.sendAttachmentType = AUTO_SEND_FILE;
  send.value.files = [];
  send.value.image_url = null;
  selectedFiles.value = [];
}

watch(() => props.currentInfo, (newVal) => { if (newVal?.id) { initSendData(); } }, { immediate: true, deep: true });

const showPdfPreviewDialog = (index) => {
  selectedPdfIndex.value = index;
  currentPdfPreview.value = filePreviews.value[index].url;
  isPdfPreviewOpen.value = true;
};

onMounted(() => {
  initSendData();
});

</script>

<style scoped>
.department_card {
  padding: 20px;
}

.text-error {
  color: red;
}

.pdf-preview {
  border: none;
  border-radius: 4px;
  background: #f5f5f5;
}

.pdf-thumbnail {
  cursor: pointer;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.pdf-thumbnail:hover {
  background-color: #f5f5f5;
}
</style>
