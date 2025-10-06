<script setup>
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
  note: '',
  quotation_id: props.currentData?.id,
  payment_duration: null,
  recurring_invoice: 'yes',
});

const resetForm = () => {
  Payment.value = {
    total: props.currentData?.total,
    note: '',
    quotation_id: props.currentData?.id,
    payment_duration: null,
    recurring_invoice: 'yes',
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
    // isSubmitting.value = false;
    // return;
  }

  if (Payment.recurring_invoice == 'yes') {
    if (Payment.payment_duration == null) {
      toast.warn('Please add Payment Duration');
      return;
    }
  }

  try {
    loading.value = true;
    const payload = { ...Payment.value };

    const res = await $api('/generate-invoices', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    toast.success(res?.data?.message || 'Invoice Generated successfully');
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
  <VNavigationDrawer :model-value="props.isDrawerOpen" temporary location="end" width="370" border="none"
    @update:model-value="handleDrawerModelValueUpdate">
    <AppDrawerHeaderSection title="Generate Invoices" @cancel="closeNavigationDrawer" />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <div class="d-flex justify-lg-space-between">
                  <div>Invoice Total</div>
                  <p class="mr-10 font-weight-bold">Rs. {{ Payment.total }}</p>
                </div>
              </VCol>

              <template v-if="Payment.total > 0">
                <VCol cols="12">
                  <div class="align-center d-flex mb-3">
                    <span>Recurring Invoice?</span>
                    <VRadioGroup v-model="Payment.recurring_invoice" inline class="ml-5">
                      <VRadio label="Yes" value="yes" />
                      <VRadio label="No" value="no" />
                    </VRadioGroup>
                  </div>
                  <span class="text-caption mt-5" v-if="Payment.recurring_invoice === 'no'">
                    <VIcon icon="tabler-help-hexagon" class="mr-2" />You are generating Invoice of full payment
                  </span>
                </VCol>
              </template>

              <VCol cols="12" v-if="Payment.recurring_invoice === 'yes'">
                <AppSelect v-model="Payment.payment_duration" label="Select Payment Duration"
                  placeholder="Select Payment Duration"
                  :items="Array.from({ length: 12 }, (_, i) => `For ${i + 1} month`)" />
              </VCol>

              <VCol cols="12">
                <AppTextarea v-model="Payment.note" label="Internal Payment Note" placeholder="Internal Payment Note" />
              </VCol>

              <VCol cols="12" class="d-flex gap-4 justify-start pb-10">
                <VBtn type="submit" color="primary" variant="tonal" :loading="loading">
                  Generate
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
</template>
