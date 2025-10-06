<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <h4 class="text-h5">Sync Headers</h4>
      <div class="d-flex align-center gap-2">
        <VBtn size="small" color="success" variant="tonal" prepend-icon="tabler-refresh" @click="tableHeaderSync">
          Sync
        </VBtn>
        <VBtn size="small" :color="allSelected ? 'error' : 'primary'" variant="tonal"
          :prepend-icon="allSelected ? 'tabler-deselect' : 'tabler-select-all'" @click="toggleAll">
          {{ allSelected ? 'Deselect All' : 'Select All' }}
        </VBtn>
        <VBtn size="small" color="secondary" variant="tonal" prepend-icon="tabler-circle-x" @click="emit('close')">
          Close
        </VBtn>
      </div>
    </div>

    <div class="d-flex align-center gap-2 gap-x-9 flex-wrap">
      <div v-for="(item, index) in parsedHeaders" :key="item.key"
        class="rounded-md hover:bg-gray-100 filterBtnListItem d-flex align-center justify-space-between border draggable-icon pa-2"
        draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent @drop="onDrop($event, index)">
        <VCheckbox v-model="item.checked" :label="item.title" class="mr-2" hide-details density="comfortable"
          @change="toggleHeader(item)" />
        <VIcon icon="tabler-grip-vertical" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineEmits, defineProps, onMounted, ref } from "vue";
import { toast } from "vue3-toastify";

const props = defineProps({
  slug: { type: String, required: true },
});

const emit = defineEmits(["filterHeaderValue", "close"]);
const headers = ref([]);
const draggedIndex = ref(null);

// Parse headers to a usable array
const parsedHeaders = computed(() => {
  try {
    return typeof headers.value === "string" ? JSON.parse(headers.value) : headers.value;
  } catch (error) {
    return [];
  }
});

const allSelected = computed(() => parsedHeaders.value.length > 0 && parsedHeaders.value.every(h => h.checked));

// Fetch headers from server
const fetchHeaders = async () => {
  try {
    const response = await $api("/table-header/get", { params: { slug: props.slug } });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to load headers.");
  }
};

// Select/Deselect all and persist
const toggleAll = async () => {
  try {
    const newValue = !allSelected.value;
    const updated = parsedHeaders.value.map(item => ({ ...item, checked: newValue }));
    const response = await $api(`/table-header/save`, {
      method: 'POST',
      body: { slug: props.slug, header_list: updated },
    });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to update headers.");
  }
};

// Toggle single header visibility and save immediately
const toggleHeader = async (header) => {
  try {
    const updated = parsedHeaders.value.map(item => item.key === header.key ? { ...item, checked: header.checked } : item);
    const response = await $api(`/table-header/save`, {
      method: 'POST',
      body: { slug: props.slug, header_list: updated },
    });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to update headers.");
  }
};

// Drag and drop reordering
const onDragStart = (event, index) => {
  draggedIndex.value = index;
  event.dataTransfer.effectAllowed = "move";
  event.dataTransfer.setData("text/plain", index);
};

const onDrop = async (event, dropIndex) => {
  event.preventDefault();
  if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;

  const newHeaders = [...parsedHeaders.value];
  const temp = newHeaders[draggedIndex.value];
  newHeaders[draggedIndex.value] = newHeaders[dropIndex];
  newHeaders[dropIndex] = temp;

  headers.value = newHeaders;

  try {
    const response = await $api("/table-header/save", {
      method: "POST",
      body: { slug: props.slug, header_list: newHeaders },
    });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to save header order.");
  } finally {
    draggedIndex.value = null;
  }
};

// Sync remote defaults
const tableHeaderSync = async () => {
  try {
    const response = await $api("/table-header/sync", { method: 'POST', body: { slug: props.slug } });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to sync headers.");
  }
};

onMounted(() => { fetchHeaders() });
</script>

<style scoped>
.filterBtnListItem {
  min-inline-size: 280px;
}

.draggable-icon {
  cursor: grab;
}

.draggable-icon:active {
  cursor: grabbing;
}
</style>
