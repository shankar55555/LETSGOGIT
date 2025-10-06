<script setup>
import { ref, reactive, onMounted } from "vue";
import {
  VBtn,
  VCard,
  VCardText,
  VCol,
  VIcon,
  VRow,
  VChip,
  VForm,
  VTextField,
  VAutocomplete,
  VDialog,
  VCardTitle,
  VCardSubtitle,
  VCardActions,
} from "vuetify/components";
import { IconPlus, IconFolder, IconFileText } from '@tabler/icons-vue';
import TreeItem from "../../components/core/TreeItem.vue";
import { toast } from "vue3-toastify";

// === Data Structure ===
const expanded = ref(false);
const loading = ref(false);
const chartData = ref([]);
// API Functions
async function fetchAccounts() {
  try {
    loading.value = true;
    const response = await $api('/api/v1/accounts');
    if (response.success) {
      chartData.value = response.data;
    }
  } catch (error) {
    console.error('Error fetching accounts:', error);
    toast.error('Failed to load accounts');
  } finally {
    loading.value = false;
  }
}

async function fetchParentOptions() {
  try {
    const response = await $api('/api/v1/accounts/parent-options');
    if (response.success) {
      parentGroups.value = response.data;
    }
  } catch (error) {
    console.error('Error fetching parent options:', error);
  }
}

// === Dialog States ===
const showLedgerDialog = ref(false);
const showGroupDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);

// === Selected Node for Editing/Deletion ===
const selectedNode = ref(null);
const selectedNodeToDelete = ref(null);

// === Form Refs ===
const ledgerFormRef = ref();
const groupFormRef = ref();
const editFormRef = ref();

// === Forms ===
const ledgerForm = reactive({
  name: "",
  parentGroup: null,
});

const groupForm = reactive({
  name: "",
  parentGroup: null,
});

const editForm = reactive({
  name: "",
  position: "",
  parentGroup: null,
});

// === Validation Rules ===
const nameRules = [(v) => !!v || "This field is required"];

const parentGroupRules = [(v) => !!v || "This field is required"];

// === Parent Group Options ===
const parentGroups = ref([]);



// === Lifecycle ===
onMounted(async () => {
  await fetchAccounts();
  await fetchParentOptions();
});

// === Submit Methods ===
async function submitLedgerForm() {
  const { valid } = await ledgerFormRef.value?.validate();
  if (!valid) {
    toast.error("Please fill all required fields for Ledger.");
    return;
  }

  try {
    const response = await $api('/api/v1/accounts', {
      method: 'POST',
      body: {
        name: ledgerForm.name,
        account_type: 'ledger',
        parent_id: ledgerForm.parentGroup,
        type: 'Balance Sheet' // Will be inherited from parent in backend
      }
    });

    if (response.success) {
      toast.success(response.message || "Ledger created successfully.");
      await fetchAccounts();
      await fetchParentOptions();
      
      // Reset form
      showLedgerDialog.value = false;
      ledgerForm.name = "";
      ledgerForm.parentGroup = null;
      ledgerFormRef.value?.resetValidation();
    }
  } catch (error) {
    console.error('Error creating ledger:', error);
    toast.error('Failed to create ledger');
  }
}

async function submitGroupForm() {
  const { valid } = await groupFormRef.value?.validate();
  if (!valid) {
    toast.error("Please fill all required fields for Group.");
    return;
  }

  try {
    const response = await $api('/api/v1/accounts', {
      method: 'POST',
      body: {
        name: groupForm.name,
        account_type: 'group',
        parent_id: groupForm.parentGroup,
        type: 'Balance Sheet' // Will be inherited from parent in backend
      }
    });

    if (response.success) {
      toast.success(response.message || "Group created successfully.");
      await fetchAccounts();
      await fetchParentOptions();
      
      // Reset form
      showGroupDialog.value = false;
      groupForm.name = "";
      groupForm.parentGroup = null;
      groupFormRef.value?.resetValidation();
    }
  } catch (error) {
    console.error('Error creating group:', error);
    toast.error('Failed to create group');
  }
}

