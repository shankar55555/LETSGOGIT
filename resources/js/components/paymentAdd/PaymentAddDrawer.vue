<script setup>
import dayjs from "dayjs";
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import { VForm } from 'vuetify/components/VForm';

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  currentData: { type: Object, default: null },
})

// console.log('currentData', props)
const emit = defineEmits(['update:isDrawerOpen', 'submit'])

const refForm = ref();
const valid = ref(true);
const isLoading = ref(false);
const recurringInvoice = ref('yes');
const paymentduration = ref(null);
const route = useRoute().fullPath;
const someFieldDisable = ref(false);
const drawerTitle = ref('');
// console.log('route', route);

let isSubmitting = false

const Payment = ref({
  balance: props.currentData?.total || 0,
  amount_receive: 0,
  total: props.currentData?.total,
  payment_date: dayjs().format("YYYY-MM-DD"),
  payment_method: 'Cash',
  note: '',
  quotation_id: '',
  invoice_id: '',
  lead_id: '',
  client_id: '',
  contract_id: '',
  payment_duration: null,
  recurring_invoice: 'yes',
  total_tax_amount: props.currentData?.tax || 0,
  discount_amount: props.currentData?.discount || 0,
  subTotal: props.currentData?.sub_total || 0,
})

if (route.includes('/invoices/details')) {
  drawerTitle.value = 'Generate Payment';
  someFieldDisable.value = true;
  recurringInvoice.value = 'no';
  Payment.value.amount_receive = props.currentData?.total || 0;
  Payment.value.invoice_id = props.currentData?.id || 0;
}

if (route.includes('/quotations/details')) {
  drawerTitle.value = 'Generate Invoice';
  someFieldDisable.value = false;
  Payment.value.quotation_id = props.currentData?.id || 0;
}

watch(recurringInvoice, (newVal) => {
  Payment.value.recurring_invoice = newVal
})

watch(paymentduration, (newVal) => {
  Payment.value.payment_duration = newVal
})


const resetForm = () => {
  Payment.value = {
    balance: '',
    amount_receive: '',
    total: '',
    payment_date: '',
    payment_method: '',
    note: '',
    quotation_id: '',
    invoice_id: '',
    lead_id: '',
    client_id: '',
    contract_id: '',
  }
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  // Reset lead data
  // resetForm()

  // // Emit a reset event to clear currentLead in the parent

  // // Wait for DOM updates before resetting validation
  // nextTick(() => {
  //   refForm.value?.resetValidation()
  // })
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

    const payload = Payment.value;

    // if (Payment.value.recurring_invoice !== 'yes') {
    //   toast.warning('Please select recurring invoice yes for partial payment')
    //   isLoading.value = false
    //   return
    // }


    const endpoint = '/payments';

    const res = await $api(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (res?.status === 200) {
      toast.success(res?.data?.message || 'Payments Saved successfully')
      emit('submit')
      emit('update:isDrawerOpen', false)
      resetForm()
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'Error occurred')
    isLoading.value = false
  } finally {
    isSubmitting = false
    isLoading.value = false
  }
}

onMounted(() => {
  // fetchUserList();
});


</script>

<template>

  <VNavigationDrawer :model-value="props.isDrawerOpen" temporary location="end" width="370" border="none"
    @update:model-value="handleDrawerModelValueUpdate">
    <AppDrawerHeaderSection :title="drawerTitle" @cancel="closeNavigationDrawer" />


    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" v-if="!someFieldDisable">
                <div class="d-flex justify-lg-space-between">
                  <div>Invoice Balance</div>
                  <p class="mr-10 font-weight-bold">Rs. {{ Payment.balance }}</p>
                </div>
                <!-- <AppTextField v-model="Payment.balance" label="Invoice Balance*" placeholder="Invoice Balance"
                  :rules="[requiredValidator]" readonly /> -->
              </VCol>

              <VCol cols="12" v-if="someFieldDisable">
                <AppTextField v-model="Payment.amount_receive" label="Payment Amount*" placeholder="Amount"
                  :rules="[requiredValidator, integerValidator, amountNotGreaterThanBalance(Payment.balance)]"
                  :readonly="someFieldDisable" />
              </VCol>

              <VCol cols="12" v-if="someFieldDisable">
                <AppDateTimePicker v-model="Payment.payment_date" :rules="[requiredValidator]" label="Payment Date*"
                  placeholder="dd-mm-yyyy" readonly />
              </VCol>

              <VCol cols="12" v-if="someFieldDisable">
                <AppSelect v-model="Payment.payment_method" label="Select Payment Method"
                  placeholder="Select Payment Method" :items="['Cash', 'Bank',]" :rules="[requiredValidator]" />
              </VCol>

              <div v-if="Payment.balance - Payment.amount_receive > 0">
                <VCol cols="12">
                  <div class="align-center d-flex mb-3">
                    <span>Recurring Invoice?</span>
                    <VRadioGroup v-model="recurringInvoice" inline class="ml-5">
                      <VRadio label="Yes" value="yes" />
                      <VRadio label="No" value="no" />
                    </VRadioGroup>
                  </div>
                  <span class="text-caption mt-5" v-if="recurringInvoice === 'no'">
                    <VIcon icon="tabler-help-hexagon" class="mr-2"/>You are generating Invoice of full payment
                  </span>
                </VCol>
              </div>

              <VCol cols="12" v-if="recurringInvoice === 'yes'">
                <AppSelect v-model="paymentduration" label="Select Payment Duration"
                  placeholder="Select Payment Duration" :items="[
                    'For 1 month', 'For 2 month', 'For 3 month', 'For 4 month',
                    'For 5 month', 'For 6 month', 'For 7 month', 'For 8 month',
                    'For 9 month', 'For 10 month', 'For 11 month', 'For 12 month'
                  ]"  />

<!-- :rules="[requiredValidator]" -->
              </VCol>

              <VCol cols="12">
                <AppTextarea v-model="Payment.note" label="Internal Payment Note" placeholder="Internal Payment Note" />
              </VCol>

              <VCol cols="12" class="d-flex gap-4 justify-start pb-10">
                <VBtn type="submit" color="primary" variant="tonal" :loading="isLoading">
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
