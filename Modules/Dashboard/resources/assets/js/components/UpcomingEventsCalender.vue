<template>
  <div>
    <VRow>
      <VCol cols="12" class="pb-6">
        <!-- Upcoming events -->
        <div class="d-flex justify-space-between align-center">
          <div class="text-subtitle-1 font-weight-medium">Upcoming Events : </div>
          <div class="ml-2 mr-2"
            style="flex-grow: 1; background-color: rgba(var(--v-theme-warning), 0.38); block-size: 1px;">
          </div>
        </div>
      </VCol>
    </VRow>
    <VCard>
      <VLayout style="z-index: 0;">
        <VMain>
          <div class="d-flex justify-space-between align-center pa-4">
            <div class="calendar-title">
              <div class="d-flex align-center">
                <VBtn icon @click="changeMonth('prev')" class="calendar-nav-btn">
                  <VIcon icon="tabler-chevron-left" />
                </VBtn>
                <h2 class="text-h5 font-weight-bold mb-0 mx-4">{{ currentMonthYear }} </h2>
                <VBtn icon @click="changeMonth('next')" class="calendar-nav-btn">
                  <VIcon icon="tabler-chevron-right" />
                </VBtn>
              </div>
            </div>

            <!-- Filter -->
            <VSelect v-model="filterOption" :items="filteredOptions" label="Filter by Type" clearable multiple
              class="filter-select" @update:modelValue="fetchAllEvents">
              <template #selection="{ index }">
                <!-- Only render once for the first chip -->
                <span v-if="index === 0">
                  <VChip size="small">
                    <template v-if="filterOption.length === filteredOptions.length">
                      All Selected
                    </template>
                    <template v-else>
                      {{ filterOption[0] }}
                      <span v-if="filterOption.length > 1" class="ml-1">
                        (+{{ filterOption.length - 1 }} more)
                      </span>
                    </template>
                  </VChip>
                </span>
              </template>
            </VSelect>
          </div>
          <VCard flat>
            <FullCalendar class="calender_custom_height" ref="refCalendar" :options="calendarOptions" />
          </VCard>
        </VMain>
      </VLayout>
    </VCard>

    <CalendarEventHandler v-model:isDrawerOpen="isEventHandlerSidebarActive" :event="event" @add-event="addEvent"
      @update-event="updateEvent" @remove-event="removeEvent" />
  </div>
</template>

<script setup>
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import FullCalendar from '@fullcalendar/vue3'
import moment from 'moment'
import tippy from 'tippy.js'
import 'tippy.js/dist/tippy.css'
import { computed, ref, watch } from 'vue'

const filterOptions = [
  { title: 'Team Birthday', value: 'Team Birthday', action: 'user-upcoming-dates', subject: 'view' },
  { title: 'Team Anniversary', value: 'Team Anniversary', action: 'user-upcoming-dates', subject: 'view' },
  { title: 'Lead Birthday', value: 'Lead Birthday', action: 'lead-upcoming-dates', subject: 'view' },
  { title: 'Lead Anniversary', value: 'Lead Anniversary', action: 'lead-upcoming-dates', subject: 'view' },
  { title: 'Client Birthday', value: 'Client Birthday', action: 'client-upcoming-dates', subject: 'view' },
  { title: 'Client Anniversary', value: 'Client Anniversary', action: 'client-upcoming-dates', subject: 'view' },
]
const filteredOptions = computed(() => getFilteredTabs(filterOptions))
const filterOption = ref([])
watch(filteredOptions, (newVal) => { filterOption.value = newVal.map(item => item.value) }, { immediate: true })

// State
const refCalendar = ref(null)
const currentCalendarDate = ref(new Date())
const calendarDate = ref(new Date())
const blankEvent = { id: '', title: '', start: '', end: '', allDay: true, extendedProps: {} }
const event = ref(structuredClone(blankEvent))
const isEventHandlerSidebarActive = ref(false)

// Reset event drawer
watch(isEventHandlerSidebarActive, val => {
  if (!val) event.value = structuredClone(blankEvent)
})

// Check today
const isToday = dateStr => moment().isSame(moment(dateStr), 'day')

const startDate = computed(() => moment(calendarDate.value).startOf('month').format('YYYY-MM-DD'))
const endDate = computed(() => moment(calendarDate.value).endOf('month').format('YYYY-MM-DD'))

