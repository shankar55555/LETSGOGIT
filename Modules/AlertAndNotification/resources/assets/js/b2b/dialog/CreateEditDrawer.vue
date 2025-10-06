<template>
  <div>
    <div v-if="props.isCreateEditDrawer" class="backdrop"></div>
    <VNavigationDrawer permanent :width="800" location="end" :model-value="props.isCreateEditDrawer"
      class="scrollable-content">
      <AppDrawerHeaderSection :title="currentInfo ? 'Edit User' : 'Add User'" @click="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <v-container>
          <VRow class="pa-2 mt-4">
            <VCol cols="12">
              <VForm ref="refForm" @submit.prevent="onSubmit">
                <VRow>
                  <VCol cols="12" v-if="form.avatar">
                    <div v-if="form.avatar" class="d-flex align-center">
                      <v-avatar size="64" class="me-2">
                        <img :src="form.avatar" alt="Avatar" style="object-fit: cover;" />
                      </v-avatar>
                      <VBtn color="error" icon @click="removeImage">
                        <VIcon icon="tabler-trash" />
                      </VBtn>
                    </div>
                  </VCol>

                  <VCol cols="6">
                    <VFileInput v-model="selectFile" prepend-inner-icon="tabler-camera" prepend-icon=""
                      @change="handleFileChange" accept="image/*" show-size @click:clear="removeImage"
                      :clearable="!!form.avatar || !!form.profile">
                      <template v-slot:label>
                        <span>Avatar</span>
                      </template>
                    </VFileInput>
                  </VCol>
                  <VCol cols="6">
                    <v-text-field v-model="form.name" :rules="[...requiredRule, ...onlyAlphabetsRule]"
                      @input="handleNameInput"><template v-slot:label>
                        <span>Name <span style="color: red;">*</span></span>
                      </template></v-text-field>
                  </VCol>
                  <VCol cols="6">
                    <v-text-field v-model="form.company" :rules="[...requiredRule]" @input="handleNameInput">
                      <template v-slot:label> <span>Company <span style="color: red;">*</span></span> </template>
                    </v-text-field>
                  </VCol>

                  <VCol cols="6">
                    <v-text-field v-model="form.email" @input="clearFieldError('email')" :rules="emailRule"
                      :error-messages="errors.email" label="Email">
                    </v-text-field>
                  </VCol>

                  <VCol cols="6">
                    <div class="d-flex align-center">
                      <!-- Country Code with Flag -->
                      <v-autocomplete v-model="form.country_code" :items="countryCodeList" :rules="requiredRule"
                        style="max-inline-size: 120px;" item-title="display" item-value="phone_code" label="Select Code"
                        dense>

                        <!-- Custom dropdown list item -->
                        <template v-slot:item="{ props, item }">
                          <v-list-item v-bind="props">
                            <template v-slot:prepend>
                              <span class="mr-2">{{ item.raw.emojiChar }}</span>
                            </template>
                          </v-list-item>
                        </template>

                        <!-- Custom selected display -->
                        <template v-slot:selection="{ item }">
                          <span>{{ item.raw.emojiChar }} +{{ item.raw.phone_code }}</span>
                        </template>
                      </v-autocomplete>

                      <!-- Contact No Field -->
                      <v-text-field v-model="form.contact_no" :rules="requiredRule" dense
                        :error-messages="errors.contact_no">
                        <template v-slot:label>
                          <span>Contact No <span style="color: red;">*</span></span>
                        </template>
                      </v-text-field>
                    </div>
                  </VCol>

                  <VCol cols="6">
                    <v-text-field v-model="form.role">
                      <template v-slot:label> <span>Role <span style="color: red;">*</span></span> </template>
                    </v-text-field>
                  </VCol>

                  <VCol cols="6">
                    <v-select :items="props.statusList" v-model="form.status" item-title="status_text"
                      item-value="slug">
                      <template v-slot:label> <span>Status <span style="color: red;">*</span></span> </template>
                    </v-select>
                  </VCol>

                  <VCol cols="12">
                    <app-textarea v-model="form.address" placeholder="Enter Address" rows="2" auto-grow>
                      <template v-slot:label> <span>Address <span style="color: red;">*</span></span> </template>
                    </app-textarea>
                  </VCol>
                </VRow>
                <VRow>
                  <VCol v-if="$can('b2b', 'create') && !currentInfo" class="d-flex align-center gap-2 justify-start"
                    cols="12">
                    <VBtn type="submit" color="primary" :loading="isSubmitting" :disabled="isSubmitting">Save</VBtn>
                  </VCol>
                  <VCol v-if="$can('b2b', 'edit') && currentInfo" class="d-flex align-center gap-2 justify-start"
                    cols="12">
                    <VBtn type="submit" color="primary" :loading="isSubmitting" :disabled="isSubmitting">Update</VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCol>
          </VRow>
        </v-container>
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>

<script setup>
import { onlyAlphabetsRule, requiredRule } from '@/validations/validationRules';
import { onMounted } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from "vue3-toastify";
import { VForm, VSelect } from 'vuetify/lib/components/index.mjs';

