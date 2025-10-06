<script setup>
import dayjs from 'dayjs';
import { onMounted, reactive, ref } from "vue";
import { toast } from "vue3-toastify";
import ConfirmationModal from '../../components/ConfirmationModal.vue';
import JournalEntryModal from '../../components/JournalEntryModal.vue';

// API Service Functions
const apiService = {
  // Get all journal entries with filters
  async getJournalEntries(params = {}) {
    try {
      const response = await $api('/v1/journal-entries', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching journal entries:', error);
      throw error;
    }
  },

  // Create new journal entry
  async createJournalEntry(data) {
    try {
      const response = await $api('/v1/journal-entries', {
        method: 'POST',
        body: data
      });
      return response.data;
    } catch (error) {
      console.error('Error creating journal entry:', error);
      throw error;
    }
  },

  // Get single journal entry
  async getJournalEntry(id) {
    try {
      const response = await $api(`/v1/journal-entries/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching journal entry:', error);
      throw error;
    }
  },

  // Update journal entry
  async updateJournalEntry(id, data) {
    try {
      const response = await $api(`/v1/journal-entries/${id}`, {
        method: 'PUT',
        body: data
      });
      return response.data;
    } catch (error) {
      console.error('Error updating journal entry:', error);
      throw error;
    }
  },

  // Delete journal entry
  async deleteJournalEntry(id) {
    try {
      const response = await $api(`/v1/journal-entries/${id}`, {
        method: 'DELETE'
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting journal entry:', error);
      throw error;
    }
  },

  // Update journal entry status
  async updateJournalEntryStatus(id, status) {
    try {
      const response = await $api(`/v1/journal-entries/${id}/status`, {
        method: 'PATCH',
        body: { status }
      });
      return response.data;
    } catch (error) {
      console.error('Error updating journal entry status:', error);
      throw error;
    }
  },

  // Get journal entry statistics
  async getJournalEntryStatistics() {
    try {
      const response = await $api('/v1/journal-entries/statistics');
      return response.data;
    } catch (error) {
      console.error('Error fetching statistics:', error);
      throw error;
    }
  },

  // Get all accounts/ledgers
  async getAccounts() {
    try {
      const response = await $api('/v1/accounts/ledgers');
      return response.data;
    } catch (error) {
      console.error('Error fetching ledgers:', error);
      throw error;
    }
  }
};

// Loading states
const loading = ref({
  entries: false,
  creating: false,
  updating: false,
  deleting: false
});

// Error handling
const errors = ref({});

// Pagination and filtering
const pagination = ref({
  page: 1,
  perPage: 15,
  total: 0
});

const filters = ref({
  search: '',
  status: '',
  voucher_type: '',
  start_date: '',
  end_date: '',
  sort_by: 'entry_date',
  sort_order: 'desc'
});

// Edit mode
const editMode = ref(false);
const editingEntryId = ref(null);
const originalFormData = ref(null);

// Function to handle amount input and show words
function handleAmountInput(event, rowIndex, type) {
  const amount = event.target.value;
  const numValue = parseFloat(amount) || 0;
  const words = numberToWords(numValue);

  // Update the row with both numeric value and words
  if (type === "debit") {
    debitRows.value[rowIndex].amount = numValue;
    debitRows.value[rowIndex].amountInWords = words;
  } else {
    creditRows.value[rowIndex].amount = numValue;
    creditRows.value[rowIndex].amountInWords = words;
  }
}

// Journal entry form data
const journalEntryForm = ref({
  entryDate: new Date(),
  description: "",
  voucherType: "",
});

// Validation rules for journal entry form
// const journalEntryRules = {
// entryDate: (value) => validateField(value, journalEntryValidations.entryDate),
// description: (value) =>
//   validateField(value, journalEntryValidations.description),

// voucherType: (value) =>
// validateField(value, journalEntryValidations.voucherType),
// };
// console.log(journalEntryRules.description);

const descriptionError = ref("");
const checkValidation = (value) => {
  descriptionError.value = validateField(
    value,
    journalEntryValidations.description
  );
  console.log(descriptionError.value);
  // return result === true ? true : result;
};

// Form reference
const journalEntryFormRef = ref();
const journalEntryModalRef = ref();

// Load journal entries from API
async function loadJournalEntries() {
  try {
    loading.value.entries = true;
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.perPage,
      ...filters.value
    };

    const response = await apiService.getJournalEntries(params);
    console.log('response', response.data);

    // Transform API data to match table structure
    if (response?.data?.length > 0) {
      allEntries.value = response.data.map(entry => {
        // Combine debit and credit entries into accounts array
        const accounts = [];

        // Add debit entries
        if (entry.debit_entries && Array.isArray(entry.debit_entries)) {
          entry.debit_entries_with_accounts.forEach(debitEntry => {
            accounts.push({
              title: debitEntry.account?.name || `Account ${debitEntry.account_id}`,
              debit: debitEntry.amount,
              credit: ''
            });
          });
        }

        // Add credit entries
        if (entry.credit_entries_with_accounts && Array.isArray(entry.credit_entries_with_accounts)) {
          entry.credit_entries_with_accounts.forEach(creditEntry => {
            accounts.push({
              title: creditEntry.account?.name || `Account ${creditEntry.account_id}`,
              debit: '',
              credit: creditEntry.amount
            });
          });
        }

        return {
          ...entry,
          date: entry.entry_date,
          entry: entry.entry_number,
          particulars: {
            accounts: accounts
          }
        };
      });
    } else {
      allEntries.value = [];
    }

    console.log('transformed allEntries:', allEntries.value);
    pagination.value.total = response.total;
    pagination.value.page = response.current_page;
  } catch (error) {
    toast.error('Failed to load journal entries');
    console.error('Load entries error:', error);
  } finally {
    loading.value.entries = false;
  }
}

// Load accounts/ledgers from API
async function loadAccounts() {
  try {
    const response = await apiService.getAccounts();

    if (response.length > 0) {
      // Transform the accounts data to match the expected format
      allLedgers.value = response.map(account => ({
        title: account.name,
        value: account.id,
        groupId: account.parent_id || account.group_id
      }));

      // Update chartData with accounts hierarchy if available
      if (response.hierarchy) {
        Object.assign(chartData, response.data.hierarchy);
      }
    }
  } catch (error) {
    toast.error('Failed to load accounts');
    console.error('Load accounts error:', error);
  }
}

// Prepare form data for API
function prepareFormData() {
  const debitEntries = debitRows.value
    .filter(row => row.account && row.amount > 0)
    .map(row => ({
      account_id: row.account,
      amount: parseFloat(row.amount)
    }));

  const creditEntries = creditRows.value
    .filter(row => row.account && row.amount > 0)
    .map(row => ({
      account_id: row.account,
      amount: parseFloat(row.amount)
    }));

  return {
    entry_date: journalEntryForm.value.entryDate,
    description: journalEntryForm.value.description,
    voucher_type: journalEntryForm.value.voucherType,
    debit_entries: debitEntries,
    credit_entries: creditEntries,
    status: 'pending'
  };
}

// Modal operations
const openCreateModal = () => {
  selectedEntry.value = null
  editMode.value = false
  showJournalEntryModal.value = true
}

const openEditModal = (entry) => {
  selectedEntry.value = { ...entry }
  editMode.value = true
  editingEntryId.value = entry.id
  showJournalEntryModal.value = true
}

const handleModalSubmit = async (formData) => {
  try {
    loading.value.creating = true

    let response
    if (editMode.value && editingEntryId.value) {
      // Update existing entry
      response = await apiService.updateJournalEntry(editingEntryId.value, formData)
      toast.success('Journal entry updated successfully!')
    } else {
      // Create new entry
      response = await apiService.createJournalEntry(formData)
      toast.success('Journal entry created successfully!')
    }

    // Close modal and reload entries
    showJournalEntryModal.value = false
    await loadJournalEntries()

  } catch (error) {
    console.error('Error submitting journal entry:', error)
    if (error.response?.data?.errors) {
      // Handle validation errors - pass to modal
      if (journalEntryModalRef.value && journalEntryModalRef.value.setErrors) {
        journalEntryModalRef.value.setErrors(error.response.data.errors)
      }
    } else {
      toast.error('Failed to save journal entry. Please try again.')
    }
  } finally {
    loading.value.creating = false
  }
}

const handleModalCancel = () => {
  selectedEntry.value = null
  editMode.value = false
  editingEntryId.value = null
  showJournalEntryModal.value = false
}

// Submit journal entry form
async function submitJournalEntryForm() {
  try {
    checkValidation(journalEntryForm.value.description);
    const { valid } = await journalEntryFormRef.value?.validate();
    if (!valid) {
      return false;
    }

    const formData = prepareFormData();

    // Validate that debit and credit entries exist
    if (formData.debit_entries.length === 0 || formData.credit_entries.length === 0) {
      toast.error('Please add at least one debit and one credit entry');
      return false;
    }

    if (editMode.value && editingEntryId.value) {
      // Update existing entry
      loading.value.updating = true;
      const response = await apiService.updateJournalEntry(editingEntryId.value, formData);

      if (response.success) {
        toast.success('Journal entry updated successfully!');
        await loadJournalEntries();
        resetForm();
      }
    } else {
      // Create new entry
      loading.value.creating = true;
      const response = await apiService.createJournalEntry(formData);

      if (response.success) {
        toast.success('Journal entry created successfully!');
        await loadJournalEntries();
        resetForm();
      }
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      // Handle validation errors
      const validationErrors = error.response.data.errors;
      Object.keys(validationErrors).forEach(key => {
        toast.error(validationErrors[key][0]);
      });
    } else {
      toast.error(editMode.value ? 'Failed to update journal entry' : 'Failed to create journal entry');
    }
    console.error('Submit form error:', error);
  } finally {
    loading.value.creating = false;
    loading.value.updating = false;
  }
}

// Reset form to initial state
function resetForm() {
  journalEntryForm.value = {
    entryDate: new Date(),
    description: "",
    voucherType: "",
  };

  debitRows.value = [{ account: null, amount: 0, amountInWords: "" }];
  creditRows.value = [{ account: null, amount: 0, amountInWords: "" }];

  showJournalEntryCard.value = false;
  editMode.value = false;
  editingEntryId.value = null;
  originalFormData.value = null;

  journalEntryFormRef.value?.resetValidation();
}

// Edit journal entry
async function editJournalEntry(entry) {
  try {
    loading.value.entries = true;
    const response = await apiService.getJournalEntry(entry.id);

    if (response.success) {
      const data = response.data;

      // Store original data for revert functionality
      originalFormData.value = JSON.parse(JSON.stringify(data));

      // Populate form
      journalEntryForm.value = {
        entryDate: new Date(data.entry_date),
        description: data.description,
        voucherType: data.voucher_type,
      };

      // Populate debit rows
      debitRows.value = data.debit_entries.map(entry => ({
        account: entry.account_id,
        amount: entry.amount,
        amountInWords: numberToWords(entry.amount)
      }));

      // Populate credit rows
      creditRows.value = data.credit_entries.map(entry => ({
        account: entry.account_id,
        amount: entry.amount,
        amountInWords: numberToWords(entry.amount)
      }));

      editMode.value = true;
      editingEntryId.value = entry.id;
      showJournalEntryCard.value = true;
    }
  } catch (error) {
    toast.error('Failed to load journal entry for editing');
    console.error('Edit entry error:', error);
  } finally {
    loading.value.entries = false;
  }
}

const confirmDelete = (entry) => {
  confirmationConfig.value = {
    type: 'danger',
    title: 'Delete Journal Entry',
    message: `Are you sure you want to delete journal entry #${entry.entry || entry.id}?`,
    details: 'This action cannot be undone and will permanently remove this entry from your records.',
    action: () => deleteJournalEntry(entry.id)
  }
  showConfirmationModal.value = true
}

// Delete journal entry
async function deleteJournalEntry(entryId) {
  try {
    loading.value.deleting = true;
    const response = await apiService.deleteJournalEntry(entryId);

    if (response.success) {
      toast.success('Journal entry deleted successfully!');
      showConfirmationModal.value = false;
      await loadJournalEntries();
    }
  } catch (error) {
    if (error.response?.status === 422) {
      toast.error('Cannot delete approved journal entry');
    } else {
      toast.error('Failed to delete journal entry');
    }
    console.error('Delete entry error:', error);
  } finally {
    loading.value.deleting = false;
  }
}

const handleConfirmationConfirm = () => {
  if (confirmationConfig.value.action) {
    confirmationConfig.value.action()
  }
}

const handleConfirmationCancel = () => {
  showConfirmationModal.value = false
  confirmationConfig.value.action = null
}

// Update journal entry status
async function updateEntryStatus(entry, newStatus) {
  try {
    const response = await apiService.updateJournalEntryStatus(entry.id, newStatus);

    if (response.success) {
      toast.success(`Journal entry ${newStatus} successfully!`);
      await loadJournalEntries();
    }
  } catch (error) {
    toast.error(`Failed to ${newStatus} journal entry`);
    console.error('Update status error:', error);
  }
}

// Revert changes (restore original form data)
function revertChanges() {
  if (!originalFormData.value) {
    resetForm();
    return;
  }

  const data = originalFormData.value;

  journalEntryForm.value = {
    entryDate: new Date(data.entry_date),
    description: data.description,
    voucherType: data.voucher_type,
  };

  debitRows.value = data.debit_entries.map(entry => ({
    account: entry.account_id,
    amount: entry.amount,
    amountInWords: numberToWords(entry.amount)
  }));

  creditRows.value = data.credit_entries.map(entry => ({
    account: entry.account_id,
    amount: entry.amount,
    amountInWords: numberToWords(entry.amount)
  }));

  toast.info('Changes reverted to original values');
}

const chartData = reactive([
  {
    id: "1",
    name: "Assets",
    type: "Balance Sheet",
    children: [
      {
        id: "1.1",
        name: "Current Assets",
        type: "Balance Sheet",
        children: [
          { id: "1.1.1", name: "Cash", type: "Balance Sheet" },
          { id: "1.1.2", name: "Bank Accounts", type: "Balance Sheet" },
          { id: "1.1.3", name: "Accounts Receivable", type: "Balance Sheet" },
        ],
      },
      {
        id: "1.2",
        name: "Fixed Assets",
        type: "Balance Sheet",
        children: [
          { id: "1.2.1", name: "Property & Equipment", type: "Balance Sheet" },
          { id: "1.2.2", name: "Vehicles", type: "Balance Sheet" },
        ],
      },
    ],
  },
  {
    id: "2",
    name: "Liabilities",
    type: "Balance Sheet",
    children: [
      {
        id: "2.1",
        name: "Current Liabilities",
        type: "Balance Sheet",
        children: [
          { id: "2.1.1", name: "Accounts Payable", type: "Balance Sheet" },
          { id: "2.1.2", name: "Credit Card Payable", type: "Balance Sheet" },
        ],
      },
      {
        id: "2.2",
        name: "Long-Term Liabilities",
        type: "Balance Sheet",
        children: [{ id: "2.2.1", name: "Bank Loan", type: "Balance Sheet" }],
      },
    ],
  },
  {
    id: "3",
    name: "Equity",
    type: "Balance Sheet",
    children: [
      { id: "3.1", name: "Owner's Equity", type: "Balance Sheet" },
      { id: "3.2", name: "Retained Earnings", type: "Balance Sheet" },
    ],
  },
  {
    id: "4",
    name: "Income",
    type: "Profit & Loss",
    children: [
      { id: "4.1", name: "Sales Revenue", type: "Profit & Loss" },
      { id: "4.2", name: "Interest Income", type: "Profit & Loss" },
    ],
  },
  {
    id: "5",
    name: "Expenses",
    type: "Profit & Loss",
    children: [
      {
        id: "5.1",
        name: "Cost of Goods Sold",
        type: "Profit & Loss",
        children: [{ id: "5.1.1", name: "Purchases", type: "Profit & Loss" }],
      },
      {
        id: "5.2",
        name: "Operating Expenses",
        type: "Profit & Loss",
        children: [
          { id: "5.2.1", name: "Rent Expense", type: "Profit & Loss" },
          { id: "5.2.2", name: "Salaries & Wages", type: "Profit & Loss" },
          { id: "5.2.3", name: "Utilities Expense", type: "Profit & Loss" },
        ],
      },
    ],
  },
]);

const showJournalEntryCard = ref(false);
const showDetailsDialog = ref(false);
const showJournalEntryModal = ref(false);
const showConfirmationModal = ref(false);
const selectedEntry = ref(null);
const confirmationConfig = ref({
  type: 'warning',
  title: 'Confirm Action',
  message: '',
  details: '',
  action: null
});

// Initialize as empty array - data will be loaded from API
const allEntries = ref([]);

const entriesTableHeaders = ref([
  { title: "Date", value: "date", visible: true },
  { title: "Entry #", value: "entry", visible: true },
  { title: "Voucher Type", value: "voucher_type", visible: true },
  { title: "Particulars", value: "particulars", visible: true },
  { title: "Debit", value: "debit", visible: true },
  { title: "Credit", value: "credit", visible: true },
  { title: "Status", value: "status", visible: true },
  { title: "Actions", value: "actions", visible: true },
]);

// Initialize as empty array - data will be loaded from API
const allLedgers = ref([]);

const voucherTypes = ref([
  { title: "Sales Voucher", value: "sales_voucher" },
  { title: "Purchase Voucher", value: "purchase_voucher" },
  { title: "Journal Voucher", value: "journal_voucher" },
  { title: "Payment Voucher", value: "payment_voucher" },
  { title: "Reciept Voucher", value: "reciept_voucher" },
]);

const debitRows = ref([{ account: null, amount: 0, amountInWords: "" }]);
const creditRows = ref([{ account: null, amount: 0, amountInWords: "" }]);

// Ledger form
const ledgerForm = reactive({
  name: "",
  parentGroup: null,
});

const ledgerFormRef = ref();

// Import ledger validations

const nameRules = [(v) => validateField(v, ledgerValidations.ledgerName)];
const parentGroupRules = [
  (v) => validateField(v, ledgerValidations.parentGroup),
];

// Build parent group options
function buildParentGroupOptions(data, level = 0) {
  return data.flatMap((node) => {
    if (!node.children && node.children !== undefined) return [];
    const indent = "— ".repeat(level);
    const current = {
      title: `${indent}${node.name}`,
      value: node.id,
    };
    const children = node.children
      ? buildParentGroupOptions(node.children, level + 1)
      : [];
    return [current, ...children];
  });
}
const parentGroups = ref(buildParentGroupOptions(chartData));

// Find node by ID
function findNodeById(data, id) {
  for (const node of data) {
    if (node.id === id) return node;
    if (node.children) {
      const found = findNodeById(node.children, id);
      if (found) return found;
    }
  }
  return null;
}

// Submit ledger form
async function submitLedgerForm() {
  const { valid } = await ledgerFormRef.value?.validate();
  if (!valid) {
    toast.error("Please fill all required fields for Ledger.");
    return;
  }

  const parentNode = findNodeById(chartData, ledgerForm.parentGroup);
  if (!parentNode) {
    toast.error("Parent group not found.");
    return;
  }

  if (!parentNode.children) {
    parentNode.children = [];
  }

  const parentParts = ledgerForm.parentGroup.split(".");
  const newIndex = parentNode.children.length
    ? Math.max(
      ...parentNode.children.map((c) => parseInt(c.id.split(".").pop()))
    ) + 1
    : 1;
  const newId = `${ledgerForm.parentGroup}.${newIndex}`;

  const newLedger = {
    id: newId,
    name: ledgerForm.name,
    type: parentNode.type,
    children: null,
  };

  parentNode.children.push(newLedger);
  parentGroups.value = buildParentGroupOptions(chartData);
  toast.success("Ledger created successfully.");

  // Reset
  ledgerForm.name = "";
  ledgerForm.parentGroup = null;
  ledgerFormRef.value?.resetValidation();
}

const addDebitRow = () => {
  debitRows.value.push({ account: null, amount: 0, amountInWords: "" });
};

const removeDebitRow = (index) => {
  if (debitRows.value.length > 1) {
    debitRows.value.splice(index, 1);
  }
};

const addCreditRow = () => {
  creditRows.value.push({ account: null, amount: 0, amountInWords: "" });
};

const removeCreditRow = (index) => {
  if (creditRows.value.length > 1) {
    creditRows.value.splice(index, 1);
  }
};

// Open details dialog for the selected entry
function openDetailsDialog(entry) {
  selectedEntry.value = entry;
  showDetailsDialog.value = true;
}

function totalAmount(accounts, type) {
  if (!accounts || !Array.isArray(accounts)) return "₹0.00";

  const sum = accounts.reduce((acc, item) => {
    const value = item[type]?.replace(/[^0-9.-]+/g, "") || "0";
    return acc + parseFloat(value);
  }, 0);

  return `₹${sum.toLocaleString("en-IN", { minimumFractionDigits: 2 })}`;
}

function getToAccounts(entry) {
  if (!entry?.particulars?.description?.to) return [];
  return entry.particulars.description.to.split(",").map((a) => a.trim());
}

const hoveredRowIndex = ref(null);
const bounceKey = ref(0)

// Search and filter functions
function applyFilters() {
  pagination.value.page = 1;
  loadJournalEntries();
}

function clearFilters() {
  filters.value = {
    search: '',
    status: '',
    voucher_type: '',
    start_date: '',
    end_date: '',
    sort_by: 'entry_date',
    sort_order: 'desc'
  };
  pagination.value.page = 1;
  loadJournalEntries();
}

// Pagination functions
function changePage(page) {
  pagination.value.page = page;
  loadJournalEntries();
}

function changePerPage(perPage) {
  pagination.value.perPage = perPage;
  pagination.value.page = 1;
  loadJournalEntries();
}

onMounted(async () => {
  // Load initial data
  await Promise.all([
    loadJournalEntries(),
    loadAccounts()
  ]);

  setInterval(() => {
    bounceKey.value++ // force key change to retrigger animation
  }, 3000)
})
</script>

<template>
  <div class="account">
    <!-- Journal Entry Modal -->
    <JournalEntryModal v-model="showJournalEntryModal" :entry="selectedEntry" :accounts="allLedgers"
      :voucher-types="voucherTypes" :loading="loading.creating" @submit="handleModalSubmit" @cancel="handleModalCancel"
      ref="journalEntryModalRef" />

    <!-- Confirmation Modal -->
    <ConfirmationModal v-model="showConfirmationModal" :type="confirmationConfig.type" :title="confirmationConfig.title"
      :message="confirmationConfig.message" :details="confirmationConfig.details" :loading="loading.deleting"
      @confirm="handleConfirmationConfirm" @cancel="handleConfirmationCancel" />

    <VCard title="All Entries" subtitle="A record of all financial transactions."
      class="account_vcard_border pa-2 account_ui_vcard shadow-none">
      <div class="d-flex align-center px-3 justify-space-between">
        <VTextField style="max-inline-size: 265px;" class="accouting_field accouting_active_field"
          placeholder="Filter entries" variant="outlined">
          <template #prepend-inner>
            <IconSearch size="20" />
          </template>
        </VTextField>

        <div class="d-flex align-center gap-2">
          <VSwitch density="compact" inset class="account_swtich_btn mr-3" color="primary" hide-details
            label="Compact" />
          <VMenu width="200px" location="start" :close-on-content-click="false">
            <template #activator="{ props }">
              <v-tooltip text="Filters" location="top">
                <template #activator="{ props: tooltipProps }">
                  <VBtn v-bind="{ ...props, ...tooltipProps }" variant="text" class="account_filter_btn_color"
                    rounded="1" size="36">
                    <IconFilter size="24" />
                  </VBtn>
                </template>
              </v-tooltip>
            </template>
            <VCard class="account_vcard_menu account_vcard_border">
              <div class="account_vcard_menu_hdng px-4">Add Filters</div>
              <VDivider class="my-1 mt-0" />
              <div class="account_table_filter_menu py-1">
                <div class="account_vcard_menu_item">
                  <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                    <VCheckbox class="account_v_checkbox account_filter_menu_checkbox" density="compact" />
                    <span>Date</span>
                  </div>
                </div>
                <div class="account_vcard_menu_item">
                  <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                    <VCheckbox class="account_v_checkbox account_filter_menu_checkbox" density="compact" />
                    <span>Created By</span>
                  </div>
                </div>
                <div class="account_vcard_menu_item">
                  <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                    <VCheckbox class="account_v_checkbox account_filter_menu_checkbox" density="compact" />
                    <span>Account</span>
                  </div>
                </div>
              </div>
            </VCard>
          </VMenu>

          <VMenu width="110px" location="bottom" :close-on-content-click="false">
            <template v-slot:activator="{ props }">
              <VBtn v-bind="props" class="account_filter_btn_color" variant="text" rounded="1" size="36">
                <IconDownload size="24" />
              </VBtn>
            </template>
            <VCard class="account_vcard_border">
              <div class="account_table_filter_menu py-1">
                <div class="account_vcard_menu_item">
                  ninthree <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                    <span>PDF</span>
                  </div>
                </div>
                <div class="account_vcard_menu_item">
                  <div class="my-1 field_list_title cursor-pointer px-3 py-1 d-flex align-center gap-2">
                    <span>CSV</span>
                  </div>
                </div>
              </div>
            </VCard>
          </VMenu>
        </div>
      </div>
      <VCardText class="mt-2 pa-3">
        <VCard variant="flat" class="shadow-none">
          <div class="gst_summary_table_container">
            <table class="table table-bordered account_entries_table text-center w-100">
              <thead>
                <tr>
                  <th class="account_entries_table_header_date">Date</th>
                  <th class="account_entries_table_header_entry">Entry #</th>
                  <th class="account_entries_table_header_voucher">
                    Voucher Type
                  </th>
                  <th class="account_entries_table_header_particulars">
                    Particulars
                  </th>
                  <th class="account_entries_table_header_debit">Debit</th>
                  <th class="account_entries_table_header_credit">Credit</th>
                  <th class="account_entries_table_header_status">Status</th>
                  <th class="account_entries_table_header_actions">Actions</th>
                </tr>
              </thead>
              <tbody v-if="allEntries.length > 0">
                <template v-for="(entry, index) in allEntries" :key="index">
                  <template
                    v-if="entry && entry.particulars && entry.particulars.accounts && Array.isArray(entry.particulars.accounts) && entry.particulars.accounts.length > 0">
                    <tr :class="[
                      'account_entries_table_row',
                      { 'even-entry': index % 2 === 0 },
                    ]" @mouseover="hoveredRowIndex = index" @mouseleave="hoveredRowIndex = null">
                      <!-- Date, Entry #, Voucher Type, Status, and Actions span all account rows and description -->
                      <td class="account_entries_table_date" :rowspan="entry.particulars.accounts.length + 1"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ dayjs(entry.date).format('DD-MM-YYYY') || "N/A" }}
                      </td>
                      <td class="account_entries_table_entry" :rowspan="entry.particulars.accounts.length + 1"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ entry.entry || "N/A" }}<br />
                        <span @click="openDetailsDialog(entry)">View Details</span>
                      </td>
                      <td class="account_entries_table_voucher" :rowspan="entry.particulars.accounts.length + 1"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ entry.voucher_type || "N/A" }}
                      </td>
                      <!-- First account row -->
                      <td class="account_entries_table_particulars"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ entry.particulars.accounts[0]?.title || "N/A" }}
                      </td>
                      <td class="account_entries_table_debit account_primary_color">
                        {{ entry.particulars.accounts[0]?.debit || "" }}
                      </td>
                      <td class="account_entries_table_credit account_error_color">
                        {{ entry.particulars.accounts[0]?.credit || "" }}
                      </td>
                      <td class="account_entries_table_status" :rowspan="entry.particulars.accounts.length + 1">
                        <VChip class="account_v_chip"
                          :class="entry.status === 'Pending' ? 'account_chip_error' : 'account_chip_primary'"
                          size="small">
                          {{ entry.status || "N/A" }}
                        </VChip>
                      </td>
                      <td class="account_entries_table_actions" :rowspan="entry.particulars.accounts.length + 1">
                        <div class="d-flex align-center justify-center gap-2">
                          <VBtn size="small" class="account_v_btn_ghost" variant="text" @click="openEditModal(entry)">
                            <IconPencil size="20" />
                          </VBtn>
                          <VBtn size="small" class="account_v_btn_ghost" variant="text"
                            @click="updateEntryStatus(entry, 'approved')" v-if="entry.status === 'Pending'">
                            <IconArrowBackUp size="20" />
                          </VBtn>
                          <VBtn size="small" class="account_v_btn_ghost" variant="text" @click="confirmDelete(entry)">
                            <IconTrash size="20" />
                          </VBtn>
                        </div>
                      </td>
                    </tr>
                    <!-- Additional account rows (if any) -->
                    <tr v-for="(account, accIndex) in entry.particulars.accounts.slice(1)" :key="`${index}-${accIndex}`"
                      :class="['account_entries_table_row', { 'even-entry-extension': index % 2 === 0 },]"
                      @mouseover="hoveredRowIndex = index" @mouseleave="hoveredRowIndex = null">
                      <td class="account_entries_table_particulars"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ account.title || "N/A" }}
                      </td>
                      <td class="account_entries_table_debit account_primary_color"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ account.debit || "" }}
                      </td>
                      <td class="account_entries_table_credit account_error_color"
                        :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        {{ account.credit || "" }}
                      </td>
                    </tr>
                    <!-- Description and Narration row -->
                    <tr :class="['account_entries_table_row', { 'even-entry-extension': index % 2 === 0 },]">
                      <td colspan="3" :class="{ 'hovered-cell': hoveredRowIndex === index }">
                        <div class="d-flex flex-column align-start justify-center">
                          <span class="account_entry_desc_text">(Narration: {{ entry.description || "N/A" }})</span>
                        </div>
                      </td>
                    </tr>
                  </template>
                </template>
              </tbody>
              <tbody v-else class="text-center border">
                No Data Available
              </tbody>
            </table>
          </div>
        </VCard>
      </VCardText>
    </VCard>


    <!-- Entry Details Dialog -->
    <VDialog v-model="showDetailsDialog" max-width="600" @click:outside="showDetailsDialog = false">
      <VCard class="account_vcard_border account_details_dialog" title="Journal Voucher"
        :subtitle="selectedEntry?.entry">
        <template #append>
          <VBtn variant="text" size="x-small" rounded="" @click="showDetailsDialog = false"
            class="account_vcard_close_btn">
            <IconX size="20" />
          </VBtn>
        </template>
        <VCardText>
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center gap-1">
              <span class="account_label_bold">Date:</span>
              <span class="account_label_light">{{ selectedEntry?.date }}</span>
            </div>
            <div class="d-flex align-center gap-1">
              <span class="account_label_bold">Type:</span>
              <span class="account_label_light">{{
                selectedEntry?.voucher_type
              }}</span>
            </div>
          </div>

          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-1">
              <span class="account_label_bold">Created By:</span>
              <span class="account_label_light">Admin</span>
            </div>
            <div class="">
              <VChip class="account_chip_primary" size="small" :text="selectedEntry?.status" />
            </div>
          </div>

          <VDivider class="my-2" />

          <VCard class="account_vcard_border shadow-none account_entries_table mt-2">
            <VTable class="">
              <thead>
                <tr>
                  <th>Particulars</th>
                  <th class="text-right">Debit</th>
                  <th class="text-right">Credit</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(acc, i) in selectedEntry?.particulars?.accounts" :key="i">
                  <tr>
                    <td :class="{ 'pl-9': i !== 0 }">{{ acc.title }}</td>
                    <td class="text-success text-right">
                      {{ acc.debit || "" }}
                    </td>
                    <td class="text-error text-right">
                      {{ acc.credit || "" }}
                    </td>
                  </tr>
                </template>
                <tr class="font-weight-bold">
                  <td>Total</td>
                  <td class="text-success text-right">
                    {{
                      totalAmount(selectedEntry.particulars.accounts, "debit")
                    }}
                  </td>
                  <td class="text-error text-right">
                    {{
                      totalAmount(selectedEntry.particulars.accounts, "credit")
                    }}
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>

          <div class="d-flex align-center gap-1 mt-3 mb-2">
            <span class="account_label_bold abc">Narration:</span>
            <span class="account_label_light font-italic">{{
              selectedEntry?.particulars?.description?.narration || "N/A"
            }}</span>
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <VBtn @click="openCreateModal" :key="bounceKey" class="account_add_new_btn bounce">
      <template #prepend>
        <IconCirclePlus size="18" />
      </template>
      New Journal Entry
    </VBtn>
  </div>
</template>

<style scoped>
@keyframes bounce {

  0%,
  100% {
    transform: translateY(0);
  }

  20% {
    transform: translateY(-12px);
  }

  40% {
    transform: translateY(0);
  }

  60% {
    transform: translateY(-6px);
  }

  80% {
    transform: translateY(0);
  }
}

.bounce {
  animation: bounce 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
<style scoped>
/* Custom border color for the HTML table with class account_entries_table */
.account_entries_table {
  border-collapse: collapse;
  inline-size: 100%;
}

.account_entries_table th,
.account_entries_table td {
  padding: 8px;
  border: 1.5px solid var(--acc-border-color) !important;
}

.account_entries_table tr {
  border-block-end: 1.5px solid var(--acc-border-color) !important;
}
</style>
