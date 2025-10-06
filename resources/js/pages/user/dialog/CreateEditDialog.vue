<template>
  <div>
    <div v-if="props.isDialogVisible" class="backdrop"></div>
    <VNavigationDrawer permanent :width="700" location="end" :model-value="props.isDialogVisible"
      class="scrollable-content">
      <AppDrawerHeaderSection :title="currentInfo ? 'Edit User' : 'Add User'" @click="closeNavigationDrawer" />
      <VDivider />
      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <v-container>
          <VRow class="pa-2 mt-4">
            <VCol cols="12">
              <VForm ref="refForm" @submit.prevent="onSubmit">
                <VRow>

                  <VCol cols="12" v-if="formData.avatar">
                    <div v-if="formData.avatar" class="d-flex align-center">
                      <v-avatar size="64" class="me-2">
                        <img :src="formData.avatar" alt="Avatar" style="object-fit: cover;" />
                      </v-avatar>
                      <VBtn color="error" icon @click="removeImage">
                        <VIcon icon="tabler-trash" />
                      </VBtn>
                    </div>
                  </VCol>

                  <VCol cols="6">
                    <VFileInput v-model="selectedFile" prepend-inner-icon="tabler-camera" prepend-icon=""
                      @change="handleFileChange" accept="image/*" show-size @click:clear="removeImage"
                      :clearable="!!formData.avatar || !!formData.profile">
                      <template v-slot:label>
                        <span>Avatar</span>
                      </template>
                    </VFileInput>
                  </VCol>
                  <VCol cols="6">
                    <v-text-field v-model="formData.name" :rules="[...requiredRule, ...onlyAlphabetsRule]"
                      @input="handleNameInput"><template v-slot:label>
                        <span>Name <span style="color: red;">*</span></span>
                      </template></v-text-field>
                  </VCol>
                  <VCol cols="6">
                    <v-text-field v-model="formData.email" @input="clearFieldError('email')" :rules="requiredRule"
                      :error-messages="errors.email"><template v-slot:label>
                        <span>Email <span style="color: red;">*</span></span>
                      </template></v-text-field>
                  </VCol>

                  <VCol cols="6">
                    <div class="d-flex align-center">
                      <!-- Country Code with Flag -->
                      <v-autocomplete v-model="formData.country_code" :items="countryCodeList" :rules="requiredRule"
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
                      <v-text-field v-model="formData.phone" :rules="requiredRule" dense :error-messages="errors.phone">
                        <template v-slot:label>
                          <span>Phone <span style="color: red;">*</span></span>
                        </template>
                      </v-text-field>
                    </div>
                  </VCol>

                  <VCol cols="6">
                    <v-select :rules="requiredRule" :items="props.statusList" item-title="status_text" item-value="slug"
                      v-model="formData.status" :readonly="!isSalaryEditable"><template v-slot:label>
                        <span>Status <span style="color: red;">*</span></span>
                      </template></v-select>
                  </VCol>

                  <VCol cols="6">
                    <v-select :items="mark_attendance" item-title="title" v-model="formData.mark_attendance"
                      item-value="value" :readonly="!isSalaryEditable"><template v-slot:label>
                        <span>Mark
                          Attendance <span style="color: red;">*</span></span>
                      </template></v-select>
                  </VCol>

                  <VCol cols="6">
                    <v-text-field v-model="formData.user_name" :rules="requiredRule"
                      @input="clearFieldError('user_name')" :error-messages="errors.user_name"><template v-slot:label>
                        <span>Username <span style="color: red;">*</span></span>
                      </template> </v-text-field>
                  </VCol>

                  <VCol cols="6" v-if="!currentInfo">
                    <v-text-field v-model="formData.password" :rules="!currentInfo ? requiredRule : []"
                      hint="Enter your password"><template v-slot:label>
                        <span>Password <span style="color: red;">*</span></span>
                      </template></v-text-field>
                  </VCol>

                  <!-- readonly -->
                  <VCol cols="6">
                    <v-text-field v-model="formData.salary" :rules="requiredRule" type="number"
                      :readonly="!isSalaryEditable"><template v-slot:label>
                        <span>Monthly Salary <span style="color: red;">*</span></span>
                      </template> </v-text-field>
                  </VCol>

                  <VCol :cols="!currentInfo ? '6' : '12'" v-show="!currentInfo?.isAdmin">
                    <v-select v-model="formData.roles" :rules="requiredRule" :items="roleList" item-title="name"
                      item-value="id" multiple :readonly="!isSalaryEditable">
                      <template v-slot:label>
                        <span>Select Role <span style="color: red;">*</span></span>
                      </template></v-select>
                  </VCol>
                </VRow>
                <VRow>
                  <!-- Add new date fields -->
                  <VCol cols="6">
                    <AppDateTimePicker v-model="formData.date_of_birth" label="Date of Birth"
                      placeholder="Select Date of Birth" :config="{
                        enableTime: false,
                        dateFormat: 'Y-m-d'
                      }" />
                  </VCol>

                  <VCol cols="6">
                    <AppDateTimePicker v-model="formData.anniversary_date" label="Anniversary Date"
                      placeholder="Select Anniversary Date" :config="{
                        enableTime: false,
                        dateFormat: 'Y-m-d'
                      }" />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol v-if="$can('user', 'create') && !currentInfo" class="d-flex align-center gap-2 justify-start"
                    cols="12">
                    <VBtn type="submit" color="primary" :loading="isSubmitting" :disabled="isSubmitting">Save</VBtn>
                  </VCol>
                  <VCol v-if="currentInfo" class="d-flex align-center gap-2 justify-start" cols="12">
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
import { panelDetails } from "@layouts/stores/panel";
import { storeToRefs } from 'pinia';
import { computed, onMounted } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import { VForm, VSelect } from 'vuetify/lib/components/index.mjs';
const panelStore = panelDetails();
const { userDetails: userInfo } = storeToRefs(panelStore);

