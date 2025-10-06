<template>
  <div>
    <!-- Header line with Client Info and Refresh Button -->
    <!-- <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('user', 'view')">
      <div class="text-subtitle-1 font-weight-medium">User Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;"></div>
    </div> -->
    <!-- Cards Section -->
    <VRow v-if="$can('user', 'view')">
      <!-- <VCol cols="12" md="3" sm="6" v-if="$can('user', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-warning),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.user_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Users</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3" sm="6" v-if="$can('user', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-warning),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.in_active_user_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">In-Active Users</div>
          </VCardText>
        </VCard>
      </VCol> -->


    </VRow>
    <!-- Header line with Client Info and Refresh Button -->
    <!-- <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('client', 'view')">
      <div class="text-subtitle-1 font-weight-medium">Client Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;"></div>
    </div> -->
    <!-- Cards Section -->
    <VRow v-if="$can('client', 'view')">

      <!-- <VCol cols="12" md="3" sm="6" v-if="$can('client', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-warning),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.client_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Clients</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="3" sm="6" v-if="$can('client', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-warning),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.non_in_active_client_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Non In-Active Clients</div>
          </VCardText>
        </VCard>
      </VCol> -->
    </VRow>

    <!-- Header line with Lead Info and Refresh Button -->
    <!-- <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('leads', 'view')">
      <div class="text-subtitle-1 font-weight-medium">Lead Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;"></div>
    </div> -->

    <!-- Cards Section -->
    <!-- <VRow v-if="$can('leads', 'view')">
      <VCol cols="12" md="3" sm="6" v-if="$can('leads', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.lead_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Leads</div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6" v-if="$can('leads', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.non_in_active_lead_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Non In-Active Leads</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow> -->

    <!-- Header line with Quotation Info and Refresh Button -->
    <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('quotation', 'view')">
      <div class="text-subtitle-1 font-weight-medium">Quotation Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>

    <!-- this will show the quotation and leads  -->
    <InfoCard v-if="$can('quotation', 'view')" :quotation-data="quotationData" />

    <!-- Cards Section -->
    <!-- <VRow v-if="$can('quotation', 'view')">
      <VCol cols="12" md="3" sm="6" v-if="$can('quotation', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.quotation_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Quotations</div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6" v-if="$can('quotation', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.pending_quotation_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Pending Quotations</div>
          </VCardText>
        </VCard>
      </VCol>


      <VCol cols="12" md="3" sm="6" v-if="$can('quotation', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.accepted_quotation_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Accepted Quotations</div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6" v-if="$can('quotation', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.cancel_quotation_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Cancelled Quotations</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow> -->


    <!-- Header line with Invoice Info and Refresh Button -->
    <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('invoice', 'view')">
      <div class="text-subtitle-1 font-weight-medium">Invoice Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>

    <!-- this is show the invoice data -->
    <Invoice :invoice-data="invoiceData" />

    <!-- Cards Section -->
    <!-- <VRow v-if="$can('invoice', 'view')">
      <VCol cols="12" md="3" sm="6" v-if="$can('invoice', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.invoice_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Invoices</div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6" v-if="$can('invoice', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.un_paid_invoice_count }}</h4>
            </div>
            <div class="text-body-1 mb-1">Un-Paid Invoices</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow> -->


    <!-- Header line with User Attendance Info and Refresh Button -->
    <div class="d-flex justify-space-between align-center mt-2 mb-2"
      v-if="$can('userAttendance', 'view') && dashboardInfo.mark_attendance">
      <div class="text-subtitle-1 font-weight-medium">User Attendance Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>
    <!-- Cards Section -->
    <VRow v-if="$can('userAttendance', 'view') && dashboardInfo.mark_attendance">
      <VCol cols="12" md="4" sm="6" v-if="$can('userAttendance', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex justify-space-between align-center mb-2">
              <div class="text-center">
                <h4 class="text-h4 mb-1">
                  {{ dashboardInfo.last_month_salary > 0 ? parseInt(dashboardInfo.last_month_salary) : '' }} / {{
                    dashboardInfo.user_salary ?? 0 }}
                </h4>
                <div class="text-caption">Last Month Salary</div>
              </div>

              <div class="text-center">
                <h4 class="text-h4 mb-1">
                  {{ dashboardInfo.last_month_total_attendance > 0 ? dashboardInfo.last_month_present_attendance : '-'
                  }} / {{ dashboardInfo.last_month_total_attendance }}
                </h4>
                <div class="text-caption">Last Month Attendance</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6" v-if="$can('userAttendance', 'view')">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4"> {{ dashboardInfo.this_month_present_attendance }} / {{
                dashboardInfo.this_month_total_attendance }}</h4>
            </div>
            <div class="text-body-1 mb-1">This Month Attendance</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Header line with Target Info and Refresh Button -->
    <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('targets', 'view')">
      <div class="text-subtitle-1 font-weight-medium">User Target-Incentive Info : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>

    <!-- this is show the Incentive data -->
    <Incentive :incentive-data="incentiveData" />

    <!-- Cards Section -->
    <!-- <VRow>
      <VCol cols="12" md="3" sm="6">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.user_incentive_amount }}</h4>
            </div>
            <div class="text-body-1 mb-1">Last Month Incentive</div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="3" sm="6">
        <VCard class="logistics-card-statistics cursor-pointer"
          :style="`border-block-end-color: rgba(var(--v-theme-primary),0.38)`">
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-1">
              <h4 class="text-h4">{{ dashboardInfo.this_month_user_total_target }}</h4>
            </div>
            <div class="text-body-1 mb-1">This Month Target</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow> -->

    <!-- Upcoming events -->
    <div class="d-flex justify-space-between align-center mt-2 mb-2" v-if="$can('invoice', 'view')">
      <div class="text-subtitle-1 font-weight-medium">Upcoming Events : </div>
      <div class="ml-2 mr-2"
        style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
      </div>
    </div>

    <!-- Upcoming events -->
    <UpcomingEventsCalender
      v-if="$can('user-upcoming-dates', 'view') || $can('lead-upcoming-dates', 'view') || $can('client-upcoming-dates', 'view')" />

    <!-- 
    <UpcomingEventSection v-if="$can('user-upcoming-dates', 'view')" title="Upcoming My Team Birthdays"
      :members="teamBirthdays" themeColor="primary" :formatDate="formatAnniversary" :isTodayFn="isAnniversaryToday"
      :avatarText="avatarText" column="birthday" position="Team Member" />

    <UpcomingEventSection v-if="$can('user-upcoming-dates', 'view')" title="Upcoming Team Anniversaries"
      :members="teamAnniversaries" themeColor="success" :formatDate="formatAnniversary" :isTodayFn="isAnniversaryToday"
      :avatarText="avatarText" column="anniversary" position="Team Member" />

    <UpcomingEventSection v-if="$can('lead-upcoming-dates', 'view')" title="Upcoming Lead Birthdays"
      :members="LeadBirthdays" themeColor="success" :formatDate="formatAnniversary" :isTodayFn="isAnniversaryToday"
      :avatarText="avatarText" column="birthday" position="Lead" />

    <UpcomingEventSection v-if="$can('lead-upcoming-dates', 'view')" title="Upcoming Lead Anniversaries"
      :members="LeadAnniversaries" themeColor="success" :formatDate="formatAnniversary" :isTodayFn="isAnniversaryToday"
      :avatarText="avatarText" column="anniversary" position="Lead" />

    <UpcomingEventSection v-if="$can('client-upcoming-dates', 'view')" title="Upcoming Client Birthdays"
      :members="ClientBirthdays" themeColor="success" :formatDate="formatAnniversary" :isTodayFn="isAnniversaryToday"
      :avatarText="avatarText" column="birthday" position="Client" />

    <UpcomingEventSection v-if="$can('client-upcoming-dates', 'view')" title="Upcoming Client Anniversaries"
      :members="ClientAnniversaries" themeColor="success" :formatDate="formatAnniversary"
      :isTodayFn="isAnniversaryToday" :avatarText="avatarText" column="anniversary" position="Client" /> -->

    <!-- Upcoming Service Sites Next 14 Day -->
    <UpcomingSiteRisk class="mt-4" v-if="$can('siteVisit', 'view')" title="Potential Upcoming Service Sites" />

    <!-- user information graph -->
    <v-row class="mt-2">
      <VCol cols="12">
        <!-- <VCard title="User Information" class="mb-6 pa-3"> -->
        <UserCountChart />
        <!-- </VCard> -->
      </VCol>
    </v-row>

    <v-row>
      <VCol cols="12">
        <!-- <VCard title="Leads and Client Info" class="mb-6 pa-3">
          <template #append>
            <VSelect v-model="selectedOption" :items="items" label="Select" placeholder="Select Type" />
          </template> -->
        <!-- :type="selectedOption" -->
        <ClientAndLeadInfoChart />
        <!-- </VCard> -->
      </VCol>
    </v-row>

    <!-- 🔽 Modal -->
    <VDialog v-model="isAttendanceModalOpen" max-width="500">
      <VCard>
        <VCardTitle class="text-h5">Mark Attendance</VCardTitle>
        <VCardText>
          <VCheckbox v-model="attendanceStatus.present" label="Present"
            :disabled="attendanceStatus.halfPresent || attendanceStatus.absent" />
          <VCheckbox v-model="attendanceStatus.halfPresent" label="Half-Present"
            :disabled="attendanceStatus.present || attendanceStatus.absent" />

          <!-- <h5 class="mt-4 ml-2">Mark In or Out</h5>
          <div class="d-flex">
            <VCheckbox v-model="attendanceStatus.timeIn" label="Time In" :disabled="attendanceStatus.timeOut"
              class="mr-3" />
            <VCheckbox v-model="attendanceStatus.timeOut" label="Time out" :disabled="attendanceStatus.timeIn" />
          </div> -->

          <!-- <AppTextarea label="Work Description" v-model="todayUserWork" placeholder="Add your work" class="mt-3 ml-3" /> -->

        </VCardText>
        <VCardActions class="justify-end">
          <VBtn color="primary" @click="submitAttendance">Marked</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { toast } from 'vue3-toastify';
