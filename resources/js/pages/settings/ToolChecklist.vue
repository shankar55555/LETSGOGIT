<script setup>
import { onMounted, ref } from 'vue'
import { toast } from 'vue3-toastify'
import { VForm } from 'vuetify/components'

const refForm = ref()
const valid = ref(true)
const isLoading = ref(false)
const fieldloader = ref(false);
const tools_checklist = ref([])


// Submit form
const onSubmit = async () => {
  isLoading.value = true;
  try {
    const payload = {
      tools_checklist: tools_checklist.value
    }
    const res = await $api('/product-checklist', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    isLoading.value = false;
    toast.success(res?.data?.message || 'Tools checklist updated successfully!')
  } catch (err) {
    isLoading.value = false;
    console.error(err)
    toast.error(err?._data?.message || 'An error occurred while saving.')
  }
}

function removeToolChecklistTag(index) {
  tools_checklist.value.splice(index, 1)
}

const fetchChecklist = async () => {
  fieldloader.value = true;

  try {
    const { data } = await $api('/product-checklist', {
    })
    tools_checklist.value = data // adapt if your API response is shaped differently
    fieldloader.value = false;

  } catch (e) {
    console.error('Failed to load tok', e)
    fieldloader.value = false;
  }
}

onMounted(() => {
  fetchChecklist();
})
</script>

<template>
  <VCard flat>
    <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">

      <!-- <VProgressLinear v-if="isLoading" indeterminate color="primary"></VProgressLinear> -->

      <VCardText style="block-size: calc(100vh - 5rem);">
        <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" class="mt-4">
              <BaseSpinner class="d-flex" v-if="fieldloader" />

              <VRow v-else>

                <VCol cols="12" md="12">
                  <label for="">Tools Checklist*</label>
                  <VCombobox v-model="tools_checklist" multiple :items="[]" chips
                    placeholder="Enter title and press enter" hint="Enter title and press enter"
                    :rules="[requiredValidator]">
                    <template v-slot:chip="{ item, index }">
                      <VChip class="ma-1" color="primary">
                        {{ item.raw }}
                        <v-icon @click="removeToolChecklistTag(index)" class="ml-1" size="large"
                          icon="tabler-circle-letter-x"></v-icon>
                      </VChip>
                    </template>
                  </VCombobox>
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12" class="d-flex gap-4 justify-start pt-6 pb-10">
              <VBtn type="submit" color="primary" :loading="isLoading">
                Save
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

    </PerfectScrollbar>
  </VCard>
</template>

<style scoped>
.chip_clear_icon {
  block-size: 13px !important;
}
</style>
