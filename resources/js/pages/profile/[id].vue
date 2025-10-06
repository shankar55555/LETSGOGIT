<template>
  <div>
    <BaseSpinner v-if="loading" class="d-flex" />
    <template v-else-if="userData">
      <VRow>
        <VCol cols="12" md="5" lg="4" v-if="$can('profile', 'view')">
          <UserBioPanel :currentUser="userData" @backCallFunction="getUserData" />
        </VCol>
        <VCol v-if="$can('profile', 'change-password') || $can('loginLog', 'view')" cols="12" :md="$can('profile', 'view') ? '7' : '12'" :lg="$can('profile', 'view') ? '8' : '12'">
          <div class="d-flex justify-space-between">
            <VTabs v-model="userTab" class="v-tabs-pill disable-tab-transition my-2">
              <VTab v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
                <VIcon size="20" start :icon="tab.icon" /> {{ tab.title }}
              </VTab>
            </VTabs>
          </div>
          <VWindow v-model="userTab" :touch="false">
            <VWindowItem v-for="tab in filterTabs" :key="tab.slug" :value="tab.slug">
              <UserTabSecurity v-if="tab.slug === 'security' && ($can('profile', 'change-password') || $can('loginLog', 'view'))" />
              <Attendance v-if="tab.slug === 'attendance'  && $can('userAttendance', 'view')" :userInfo="userData" />
              <Targets v-if="tab.slug === 'targets' && $can('targets', 'view')" :userInfo="userData" />
            </VWindowItem>
          </VWindow>
        </VCol>
      </VRow>
    </template>

    <VAlert v-else type="error" variant="tonal">
      User with ID {{ route.params.id }} not found!
    </VAlert>
  </div>
</template>

<script setup>
import { can } from '@layouts/plugins/casl';
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import Attendance from '../user/attendance/list/index.vue';
import Targets from '../user/targets/list/index.vue';
import UserBioPanel from "../user/view/UserProfile.vue";
import UserTabSecurity from './UserTabSecurity.vue';
const route = useRoute();

const userData = ref(null);
const loading = ref(true);
const tabs = [
  {
    icon: "tabler-lock",
    title: "Security",
    slug: "security",
    extraPermissions: [{ action : "profile", subject : "change-password"} ,{ action : "loginLog", subject : "view"}],
  },
  {
    icon: "tabler-target-arrow",
    title: "Targets",
    slug: "targets",
    extraPermissions: [{ action : "targets", subject : "view"}],
  },
  {
    icon: "tabler-calendar-check",
    title: "Attendance",
    slug: "attendance",
    extraPermissions: [{ action : "userAttendance", subject : "view"}],
  },
];

const filterTabs = computed(() =>
  tabs.filter(({ slug , extraPermissions }) => {
    if( slug != "security" && userData.value.isAdmin && userData.value.mark_attendance == false){
      return false;
    }
    const extra = extraPermissions?.some(extra => can(extra.action, extra.subject));
    return extra;
  })
);

const userTab = ref(localStorage.getItem("activeProfile") || "security");
const getUserData = async () => {
  loading.value = true;
  try {
    const { data } = await $api(`user/${route.params.id}`);
    userData.value = data;
  } catch (error) {
    console.error(error._data?.message || error.message || "Error fetching user data");
  } finally {
    loading.value = false;
  }
};

watch(userTab, (newTab) => {
  localStorage.setItem("activeProfile", newTab);
});

onMounted(async () => {
  await getUserData();
  const savedTab = localStorage.getItem("activeProfile");
  userTab.value = filterTabs.value.some(tab => tab.slug === savedTab) ? savedTab : filterTabs.value[0]?.slug || "security";
});
</script>
