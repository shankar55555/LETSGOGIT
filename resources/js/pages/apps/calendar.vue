<template>
  <div v-if="$can('calendar', 'view')">
    <VCard>
      <VLayout style="z-index: 0;">
        <!-- 📅 Sidebar with Date Picker -->
        <VNavigationDrawer v-model="isLeftSidebarOpen" width="292" absolute touchless location="start"
          class="calendar-add-event-drawer" :temporary="$vuetify.display.mdAndDown">
          <VDivider />
          <div class="d-flex align-center justify-center pa-2">
            <!-- @update:model-value="setSelectedDate" -->
            <AppDateTimePicker v-model="selectedDate" :config="{ inline: true }" class="calendar-date-picker" />
          </div>
          <VDivider />
          <div class="pa-6">
            <h6 class="text-lg font-weight-medium mb-4">Event Filters</h6>
            <div class="d-flex flex-column calendars-checkbox">
              <VCheckbox v-model="checkAll" label="View all" />
              <VCheckbox v-for="calendar in store.availableCalendars" :key="calendar.label"
                v-model="store.selectedCalendars" :value="calendar.label" :color="calendar.color"
                :label="calendar.label" />
            </div>
          </div>
        </VNavigationDrawer>

        <!-- 📆 Main Calendar -->
        <VMain>
          <VCard flat>
            <FullCalendar ref="refCalendar" :options="calendarOptions" />
          </VCard>
        </VMain>
      </VLayout>
    </VCard>
  </div>
</template>
<!-- C:\Projects\nobal-solar\resources\js\pages\apps\calendar.vue -->
<script setup>
import { blankEvent, useCalendar } from '@/views/apps/calendar/useCalendar'
import { useCalendarStore } from '@/views/apps/calendar/useCalendarStore'
import { useResponsiveLeftSidebar } from '@core/composable/useResponsiveSidebar'
import FullCalendar from '@fullcalendar/vue3'
import { computed, reactive, ref, watch } from 'vue'

const store = useCalendarStore()
const event = ref(structuredClone(blankEvent))
const isEventHandlerSidebarActive = ref(false)
const { isLeftSidebarOpen } = useResponsiveLeftSidebar()
const selectedDate = ref(new Date().toISOString().slice(0, 10))
const refCalendar = ref(null)
const outerDateChange = ref(true)
const previousStartDate = ref(null)

const { calendarOptions: baseCalendarOptions, addEvent, updateEvent, removeEvent, jumpToDate, } = useCalendar(event, isEventHandlerSidebarActive, isLeftSidebarOpen)

// Reset event form when sidebar closes
watch(isEventHandlerSidebarActive, val => {
  if (!val) event.value = structuredClone(blankEvent)
})

// Checkbox: check/uncheck all
const checkAll = computed({
  get: () => store.selectedCalendars.length === store.availableCalendars.length,
  set: val => {
    store.selectedCalendars = val ? store.availableCalendars.map(i => i.label) : []
  },
})

// Watch selected calendars
watch(() => store.selectedCalendars, async () => {
  console.log('watch - selectedCalendars ');
  const events = await store.fetchEvents(selectedDate.value)
  calendarOptions.events = events
}, { deep: true }
)

// Called when date is picked from sidebar
// const setSelectedDate = async date => {
//   console.log('setSelectedDate - jumpToDateFn ');
//   outerDateChange.value = false;
//   await jumpToDateFn(date)
// }

watch(() => selectedDate.value, async (newDate, oldDate) => {
  if (newDate !== oldDate) {
    console.log('setSelectedDate - watch ');
    outerDateChange.value = false;
    await jumpToDateFn(newDate);
  }
});

// Jump to selected date
const jumpToDateFn = async date => {
  if (refCalendar.value?.getApi) {
    const calendarApi = refCalendar.value.getApi()
    calendarApi.gotoDate(date)
  }
  const events = await store.fetchEvents(date)
  calendarOptions.events = events
}

