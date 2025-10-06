<template>
  <VCard title="Leads and Client Info" class="mb-6 pa-3">
    <template #append>
      <VSelect v-model="selectedOption" :items="items" label="Select" placeholder="Select Type" />
    </template>
    <div>
      <!-- Bar Chart -->
      <VueApexCharts type="bar" height="350" :options="chartOptions" :series="currentSeries"
        @click="handleChartClick" />

      <!-- Back button for drilled down view -->
      <div v-if="isDrilledDown" class="mt-3">
        <button @click="goBackToMonthly" class="btn btn-primary btn-sm">
          ← Back to Monthly View
        </button>
      </div>
    </div>
  </VCard>
</template>

<script setup>
import moment from 'moment'
import { computed, defineProps, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const selectedYear = ref([moment().year()])
const selectedOption = ref('Leads')
const items = ['Leads', 'Clients']

const props = defineProps({
  type: {
    type: String,
    default: 'Leads',
  },
})

// State for drilling functionality
const isDrilledDown = ref(false)
const currentMonth = ref(null)

// Dummy monthly data
const dataMap = {
  Leads: {
    active: [20, 25, 22, 28, 35, 40, 45, 50, 48, 47, 45, 42],
    inactive: [5, 8, 6, 10, 12, 9, 7, 6, 10, 12, 13, 15],
  },
  Clients: {
    active: [10, 15, 14, 18, 20, 25, 28, 30, 29, 27, 26, 25],
    inactive: [2, 3, 4, 5, 6, 5, 4, 3, 5, 6, 7, 8],
  },
}

// Dummy daily data for drilling - distributed based on monthly totals
const dailyDataMap = {
  Leads: {
    Jan: {
      active: [2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2],
      inactive: [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1],
    },
    Feb: {
      active: [3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1],
      inactive: [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0],
    },
    Mar: {
      active: [4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5],
      inactive: [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2],
    },
    Apr: {
      active: [5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4],
      inactive: [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1],
    },
    May: {
      active: [6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7],
      inactive: [3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3],
    },
    Jun: {
      active: [7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6],
      inactive: [3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2],
    },
    Jul: {
      active: [8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9],
      inactive: [4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4],
    },
    Aug: {
      active: [9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10],
      inactive: [4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4],
    },
    Sep: {
      active: [10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9],
      inactive: [5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4],
    },
    Oct: {
      active: [11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12],
      inactive: [5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5],
    },
    Nov: {
      active: [12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11],
      inactive: [6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5],
    },
    Dec: {
      active: [13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14, 11, 13, 12, 14],
      inactive: [6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6, 5, 6],
    },
  },
  Clients: {
    Jan: {
      active: [1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1],
      inactive: [0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0]
    },
    Feb: {
      active: [2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 3, 2, 1, 2, 1, 2],
      inactive: [0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1]
    },
    Mar: {
      active: [3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4, 1, 3, 2, 4],
      inactive: [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1]
    },
    Apr: {
      active: [4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3, 5, 2, 4, 3],
      inactive: [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0]
    },
    May: {
      active: [5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6, 3, 5, 4, 6],
      inactive: [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2]
    },
    Jun: {
      active: [6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5, 7, 4, 6, 5],
      inactive: [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1]
    },
    Jul: {
      active: [7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8, 5, 7, 6, 8],
      inactive: [3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3]
    },
    Aug: {
      active: [8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9, 6, 8, 7, 9],
      inactive: [3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3, 2, 3]
    },
    Sep: {
      active: [9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8, 10, 7, 9, 8],
      inactive: [4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3]
    },
    Oct: {
      active: [10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11, 8, 10, 9, 11],
      inactive: [4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4, 3, 4],
    },
    Nov: {
      active: [11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10, 12, 9, 11, 10],
      inactive: [5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4],
    },
    Dec: {
      active: [12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13, 10, 12, 11, 13],
      inactive: [5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5, 4, 5],
    },
  },
}

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const currentSeries = computed(() => {
  const key = props.type

  if (isDrilledDown.value && currentMonth.value !== null) {
    const monthData = dailyDataMap[key][currentMonth.value]
    if (monthData) {
      // Generate daily categories (1-31)
      const daysInMonth = monthData.active.length
      const dailyCategories = Array.from({ length: daysInMonth }, (_, i) => i + 1)

      return [
        { name: 'Active', data: monthData.active },
        { name: 'Inactive', data: monthData.inactive },
      ]
    }
  }

  return [
    { name: 'Active', data: dataMap[key].active },
    { name: 'Inactive', data: dataMap[key].inactive },
  ]
})

// Chart options for ApexCharts
const chartOptions = computed(() => {
  const baseOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      events: {
        click: (event, chartContext, config) => {
          if (!isDrilledDown.value) {
            handleChartClick(event, chartContext, config)
          }
        }
      }
    },
    colors: ['#7367f0', '#ff4c51d9'],
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded',
      },
    },
    dataLabels: { enabled: false },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent'],
    },
    legend: { position: 'bottom' },
    fill: { opacity: 1 },
    tooltip: {
      y: { formatter: val => `${val} Users` },
      custom: function ({ series, seriesIndex, dataPointIndex, w }) {
        if (!isDrilledDown.value) {
          // Monthly view tooltip
          const monthName = monthNames[dataPointIndex]
          const activeValue = series[0][dataPointIndex]
          const inactiveValue = series[1][dataPointIndex]
          const totalValue = activeValue + inactiveValue

          return `
                        <div style="padding: 8px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="font-weight: bold; margin-bottom: 4px;">${monthName}</div>
                            <div style="margin-bottom: 2px;">Active: ${activeValue}</div>
                            <div style="margin-bottom: 2px;">Inactive: ${inactiveValue}</div>
                            <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 4px; margin-top: 4px;">
                                Total ${props.type}: ${totalValue}
                            </div>
                        </div>
                    `
        } else {
          // Daily view tooltip
          const dayNumber = dataPointIndex + 1
          const activeValue = series[0][dataPointIndex]
          const inactiveValue = series[1][dataPointIndex]
          const totalValue = activeValue + inactiveValue

          return `
                        <div style="padding: 8px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="font-weight: bold; margin-bottom: 4px;">Day ${dayNumber} - ${currentMonth.value}</div>
                            <div style="margin-bottom: 2px;">Active: ${activeValue}</div>
                            <div style="margin-bottom: 2px;">Inactive: ${inactiveValue}</div>
                            <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 4px; margin-top: 4px;">
                                Total ${props.type}: ${totalValue}
                            </div>
                        </div>
                    `
        }
      }
    },
  }

  if (isDrilledDown.value) {
    // Daily view options
    const daysInMonth = dailyDataMap[props.type][currentMonth.value]?.active.length || 31
    const dailyCategories = Array.from({ length: daysInMonth }, (_, i) => i + 1)

    return {
      ...baseOptions,
      xaxis: {
        categories: dailyCategories,
        title: { text: `Daily Data for ${currentMonth.value}` }
      },
      title: {
        text: `${props.type} - Daily Report (${currentMonth.value})`,
        align: 'center',
        style: { fontSize: '16px' }
      }
    }
  } else {
    // Monthly view options
    return {
      ...baseOptions,
      xaxis: {
        categories: monthNames,
        title: { text: 'Monthly Data' }
      },
      title: {
        text: `${props.type} - Monthly Report`,
        align: 'center',
        style: { fontSize: '16px' }
      }
    }
  }
})

// Handle chart click for drilling
const handleChartClick = (event, chartContext, config) => {
  if (config.dataPointIndex !== undefined && !isDrilledDown.value) {
    const clickedMonth = monthNames[config.dataPointIndex]
    currentMonth.value = clickedMonth
    isDrilledDown.value = true
  }
}

// Go back to monthly view
const goBackToMonthly = () => {
  isDrilledDown.value = false
  currentMonth.value = null
}
</script>
