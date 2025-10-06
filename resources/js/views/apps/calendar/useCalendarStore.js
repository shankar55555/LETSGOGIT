// C:\Projects\nobal-solar\resources\js\views\apps\calendar\useCalendarStore.js
import { $api } from '@/utils/api';
import moment from 'moment';
import { defineStore } from 'pinia';
import { toast } from 'vue3-toastify';

const colorMap = {
  error: '#FF5252',
  primary: '#1976D2',
  warning: '#FB8C00',
  success: '#4CAF50',
}

export const useCalendarStore = defineStore('calendar', {
  state: () => ({
    availableCalendars: [
      { color: 'error', label: 'Leads Followup' },
      { color: 'primary', label: 'Leads Site Visit' },
      { color: 'warning', label: 'Client Followup' },
      { color: 'success', label: 'Client Site Visit' },
    ],
    selectedCalendars: ['Leads Followup', 'Leads Site Visit', 'Client Followup', 'Client Site Visit'],
    loading: false,
  }),

  actions: {
    async fetchEvents(date = null) {
      try {
        const selected = this.selectedCalendars

        if (selected.length <= 0) {
          return toast.error('Event Filters in any one select item');
        }


        const selectedDate = moment(date || new Date());
        if (this.loading) {
          // return [];
        }
        this.loading = true;
        this.search_date = selectedDate.startOf('month').format('YYYY-MM-DD');
        const start_date = selectedDate ? selectedDate.startOf('month').format('YYYY-MM-DD') : null;
        const end_date = selectedDate ? selectedDate.endOf('month').format('YYYY-MM-DD') : null;


        // alert(`Start Date: ${start_date}, End Date: ${end_date}`)

        const response = await $api('/calendar-events', {
          method: 'POST',
          body: {
            calendars: selected,
            start_date,
            end_date,
          },
        })

        return (response?.data ?? []).map(event => {
          const calendar = this.availableCalendars.find(cal => cal.label === event.extendedProps.calendar)
          const color = colorMap[calendar?.color] || '#1976D2'
          return { ...event, backgroundColor: color, borderColor: color, display: 'block', }
        })
      } catch (error) {
        console.log('Exception fetching events:', error)
        toast.error(error?._data.message || 'Please select a date first');
        return []
      } finally {
        this.loading = false;

      }
    },

    async addEvent(event) {
      try {
        return await $api('/apps/calendar', { method: 'POST', body: event })
      } catch (e) {
        console.error('Add event error:', e)
      }
    },

    async updateEvent(event) {
      try {
        return await $api(`/apps/calendar/${event.id}`, { method: 'PUT', body: event })
      } catch (e) {
        console.error('Update event error:', e)
        throw e
      }
    },

    async removeEvent(eventId) {
      try {
        return await $api(`/apps/calendar/${eventId}`, { method: 'DELETE' })
      } catch (e) {
        console.error('Remove event error:', e)
        throw e
      }
    },
  },
})
