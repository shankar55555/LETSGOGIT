<script setup>
import moment from 'moment'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import AddEditDrawer from '../add/AddEditDrawer.vue'
const props = defineProps({
  userInfo: { type: Object, default: null },
})
const searchQuery = ref('')
const isAddEditDrawerOpen = ref(false)
// Data table options
const route = useRoute()

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentTarget = ref(null);

// Data table Headers
const tableHeaderSlug = ref('targets_and_incentives');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const editTarget = (item) => {
  currentTarget.value = JSON.parse(JSON.stringify(item));
  isAddEditDrawerOpen.value = true;
};

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchTargets();
}
const dataItems = ref([])
const totalItems = ref(0)

const fetchTargets = async () => {
  try {
    let endpoint= `/user-targets?search=${searchQuery.value ?? ""}&page=${page.value}&sort_key=${sortBy.value ?? ""}&sort_order=${orderBy.value ?? ""}&per_page=${itemsPerPage.value}`;
    if(route.params.id){
      endpoint = `${endpoint}&user_id=${route.params.id}`;
    }
    const response = await $api(endpoint)
    dataItems.value = response.data
    totalItems.value = response.meta.total
  } catch (err) {
    console.error('Failed to fetch targets:', err)
    // Optionally show a toast
    toast.error('Failed to load targets')
  }
}

const addTarget = (item) => {
  currentTarget.value = null;
  isAddEditDrawerOpen.value = true;
}

const refresh = () => {
  fetchTargets();
}

const makeDateFormat = (date , onlyDate = false) => {
    if(onlyDate)
    return moment(date).format('LL');
    else
    return moment(date).format('LLLL');
};


const markAsPaid = async (item) => {
  try {
    // Toggle the is_paid status locally immediately for responsive UI
    const newStatus = !item.is_paid;
    item.is_paid = newStatus;
    
    // Make API call to update the status
    await $api(`/user-targets/mark-as-paid`,{
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: {
              id: item.id,
              is_paid: newStatus
              },
        });
    toast.success(`Target marked as ${newStatus ? 'paid' : 'unpaid'} successfully`);
  } catch (err) {
    // Revert the change if API call fails
    item.is_paid = !item.is_paid;
    console.error('Failed to update payment status:', err);
    toast.error('Failed to update payment status');
  }
};

</script>

<template>
  <div v-if="$can('targets', 'view')">
    <VCard title="Targets">
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          @input="fetchTargets"
            placeholder="Search By Target or Incentive" />
          <div class="d-flex flex-row gap-4 align-center flex-wrap">
            <AppSelect v-model="itemsPerPage" :items="[5, 10, 20, 50, 100]" />

            <VBtn v-if="$can('targets', 'export-list')" prepend-icon="tabler-upload" variant="tonal" color="secondary">
              Export
            </VBtn>
            <VBtn v-if="$can('targets', 'create')" prepend-icon="tabler-plus" @click="addTarget()">
              Add New
            </VBtn>

            <!-- Filter Header Btn FilterHeaderTableBtn -->
            <FilterHeaderTableBtn :slug="tableHeaderSlug" @filterHeaderValue="getFilteredHeaderValue" />
          </div>
        </div>
      </VCardText>

      <VDivider />
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:page="page" :items="dataItems" item-value="name"
        :headers="headers.filter((header) => header.checked)" :items-length="totalItems" show-select
        class="text-no-wrap" @update:options="updateOptions">

        <template #item.name="{ item }">
          <RouterLink :to="{ name: 'target-details-id', params: { id: item.id } }"
                  class="text-link font-weight-medium d-inline-block" style="line-height: 1.375rem;">
                  {{ item.name }}
          </RouterLink>
        </template>

         <!-- is_paid -->
         <template #item.is_paid="{ item }">
          <VSwitch :model-value="item.is_paid" 
              @update:model-value="markAsPaid(item)"
              :disabled="!$can('targets', 'edit')"
              />
        </template>
         <!-- incentive_percentage -->
         <template #item.incentive_percentage="{ item }">
          {{ item.incentive_percentage }}%
        </template>
        <template #item.month="{ item }">
          {{ makeDateFormat(item.month,true)}}
        </template>
        <template #item.created_at="{ item }">
          {{ makeDateFormat(item.created_at )}}
        </template>
        <!-- Actions Column -->
        <template #item.action="{ item }">
          <IconBtn v-if="$can('targets', 'edit')" @click="editTarget(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>
 
    <AddEditDrawer v-model:is-drawer-open="isAddEditDrawerOpen" :currentTarget="currentTarget" @submit="refresh"
      @close="isAddEditDrawerOpen = false" />
  </div>
</template>
