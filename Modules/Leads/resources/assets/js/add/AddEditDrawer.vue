<script setup>
import { useFetchStatusList } from "@/utils/common";
import { computed, nextTick, ref } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import { VForm } from 'vuetify/components/VForm';

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  currentLead: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'submit'])

const refForm = ref()
const valid = ref(true)
const isLoading = ref(false)
let isSubmitting = false
const selectedFile = ref('');
const { statusList, fetchStatusList } = useFetchStatusList();

const lead = ref({
  name: '',
  contact_person: '',
  contact_person_role: '',
  email: '',
  phone: '',
  secondary_phone: [],
  address: '',
  status: 'no_action',
  source: '',
  referral_detail: '', // <-- Added for referral detail
  assigned_user: '',
  note: '',
  client_id: '',
  quotation_id: '',
  contract_id: '',
  invoice_id: '',
  date_of_birth: '',
  anniversary_date: '',
  city_id: null,
})

// Computed property for referral_detail validation
const referralDetailRules = computed(() => {
  if (lead.value.source === 'Referral') {
    return [v => !!v || 'Referral detail is required when source is Referral']
  }
  return []
})

const resetForm = () => {
  lead.value = {
    name: '',
    contact_person: '',
    contact_person_role: '',
    email: '',
    phone: '',
    secondary_phone: [],
    address: '',
    status: 'no_action',
    source: '',
    referral_detail: '', // <-- Added for referral detail
    assigned_user: '',
    note: '',
    client_id: '',
    quotation_id: '',
    contract_id: '',
    invoice_id: '',
    date_of_birth: '',
    anniversary_date: '',
    city_id: null,
  }
}

watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val) {
      fetchStatusList(MODULE_LEAD);
      fetchUserList();
      if (props.currentLead?.id) {
        fetchCityById(props.currentLead.city_id)
        lead.value = JSON.parse(JSON.stringify(props.currentLead))
        lead.value.city_id = "";
        if (props.currentLead?.assigned_user?.id) {
          lead.value.assigned_user = props.currentLead.assigned_user.uuid
        }
      } else {
        fetchCityList();
        resetForm()
      }

      // Optionally reset form validations too
      nextTick(() => {
        refForm.value?.resetValidation()
      })
    }
  }
)

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  resetForm()
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      lead.value.avatar = e.target.result;
    };
    reader.readAsDataURL(file);

    lead.value.profile = file;
    lead.value.image_delete = false;
  }
};

const removeImage = () => {
  lead.value.profile = null
  lead.value.avatar = '';
  lead.value.image_delete = true;
  selectedFile.value = null;
};

const fetchCityById = async (id) => {
  try {
    const res = await $api('/city-list/' + id)
    cityList.value = res.data ?? []
  } catch (e) {
    // toast.error('Failed to load city detail')
  } finally {
    lead.value.city_id = id;
  }
}

const fetchCityList = async (search = '') => {
  loadingCities.value = true
  try {
    const res = await $api('/city-list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ search }),
    })
    cityList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load cities')
  } finally {
    loadingCities.value = false
  }
}

const onSubmit = async () => {
  if (isSubmitting) return
  isSubmitting = true

  const { valid: isValid } = await refForm.value.validate()
  if (!isValid) {
    isSubmitting = false
    return
  }

  try {
    isLoading.value = true

    const payload = lead.value;

    const endpoint = props.currentLead
      ? `/leads/${props.currentLead.id}?_method=PUT`
      : '/leads'

    const res = await $api(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Saved successfully')
      emit('submit')
      emit('update:isDrawerOpen', false)
      resetForm()
    }
  } catch (err) {
    console.error(err);
    toast.error(err?._data?.message || 'Error occurred');
  } finally {
    isSubmitting = false;
    isLoading.value = false;
  }
};
const cityList = ref([])
const loadingCities = ref(false)
const countryList = ref([])
const stateList = ref([])
const isCreateCityDialog = ref(false)
const citySearchText = ref('')
const newCity = ref({
  name: '',
  country_id: null,
  state_id: null,
  latitude: '0',
  longitude: '0',
  city_type: 'no',
})
const userList = ref([]);
const loading = ref(false);
const fetchUserList = async () => {
  try {
    const response = await $api("/dropdown-user-list");
    userList.value = response.data ?? [];
  } catch (error) {
    toast.error(error?.response?.data?.message || "Error fetching user list.");
  }
};

