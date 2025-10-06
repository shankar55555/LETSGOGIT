<template>
  <VDialog v-model="props.resultDialogVisible"  :fullscreen="mdAndDown"
  :max-width="mdAndDown ? '100%' : '700px'" scrollable persistent>
    <VCard>
      <VCardTitle class="text-h6 d-flex justify-space-between">
        Import Result
        <VIcon icon="tabler-x" class="cursor-pointer" @click="emit('update:resultDialogVisible', false)" />
      </VCardTitle>
      <VDivider />

      <VCardText>
        <div v-if="props.importResult.failed.length">
          <h3 class="text-subtitle-1 mb-2">❌ Failed Entries</h3>
          <VTable>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in props.importResult.failed" :key="item.email">
                <td>{{ item.name }}</td>
                <td>{{ item.email }}</td>
                <td>{{ item.contact_no }}</td>
                <td>{{ item.message }}</td>
              </tr>
            </tbody>
          </VTable>
        </div>

        <div v-if="props.importResult.duplicates.length" class="mt-6">
          <h3 class="text-subtitle-1 mb-2">⚠️ Duplicate Entries</h3>
          <VTable>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in props.importResult.duplicates" :key="item.email">
                <td>{{ item.name }}</td>
                <td>{{ item.email }}</td>
                <td>{{ item.contact_no }}</td>
                <td>{{ item.message }}</td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn variant="outlined" @click="downloadFailedUsersCsv('duplicate')"  v-if="props.importResult.duplicates.length">
          Download Duplicate Users Csv
        </VBtn>
        <VBtn variant="outlined" @click="downloadFailedUsersCsv('failed')"  v-if="props.importResult.failed.length">
          Download Failed Users Csv
        </VBtn>
        <VBtn variant="elevated" @click="emit('update:resultDialogVisible', false)">
          Close
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

</template>

<script setup>
import { useDisplay } from 'vuetify';
const { mdAndDown } = useDisplay();
const props = defineProps({
  importResult: { type: Object, required: true, },
  resultDialogVisible: { type: Boolean, required: true, },
})

const emit = defineEmits(['update:resultDialogVisible', 'clearImportResult',])

const downloadFailedUsersCsv = (type) => {
  const rows = [];

  // Headers
  rows.push(['Name', 'Email', 'Contact', 'Message']);

  let file_name = '';

  if (type === 'failed') {
    file_name = 'failed_users.csv';
    props.importResult.failed.forEach(item => {
      rows.push([item.name, item.email, item.contact_no, item.message || '']);
    });
  }

  if (type === 'duplicate') {
    file_name = 'duplicate_users.csv';
    props.importResult.duplicates.forEach(item => {
      rows.push([item.name, item.email, item.contact_no, item.message || 'Duplicate Entry']);
    });
  }

  // Convert to CSV
  const csvContent = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', file_name);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

</script>
