<template>
  <VDialog v-model="isOpen" max-width="900" persistent @click:outside="handleCancel">
    <VCard class="account_vcard_border" :title="isEditMode ? 'Edit Journal Entry' : 'Create Journal Entry'">
      <template #append>
        <VBtn variant="text" size="x-small" rounded @click="handleCancel" class="account_vcard_close_btn">
          <IconX size="20" />
        </VBtn>
      </template>

      <VCardText>
        <VForm ref="journalEntryFormRef" @submit.prevent="handleSubmit">
          <VRow>
            <VCol cols="12" lg="6" md="6">
              <VTextField v-model="formData.entry_date" class="accouting_field accouting_active_field"
                variant="outlined" density="compact" type="date" label="Entry Date"
                :error-messages="errors.entry_date" />
            </VCol>
            <VCol cols="12" lg="6" md="6">
              <VTextField v-model="formData.entry_number" class="accouting_field accouting_active_field"
                variant="outlined" density="compact" label="Entry Number" :error-messages="errors.entry_number" />
            </VCol>
          </VRow>

          <!-- Debit Rows -->
          <VRow>
            <VCol cols="12">
              <div class="d-flex align-center justify-space-between mb-2">
                <h6 class="text-h6">Debit Entries</h6>
                <VBtn @click="addDebitRow" size="small" variant="outlined" color="primary">
                  <IconPlus size="16" />
                  Add Debit
                </VBtn>
              </div>

              <div v-for="(row, index) in formData.debit_entries" :key="`debit-${index}`" class="mb-3">
                <VRow>
                  <VCol cols="12" lg="6" md="6">
                    <VAutocomplete v-model="row.account_id" class="accouting_field accouting_active_field"
                      variant="outlined" density="compact" :items="accounts" item-title="title" item-value="value"
                      label="Account" :error-messages="errors[`debit_entries.${index}.account_id`]" />
                  </VCol>
                  <VCol cols="12" lg="4" md="4">
                    <VTextField v-model="row.amount" class="accouting_field accouting_active_field" variant="outlined"
                      density="compact" type="number" step="0.01" label="Amount"
                      :error-messages="errors[`debit_entries.${index}.amount`]" @input="handleAmountInput" />
                  </VCol>
                  <VCol cols="12" lg="2" md="2" class="d-flex align-center">
                    <VBtn v-if="formData.debit_entries.length > 1" @click="removeDebitRow(index)" size="small"
                      variant="text" color="error">
                      <IconTrash size="16" />
                    </VBtn>
                  </VCol>
                </VRow>
              </div>
            </VCol>
          </VRow>

          <!-- Credit Rows -->
          <VRow>
            <VCol cols="12">
              <div class="d-flex align-center justify-space-between mb-2">
                <h6 class="text-h6">Credit Entries</h6>
                <VBtn @click="addCreditRow" size="small" variant="outlined" color="primary">
                  <IconPlus size="16" />
                  Add Credit
                </VBtn>
              </div>

              <div v-for="(row, index) in formData.credit_entries" :key="`credit-${index}`" class="mb-3">
                <VRow>
                  <VCol cols="12" lg="6" md="6">
                    <VAutocomplete v-model="row.account_id" class="accouting_field accouting_active_field"
                      variant="outlined" density="compact" :items="accounts" item-title="title" item-value="value"
                      label="Account" :error-messages="errors[`credit_entries.${index}.account_id`]" />
                  </VCol>
                  <VCol cols="12" lg="4" md="4">
                    <VTextField v-model="row.amount" class="accouting_field accouting_active_field" variant="outlined"
                      density="compact" type="number" step="0.01" label="Amount"
                      :error-messages="errors[`credit_entries.${index}.amount`]" @input="handleAmountInput" />
                  </VCol>
                  <VCol cols="12" lg="2" md="2" class="d-flex align-center">
                    <VBtn v-if="formData.credit_entries.length > 1" @click="removeCreditRow(index)" size="small"
                      variant="text" color="error">
                      <IconTrash size="16" />
                    </VBtn>
                  </VCol>
                </VRow>
              </div>
            </VCol>
          </VRow>

          <!-- Description -->
          <VRow>
            <VCol cols="12">
              <VTextarea v-model="formData.description" class="accouting_field accouting_active_field"
                variant="outlined" density="compact" label="Description/Narration" rows="3"
                :error-messages="errors.description" />
            </VCol>
          </VRow>

          <!-- Voucher Type -->
          <VRow>
            <VCol cols="12" lg="6" md="6">
              <VAutocomplete v-model="formData.voucher_type" class="accouting_field accouting_active_field"
                variant="outlined" density="compact" :items="voucherTypes" item-title="title" item-value="value"
                label="Voucher Type" :error-messages="errors.voucher_type" />
            </VCol>
            <VCol cols="12" lg="6" md="6">
              <VCard class="account_vcard_border mt-2 account_module_card shadow-none" title="Auto-Approve Entry"
                subtitle="This entry will be approved automatically and will immediately affect your books.">
                <template #append>
                  <VSwitch v-model="formData.auto_approve" density="compact" inset class="account_swtich_btn"
                    color="primary" hide-details />
                </template>
              </VCard>
            </VCol>
          </VRow>

          <!-- Balance Summary -->
          <VRow>
            <VCol cols="12">
              <VCard class="account_vcard_border shadow-none" :class="isBalanced ? 'border-success' : 'border-error'">
                <VCardText>
                  <div class="d-flex justify-space-between align-center">
                    <div>
                      <span class="text-subtitle-2">Total Debit: </span>
                      <span class="text-success font-weight-bold">{{ totalDebit.toFixed(2) }}</span>
                    </div>
                    <div>
                      <span class="text-subtitle-2">Total Credit: </span>
                      <span class="text-error font-weight-bold">{{ totalCredit.toFixed(2) }}</span>
                    </div>
                    <div>
                      <VChip :color="isBalanced ? 'success' : 'error'" size="small">
                        {{ isBalanced ? 'Balanced' : 'Unbalanced' }}
                      </VChip>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardActions class="px-6 pb-6">
        <VSpacer />
        <VBtn @click="handleCancel" variant="outlined" class="account_v_btn_outlined">
          Cancel
        </VBtn>
        <VBtn @click="handleSubmit" :loading="loading" :disabled="!isBalanced" variant="flat" color="primary"
          class="account_v_btn_primary">
          <template #prepend>
            <IconDeviceFloppy size="18" />
          </template>
          {{ isEditMode ? 'Update' : 'Save' }} Entry
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { IconDeviceFloppy, IconPlus, IconTrash, IconX } from '@tabler/icons-vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  entry: {
    type: Object,
    default: null
  },
  accounts: {
    type: Array,
    default: () => []
  },
  voucherTypes: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const journalEntryFormRef = ref(null)
