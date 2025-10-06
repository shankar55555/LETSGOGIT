<script setup>
import { IconChevronDown, IconChevronRight, IconDots, IconFileText, IconFolder, IconPencil, IconTrash } from '@tabler/icons-vue';
import { computed, onMounted, ref } from "vue";
import { VChip, VList, VListItem, VListItemTitle, VMenu } from "vuetify/components";
import TreeItem from "./TreeItem.vue";

const props = defineProps({
  node: Object,
  level: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['edit', 'delete']);

const expanded = ref(false);
const menu = ref(false);
const isHovered = ref(false);

function toggle() {
  if (props.node.children) {
    expanded.value = !expanded.value;
  }
}

function onEdit() {
  console.log("Edit:", props.node);
  menu.value = true;
  emit("edit", props.node);
  console.log(menu.value);
}

function onDelete() {
  console.log("Delete:", props.node);
  menu.value = true;
  emit("delete", props.node);
}

// Dynamic class for chip
const typeClass = computed(() => {
  return props.node.type === "Balance Sheet"
    ? "account_balance_chip"
    : "account_profit_loss_chip";
});

// Determine if node is a ledger (no children)
const isLedger = computed(() => {
  return (
    props.node.children === null ||
    (props.node.children && props.node.children.length === 0 && !props.node.children)
  );
});

onMounted(() => {
  if (props.node.children && props.node.children.length > 0) {
    expanded.value = true;
  }
});

</script>

<template>
  <!-- Apply padding based on level -->
  <div class="mb-1" :style="{ paddingLeft: `${props.level * 16}px` }" @mouseenter="isHovered = true"
    @mouseleave="isHovered = false">
    <div class="d-flex align-center justify-space-between pa-1 tree-item-row" @click="toggle"
      :class="{ 'tree-item-hovered': isHovered }">
      <div class="d-flex align-center gap-2">
        <div v-if="!isLedger" style=" block-size: 16px;inline-size: 16px;" class="">
          <IconChevronDown v-if="props.node.children && expanded" size="16" />
          <IconChevronRight v-else-if="props.node.children" size="16" />
        </div>
        <IconFileText v-if="isLedger" class="account_ledger_icon" size="16" />
        <IconFolder v-else class="account_folder_icon" size="16" />
        <div class="">
          <h6 :class="[
            props.level === 0 ? 'expansion_base_parent_title' : 'expansion_node_title',
            'mb-0',
          ]">
            {{ props.node.name }}
          </h6>
        </div>
      </div>
      <div class="d-flex align-center gap-2" @click.stop>
        <VChip v-if="!isLedger" :class="typeClass" size="small">
          {{ props.node.type }}
        </VChip>
        <div class="more_options_w">
          <VMenu v-model="menu" :close-on-content-click="false" class="account_vmenu_border" location="bottom end">
            <template #activator="{ props: menuProps }">
              <IconDots v-if="isHovered || menu" size="16" v-bind="menuProps" />
            </template>
            <VList class="account_expansion_list">
              <VListItem @click="onEdit">
                <VListItemTitle class="d-flex align-center gap-3">
                  <IconPencil size="18" />
                  <p class="mb-0">Edit</p>
                </VListItemTitle>
              </VListItem>
              <VListItem @click="onDelete">
                <VListItemTitle class="d-flex trash align-center gap-3">
                  <IconTrash size="18" />
                  <p class="mb-0">Delete</p>
                </VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </div>
    </div>

    <!-- Recursive Children with incremented level -->
    <div v-if="expanded">
      <TreeItem v-for="child in props.node.children" :key="child.id" :node="child" :level="props.level + 1"
        @edit="emit('edit', $event)" @delete="emit('delete', $event)" />
    </div>
  </div>
</template>

<style scoped>
.tree-item-row {
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.tree-item-hovered {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.tree-item-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.08);
}
</style>
