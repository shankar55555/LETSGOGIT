<template>
  <div v-if="$can('reachout', 'send-message')">
    <div v-if="isDialogVisible" class="backdrop"></div>
    <VNavigationDrawer permanent :width="500" location="end" class="scrollable-content" :model-value="isDialogVisible"
      @update:model-value="handleDrawerModelValueUpdate">
      <AppDrawerHeaderSection title="Send Message" @cancel="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard class="department_card">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" v-if="props.type === 'BToB-User'">
                <VLabel>To (B2B User) <span style="color: red;">*</span></VLabel>
                <VSelect v-model="send.b_to_b_user_ids" :items="BToBUserList" item-title="name"
                  placeholder="Select B2B User" item-value="id" :rules="props.type === 'BToB-User' ? requiredRule : []"
                  multiple :readOnly="props.currentInfo ? true : false">
                </VSelect>
              </VCol>

              <VCol cols="12" v-if="props.type === 'Client'">
                <VLabel>To (Client) <span style="color: red;">*</span></VLabel>
                <VSelect v-model="send.client_ids" :items="client_list" item-title="name" placeholder="Select Clients"
                  item-value="id" :rules="props.type === 'Client' ? requiredRule : []" multiple
                  :readOnly="props.currentInfo ? true : false">
                </VSelect>
              </VCol>

              <VCol cols="12" v-if="props.type === 'Lead'">
                <VLabel>To (Lead) <span style="color: red;">*</span></VLabel>
                <VSelect v-model="send.lead_ids" :items="lead_list" item-title="name" item-value="id"
                  placeholder="Select Leads" :rules="props.type === 'Lead' ? requiredRule : []" multiple
                  :readOnly="props.currentInfo ? true : false">
                </VSelect>
              </VCol>
              <VCol cols="12">
                <VLabel>Send From Way <span style="color: red;">*</span></VLabel>
                <VSelect v-model="send.socialPlatform" :items="SEND_NOTIFICATION_LIST" :item-disabled="isItemDisabled"
                  placeholder="Select Plat Form" :rules="requiredRule" disabled>
                </VSelect>
              </VCol>

              <VCol cols="12">
                <VLabel>Write Message here <span style="color: red;">*</span></VLabel>
                <app-textarea v-model="send.message" :rules="requiredRule" placeholder="Enter Message" rows="2"
                  auto-grow>
                </app-textarea>
              </VCol>

              <VCol v-if="previewUrl" cols="12">
                <h3>Preview:</h3>
                <img :src="previewUrl" alt="Uploaded Image" style=" block-size: auto;max-inline-size: 100%;" />
              </VCol>

              <VCol cols="12">
                <VLabel>Upload File <span style="color: red;">*</span></VLabel>
                <VFileInput placeholder="Select File" prepend-inner-icon="tabler-paperclip" prepend-icon=""
                  v-model="send.file" accept=".pdf,.xls,.xlsx,.txt,image/*" show-size @change="handleFileChange" />
              </VCol>

              <!-- <VCol v-if="send.file" cols="12">
                <div class="d-flex align-center justify-between">
                  <div>{{ send.file.name }} ({{ formatFileSize(send.file.size) }})</div>
                  <VTooltip location="top">
                    <template #activator="{ props }">
                      <VIcon v-bind="props" icon="tabler-trash" color="primary" variant="elevated" :size="20"
                        class="ml-3" @click="removeFile(item)" />
                    </template>
<template #default>
                      Remove
                    </template>
</VTooltip>
</div>
</VCol> -->

              <VCol cols="12" v-if="$can('reachout', 'send-message')">
                <VBtn v-if="!isLoading" type="submit" class="me-3"> Send </VBtn>
                <VBtn class="me-3" disabled style="cursor: not-allowed;" v-else>
                  <v-progress-circular color="light" :width="4" :size="20" indeterminate class="mr-2" /> Send
                </VBtn>
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
  isDialogVisible: Boolean,
  currentInfo: { type: Object, default: () => ({}) },
  selectedIdList: { type: Array, default: () => [] },
  type: String,
});
const emit = defineEmits(["update:isDialogVisible", "submit"]);