// FullCalendar options
const calendarOptions = reactive({
  ...baseCalendarOptions,
  // headerToolbar: { left: 'prev,next title', right: '' },

  // This hides the time from being shown in events
  displayEventTime: false,

  dateClick(info) {
    console.log('📅 Date clicked:', info.dateStr, ' selected Date :', selectedDate.value)
    baseCalendarOptions.dateClick?.(info)
  },

  eventClick(info) {
    console.log('📌 Event clicked:', info.event)
    baseCalendarOptions.eventClick?.(info)
  },

  eventDrop(info) {
    console.log('🔀 Event dropped to:', info.event.start)
    baseCalendarOptions.eventDrop?.(info)
  },

  eventResize(info) {
    console.log('📏 Event resized to end at:', info.event.end)
    baseCalendarOptions.eventResize?.(info)
  },

  select(info) {
    console.log('✅ Date range selected:', info.startStr, 'to', info.endStr)
    baseCalendarOptions.select?.(info)
  },

  // When calendar view changes (e.g. next month), update sidebar date
  datesSet(info) {
    const viewType = info.view.type;
    const newDate = info.startStr.slice(0, 10);
    let direction = null;

    if (viewType === 'dayGridMonth') {
      const currentStart = new Date(info.startStr);
      if (previousStartDate.value) {
        direction = currentStart > previousStartDate.value ? 'next' : currentStart < previousStartDate.value ? 'previous' : 'same';
      }
    }

    if (outerDateChange.value && direction != 'same') {
      if (viewType === 'dayGridMonth') {
        const date = new Date(selectedDate.value);
        if (direction === 'next') {
          date.setMonth(date.getMonth() + 1);
        } else if (direction === 'previous') {
          date.setMonth(date.getMonth() - 1);
        }
        selectedDate.value = date.toISOString().slice(0, 10);
      } else {
        selectedDate.value = newDate;
      }
      console.log('datesSet - if ');
      baseCalendarOptions.datesSet?.(info);
    } else {
      console.log('datesSet - Else ');
      outerDateChange.value = true;
    }

    console.log('Processed date set : ', { startStr: info.startStr, slicedDate: newDate, selectedDate: selectedDate.value, outerDateChange: outerDateChange.value, direction, });
    // Update previous start for next comparison
    if (viewType === 'dayGridMonth') {
      previousStartDate.value = new Date(info.startStr);
    }
  },

  eventContent(arg) {
    const currentView = refCalendar.value?.getApi?.().view.type
    const event = arg.event
    const title = event.title
    const products = event.extendedProps?.products ?? ''
    const time = event.extendedProps?.time ?? ''
    // Don't customize content in Month view
    if (['dayGridMonth', 'timeGridWeek'].includes(currentView)) {
      return {
        domNodes: [
          Object.assign(document.createElement('div'), {
            innerHTML: `
            <div style="display: flex; flex-direction: column; font-size: 0.85rem; line-height: 1.2;">
             ${title}
            </div>
          `,
          }),
        ],
      }
    } else {
      return {
        domNodes: [
          Object.assign(document.createElement('div'), {
            innerHTML: `
              <div style="display: flex; flex-direction: column; font-size: 0.85rem; line-height: 1.2;">
                <span> Name : ${title}</span>
                ${time ? `<small style="color: #666;">🕒 ${time}</small>` : ''}
                ${products ? `<span style="color: #2196f3;"> Products : ${products}</span>` : ''}
              </div>
            `,
          }),
        ],
      }
    }
  },
})
</script>

<style lang="scss">
@use "@core-scss/template/libs/full-calendar";

.calendars-checkbox {
  .v-label {
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    opacity: var(--v-high-emphasis-opacity);
  }
}

.calendar-add-event-drawer {
  &.v-navigation-drawer:not(.v-navigation-drawer--temporary) {
    border-end-start-radius: 0.375rem;
    border-start-start-radius: 0.375rem;
  }

  &.v-navigation-drawer--temporary:not(.v-navigation-drawer--active) {
    transform: translateX(-110%) !important;
  }
}

.calendar-date-picker {
  display: none;

  +.flatpickr-input {
    +.flatpickr-calendar.inline {
      border: none;
      box-shadow: none;

      .flatpickr-months {
        border-block-end: none;
      }
    }
  }

  &~.flatpickr-calendar .flatpickr-weekdays {
    margin-block: 0 4px;
  }
}

@media screen and (max-width: 1279px) {
  .calendar-add-event-drawer {
    border-width: 0;
  }
}
</style>
<style lang="scss" scoped>
.v-layout {
  overflow: visible !important;

  .v-card {
    overflow: visible;
  }
}
</style>
