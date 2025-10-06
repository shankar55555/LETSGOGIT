<script setup>
import EcommerceCongratulationsJohn from '@/views/dashboards/ecommerce/EcommerceCongratulationsJohn.vue'
import EcommerceGeneratedLeads from '@/views/dashboards/ecommerce/EcommerceGeneratedLeads.vue'
import EcommerceInvoiceTable from '@/views/dashboards/ecommerce/EcommerceInvoiceTable.vue'
import EcommerceOrder from '@/views/dashboards/ecommerce/EcommerceOrder.vue'
import EcommercePopularProducts from '@/views/dashboards/ecommerce/EcommercePopularProducts.vue'
import EcommerceRevenueReport from '@/views/dashboards/ecommerce/EcommerceRevenueReport.vue'
import EcommerceStatistics from '@/views/dashboards/ecommerce/EcommerceStatistics.vue'
import EcommerceTransactions from '@/views/dashboards/ecommerce/EcommerceTransactions.vue'


import LogisticsCardStatistics from '@/views/apps/logistics/LogisticsCardStatistics.vue'
import LogisticsOverviewTable from '@/views/apps/logistics/LogisticsOverviewTable.vue'
import LogisticsShipmentStatistics from '@/views/apps/logistics/LogisticsShipmentStatistics.vue'
import LogisticsVehicleOverview from '@/views/apps/logistics/LogisticsVehicleOverview.vue'
</script>

<template>
  <VRow class="match-height">
    <!-- 👉 Congratulation John -->
    <VCol cols="12" md="5" lg="4">
      <EcommerceCongratulationsJohn />
    </VCol>

    <!-- 👉 Ecommerce Transition -->
    <VCol cols="12" md="7" lg="8">
      <EcommerceStatistics class="h-100" />
    </VCol>

    <!-- 👉 Total Profit Line -->
    <VCol cols="12" md="4" lg="8">
      <EcommerceRevenueReport />
    </VCol>

    <!-- 👉 Generated Leads -->
    <VCol cols="12" md="6" lg="4">
      <EcommerceGeneratedLeads />
    </VCol>

    <VCol cols="12">
      <LogisticsCardStatistics />
    </VCol>


    <VCol cols="12" md="6">
      <LogisticsVehicleOverview />
    </VCol>

    <VCol cols="12" md="6">
      <LogisticsShipmentStatistics />
    </VCol>

    <VCol cols="12">
      <LogisticsOverviewTable />
    </VCol>

    <!-- 👉 Popular Products -->
    <VCol cols="12" sm="6" lg="4">
      <EcommercePopularProducts />
    </VCol>

    <!-- 👉 Order -->
    <VCol cols="12" sm="6" lg="4">
      <EcommerceOrder />
    </VCol>

    <!-- 👉 Transaction -->
    <VCol cols="12" sm="6" lg="4">
      <EcommerceTransactions />
    </VCol>

    <!-- 👉 Invoice Table -->
    <VCol cols="12" lg="8">
      <EcommerceInvoiceTable />
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
