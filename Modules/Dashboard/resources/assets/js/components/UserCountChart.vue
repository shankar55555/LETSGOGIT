<template>
  <BaseSpinner class="d-flex" v-if="loading" />
  <VCard v-else title="User Information" class="mb-6 pa-3">
    <div>
      <VueApexCharts type="bar" height="350" :options="chartOptions" :series="series" />
    </div>
  </VCard>
</template>

<script setup>
import { useFetchStatusList } from "@/utils/common";
import { onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const { fetchStatusList } = useFetchStatusList();

const series = ref([]);
const loading = ref(false);

const chartOptions = ref({
  chart: { type: 'bar', toolbar: { show: false }, },
  colors: [],
  plotOptions: {
    bar: { horizontal: false, columnWidth: '55%', endingShape: 'rounded', },
  },
  dataLabels: { enabled: false, },
  stroke: { show: true, width: 2, colors: ['transparent'], },
  xaxis: {
    categories: [],
    // categories: [
    //   'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    //   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    // ],
  },
  legend: {
    position: 'bottom',
  },
  fill: {
    opacity: 1,
  },
  tooltip: {
    y: {
      formatter: val => `${val} Users`,
    },
  },
});

const userInformationChartList = async () => {
  loading.value = false;
  try {
    const response = await $api('/user-information-chart-list');
    const data = response.data;

    // Update the entire xaxis object to trigger reactivity
    chartOptions.value = {
      ...chartOptions.value,
      xaxis: {
        ...chartOptions.value.xaxis,
        categories: [...data.months] // replace with new array
      }
    };

    // Build series dynamically
    series.value = [];
    data.statuses.forEach(status => {
      chartOptions.value.colors.push(status.status_color);
      series.value.push({
        name: status.status_text,
        data: status.data
      });
    });
  } catch (err) {
    console.error('Error fetching user information:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  // await fetchStatusList(MODULE_USER);
  await userInformationChartList();
});
</script>
