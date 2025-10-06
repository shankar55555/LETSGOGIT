<script setup>
import { can } from '@layouts/plugins/casl';
import { computed } from "vue";

const shortcuts = [
  {
    icon: 'tabler-calendar',
    title: 'Calendar',
    subtitle: 'Schedule List',
    to: { name: 'apps-calendar' },
    action: 'calendar', subject: 'view',
    extraPermissions: []
  },
  {
    title: 'Invoices',
    icon: 'tabler-file-invoice',
    subtitle: 'Manage Invoices',
    to: { name: 'invoice-list' },
    action: 'invoice', subject: 'view',
    extraPermissions: []
  },
  {
    title: 'User List',
    icon: 'tabler-users',
    subtitle: 'Manage Users',
    to: { name: 'user-list' },
    action: 'user', subject: 'view',
    extraPermissions: []
  },
  {
    title: 'Role and Permission',
    icon: 'tabler-shield-lock',
    subtitle: 'Permission',
    to: { name: 'role-list' },
    action: 'role', subject: 'view',
    extraPermissions: []
  },
  {
    title: 'Dashboard',
    icon: 'tabler-smart-home',
    subtitle: 'Dashboard Analytics',
    to: { name: 'dashboard' },
    action: 'dashboard', subject: 'view',
    extraPermissions: []
  },
  {
    title: 'Settings',
    icon: 'tabler-settings',
    subtitle: 'Account Settings',
    to: { name: 'settings' },
    action: "generalSetting", subject: "view",
    extraPermissions: [{ action: "status", subject: "view" }]
  },
];

const filteredTabs = computed(() => {
  return shortcuts.filter(item => {
    const hasPermission = can(item.action, item.subject);
    const hasExtraPermission = Array.isArray(item.extraPermissions)
      ? item.extraPermissions.some(extra => can(extra.action, extra.subject))
      : false;
    return hasPermission || hasExtraPermission;
  });
});
// console.log(`Checking permissions for ${item.title}: ${hasPermission} or ${hasExtraPermission}`);

</script>

<template>
  <Shortcuts :shortcuts="filteredTabs" />
</template>
