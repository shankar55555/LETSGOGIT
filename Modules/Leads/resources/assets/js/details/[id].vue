<template>
  <BaseSpinner class="d-flex" v-if="!InfoData" />
  <div v-else>
    <!-- 👉 Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <div class="d-flex align-center">
          <h5 class="text-h5 mb-1 mr-3">Lead: {{ InfoData.name }}</h5>
        </div>
        <div class="text-body-1">
          {{ $typeAccordingDateFormatChange(InfoData.created_at, 'full_date_1') }}
        </div>
      </div>
      <VBtn variant="tonal" color="primary" @click="router.go(-1)">
        <VIcon icon="tabler-arrow-back-up" class="mr-2" />Back
      </VBtn>
    </div>

    <VRow v-if="InfoData">
      <VCol cols="12">
        <VTabs v-model="userTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
            <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
          </VTab>
        </VTabs>

        <VWindow v-model="userTab" class="mt-6 disable-tab-transition" :touch="false">
          <VWindowItem v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
            <Information v-if="tab.slug === 'information' && $can('leads', 'show')" :InfoData="InfoData"
              @backCallLeadInfo="refreshData" />
            <LeadFollowUp v-if="tab.slug === 'follow-up' && $can('followUp', 'view')" :is="MODULE_FOLLOW_UP"
              :type="QUOTATION_LEAD" />
            <LeadSiteVisit v-if="tab.slug === 'site-visit' && $can('siteVisit', 'view')" :is="MODULE_SITE_VISIT"
              :type="QUOTATION_LEAD" />
            <LeadQuotation v-if="tab.slug === 'quotations' && $can('quotation', 'view')" :is="MODULE_QUOTATION"
              :type="QUOTATION_LEAD" :id="route.params.id" />
            <LeadInvoice v-if="tab.slug === 'invoices' && $can('invoice', 'view')" :is="MODULE_INVOICE"
              :type="QUOTATION_LEAD" :id="route.params.id" />
            <CommonNotificationLog
              v-if="tab.slug === 'notification-log' && ($can('emailLog', 'view') || $can('whatsAppLog', 'view') || $can('bellNotificationLog', 'view'))"
              :module_id="route.params.id" :log_type="MODULE_LEAD" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>

    <VAlert v-else type="error" variant="tonal">
      Lead with ID {{ route.params.id }} not found!
    </VAlert>
  </div>
</template>

<script setup>
import { getFilteredTabs } from "@/utils/common";
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';
// import CommonNotificationLog from '../../../../../../resources/js/@core/components/CommonNotificationLog.vue';
import Information from './tabs/Information.vue';

const router = useRouter();
const route = useRoute();

const InfoData = ref(null);
const userTab = ref(localStorage.getItem("activeLeadView") || "information");

// Define all tab configurations
const tabs = [
  { title: 'Information', slug: 'information', icon: 'tabler-user-search', action: 'leads', subject: 'show' },
  { title: 'Follow Up', slug: 'follow-up', icon: 'tabler-message', action: 'followUp', subject: 'view' },
  { title: 'Site Visit', slug: 'site-visit', icon: 'tabler-map', action: 'siteVisit', subject: 'view' },
  { title: 'Quotations', slug: 'quotations', icon: 'tabler-file-text', action: 'quotation', subject: 'view' },
  { title: 'Invoices', slug: 'invoices', icon: 'tabler-file-invoice', action: 'invoice', subject: 'view' },
  {
    title: 'Notification Logs', slug: 'notification-log', icon: 'tabler-bell', extraPermissions: [
      { action: "emailLog", subject: "view" },
      { action: "whatsAppLog", subject: "view" },
      { action: "bellNotificationLog", subject: "view" },
    ],
  }
];

// Dynamically import module components
const modules = import.meta.glob('/Modules/**/resources/assets/js/list/index.vue');
const getComponentByModule = (moduleName) => {
  const matchPath = Object.keys(modules).find(path => path.includes(`/Modules/${moduleName}/`));
  return matchPath ? defineAsyncComponent(modules[matchPath]) : null;
};

const LeadFollowUp = getComponentByModule(MODULE_FOLLOW_UP);
const LeadSiteVisit = getComponentByModule(MODULE_SITE_VISIT);
const LeadQuotation = getComponentByModule(MODULE_QUOTATION);
const LeadInvoice = getComponentByModule(MODULE_INVOICE);

// Filter tabs based on permissions
const filteredTabs = computed(() => getFilteredTabs(tabs));

// Watch active tab and store in localStorage
watch(userTab, (newTab) => {
  localStorage.setItem("activeLeadView", newTab);
});

// Fetch lead data
const refreshData = async () => {
  try {
    const { data } = await $api(`/leads/${route.params.id}`);
    InfoData.value = data;
  } catch (error) {
    console.error('Failed to fetch lead data:', error);
    toast.error(error?.response?.data?.message || 'Failed to load lead details.');
  }
};

onMounted(async () => {
  await refreshData();
  const savedTab = localStorage.getItem("activeLeadView");
  userTab.value = filteredTabs.value.find(tab => tab.slug === savedTab)?.slug || filteredTabs.value[0]?.slug || "information";
});
</script>