const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  roleList: { type: Array, default: [] },
  statusList: { type: Array, default: () => [] },
  currentInfo: { type: Object, default: null },
  peopleAdd: { type: String, required: true }
})

const refForm = ref(false)
const selectedFile = ref(null);

const mark_attendance = ref([
  { title: 'Attended', value: true },
  { title: 'Did Not Attend', value: false }
]);

const errors = ref({});
const formData = ref(
  {
    id: '',
    name: '',
    email: '',
    profile: null,
    status: '',
    country_code: '91',
    phone: '',
    user_name: '',
    password: '',
    roles: [],
    avatar: '',
    salary: 0,
    image_delete: false,
    date_of_birth: '',
    anniversary_date: '',
  },
);

const emit = defineEmits([
  'update:isDialogVisible',
  'clearPropInfo',
]);

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('clearPropInfo', NO_CALL);
  emit('update:isDialogVisible', false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  })
}
const handleNameInput = (event) => {
  formData.value.name = event.target.value.replace(/[^A-Za-z\s]/g, '');
};

const clearFieldError = (field) => {
  errors.value[field] = null;
};

const isSalaryEditable = computed(() => {
  return userInfo.value?.isAdmin ?? false;
});

const resetFiledInfo = () => {
  formData.value = {
    id: '',
    name: '',
    email: '',
    country_code: '91',
    phone: '',
    profile: null,
    status: '',
    user_name: '',
    password: '',
    roles: [],
    image_delete: false,
    date_of_birth: '',
    anniversary_date: '',
  };
};
const countryCodeList = ref([]);
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
  if (!formData.value.country_code) {
    const india = countryCodeList.value.find(item => item.phone_code === '91');
    if (india) formData.value.country_code = india.phone_code;
  }
};

onMounted(async () => {
  await getPhoneCodeEmojiList();
  errors.value = {};
  if (props.currentInfo) {
    console.log(props.currentInfo);
    const { currentInfo } = props;
    const role_ids = currentInfo.roles?.map(role => role.id) || [];
    formData.value = {
      id: currentInfo.id,
      name: currentInfo.name,
      email: currentInfo.email,
      status: currentInfo.status?.toLowerCase(),
      mark_attendance: currentInfo.mark_attendance,
      country_code: currentInfo.country_code.replace('+', ''),
      phone: currentInfo.phone,
      roles: role_ids,
      profile: null,
      user_name: currentInfo.user_name,
      salary: currentInfo.isAdmin ? 1 : currentInfo.salary,
      avatar: currentInfo.avatar,
      image_delete: false,
      date_of_birth: currentInfo.date_of_birth || '',
      anniversary_date: currentInfo.anniversary_date || '',
    };
  } else {
    resetFiledInfo();
    formData.value.status = "active";
  }
});

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      formData.value.avatar = e.target.result;
    };
    reader.readAsDataURL(file);

    formData.value.profile = file;
    formData.value.image_delete = false;
  }
};

const removeImage = () => {
  formData.value.profile = null
  formData.value.avatar = '';
  formData.value.image_delete = true;
  selectedFile.value = null;
};

const isSubmitting = ref(false);
const onSubmit = async () => {
  if (isSubmitting.value) return;
  try {
    // Validate the form
    const { valid } = await refForm.value.validate();

    if (!formData.value.status) {
      toast.error("Status is required.");
      return;
    }

    if (!valid) {
      toast.error('Please Check Required Fields!');
      return;
    }

    isSubmitting.value = true;
    const formDataObject = new FormData();
    Object.entries(formData.value).forEach(([key, value]) => {
      if (typeof value === 'boolean') {
        formDataObject.append(key, value ? '1' : '0');
      } else if (value !== null && value !== undefined) {
        formDataObject.append(key, value);
      }
    });

    if (formData.value.profile) formDataObject.append("profile", formData.value.profile);

    const url_pai = props.currentInfo ? '/user/update' : '/user/create';

    const response = await $api(url_pai,
      { method: 'POST', body: formDataObject },
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );

    toast.success(response.message || 'Operation successful!');
    emit('update:isDialogVisible', false);
    emit('clearPropInfo', formData.value);

    // Reset form data
    resetFiledInfo();
    nextTick(() => {
      refForm.value?.reset();
      refForm.value?.resetValidation();
    });
  } catch (error) {
    let errorMessage = error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
    if (errorMessage === "The email has already been taken.") {
      errors.value.email = "Email is already in use.";
    } else if (errorMessage === "The phone has already been taken.") {
      errors.value.phone = "Phone is already in use.";
    } else if (errorMessage === "The user name has already been taken.") {
      errors.value.user_name = "User Name is already in use.";
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>
