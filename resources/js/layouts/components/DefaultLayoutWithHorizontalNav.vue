<script setup>
import navItems from '@/navigation/horizontal'
import { themeConfig } from '@themeConfig'
import { onMounted } from 'vue'
// Components
import Footer from '@/layouts/components/Footer.vue'

import NavSearchBar from '@/layouts/components/NavSearchBar.vue'
import NavbarShortcuts from '@/layouts/components/NavbarShortcuts.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import { HorizontalNavLayout } from '@layouts'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { useRoute } from 'vue-router'
import UserProfile from '../../pages/profile/UserProfile.vue'
const route = useRoute()

const modules = import.meta.glob('/Modules/**/resources/assets/js/**/*.vue')

const getNotificationComponent = (moduleName, folderName, fileName = null) => {
  const targetFile = fileName || `${folderName.charAt(0).toUpperCase()}${folderName.slice(1)}Notification.vue`
  const matchPath = Object.keys(modules).find(path =>
    path.includes(`/Modules/${moduleName}/resources/assets/js/${folderName}/${targetFile}`)
  )
  return matchPath ? defineAsyncComponent(modules[matchPath]) : null
}

const EmailNotification = getNotificationComponent(MODULE_ALERT_AND_NOTIFICATION, 'email')
const BellNotification = getNotificationComponent(MODULE_ALERT_AND_NOTIFICATION, 'bell-notification', 'BellNotification.vue')
const WhatsAppNotification = getNotificationComponent(MODULE_ALERT_AND_NOTIFICATION, 'whats-app', 'WhatsAppNotification.vue')
const SmsNotification = getNotificationComponent(MODULE_ALERT_AND_NOTIFICATION, 'sms')
const AppNotification = getNotificationComponent(MODULE_ALERT_AND_NOTIFICATION, 'app')

const notification = ref(null);
const allNotificationCount = async () => {
  try {
    const response = await $api('/all-notification-count?type=count');
    notification.value = response.data ?? null;
  } catch (error) {
    let errorMessage = error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  }
};

const notification_list = ref([]);
const allNotificationLatestFiveList = async () => {
  try {
    const response = await $api('/all-notification-latest-five-list?type=list');
    notification_list.value = response.data ?? [];
  } catch (error) {
    let errorMessage = error?._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  } finally {
  }
};

onMounted(async () => {
  allNotificationCount();
  allNotificationLatestFiveList();
})
</script>

<template>
  <HorizontalNavLayout :nav-items="navItems">
    <!-- 👉 navbar -->
    <template #navbar>
      <RouterLink to="/" class="app-logo d-flex align-center gap-x-3">
        <VNodeRenderer :nodes="themeConfig.app.logo" />

        <h1 class="app-title font-weight-bold leading-normal text-xl text-capitalize">
          {{ themeConfig.app.title }}
        </h1>
      </RouterLink>
      <VSpacer />

      <NavSearchBar trigger-btn-class="ms-lg-n3" />
<!-- 
      <NavBarI18n v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
        :languages="themeConfig.app.i18n.langConfig" /> -->

      <NavbarThemeSwitcher />
      <NavbarShortcuts />
      <!-- Conditionally render each notification component only if it exists -->
      <EmailNotification v-if="$can('emailLog', 'view')" :count="notification?.un_read_email_count"
        :list="notification_list?.email_list" />

      <BellNotification v-if="$can('bellNotificationLog', 'view')" :count="notification?.un_read_bell_count"
        :list="notification_list?.bell_list" />

      <WhatsAppNotification v-if="$can('whatsAppLog', 'view')" :count="notification?.un_read_whatsapp_count"
        :list="notification_list?.whatsapp_list" />

      <SmsNotification v-if="$can('smsLog', 'view')" :count="notification?.un_read_sms_count"
        :list="notification_list?.sms_list" />

      <AppNotification v-if="$can('appLog', 'view')" :count="notification?.un_read_app_count"
        :list="notification_list?.app_list" />
      <UserProfile />

    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>

    <!-- 👉 Customizer -->
    <TheCustomizer v-if="route.name == 'dashboard'" />
  </HorizontalNavLayout>
</template>
