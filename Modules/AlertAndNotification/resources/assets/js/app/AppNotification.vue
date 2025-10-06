<!-- WhatsAppNotification.vue -->
<template>
  <IconBtn id="notification-btn" v-if="$can('appLog', 'view')">
    <VTooltip bottom>
      <template v-slot:activator="{ on, attrs }">
        <VBadge v-bind="on" color="error" :content="unreadCount" offset-x="2" offset-y="3">
          <VIcon size="24" icon="tabler-device-mobile" />
        </VBadge>
      </template>
      <span>App Notifications</span>
    </VTooltip>

    <VMenu ref="notificationMenu" activator="parent" min-width="300px" location="bottom end" offset="8">
      <VCard class="d-flex flex-column">
        <!-- Header -->
        <VCardItem class="notification-section">
          <VCardTitle class="text-h6">Notifications</VCardTitle>

          <template #append>
            <VChip v-show="unreadCount > 0" size="small" color="primary" class="me-2"> {{ unreadCount }} New </VChip>
            <IconBtn v-show="notification_list.length" size="34" @click="markAllReadOrUnread()">
              <VIcon size="20" color="high-emphasis" :icon="unreadCount > 0 ? 'tabler-mail' : 'tabler-mail-opened'" />
              <VTooltip activator="parent" location="start">
                {{ unreadCount == 0 ? 'Mark all as unread' : 'Mark all as read' }} </VTooltip>
            </IconBtn>
          </template>
        </VCardItem>

        <VDivider />

        <!-- Notifications List -->
        <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-block-size: 380px;">
          <VList class="notification-list rounded-0 py-0">
            <template v-for="(notification, index) in notification_list" :key="notification.id">
              <VDivider v-if="index > 0" />

              <VListItem link lines="one" min-height="60" class="list-item-hover-class"
                @click="handleItemClick(notification)">
                <div class="d-flex align-start gap-3 w-100">
                  <!-- Avatar -->
                  <VAvatar size="40"
                    :image="notification.sender && notification.sender.avatar ? notification.sender.avatar : undefined"
                    :icon="notification.icon || undefined"
                    :variant="notification.sender && notification.sender.avatar ? undefined : 'tonal'">
                    <span v-if="notification.sender">{{ avatarText(notification.sender.name) }}</span>
                  </VAvatar>

                  <!-- Notification Content -->
                  <div class="flex-grow-1">
                    <!-- Subject -->
                    <VListItemTitle class="text-sm font-weight-medium">
                      {{ notification.subject }}
                    </VListItemTitle>

                    <!-- HTML Content -->
                    <VListItemSubtitle class="text-body-2">
                      <p class="mb-0 d-flex flex-wrap">{{ changedContent(notification.content) }}</p>
                    </VListItemSubtitle>

                    <!-- Sender Info -->
                    <VListItemSubtitle class="text-body-2">
                      Created By: {{ notification.sender?.name ?? 'Unknown' }}
                    </VListItemSubtitle>

                    <!-- Time Ago -->
                    <VListItemSubtitle class="text-sm text-disabled">
                      {{ $typeAccordingDateFormatChange(notification.created_at, 'time_ago') }}
                    </VListItemSubtitle>
                  </div>

                  <!-- Status and Delete Icons -->
                  <VIcon size="10" icon="tabler-circle-filled"
                    :color="!notification.is_read_by_user ? 'primary' : '#a8aaae'" class="mb-2"
                    @click.stop="toggleReadUnread(notification.is_read_by_user, notification.id)" />

                  <VIcon v-if="$can('smsLog', 'delete')" size="20" icon="tabler-x" class="visible-in-hover"
                    @click.stop="removeMoveNotification(notification.id)" />
                </div>
              </VListItem>
            </template>

            <VListItem v-show="!notification_list.length" class="text-center text-medium-emphasis"
              style="block-size: 56px;">
              <VListItemTitle>No Notification Found!</VListItemTitle>
            </VListItem>
          </VList>
        </PerfectScrollbar>

        <VDivider />

        <!-- Footer -->
        <VCardText v-show="(notification_list.length || unreadCount > 0) && $can('appLog', 'view')" class="pa-4">
          <RouterLink v-if="route.name != 'notification-logs'" to="/notification-logs">
            <VBtn block size="small"> View All Notifications </VBtn>
          </RouterLink>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<script setup>
