<script setup>
import moment from 'moment'
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
const route = useRoute()
const refForm = ref()
const valid = ref(true)
const isLoading = ref(false)
let isSubmitting = false

// Props definition
const props = defineProps({
  initialData: {
  type: Object,
  required: true,
  default: () => ({
    attendance_date: moment().format('YYYY-MM-DD'), // Keep as attendance_date
    status: 'present',
    user_id: null
  })
},
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  currentTarget: {
    type: Object,
    default: null
  }
})

// Event emissions
const emit = defineEmits([
  'submit',
  'cancel',
  'update:isDrawerOpen'
])

// Reactive form data
// Reactive form data
const form = ref({ ...props.initialData })

// Watch for changes in currentTarget (edit mode)
watch(() => props.currentTarget, (newTarget) => {
  if (newTarget) {
    form.value = {
      attendance_date: newTarget.attendance_date,
      status: newTarget.status,
      user_id: newTarget.user_id
    }
  } else {
    // fallback for add mode
    form.value = {
      attendance_date: moment().format('YYYY-MM-DD'),
      status: 'present',
      user_id: route.params.id
    }
  }
}, { immediate: true })



// Status options for dropdown
const statusOptions = [
  { title: 'Present', value: PRESENT},
  { title: 'Half Day', value: HALF_PRESENT },
  { title: 'Absent', value: ABSENT}
]

// Prevent selecting future dates
const validateDate = (date) => {
  return moment(date).isSameOrBefore(moment(), 'day') || 'Future dates not allowed'
}

const resetForm = () => {
  form.value = {
    attendance_date: moment().format('YYYY-MM-DD'), // Changed from date to attendance_date
    status: 'present',
    user_id: route.params.id
  }
}


const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('cancel')
  resetForm()
}

const submitForm = async () => {
  if (isSubmitting) return
  isSubmitting = true

  const { valid: isValid } = await refForm.value.validate()
  if (!isValid) {
    isSubmitting = false
    return
  }

  try {
    isLoading.value = true

    const payload = {
      ...form.value,
      user_id: route.params.id,
      date: moment(form.value.date).format('YYYY-MM-DD'),
      // work: 
    }

    const endpoint = props.currentTarget
      ? `/user-attendance/${props.currentTarget.id}?_method=PUT`
      : '/user-attendance'

    const res = await $api(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res?.data?.message || 'Attendance saved successfully')
      emit('update:isDrawerOpen', false)
      emit('submit', res.data)
      resetForm()
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'Failed to save attendance')
  } finally {
    isSubmitting = false
    isLoading.value = false
  }
}
</script>

<template>
  <VNavigationDrawer 
    :model-value="props.isDrawerOpen" 
    temporary 
    location="end" 
    width="400" 
    border="none"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <AppDrawerHeaderSection 
      :title="props.currentTarget ? 'Edit Attendance' : 'Add Attendance'" 
      @cancel="closeNavigationDrawer" 
    />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="submitForm">
            <VRow>
              <VCol cols="12">
                <AppDateTimePicker  disabled
                  v-model="form.attendance_date" 
                  label="Attendance Date" 
                  placeholder="Select date"
                  :config="{ 
                    enableTime: false, 
                    dateFormat: 'Y-m-d',
                    maxDate: moment().format('YYYY-MM-DD')
                  }"
                  :rules="[validateDate]"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="form.status"
                  label="Attendance Status"
                  :items="statusOptions"
                  :rules="[v => !!v || 'Status is required']"
                  required
                />
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-3 mt-4">
                <VBtn
                  type="button"
                  variant="tonal"
                  color="secondary"
                  :disabled="isLoading"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="isLoading"
                  :disabled="isLoading"
                >
                  Save Attendance
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </PerfectScrollbar>
    </VCard>
  </VNavigationDrawer>
</template>
