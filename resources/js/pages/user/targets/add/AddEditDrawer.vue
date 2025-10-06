<script setup>
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect'
import 'flatpickr/dist/plugins/monthSelect/style.css'
import { nextTick, ref } from 'vue'
import { useRoute } from 'vue-router'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { toast } from 'vue3-toastify'
import { VForm } from 'vuetify/components/VForm'
const route = useRoute()

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  currentTarget: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'submit'])

const refForm = ref()
const valid = ref(true)
const isLoading = ref(false)
let isSubmitting = false

const target = ref({
  month: '',
  target_amount: '',
  achieved_amount: '',
  incentive_percentage: '',
})

const resetForm = () => {
  target.value = {
    month: '',
    target_amount: '',
    achieved_amount: '',
    incentive_percentage: '',
  }
}

watch(
  () => props.isDrawerOpen,
  (val) => {
    if (val) {
      if (props.currentTarget?.id) {
        target.value = JSON.parse(JSON.stringify(props.currentTarget))
      } else {
        resetForm()
      }

      // Optionally reset form validations too
      nextTick(() => {
        refForm.value?.resetValidation()
      })
    }
  }
)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  // Reset target data
  resetForm()

  // Emit a reset event to clear currentTarget in the parent
  // Wait for DOM updates before resetting validation
  nextTick(() => {
    refForm.value?.resetValidation()
  })
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

    const payload = target.value;
    payload.user_id = route.params.id;

    const endpoint = props.currentTarget
      ? `/user-targets/${props.currentTarget.id}?_method=PUT`
      : '/user-targets'

    const res = await $api(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Saved successfully')
      emit('update:isDrawerOpen', false)
      emit('submit')
      resetForm()
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'Error occurred')
  } finally {
    isSubmitting = false
    isLoading.value = false
  }
}

// Get the first day of next month
const firstOfNextMonth = new Date()
firstOfNextMonth.setMonth(firstOfNextMonth.getMonth() + 1)
firstOfNextMonth.setDate(1)

// Custom enable function: only enable the 1st of each month
const enableFirstDayOnly = (date) => {
  return date.getDate() === 1 && date < firstOfNextMonth
}
</script>

<template>

  <VNavigationDrawer :model-value="props.isDrawerOpen" temporary location="end" width="370" border="none">
    <AppDrawerHeaderSection :title="props.currentTarget ? 'Edit Target' : 'Add Target'"
      @cancel="closeNavigationDrawer" />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VLabel>Month <span style="color: red;">*</span></VLabel>
                <AppDateTimePicker v-model="target.month" placeholder="Select Month" :config="{
                  dateFormat: 'Y-m',
                  plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m', altFormat: 'F Y' })],
                  // maxDate: new Date(),
                }" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="12">
                <VLabel>Target Amount <span style="color: red;">*</span></VLabel>
                <AppTextField v-model="target.target_amount" :rules="[requiredValidator]" type="number" />
              </VCol>
              <VCol cols="12">
                <VLabel>Achieved Amount (optional)</VLabel>
                <AppTextField v-model="target.achieved_amount" type="number" />
                <VCol cols="12">
                  <VLabel>How many Percentage <span style="color: red;">*</span></VLabel>
                  <AppSelect v-model="target.incentive_percentage"
                    :items="[{ title: '25%', value: 25 }, { title: '50%', value: 50 }, { title: '75%', value: 75 }, { title: '100%', value: 100 }]"
                    :rules="[requiredValidator]" />
                </VCol>
              </VCol>
              <VCol cols="12" class="d-flex gap-4 justify-start pb-10">
                <VBtn type="submit" color="primary" :loading="isLoading">
                  {{ props.currentTarget ? 'Update' : 'Add' }}
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