import echo from '@/echo.js';
import { goToPage } from "@/utils/common";
import { getCurrentInstance, onMounted } from "vue";
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import { toast } from "vue3-toastify";
const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;

import { useRoute, useRouter } from 'vue-router';
const router = useRouter();
const route = useRoute();
const props = defineProps({
  count: { type: Number, default: 0 },
  list: { type: Array, default: () => [] },
});
const unreadCount = ref(0);
const loading = ref(false);
const notificationMenu = ref(null);

const notification_count = ref(null);
const getNotificationCount = async () => {
  try {
    const response = await $api('/app/notification-count');
    notification_count.value = response.data ?? null;
    unreadCount.value = notification_count.value ? notification_count.value.un_read : 0;
  } catch (error) {
    let errorMessage = error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  }
};

const changedContent = (subject) => {
  return subject.replace(/<br\s*\/?>/gi, '').replace(/amp;/g, '');
}

const notification_list = ref([]);
const getLatestFiveNotificationList = async () => {
  try {
    loading.value = true;
    const response = await $api('/app/latest-five-notification-list');
    notification_list.value = response.data ?? [];
  } catch (error) {
    let errorMessage = error?._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

const markAllReadOrUnread = async () => {
  try {
    let is_read = true;
    if (unreadCount.value == 0) { is_read = false; }
    const response = await $api('/app/mark-all-read-or-un-read', { method: 'POST', body: { is_read: is_read } });
    toast.success(response.message);
    await getNotificationCount(),
      await getLatestFiveNotificationList();
  } catch (error) {
    let errorMessage = error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  }
}

const handleItemClick = async (item) => {
  if (!item.is_read_by_user) {
    await isReadNotification(item.id, true);
  }

  const typeMap = TYPE_MAP_NOTIFICATION_LIST;
  const type = typeMap.find(t => item[t]);

  const url = goToPage(item, type);
  if (url) {
    router.push(url);
  } else {
    // toast.error("You do not have permission to view this page.");
  }
};

const toggleReadUnread = async (is_read_by_user, notification_id) => {
  if (is_read_by_user) {
    await isReadNotification(notification_id, false);
  } else {
    await isReadNotification(notification_id, true);
  }
}

const isReadNotification = async (notification_id, is_read) => {
  try {
    const response = await $api('/app/is-read-notification', { method: 'POST', body: { notification_id: notification_id, is_read: is_read } });
    toast.success(response.message);
    await getNotificationCount(),
      await getLatestFiveNotificationList();
  } catch (error) {
    let errorMessage = error._data.message ?? "Error occurred while processing the request.";
    toast.error(errorMessage);
  }
}

const removeMoveNotification = async (notification_id) => {
  notification_list.value.forEach((item, index) => { if (notification_id === item.id) notification_list.value.splice(index, 1) });
}
// Watch props changes
watch(() => props.count, (newCount) => { unreadCount.value = newCount ?? 0; });
watch(() => props.list, (newList) => { notification_list.value = newList ?? []; });

onMounted(async () => {
  const userId = localStorage.getItem('user_id') ?? null;
  echo.channel(`notification-app-channel-${userId}`).listen(".new-notification", (data) => {
    console.log('notification-app-channel ', JSON.stringify(data));
    // if (data.message) toast.info(data.message);
    getNotificationCount();
    getLatestFiveNotificationList();
  });
});
</script>

<style lang="scss">
.notification-section {
  padding-block: 0.75rem;
  padding-inline: 1rem;
}

.list-item-hover-class {
  .visible-in-hover {
    display: none;
  }

  &:hover {
    .visible-in-hover {
      display: block;
    }
  }
}

.notification-list.v-list {
  .v-list-item {
    border-radius: 0 !important;
    margin: 0 !important;
    padding-block: 0.75rem !important;
  }
}

.notification-badge {
  .v-badge__badge {
    padding: 0;
    block-size: 18px;
    min-inline-size: 18px;
  }
}
</style>
