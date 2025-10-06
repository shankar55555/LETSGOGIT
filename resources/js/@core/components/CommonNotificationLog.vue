<template>
  <EmailLog v-if="$can('emailLog', 'view')" :module_id="props.module_id" :log_type="props.log_type" />
  <BellNotificationLog v-if="$can('bellNotificationLog', 'view')" :module_id="props.module_id"
    :log_type="props.log_type" />
  <WhatsAppLog v-if="$can('whatsAppLog', 'view')" :module_id="props.module_id" :log_type="props.log_type" />
</template>

<script setup>
import { defineAsyncComponent, defineProps } from 'vue';

const props = defineProps({
  module_id: { type: String, required: true },
  log_type: { type: String, required: true },
});

// Import all module components dynamically
const modules = import.meta.glob('/Modules/AlertAndNotification/resources/assets/js/**/index.vue');

// Helper function to create dynamic imports
const getComponents = (paths) => {
  return Object.fromEntries(
    Object.entries(paths).map(([key, segment]) => {
      const matchPath = Object.keys(modules).find(path => path.includes(segment));
      return [key, matchPath ? defineAsyncComponent(modules[matchPath]) : null];
    })
  );
};

// Dynamically load all components
const { EmailLog, BellNotificationLog, WhatsAppLog, AppLog, SmsLog } = getComponents({
  EmailLog: 'email/list',
  BellNotificationLog: 'bell-notification/log',
  WhatsAppLog: 'whats-app/log',
  AppLog: 'app/log',
  SmsLog: 'sms/log',
});
</script>