const teamBirthdays = ref([])
const teamAnniversaries = ref([])
const leadBirthdays = ref([])
const leadAnniversaries = ref([])
const ClientBirthdays = ref([])
const ClientAnniversaries = ref([])
const getTeamEvents = async () => {
  try {
    const mapFilter = ['Team Birthday', 'Team Anniversary'];
    const selectedFilters = mapFilter.filter(f => filterOption.value.includes(f))
    if (!selectedFilters.includes(mapFilter[0]) && !selectedFilters.includes(mapFilter[1])) {
      teamBirthdays.value = [];
      teamAnniversaries.value = [];
      refCalendar.value?.getApi()?.refetchEvents();
      return;
    }

    let filters = {}
    if (selectedFilters.includes(mapFilter[0])) filters.birthdays = 'date_of_birth'
    if (selectedFilters.includes(mapFilter[1])) filters.anniversaries = 'anniversary_date'

    const response = await $api('/upcoming-team-events', { method: 'GET', params: { start_date: startDate.value, end_date: endDate.value, filters } })
    teamBirthdays.value = response.data?.birthdays || []
    teamAnniversaries.value = response.data?.anniversaries || []
    refCalendar.value?.getApi()?.refetchEvents()
  } catch (err) {
    teamBirthdays.value = []
    teamAnniversaries.value = []
  }
}

const getLeadEvents = async () => {
  try {
    const mapFilter = ['Lead Birthday', 'Lead Anniversary'];
    const selectedFilters = mapFilter.filter(f => filterOption.value.includes(f))
    if (!selectedFilters.includes(mapFilter[0]) && !selectedFilters.includes(mapFilter[1])) {
      leadBirthdays.value = [];
      leadAnniversaries.value = [];
      refCalendar.value?.getApi()?.refetchEvents();
      return;
    }

    let filters = {}
    if (selectedFilters.includes(mapFilter[0])) filters.birthdays = 'date_of_birth'
    if (selectedFilters.includes(mapFilter[1])) filters.anniversaries = 'anniversary_date'

    const response = await $api('/upcoming-lead-events', { method: 'GET', params: { start_date: startDate.value, end_date: endDate.value, filters } })
    leadBirthdays.value = response.data?.birthdays || []
    leadAnniversaries.value = response.data?.anniversaries || []
    refCalendar.value?.getApi()?.refetchEvents()
  } catch (err) {
    leadBirthdays.value = []
    leadAnniversaries.value = []
  }
}

const getClientEvents = async () => {
  try {
    const mapFilter = ['Client Birthday', 'Client Anniversary'];
    const selectedFilters = mapFilter.filter(f => filterOption.value.includes(f))
    if (!selectedFilters.includes(mapFilter[0]) && !selectedFilters.includes(mapFilter[1])) {
      ClientBirthdays.value = [];
      ClientAnniversaries.value = [];
      refCalendar.value?.getApi()?.refetchEvents();
      return;
    }

    let filters = {}
    if (selectedFilters.includes(mapFilter[0])) filters.birthdays = 'date_of_birth';
    if (selectedFilters.includes(mapFilter[1])) filters.anniversaries = 'anniversary_date';

    const response = await $api('/upcoming-client-events', { method: 'GET', params: { start_date: startDate.value, end_date: endDate.value, filters } })
    ClientBirthdays.value = response.data?.birthdays || []
    ClientAnniversaries.value = response.data?.anniversaries || []
    refCalendar.value?.getApi()?.refetchEvents()
  } catch (err) {
    ClientBirthdays.value = []
    ClientAnniversaries.value = []
  }
}
// Fetch all APIs
const fetchAllEvents = async () => {
  await Promise.all([
    getTeamEvents(),
    getLeadEvents(),
    getClientEvents(),
  ])
  refCalendar.value?.getApi()?.refetchEvents()
}

