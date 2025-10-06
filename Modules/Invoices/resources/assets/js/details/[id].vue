<template>
  <div v-if="InfoData">
    <!-- 👉 Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h5 class="text-h5 mb-1">Invoice Number #{{ InfoData.invoice_number }}</h5>
        <div class="text-body-1">{{ formatDate(InfoData.created_at) }}</div>
      </div>

      <div class="d-flex gap-4">
        <VBtn v-if="showRecordPayment" variant="tonal" color="primary" @click="IsPayInvoiceDrawerOpen = true">
          Record Payment
        </VBtn>

        <VBtn v-if="$can('invoice', 'send-message') && InfoData.status !== 'draft'" variant="tonal" color="primary"
          @click="isSendMessageDialogVisible = true" v-tooltip="'Send Message'">
          <VIcon icon="tabler-message" color="primary" />
        </VBtn>

        <VBtn v-if="InfoData.status !== 'draft'" variant="tonal" color="secondary" @click="downloadInvoicePdf"
          :loading="pdfLoading">
          <VIcon icon="tabler-download" class="mr-2" />
          Download PDF
        </VBtn>

        <VBtn v-if="InfoData.status === 'paid'" variant="tonal" color="warning" @click="isCancelledDialogOpen = true"
          :loading="cancelledLoading">
          <VIcon icon="tabler-cancel" class="mr-2" />
          Cancelled
        </VBtn>

        <VBtn variant="tonal" color="primary" @click="router.go(-1)">
          <VIcon icon="tabler-arrow-back-up" class="mr-2" />Back
        </VBtn>
      </div>
    </div>

    <!-- Tabs -->
    <VRow>
      <VCol cols="12">
        <VTabs v-model="userTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs>

        <VWindow v-model="userTab" class="disable-tab-transition" :touch="false">
          <VWindowItem value="information">
            <Information :InfoData="InfoData" />
          </VWindowItem>

          <VWindowItem value="notification-log">
            <CommonNotificationLog :module_id="route.params.id" :log_type="MODULE_INVOICE" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>

    <!-- 👉 Confirm Dialog -->
    <CancelledDialog v-model:isDialogVisible="isCancelledDialogOpen" confirm-title="Cancelled!"
      confirmation-question="Are you sure you want to cancel the invoice?" :currentItem="InfoData"
      :endpoint="`/invoices/${InfoData?.id}/cancel`" @submit="refresh" />

    <!-- Record Payment Drawer -->
    <PayInvoice v-model:is-drawer-open="IsPayInvoiceDrawerOpen" :currentData="InfoData" @submit="refresh" />

    <!-- Send Message Dialog -->
    <WhatsAppAndEmailSendMessage v-if="isSendMessageDialogVisible"
      v-model:isSendMessageDialogVisible="isSendMessageDialogVisible" :currentInfo="InfoData" :selectedIdList="[]"
      :type="MODULE_INVOICE" @submit="clearSendMessageSearchFilter" />
  </div>
  <!-- No data fallback -->
  <div v-else>
    <VAlert type="error" variant="tonal">
      Invoice with ID {{ route.params.id }} not found!
    </VAlert>
  </div>
</template>

<script setup>
import { getFilteredTabs } from "@/utils/common";
import moment from 'moment';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';

import PayInvoice from '../add/PayInvoice.vue';
import CancelledDialog from '../dialog/CancelledDialog.vue';
import Information from './tabs/Information.vue';

const route = useRoute();
const router = useRouter();

const InfoData = ref(null);
const pdfLoading = ref(false);
const isSendMessageDialogVisible = ref(false);
const isCancelledDialogOpen = ref(false);
const IsPayInvoiceDrawerOpen = ref(false);
const cancelledLoading = ref(false);

const userTab = ref(localStorage.getItem("activeInvoiceView") || "information");

const tabs = [
  { title: 'Information', slug: 'information', icon: 'tabler-user' },
  {
    title: 'Notification Logs',
    slug: 'notification-log',
    icon: 'tabler-bell',
    extraPermissions: [
      { action: "emailLog", subject: "view" },
      { action: "whatsAppLog", subject: "view" },
      { action: "bellNotificationLog", subject: "view" },
    ],
  }
];

const showRecordPayment = computed(() =>
  InfoData.value &&
  !['paid', 'draft', 'paid-to-cancelled'].includes(InfoData.value.status) &&
  InfoData.value.total !== 0
);

const formatDate = (date, onlyDate = false) =>
  onlyDate ? moment(date).format('DD-MM-Y') : moment(date).format('LLLL');

const fetchInvoiceData = async () => {
  try {
    const { data } = await $api(`/invoices/${route.params.id}`);
    InfoData.value = data;
  } catch (error) {
    console.error('Failed to fetch invoice data:', error);
    toast.error(error?.response?.data?.message || 'Failed to load invoice details.');
  }
};

const refresh = () => fetchInvoiceData();

const downloadInvoicePdf = async () => {
  try {
    pdfLoading.value = true;
    const response = await $api(`/invoice/${InfoData.value.id}/pdf`, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `invoice_${InfoData.value.invoice_number}.pdf`);
    document.body.appendChild(link);
    link.click();
    setTimeout(() => {
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    }, 100);
  } catch (error) {
    console.error('Error downloading invoice PDF:', error);
    toast.error(error?.response?.data?.message || 'Failed to download invoice PDF.');
  } finally {
    pdfLoading.value = false;
  }
};

const clearSendMessageSearchFilter = () => {
  isSendMessageDialogVisible.value = false;
  refresh();
};

const filteredTabs = computed(() => getFilteredTabs(tabs));

watch(userTab, (newTab) => {
  localStorage.setItem("activeInvoiceView", newTab);
});

onMounted(() => {
  fetchInvoiceData();
  const savedTab = localStorage.getItem("activeInvoiceView");
  userTab.value =
    filteredTabs.value.find(tab => tab.slug === savedTab)?.slug ||
    filteredTabs.value[0]?.slug ||
    "information";
});
</script>
