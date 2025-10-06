<script setup>
import { IconCalendar, IconCheck, IconChevronRight, IconDownload, IconPrinter, IconSettings, IconFolder, IconFileText, IconArrowUp, IconStar } from '@tabler/icons-vue'
import { computed, onMounted, ref, nextTick, watch } from 'vue'

const loading = ref(false)
const error = ref(null)
const isFullWidthView = ref(false)
const showCompareMode = ref(false)
const showPercent = ref(false)
const tableKey = ref(0)

const incomeData = ref([]);

const expensesData = ref([]);

const incomeHeaders = computed(() => {
  return [
    { title: 'Income Type', value: 'name', width: '550px', align: 'start', visible: true },
    { title: showCompareMode.value ? 'Current' : '', value: 'current', width: '', align: 'end', visible: true },
    { title: 'Previous', value: 'previous', width: '', align: 'end', visible: showCompareMode.value },
    { title: 'Change', value: 'change', width: '', align: 'end', visible: showCompareMode.value },
  ].filter(h => h.visible);
});

const expensesHeaders = computed(() => {
  return [
    { title: 'Expense Type', value: 'name', width: '550px', align: 'start', visible: true },
    { title: showCompareMode.value ? 'Current' : '', value: 'current', width: '', align: 'end', visible: true },
    { title: 'Previous', value: 'previous', width: '', align: 'end', visible: showCompareMode.value },
    { title: 'Change', value: 'change', width: '', align: 'end', visible: showCompareMode.value },
  ].filter(h => h.visible);
});


// Flatten hierarchical data for VDataTable
function flattenData(data) {
  const flatList = [];
  
  function addItems(items) {
    for (const item of items) {
      // Extract numeric values for calculations
      const currentValue = parseFloat(item.current?.toString().replace(/[^0-9.-]+/g, '') || '0');
      const previousValue = parseFloat(item.previous?.toString().replace(/[^0-9.-]+/g, '') || '0');
      
      // Calculate percentage change
      let changePercent = 0;
      if (previousValue !== 0) {
        changePercent = ((currentValue - previousValue) / previousValue) * 100;
      }
      
      // Map the API response fields to what the template expects
      const mappedItem = {
        ...item,
        current: item.currentFormatted || item.current || '₹0.00',
        previous: item.previousFormatted || item.previous || '₹0.00',
        change: item.changeFormatted || item.change || `${changePercent.toFixed(1)}%`
      };
      flatList.push(mappedItem);
      if (item.children && item.children.length > 0) {
        addItems(item.children);
      }
    }
  }
  
  addItems(data);
  return flatList;
}

const flatIncomeData = computed(() => flattenData(incomeData.value));
const flatExpensesData = computed(() => flattenData(expensesData.value));



