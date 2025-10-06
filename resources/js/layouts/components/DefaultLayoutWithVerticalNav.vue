<script setup>
import navItems from "@/navigation/vertical";
import { onMounted } from "vue";
import { toast } from "vue3-toastify";
// Components
import Footer from "@/layouts/components/Footer.vue";
import NavSearchBar from "@/layouts/components/NavSearchBar.vue";
import NavbarShortcuts from '@/layouts/components/NavbarShortcuts.vue';
import NavbarThemeSwitcher from "@/layouts/components/NavbarThemeSwitcher.vue";

import { VerticalNavLayout } from "@layouts";
import { defineAsyncComponent } from "vue";
import { useRoute } from "vue-router";
import UserProfile from "../../pages/profile/UserProfile.vue";
const todayUserWork = ref();
const route = useRoute();
const isDialogVisible = ref(false);
const userData = ref(null);

// Dynamic import for all notification module components
const modules = import.meta.glob("/Modules/**/resources/assets/js/**/*.vue");

// Safe dynamic component resolver
const getNotificationComponent = (moduleName, folderName, fileName = null) => {
  const targetFile =
    fileName ||
    `${folderName.charAt(0).toUpperCase()}${folderName.slice(
      1
    )}Notification.vue`;
  const matchPath = Object.keys(modules).find((path) =>
    path.includes(
      `/Modules/${moduleName}/resources/assets/js/${folderName}/${targetFile}`
    )
  );
  return matchPath ? defineAsyncComponent(modules[matchPath]) : null;
};

// Optional Notification Components
const EmailNotification = getNotificationComponent(
  MODULE_ALERT_AND_NOTIFICATION,
  "email"
);
const BellNotification = getNotificationComponent(
  MODULE_ALERT_AND_NOTIFICATION,
  "bell-notification",
  "BellNotification.vue"
);
const WhatsAppNotification = getNotificationComponent(
  MODULE_ALERT_AND_NOTIFICATION,
  "whats-app",
  "WhatsAppNotification.vue"
);
const SmsNotification = getNotificationComponent(
  MODULE_ALERT_AND_NOTIFICATION,
  "sms"
);
const AppNotification = getNotificationComponent(
  MODULE_ALERT_AND_NOTIFICATION,
  "app"
);

const notification = ref(null);
const allNotificationCount = async () => {
  try {
    const response = await $api("/all-notification-count?type=count");
    notification.value = response.data ?? null;
    // console.log("allNotificationCount ", JSON.stringify(notification.value));
  } catch (error) {
    let errorMessage =
      error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  }
};

const notification_list = ref([]);
const allNotificationLatestFiveList = async () => {
  try {
    const response = await $api("/all-notification-latest-five-list?type=list");
    notification_list.value = response.data ?? [];
    notification_list.value.bell_list = [
      {
        subject: "asdfghjkl",
        content: 'abc gerfh mhbjhg  duhfdksuyfskmbn dsfdsf dsgfdsf hgdfh efeftergdf dwstdwgfds dsgfdsgf'
      },
      {
        subject: "asdfghjkl",
        content: 'abc gerfh mhbjhg  duhfdksuyfskmbn dsfdsf dsgfdsf hgdfh efeftergdf dwstdwgfds dsgfdsgf'
      }
    ]
    // console.log("allNotificationLatestFiveList ", JSON.stringify(notification_list.value));
  } catch (error) {
    let errorMessage =
      error?._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  } finally {
  }
};

const updateShiftOutTime = async () => {
  try {
    const userId = localStorage.getItem("user_id") ?? null;
    const today = new Date().toISOString().split("T")[0];

    const payload = {
      attendance_date: today,
      work: todayUserWork.value ?? "",
      timeDuration: "eveningTime",
      user_id: userId,
    };

    const res = await $api("/updateShift-time", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (res?.data) {
      toast.success(res.data.message || "Attendance Updated successfully");
      todayUserWork.value = "";
      isDialogVisible.value = false;

      setTimeout(() => {
        window.location.reload();
      }, 500);
    }
  } catch (err) {
    console.error(err);
    toast.error(err?._data?.message || "Failed to update attendance");
  }
};

const hasClosedToday = ref(null);
const hasMarkedAttendance = ref(null);

const checkAttendance = async () => {
  try {
    const response = await $api("/check-todays-attendance");

    hasClosedToday.value = response.has_closed_attendance;
    hasMarkedAttendance.value = response.has_marked_attendance;
  } catch (error) {
    // console.error('Error checking attendance:', error);
  }
};

const userId = ref(localStorage.getItem("user_id"));

const getUserData = async () => {
  try {
    const { data } = await $api(`user/${userId.value}`);
    userData.value = data ?? [];
  } catch (error) {
    console.error(
      error._data?.message || error.message || "Error fetching user data"
    );
  }
};

const formatNotificationCount = (count) => {
  return count > 99 ? '99+' : count;
};

onMounted(async () => {
  allNotificationCount();
  allNotificationLatestFiveList();
  checkAttendance();
  getUserData();
});
</script>

<template>
  <VerticalNavLayout :nav-items="navItems">
    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="(isDialogVisible = !isDialogVisible), (todayUserWork = '')" />

      <!-- Dialog Content -->
      <VCard title="Write What have you done today ?">
        <VCardText>
          <AppTextarea label="Work Description" v-model="todayUserWork" placeholder="Add your work" class="mt-3 ml-3" />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn @click="updateShiftOutTime()"> Submit </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <IconBtn id="vertical-nav-toggle-btn" class="ms-n3 d-lg-none" @click="toggleVerticalOverlayNavActive(true)">
          <VIcon size="26" icon="tabler-menu-2" />
        </IconBtn>
        <NavSearchBar class="ms-lg-n3" />
        <VSpacer />

        <!-- <NavBarI18n v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
          :languages="themeConfig.app.i18n.langConfig" /> -->
        <NavbarThemeSwitcher />
        <NavbarShortcuts />

        <div v-if="
          hasMarkedAttendance == true &&
          hasClosedToday == false &&
          userData?.mark_attendance == true
        ">
          <VTooltip text="Mark shift as completed" location="top">
            <template #activator="{ props }">
              <IconBtn v-bind="props" @click="isDialogVisible = true">
                <VIcon size="26" icon="tabler-fingerprint" />
              </IconBtn>
            </template>
          </VTooltip>
        </div>

        <!-- Conditionally render each notification component only if it exists -->
        <EmailNotification v-if="$can('emailLog', 'view')" class="ml-2"
          :count="formatNotificationCount(notification?.un_read_email_count)" :list="notification_list?.email_list" />

        <BellNotification v-if="$can('bellNotificationLog', 'view')" class="ml-2"
          :count="formatNotificationCount(notification?.un_read_bell_count)" :list="notification_list?.bell_list" />

        <!-- <WhatsAppNotification v-if="$can('whatsAppLog', 'view')"
          :count="formatNotificationCount(notification?.un_read_whatsapp_count)"
          :list="notification_list?.whatsapp_list" /> -->

        <SmsNotification v-if="$can('smsLog', 'view')" :count="formatNotificationCount(notification?.un_read_sms_count)"
          class="ml-2" :list="notification_list?.sms_list" />

        <AppNotification v-if="$can('appLog', 'view')" :count="formatNotificationCount(notification?.un_read_app_count)"
          :list="notification_list?.app_list" class="ml-2" />

        <UserProfile />
      </div>
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>

    <!-- 👉 Customizer -->
    <TheCustomizer v-if="route.name == 'dashboard'" />
  </VerticalNavLayout>
</template>
