<script setup>
import { computed, onMounted, ref } from "vue";

const loading = ref(false);
const assetsData = ref([]);
const liabilitiesData = ref([]);

// API functions
const fetchBalanceSheetData = async () => {
  try {
    loading.value = true;
    console.log('Fetching balance sheet data...');
    const response = await $api('/v1/accounts/balance-sheet', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    console.log('API Response:', response);

    if (response && response.success && response.data.assets && response.data.liabilities) {
      console.log('Processing API data...');
      // Process hierarchical data from API
      assetsData.value = response.data.assets || [];
      liabilitiesData.value = response.data.liabilities || [];
      console.log('Assets Data:', assetsData.value);
      console.log('Liabilities Data:', liabilitiesData.value);
    } else {
      console.error('Invalid API response structure:', response);
      // Set empty arrays instead of static data
      assetsData.value = [];
      liabilitiesData.value = [];
    }
  } catch (error) {
    console.error('Error fetching balance sheet data:', error);
    // Set empty arrays instead of static data
    assetsData.value = [];
    liabilitiesData.value = [];
  } finally {
    loading.value = false;
  }
};

// Initialize data on component mount
onMounted(() => {
  fetchBalanceSheetData();
});

const assetsHeaders = computed(() => {
  return [
    {
      title: "Assets",
      value: "name",
      width: "550px",
      align: "start",
      visible: true,
    },
    {
      title: showCompareMode.value ? "Current" : "",
      value: "current",
      width: "",
      align: "end",
      visible: true,
    },
    {
      title: "Previous",
      value: "previous",
      width: "",
      align: "end",
      visible: showCompareMode.value,
    },
    {
      title: "Change",
      value: "change",
      width: "",
      align: "end",
      visible: showCompareMode.value,
    },
  ].filter((h) => h.visible);
});

const liabilitiesHeaders = computed(() => {
  return [
    {
      title: "Liabilities",
      value: "name",
      width: "550px",
      align: "start",
      visible: true,
    },
    {
      title: showCompareMode.value ? "Current" : "",
      value: "current",
      width: "",
      align: "end",
      visible: true,
    },
    {
      title: "Previous",
      value: "previous",
      width: "",
      align: "end",
      visible: showCompareMode.value,
    },
    {
      title: "Change",
      value: "change",
      width: "",
      align: "end",
      visible: showCompareMode.value,
    },
  ].filter((h) => h.visible);
});

function flattenTree(data, level = 0, parentType = "") {
  const result = [];

  if (!data || !Array.isArray(data)) {
    return result;
  }

  data.forEach(item => {
    // Add the current item with level and parentType
    const flatItem = {
      ...item,
      level,
      parentType: parentType || item.type
    };

    // Remove children from the flat item to avoid duplication
    delete flatItem.children;
    result.push(flatItem);

    // Recursively process children if they exist
    if (item.children && Array.isArray(item.children) && item.children.length > 0) {
      result.push(...flattenTree(item.children, level + 1, item.type));
    }
  });

  return result;
}

const flatAssetsData = computed(() => {
  // Process hierarchical data with children arrays
  return flattenTree(assetsData.value);
});

const flatLiabilitiesData = computed(() => {
  // Process hierarchical data with children arrays
  return flattenTree(liabilitiesData.value);
});

const isFullWidthView = ref(false);
const showCompareMode = ref(false);
const showPercent = ref(false);

const getTotal = (data) => {
  let total = {
    current: 0,
    previous: 0,
    change: 0,
  };
  function accumulate(items) {
    for (const item of items) {
      // Sum all accounts - API provides calculated balances
      const curr = typeof item.current === 'number' ? item.current : parseFloat(item.current?.replace(/[^0-9.-]+/g, "") || 0);
      const prev = typeof item.previous === 'number' ? item.previous : parseFloat(item.previous?.replace(/[^0-9.-]+/g, "") || 0);
      total.current += curr;
      total.previous += prev;

      if (item.children?.length) {
        accumulate(item.children);
      }
    }
  }
  accumulate(data);
  const percentChange =
    total.previous === 0
      ? 0
      : ((total.current - total.previous) / total.previous) * 100;
  return {
    currentFormatted: `₹${total.current.toLocaleString("en-IN", {
      minimumFractionDigits: 2,
    })}`,
    previousFormatted: `₹${total.previous.toLocaleString("en-IN", {
      minimumFractionDigits: 2,
    })}`,
    changeFormatted: `${percentChange.toFixed(1)}%`,
    isIncrease: percentChange >= 0,
  };
};

const totalAssets = computed(() => getTotal(assetsData.value));
const totalLiabilities = computed(() => getTotal(liabilitiesData.value));

const downloadMenu = ref(false);

function downloadAs(type) {
  downloadMenu.value = false;
  // trigger download logic
  console.log("Download as", type);
}
</script>

<template>
  <div class="account_ui_vcard">
    <VRow>
      <VCol cols="12">
        <VCard class="account_vcard_border shadow-none" title="Balance Sheet" subtitle="As at Jul 09, 2025">
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
                        @click="isFullWidthView = !isFullWidthView">
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
              <!-- Assets Data Table -->
              <VCol :cols="12" :lg="isFullWidthView ? 12 : 6" :md="isFullWidthView ? 12 : 6"
                :sm="isFullWidthView ? 12 : 6" class="col-transition"
                :class="isFullWidthView ? 'col-expand' : 'col-shrink'">
                <VCard variant="text"
                  class="h-100 account_vcard_border card-content-transition account_income_card shadow-none">
                  <VDataTable :headers="assetsHeaders" :items="flatAssetsData" class="account_income_table"
                    hide-default-footer item-value="name" :items-per-page="-1">
                    <template #item="{ item }">
                      <tr :class="item.type === 'group'
                        ? item.level === 0
                          ? 'amount_income_item_title'
                          : 'amount_income_overlay_item_title'
                        : ''
                        ">
                        <!-- Name / tree column -->
                        <td>
                          <div class="d-flex align-center gap-2" :style="{ paddingLeft: `${item.level * 24}px` }">
                            <IconFolder v-if="item.type === 'group'" size="16" />
                            <IconFileText v-else size="16" />
                            <p class="mb-0 amount_income_group_item" :class="item.type === 'ledger'
                              ? 'account_ledger_secondary'
                              : item.name
                                ?.toLowerCase()
                                .includes('liability')
                                ? 'account_group_error'
                                : 'account_group_primary'
                              ">
                              {{ item.name }}
                            </p>
                            <VChip v-if="item.percent && showPercent" density="compact" variant="tonal"
                              class="account_income_chip py-1 px-1" :class="item.type === 'ledger'
                                ? 'account_chip_outline'
                                : 'account_chip_secondary'
                                ">
                              ({{ item.percent }})
                            </VChip>
                          </div>
                        </td>
                        <!-- Current -->
                        <td class="text-end" v-if="true">
                          <p class="mb-0 amount_inc_current_item" :class="item.level === 0 && item.type === 'group'
                            ? 'amount_inc_current_font_wght'
                            : ''
                            ">
                            {{ item.currentFormatted || (typeof item.current === 'number' ? '₹' +
                              item.current.toLocaleString('en-IN', { minimumFractionDigits: 2 }) : item.current) }}
                          </p>
                        </td>
                        <!-- Previous -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <p class="mb-0 amount_inc_previous_item" :class="item.level > 0 && item.type === 'group'
                              ? 'amount_inc_previous_font_wght'
                              : ''
                              ">
                              {{ item.previousFormatted || (typeof item.previous === 'number' ? '₹' +
                                item.previous.toLocaleString('en-IN', { minimumFractionDigits: 2 }) : item.previous) }}
                            </p>
                          </td>
                        </Transition>
                        <!-- Change -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <div class="d-flex justify-end align-center gap-2">
                              <p class="mb-0 amount_inc_change_item" :class="[
                                item.type === 'ledger'
                                  ? 'amount_inc_change_font_wght'
                                  : '',
                                parseFloat((item.change || '0%').replace('%', '')) > 0
                                  ? 'text-success'
                                  : parseFloat((item.change || '0%').replace('%', '')) < 0
                                    ? 'text-error'
                                    : 'text-medium-emphasis',
                              ]">
                                {{ item.change }}
                              </p>
                              <IconStar v-if="item.new" size="12" class="text-info" />
                              <component v-else
                                :is="$renderTablerIcon(parseFloat((item.change || '0%').replace('%', '')) < 0 ? 'arrow-down' : 'arrow-up')"
                                size="12"
                                :class="parseFloat((item.change || '0%').replace('%', '')) < 0 ? 'text-error' : 'text-success'" />
                            </div>
                          </td>
                        </Transition>
                      </tr>
                    </template>
                  </VDataTable>
                  <VDivider class="my-2" />
                  <!-- Total Assets Row -->
                  <div class="d-flex justify-end mb-3 px-4">
                    <table style="min-inline-size: 100%;">
                      <tr class="font-weight-medium">
                        <td style="min-inline-size: 240px;" class="text-start">
                          Total Assets
                        </td>
                        <td class="text-end amount_inc_current_item">
                          {{ totalAssets.currentFormatted }}
                        </td>
                        <Transition name="slide-fade">
                          <td class="text-end amount_inc_previous_item" v-if="showCompareMode">
                            {{ totalAssets.previousFormatted }}
                          </td>
                        </Transition>
                        <Transition name="slide-fade">
                          <td class="text-end d-flex align-center amount_inc_change_item justify-end gap-2"
                            v-if="showCompareMode">
                            {{ totalAssets.changeFormatted }}
                            <IconArrowUp v-if="totalAssets.isIncrease" size="12" class="text-success" />
                            <IconArrowDown v-else size="12" class="text-error" />
                          </td>
                        </Transition>
                      </tr>
                    </table>
                  </div>
                </VCard>
              </VCol>
              <!-- Liabilities Data Table -->
              <VCol :cols="12" :lg="isFullWidthView ? 12 : 6" :md="isFullWidthView ? 12 : 6"
                :sm="isFullWidthView ? 12 : 6" class="col-transition"
                :class="isFullWidthView ? 'col-expand' : 'col-shrink'">
                <VCard variant="text"
                  class="h-100 account_vcard_border card-content-transition account_expense_card shadow-none">
                  <VDataTable :headers="liabilitiesHeaders" :items="flatLiabilitiesData"
                    class="account_income_table account_expense_table" hide-default-footer item-value="name"
                    :items-per-page="-1">
                    <template #item="{ item }">
                      <tr :class="item.type === 'group'
                        ? item.level === 0
                          ? 'amount_income_item_title'
                          : 'amount_income_overlay_item_title'
                        : ''
                        ">
                        <!-- Name / tree column -->
                        <td>
                          <div class="d-flex align-center gap-2" :style="{ paddingLeft: `${item.level * 24}px` }">
                            <IconFolder v-if="item.type === 'group'" size="16" />
                            <IconFileText v-else size="16" />
                            <p class="mb-0 amount_income_group_item" :class="item.type === 'ledger'
                              ? 'account_ledger_secondary'
                              : item.name?.toLowerCase().includes('asset')
                                ? 'account_group_success'
                                : 'account_group_error'
                              ">
                              {{ item.name }}
                            </p>
                            <VChip v-if="item.percent && showPercent" density="compact" variant="tonal"
                              class="account_income_chip py-1 px-1" :class="item.type === 'ledger'
                                ? 'account_chip_outline'
                                : 'account_chip_secondary'
                                ">
                              ({{ item.percent }})
                            </VChip>
                          </div>
                        </td>
                        <!-- Current -->
                        <td class="text-end" v-if="true">
                          <p class="mb-0 amount_inc_current_item" :class="item.level === 0 && item.type === 'group'
                            ? 'amount_inc_current_font_wght'
                            : ''
                            ">
                            {{ item.currentFormatted || (typeof item.current === 'number' ? '₹' +
                              item.current.toLocaleString('en-IN', { minimumFractionDigits: 2 }) : item.current) }}
                          </p>
                        </td>
                        <!-- Previous -->
                        <td v-if="showCompareMode" class="text-end">
                          <Transition name="slide-fade">
                            <p class="mb-0 amount_inc_previous_item" :class="item.level > 0 && item.type === 'group'
                              ? 'amount_inc_previous_font_wght'
                              : ''
                              ">
                              {{ item.previousFormatted || (typeof item.previous === 'number' ? '₹' +
                                item.previous.toLocaleString('en-IN', { minimumFractionDigits: 2 }) : item.previous) }}
                            </p>
                          </Transition>
                        </td>
                        <!-- Change -->
                        <Transition name="slide-fade">
                          <td v-if="showCompareMode" class="text-end">
                            <div class="d-flex justify-end align-center gap-2">
                              <p class="mb-0 amount_inc_change_item" :class="[
                                item.type === 'ledger'
                                  ? 'amount_inc_change_font_wght'
                                  : '',
                                parseFloat((item.change || '0%').replace('%', '')) > 0
                                  ? 'text-success'
                                  : parseFloat((item.change || '0%').replace('%', '')) < 0
                                    ? 'text-error'
                                    : 'text-medium-emphasis',
                              ]">
                                {{ item.change }}
                              </p>
                              <IconStar v-if="item.new" size="12" class="text-info" />
                              <component v-else
                                :is="$renderTablerIcon(parseFloat((item.change || '0%').replace('%', '')) < 0 ? 'arrow-down' : 'arrow-up')"
                                size="12"
                                :class="parseFloat((item.change || '0%').replace('%', '')) < 0 ? 'text-error' : 'text-success'" />
                            </div>
                          </td>
                        </Transition>
                      </tr>
                    </template>
                  </VDataTable>
                  <VDivider class="my-2" />
                  <!-- Total Liabilities Row -->
                  <div class="d-flex justify-end mb-3 px-4">
                    <table style="min-inline-size: 100%;">
                      <tr class="font-weight-medium">
                        <td style="min-inline-size: 240px;" class="text-start">
                          Total Liabilities
                        </td>
                        <td class="text-end amount_inc_current_item">
                          {{ totalLiabilities.currentFormatted }}
                        </td>
                        <Transition name="slide-fade">
                          <td class="text-end amount_inc_previous_item" v-if="showCompareMode">
                            {{ totalLiabilities.previousFormatted }}
                          </td>
                        </Transition>
                        <Transition name="slide-fade">
                          <td class="text-end d-flex align-center justify-end amount_inc_change_item gap-2"
                            v-if="showCompareMode">
                            {{ totalLiabilities.changeFormatted }}
                            <IconArrowUp v-if="totalLiabilities.isIncrease" size="12" class="text-success" />
                            <IconArrowDown v-else size="12" class="text-error" />
                          </td>
                        </Transition>
                      </tr>
                    </table>
                  </div>
                </VCard>
              </VCol>
            </VRow>
            <VDivider class="mt-3 mb-0" />
            <VRow class="justify-end mt-2">
              <VCol cols="12" lg="6" md="6">
                <div class="d-flex justify-space-between align-center px-4 py-3 rounded total_assets_card">
                  <div class="d-flex align-center gap-2">
                    <h5 class="mb-0 account_assets_title">Total Assets</h5>
                  </div>
                  <div>
                    <span class="account_assets_title">{{
                      totalAssets.currentFormatted
                    }}</span>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" lg="6" md="6">
                <div class="d-flex justify-space-between align-center px-4 py-3 rounded total_liabilities_card">
                  <div>
                    <h5 class="mb-0 account_liabilities_title">
                      Total Liabilities
                    </h5>
                  </div>
                  <div>
                    <span class="account_liabilities_title">{{ totalLiabilities.currentFormatted }}</span>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
          <VDivider />
          <VCardText class="account_note_section pt-1 px-4">
            <p class="mb-0">Notes:</p>
            <ul class="px-5">
              <li class="mb-1">
                All figures are in Indian Rupees (INR) unless otherwise stated.
              </li>
              <li class="mb-1">
                This is an unaudited statement generated for internal review
                purposes.
              </li>
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

/* Add column width transition animations */
.col-transition {
  transform-origin: center;
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.col-expand {
  animation: expandColumn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.col-shrink {
  animation: shrinkColumn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

@keyframes expandColumn {
  0% {
    opacity: 0.8;
    transform: scale(0.95);
  }

  50% {
    transform: scale(1.02);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes shrinkColumn {
  0% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    transform: scale(0.98);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

/* Card content animation */
.card-content-transition {
  transition: all 0.3s ease;
}
</style>
