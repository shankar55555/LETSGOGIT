<template>
  <div class="gst-challan-container">
    <v-card class="pa-6">
      <v-card-title class="text-h5 mb-4">
        E-Way Challan Generation
      </v-card-title>

      <v-form ref="form" v-model="isFormValid" @submit.prevent="submitChallan">
        <!-- Basic Details -->
        <v-row>
          <v-col cols="12">
            <v-text-field v-model="challanData.gstin" label="GSTIN" :rules="[rules.required]" readonly
              placeholder="Enter 15-digit GSTIN" outlined dense />
          </v-col>

          <v-col cols="12">
            <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y
              max-width="290px" min-width="290px">
              <template #activator="{ on, attrs }">
                <v-text-field v-model="challanData.challanDate" label="Challan Date" readonly outlined dense
                  v-bind="attrs" v-on="on" />
              </template>
              <v-date-picker v-model="challanData.challanDate" no-title @input="dateMenu = false" />
            </v-menu>
          </v-col>
        </v-row>

        <!-- State Type -->
        <v-row>
          <v-col cols="12">
            <v-select v-model="challanData.stateType" :items="stateTypes" item-title="text" item-value="value"
              label="State Type" :rules="[rules.required]" outlined dense />
          </v-col>
        </v-row>

        <!-- Submit -->
        <v-card class="mt-4 pa-4" outlined>
          <v-row>

            <VCol cols="12" class="d-flex gap-4 justify-start pt-6 pb-10">
              <v-btn color="primary" type="submit" :loading="loading" :disabled="!isFormValid">
                Generate Challan
              </v-btn>
              <VBtn color="secondary" variant="tonal" @click="$emit('close')">
                Cancel
              </VBtn>
            </VCol>

          </v-row>
        </v-card>
      </v-form>
    </v-card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { toast } from 'vue3-toastify';

const isFormValid = ref(false);
const loading = ref(false);
const dateMenu = ref(false);
const form = ref(null);

const props = defineProps({
  currentInfo: { type: Object, default: () => ({}) },
});

const challanData = ref({
  gstin: '',
  financialYear: `${new Date().getFullYear()}-${(new Date().getFullYear() + 1).toString().slice(-2)}`,
  stateType: '',
  challanDate: new Date().toISOString().substr(0, 10),
});

const stateTypes = [
  { text: 'Intra State', value: 'intra' },
  { text: 'Inter State', value: 'inter' },
];

const rules = {
  required: v => !!v || 'This field is required',
};

const fetchSettingList = async () => {
  loading.value = true;
  try {
    const response = await $api('/setting-list', {
      method: 'POST',
      body: { keys: ['gst_number'] },
    });

    if (response.data?.gst_number) {
      challanData.value.gstin = response.data.gst_number;
    }
  } catch (error) {
    toast.error('Failed to fetch GST number');
    console.error('Fetch GST error:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchSettingList();
});

const submitChallan = async () => {
  if (!form.value.validate()) return;

  loading.value = true;

  try {
    const challanPayload = {
      ...challanData.value,
      quotation_id: props.currentInfo?.id,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const response = await $api('/quotations/gst-challans/store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(challanPayload),
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(response);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `GST-${props.currentInfo?.quotation_number}.pdf`);
    document.body.appendChild(link);
    link.click();

    setTimeout(() => {
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    }, 100);

    toast.success('Challan generated successfully!');
  } catch (error) {
    toast.error('Failed to generate challan');
    console.error('Challan generation error:', error);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.gst-challan-container {
  padding: 20px;
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 900px;
}
</style>