async function submitEditForm() {
  const { valid } = await editFormRef.value?.validate();
  if (!valid || !selectedNode.value) return;

  const node = selectedNode.value;
  const newName = editForm.name;
  const newParentId = editForm.parentGroup;
  const newPosition = parseInt(editForm.position);

  const oldParentId = getParentId(node);
  const oldParent = findNodeById(chartData, oldParentId);
  const newParent = findNodeById(chartData, newParentId);

  if (!newParent || (oldParentId && !oldParent)) {
    toast.error("Parent group not found.");
    return;
  }

  // Remove from old parent
  if (oldParentId === newParentId) {
    const siblings = oldParent.children || [];
    const index = siblings.findIndex((child) => child.id === node.id);
    if (index === -1) return;

    siblings.splice(index, 1); // remove current
    siblings.splice(newPosition - 1, 0, node); // insert at new position
  } else {
    if (!newParent.children) newParent.children = [];

    const newId = `${newParent.id}.${newParent.children.length + 1}`;
    const movedNode = {
      ...node,
      id: newId,
      name: newName,
    };

    // Remove from old parent
    oldParent.children = oldParent.children.filter((child) => child.id !== node.id);

    // Insert at desired position
    newParent.children.splice(newPosition - 1, 0, movedNode);

    // Update reference
    selectedNode.value = movedNode;
  }

  selectedNode.value.name = newName;

  parentGroups.value = buildParentGroupOptions(chartData);
  toast.success("Node updated successfully.");

  // Reset
  showEditDialog.value = false;
  editForm.name = "";
  editForm.position = "";
  editForm.parentGroup = null;
  editFormRef.value?.resetValidation();
}


// === Edit Handler ===
function handleEdit(node) {
  selectedNode.value = node;
  editForm.name = node.name;
  editForm.position = getPositionInParent(node); // 🟢 Position (1-based index)
  editForm.parentGroup = getParentId(node); // 🟢 Get current parent
  showEditDialog.value = true;
}

// === Helper Function to Get Parent ID ===
function getParentId(node) {
  const findParent = (data, childId) => {
    for (const item of data) {
      if (item.children) {
        if (item.children.some((child) => child.id === childId)) return item.id;
        const parentId = findParent(item.children, childId);
        if (parentId) return parentId;
      }
    }
    return null;
  };
  return findParent(chartData, node.id);
}

function getPositionInParent(node) {
  const parentId = getParentId(node);
  if (!parentId) return 1;
  const parent = findNodeById(chartData, parentId);
  if (!parent?.children) return 1;
  const index = parent.children.findIndex((child) => child.id === node.id);
  return index >= 0 ? index + 1 : 1; // 1-based
}

// === Delete Method ===
// === Delete Method ===
async function confirmDelete() {
  if (!selectedNodeToDelete.value) return;

  const parentId = getParentId(selectedNodeToDelete.value);
  if (parentId) {
    // For nested nodes
    const parent = findNodeById(chartData, parentId);
    if (parent && parent.children) {
      parent.children = parent.children.filter(child => child.id !== selectedNodeToDelete.value.id);
    }
  } else {
    // For base-level nodes
    chartData.splice(
      chartData.findIndex(node => node.id === selectedNodeToDelete.value.id),
      1
    );
  }


  parentGroups.value = buildParentGroupOptions(chartData);
  toast.success("Node deleted successfully.");

  showDeleteDialog.value = false;
  selectedNodeToDelete.value = null;
}

function handleDelete(node) {
  selectedNodeToDelete.value = node;
  showDeleteDialog.value = true;
}
</script>