const calendarEvents = computed(() => {
  const events = []
  const startYear = moment(startDate.value).year()

  const addEvent = (list, type, color, borderColor, dateField, label) => {
    list.value.forEach(item => {
      if (item[dateField]) {
        const originalDate = moment(item[dateField]).format('DD-MM-YYYY');
        const eventDate = moment(item[dateField]).year(startYear) // adjust year to selected year
        if (eventDate.isValid()) {
          events.push({
            id: `${type.toLowerCase().replace(/\s/g, '-')}-${item.id || Math.random().toString()}`,
            title: `${item.name || 'Unknown'}'s ${label}`,
            start: eventDate.format('YYYY-MM-DD'),
            allDay: true,
            extendedProps: {
              type,
              name: item.name || 'Unknown',
              originalDate
            },
            backgroundColor: color,
            borderColor: borderColor,
            classNames: [isToday(eventDate.format('YYYY-MM-DD')) ? 'today-event' : ''],
          })
        }
      }
    })
  }

  addEvent(teamBirthdays, 'Team Birthday', 'rgba(var(--v-theme-primary), 0.7)', 'rgba(var(--v-theme-primary), 1)', 'date_of_birth', 'Birthday')
  addEvent(teamAnniversaries, 'Team Anniversary', 'rgba(var(--v-theme-success), 0.7)', 'rgba(var(--v-theme-success), 1)', 'anniversary_date', 'Anniversary')
  addEvent(leadBirthdays, 'Lead Birthday', 'rgba(var(--v-theme-warning), 0.7)', 'rgba(var(--v-theme-warning), 1)', 'date_of_birth', 'Birthday (Lead)')
  addEvent(leadAnniversaries, 'Lead Anniversary', 'rgba(var(--v-theme-info), 0.7)', 'rgba(var(--v-theme-info), 1)', 'anniversary_date', 'Anniversary (Lead)')
  addEvent(ClientBirthdays, 'Client Birthday', 'rgba(110, 80, 153, 0.9)', 'rgba(103, 58, 183, 1)', 'date_of_birth', 'Birthday (Client)')
  addEvent(ClientAnniversaries, 'Client Anniversary', 'rgba(233, 30, 99, 0.7)', 'rgba(233, 30, 99, 1)', 'anniversary_date', 'Anniversary (Client)')

  return events
})

// Filtered events
const filteredEvents = computed(() =>
  !filterOption.value.length
    ? calendarEvents.value
    : calendarEvents.value.filter(e => filterOption.value.includes(e.extendedProps.type))
)

// Calendar options
const calendarOptions = {

  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: false,
  selectable: true,
  editable: true,
  height: '350',
  fixedWeekCount: false,
  showNonCurrentDates: false,
  events: (fetchInfo, successCallback) => successCallback(filteredEvents.value),
  eventClick: ({ event: clickedEvent }) => {
    event.value = { ...clickedEvent.extendedProps, id: clickedEvent.id }
    isEventHandlerSidebarActive.value = true
  },
  eventDidMount: ({ el, event }) => {
    const { type, originalDate } = event.extendedProps
    tippy(el, {
      content: `<strong>${type}</strong><br>Original Date: ${originalDate || 'N/A'}`,
      allowHTML: true,
      placement: 'top',
      theme: 'light-border',
    })
  },
  datesSet: dateInfo => {
    currentCalendarDate.value = moment(dateInfo.start).add(10, 'days').toDate()
    calendarDate.value = moment(dateInfo.start).add(10, 'days').toDate()
    fetchAllEvents()
  }
}

// Month navigation
const changeMonth = direction => {
  const api = refCalendar.value?.getApi()
  if (api) {
    direction === 'prev' ? api.prev() : api.next()
    currentCalendarDate.value = api.getDate()
  }
}

// Event actions
const addEvent = e => refCalendar.value?.getApi()?.addEvent(e)
const updateEvent = e => {
  const api = refCalendar.value?.getApi()
  const existing = api?.getEventById(e.id)
  if (existing) {
    existing.setProp('title', e.title)
    existing.setStart(e.start)
    existing.setEnd(e.end)
  }
}
const removeEvent = id => refCalendar.value?.getApi()?.getEventById(id)?.remove()

const currentMonthYear = computed(() => moment(currentCalendarDate.value).format('MMMM YYYY'))

// Initial load
// onMounted(() => { fetchAllEvents(); })
</script>

<style lang="scss">
@use "@core-scss/template/libs/full-calendar";

.v-layout {
  overflow: visible !important;

  .v-card {
    overflow: visible;
  }
}

.today-event {
  position: relative;
  animation: glow 1.5s ease-in-out infinite alternate;
}

.today-event::after {
  position: absolute;
  z-index: 10;
  border-radius: 3px;
  background-color: rgba(var(--v-theme-error), 0.9);
  color: white;
  content: "Today!";
  font-size: 10px;
  inset-block-start: -10px;
  inset-inline-end: -10px;
  padding-block: 2px;
  padding-inline: 6px;
}
</style>

<style scoped>
.filter-select {
  max-inline-size: 300px;
}

.calendar-nav-btn {
  border-radius: 50%;
  transition: all 0.2s ease;
}

.calendar-nav-btn:hover {
  background-color: rgba(var(--v-theme-primary), 0.1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 10%);
}
</style>
