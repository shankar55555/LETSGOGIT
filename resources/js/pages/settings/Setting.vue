<template>
  <div v-if="$can('generalSetting', 'view')">
    <!-- 👉 Profile Information -->
    <VForm @submit.prevent="handleSubmitForm" ref="form" v-model="valid">
      <VCard title="General Settings" class="mb-6">
        <VCardText>
          <BaseSpinner class="d-flex" v-if="fieldloader" />
          <VRow v-else>
            <VCol cols="12" md="6">
              <AppTextField label="Company name" placeholder="Enter Company Name" v-model="formData.name"
                :rules="requiredRule" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField label="Phone" placeholder="+(123) 456-7890" v-model="formData.phone"
                :rules="[...requiredRule, ...minLengthRule(10)]" @input="filterInput(10, 'phone', $event)" />
            </VCol>
            <!-- Color -->
            <VCol cols="12" md="6">
              <VLabel>Email Template Color</VLabel>
              <AppTextField v-model="formData.email_color" :rules="requiredRule" type="color" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField label="GST Number" placeholder="GST Number" v-model="formData.gst_number" />
            </VCol>
            <!-- Address -->
            <VCol cols="12" md="12">
              <AppTextarea label="Address" placeholder="Enter Address" :rules="requiredRule" v-model="formData.address"
                rows="5" />
            </VCol>

            <!-- File Input for="company_logo" -->
            <VCol cols="12" md="6">
              <VLabel>Company logo</VLabel>
              <VFileInput v-model="selectedFile" prepend-inner-icon="tabler-camera" prepend-icon=""
                @change="handleFileChange" accept="image/*" show-size @click:clear="removeImage"
                :clearable="!!previewImage || !!companyLogFile" />
            </VCol>

            <!-- Image Preview -->
            <VCol cols="12" md="6">
              <div v-if="previewImage" class="d-flex">
                <img :src="previewImage" alt="Preview" class="preview-img"
                  style=" border-radius: 10px;inline-size: 150px;" />
                <div class="cutBtn" @click="removeImage">X</div>
              </div>
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" md="6">
              <VLabel>PWA logo (192x192)</VLabel>
              <VFileInput v-model="pwaLogo192File" prepend-inner-icon="tabler-camera" prepend-icon=""
                @update:modelValue="handlePwaLogo192Change" accept="image/png" show-size
                :clearable="!!pwaLogo192Preview" @click:clear="removePwaLogo192" />
            </VCol>
            <VCol cols="12" md="6">
              <div v-if="pwaLogo192Preview" class="d-flex">
                <img :src="pwaLogo192Preview" alt="Preview" class="preview-img"
                  style="border-radius: 10px; inline-size: 150px;" />
                <div class="cutBtn" @click="removePwaLogo192">X</div>
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <VLabel>PWA logo (512x512)</VLabel>
              <VFileInput v-model="pwaLogo512File" prepend-inner-icon="tabler-camera" prepend-icon=""
                @update:modelValue="handlePwaLogo512Change" accept="image/png" show-size
                :clearable="!!pwaLogo512Preview" @click:clear="removePwaLogo512" />
            </VCol>
            <VCol cols="12" md="6">
              <div v-if="pwaLogo512Preview" class="d-flex">
                <img :src="pwaLogo512Preview" alt="Preview" class="preview-img"
                  style="border-radius: 10px; inline-size: 150px;" />
                <div class="cutBtn" @click="removePwaLogo512">X</div>
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField label="PWA Short name" placeholder="Enter PWA Short name" v-model="formData.short_name"
                :rules="requiredRule" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField label="PWA Description" placeholder="Enter PWA Description" v-model="formData.description"
                :rules="requiredRule" />
            </VCol>
          </VRow>
          <!-- 👉 Save button -->
          <div class="d-flex justify-end gap-x-4 mt-4" v-if="$can('generalSetting', 'save')">
            <VBtn type="submit">Save Changes</VBtn>
          </div>
        </VCardText>
      </VCard>
    </VForm>

    <ConfirmDialog v-model:isDialogVisible="gstDialog" confirm-title="Continue without GST"
      confirmation-question="Are you sure you want to continue without adding GST?"
      confirm-msg="Your settings will be saved without GST number." cancel-title="Add GST"
      cancel-msg="Please add GST number before saving." @confirm="handleGstDialogConfirm" />
  </div>
</template>

<script setup>
import AppTextarea from "@/@core/components/app-form-elements/AppTextarea.vue";
import { useCompanyStore } from "@/stores/companyStore";
import { minLengthRule, requiredRule } from "@/validations/validationRules";
import { onMounted, ref } from "vue";
import { toast } from "vue3-toastify";
import ConfirmDialog from '../../components/dialogs/GstConfirmDialg.vue';


const settingStore = useCompanyStore();

const valid = ref(false);
const form = ref(false);
const fieldloader = ref(false);

const formData = ref({
  name: "",
  phone: "",
  address: "",
  gst_number: null,
  image: "",
  email_color: '#7367f0',
});

