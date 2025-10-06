<template>
  <VContainer>
    <VCard class="flex-grow-1 d-flex flex-column">
      <VCardItem>
        <div class="d-flex justify-space-between align-center w-100">
          <div class="d-flex align-center">
            <VIcon icon="tabler-phone-call" class="me-2" />
            <h6 class="text-h6 mb-0">Follow Ups</h6>
          </div>
          <VBtn color="primary" prepend-icon="tabler-plus" @click="handleAddFollowup()">
            Add
          </VBtn>
        </div>
      </VCardItem>
      <VDivider />
      <VCardText>
        <VProgressLinear v-if="loading" indeterminate color="primary"></VProgressLinear>
        <div v-else class="d-flex flex-column gap-4">
          <template v-if="followups.length > 0">
            <template v-for="(followup, index) in followups" :key="followup.id">
              <div class="d-flex align-items-start gap-4">
                <VIcon :icon="resolveStatusIcon(followup.call_status)"
                  :color="followup?.self_status?.color || 'default'" />
                <div class="flex-grow-1">
                  <div class="d-flex justify-space-between">
                    <div>
                      <span class="font-weight-medium">Call Status:</span>
                      <template v-if="!statusEditing[followup.id]">
                        <VChip :color="followup?.self_status?.color || 'default'" size="small"
                          @dblclick="statusEditing[followup.id] = true; selectedStatus[followup.id] = followup.call_status">
                          {{ followup?.self_status?.title }}
                        </VChip>
                      </template>
                      <template v-else>
                        <VSelect v-model="selectedStatus[followup.id]" :items="statusList" item-title="status_text"
                          item-value="slug" dense hide-details @blur="updateFollowupStatus(followup)"
                          @update:modelValue="val => { selectedStatus[followup.id] = val; updateFollowupStatus(followup); }"
                          style=" display: inline-block;max-inline-size: 180px;" />
                      </template>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ makeDateFormat(followup.created_at) }} By {{ followup.creator?.name }}
                    </div>
                  </div>
                  <div class="mt-2">
                    <div class="text-subtitle-2">Call Summary</div>
                    <div class="text-body-2">{{ followup.call_summary }}</div>
                  </div>
                  <div v-if="followup.lead_prospect" class="mt-2">
                    <div class="text-subtitle-2">Lead Prospect</div>
                    <div class="text-body-2">{{ followup.lead_prospect }}</div>
                  </div>
                  <div v-if="followup.lead_prospect" class="mt-2">
                    <div class="text-subtitle-2">Next Call Time</div>
                    <div class="text-body-2">{{ makeDateFormat(followup.next_call_datetime) }}</div>
                  </div>
                </div>
              </div>
              <VDivider v-if="index < followups.length - 1" />
            </template>
          </template>
          <div v-else class="text-center pa-4">
            <VIcon icon="tabler-phone-off" size="48" color="grey" class="mb-2" />
            <div class="text-body-1 text-medium-emphasis">No follow ups found</div>
          </div>
        </div>
      </VCardText>
    </VCard>

    <AddDrawer v-model:is-drawer-open="isAddDrawerOpen" :type="props.type" @submit="fetchFollowups()" />
  </VContainer>
</template>

<script setup>
import { useFetchStatusList } from '@/utils/common';
import moment from 'moment';
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import AddDrawer from '../add/AddDrawer.vue';

const { statusList, fetchStatusList } = useFetchStatusList();

const props = defineProps({
  type: { type: String, default: null, validator: value => [QUOTATION_CLIENT, QUOTATION_LEAD, null].includes(value) },
  shouldRefresh: Boolean,
});

const emit = defineEmits(['add-followup']);
const isAddDrawerOpen = ref(false);
const handleAddFollowup = () => (isAddDrawerOpen.value = true);

const route = useRoute();
const followups = ref([]);
const loading = ref(false);

const makeDateFormat = (date, onlyDate = false) => {
  const m = moment.utc(date);
  return onlyDate ? m.format('DD-MM-YYYY') : m.format('dddd, MMMM Do YYYY, h:mm A');
};

const buildFollowupUrl = () => {
  const id = route.params.id;
  if (props.type === QUOTATION_LEAD) return `/followup?lead_id=${id}`;
  if (props.type === QUOTATION_CLIENT) return `/followup?client_id=${id}`;
  return '/followup';
};

const fetchFollowups = async () => {
  loading.value = true;
  try {
    const response = await $api(buildFollowupUrl());
    followups.value = response?.data || [];
  } catch (err) {
    console.error('Failed to fetch Follow ups:', err);
    toast.error('Failed to load Follow ups');
  } finally {
    loading.value = false;
  }
};

const refreshFollowups = () => fetchFollowups();
defineExpose({ refreshFollowups });

// Watchers
watch(
  () => [props.shouldRefresh, route.params.id],
  ([shouldRefresh, id]) => {
    if (shouldRefresh || id) fetchFollowups();
  },
  { immediate: true }
);

// Initial Fetch
onMounted(() => {
  Promise.all([fetchStatusList('FollowUp'), fetchFollowups()]);
});

// Status Editing
const statusEditing = ref({});
const selectedStatus = ref({});

const updateFollowupStatus = async followup => {
  const newStatus = selectedStatus.value[followup.id];
  if (!newStatus || newStatus === followup.call_status) return;

  try {
    await $api(`/followup/${followup.id}`, {
      method: 'PUT',
      body: { call_status: newStatus },
    });
    toast.success('Status updated successfully');
    statusEditing.value[followup.id] = false;
    fetchFollowups();
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to update status');
  }
};

const resolveStatusIcon = status => {
  const icons = {
    'call-picked': 'tabler-phone-call',
    'call-not-picked': 'tabler-phone-off',
    'call-later': 'tabler-clock',
  };
  return icons[status] || 'tabler-phone';
};
</script>
