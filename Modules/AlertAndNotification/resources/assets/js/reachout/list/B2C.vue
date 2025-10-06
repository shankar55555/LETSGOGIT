<template>
  <section v-if="$can?.('leads', 'view') ||  $can?.('client', 'view')">
      <VTabs v-model="userTab" class="v-tabs-pill disable-tab-transition my-2">
          <VTab v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
              <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
          </VTab>
      </VTabs>

      <VWindow v-model="userTab" :touch="false">
          <VWindowItem v-for="tab in tabs" :key="tab.slug" :value="tab.slug">
              <LeadList v-if="tab.slug === 'lead-list' && $can?.('leads', 'view')" />
              <ClientList v-if="tab.slug === 'client-list' && $can?.('client', 'view')" />
          </VWindowItem>
      </VWindow>
  </section>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue';
import ClientList from './ClientList.vue';
import LeadList from './LeadList.vue';

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;

const tabs = [
  { title: "Lead List", slug: "lead-list" , icon: "tabler-users", action: "leads", subject: "view", extraPermissions: [] },
  { title: "Client List", slug: "client-list", icon: "tabler-user", action: "client", subject: "view", extraPermissions: [] },
];

const filterTabs = computed(() => {
  if (!$can) return tabs;
  return tabs.filter(item => {
      const hasPermission = $can(item.action, item.subject);
      const hasExtraPermission = item.extraPermissions?.some(extra => $can(item.action, extra));
      return hasPermission || hasExtraPermission;
  });
});

const userTab = ref(localStorage.getItem("activeB2C") || "lead-list");

watch(userTab, (newTab) => {
  localStorage.setItem("activeB2C", newTab);
});

// Set the initial tab on mount
onMounted(() => {
  const savedTab = localStorage.getItem("activeB2C");
  if (savedTab && filterTabs.value.some(tab => tab.slug === savedTab)) {
      userTab.value = savedTab;
  } else {
      userTab.value = filterTabs.value[0]?.slug || "lead-list";
  }
});
</script>
