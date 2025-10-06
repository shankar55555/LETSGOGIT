<script setup>
import dayjs from "dayjs";
import { ref } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import { VForm } from 'vuetify/components/VForm';

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  currentData: { type: Object, default: null },
});

const emit = defineEmits(['update:isDrawerOpen', 'submit']);
const refForm = ref();
const valid = ref(true);
const loading = ref(false);
const isSubmitting = ref(false);

// Initialize Payment with default values
const Payment = ref({
  total: props.currentData?.total,
  payment_method: 'Cash',
  payment_date: dayjs().format("YYYY-MM-DD"),
  note: '',
  invoice_id: props.currentData?.id,
});

const resetForm = () => {
  Payment.value = {
    total: props.currentData?.total,
    payment_method: 'Cash',
    payment_date: dayjs().format("YYYY-MM-DD"),
    note: '',
    invoice_id: props.currentData?.id,
  };
};

const handleDrawerModelValueUpdate = (val) => {
  emit('update:isDrawerOpen', val);
};

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false);
};

const onSubmit = async () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;

  const { valid: isValid } = await refForm.value.validate();
  if (!isValid) {
    isSubmitting.value = false;
    return;
  }

  try {
    loading.value = true;
    const payload = { ...Payment.value };

    const res = await $api('/pay-invoice', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    toast.success(res?.data?.message || 'Invoice Paid successfully');
    emit('submit');
    emit('update:isDrawerOpen', false);
    resetForm();
  } catch (err) {
    console.error(err);
    toast.error(err?._data?.message || 'Error occurred');
  } finally {
    isSubmitting.value = false;
    loading.value = false;
  }
};
</script>

<template>
  <VNavigationDrawer 
    :model-value="props.isDrawerOpen" 
    temporary 
    location="end" 
    width="370" 
    border="none"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection 
      title="Record Payment" 
      @cancel="closeNavigationDrawer" 
    />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <div class="d-flex justify-lg-space-between">
                  <div>Invoice Balance</div>
                  <p class="mr-10 font-weight-bold">Rs. {{ Payment.total }}</p>
                </div>
              </VCol>
 
              <VCol cols="12">
                <!-- <AppDateTimePicker v-model="Payment.payment_date" :rules="[requiredValidator]" label="Payment Date*"
                  placeholder="dd-mm-yyyy" readonly /> -->
<!-- with dayjs can we makt this format Thursday, April 24, 2025 5:51 PM -->
                  <div class="">
                  <div>Payment Date</div>
                  <p class="mr-10 font-weight-bold">{{ dayjs(Payment.payment_date).format('dddd, MMMM D, YYYY') }}</p>
                </div>
              </VCol>

              <VCol cols="12">
                <AppSelect 
                  v-model="Payment.payment_method" 
                  label="Select Payment Method"
                  placeholder="Select Payment Method" 
                  :items="['Cash', 'Bank']" 
                  :rules="[requiredValidator]" 
                />
              </VCol>


              <VCol cols="12">
                <AppTextarea 
                  v-model="Payment.note" 
                  label="Internal Payment Note" 
                  placeholder="Internal Payment Note" 
                />
              </VCol>

              <VCol cols="12" class="d-flex gap-4 justify-start pb-10">
                <VBtn type="submit" color="primary" variant="tonal" :loading="loading">
                  Record
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </PerfectScrollbar>
    </VCard>
  </VNavigationDrawer>
</template>
