<template>
  <div>
    <BaseSpinner class="d-flex" v-if="loading" />
    <VRow v-else-if="userData">
      <VCol cols="12" md="5" lg="4">
        <UserBioPanel :currentUser="userData" @backCallFunction="getUserData" />
      </VCol>

      <VCol cols="12" md="7" lg="8">
        <div class="d-flex justify-space-between">
          <VTabs v-model="userTab" class="v-tabs-pill">
            <VTab v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
              <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
            </VTab>
          </VTabs>
          <VBtn variant="tonal" color="primary" @click="router.go(-1)">
            <VIcon icon="tabler-arrow-back-up" class="mr-2" />Back
          </VBtn>
        </div>

        <VWindow v-model="userTab" class="mt-6 disable-tab-transition" :touch="false">
          <VWindowItem v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
            <!-- Target List  -->
            <Targets v-if="tab.slug === 'target' && $can('targets', 'view')" />
            <!-- Attendance List  -->
            <Attendance v-if="tab.slug === 'attendance' && $can('userAttendance', 'view')" />
            <!-- User Lead List  -->
            <UserTabLead :type="'Not_Show'" v-if="tab.slug === 'lead' && $can('leads', 'view')" />
            <!-- User Client List -->
            <UserTabClient :type="'Not_Show'" v-if="tab.slug === 'client' && $can('client', 'view')" />
            <!-- User Quotation List -->
            <UserTabQuotation :type="'Not_Show'" v-if="tab.slug === 'quotation' && $can('quotation', 'view')" />
            <!-- User Contract List -->
            <UserTabContract :type="'Not_Show'" v-if="tab.slug === 'contract' && $can('contract', 'view')" />
            <!-- User Invoice List -->
            <UserTabInvoice :type="'Not_Show'" v-if="tab.slug === 'invoice' && $can('invoice', 'view')" />
            <!-- Common Notification Log List -->
            <CommonNotificationLog
              v-if="tab.slug === 'notification-log' && ($can('emailLog', 'view') || $can('whatsAppLog', 'view') || $can('bellNotificationLog', 'view'))"
              :module_id="route.params.id" :log_type="MODULE_USER" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        User with ID {{ route.params.id }} not found!
      </VAlert>
    </div>
  </div>
</template>

<script setup>
import { getFilteredTabs } from "@/utils/common";
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Attendance from "../attendance/list/index.vue";
import Targets from "../targets/list/index.vue";
import UserBioPanel from './UserProfile.vue';

const router = useRouter();
const route = useRoute();

const userData = ref();
const loading = ref(true);

// Load all available module components dynamically
const modules = import.meta.glob('/Modules/**/resources/assets/js/list/index.vue')

const getComponentByModule = (moduleName) => {
  const matchPath = Object.keys(modules).find(path => path.includes(`/Modules/${moduleName}/`));
  return matchPath ? defineAsyncComponent(modules[matchPath]) : null;
}

// Components will be null if not found
const UserTabTarget = getComponentByModule(MODULE_TARGETS)
const UserTabLead = getComponentByModule(MODULE_LEAD)
const UserTabClient = getComponentByModule(MODULE_CLIENT)
const UserTabQuotation = getComponentByModule(MODULE_QUOTATION)
const UserTabContract = getComponentByModule(MODULE_CONTRACT)
const UserTabInvoice = getComponentByModule(MODULE_INVOICE)

const userTab = ref(localStorage.getItem("activeUserView") || "target");
const tabs = [
  {
    icon: 'tabler-target-arrow', // 🎯 Targets
    title: 'Targets',
    slug: 'target',
    action: 'targets',
    subject: 'view',
  },
  {
    icon: 'tabler-calendar-check', // 📅✔️ Attendance
    title: 'Attendance',
    slug: 'attendance',
    action: 'userAttendance',
    subject: 'view',
  },
  {
    icon: 'tabler-user-search', // 🕵️ Leads
    title: 'Leads',
    slug: 'lead',
    action: 'leads',
    subject: 'view',
  },
  {
    icon: 'tabler-user-star', // ⭐ Clients
    title: 'Clients',
    slug: 'client',
    action: 'client',
    subject: 'view',
  },
  {
    icon: 'tabler-file-text', // 📄 Quotations
    title: 'Quotations',
    slug: 'quotation',
    action: 'quotation',
    subject: 'view',
  },
  {
    icon: 'tabler-file-contract', // 📜 Contracts
    title: 'Contracts',
    slug: 'contract',
    action: 'contract',
    subject: 'view',
  },
  {
    icon: 'tabler-file-invoice', // 🧾 Invoices
    title: 'Invoices',
    slug: 'invoice',
    action: 'invoice',
    subject: 'view',
  },
  {
    title: 'Notification Logs', slug: 'notification-log', icon: 'tabler-bell', extraPermissions: [
      { action: "emailLog", subject: "view" },
      { action: "whatsAppLog", subject: "view" },
      { action: "bellNotificationLog", subject: "view" },
    ],
  }
];

// Filter tabs based on permissions
const filteredTabs = computed(() => getFilteredTabs(tabs));

const getUserData = async () => {
  try {
    loading.value = true;
    const response = await $api(`user/${route.params.id}`);
    if (response) {
      userData.value = response.data;
    }
  } catch (error) {
    console.error(error._data.message);
  } finally {
    loading.value = false;
  }
}

watch(userTab, (newTab) => {
  localStorage.setItem("activeUserView", newTab);
});

onMounted(async () => {
  await getUserData();
  const savedTab = localStorage.getItem("activeUserView");
  userTab.value = filteredTabs.value.find(tab => tab.slug === savedTab)?.slug || filteredTabs.value[0]?.slug || "target";
});

</script>
