<template>
  <section>
    <div class="d-flex justify-space-between align-center my-2">
      <!-- Tabs on the left -->
      <VTabs v-model="userTab" class="v-tabs-pill disable-tab-transition">
        <VTab v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
          <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
        </VTab>
      </VTabs>
      <!-- Button on the right -->
      <VBtn color="primary" @click="openCreateNotificationDialog"> Create Notification </VBtn>
    </div>

    <VWindow v-model="userTab" :touch="false">
      <VWindowItem v-for="tab in tabs" :key="tab.slug" :value="tab.slug">
        <EmailUtility v-if="tab.slug === 'email-utility' && $can?.('email', 'view')"
          :listCall="callComponentFunction" />
        <SmsUtility v-if="tab.slug === 'sms-utility' && $can?.('sms', 'view')" :listCall="callComponentFunction" />
        <WhatsAppUtility v-if="tab.slug === 'whatsapp-utility' && $can?.('whatsApp', 'view')"
          :listCall="callComponentFunction" />
        <BellNotificationUtility v-if="tab.slug === 'bell-notification-utility' && $can?.('bellNotification', 'view')"
          :listCall="callComponentFunction" />
        <AppUtility v-if="tab.slug === 'app-utility' && $can?.('appUtility', 'view')"
          :listCall="callComponentFunction" />
      </VWindowItem>
    </VWindow>

    <!-- Create Notification Dialog -->
    <CreateNotification v-if="isDialogVisible" @callToFunction="callToFunction" :module="'Leads'"
      v-model:is-drawer-open="isDialogVisible" />
  </section>
</template>

<script setup>
import { can } from '@layouts/plugins/casl';
import { ref } from 'vue';
import AppUtility from '../app/utility/index.vue';
import BellNotificationUtility from '../bell-notification/utility/index.vue';
import EmailUtility from '../email/Utility/index.vue';
import SmsUtility from '../sms/utility/index.vue';
import WhatsAppUtility from '../whats-app/utility/index.vue';
import CreateNotification from './CreateNotification.vue';
const callComponentFunction = ref(false);
const isDialogVisible = ref(false);

const userTab = ref(localStorage.getItem("activeNotificationUtility") || "email-utility");
const tabs = [
  { title: "Email", slug: "email-utility", icon: "tabler-mail", action: "email", subject: "view", extraPermissions: [] },
  { title: "SMS", slug: "sms-utility", icon: "tabler-message-dots", action: "sms", subject: "view", extraPermissions: [] },
  { title: "WhatsApp Campaign", slug: "whatsapp-utility", icon: "tabler-brand-whatsapp", action: "whatsApp", subject: "view", extraPermissions: [] },
  { title: "Bell Notification", slug: "bell-notification-utility", icon: "tabler-bell", action: "bellNotification", subject: "view", extraPermissions: [] },
  { title: "App", slug: "app-utility", icon: "tabler-device-mobile", action: "appUtility", subject: "view", extraPermissions: [] },
];

const filterTabs = computed(() => {
  if (!can) return tabs;
  return tabs.filter(item => {
    const hasPermission = can(item.action, item.subject);
    const hasExtraPermission = item.extraPermissions?.some(extra => can(item.action, extra));
    return hasPermission || hasExtraPermission;
  });
});

const openCreateNotificationDialog = () => {
  callComponentFunction.value = false;
  isDialogVisible.value = true;
}

const callToFunction = () => {
  callComponentFunction.value = true;
  isDialogVisible.value = false;
}

watch(userTab, (newTab) => {
  localStorage.setItem("activeNotificationUtility", newTab);
});

// Set the initial tab on mount
onMounted(() => {
  const savedTab = localStorage.getItem("activeNotificationUtility");
  if (savedTab && filterTabs.value.some(tab => tab.slug === savedTab)) {
    userTab.value = savedTab;
  } else {
    userTab.value = filterTabs.value[0]?.slug || "email-utility";
  }
});
</script>