const props = defineProps({
  isCreateEditDrawer: { type: Boolean, required: true },
  currentInfo: { type: Object, default: null },
  statusList: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:isCreateEditDrawer', 'clearPropInfo'])

const countryCodeList = ref([]);

const errors = ref({});
const refForm = ref(false)
const form = ref(
  {
    id: '',
    name: '',
    company: '',
    email: '',
    country_code: '91',
    contact_no: '',
    status: '',
    address: '',
    role: '',
    profile: null,
    avatar: '',
    image_delete: false,
  },
);

const selectFile = ref('');

const closeNavigationDrawer = () => {
  emit('clearPropInfo', NO_CALL);
  emit('update:isCreateEditDrawer', false);
  nextTick(() => { formResetValidation(); });
};

const formResetValidation = () => {
  resetFiledInfo();
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

const handleNameInput = (event) => {
  event.target.value = event.target.value.replace(/[^A-Za-z\s]/g, '');
};

const clearFieldError = (field) => {
  errors.value[field] = null;
};

const resetFiledInfo = () => {
  form.value = {
    id: '',
    name: '',
    company: '',
    email: '',
    country_code: '91',
    contact_no: '',
    status: 'active',
    address: '',
    role: '',
    profile: null,
    avatar: '',
    image_delete: false,
  };
};

const getPhoneCodeEmojiList = async () => {
  const res = await $api('/dropdown-phone-code-emoji-list');
  countryCodeList.value = res.data.map(item => ({
    ...item,
    emojiChar: item.emojiU
      .split(' ')
      .map(code => String.fromCodePoint(parseInt(code.replace('U+', ''), 16)))
      .join(''),
    display: `+${item.phone_code}`,
  }));

  // Set India (+91) as default if not already set
  if (!form.value.country_code) {
    const india = countryCodeList.value.find(item => item.phone_code === '91');
    if (india) form.value.country_code = india.phone_code;
  }
};

onMounted(async () => {
  await getPhoneCodeEmojiList();
  errors.value = {};
  if (props.currentInfo) {
    const { currentInfo } = props;
    form.value = {
      id: currentInfo.id,
      name: currentInfo.name,
      company: currentInfo.company,
      email: currentInfo.email,
      country_code: currentInfo.country_code.replace('+', ''),
      contact_no: currentInfo.contact_no,
      status: currentInfo.status,
      address: currentInfo.address,
      role: currentInfo.role,
      profile: null,
      avatar: currentInfo.avatar,
      image_delete: false,
    };
  } else {
    resetFiledInfo();
    form.value.status = "active";
  }
});

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      form.value.avatar = e.target.result;
    };
    reader.readAsDataURL(file);

    form.value.profile = file;
    form.value.image_delete = false;
  }
};

const removeImage = () => {
  form.value.profile = null
  form.value.avatar = '';
  form.value.image_delete = true;
  selectFile.value = null;
};

const isSubmitting = ref(false);
const onSubmit = async () => {
  if (isSubmitting.value) return;

  const { valid } = await refForm.value.validate();
  if (!form.value.status) {
    toast.error("Status is required.");
    return;
  }

  if (!valid) {
    toast.error("Please Check Required Fields!");
    return;
  }

  isSubmitting.value = true;

  const formDataObject = new FormData();
  Object.entries(form.value).forEach(([key, value]) => {
    if (typeof value === "boolean") {
      formDataObject.append(key, value ? "1" : "0");
    } else if (value !== null && value !== undefined) {
      formDataObject.append(key, value);
    }
  });

  if (form.value.profile) {
    formDataObject.append("profile", form.value.profile);
  }

  const url_api = props.currentInfo ? "/b2b/update" : "/b2b/create";

  try {
    const response = await $api(
      url_api,
      { method: "POST", body: formDataObject },
      { headers: { "Content-Type": "multipart/form-data" } }
    );

    toast.success(response.message || "Operation successful!");
    emit("update:isCreateEditDrawer", false);
    emit("clearPropInfo", form.value);

    nextTick(() => {
      formResetValidation();
    });
  } catch (error) {
    const defaultMessage = "Request Failed!";
    const response = error?.response?.data || error?._data || error?.response || error || {};

    let errorMessage = "";

    if (typeof response.message === "string") {
      errorMessage = response.message;
    } else if (Array.isArray(response.errors)) {
      errorMessage = response.errors[0];
    } else if (typeof response.errors === "object") {
      const firstErrorField = Object.keys(response.errors)[0];
      errorMessage = response.errors[firstErrorField]?.[0] || defaultMessage;
    } else {
      errorMessage = defaultMessage;
    }

    toast.error(errorMessage);

    // Process validation errors
    const validationErrors = response.errors || {};
    errors.value = {};
    for (const field in validationErrors) {
      if (Object.prototype.hasOwnProperty.call(validationErrors, field)) {
        errors.value[field] = validationErrors[field][0] || "Invalid value";
      }
    }

  } finally {
    isSubmitting.value = false;
  }
};
</script>
