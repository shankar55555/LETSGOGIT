<script setup>
import moment from 'moment'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'

const router = useRouter()
const isLoading = ref(false)
const attendanceRecords = ref([])
const currentStatus = ref('')
const showManualDialog = ref(false)
const showEditDialog = ref(false)
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

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const makeDateFormat = (date, onlyDate = false) => {
  return onlyDate ? moment(date).format('DD-MM-Y') : moment(date).format('LLLL')
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
    toast.error(error.response?.data?.message || 'Failed to update attendance')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => { fetchAttendanceRecords() });
</script>



<template>
  <VCard>
    <VCardTitle class="d-flex justify-space-between align-center">
      <span>Attendance Tracking</span>
      <div class="d-flex gap-4">
        <VBtn color="info" :loading="isLoading" @click="showManualDialog = true">
          <VIcon start>tabler-calendar-plus</VIcon>Add Manual Entry
        </VBtn>
        <VBtn color="primary" :loading="isLoading" @click="recordLogin" :disabled="currentStatus === 'active'">
          <VIcon start>tabler-login</VIcon>Login
        </VBtn>
        <VBtn color="error" :loading="isLoading" @click="recordLogout" :disabled="currentStatus === 'in-active'">
          <VIcon start>tabler-logout</VIcon>Logout
        </VBtn>
      </div>
    </VCardTitle>
    <VDivider />
    <VCardText>
      <VTable>
        <thead>
          <tr>
            <th>Date</th>
            <th>Login Time</th>
            <th>Logout Time</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in attendanceRecords" :key="record.id">
            <td>{{ makeDateFormat(record.login_time, true) }}</td>
            <td>{{ makeDateFormat(record.login_time) }}</td>
            <td>{{ record.logout_time ? makeDateFormat(record.logout_time) : '-' }}</td>
            <td>
              {{
                record.logout_time
                  ? (() => {
                    const diff = new Date(record.logout_time) - new Date(record.login_time)
                    const mins = Math.floor(diff / (1000 * 60))
                    const hrs = Math.floor(mins / 60)
                    const remMin = mins % 60
                    return `${hrs ? hrs + ' hour' + (hrs > 1 ? 's' : '') : ''} ${remMin} minute${remMin !== 1 ? 's' : ''}`
                  })()
                  : 'In Progress'
              }}
            </td>
            <td>
              <VChip :color="record.status === 'active' ? 'success' : 'error'" size="small">
                {{ record.status }}
              </VChip>
            </td>
            <td>
              <VBtn v-if="record.is_manual" size="small" icon @click="openEditDialog(record)">
                <VIcon>tabler-edit</VIcon>
              </VBtn>
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCardText>

    <!-- Manual Entry Dialog -->
    <VDialog v-model="showManualDialog" max-width="500px">
      <VCard>
        <VCardTitle>Add Manual Attendance</VCardTitle>
        <VCardText>
          <VForm>
            <VRow>
              <VCol cols="12">
                <VTextField v-model="manualEntry.login_time" label="Login Time" type="datetime-local" required />
              </VCol>
              <VCol cols="12">
                <VTextField v-model="manualEntry.logout_time" label="Logout Time" type="datetime-local" required />
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

    <!-- Edit Manual Entry Dialog -->
  </VCard>
  <VDialog v-model="showEditDialog" max-width="500px">
    <template v-if="selectedRecord">
      <VCard>
        <VCardTitle>Edit Manual Attendance</VCardTitle>
        <VCardText>
          <VForm>
            <VRow>
              <VCol cols="12">
                <VTextField v-model="selectedRecord.login_time" label="Login Time" type="datetime-local" required />
              </VCol>
              <VCol cols="12">
                <VTextField v-model="selectedRecord.logout_time" label="Logout Time" type="datetime-local" required />
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