import { VCard, VCol } from 'vuetify/components';
import UpcomingSiteRisk from "../../../../../../resources/js/components/UpcomingSiteRisk.vue";
import ClientAndLeadInfoChart from '../components/ClientAndLeadInfoChart.vue';
import Incentive from '../components/Incentive.vue';
import InfoCard from '../components/InfoCard.vue';
import Invoice from '../components/Invoice.vue';
import UpcomingEventsCalender from '../components/UpcomingEventsCalender.vue';
import UserCountChart from '../components/UserCountChart.vue';

// const selectedOption = ref('Leads')
// const items = ['Leads', 'Clients']

// Compute quotation data for Info.vue
const quotationData = computed(() => [
  {
    icon: 'tabler-file-text',
    color: 'primary',
    title: 'Quotations',
    percentage: '+3.5%',
    percentageColor: 'success',
    value: dashboardInfo.value.quotation_count || 0,
    isHover: false,
  },
  {
    icon: 'tabler-hourglass',
    color: 'warning',
    title: 'Pending Quotations',
    percentage: '-15%',
    percentageColor: 'error',
    value: dashboardInfo.value.pending_quotation_count || 0,
    isHover: false,
  },
  {
    icon: 'tabler-circle-check',
    color: 'success',
    title: 'Total Invoice',
    // percentage: '0%',
    value: dashboardInfo.value.invoice_count || 0,
    isHover: false,
  },
  {
    icon: 'tabler-file-text',
    color: 'primary',
    title: 'Total Leads',
    percentage: '2%',
    percentageColor: 'success',
    value: dashboardInfo.value.lead_count || 0,
    isHover: false,
  },
]);