<template>
  <div class="account_ui_vcard">
    <VRow>
      <VCol cols="12" lg="9" md="9">
        <VCard class="account_vcard_border shadow-none" title="Chart of Groups and Ledgers"
          subtitle="Create and manage your ledger accounts and groups.">
          <template #append>
            <div class="d-flex align-center gap-2">
              <VBtn @click="showGroupDialog = true" class="account_v_btn_outlined" variant="outlined">
                <template #prepend>
                  <IconPlus size="18" />
                </template>
                Add Group
              </VBtn>
              <VBtn @click="showLedgerDialog = true" class="account_v_btn_primary" variant="tonal">
                <template #prepend>
                  <IconPlus size="18" />
                </template>
                Add Ledger
              </VBtn>
            </div>
          </template>

          <VCardText>
            <!-- Parent -->
            <div class="d-flex align-center gap-2 mb-2">
              <div class="d-flex align-center gap-1">
                <IconFolder class="account_folder_icon" size="16" />
                <p class="mb-0 account_info_title">Group</p>
              </div>
              <div class="d-flex align-center gap-1">
                <IconFileText class="account_ledger_icon" size="16" />
                <p class="mb-0 account_info_title">Ledger</p>
              </div>
            </div>
            <VCard class="py-2 pr-2 account_vcard_border shadow-none" variant="text">
              <div class="custom_expansion_item">
                <TreeItem v-for="item in chartData" :key="item.id" :node="item" :level="0" @edit="handleEdit"
                  @delete="handleDelete" />
              </div>
            </VCard>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Add Ledger dialog -->
    <VDialog v-model="showLedgerDialog" max-width="400" @click:outside="ledgerFormRef?.resetValidation()">
      <VCard>
        <VCardTitle class="account_ui_swtich_title pb-0">Add New Ledger</VCardTitle>
        <VCardSubtitle class="account_ui_swtich_subtitle text-wrap px-3">Add a new ledger to an existing group in your
          chart
          of
          accounts.</VCardSubtitle>
        <VCardText>
          <VForm ref="ledgerFormRef">
            <VTextField v-model="ledgerForm.name" :rules="nameRules" class="accouting_field accouting_active_field mb-2"
              placeholder="Name" variant="outlined" hide-details="auto" />
            <VAutocomplete v-model="ledgerForm.parentGroup" :items="parentGroups" :rules="parentGroupRules"
              class="accouting_field accouting_active_field" placeholder="Parent Group" item-title="title"
              item-value="value" variant="outlined" hide-details="auto" />
          </VForm>
        </VCardText>
        <VCardActions class="justify-end mr-4 mb-2">
          <VBtn text="Cancel" class="account_v_btn_outlined" variant="outlined"
            @click=" showLedgerDialog = false; ledgerFormRef?.resetValidation();" />
          <VBtn text="Add Ledger" class="account_v_btn_primary" @click="submitLedgerForm" />
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add Group dialog -->
    <VDialog v-model="showGroupDialog" max-width="400" @click:outside="groupFormRef?.resetValidation()">
      <VCard>
        <VCardTitle class="account_ui_swtich_title" pb-0>Add New Group</VCardTitle>
        <VCardSubtitle class="account_ui_swtich_subtitle px-3">Add a new group to your chart of accounts.
        </VCardSubtitle>
        <VCardText>
          <VForm ref="groupFormRef">
            <VTextField v-model="groupForm.name" :rules="nameRules" class="accouting_field accouting_active_field mb-2"
              placeholder="Name" variant="outlined" hide-details="auto" />
            <VAutocomplete v-model="groupForm.parentGroup" :items="parentGroups" :rules="parentGroupRules"
              class="accouting_field accouting_active_field" placeholder="Parent Group" item-title="title"
              item-value="value" variant="outlined" hide-details="auto" />
          </VForm>
        </VCardText>
        <VCardActions class="justify-end mr-4 mb-2">
          <VBtn text="Cancel" class="account_v_btn_outlined" variant="outlined" @click="
            showGroupDialog = false;
          groupFormRef?.resetValidation();
          " />
          <VBtn text="Add Group" class="account_v_btn_primary" @click="submitGroupForm" />
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Edit Dialog -->
    <VDialog v-model="showEditDialog" max-width="400" @click:outside="editFormRef?.resetValidation()">
      <VCard>
        <VCardTitle class="account_ui_swtich_title pb-0">Edit group</VCardTitle>
        <VCardSubtitle class="account_ui_swtich_subtitle px-3">Update the name or parent group.</VCardSubtitle>
        <VCardText>
          <VForm ref="editFormRef">
            <VTextField v-model="editForm.name" :rules="nameRules" class="accouting_field accouting_active_field mb-2"
              placeholder="Name" variant="outlined" hide-details="auto" />
            <VTextField v-model="editForm.position" class="accouting_field accouting_active_field mb-2"
              placeholder="Position" variant="outlined" hide-details="auto" />
            <VAutocomplete v-model="editForm.parentGroup" :items="parentGroups" :rules="parentGroupRules"
              class="accouting_field accouting_active_field" placeholder="Parent Group" item-title="title"
              item-value="value" variant="outlined" hide-details="auto" />
          </VForm>
        </VCardText>
        <VCardActions class="justify-end mr-4 mb-2">
          <VBtn text="Cancel" class="account_v_btn_outlined" variant="outlined" @click="
            showEditDialog = false;
          editFormRef?.resetValidation();
          " />
          <VBtn text="Save Changes" class="account_v_btn_primary" @click="submitEditForm" />
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Delete Dialog -->
    <VDialog v-model="showDeleteDialog" max-width="400" @click:outside="showDeleteDialog = false">
      <VCard>
        <VCardTitle class="account_ui_swtich_title pb-0">Are you absolutely sure?</VCardTitle>
        <VCardSubtitle class="account_ui_swtich_subtitle text-wrap px-3">
          This action cannot be undone. This will permanently delete the {{ selectedNodeToDelete?.name }} group and all
          of
          its subgroups.
        </VCardSubtitle>
        <VCardActions class="justify-end mr-4 mb-2">
          <VBtn text="Cancel" class="account_v_btn_outlined" variant="outlined" @click="showDeleteDialog = false" />
          <VBtn text="Continue" class="account_v_btn_primary" @click="confirmDelete" />
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