const gstDialog = ref(false);
const companyLogFile = ref(null);
const pwaLogo192File = ref(null);
const pwaLogo512File = ref(null);
const selectedFile = ref(null);
const previewImage = ref(null);
const pwaLogo192Preview = ref(null);
const pwaLogo512Preview = ref(null);

const setting = ref(null);

// // 👉 Update Profile
// const handleFileChange = async (event) => {
//   const file = event.target.files[0];
//   if (file) {
//     selectedFile.value = await convertToBase64(file);
//     previewImage.value = URL.createObjectURL(file);
//   }
// };

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
    companyLogFile.value = file;
  }
};


const handlePwaLogo192Change = (file) => {
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      pwaLogo192Preview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    pwaLogo192File.value = file;
  }
};

const handlePwaLogo512Change = (file) => {
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      pwaLogo512Preview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    pwaLogo512File.value = file;
  }
};


const removeImage = () => {
  companyLogFile.value = null
  previewImage.value = null;
  selectedFile.value = null;
};


const removePwaLogo192 = () => {
  pwaLogo192File.value = null;
  pwaLogo192Preview.value = null;
};

const removePwaLogo512 = () => {
  pwaLogo512File.value = null;
  pwaLogo512Preview.value = null;
};

const fetchCompanyDetails = async () => {
  fieldloader.value = true;
  try {
    const response = await $api('/settings');
    setting.value = response.data ?? null;
    if (setting.value) {
      formData.value.name = setting.value.company_name;
      formData.value.phone = setting.value.phone;
      formData.value.gst_number = setting.value.gst_number;
      formData.value.address = setting.value.address;
      formData.value.image = setting.value.company_logo;
      formData.value.email_color = setting.value.email_color;
      previewImage.value = setting.value.company_logo ? setting.value.company_logo : null;
      formData.value.short_name = setting.value.short_name || setting.value.company_name;
      formData.value.description = setting.value.description || "This is Modular CRM";
      pwaLogo192Preview.value = setting.value.pwa_logo_192 || null;
      pwaLogo512Preview.value = setting.value.pwa_logo_512 || null;
    }
    fieldloader.value = false;
  } catch (error) {
    fieldloader.value = false;
  }
};

const handleSubmitForm = async () => {
  let { valid } = await form.value.validate();
  if (!valid) return false;

  // Check if GST number is empty or null
  if (!formData.value.gst_number || formData.value.gst_number.trim() === '') {
    gstDialog.value = true;
    return;
  }

  // If GST is provided, proceed with form submission
  await submitForm();
};

const handleGstDialogConfirm = async (confirmed) => {
  gstDialog.value = false;

  if (confirmed) {
    // User clicked "Yes" - proceed without GST
    await submitForm();
  }
  // If user clicked "No", just close the dialog (do nothing)
};

const submitForm = async () => {
  try {
    const formDataObject = new FormData();

    // Ensure required fields are appended
    formDataObject.append('company_name', formData.value.name);
    formDataObject.append('phone', formData.value.phone);
    formDataObject.append('gst_number', formData.value.gst_number);
    formDataObject.append('address', formData.value.address);
    formDataObject.append('email_color', formData.value.email_color);
    formDataObject.append('is_delete', previewImage.value ? '0' : '1');

    // Append PWA fields
    formDataObject.append("short_name", formData.value.short_name);
    formDataObject.append("description", formData.value.description);

    // Append image only if available
    if (companyLogFile.value) {
      formDataObject.append('image', companyLogFile.value);
    }

    if (pwaLogo192File.value) {
      formDataObject.append("pwa_logo_192", pwaLogo192File.value);
    }
    if (pwaLogo512File.value) {
      formDataObject.append("pwa_logo_512", pwaLogo512File.value);
    }

    // Simulate PUT method (important for file upload!)
    formDataObject.append('_method', 'PUT');

    // Send as POST request
    const response = await $api(
      '/settings',
      { method: 'POST', body: formDataObject },
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );

    await settingStore.$patch({ companyDetails: null });
    await settingStore.fetchSettingList(SETTING_KEYS);

    await nextTick(() => {
      toast.success('Settings updated successfully!');
    });

  } catch (error) {
    console.error(error);
    toast.error('Something went wrong');
  }
};


const filterInput = (maxLength, field, event) => {
  let value = event.target.value;
  let filteredValue = value.replace(/[^0-9]/g, "");
  if (filteredValue.length > maxLength) {
    filteredValue = filteredValue.slice(0, maxLength);
  }
  formData.value[field] = filteredValue;
};

// Note: Preserve user's manual line breaks in address as-is.

// const removeImage = () => {
//   previewImage.value = null;
//   selectedFile.value = null;
// };

onMounted(async () => {
  await fetchCompanyDetails();
});
</script>

<style scoped>
.cutBtn {
  border-radius: 20%;
  background-color: #db3838;
  block-size: min-content;
  color: white;
  cursor: pointer;
  margin-inline-start: 4px;
  padding-block: 2px;
  padding-inline: 7px;
}
</style>
