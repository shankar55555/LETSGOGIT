  <script setup>
  import moment from 'moment';
  import { onMounted, ref } from 'vue';
  import { toast } from 'vue3-toastify';
  const searchQuery = ref('')
  const itemsPerPage = ref(10)
  const page = ref(1)
  const tableHeaderSlug = ref('attendance-list')
  const headers = ref([])
  const getFilteredHeaderValue = async (headerList) => { headers.value = headerList }
  const isLoading = ref(false)
  const attendanceRecords = ref([])
  const currentStatus = ref('')
  const showManualDialog = ref(false)
  const showEditDialog = ref(false)
  const totalItems = ref(0)
  const manualEntry = ref({
    login_time: '',
    logout_time: ''
  })
  const selectedRecord = ref(null)
  const fetchAttendanceRecords = async () => {
    try {
      isLoading.value = true
      const response = await $api('/attendance/records')
      attendanceRecords.value = response.data
      totalItems.value = response.meta.total
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to fetch attendance records')
    } finally {
      isLoading.value = false
    }
  }
  const recordLogin = async () => {
    try {
      isLoading.value = true
      await $api('/attendance/login', { method: 'POST' })
      currentStatus.value = 'active'
      toast.success('Login recorded successfully')
      await fetchAttendanceRecords()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to record login')
    } finally {
      isLoading.value = false
    }
  }
  const recordLogout = async () => {
    try {
      isLoading.value = true
      await $api('/attendance/logout', { method: 'POST' })
      currentStatus.value = 'in-active'
      toast.success('Logout recorded successfully')
      await fetchAttendanceRecords()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to record logout')
    } finally {
      isLoading.value = false
    }
  }
  const addManualAttendance = async () => {
    try {
      isLoading.value = true
      if (!manualEntry.value.login_time || !manualEntry.value.logout_time) {
        toast.error('Please fill in all required fields')
        return
      }
      const payload = {
        login_time: manualEntry.value.login_time,
        logout_time: manualEntry.value.logout_time
      }
      await $api('/attendance', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      toast.success('Manual attendance recorded successfully')
      showManualDialog.value = false
      manualEntry.value = { login_time: '', logout_time: '' }
      await fetchAttendanceRecords()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to record manual attendance')
    } finally {
      isLoading.value = false
    }
  }
  const openEditDialog = (record) => {
    selectedRecord.value = { ...record }
    showEditDialog.value = true
  }
  const updateManualAttendance = async () => {
    try {
      isLoading.value = true
      const payload = {
        login_time: selectedRecord.value.login_time,
        logout_time: selectedRecord.value.logout_time
      }
      await $api(`/attendance/${selectedRecord.value.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      toast.success('Manual attendance updated successfully')
      showEditDialog.value = false
      selectedRecord.value = null
      await fetchAttendanceRecords()
    } catch (error) {
      console.log(error);
      toast.error(error.response?.data?.message || 'Failed to update attendance')
    } finally {
      isLoading.value = false
    }
  }
  onMounted(() => { fetchAttendanceRecords() })
  const makeDateFormat = (date, onlyDate = false) => {
    return onlyDate
      ? moment(date).format('DD-MM-YYYY')
      : moment(date).format('dddd, MMMM D, YYYY');
  };
</script>
  <template>
    <div v-if="$can('attendance', 'view')">
      <VCard>
        <VCardText>
          <div class="d-flex justify-space-between flex-wrap gap-y-4">
            <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
              placeholder="Search Name" @input="fetchClients" />
            <div class="d-flex flex-row gap-4 align-center flex-wrap">
              <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" @update:modelValue="fetchClients" />
              <VBtn v-if="$can('attendance', 'export-list')" prepend-icon="tabler-upload" variant="tonal"
                color="secondary">
                Export
              </VBtn>
              <div class="d-flex gap-4">
                <VBtn color="info" :loading="isLoading" @click="showManualDialog = true">
                  <VIcon start>tabler-calendar-plus</VIcon>Add Manual
                </VBtn>
                <VBtn color="primary" :loading="isLoading" @click="recordLogin" :disabled="currentStatus === 'active'">
                  <VIcon start>tabler-login</VIcon>Login
                </VBtn>
                <VBtn color="error" :loading="isLoading" @click="recordLogout"
                  :disabled="currentStatus === 'in-active'">
                  <VIcon start>tabler-logout</VIcon>Logout
                </VBtn>
              </div>
              <!-- Filter Header Btn -->
              <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />
            </div>
          </div>
        </VCardText>
        <VDivider />
        <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="attendanceRecords"
          :headers="headers.filter((header) => header.checked)" :items-length="totalItems" :loading="loading"
          @update:options="loadItems" class="text-no-wrap">
          <!-- Your column templates remain the same -->
          <template #item.attendance_date="{ item }">
            {{ makeDateFormat(item.attendance_date) }}
          </template>
          <template #item.login_time="{ item }">
            {{ (item.login_time) }}
          </template>
          <template #item.logout_time="{ item }">
            {{ item.logout_time ? (item.logout_time) : '—' }}
          </template>
          <template #item.duration="{ item }">
            {{ (item.duration, item.duration) }}
          </template>
          <template #item.action="{ item }">
            <VBtn v-if="item.is_manual" size="small" icon @click="openEditDialog(item)">
              <VIcon>tabler-edit</VIcon>
            </VBtn>
          </template>
          <template #bottom>
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
          </template>
        </VDataTableServer>
      </VCard>
    </div>
    <VDialog v-model="showManualDialog" max-width="500px">
      <VCard>
        <VCardTitle>Add Manual Attendance</VCardTitle>
        <VCardText>
          <VForm>
            <VRow>
              <VCol cols="12">
                <AppDateTimePicker v-model="manualEntry.login_time" label="Login Time" placeholder="Login Time"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" :rules="[requiredValidator]" />

              </VCol>
              <VCol cols="12">
                <AppDateTimePicker v-model="manualEntry.logout_time" label="Logout Time" placeholder="Logout Time"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="error" variant="text" @click="showManualDialog = false">Cancel</VBtn>
          <VBtn color="primary" :loading="isLoading" @click="addManualAttendance">Save</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    <VDialog v-model="showEditDialog" max-width="500px">
      <template v-if="selectedRecord">
        <VCard>
          <VCardTitle>Edit Manual Attendance</VCardTitle>
          <VCardText>
            <VForm>
              <VRow>
                <VCol cols="12">
                  <AppDateTimePicker v-model="selectedRecord.login_time" label="Login Time" placeholder="Login Time"
                    :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" :rules="[requiredValidator]" />
                </VCol>
                <VCol cols="12">
                  <AppDateTimePicker v-model="selectedRecord.logout_time" label="Logout Time" placeholder="Logout Time"
                    :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" :rules="[requiredValidator]" />
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
          <VCardActions>
            <VSpacer />
            <VBtn color="error" variant="text" @click="() => { showEditDialog = false; selectedRecord = null }">Cancel
            </VBtn>
            <VBtn color="primary" :loading="isLoading" @click="updateManualAttendance">Update</VBtn>
          </VCardActions>
        </VCard>
      </template>
    </VDialog>
  </template>
<style scoped>
.text-link {
  color: rgba(var(--v-theme-primary), var(--v-high-emphasis-opacity));
  text-decoration: underline;
}
</style>