const getTotal = (data) => {
  let total = {
    current: 0,
    previous: 0,
    change: 0,
  };

  function accumulate(items) {
    for (const item of items) {
      if (item.type === 'ledger') {
        const curr = parseFloat(item.current?.replace(/[^0-9.-]+/g, '') || 0);
        const prev = parseFloat(item.previous?.replace(/[^0-9.-]+/g, '') || 0);
        total.current += curr;
        total.previous += prev;
      }

      if (item.children?.length) {
        accumulate(item.children);
      }
    }
  }

  accumulate(data);

  const percentChange = total.previous === 0 ? 0 : ((total.current - total.previous) / total.previous) * 100;

  return {
    currentFormatted: `₹${total.current.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    previousFormatted: `₹${total.previous.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    changeFormatted: `${percentChange.toFixed(1)}%`,
    isIncrease: percentChange >= 0,
  };
};

const totalIncome = computed(() => getTotal(incomeData.value));
const totalExpenses = computed(() => getTotal(expensesData.value));

const netProfit = computed(() => {
  const income = getTotal(incomeData.value);
  const expenses = getTotal(expensesData.value);

  const netCurrent = parseFloat(income.currentFormatted.replace(/[^0-9.-]+/g, '')) -
    parseFloat(expenses.currentFormatted.replace(/[^0-9.-]+/g, ''));

  const netPrevious = parseFloat(income.previousFormatted.replace(/[^0-9.-]+/g, '')) -
    parseFloat(expenses.previousFormatted.replace(/[^0-9.-]+/g, ''));

  const percentChange = netPrevious === 0 ? 0 : ((netCurrent - netPrevious) / netPrevious) * 100;

  return {
    percent: `${percentChange.toFixed(1)}%`,
    currentFormatted: `₹${netCurrent.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    previousFormatted: `₹${netPrevious.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    isIncrease: percentChange >= 0,
  };
});

// Profit Before Tax calculation (Total Income - Total Expenses)
const profitBeforeTax = computed(() => {
  const income = getTotal(incomeData.value);
  const expenses = getTotal(expensesData.value);

  const currentPBT = parseFloat(income.currentFormatted.replace(/[^0-9.-]+/g, '')) -
    parseFloat(expenses.currentFormatted.replace(/[^0-9.-]+/g, ''));

  const previousPBT = parseFloat(income.previousFormatted.replace(/[^0-9.-]+/g, '')) -
    parseFloat(expenses.previousFormatted.replace(/[^0-9.-]+/g, ''));

  const percentChange = previousPBT === 0 ? 0 : ((currentPBT - previousPBT) / previousPBT) * 100;

  return {
    currentFormatted: `₹${currentPBT.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    previousFormatted: `₹${previousPBT.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
    changeFormatted: `${percentChange.toFixed(1)}%`,
    isIncrease: percentChange >= 0,
  };
});

// Income Tax calculation (assumed 20% of Profit Before Tax for demo)
const incomeTax = computed(() => {
  const pbt = profitBeforeTax.value;
  const currentPBT = parseFloat(pbt.currentFormatted.replace(/[^0-9.-]+/g, ''));
  const previousPBT = parseFloat(pbt.previousFormatted.replace(/[^0-9.-]+/g, ''));
  
  // Calculate tax as 20% of profit before tax (only if positive)
  const currentTax = currentPBT > 0 ? currentPBT * 0.20 : 0;
  const previousTax = previousPBT > 0 ? previousPBT * 0.20 : 0;
  
  const percentChange = previousTax === 0 ? 0 : ((currentTax - previousTax) / previousTax) * 100;

  return {
    currentFormatted: `(₹${currentTax.toLocaleString('en-IN', { minimumFractionDigits: 2 })})`,
    previousFormatted: `(₹${previousTax.toLocaleString('en-IN', { minimumFractionDigits: 2 })})`,
    changeFormatted: `${percentChange.toFixed(1)}%`,
    isIncrease: percentChange >= 0,
  };
});

const downloadMenu = ref(false)

// Force table re-rendering
const forceTableRerender = () => {
  tableKey.value += 1
}

// Watch for layout changes that require table re-rendering
watch(showCompareMode, () => {
  nextTick(() => {
    forceTableRerender()
  })
})

const fetchProfitLossData = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await $api('/v1/accounts/profit-and-loss')

    if (response.success) {
      incomeData.value = response.data.income || []
      expensesData.value = response.data.expenses || []
      // Force table re-render after data is loaded
      await nextTick()
      forceTableRerender()
    } else {
      error.value = response.message || 'Failed to fetch profit & loss data'
    }
  } catch (err) {
    error.value = 'An error occurred while fetching data'
    console.error('Error fetching profit & loss data:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProfitLossData()
})

function downloadAs(type) {
  downloadMenu.value = false
  // trigger download logic
  console.log('Download as', type)
}



</script>

<template>
  <div class="account_ui_vcard">
    <VRow>
      <VCol cols="12">
        <VCard class="account_vcard_border shadow-none" title="Profit & Loss Statement"
          subtitle="For the period of Jan 01, 2025 to Jul 07, 2025">
          <template #append>
            <div class="d-flex align-center gap-2">
              <VSwitch v-model="showPercent" density="compact" inset label="Show %" class="account_swtich_btn mr-4"
                style="min-inline-size: 121px;" color="primary" hide-details />
              <v-date-input class="accounting_date_input" cancel-text="Close" style="inline-size: 300px;"
                multiple="range" ok-text="Apply">
                <template #prepend-inner>
                  <IconCalendar size="20" />
                </template>
              </v-date-input>
              <VMenu location="start" transition="slide-y-transition" offset-y :close-on-content-click="false">
                <template #activator="{ props }">
                  <VBtn v-bind="props" class="account_v_btn_outlined" variant="outlined" size="34" rounded="2">
                    <IconSettings size="22" />
                  </VBtn>
                </template>
                <VCard class="account_vcard_menu account_vcard_border">
                  <div class="py-1">
                    <div class="account_vcard_menu_item">
                      <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2"
                        @click="isFullWidthView = !isFullWidthView; forceTableRerender()">
                        <IconCheck v-if="isFullWidthView" size="16" />
                        <span :class="isFullWidthView ? '' : 'ml-6'">Full Width View</span>
                      </div>
                    </div>
                    <div class="account_vcard_menu_item">
                      <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2"
                        @click="showCompareMode = !showCompareMode">
                        <IconCheck v-if="showCompareMode" size="16" />
                        <span :class="showCompareMode ? '' : 'ml-6'">Compare Periods</span>
                      </div>
                    </div>
                    <VDivider class="my-2" />
                    <!-- Inside your main menu -->
                    <div class="account_vcard_menu_item">
                      <!-- Nested VMenu for Download -->
                      <VMenu v-model="downloadMenu" location="end" offset="10" transition="slide-y-transition"
                        :close-on-content-click="false">
                        <template #activator="{ props: downloadProps }">
                          <div v-bind="downloadProps"
                            class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                            <IconDownload size="16" />
                            <span>Download</span>
                            <IconChevronRight size="14" class="ml-auto" />
                          </div>
                        </template>
                        <VCard class="account_vcard_menu account_vcard_border" width="120">
                          <div class="py-1">
                            <div class="account_vcard_menu_item field_list_title cursor-pointer px-3 py-1"
                              @click="downloadAs('pdf')">
                              <span>PDF</span>
                            </div>
                            <div class="account_vcard_menu_item field_list_title cursor-pointer px-3 py-1"
                              @click="downloadAs('xls')">
                              <span>XLS</span>
                            </div>
                          </div>
                        </VCard>
                      </VMenu>
                    </div>
                    <div class="account_vcard_menu_item">
                      <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                        <IconPrinter size="16" />
                        <span>Print</span>
                      </div>
                    </div>
                  </div>
                </VCard>
              </VMenu>
            </div>
          </template>
          <VCardText>
            <VRow>
              <!-- Income Data Table -->
              <VCol :cols="12" :lg="isFullWidthView ? 12 : 6" :md="isFullWidthView ? 12 : 6"
                :sm="isFullWidthView ? 12 : 6">
                <VCard variant="text" class="h-100 account_vcard_border account_income_card shadow-none">
                  <VDataTable :key="`income-${tableKey}`" :headers="incomeHeaders" :items="flatIncomeData" class="account_income_table"
                    hide-default-footer item-value="name" :items-per-page="-1">
                    <template #item="{ item }">
                      <tr :class="item.type === 'group' ? 'amount_income_item_title' : ''">
                        <!-- Name / tree column -->
                        <td>
                          <div class="d-flex align-center gap-2" :style="{
                            paddingLeft: item.account_type === 'group' ? '0px' : 
                                        item.account_type === 'sub-group' ? '20px' : '40px'
                          }">
                            <IconFolder v-if="item.type === 'group'" size="16" />
                            <IconFileText v-else size="16" />
                            <p class="mb-0 amount_income_group_item" :class="item.type === 'ledger'
                              ? 'account_ledger_secondary'
                              : item.name?.toLowerCase().includes('expense')
                                ? 'account_group_error'
                                : 'account_group_primary'">
                              {{ item.name }}
                            </p>
                            <VChip v-if="item.percent && showPercent" density="compact" variant="tonal"
                              class="account_income_chip py-1 px-1"
                              :class="item.type === 'ledger' ? 'account_chip_outline' : 'account_chip_secondary'">
                              ({{ item.percent }})
                            </VChip>
                          </div>
                        </td>
                        <!-- Current -->
                        <td class="text-end" v-if="true">
                          <p class="mb-0 amount_inc_current_item"
                            :class="item.type === 'group' ? 'amount_inc_current_font_wght' : ''">
                            {{ item.current }}
                          </p>
                        </td>
                        <!-- Previous -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <p class="mb-0 amount_inc_previous_item"
                              :class="item.type === 'group' ? 'amount_inc_previous_font_wght' : ''">
                              {{ item.previous }}
                            </p>
                          </td>
                        </Transition>
                        <!-- Change -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <div class="d-flex justify-end align-center gap-2">
                              <p class="mb-0 amount_inc_change_item" :class="[
                                item.type === 'ledger' ? 'amount_inc_change_font_wght' : '',
                                parseFloat(item.change) > 0 ? 'text-success' :
                                  parseFloat(item.change) < 0 ? 'text-error' : 'text-medium-emphasis'
                              ]">
                                {{ item.change }}
                              </p>
                              <IconStar v-if="item.new" size="12" class="text-info" />
                              <component v-else
                                :is="$renderTablerIcon(parseFloat(item.change) < 0 ? 'arrow-down' : 'arrow-up')"
                                size="12" :class="parseFloat(item.change) < 0 ? 'text-error' : 'text-success'" />
                            </div>
                          </td>
                        </Transition>
                      </tr>
                    </template>
                  </VDataTable>
                  <VDivider class="my-2" />
                  <!-- Total Income Row -->
                  <div class="d-flex justify-end mb-3 px-4">
                    <table style="min-inline-size: 100%;">
                      <tr class="font-weight-medium">
                        <td style="min-inline-size: 240px;" class="text-start">Total Income</td>
                        <td class="text-end amount_inc_current_item">{{ totalIncome.currentFormatted }}</td>
                        <Transition name="slide-fade">
                          <td class="text-end amount_inc_previous_item" v-if="showCompareMode">{{
                            totalIncome.previousFormatted }}
                          </td>
                        </Transition>
                        <Transition name="slide-fade">
                          <td class="text-end d-flex align-center amount_inc_change_item justify-end gap-2"
                            v-if="showCompareMode">
                            {{ totalIncome.changeFormatted }}
                            <component :is="$renderTablerIcon(totalIncome.isIncrease ? 'arrow-up' : 'arrow-down')"
                              size="12" :class="totalIncome.isIncrease ? 'text-success' : 'text-error'" />
                          </td>
                        </Transition>
                      </tr>
                    </table>
                  </div>
                </VCard>
              </VCol>
              <!-- Expenses Data Table -->
              <VCol :cols="12" :lg="isFullWidthView ? 12 : 6" :md="isFullWidthView ? 12 : 6"
                :sm="isFullWidthView ? 12 : 6">
                <VCard variant="text" class="h-100 account_vcard_border account_expense_card shadow-none">
                  <VDataTable :key="`expenses-${tableKey}`" :headers="expensesHeaders" :items="flatExpensesData"
                    class="account_income_table account_expense_table" hide-default-footer item-value="name"
                    :items-per-page="-1">
                    <template #item="{ item }">
                      <tr :class="item.type === 'group' ? 'amount_income_item_title' : ''">
                        <!-- Name / tree column -->
                        <td>
                          <div class="d-flex align-center gap-2" :style="{
                            paddingLeft: item.account_type === 'group' ? '0px' : 
                                        item.account_type === 'sub-group' ? '20px' : '40px'
                          }">
                            <IconFolder v-if="item.type === 'group'" size="16" />
                            <IconFileText v-else size="16" />
                            <p class="mb-0 amount_income_group_item" :class="item.type === 'ledger'
                              ? 'account_ledger_secondary'
                              : item.name?.toLowerCase().includes('income')
                                ? 'account_group_success'
                                : 'account_group_error'">
                              {{ item.name }}
                            </p>
                            <VChip v-if="item.percent && showPercent" density="compact" variant="tonal"
                              class="account_income_chip py-1 px-1"
                              :class="item.type === 'ledger' ? 'account_chip_outline' : 'account_chip_secondary'">
                              ({{ item.percent }})
                            </VChip>
                          </div>
                        </td>
                        <!-- Current -->
                        <td class="text-end" v-if="true">
                          <p class="mb-0 amount_inc_current_item"
                            :class="item.type === 'group' ? 'amount_inc_current_font_wght' : ''">
                            {{ item.current }}
                          </p>
                        </td>
                        <!-- Previous -->
                        <td v-if="showCompareMode" class="text-end">
                          <Transition name="slide-fade">
                            <p class="mb-0 amount_inc_previous_item"
                              :class="item.type === 'group' ? 'amount_inc_previous_font_wght' : ''">
                              {{ item.previous }}
                            </p>
                          </Transition>
                        </td>
                        <!-- Change -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <div class="d-flex justify-end align-center gap-2">
                              <p class="mb-0 amount_inc_change_item" :class="[
                                item.type === 'ledger' ? 'amount_inc_change_font_wght' : '',
                                parseFloat(item.change) > 0 ? 'text-success' :
                                  parseFloat(item.change) < 0 ? 'text-error' : 'text-medium-emphasis'
                              ]">
                                {{ item.change }}
                              </p>
                              <IconStar v-if="item.new" size="12" class="text-info" />
                              <component v-else
                                :is="$renderTablerIcon(parseFloat(item.change) < 0 ? 'arrow-down' : 'arrow-up')"
                                size="12" :class="parseFloat(item.change) < 0 ? 'text-error' : 'text-success'" />
                            </div>
                          </td>
                        </Transition>
                      </tr>
                    </template>
                  </VDataTable>
                  <VDivider class="my-2" />
                  <!-- Total Expenses Row -->
                  <div class="d-flex justify-end mb-3 px-4">
                    <table style="min-inline-size: 100%;">
                      <tr class="font-weight-medium">
                        <td style="min-inline-size: 240px;" class="text-start">Total Expenses</td>
                        <td class="text-end amount_inc_current_item">{{ totalExpenses.currentFormatted }}</td>
                        <Transition name="slide-fade">
                          <td class="text-end amount_inc_previous_item" v-if="showCompareMode">{{
                            totalExpenses.previousFormatted
                          }}</td>
                        </Transition>
                        <Transition name="slide-fade">
                          <td class="text-end d-flex align-center justify-end amount_inc_change_item gap-2"
                            v-if="showCompareMode">
                            {{ totalExpenses.changeFormatted }}
                            <component :is="$renderTablerIcon(totalExpenses.isIncrease ? 'arrow-up' : 'arrow-down')"
                              size="12" :class="totalExpenses.isIncrease ? 'text-success' : 'text-error'" />
                          </td>
                        </Transition>
                      </tr>
                    </table>
                  </div>
                </VCard>
              </VCol>
            </VRow>
            <VDivider class="my-3" />
            <VRow class="justify-end">
              <VCol cols="12" lg="6" md="6">
                <VCard variant="text" class="account_vcard_border account_expense_card shadow-none">
                  <VCardText class="">
                    <VRow class="justify-content-between">
                      <VCol cols="7">
                        <div class="d-flex align-center gap-2">
                          <h5 class="mb-0">Profit Before Tax</h5>
                          <VChip v-if="showPercent" density="compact"
                            class="account_chip account_chip_outlined py-1 px-1">
                            {{ profitBeforeTax.changeFormatted }}
                          </VChip>
                        </div>
                        <p class="mb-0 mt-2">Less: Income Tax</p>
                      </VCol>
                      <VCol class="px-0" cols="2">
                        <div class="d-flex justify-end flex-column">
                          <p class="mb-0 amount_inc_current_item">{{ profitBeforeTax.currentFormatted }}</p>
                          <p class="mb-0 mt-2">{{ incomeTax.currentFormatted }}</p>
                        </div>
                      </VCol>
                      <Transition name="slide-fade">
                        <VCol v-if="showCompareMode" class="px-0" cols="2">
                          <div class="d-flex justify-end flex-column">
                            <p class="mb-0 amount_inc_previous_item">{{ profitBeforeTax.previousFormatted }}</p>
                            <p class="mb-0 mt-2">{{ incomeTax.previousFormatted }}</p>
                          </div>
                        </VCol>
                      </Transition>
                      <Transition name="slide-fade">
                        <VCol v-if="showCompareMode" class="px-0" cols="1">
                          <div class="d-flex justify-end align-center gap-2">
                            <p class="mb-0 amount_inc_change_item">{{ profitBeforeTax.changeFormatted }}</p>
                            <component :is="$renderTablerIcon(profitBeforeTax.isIncrease ? 'arrow-up' : 'arrow-down')"
                              size="12" :class="profitBeforeTax.isIncrease ? 'text-success' : 'text-error'" />
                          </div>
                        </VCol>
                      </Transition>
                    </VRow>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
            <VRow class="justify-end mt-2">
              <VCol cols="12">
                <div class="d-flex justify-space-between align-center px-4 py-2 rounded"
                  style="background-color: #e6fff0;">
                  <!-- Left Side -->
                  <div class="d-flex align-center gap-2">
                    <h5 class="mb-0 text-success font-weight-bold">Net Profit</h5>
                    <VChip density="compact" class="py-1 px-2 text-success" variant="outlined">
                      {{ netProfit.percent }}
                    </VChip>
                  </div>
                  <!-- Right Side -->
                  <div class="d-flex align-center gap-4">
                    <!-- Previous -->
                    <div v-if="showCompareMode" class="px-2 py-1"
                      style=" border-radius: 4px;background-color: #f5d6c6;">
                      <span class="font-weight-medium">{{ netProfit.previousFormatted }}</span>
                    </div>
                    <!-- Current -->
                    <div>
                      <span class="text-success font-weight-bold">{{ netProfit.currentFormatted }}</span>
                    </div>
                    <!-- Change -->
                    <div v-if="showCompareMode" class="d-flex align-center gap-1">
                      <span :class="netProfit.isIncrease ? 'text-success' : 'text-error'">{{
                        netProfit.percent
                        }}</span>
                      <component :is="$renderTablerIcon(netProfit.isIncrease ? 'arrow-up' : 'arrow-down')" size="14"
                        :class="netProfit.isIncrease ? 'text-success' : 'text-error'" />
                    </div>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
          <VDivider class="my-2" />
          <VCardText class="account_note_section pt-1 px-4">
            <p class="mb-0">Notes:</p>
            <ul class="px-5">
              <li class="mb-1">All figures are in Indian Rupee (INR) unless otherwise stated.</li>
              <li class="mb-1">This is an unaudited statement generated for internal review purposes.</li>
              <li class="mb-1">GST collected and paid are accounted for under Duties & Taxes and do not affect the
                profit
                calculation directly.</li>
            </ul>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