function removeToolChecklistTag(index) {
  lead.value.secondary_phone.splice(index, 1)
}

const handleCitySearchUpdate = (value) => {
  citySearchText.value = value || ''
  fetchCityList(value || '')
}

const openCreateCityDialog = async () => {
  newCity.value = {
    name: citySearchText.value || '',
    country_id: null,
    state_id: null,
    latitude: '0',
    longitude: '0',
    city_type: 'no',
  }
  await fetchDropdownCountries()
  stateList.value = []
  isCreateCityDialog.value = true
}

const fetchDropdownCountries = async () => {
  try {
    const res = await $api('/dropdown-country-list')
    countryList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load countries')
  }
}

const fetchDropdownStates = async (countryId) => {
  if (!countryId) { stateList.value = []; return }
  try {
    const res = await $api(`/dropdown-state-list`, { params: { country_id: countryId } })
    stateList.value = res.data ?? []
  } catch (e) {
    toast.error('Failed to load states')
  }
}

const createCity = async () => {
  if (!newCity.value.name || !newCity.value.country_id || !newCity.value.state_id) {
    toast.error('Please fill city name, country and state')
    return
  }
  try {
    loadingCities.value = true
    const res = await $api('/city-create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newCity.value),
    })
    const created = res.data
    if (created) {
      // Add to list and select
      const already = cityList.value.find(c => c.id === created.id)
      if (!already) cityList.value.unshift(created)
      lead.value.city_id = created.id
      toast.success(res?.message || 'City created successfully')
      isCreateCityDialog.value = false
    }
  } catch (e) {
    toast.error(e?._data?.message || 'Failed to create city')
  } finally {
    loadingCities.value = false
  }
}
</script>

<template>
  <VNavigationDrawer :model-value="props.isDrawerOpen" temporary location="end" width="700" border="none"
    @update:model-value="handleDrawerModelValueUpdate">
    <AppDrawerHeaderSection :title="props.currentLead ? 'Edit Lead' : 'Add Lead'" @cancel="closeNavigationDrawer" />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <!-- <VCol cols="12" v-if="lead.avatar">   
                    <div v-if="lead.avatar" class="d-flex align-center">
                      <v-avatar size="64" class="me-2">
                        <img :src="lead.avatar" alt="Avatar" style="object-fit: cover;" />
                      </v-avatar>   
                      <VBtn color="error" icon @click="removeImage">
                        <VIcon icon="tabler-trash" />
                      </VBtn>
                    </div>
                  </VCol>

                  <VCol cols="6">
                    <VFileInput v-model="selectedFile" prepend-inner-icon="tabler-camera" prepend-icon=""
                      @change="handleFileChange" accept="image/*" show-size @click:clear="removeImage"
                      :clearable="!!lead.avatar || !!lead.profile">
                      <template v-slot:label>
                        <span>Avatar</span>
                      </template>