const valid = ref(true);
const refForm = ref(null);
const isLoading = ref(false);
const previewUrl = ref(null);

const send = ref({
  client_ids: [],
  lead_ids: [],
  b_to_b_user_ids: [],
  message: "",
  socialPlatform: WHATSAPP,
  type: '',
  file: null,
});

const lead_list = ref([]);
const client_list = ref([]);
const BToBUserList = ref([]);

const optionBToBUserList = async () => {
  const res = await $api('/b2b/option-list');
  BToBUserList.value = res.data;
};

const optionLeadList = async () => {
  const res = await $api('/option-lead-list');
  lead_list.value = res.data;
};

const optionClientList = async () => {
  const res = await $api('/option-client-list');
  client_list.value = res.data;
};

const handleDrawerModelValueUpdate = (val) => emit("update:isDialogVisible", val);

const closeNavigationDrawer = () => {
  emit("update:isDialogVisible", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

const handleFileChange = (file) => {
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => previewUrl.value = e.target.result;
    reader.readAsDataURL(file);
  } else {
    previewUrl.value = null;
  }
};

const removeFile = () => {
  send.value.file = null;
  previewUrl.value = null;
};

const formatFileSize = (size) => {
  const kb = size / 1024;
  return kb > 1024 ? `${(kb / 1024).toFixed(2)} MB` : `${kb.toFixed(2)} KB`;
};

const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  const file = send.value.file;
  const allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];

  if (file) {
    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExt)) {
      toast.error("Unsupported file type. Only PDF, DOC, PNG, JPG allowed.");
      return;
    }
  }

  isLoading.value = true;
  const formData = new FormData();
  formData.append('message', send.value.message);
  formData.append('type', send.value.type);
  formData.append('socialPlatform', send.value.socialPlatform);
  formData.append('client_ids', send.value.client_ids);
  formData.append('lead_ids', send.value.lead_ids);
  formData.append('b_to_b_user_ids', send.value.b_to_b_user_ids);
  if (send.value.file) { formData.append('file', send.value.file); }

  let api_url = props.type === 'Client' ? "/whatsApp/reachout-send-message" : props.type === 'Lead' ? "/whatsApp/reachout-send-message" : "/b2b/reachout-send-message";
  try {
    const res = await $api(api_url,
      { method: 'POST', body: formData },
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    if (res) {
      toast.success("Message Sent Successfully");
      emit("submit");
      emit("update:isDialogVisible", false);
      await nextTick(() => {
        refForm.value?.reset();
        refForm.value?.resetValidation();
      });
    } else {
      toast.error(res?.message || "Something went wrong");
    }
  } catch (err) {
    toast.error(err?._data?.message || "An unexpected error occurred");
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  if (props.type === 'BToB-User') await optionBToBUserList();
  if (props.type === 'Lead') await optionLeadList();
  if (props.type === 'Client') await optionClientList();

  if (props.currentInfo?.id) {
    send.value = {
      client_ids: props.type === 'Client' ? [props.currentInfo.id] : [],
      lead_ids: props.type === 'Lead' ? [props.currentInfo.id] : [],
      b_to_b_user_ids: props.type === 'BToB-User' ? [props.currentInfo.id] : [],
      message: "",
      socialPlatform: WHATSAPP,
      type: props.type,
      file: null,
    };
  } else {
    send.value.socialPlatform = WHATSAPP;
    send.value.type = props.type;
    send.value.client_ids = props.type === 'Client' ? props.selectedIdList : [];
    send.value.lead_ids = props.type === 'Lead' ? props.selectedIdList : [];
    send.value.b_to_b_user_ids = props.type === 'BToB-User' ? props.selectedIdList : [];
  }
});
</script>

<style scoped>
.department_card {
  padding: 20px;
}
</style>