const errors = ref({})

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEditMode = computed(() => !!props.entry?.id)

const defaultFormData = () => ({
  entry_date: new Date().toISOString().split('T')[0],
  entry_number: '',
  description: '',
  voucher_type: '',
  auto_approve: false,
  debit_entries: [{ account_id: null, amount: 0 }],
  credit_entries: [{ account_id: null, amount: 0 }]
})

const formData = ref(defaultFormData())

const totalDebit = computed(() => {
  return formData.value.debit_entries.reduce((sum, entry) => {
    return sum + (parseFloat(entry.amount) || 0)
  }, 0)
})

const totalCredit = computed(() => {
  return formData.value.credit_entries.reduce((sum, entry) => {
    return sum + (parseFloat(entry.amount) || 0)
  }, 0)
})

const isBalanced = computed(() => {
  return Math.abs(totalDebit.value - totalCredit.value) < 0.01
})

const handleAmountInput = () => {
  // Force reactivity update
  nextTick()
}

const addDebitRow = () => {
  formData.value.debit_entries.push({ account_id: null, amount: 0 })
}

const removeDebitRow = (index) => {
  if (formData.value.debit_entries.length > 1) {
    formData.value.debit_entries.splice(index, 1)
  }
}

const addCreditRow = () => {
  formData.value.credit_entries.push({ account_id: null, amount: 0 })
}

const removeCreditRow = (index) => {
  if (formData.value.credit_entries.length > 1) {
    formData.value.credit_entries.splice(index, 1)
  }
}

const resetForm = () => {
  formData.value = defaultFormData()
  errors.value = {}
}

const populateForm = (entry) => {
  if (entry) {
    formData.value = {
      entry_date: entry.entry_date || new Date().toISOString().split('T')[0],
      entry_number: entry.entry_number || '',
      description: entry.description || '',
      voucher_type: entry.voucher_type || '',
      auto_approve: entry.auto_approve || false,
      debit_entries: entry.debit_entries?.length ? entry.debit_entries : [{ account_id: null, amount: 0 }],
      credit_entries: entry.credit_entries?.length ? entry.credit_entries : [{ account_id: null, amount: 0 }]
    }
  } else {
    resetForm()
  }
}

const handleSubmit = () => {
  if (!isBalanced.value) {
    return
  }

  const submitData = {
    ...formData.value,
    id: props.entry?.id
  }

  emit('submit', submitData)
}

const handleCancel = () => {
  resetForm()
  emit('cancel')
  isOpen.value = false
}

const setErrors = (validationErrors) => {
  errors.value = validationErrors || {}
}

// Watch for entry changes to populate form
watch(() => props.entry, (newEntry) => {
  populateForm(newEntry)
}, { immediate: true })

// Watch for modal open/close to reset form
watch(isOpen, (newValue) => {
  if (newValue && !props.entry) {
    resetForm()
  }
})

// Expose methods for parent component
defineExpose({
  setErrors,
  resetForm
})
</script>

<style scoped>
.border-success {
  border-color: rgb(var(--v-theme-success)) !important;
}

.border-error {
  border-color: rgb(var(--v-theme-error)) !important;
}
</style>
