<template>
  <VMenu>
    <template v-slot:activator="{ props }">
      <VBtn v-bind="props" variant="outlined" color="primary" v-tooltip="'Lead Actions'" size="small"
        icon="tabler-files" />
    </template>
    <VList class="box_shadow">
      <VListItem class="mx-0" v-if="$can('leads', 'export-list')">
        <VBtn prepend-icon="tabler-download" @click="exportLeads" variant="outlined" size="small"
          color="primary">
          Export
        </VBtn>
      </VListItem>
      <VListItem class="mx-0" v-if="$can('leads', 'create')">
        <VBtn prepend-icon="tabler-upload" @click="$refs.fileInput.click()" variant="outlined" size="small"
          color="primary">
          Upload</VBtn>
        <input ref="fileInput" type="file" accept=".xls,.xlsx" style="display: none;"
          @change="handleFileImport" />
      </VListItem>
      <VListItem class="mx-0" v-if="$can('leads', 'export-list')">
        <VBtn prepend-icon="tabler-download" @click="downloadSampleExcel" variant="outlined" size="small"
          color="secondary">
          Sample</VBtn>
      </VListItem>
    </VList>
  </VMenu>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['export-leads', 'import-file', 'download-sample'])
const fileInput = ref(null)

const exportLeads = () => {
  emit('export-leads')
}

const handleFileImport = (event) => {
  const file = event.target.files[0]
  if (file) {
    emit('import-file', file)
  }
  // Reset the input so the same file can be selected again
  event.target.value = ''
}

const downloadSampleExcel = () => {
  emit('download-sample')
}
</script>