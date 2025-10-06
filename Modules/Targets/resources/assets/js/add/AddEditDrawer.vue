<script setup>
import { nextTick, ref } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'

import { toast } from 'vue3-toastify'

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
  title: '',
  target_type: '',
  target_value: '',
  target_amount: '',
  incentive_percent: '',
})


const resetForm = () => {
  target.value = {
    title: '',
    target_type: '',
    target_value: '',
    target_amount: '',
    incentive_percent: '',
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


const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

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

    const endpoint = props.currentTarget
      ? `/targets/${props.currentTarget.id}?_method=PUT`
      : '/targets'

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
    console.error(err)
    toast.error(err?._data?.message || 'Error occurred')
  } finally {
    isSubmitting = false
    isLoading.value = false
  }
}
</script>

<template>
  <VNavigationDrawer :model-value="props.isDrawerOpen" temporary location="end" width="370" border="none"
    @update:model-value="handleDrawerModelValueUpdate">
    <AppDrawerHeaderSection :title="props.currentTarget ? 'Edit Target' : 'Add Target'" @cancel="closeNavigationDrawer" />

    <VDivider />

    <VCard flat>
      <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
        <VCardText style="block-size: calc(100vh - 5rem);">
          <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <AppTextField v-model="target.title" label="Title*" :rules="[requiredValidator]" />
              </VCol>

              <VCol cols="12">
                <AppSelect v-model="target.target_type" label="Target Type*" :items="['Daily','Weekly','Monthly']" :rules="[requiredValidator]" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="target.target_value" label="Targets*" type="number" :rules="[requiredValidator]"
                    />
              </VCol>
              <VCol cols="12" md="12">
                  <AppTextField v-model="target.target_amount" :rules="[requiredValidator]" type="number" label="Amount*" />
                </VCol>

                <VCol cols="12" md="12">
                  <AppTextField v-model="target.incentive_percent" :rules="[requiredValidator]" type="number" label="Incentive percentage*" />
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
