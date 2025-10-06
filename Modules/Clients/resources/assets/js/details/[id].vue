<template>
  <div>
    <!-- 👉 Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h5 class="text-h5 mb-1">Client: {{ InfoData?.name }}</h5>
        <div class="text-body-1">{{ makeDateFormat(InfoData?.created_at) }}</div>
      </div>
      <div class="d-flex gap-4">
        <VBtn variant="tonal" color="success" :to="{ name: 'clients-list' }">Back</VBtn>
      </div>
    </div>

    <VRow v-if="InfoData">
      <VCol cols="12">
        <!-- Tabs -->
        <VTabs v-model="userTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tabItem in filterTabs" :key="tabItem.slug" :value="tabItem.slug">
            <VIcon size="20" start :icon="tabItem.icon" />
            {{ tabItem.title }}
          </VTab>
        </VTabs>

        <VWindow v-model="userTab" class="disable-tab-transition" :touch="false">
          <VWindowItem value="information">
            <Information :InfoData="InfoData" />
          </VWindowItem>

          <VWindowItem value="follow-up" v-if="dynamicComponents.FollowUp.value">
            <component :is="dynamicComponents.FollowUp.value" :type="QUOTATION_CLIENT" :id="route.params.id" />
          </VWindowItem>

          <VWindowItem value="site-visit" v-if="dynamicComponents.SiteVisit.value">
            <component :is="dynamicComponents.SiteVisit.value" :type="QUOTATION_CLIENT" :id="route.params.id" />
          </VWindowItem>

          <VWindowItem value="quotations" v-if="dynamicComponents.Quotations.value">
            <component :is="dynamicComponents.Quotations.value" :type="QUOTATION_CLIENT" :id="route.params.id" />
          </VWindowItem>

          <VWindowItem value="invoices" v-if="dynamicComponents.Invoices.value">
            <component :is="dynamicComponents.Invoices.value" :type="QUOTATION_CLIENT" :id="route.params.id" />
          </VWindowItem>

          <VWindowItem value="notification-log" v-if="showNotificationLogTab">
            <CommonNotificationLog v-if="userTab === 'notification-log'" :module_id="route.params.id"
              :log_type="MODULE_CLIENT" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>

    <div v-else>
      <VAlert type="error" variant="tonal">
        Client with ID {{ route.params.id }} not found!
      </VAlert>
    </div>
  </div>
</template>

<script setup>
import moment from 'moment';
import { computed, defineAsyncComponent, getCurrentInstance, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import Information from './tabs/Information.vue';

const route = useRoute();
const InfoData = ref(null);

const dynamicComponents = {
  FollowUp: ref(null),
  SiteVisit: ref(null),
  Quotations: ref(null),
  Invoices: ref(null),
};

const tabs = ref([
  { title: 'Information', slug: 'information', icon: 'tabler-user', action: 'client', subject: 'view' },
]);

const tabConfig = [
  { key: 'FollowUp', slug: 'follow-up', title: 'Follow Up', icon: 'tabler-message', action: 'followUp', subject: 'view' },
  { key: 'SiteVisit', slug: 'site-visit', title: 'Site Visit', icon: 'tabler-map', action: 'siteVisit', subject: 'view' },
  { key: 'Quotations', slug: 'quotations', title: 'Quotations', icon: 'tabler-file', action: 'quotation', subject: 'view' },
  { key: 'Invoices', slug: 'invoices', title: 'Invoices', icon: 'tabler-receipt', action: 'invoice', subject: 'view' },
];

const notificationTab = {
  title: 'Notification Logs',
  slug: 'notification-log',
  icon: 'tabler-bell',
  extraPermissions: [
    { action: "emailLog", subject: "view" },
    { action: "whatsAppLog", subject: "view" },
    { action: "bellNotificationLog", subject: "view" },
  ],
};

const userTab = ref(localStorage.getItem("activeClientView") || "information");

// Load client data
try {
  const { data } = await $api(`/clients/${route.params.id}`);
  InfoData.value = data;
} catch (error) {
  console.error('Failed to fetch client data:', error);
  toast.error(error?.response?.data?.message || 'Failed to load client details.');
}

// Load dynamic components
const components = import.meta.glob('@modules/*/resources/assets/js/list/index.vue');

tabConfig.forEach(config => {
  for (const path in components) {
    if (path.includes(`/${config.key}/`)) {
      dynamicComponents[config.key].value = defineAsyncComponent(components[path]);
      tabs.value.push({
        title: config.title,
        slug: config.slug,
        icon: config.icon,
        action: config.action,
        subject: config.subject,
      });
      break;
    }
  }
});

// Add notification tab once
tabs.value.push(notificationTab);

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;

const filterTabs = computed(() =>
  tabs.value.filter(({ action, subject, extraPermissions }) => {
    if (extraPermissions) {
      return extraPermissions.some(({ action, subject }) => $can?.(action, subject));
    }
    if (!action || !subject) return true;
    return $can?.(action, subject);
  })
);

const showNotificationLogTab = computed(() =>
  notificationTab.extraPermissions.some(({ action, subject }) => $can?.(action, subject))
);

watch(userTab, (newTab) => {
  localStorage.setItem("activeClientView", newTab);
});

onMounted(() => {
  const savedTab = localStorage.getItem("activeClientView");
  userTab.value = filterTabs.value.find(tab => tab.slug === savedTab)?.slug || "information";
});

const makeDateFormat = (date, onlyDate = false) =>
  onlyDate ? moment(date).format('DD-MM-Y') : moment(date).format('LLLL');
</script>
