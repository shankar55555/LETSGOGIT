<script setup>
import moment from 'moment'
import { toast } from 'vue3-toastify'
import AddEditDrawer from '../add/AddEditDrawer.vue'
import ConfirmDialog from '../dialog/ConfirmDialog.vue'
const searchQuery = ref('')
const isAddEditDrawerOpen = ref(false)
const isDeleteDialogOpen = ref(false)
// Data table options

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const currentTarget = ref(null);

// Data table Headers
const tableHeaderSlug = ref('target-list');
const headers = ref([]);
const getFilteredHeaderValue = async (headerList) => { headers.value = headerList; };

const editTarget = (item) => {
  currentTarget.value = JSON.parse(JSON.stringify(item));
  isAddEditDrawerOpen.value = true;
};

const resolveStatusVariant = status => {
  if (status === 1) return { color: 'primary', text: 'Current' }
  else if (status === 2) return { color: 'success', text: 'Professional' }
  else if (status === 3) return { color: 'error', text: 'Rejected' }
  else if (status === 4) return { color: 'warning', text: 'Resigned' }
  else return { color: 'info', text: 'Applied' }
}

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchTargets();
}
const dataItems = ref([])
const totalItems = ref(0)

const fetchTargets = async () => {
  try {
   

    const response = await $api(
      `/targets?search=${searchQuery.value ?? ""}&page=${page.value}&sort_key=${sortBy.value ?? ""}&sort_order=${orderBy.value ?? ""}&per_page=${itemsPerPage.value}`
    )

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

const openDeleteDialog = (item) => {
  currentTarget.value = JSON.parse(JSON.stringify(item));
  isDeleteDialogOpen.value = true;
}

const refresh = () => {
  fetchTargets();
}

const makeDateFormat = (date , onlyDate = false) => {
    if(onlyDate)
    return moment(date).format('DD-MM-Y');
    else
    return moment(date).format('LLLL');
};
</script>

<template>
  <div v-if="$can('targets', 'view')">
    <VCard>
      <VCardText>
        <div class="d-flex justify-space-between flex-wrap gap-y-4">
          <AppTextField v-model="searchQuery" style="max-inline-size: 280px; min-inline-size: 280px;"
          @input="fetchTargets"
            placeholder="Search Name" />
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

        
         <!-- incentive_percent -->
         <template #item.incentive_percent="{ item }">
          {{ item.incentive_percent }}%
        </template>
        <!-- creator -->
        <template #item.created_by="{ item }">
          {{ item.creator?.name || '—' }}
        </template>
        <!-- updater -->
        <template #item.last_updated_by="{ item }">
          {{ item.updater?.name || '-' }}
        </template>
        <template #item.start_date="{ item }">
          {{ makeDateFormat(item.start_date,true)}}
        </template>
        <template #item.end_date="{ item }">
          {{ makeDateFormat(item.end_date,true)}}
        </template>
        <template #item.created_at="{ item }">
          {{ makeDateFormat(item.created_at )}}
        </template>
        <template #item.updated_at="{ item }">
          {{ item.updater ? makeDateFormat(item.updated_at ) : '-'}}
        </template>
        <!-- Actions Column -->
        <template #item.action="{ item }">
          <IconBtn :to="{ name: 'target-details-id', params: { id: item.id } }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn v-if="$can('targets', 'edit')" @click="editTarget(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="$can('targets', 'delete')" @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>


    <!-- 👉 Confirm Dialog -->
    <ConfirmDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!"
      confirmation-question="Are you sure want to delete target?" :currentItem="currentTarget" @submit="refresh"
      :endpoint="`/targets/${currentTarget?.id}`" @close="isDeleteDialogOpen = false" />

    <AddEditDrawer v-model:is-drawer-open="isAddEditDrawerOpen" :currentTarget="currentTarget" @submit="refresh"
      @close="isAddEditDrawerOpen = false" />
  </div>
</template>
