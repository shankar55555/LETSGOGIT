<template>
  <v-menu v-model="menu" :close-on-content-click="false" transition="scale-transition" offset-y min-width="290px">
    <template v-slot:activator="{ props }">
      <v-text-field v-model="dateRangeText" label="Select Date Range" prepend-icon="mdi-calendar" readonly
        v-bind="props" />
    </template>

    <v-card>
      <v-date-picker v-model="selectedDate" color="primary" :allowed-dates="allowedDates" :events="rangeEvents"
        event-color="light-blue" @update:model-value="handleManualDateRange" />
    </v-card>
  </v-menu>
</template>

<script setup>
import moment from 'moment';
import { computed, ref, watch } from 'vue';

const emit = defineEmits(['update:dateRange']);

const menu = ref(false);
const today = moment().format('YYYY-MM-DD');

const selectedDate = ref(today);
const rangeStart = ref(today);
const rangeEnd = ref(today);
const dateRange = ref([today, today]);
const dateRangeText = ref(`${moment(today).format('DD-MM-YYYY')} to ${moment(today).format('DD-MM-YYYY')}`);

const allowedDates = () => true;

const rangeEvents = computed(() => {
  if (!rangeStart.value || !rangeEnd.value) return [];
  const start = moment(rangeStart.value);
  const end = moment(rangeEnd.value);
  const dates = [];
  while (start.isSameOrBefore(end)) {
    dates.push(start.format('YYYY-MM-DD'));
    start.add(1, 'day');
  }
  return dates;
});

const handleManualDateRange = date => {
  if (!rangeStart.value || (rangeStart.value && rangeEnd.value)) {
    // Start new range
    rangeStart.value = date;
    rangeEnd.value = null;
    selectedDate.value = date;
    dateRangeText.value = moment(date).format('DD-MM-YYYY');
  } else {
    if (moment(date).isBefore(rangeStart.value)) {
      rangeEnd.value = rangeStart.value;
      rangeStart.value = date;
    } else {
      rangeEnd.value = date;
    }

    dateRange.value = [rangeStart.value, rangeEnd.value];
    dateRangeText.value = `${moment(rangeStart.value).format('DD-MM-YYYY')} to ${moment(rangeEnd.value).format('DD-MM-YYYY')}`;
    emit('update:dateRange', [...dateRange.value]);
    menu.value = false;
  }
};

watch(menu, (isOpen) => {
  if (!isOpen && rangeStart.value && !rangeEnd.value) {
    rangeEnd.value = rangeStart.value;
    dateRange.value = [rangeStart.value, rangeEnd.value];
    dateRangeText.value = `${moment(rangeStart.value).format('DD-MM-YYYY')} to ${moment(rangeEnd.value).format('DD-MM-YYYY')}`;
    emit('update:dateRange', [...dateRange.value]);
  }
});
</script>
