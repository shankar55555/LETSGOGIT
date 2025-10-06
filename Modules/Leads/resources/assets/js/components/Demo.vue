<template>
  <div class="bg-gray-50 min-h-screen flex flex-col">
    <div class="max-w-7xl mx-auto w-full px-4 py-8">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Data Table View</h1>
        <p class="text-gray-600">Toggle between normal and compact views</p>
      </div>

      <!-- Toggle Buttons -->
      <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex items-center">
          <span class="text-gray-700 font-medium mr-3">Table View:</span>
          <div class="inline-flex rounded-md shadow-sm" role="group">
            <button
              :class="[
                'view-toggle px-4 py-2 text-sm font-medium rounded-l-lg flex items-center transition-all duration-300',
                isNormalView ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700',
                isNormalView ? 'hover:bg-primary-dark' : 'hover:bg-gray-300'
              ]"
              @click="toggleView('normal')"
            >
              <i class="fas fa-expand mr-2"></i>
              <span class="button-text">Normal View</span>
            </button>
            <button
              :class="[
                'view-toggle px-4 py-2 text-sm font-medium rounded-r-lg flex items-center transition-all duration-300',
                !isNormalView ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700',
                !isNormalView ? 'hover:bg-primary-dark' : 'hover:bg-gray-300'
              ]"
              @click="toggleView('compact')"
            >
              <i class="fas fa-compress mr-2"></i>
              <span class="button-text">Compact View</span>
            </button>
          </div>
        </div>

        <div class="flex items-center text-gray-600">
          <i class="fas fa-info-circle mr-2"></i>
          <span class="text-sm">Click headers to sort data</span>
        </div>
      </div>

      <!-- Table Container -->
      <div class="table-container bg-white rounded-xl shadow-md overflow-hidden">
        <table :class="['w-full table-transition', isNormalView ? 'normal-view' : 'compact-view']">
          <thead class="bg-gray-100">
            <tr>
              <th
                v-for="header in headers"
                :key="header.key"
                class="text-left cursor-pointer hover:bg-gray-200 transition-colors duration-150 py-3 px-4 text-gray-700 font-semibold"
                :data-sort="header.key"
                @click="sortTable(header.key)"
              >
                <div class="flex items-center justify-between">
                  <span>{{ header.label }}</span>
                  <i :class="['fas ml-1 text-gray-500', getSortIcon(header.key)]"></i>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, index) in sortedData"
              :key="row.id"
              :class="['border-t border-gray-200', index % 2 === 0 ? 'hover:bg-gray-50' : 'bg-gray-50 hover:bg-gray-100']"
            >
              <td class="py-3 px-4">{{ row.id }}</td>
              <td class="py-3 px-4 font-medium">{{ row.name }}</td>
              <td class="py-3 px-4">{{ row.department }}</td>
              <td class="py-3 px-4">{{ row.position }}</td>
              <td class="py-3 px-4">{{ row.email }}</td>
              <td class="py-3 px-4">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs',
                    row.status === 'Active' ? 'bg-green-100 text-green-800' : 
                    row.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                    'bg-red-100 text-red-800'
                  ]"
                >
                  {{ row.status }}
                </span>
              </td>
              <td class="py-3 px-4">{{ row.joinDate }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Stats Footer -->
      <div class="mt-4 flex flex-col sm:flex-row justify-between items-center text-sm text-gray-600">
        <div class="mb-2 sm:mb-0">
          Showing <span class="font-medium">1 to {{ tableData.length }}</span> of <span class="font-medium">{{ tableData.length }}</span> entries
        </div>
        <div class="flex items-center">
          <span class="mr-3">View:</span>
          <span class="font-medium">{{ tableData.length }} rows</span>
          <span class="mx-2">|</span>
          <span>{{ headers.length }} columns</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Table headers configuration
const headers = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'department', label: 'Department' },
  { key: 'position', label: 'Position' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' },
  { key: 'joinDate', label: 'Join Date' }
]

// Table data
const tableData = ref([
  { id: 101, name: 'John Smith', department: 'Marketing', position: 'Manager', email: 'john.smith@example.com', status: 'Active', joinDate: '2020-05-15' },
  { id: 102, name: 'Sarah Johnson', department: 'Finance', position: 'Analyst', email: 'sarah.j@example.com', status: 'Active', joinDate: '2019-11-22' },
  { id: 103, name: 'Michael Chen', department: 'Engineering', position: 'Developer', email: 'm.chen@example.com', status: 'Pending', joinDate: '2023-01-10' },
  { id: 104, name: 'Emma Wilson', department: 'HR', position: 'Specialist', email: 'emma.w@example.com', status: 'Active', joinDate: '2018-08-05' },
  { id: 105, name: 'David Brown', department: 'Sales', position: 'Director', email: 'david.b@example.com', status: 'Active', joinDate: '2017-03-12' },
  { id: 106, name: 'Lisa Taylor', department: 'Marketing', position: 'Coordinator', email: 'lisa.t@example.com', status: 'Inactive', joinDate: '2022-06-30' },
  { id: 107, name: 'Robert Garcia', department: 'Engineering', position: 'Lead', email: 'robert.g@example.com', status: 'Active', joinDate: '2021-09-18' },
  { id: 108, name: 'Olivia Martinez', department: 'Finance', position: 'Manager', email: 'olivia.m@example.com', status: 'Active', joinDate: '2020-02-14' },
  { id: 109, name: 'Thomas Lee', department: 'Operations', position: 'Supervisor', email: 'thomas.l@example.com', status: 'Pending', joinDate: '2023-03-01' },
  { id: 110, name: 'Sophia Clark', department: 'IT', position: 'Administrator', email: 'sophia.c@example.com', status: 'Active', joinDate: '2019-07-24' }
])

// View state
const isNormalView = ref(true)

// Sorting state
const sortKey = ref('')
const sortOrder = ref(1) // 1 for ascending, -1 for descending

// Toggle view function
const toggleView = (view) => {
  isNormalView.value = view === 'normal'
}

// Sort table function
const sortTable = (key) => {
  if (sortKey.value === key) {
    sortOrder.value *= -1
  } else {
    sortKey.value = key
    sortOrder.value = 1
  }
}

// Computed sorted data
const sortedData = computed(() => {
  if (!sortKey.value) return tableData.value

  return [...tableData.value].sort((a, b) => {
    const valueA = a[sortKey.value]
    const valueB = b[sortKey.value]

    if (typeof valueA === 'string') {
      return valueA.localeCompare(valueB) * sortOrder.value
    } else {
      return (valueA - valueB) * sortOrder.value
    }
  })
})

// Get sort icon class
const getSortIcon = (key) => {
  if (sortKey.value !== key) return 'fa-sort'
  return sortOrder.value === 1 ? 'fa-sort-up' : 'fa-sort-down'
}
</script>

<style scoped>
.table-transition {
  transition: all 0.3s ease;
}

.compact-view td,
.compact-view th {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.normal-view td,
.normal-view th {
  padding: 0.75rem 1rem;
  font-size: 1rem;
  line-height: 1.5rem;
}

.table-container {
  overflow-x: auto;
}

@media (max-width: 640px) {
  .button-text {
    display: none;
  }
}
</style>