// Compute invoice data for Invoice.vue
const invoiceData = computed(() => [
  {
    icon: 'tabler-file-invoice',
    color: 'primary',
    title: 'Invoices',
    value: dashboardInfo.value.invoice_count || 0,
    isHover: false,
  },
  {
    icon: 'tabler-circle-x',
    color: 'error',
    title: 'Un-Paid Invoices',
    value: dashboardInfo.value.un_paid_invoice_count || 0,
    isHover: false,
  },
]);

// Compute invoice data for Incentive.vue
const incentiveData = computed(() => [
  {
    icon: 'tabler-file-invoice',
    color: 'primary',
    title: 'Last Month Incentive',
    value: dashboardInfo.value.user_incentive_amount || 0,
    isHover: false,
  },
  {
    icon: 'tabler-circle-x',
    color: 'error',
    title: 'This Month Target',
    value: dashboardInfo.value.this_month_user_total_target || 0,
    isHover: false,
  },
]);

const isSubmitting = ref(false)
const dashboardInfo = ref({})
const getDashboard = async () => {
  try {
    const response = await $api('/dashboard')
    console.log("the dashboard response is :", response)
    dashboardInfo.value = response.data
  } catch (err) {
  }
}
getDashboard()

// Attendance modal logic
const isAttendanceModalOpen = ref(false);

