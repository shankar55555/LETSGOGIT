<template>
  <div v-if="$can('generalSetting', 'view')">
    <!-- 👉 Profile Information -->
    <VForm @submit.prevent="handleSubmitForm" ref="form" v-model="valid">
      <VCard title="General Settings" class="mb-6">
        <VCardText>
          <BaseSpinner class="d-flex" v-if="fieldloader" />
          <VRow v-else>
            <VCol cols="12" md="12" sm="12">
              <VLabel>Payment Terms</VLabel>
              <ProductDescriptionEditor class="border rounded" v-model="formData.payment_terms" />
              <!-- <VTextarea v-model="formData.payment_terms" placeholder="Enter Payment Terms" rows="1"
                                outlined dense auto-grow /> -->
            </VCol>
            <VCol cols="12" md="12" sm="12">
              <VLabel>Terms and Conditions</VLabel>
              <ProductDescriptionEditor class="border rounded" v-model="formData.terms_conditions" />
              <!-- <VTextarea v-model="formData.terms_conditions" placeholder="Enter Terms and Conditions"
                                rows="4" outlined dense auto-grow /> -->
            </VCol>
          </VRow>

          <div class="d-flex justify-end gap-x-4 mt-4" v-if="$can('generalSetting', 'save')">
            <VBtn type="submit">Save Changes</VBtn>
          </div>
        </VCardText>
      </VCard>
    </VForm>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { toast } from 'vue3-toastify'

// Refs
const form = ref(null)
const valid = ref(false)
const fieldloader = ref(false)

// Form data
const formData = reactive({
  payment_terms: '',
  terms_conditions: ''
})

const fetchPatmentTermDetails = async () => {
  try {
    fieldloader.value = true;
    const response = await $api('/settings');
    console.log('API Response:', response.data);

    if (response.data) {
      formData.payment_terms = response.data.payment_term;
      formData.terms_conditions = response.data.term_condition;
    }
  } catch (error) {
    console.error("API Error:", error);
    if (error.response) {
      console.error("Response Status:", error.response.status);
      console.error("Response Data:", error.response.data);
    }
  } finally {
    fieldloader.value = false;
  }

}
// Methods
const handleSubmitForm = async () => {
  const { valid: formValid } = await form.value.validate()

  if (!formValid) {
    toast.error('Please fill all required fields')
    return
  }

  try {
    fieldloader.value = true
    const payload = {
      "payment_term": formData.payment_terms,
      "term_condition": formData.terms_conditions
    }
    const response = await $api('/settings/term-condition', {
      method: "PUT",
      body: JSON.stringify(payload)
    });

    console.log(response);
    // Add your API call here
    // await updateGeneralSettings(formData)

    toast.success('Settings updated successfully')
  } catch (error) {
    toast.error('Failed to update settings')
    console.error('Error updating settings:', error)
  } finally {
    fieldloader.value = false
  }
}

onMounted(async () => {
  await fetchPatmentTermDetails();
})
</script>
