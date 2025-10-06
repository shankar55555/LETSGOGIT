<template>
  <VCard>
    <div class="d-flex justify-space-between mb-6">
      <h4 class="text-h5 text-center">Sync Headers</h4>
      <VIcon color="error" icon="tabler-circle-x" rounded="8" variant="tonal" size="large"
        @click="CloseTableHeaderDragVisible" />
    </div>
    <!-- <VRow no-gutters>
                <VCol cols="3" v-for="(item, index) in parsedHeaders" :key="item.key"
                    class="rounded-md hover:bg-gray-100 filterBtnListItem d-flex align-center justify-space-between border"
                    draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent
                    @drop="onDrop($event, index)">
                    <VCheckbox v-model="item.checked" :label="item.title" class="mr-2" @change="toggleHeader(item)" />
                    <VIcon icon="tabler-grip-vertical" />
                </VCol>
            </VRow> -->
    <div class="d-flex align-center gap-4 gap-x-9 flex-wrap">
      <div v-for="(item, index) in parsedHeaders" :key="item.key"
        class="rounded-md hover:bg-gray-100 filterBtnListItem d-flex align-center justify-space-between border draggable-icon"
        draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent @drop="onDrop($event, index)">
        <VCheckbox v-model="item.checked" :label="item.title" class="mr-2" @change="toggleHeader(item)" />
        <VIcon icon="tabler-grip-vertical" />
      </div>
    </div>
  </VCard>
  <!-- <div>
        <VRow>
            <VCol cols="3" v-for="(item, index) in parsedHeaders" :key="item.key"
                class="rounded-md hover:bg-gray-100 filterBtnListItem d-flex align-center justify-space-between border"
                draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent
                @drop="onDrop($event, index)">
                <VCheckbox v-model="item.checked" :label="item.title" class="mr-2" @change="toggleHeader(item)" />
                <VIcon icon="tabler-grip-vertical" />
            </VCol>
        </VRow>
    </div> -->
</template>

<script setup>
import { computed, defineEmits, defineProps, onMounted, ref } from "vue";
import { toast } from "vue3-toastify";

const props = defineProps({
  slug: { type: String, required: true },
});

const emit = defineEmits(["filterHeaderValue", "tableHeaderDragVisible"]);

const CloseTableHeaderDragVisible = () => {
  emit("tableHeaderDragVisible", false)
}

const headers = ref([]);
const draggedIndex = ref(null);

// Computed property to parse headers properly
const parsedHeaders = computed(() => {
  try {
    return typeof headers.value === "string" ? JSON.parse(headers.value) : headers.value;
  } catch (error) {
    return [];
  }
});

const fetchHeaders = async () => {
  try {
    const response = await $api("/table-header/get", { params: { slug: props.slug } });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to load headers.");
  }
};

const toggleHeader = async (header) => {
  try {
    let headerList = parsedHeaders.value.filter(item => {
      return item.key === header.key ? { ...item, checked: !item.checked } : item;
    });
    const response = await $api(`/table-header/save`, { method: 'POST', body: { slug: props.slug, header_list: headerList }, });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to update headers.");
  }
};

const tableHeaderSync = async () => {
  try {
    const response = await $api("/table-header/sync", { method: 'POST', body: { slug: props.slug } });
    headers.value = response.data?.headers ?? [];
    emit("filterHeaderValue", parsedHeaders.value);
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to load headers.");
  }
};

// Drag-and-drop handlers
const onDragStart = (event, index) => {
  draggedIndex.value = index;
  event.dataTransfer.effectAllowed = "move";
  event.dataTransfer.setData("text/plain", index);
};

const onDrop = async (event, dropIndex) => {
  event.preventDefault();
  if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;

  const newHeaders = [...parsedHeaders.value];

  // Swap the two items
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


onMounted(() => { fetchHeaders() });
</script>

<style scoped>
.filterBtnListItem {
  min-inline-size: 300px;
}

.draggable-icon {
  cursor: grab;
}

.draggable-icon:active {
  cursor: grabbing;
}
</style>
