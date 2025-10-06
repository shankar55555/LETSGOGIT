<template>
  <VRow>
    <VCol cols="3">
      <VTabs v-model="activeTab" direction="vertical" class="v-tabs-pill disable-tab-transition">
        <VTab v-for="(tab, index) in filterTabs" :key="index" :prepend-icon="tab.icon">
          {{ tab.title }}
        </VTab>
      </VTabs>
    </VCol>

    <VCol cols="12" md="9">
      <VWindow v-model="activeTab" class="disable-tab-transition" :touch="false">
        <VWindowItem v-for="(tab, index) in tabs" :key="index">
          <component :is="tab.component" />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>
</template>

<script setup>
import { can } from '@layouts/plugins/casl';
import { computed, ref } from "vue";
import Setting from "./Setting.vue";
import Status from "./Status.vue";
import ToolChecklist from "./ToolChecklist.vue";
import preview from "./preview.vue";

// Tab configuration
const tabs = [
  {
    icon: 'tabler-settings',
    title: 'General Settings',
    slug: 'general-setting',
    component: Setting,
    action: 'generalSetting',
    subject: 'view'
  },
  {
    icon: 'tabler-list-details',
    title: 'Status Details',
    slug: 'status-detail',
    component: Status,
    action: 'status',
    subject: 'view'
  },
  {
    icon: 'tabler-tool',
    title: 'Tools Checklist',
    slug: 'tools-checklist',
    component: ToolChecklist,
    action: 'generalSetting',
    subject: 'view'
  },
  {
    icon: "tabler-file-text",
    title: "Terms & Conditions",
    component: preview,
    action: 'generalSetting',
    subject: 'view'
  },
]

const activeTab = ref(0)
const filterTabs = computed(() => {
  return tabs.filter((item) => {
    const hasPermission = can?.(item.action, item.subject);
    const hasExtraPermission = item.extraPermissions?.some((extra) => can?.(item.action, extra));
    return hasPermission || hasExtraPermission;
  });
});
</script>

<style lang="scss">
.my-class {
  padding: 1.25rem;
  border-radius: 0.375rem;
  background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
}
</style>
