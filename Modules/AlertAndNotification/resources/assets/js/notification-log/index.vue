<template>
  <section>
    <VTabs v-model="userTab" class="v-tabs-pill disable-tab-transition my-2">
      <VTab v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
        <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
      </VTab>
    </VTabs>

    <VWindow v-model="userTab" :touch="false">
      <VWindowItem v-for="tab in tabs" :key="tab.slug" :value="tab.slug">
        <EmailLog v-if="tab.slug === 'email-log' && $can?.('emailLog', 'view')" />
        <SmsLog v-if="tab.slug === 'sms-log' && $can?.('smsLog', 'view')" />
        <WhatsAppLog v-if="tab.slug === 'whatsapp-log' && $can?.('whatsAppLog', 'view')" />
        <BellNotificationLog v-if="tab.slug === 'bell-notification-log' && $can?.('bellNotificationLog', 'view')" />
        <AppLog v-if="tab.slug === 'app-log' && $can?.('appLog', 'view')" />
      </VWindowItem>
    </VWindow>
  </section>
</template>

<script setup>
import { can } from '@layouts/plugins/casl';
import { computed, onMounted, ref, watch } from 'vue';
import AppLog from '../app/log/index.vue';
import BellNotificationLog from '../bell-notification/log/index.vue';
import EmailLog from '../email/list/index.vue';
import SmsLog from '../sms/log/index.vue';
import WhatsAppLog from '../whats-app/log/index.vue';

const tabs = [
  { title: "Email", slug: "email-log", icon: "tabler-mail", action: "emailLog", subject: "view", extraPermissions: [] },
  { title: "SMS", slug: "sms-log", icon: "tabler-message-dots", action: "smsLog", subject: "view", extraPermissions: [] },
  { title: "WhatsApp Campaign", slug: "whatsapp-log", icon: "tabler-brand-whatsapp", action: "whatsAppLog", subject: "view", extraPermissions: [] },
  { title: "Bell Notification", slug: "bell-notification-log", icon: "tabler-bell", action: "bellNotificationLog", subject: "view", extraPermissions: [] },
  { title: "App", slug: "app-log", icon: "tabler-device-mobile", action: "appLog", subject: "view", extraPermissions: [] },
];

const filterTabs = computed(() => {
  return tabs.filter(item => {
    const hasPermission = can(item.action, item.subject);
    const hasExtraPermission = Array.isArray(item.extraPermissions)
      ? item.extraPermissions.some(extra => can(extra.action, extra.subject))
      : false;
    return hasPermission || hasExtraPermission;
  });
});
// console.log(`Checking permissions for ${item.title}: ${hasPermission} or ${hasExtraPermission}`);

const userTab = ref(localStorage.getItem("activeNotificationLog") || "email-log");

watch(userTab, (newTab) => {
  localStorage.setItem("activeNotificationLog", newTab);
});

// Set the initial tab on mount
onMounted(() => {
  const savedTab = localStorage.getItem("activeNotificationLog");
  if (savedTab && filterTabs.value.some(tab => tab.slug === savedTab)) {
    userTab.value = savedTab;
  } else {
    userTab.value = filterTabs.value[0]?.slug || "email-log";
  }
});
</script>
