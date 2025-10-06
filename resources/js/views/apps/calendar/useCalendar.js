// C:\Projects\nobal-solar\resources\js\views\apps\calendar\useCalendar.js

import { useCalendarStore } from '@/views/apps/calendar/useCalendarStore'
import { useConfigStore } from '@core/stores/config'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import { onMounted } from 'vue'

export const blankEvent = {
  title: '',
  start: '',
  end: '',
  allDay: false,
  url: '',
  extendedProps: {
    calendar: undefined,
    guests: [],
    location: '',
    description: '',
  },
}

export const useCalendar = (event, isEventHandlerSidebarActive, isLeftSidebarOpen) => {
  const configStore = useConfigStore()
  const store = useCalendarStore()
  const refCalendar = ref()
  const calendarApi = ref(null)
  const isCalendarInitialized = ref(false)

  // Calendar colors
  const calendarsColor = {
    'Leads Followup': 'error',
    'Leads Site Visit': 'primary',
    'Client Followup': 'warning',
    'Client Site Visit': 'success',
  }

  // Extract event data from event API
  const extractEventDataFromEventApi = eventApi => {
    const { id, title, start, end, url, extendedProps: { calendar, guests, location, description }, allDay } = eventApi
    return {
      id,
      title,
      start,
      end,
      url,
      extendedProps: {
        calendar,
        guests: guests || [],
        location: location || '',
        description: description || '',
      },
      allDay,
    }
  }

  // Debounce function to limit API calls
  const debounce = (func, wait) => {
    let timeout
    return (...args) => {
      clearTimeout(timeout)
      timeout = setTimeout(() => func.apply(this, args), wait)
    }
  }

  // Fetch events
  const fetchEvents = (info, successCallback) => {
    if (!info) return
    console.log('Fetching events with calendars:', store.selectedCalendars)
    store.fetchEvents()
      .then(r => {
        const mappedEvents = r.map(e => ({
          ...e,
          backgroundColor: calendarsColor[e.extendedProps.calendar] || 'primary',
          borderColor: calendarsColor[e.extendedProps.calendar] || 'primary',
          display: 'block',
          start: new Date(e.start),
          end: e.end ? new Date(e.end) : new Date(e.start),
        }))
        console.log('Events fetched:', mappedEvents)
        successCallback(mappedEvents)
        // Reinitialize calendar events
        if (calendarApi.value) {
          console.log('Reinitializing calendar with new events')
          calendarApi.value.getEvents().forEach(event => event.remove())
          mappedEvents.forEach(event => calendarApi.value.addEvent(event))
        }
      })
      .catch(e => {
        console.error('Error occurred while fetching calendar events:', e)
      })
  }

  // Debounced fetch events
  const debouncedFetchEvents = debounce(fetchEvents, 150)

  // Update event in calendar
  const updateEventInCalendar = (updatedEventData, propsToUpdate, extendedPropsToUpdate) => {
    if (!calendarApi.value) return
    const existingEvent = calendarApi.value.getEventById(String(updatedEventData.id))
    if (!existingEvent) {
      console.warn('Cannot find event in calendar to update')
      return
    }
    for (const propName of propsToUpdate) {
      existingEvent.setProp(propName, updatedEventData[propName])
    }
    existingEvent.setDates(updatedEventData.start, updatedEventData.end, { allDay: updatedEventData.allDay })
    for (const propName of extendedPropsToUpdate) {
      existingEvent.setExtendedProp(propName, updatedEventData.extendedProps[propName])
    }
  }

  // Remove event in calendar
  const removeEventInCalendar = eventId => {
    if (!calendarApi.value) return
    const _event = calendarApi.value.getEventById(eventId)
    if (_event) _event.remove()
  }

  // Refetch events
  const refetchEvents = () => {
    if (!calendarApi.value || !isCalendarInitialized.value) {
      console.log('Calendar API not initialized yet, waiting for initialization')
      return
    }
    console.log('Refetching events...')
    calendarApi.value.refetchEvents()
  }

  // Watch selectedCalendars and refetch events
  watch(() => store.selectedCalendars, (newVal, oldVal) => {
    console.log('Selected calendars changed:', newVal, 'from:', oldVal)
    if (isCalendarInitialized.value) {
      refetchEvents()
    } else {
      console.log('Calendar not initialized, skipping refetch until mounted')
    }
  }, { deep: true })

  // Add event
  const addEvent = _event => {
    store.addEvent(_event).then(() => {
      refetchEvents()
    })
  }

  // Update event
  const updateEvent = _event => {
    store.updateEvent(_event).then(r => {
      const propsToUpdate = ['id', 'title', 'url']
      const extendedPropsToUpdate = ['calendar', 'guests', 'location', 'description']
      updateEventInCalendar(r, propsToUpdate, extendedPropsToUpdate)
      refetchEvents()
    })
  }

  // Remove event
  const removeEvent = eventId => {
    store.removeEvent(eventId).then(() => {
      removeEventInCalendar(eventId)
    })
  }

  // Calendar options
  const calendarOptions = {
    plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin, listPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
      start: 'drawerToggler,prev,next title',
      end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
    },
    events: debouncedFetchEvents,
    forceEventDuration: true,
    editable: true,
    eventResizableFromStart: true,
    dragScroll: true,
    dayMaxEvents: 2,
    navLinks: true,
    eventClassNames({ event: calendarEvent }) {
      const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar] || 'primary'
      return [`bg-light-${colorName} text-${colorName}`]
    },
    eventClick({ event: clickedEvent, jsEvent }) {
      jsEvent.preventDefault()
      if (clickedEvent.url) {
        window.open(clickedEvent.url, '_blank')
      }
      event.value = extractEventDataFromEventApi(clickedEvent)
      isEventHandlerSidebarActive.value = true
    },
    dateClick(info) {
      event.value = { ...event.value, start: info.date }
      isEventHandlerSidebarActive.value = true
    },
    eventDrop({ event: droppedEvent }) {
      updateEvent(extractEventDataFromEventApi(droppedEvent))
    },
    eventResize({ event: resizedEvent }) {
      if (resizedEvent.start && resizedEvent.end) {
        updateEvent(extractEventDataFromEventApi(resizedEvent))
      }
    },
    customButtons: {
      drawerToggler: {
        text: 'calendarDrawerToggler',
        click() {
          isLeftSidebarOpen.value = true
        },
      },
    },
  }

  // Initialize calendar API on mount
  onMounted(() => {
    console.log('Mounting calendar, initializing API...')
    if (!refCalendar.value) {
      console.error('refCalendar is null, FullCalendar component may not be rendered correctly')
      return
    }
    try {
      calendarApi.value = refCalendar.value.getApi()
      isCalendarInitialized.value = true
      console.log('Calendar API initialized successfully')
      refetchEvents() // Trigger initial fetch
    } catch (e) {
      console.error('Error initializing calendar API:', e)
    }
  })

  // Watch refCalendar to handle dynamic mounting
  watch(refCalendar, (newVal) => {
    if (newVal && !isCalendarInitialized.value) {
      console.log('refCalendar set, initializing API...')
      try {
        calendarApi.value = newVal.getApi()
        isCalendarInitialized.value = true
        console.log('Calendar API initialized successfully (via watcher)')
        refetchEvents() // Trigger fetch after late initialization
      } catch (e) {
        console.error('Error initializing calendar API via watcher:', e)
      }
    }
  })

  // Jump to date
  const jumpToDate = currentDate => {
    if (calendarApi.value) {
      calendarApi.value.gotoDate(new Date(currentDate))
    }
  }

  // Watch RTL setting
  watch(() => configStore.isAppRTL, val => {
    if (calendarApi.value) {
      calendarApi.value.setOption('direction', val ? 'rtl' : 'ltr')
    }
  }, { immediate: true })

  return {
    refCalendar,
    calendarOptions,
    refetchEvents,
    fetchEvents: debouncedFetchEvents,
    addEvent,
    updateEvent,
    removeEvent,
    jumpToDate,
  }
}
