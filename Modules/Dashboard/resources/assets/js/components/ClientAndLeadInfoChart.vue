<template>
  <BaseSpinner class="d-flex" v-if="loading" />
  <VCard v-else title="Leads and Client Info" class="mb-6 pa-3">
    <template #append>
      <!-- Year Range Picker -->
      <el-date-picker style="max-inline-size: 200px; min-inline-size: 200px;" class="dateRangePicker"
        v-model="selectedYears" type="yearrange" unlink-panels range-separator="To" start-placeholder="Start Year"
        end-placeholder="End Year" @change="fetchChartData" clearable />

      <!-- Type Selector -->
      <VSelect class="ml-2" v-model="selectedOption" :items="items" label="Select" placeholder="Select Type"
        @update:modelValue="fetchChartData" />
    </template>

    <div>
      <!-- Bar Chart -->
      <VueApexCharts type="bar" height="350" :options="chartOptions" :series="chartSeries" />

      <!-- Back Button -->
      <div v-if="isDrilledDown" class="mt-3">
        <button @click="goBackToMonthly" class="btn btn-primary btn-sm">
          ← Back to Monthly View
        </button>
      </div>
    </div>
  </VCard>
</template>

<script setup>
import { useFetchStatusList } from '@/utils/common';
import moment from 'moment';
import { computed, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const { fetchStatusList } = useFetchStatusList();

const loading = ref(false);
const selectedYears = ref([
  new Date(moment().year() - 1, 0, 1), // Last year
  new Date(moment().year(), 0, 1)      // Current year
]);
const selectedOption = ref('Leads');
const items = ['Leads', 'Clients'];

const isDrilledDown = ref(false);
const currentMonth = ref(null);
const chartData = ref({ months: [], statuses: [] });
const dailyDataMap = ref({});
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Computed chart series (based on API data)
const chartSeries = computed(() =>
  chartData.value.statuses.map(status => ({
    name: status.status_text,
    color: status.status_color,
    data: isDrilledDown.value
      ? dailyDataMap.value[selectedOption.value]?.[currentMonth.value]?.[status.status_text] || []
      : status.data,
  }))
);

// Chart options (dynamic based on drill state)
const chartOptions = computed(() => {
  const baseOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      events: {
        click: (event, chartContext, config) => {
          if (!isDrilledDown.value && config.dataPointIndex !== -1) {
            handleChartClick(config.dataPointIndex);
          }
        },
      },
    },
    colors: chartData.value.statuses.map(s => s.status_color || '#7367f0'),
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded',
      },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    legend: { position: 'bottom' },
    fill: { opacity: 1 },
    tooltip: {
      custom: ({ series, seriesIndex, dataPointIndex }) => {
        const seriesData = chartSeries.value.map((s, index) => ({
          name: s.name,
          color: s.color,
          value: series[index]?.[dataPointIndex] || 0,
        }));

        const totalValue = seriesData.reduce((sum, s) => sum + s.value, 0);

        const title = !isDrilledDown.value
          ? monthNames[dataPointIndex] // = Monthly view
          : `Day ${dataPointIndex + 1} - ${currentMonth.value}`; // = Daily view

        return tooltipTemplate(title, seriesData, totalValue);
      },
    },
  };

  if (isDrilledDown.value) {
    const daysInMonth =
      dailyDataMap.value[selectedOption.value]?.[currentMonth.value]?.[chartData.value.statuses[0]?.status_text]?.length ||
      31;

    return {
      ...baseOptions,
      xaxis: { categories: Array.from({ length: daysInMonth }, (_, i) => i + 1) },
      title: { text: `${selectedOption.value} - Daily Report (${currentMonth.value})`, align: 'center', style: { fontSize: '16px' } },
    };
  }

  return {
    ...baseOptions,
    xaxis: { categories: monthNames },
    title: { text: `${selectedOption.value} - Monthly Report`, align: 'center', style: { fontSize: '16px' } },
  };
});

const tooltipTemplate = (title, seriesData, total) => {
  const seriesHtml = seriesData.map(s => `
        <div style="margin-bottom: 2px; color:${s.color}">
          ${s.name}: ${s.value}
        </div>`
  ).join('');

  return `
    <div style="padding: 8px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
      <div style="font-weight: bold; margin-bottom: 4px;">${title}</div>
      ${seriesHtml}
      <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 4px; margin-top: 4px;">
        Total ${selectedOption.value}: ${total}
      </div>
    </div>
  `;
};

// Handle chart click (for drill-down)
const handleChartClick = index => {
  currentMonth.value = monthNames[index];
  isDrilledDown.value = true;
};

// Go back to monthly view
const goBackToMonthly = () => {
  isDrilledDown.value = false;
  currentMonth.value = null;
};

// Fetch chart data from API
const fetchChartData = async () => {
  loading.value = true;
  try {
    let start_year, end_year;

    if (selectedYears.value && Array.isArray(selectedYears.value) && selectedYears.value.length > 0) {
      start_year = moment(selectedYears.value[0]).year();
      end_year = selectedYears.value.length === 2 ? moment(selectedYears.value[1]).year() : start_year;
    } else {
    }

    const params = {
      start_year, end_year
    };

    const api_url = selectedOption.value === 'Leads' ? '/lead-info-chart-list' : '/client-info-chart-list';
    const response = await $api(api_url, { params });
    chartData.value = response.data || { months: [], statuses: [] };
    dailyDataMap.value[selectedOption.value] = response.data.dailyData || {};
  } catch (err) {
    console.error('Error fetching chart data:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchChartData);
</script>