</VFileInput>
</VCol> -->

              <VCol cols="12">
                <AppTextField v-model="lead.name" label="Name*" placeholder="John Doe" :rules="[requiredValidator]" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="lead.contact_person" label="Contact Person" placeholder="Jane Doe" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="lead.contact_person_role" label="Designation" placeholder="Manager" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="lead.email" label="Email" :rules="[emailValidator]"
                  placeholder="email@example.com" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="lead.phone" type="tel" label="Phone*" :rules="[requiredValidator]"
                  placeholder="1234567890" maxlength="16" @input="lead.phone = lead.phone.replace(/\D/g, '')" />
              </VCol>

              <VCol cols="12">
                <label for="">Secondary phone</label>
                <VCombobox v-model="lead.secondary_phone" multiple :items="[]" chips placeholder="Add multiple number"
                  hint="Enter number and press enter">
                  <template v-slot:chip="{ item, index }">
                    <VChip class="ma-1" color="primary">
                      {{ item.raw }}
                      <v-icon @click="removeToolChecklistTag(index)" class="ml-1" size="large"
                        icon="tabler-circle-letter-x"></v-icon>
                    </VChip>
                  </template>
                </VCombobox>
              </VCol>

              <VCol cols="12">
                <AppTextarea v-model="lead.address" label="Address*" :rules="[requiredValidator]" />
              </VCol>

              <!-- <VCol cols="12" v-if="props.currentLead">
                <AppSelect v-model="lead.lead_status" label="Status*" :items="['active','in-active']" :loading="loading" :rules="[requiredValidator]" />
              </VCol> -->

              <VCol cols="12" v-if="false">
                <AppSelect v-model="lead.status" label="Status*" :items="statusList" item-title="status_text"
                  item-value="slug" :loading="loading" :rules="[requiredValidator]" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="lead.source" label="Source*"
                  :items="['Dial', 'Website', 'Referral', 'Advertisement', 'Existing customer reference', 'Visited by store', 'BNI reference', 'Justdial', 'Facebook', 'India Mart', 'Websites Visited Store']"
                  :rules="[requiredValidator]" />
              </VCol>

              <VCol cols="12" v-if="lead.source === 'Referral'">
                <AppTextField v-model="lead.referral_detail" label="Referral Detail"
                  placeholder="Enter referral details" :rules="referralDetailRules" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="lead.assigned_user" :items="userList" item-title="name" item-value="uuid"
                  label="Assigned To*" placeholder="Select User" />
              </VCol>

              <VCol cols="6">
                <AppDateTimePicker v-model="lead.date_of_birth" label="Date of Birth" placeholder="Select Date of Birth"
                  :config="{
                    enableTime: false,
                    dateFormat: 'Y-m-d'
                  }" />
              </VCol>

              <VCol cols="6">
                <AppDateTimePicker v-model="lead.anniversary_date" label="Anniversary Date"
                  placeholder="Select Anniversary Date" :config="{
                    enableTime: false,
                    dateFormat: 'Y-m-d'
                  }" />
              </VCol>

              <VCol cols="12" md="6">
                <VLabel>City <span style="color: red;">*</span></VLabel>
                <AppAutocomplete v-model="lead.city_id" :items="cityList" item-title="name" item-value="id"
                  :loading="loadingCities" :searchable="true" density="comfortable"
                  @update:search="handleCitySearchUpdate" placeholder="Search City" clearable
                  :rules="[requiredValidator]">
                  <template #no-data>
                    <div class="pa-4 text-center">
                      <VIcon icon="tabler-map-pin-plus" class="mb-2" />
                      <div>
                        No matching city found
                      </div>
                      <VBtn v-if="citySearchText" variant="outlined" size="small" class="mt-2"
                        @click="openCreateCityDialog">
                        Add "{{ citySearchText }}"
                      </VBtn>
                    </div>
                  </template>
                </AppAutocomplete>
              </VCol>



              <VCol cols="12">
                <AppTextarea v-model="lead.note" label="Note" placeholder="Additional information" type="textarea"
                  rows="5" />
              </VCol>

              <!-- <VCol cols="12">
                <AppSelect v-model="lead.client_id" :items="[]" label="Client ID*" placeholder="Client Identifier" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="lead.quotation_id" :items="[]" label="Quotation ID"
                  placeholder="Quotation Identifier" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="lead.contract_id" :items="[]" label="Contract ID"
                  placeholder="Contract Identifier" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="lead.invoice_id" :items="[]" label="Invoice ID" placeholder="Invoice Identifier" />
              </VCol> -->

              <VCol cols="12" class="d-flex gap-4 justify-start pb-10">
                <VBtn type="submit" color="primary" :loading="isLoading">
                  {{ props.currentLead ? 'Update' : 'Add' }}
                </VBtn>
                <VBtn color="error" variant="tonal" @click="resetForm">
                  Reset
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </PerfectScrollbar>
    </VCard>
  </VNavigationDrawer>

  <!-- Create City Dialog -->
  <VDialog max-width="600" :model-value="isCreateCityDialog" @update:model-value="val => isCreateCityDialog = val">
    <VCard>
      <VCardTitle class="pa-4">Add City</VCardTitle>
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppTextField v-model="newCity.name" label="City Name" placeholder="Enter city name" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.country_id" :items="countryList" item-title="name" item-value="id"
              label="Country" placeholder="Select Country" @update:modelValue="fetchDropdownStates" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.state_id" :items="stateList" item-title="name" item-value="id" label="State"
              placeholder="Select State" />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField v-model="newCity.latitude" label="Latitude" placeholder="0" />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField v-model="newCity.longitude" label="Longitude" placeholder="0" />
          </VCol>
          <VCol cols="12" md="6">
            <AppSelect v-model="newCity.city_type" :items="['no', 'yes']" label="City Type" />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="tonal" color="secondary" @click="isCreateCityDialog = false">Cancel</VBtn>
        <VBtn color="primary" :loading="loadingCities" @click="createCity">Create</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
