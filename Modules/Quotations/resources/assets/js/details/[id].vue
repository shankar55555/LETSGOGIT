<template>
  <div v-if="InfoData">
    <!-- 👉 Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <div class="d-flex">
          <h5 class="text-h5 mb-1 mr-3">
            Quotation Number #{{ InfoData.quotation_number }}
          </h5>
        </div>
        <div class="text-body-1">
          {{ $typeAccordingDateFormatChange(InfoData.created_at, 'full_date_1') }}
        </div>
      </div>

      <div class="d-flex gap-4">
        <!-- Send Message -->
        <VBtn v-if="$can('quotation', 'send-message') && !QUOTATION_NOT_IN.includes(InfoData.status)" variant="tonal"
          color="primary" @click="openDialog" v-tooltip="'Send Message'">
          <VIcon icon="tabler-message" color="primary" />
        </VBtn>

        <VBtn v-if="showGenerateBtn" variant="tonal" color="primary" @click="openGenerateInvoices(InfoData)">
          {{ generateTitle }}
        </VBtn>

        <VBtn variant="tonal" color="secondary" @click="downloadQuotationPdf(InfoData)" :loading="pdfLoading">
          <VIcon icon="tabler-download" class="mr-2" /> Download PDF
        </VBtn>

        <VBtn variant="tonal" color="primary" @click="router.go(-1)">
          <VIcon icon="tabler-arrow-back-up" class="mr-2" /> Back
        </VBtn>
      </div>
    </div>

    <VAlert type="warning" variant="tonal" class="mt-4" v-if="showingPaidWarning">
      Some invoices are already paid. Please cancel them if you want to re-generate.
    </VAlert>

    <VRow>
      <VCol cols="12">
        <!-- Tabs -->
        <VTabs v-model="userTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in filteredTabs" :key="tab.slug" :value="tab.slug">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs>

        <VWindow v-model="userTab" class="disable-tab-transition" :touch="false">
          <VWindowItem value="information">
            <Information :InfoData="InfoData" @status-updated="fetchQuotationData" />
          </VWindowItem>

          <VWindowItem value="invoices">
            <Invoices type="quotations" :id="route.params.id" />
          </VWindowItem>

          <VWindowItem value="notification-log">
            <CommonNotificationLog :module_id="route.params.id" :log_type="MODULE_QUOTATION" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
  </div>
  <div v-else>
    <VAlert type="error" variant="tonal" v-if="!loading">
      Quotation with ID {{ route.params.id }} not found!
    </VAlert>
  </div>

  <GenerateInvoices v-if="drawerInfoData" v-model:is-drawer-open="AddGenerateInvoicesOpen" :currentData="drawerInfoData"
    @submit="onSubmitInvoice" />

  <WhatsAppAndEmailSendMessage v-if="isSendMessageDialogVisible"
    v-model:isSendMessageDialogVisible="isSendMessageDialogVisible" :currentInfo="InfoData" :selectedIdList="[]"
    :type="MODULE_QUOTATION" @submit="clearSendMessageSearchFilter" />
</template>

<script setup>
import { getFilteredTabs } from "@/utils/common";
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';
import GenerateInvoices from '../add/GenerateInvoices.vue';
import Information from './tabs/Information.vue';

// Dynamic import helper
const modules = import.meta.glob('/Modules/**/resources/assets/js/list/index.vue');
const getComponentByModule = (moduleName) => {
  const matchPath = Object.keys(modules).find(path => path.includes(`/Modules/${moduleName}/`));
  return matchPath ? defineAsyncComponent(modules[matchPath]) : null;
};

const Invoices = getComponentByModule(MODULE_INVOICE);

const AddGenerateInvoicesOpen = ref(false);
const drawerInfoData = ref();
const route = useRoute();
const router = useRouter();
const InfoData = ref();
const pdfLoading = ref(false);
const loading = ref(true);

const generateTitle = ref("Generate Invoices");
const showGenerateBtn = ref(true);
const showingPaidWarning = ref(false);
const isSendMessageDialogVisible = ref(false);

const tabs = [
  { title: 'Information', slug: 'information', icon: 'tabler-user', action: "quotation", subject: "view" },
  { title: 'Invoices', slug: 'invoices', icon: 'tabler-file-invoice', action: "invoice", subject: "view" },
  {
    title: 'Notification Logs',
    slug: 'notification-log',
    icon: 'tabler-bell',
    extraPermissions: [
      { action: "emailLog", subject: "view" },
      { action: "whatsAppLog", subject: "view" },
      { action: "bellNotificationLog", subject: "view" },
    ],
  },
];

const userTab = ref(localStorage.getItem("activeQuotationView") || "information");
const filteredTabs = computed(() => getFilteredTabs(tabs));

watch(userTab, (newTab) => {
  localStorage.setItem("activeQuotationView", newTab);
});

const fetchQuotationData = async () => {
  try {
    const { data } = await $api(`/quotations/${route.params.id}`);
    InfoData.value = data;
    loading.value = false;
    checkReGenerate();
  } catch (error) {
    console.error('Failed to fetch quotation data:', error);
    toast.error(error?.response?.data?.message || 'Failed to load quotation details.');
    loading.value = false;
  }
};

const checkReGenerate = () => {
  const invoices = InfoData.value.invoices || [];
  const paidInvoices = invoices.filter(item => item.status === 'paid');
  const unpaidInvoices = invoices.filter(item => !['paid', 'paid-to-cancelled'].includes(item.status));

  generateTitle.value = paidInvoices.length || unpaidInvoices.length ? "Re-Generate Invoices" : "Generate Invoices";
  showGenerateBtn.value = unpaidInvoices.length > 0 || (!paidInvoices.length && !unpaidInvoices.length);

  if (!InfoData.value.items.length) {
    showGenerateBtn.value = false;
  }
};

const openGenerateInvoices = (item) => {
  showingPaidWarning.value = false;
  if (InfoData.value.invoices.some(item => item.status === 'paid')) {
    showingPaidWarning.value = true;
    return;
  }
  AddGenerateInvoicesOpen.value = true;
  drawerInfoData.value = item;
};

const downloadQuotationPdf = async (quotation) => {
  try {
    pdfLoading.value = true;
    const response = await $api(`/quotations/${quotation.id}/pdf`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `quotation_${quotation.quotation_number}.pdf`);
    document.body.appendChild(link);
    link.click();
    setTimeout(() => {
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    }, 100);
  } catch (error) {
    console.error("Error downloading quotation PDF:", error);
    toast.error(error?.response?.data?.message || "Failed to download quotation PDF.");
  } finally {
    pdfLoading.value = false;
  }
};

const onSubmitInvoice = () => {
  if (InfoData.value.client_id) {
    fetchQuotationData();
  } else {
    router.push({ name: 'clients-list' });
  }
};

const openDialog = () => { isSendMessageDialogVisible.value = true; };
const clearSendMessageSearchFilter = () => { isSendMessageDialogVisible.value = false; fetchQuotationData(); };

onMounted(() => {
  fetchQuotationData();
  const savedTab = localStorage.getItem("activeQuotationView");
  userTab.value = filteredTabs.value.find(tab => tab.slug === savedTab)?.slug || filteredTabs.value[0]?.slug || "information";
});
</script>