// In your Vue component
const hasMarkedToday = ref(false);
const checkAttendance = async () => {
  try {
    const response = await $api('/check-todays-attendance');
    hasMarkedToday.value = response.has_marked_attendance;
    if (hasMarkedToday.value) {
      isAttendanceModalOpen.value = false
    } else {
      isAttendanceModalOpen.value = true
    }
  } catch (error) {
    // console.error('Error checking attendance:', error);
  }
};

// const teamBirthdays = ref([])
// const teamAnniversaries = ref([])

// const getTeamEvents = async () => {
//   try {
//     const response = await $api('/upcoming-team-events')

//     teamBirthdays.value = response.data.birthdays || []
//     teamAnniversaries.value = response.data.anniversaries || []
//   } catch (err) {
//     teamBirthdays.value = [];
//     teamAnniversaries.value = [];
//   }
// }


// const LeadBirthdays = ref([])
// const LeadAnniversaries = ref([])


// const getLeadEvents = async () => {
//   try {
//     const response = await $api('/upcoming-lead-events')
//     LeadBirthdays.value = response.data.birthdays || []
//     LeadAnniversaries.value = response.data.anniversaries || []

//   } catch (err) {
//     LeadBirthdays.value = [];
//     LeadAnniversaries.value = [];
//   }
// }

// const ClientBirthdays = ref([])
// const ClientAnniversaries = ref([])

// const getClientEvents = async () => {
//   try {
//     const response = await $api('/upcoming-client-events')
//     ClientBirthdays.value = response.data.birthdays || []
//     ClientAnniversaries.value = response.data.anniversaries || []

//   } catch (err) {
//     ClientBirthdays.value = []
//     ClientAnniversaries.value = []

//   }
// }

// function formatAnniversary(dateStr) {
//   if (!dateStr) return ''
//   const date = new Date(dateStr)
//   return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
// }

// function isAnniversaryToday(dateStr) {
//   if (!dateStr) return false
//   const today = new Date()
//   const date = new Date(dateStr)
//   return today.getDate() === date.getDate() && today.getMonth() === date.getMonth()
// }

onMounted(() => {
  checkAttendance()
  // getTeamEvents()
  // getLeadEvents()
  // getClientEvents()
})

const attendanceStatus = ref({
  present: false,
  halfPresent: false,
  absent: false,
  timeIn: true,
});

// const todayUserWork = ref('');

const submitAttendance = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true

  // Determine the selected status
  let status = ''
  if (attendanceStatus.value.present) status = PRESENT
  else if (attendanceStatus.value.halfPresent) status = HALF_PRESENT // or whatever your API expects
  else if (attendanceStatus.value.absent) status = ABSENT


  if (!status) {
    toast.error('Please select an attendance status')
    isSubmitting.value = false
    return
  }

  try {
    // Get current date in YYYY-MM-DD format
    const today = new Date().toISOString().split('T')[0]

    const payload = {
      attendance_date: today, // Today's date
      status: status,
      // work: todayUserWork.value ?? '',
      timeDuration: attendanceStatus.value.timeIn ? 'morningTime' : 'eveningTime',
    }

    const res = await $api('/user-attendance', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (res?.data) {
      toast.success(res.data.message || 'Attendance saved successfully')
      isAttendanceModalOpen.value = false
      // Reset checkboxes
      attendanceStatus.value = { present: false, halfPresent: false, absent: false }
      setTimeout(() => {
        window.location.reload();
      }, 500);
    }
  } catch (err) {
    console.error(err)
    toast.error(err?._data?.message || 'Failed to save attendance')
  } finally {
    isSubmitting.value = false
  }
}

</script>
