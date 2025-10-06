<template>
  <div v-if="$can('userAttendance', 'view')">

    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">

      <!-- Dialog close btn -->
      <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="List of Tasks">
        <VCardText>
          <div v-if="userWork != null">
            {{ userWork }}
          </div>
          <div v-else class="text-center my-4">
            No Task is there.
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <VCard title="Attendance Management">
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <VCol cols="6">
            <AppDateTimePicker v-model="dateRange" placeholder="Select date range" clearable :config="{
              mode: 'range',
              enableTime: false,
              dateFormat: 'Y-m-d',
              maxDate: moment().format('YYYY-MM-DD')
            }" required />
          </VCol>

          <div class="d-flex flex-row gap-4 align-center flex-wrap">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]"
              @update:modelValue="fetchUserAttendanceList" />
            <VBtn v-if="$can('userAttendance', 'export-list')" @click="handleExport()" :loading="exportLoader"
              :disabled="exportLoader">
              <VIcon icon="tabler-upload" />
              <VTooltip activator="parent" location="top">Export Attendance List</VTooltip>
            </VBtn>
            <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />
          </div>
        </div>
      </VCardText>
      <VDivider />
      <BaseSpinner class="d-flex" v-if="loading" />
      <VCardText v-else class="px-0">
        <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
          :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
          class="text-no-wrap" @update:options="updateOptions">
          <template #item.name="{ item }">
            <RouterLink :to="{ name: 'target-details-id', params: { id: item.id } }"
              class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
              {{ item.name }}
            </RouterLink>
          </template>
          <template #item.status="{ item }">
            {{ (item.status == PRESENT) ? 'Present' : (item.status == ABSENT) ? 'Absent' : 'Half Day' }}
          </template>
          <template #item.attendance_date="{ item }">
            {{ dayjs(item.attendance_date).format('DD-MM-YYYY') }}
          </template>
          <template #item.time_in="{ item }">
            {{ item.time_in ? dayjs(item.time_in, 'HH:mm:ss').format('hh:mm:ss A') : '-' }}
          </template>


          <template #item.time_out="{ item }">
            {{ item.time_out ? dayjs(item.time_out, 'HH:mm:ss').format('hh:mm:ss A') : '-' }}
          </template>
          <!-- Actions Column -->
          <template #item.work="{ item }">
            <VTooltip text="View Task List" location="top">
              <template #activator="{ props }">
                <IconBtn v-bind="props" @click="showMessageDialog(item.work)">
                  <VIcon icon="tabler-checklist" />
                </IconBtn>
              </template>
            </VTooltip>
            <IconBtn
              v-if="$can('userAttendance', 'edit') && isToday($typeAccordingDateFormatChange(item.attendance_date, 'm-d-y'))"
              @click="editTarget(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>
          </template>
          <template #bottom>
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>
    <AddEditDrawer v-model:is-drawer-open="isAddEditDrawerOpen" :currentTarget="currentTarget"
      @submit="fetchUserAttendanceList" @close="isAddEditDrawerOpen = false" />
  </div>
</template>
<script setup>
import dayjs from 'dayjs'
import moment from 'moment'
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import AddEditDrawer from '../add/AddEditDrawer.vue'

import customParseFormat from 'dayjs/plugin/customParseFormat'

dayjs.extend(customParseFormat)

const props = defineProps({
  userInfo: { type: Object, default: null },
})

const searchQuery = ref('')
const isAddEditDrawerOpen = ref(false)
const dateRange = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref('')
const orderBy = ref('')
const currentTarget = ref(null)
const loading = ref(false)
const dataItems = ref([])
const totalItems = ref(0)
const exportLoader = ref(false)
const selectedType = ref("xlsx")
const route = useRoute()
const isDialogVisible = ref(false);
const tableHeaderSlug = ref('user-attendance-list')
const headers = ref([])



const getFilteredHeaderValue = (headerList) => {
  headers.value = headerList
}

// Watch page, itemsPerPage, etc.
watch([page, itemsPerPage, dateRange], () => {
  fetchUserAttendanceList()
})

const editTarget = (item) => {
  currentTarget.value = {
    id: item.id,
    attendance_date: item.attendance_date,
    status: item.status,
    user_id: item.user_id
  }
  isAddEditDrawerOpen.value = true
}

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key ?? ''
  orderBy.value = options.sortBy[0]?.order ?? ''
}

const fetchUserAttendanceList = async () => {
  try {
    loading.value = true

    const params = new URLSearchParams({
      search: searchQuery.value || "",
      page: page.value,
      sort_key: sortBy.value,
      sort_order: orderBy.value,
      per_page: itemsPerPage.value,
    })

    if (dateRange.value) {
      const dates = dateRange.value.split('to')
      if (dates.length === 2) {
        params.append('start_date', dates[0])
        params.append('end_date', dates[1])
      }
    }

    if (route.params.id) {
      params.append('user_id', route.params.id)
    }

    const response = await $api(`/user-attendance?${params.toString()}`)

    dataItems.value = response.data || []
    totalItems.value = response.meta?.total || 0
  } catch (error) {
    toast.error(error?._data?.message ?? "Failed to fetch attendance list.")
  } finally {
    loading.value = false
  }
}
const userWork = ref('');
const showMessageDialog = (work) => {
  isDialogVisible.value = true
  userWork.value = work;
}

const handleExport = async () => {
  return;
  try {
    exportLoader.value = true

    const params = new URLSearchParams({
      search: searchQuery.value || "",
      sort_key: sortBy.value,
      sort_order: orderBy.value,
      export_type: selectedType.value,
      type: 'User-Attendance',
    })

    if (dateRange.value) {
      const dates = dateRange.value.split(',')
      if (dates.length === 2) {
        params.append('start_date', dates[0])
        params.append('end_date', dates[1])
      }
    }

    if (route.params.id) {
      params.append('user_id', route.params.id)
    }

    const response = await $api(`/user-attendance/export?${params.toString()}`, {
      responseType: 'blob',
    })

    if (response.data?.url) {
      const fileResponse = await fetch(response.data.url)
      const fileBlob = await fileResponse.blob()
      const link = document.createElement("a")
      link.href = URL.createObjectURL(fileBlob)
      link.setAttribute("download", response.data.file_name)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    } else {
      console.error("No download URL found.")
    }
  } catch (error) {
    toast.error(error?._data?.message ?? "Export failed!")
  } finally {
    exportLoader.value = false
  }
}

const isToday = (date) => {
  const today = new Date();
  const checkDate = new Date(date);
  if (!props.userInfo) return true;
  return !props.userInfo.is_admin && today.toDateString() === checkDate.toDateString();
};

onMounted(() => {
  fetchUserAttendanceList();
})
</script>
