<template>
  <section v-if="$can('b2b','view') || $can?.('client', 'view') || $can?.('leads', 'view')">
      <VTabs v-model="userTab" class="v-tabs-pill disable-tab-transition my-2">
          <VTab v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
              <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
          </VTab>
      </VTabs>

      <VWindow v-model="userTab" :touch="false">
          <VWindowItem v-for="tab in tabs" :key="tab.slug" :value="tab.slug">
              <BToB v-if="tab.slug === 'b2b-list' && $can?.('b2b', 'view')" />
              <BToC v-if="tab.slug === 'b2c-list' &&( $can?.('client', 'view') || $can?.('leads', 'view'))" />
          </VWindowItem>
      </VWindow>
  </section>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue';
import BToB from '../../b2b/list/index.vue';
import BToC from './B2C.vue';

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;
 
const tabs = [
  { title: "B2B", slug: "b2b-list" , icon: "tabler-users", action: "b2b", subject: "view", extraPermissions: [] },
  { title: "B2C", slug: "b2c-list", icon: "tabler-user", action: "client", subject: "view", extraPermissions: [{action: "leads", subject: "view"},{action: "client", subject: "view"},] },
];

const filterTabs = computed(() => {
  if (!$can) return tabs;
  return tabs.filter(item => {
      const hasPermission = $can(item.action, item.subject);
      const hasExtraPermission = item.extraPermissions?.some(extra => $can(extra.action, extra.subject));
      return hasPermission || hasExtraPermission;
  });
});

const userTab = ref(localStorage.getItem("activeReachOut") || "b2b-list");

watch(userTab, (newTab) => {
  localStorage.setItem("activeReachOut", newTab);
});

// Set the initial tab on mount
onMounted(() => {
  const savedTab = localStorage.getItem("activeReachOut");
  if (savedTab && filterTabs.value.some(tab => tab.slug === savedTab)) {
      userTab.value = savedTab;
  } else {
      userTab.value = filterTabs.value[0]?.slug || "b2b-list";
  }
});
</script>
