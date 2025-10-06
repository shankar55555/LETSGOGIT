<template>
  <!-- Rule Management Modal -->
  <VDialog max-width="1300" :model-value="props.isDrawerOpen" @update:model-value="updateModelValue" scrollable
    persistent>
    <VCard>
      <!-- Header -->
      <VCardItem>
        <VRow align="center" justify="space-between">
          <VCol cols="6">
            <h4 class="text-h5 font-weight-medium">
              {{ props.currentInfo ? 'Edit Rule' : 'Add New Rule' }}
            </h4>
          </VCol>
          <VCol cols="6" class="d-flex justify-end">
            <IconBtn @click="updateModelValue(false)" icon="tabler-x" color="default" />
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider class="my-1" />

      <!-- Form Body -->
      <VCardText class="pt-1">
        <VForm ref="refForm" v-model="valid" @submit.prevent="onSubmit">
          <VRow class="align-center pa-2">
            <!-- Rule Name Input -->
            <VCol cols="12" lg="3">
              <AppTextField v-model="currentItem.rule" :rules="[requiredValidator]" placeholder="Enter rule"> <template
                  v-slot:label><span>Rule <span style="color: red;">*</span></span> </template>
              </AppTextField>
            </VCol>

            <!-- ====================== CONDITIONS ====================== -->
            <VCol cols="12" lg="12">
              <v-chip color="primary" class="mb-2">Conditions</v-chip>

              <!-- Loop through condition items -->
              <VRow v-for="(cond, index) in currentItem.conditions" :key="index" class="align-center pa-2">
                <!-- Module -->
                <VCol cols="12" lg="2">
                  <AppSelect :disabled="index !== 0" v-model="cond.module" :items="getModuleList"
                    :rules="[requiredValidator]" @update:modelValue="resetForm(index, cond.module)"> <template
                      v-slot:label><span>Module <span style="color: red;">*</span></span> </template></AppSelect>
                </VCol>

                <!-- Trigger Event -->
                <VCol cols="12" lg="3">
                  <AppSelect v-model="cond.trigger_event"
                    :items="filteredAttributes(cond.module, cond.trigger_event, index)" :rules="[requiredValidator]"
                    @update:modelValue="() => {
                      resetForm(index, cond.module, cond.trigger_event);
                      haveCondition(index);
                    }"> <template v-slot:label><span>Trigger Event <span style="color: red;">*</span></span>
                    </template></AppSelect>
                </VCol>

                <!-- Conditional block if Trigger Event is status -->
                <template v-if="cond.trigger_event === RULE_STATUS_TRIGGER">
                  <!-- Status Trigger -->
                  <VCol cols="12" lg="3">
                    <AppSelect v-model="cond.action_status"
                      :items="filteredStatusAttributes(cond.module, cond.trigger_event, 'status')"
                      item-title="status_text" item-value="id" :rules="[requiredValidator]" @update:modelValue="() => {
                        resetForm(index, cond.module, cond.trigger_event, cond.action_status);
                        haveCondition(index);
                      }"> <template v-slot:label><span>Status Trigger <span style="color: red;">*</span></span>
                      </template>
                    </AppSelect>
                  </VCol>

                  <!-- Status Condition -->
                  <VCol cols="12" lg="3" v-if="cond.action_status">
                    <AppSelect v-model="cond.condition"
                      :items="filteredStatusAttributes(cond.module, cond.trigger_event, 'condition')"
                      :rules="[requiredValidator]" @update:modelValue="() => {
                        resetForm(index, cond.module, cond.trigger_event, cond.action_status, cond.condition);
                        haveCondition(index);
                      }"> <template v-slot:label><span>Condition <span style="color: red;">*</span></span> </template>
                    </AppSelect>
                  </VCol>
                </template>

                <!-- Other Conditional Fields -->
                <template
                  v-if="(cond.allow_condition && cond.trigger_event !== RULE_STATUS_TRIGGER) || cond.condition === 'condition'">
                  <!-- Control Operator -->
                  <VCol cols="12" lg="3">
                    <AppSelect v-model="cond.operator" :items="filterControls(index)" clearable>
                      <template v-slot:label><span>Control <span style="color: red;">*</span></span> </template>
                    </AppSelect>
                  </VCol>

                  <!-- Data Type -->
                  <VCol cols="12" lg="2">
                    <AppSelect v-model="cond.datatype" :items="filterDataTypes(index)" clearable>
                      <template v-slot:label><span>Data Type <span style="color: red;">*</span></span> </template>
                    </AppSelect>
                  </VCol>

                  <!-- Data Type -->
                  <VCol cols="12" lg="2">
                    <AppSelect v-model="cond.field" :items="filterFields(index)" clearable>
                      <template v-slot:label><span>Field <span style="color: red;">*</span></span> </template>
                    </AppSelect>
                  </VCol>

                  <!-- Value -->
                  <VCol cols="12" lg="1">
                    <AppTextField v-model="cond.value" @input="cond.value = cond.value.replace(/[^0-9]/g, '')">
                      <template v-slot:label><span>Value <span style="color: red;">*</span></span> </template>
                    </AppTextField>
                  </VCol>
                </template>

                <!-- AND/OR Logic Selector -->
                <VCol cols="12" lg="2" v-if="index === 0 && currentItem.conditions.length >= 2">
                  <AppSelect v-model="currentItem.condition_type" :items="['AND', 'OR']" :rules="[requiredValidator]">
                    <template v-slot:label><span>Logic <span style="color: red;">*</span></span> </template>
                  </AppSelect>
                </VCol>

                <!-- Add/Remove Buttons -->
                <VCol cols="12" lg="1">
                  <IconBtn class="mt-4" color="primary" v-if="index === 0" @click="addConditionRow()"
                    :disabled="!canAddNewConditionRow() || currentItem.conditions.length >= 2">
                    <VIcon icon="tabler-plus" />
                  </IconBtn>

                  <IconBtn class="mt-4" color="error" v-if="index > 0" @click="removeConditionRow(index)">
                    <VIcon icon="tabler-minus" />
                  </IconBtn>
                </VCol>
              </VRow>
            </VCol>

            <!-- ====================== ACTIONS ====================== -->
            <VCol cols="12" lg="12">
              <v-chip color="primary" class="mb-2">Actions</v-chip>

              <VRow v-for="(act, index) in currentItem.actions" :key="index" class="align-center pa-2">
                <!-- Action Type -->
                <VCol cols="12" lg="3">
                  <AppSelect v-model="act.action_type" :items="getActionList()" :rules="[requiredValidator]"
                    @update:modelValue="resetActionForm(index, act.action_type)"> <template v-slot:label><span>Action
                        Type <span style="color: red;">*</span></span> </template></AppSelect>
                </VCol>

                <!-- SEND_NOTIFICATION action -->
                <template
                  v-if="(act.action_type === ACTION_SEND_NOTIFICATION || SEND_PLAT_FROM.includes(act.action_type))">
                  <VCol cols="12" lg="3" v-if="getMetaList(index, act.action_type, 'templates').length > 0">
                    <AppSelect v-model="act.template_id" :items="getMetaList(index, act.action_type, 'templates')"
                      item-title="title" item-value="id">
                      <template v-slot:label>
                        <span>Select Template <span style="color: red;">*</span></span>
                      </template>
                    </AppSelect>
                  </VCol>

                  <VCol cols="12" lg="2">
                    <AppSelect v-model="act.recipients" :items="getMetaList(index, act.action_type, 'recipient_list')"
                      multiple clearable :rules="[requiredValidator]">
                      <template v-slot:label>
                        <span>Recipients <span style="color: red;">*</span></span>
                      </template>
                    </AppSelect>
                  </VCol>

                  <VCol cols="12" lg="3">
                    <AppSelect v-model="act.notification_methods"
                      :items="getMetaList(index, act.action_type, 'notification_methods')" multiple clearable
                      :rules="[requiredValidator]">
                      <template v-slot:label>
                        <span>Notification Method <span style="color: red;">*</span></span>
                      </template>
                    </AppSelect>
                  </VCol>
                </template>

                <!-- CHANGE_STATUS action -->
                <template v-if="act.action_type === ACTION_CHANGE_STATUS">
                  <VCol cols="12" lg="4">
                    <AppSelect v-model="act.status_id" :items="getStatusList(act.action_type, index)"
                      item-title="status_text" item-value="id" clearable :rules="[requiredValidator]"> <template
                        v-slot:label><span>Change Status <span style="color: red;">*</span></span> </template>
                    </AppSelect>
                  </VCol>
                </template>

                <!-- APPEND_NOTE action -->
                <template v-if="act.action_type === ACTION_APPEND_NOTE">
                  <VCol cols="12" lg="4">
                    <v-textarea v-model="act.note" placeholder="Enter Note" rows="1" auto-grow
                      :rules="[requiredValidator]">
                      <template v-slot:label><span>Note <span style="color: red;">*</span></span>
                      </template></v-textarea>
                  </VCol>
                </template>

                <!-- Action Add/Remove Buttons -->
                <VCol cols="12" lg="1">
                  <IconBtn class="mt-4" color="primary" v-if="index === 0" @click="addActionRow()"
                    :disabled="!canAddNewActionRow() || !haveMoreAction()">
                    <VIcon icon="tabler-plus" />
                  </IconBtn>
                  <IconBtn class="mt-4" color="error" v-else @click="removeActionRow(index)">
                    <VIcon icon="tabler-minus" />
                  </IconBtn>
                </VCol>
              </VRow>
            </VCol>
          </VRow>

          <!-- ====================== SUBMIT BUTTONS ====================== -->
          <VRow>
            <VCol cols="12" class="d-flex align-center gap-2 justify-start"
              v-if="$can('rule', currentInfo ? 'edit' : 'create')">
              <VBtn type="submit" color="primary" :loading="isSubmitting" :disabled="isSubmitting">
                {{ currentInfo ? 'Update' : 'Save' }}
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { toast } from 'vue3-toastify';

// Props from parent
const props = defineProps({
  currentInfo: { type: [Object, null], default: null, required: false },
  isDrawerOpen: { type: Boolean, required: true },
});

// Form validation refs
const valid = ref(true);
const refForm = ref(null);

// Emits
const emit = defineEmits(['submit', 'update:isDrawerOpen']);

// Reactive rule list and current item
const ruleList = ref([]);
const currentItem = ref({
  rule: null,
  condition_type: "AND",
  conditions: [{
    module: null,
    trigger_event: null,
    allow_condition: false,
    action_status: '',
    condition: '',
    operator: '',
    datatype: '',
    value: '',
    field: '',
  }],
  actions: [{
    action_type: '',
    notification_methods: [],
    recipients: [],
    template_id: '',
    interval: 'Immediate',
    priority: 'High',
    status_id: '',
    note: '',
  }]
});

// ==============================
// 🧠 WATCHERS
// ==============================
// Watcher for when modal opens/closes
watch(() => props.isDrawerOpen, (newVal) => {
  if (newVal) {
    // If editing existing rule, populate form data
    if (props.currentInfo?.id) {
      currentItem.value = JSON.parse(JSON.stringify(props.currentInfo));

      // Parse actions JSON string to array
      try {
        const parsedAction = JSON.parse(props.currentInfo.actions);
        currentItem.value.actions = Array.isArray(parsedAction) ? parsedAction : [parsedAction];
      } catch (error) {
        console.error('Failed to parse actions:', error);
        currentItem.value.actions = [{
          action_type: '',
          notification_methods: [],
          recipients: [],
          template_id: '',
          interval: 'Immediate',
          priority: 'High',
          status_id: '',
          note: '',
        }];
      }

      // Parse conditions JSON string to array
      try {
        const parsedCondition = JSON.parse(props.currentInfo.conditions);
        currentItem.value.conditions = Array.isArray(parsedCondition) ? parsedCondition : [parsedCondition];
        // Ensure each condition has all required fields
        currentItem.value.conditions = currentItem.value.conditions.map(cond => ({
          module: cond.module || null,
          trigger_event: cond.trigger_event || null,
          allow_condition: cond.allow_condition || false,
          operator: cond.operator || '',
          datatype: cond.datatype || '',
          value: cond.value || '',
          action_status: cond.action_status || '',
          condition: cond.condition || '',
          field: cond.field || '',
        }));
      } catch (error) {
        console.error('Failed to parse conditions:', error);
        currentItem.value.conditions = [{
          module: null,
          trigger_event: null,
          allow_condition: false,
          operator: '',
          datatype: '',
          value: '',
          field: '',
          action_status: '',
          condition: '',
        }];
      }
    }
  }
}, { immediate: true });


// ==============================
// 📦 COMPUTED
// ==============================

const getModuleList = computed(() => ruleList.value.map(rule => rule.module));

// ==============================
// ✅ FORM HELPERS
// ==============================

// Returns empty condition structure
const getEmptyCondition = () => ({
  module: null,
  trigger_event: null,
  allow_condition: false,
  action_status: '',
  condition: '',
  operator: '',
  datatype: '',
  value: '',
  field: '',
});

// Returns empty action structure
const getEmptyAction = () => ({
  action_type: '',
  notification_methods: [],
  recipients: [],
  template_id: '',
  interval: 'Immediate',
  priority: 'High',
  status_id: '',
  note: ''
});

// ==============================
// 🔍 FILTER & LOOKUP HELPERS
// ==============================

// Get rule object by module name
const getRuleByModule = (module) => ruleList.value.find(rule => rule.module === module);

// Get event by module and trigger slug
const getTriggerEvent = (module, triggerSlug) => getRuleByModule(module)?.trigger_event?.find(ev => ev.slug === triggerSlug) || null;

// Get first condition object
const getFirstCondition = () => currentItem.value.conditions[0] || null;

// Filter trigger event options by module
const filteredAttributes = (module, trigger_event, conditionIndex) => {
  const rule = getRuleByModule(module);
  const events = rule?.trigger_event || [];
  return conditionIndex === 0
    ? events.map(ev => ({ title: ev.name, value: ev.slug }))
    : events.filter(ev => ev.allow_condition).map(ev => ({ title: ev.name, value: ev.slug }));
};

// Filter status list for conditions or actions
const filteredStatusAttributes = (module, trigger_event, type) => {
  const rule = getRuleByModule(module);
  const event = rule?.trigger_event?.find(ev => ev.slug === trigger_event);
  if (!event) return [];
  return type === 'condition' ? event.condition_list : (type === 'status' ? event.status_list : []);
};

// Set allow_condition boolean based on trigger
const haveCondition = (i) => {
  const condition = currentItem.value.conditions[i];
  const event = getTriggerEvent(condition.module, condition.trigger_event);
  condition.allow_condition = !!event?.allow_condition;
};

// Get control types for the condition
const filterControls = (i) => {
  const cond = currentItem.value.conditions[i];
  return getTriggerEvent(cond.module, cond.trigger_event)?.condition?.control || [];
};

// Get data types for the condition
const filterDataTypes = (i) => {
  const cond = currentItem.value.conditions[i];
  return getTriggerEvent(cond.module, cond.trigger_event)?.condition?.datatype || [];
};
// Get data types for the condition
const filterFields = (i) => {
  const cond = currentItem.value.conditions[i];
  return getTriggerEvent(cond.module, cond.trigger_event)?.condition?.fields || [];
};

// Return all available actions for selected trigger
const getActionList = () => {
  const cond = getFirstCondition();
  return getTriggerEvent(cond?.module, cond?.trigger_event)?.actionList?.map(act => ({ title: act.action, value: act.slug })) || [];
};

// Get status list based on action type
const getStatusList = (actionType, index) => {
  const cond = getFirstCondition();
  const event = getTriggerEvent(cond?.module, cond?.trigger_event);
  return event?.actionList?.find(act => act.slug === actionType)?.status_list || [];
};

// Get Template list
const getTemplateList = (i, action_type) => {
  if (SEND_PLAT_FROM.includes(action_type)) {
    return getActionMetaSend(i, action_type, 'templates');
  } else {
    return getActionMeta(i, 'templates');
  }
}

const getIntervalList = (index, action_type) => getMetaList(index, action_type, 'interval');
const getPriorityList = (index, action_type) => getMetaList(index, action_type, 'priority');

const getMetaList = (index, action_type, key) => {
  const cond = currentItem.value.conditions[index] || getFirstCondition() || null;
  if (!cond?.trigger_event) return [];

  const event = getTriggerEvent(cond.module, cond.trigger_event);
  if (!event?.actionList) return [];

  const targetAction = SEND_PLAT_FROM.includes(action_type)
    ? event.actionList.find(act => act.slug === action_type)
    : event.actionList.find(act => act.slug === ACTION_SEND_NOTIFICATION);

  if (!targetAction) return [];

  return targetAction[key] || [];
};

// Check if user can add another action row
const haveMoreAction = () => {
  const cond = getFirstCondition();
  return currentItem.value.actions.length < (getTriggerEvent(cond?.module, cond?.trigger_event)?.actionList?.length || 0);
};

// Validate all existing actions before allowing new one
const canAddNewActionRow = () => {
  return currentItem.value.actions?.every(action => {
    if (!action.action_type) return false;
    if (action.action_type === ACTION_SEND_NOTIFICATION) return action.notification_methods?.length && action.recipients?.length;
    if (action.action_type === ACTION_CHANGE_STATUS) return !!action.status_id;
    if (action.action_type === ACTION_APPEND_NOTE) return !!action.note;
    if (action.action_type === CONVERT_TO_LEAD) return !!action.action_type;
    if (action.action_type === CONVERT_TO_CLIENT) return !!action.action_type;
    return false;
  });
};

// ==============================
// ➕ FORM MODIFICATION METHODS
// ==============================

// Add new action row
const addActionRow = () => {
  currentItem.value.actions.push(getEmptyAction());
};

// Remove action row
const removeActionRow = (i) => {
  currentItem.value.actions.splice(i, 1);
};

// Check if new condition can be added
const canAddNewConditionRow = () => {
  return currentItem.value.conditions?.every(c =>
    c.module && c.trigger_event && c.operator && c.datatype && c.field && c.value !== '');
};

// Add a new condition row
const addConditionRow = () => {
  currentItem.value.conditions.push({ ...getEmptyCondition(), module: currentItem.value.conditions[0]?.module || null });
};

// Remove a condition row
const removeConditionRow = (i) => {
  currentItem.value.conditions.splice(i, 1);
  if (currentItem.value.conditions.length < 2) {
    currentItem.value.condition_type = "AND";
  }
};

// Reset full form (or partial condition)
const resetForm = (index = 0, module = null, trigger_event = null, action_status = null, condition = null) => {
  if (index === 0) {
    currentItem.value = {
      id: null,
      rule: currentItem.value.rule,
      condition_type: "AND",
      conditions: [{ ...getEmptyCondition(), module, trigger_event, action_status, condition }],
      actions: [getEmptyAction()],
    };
  } else if (index === 1) {
    currentItem.value.conditions[1] = { ...getEmptyCondition(), module, trigger_event, action_status, condition };
  }
};

// Reset specific action row based on type
const resetActionForm = (i = 0, type = null) => {
  const action = currentItem.value.actions[i];
  action.action_type = type;
  if (type === ACTION_SEND_NOTIFICATION || SEND_PLAT_FROM.includes(type)) {
    Object.assign(action, {
      notification_methods: [],
      recipients: [],
      template_id: '',
      interval: 'Immediate',
      priority: 'High'
    });
    delete action.status_id;
    delete action.note;
  } else if (type === ACTION_CHANGE_STATUS) {
    action.status_id = '';
    delete action.notification_methods;
    delete action.recipients;
    delete action.template_id;
    delete action.interval;
    delete action.priority;
    delete action.note;
  } else if (type === ACTION_APPEND_NOTE) {
    action.note = '';
    delete action.status_id;
    delete action.notification_methods;
    delete action.recipients;
    delete action.template_id;
    delete action.interval;
    delete action.priority;
  }
};

// Emit drawer close
const updateModelValue = (val) => emit('update:isDrawerOpen', val);

// ==============================
// 🚀 FORM SUBMISSION
// ==============================

const isSubmitting = ref(false);

const onSubmit = async () => {
  if (isSubmitting.value) return;

  // Condition validation
  for (const [i, cond] of currentItem.value.conditions.entries()) {
    if ((cond.allow_condition && cond.trigger_event != RULE_STATUS_TRIGGER) || cond.condition === 'condition') {
      if (!cond.operator || !cond.datatype || (cond.value === '' || cond.field === '' || cond.value === null)) {
        toast.error(`Condition #${i + 1} is incomplete.`);
        return;
      }
    }
  }

  // Form validation
  const { valid: isValid } = await refForm.value.validate();
  if (!isValid) return;

  isSubmitting.value = true;

  const payload = {
    rule: currentItem.value.rule,
    rule_slug: currentItem.value.conditions[0].trigger_event,
    condition_type: currentItem.value.conditions.length >= 2 ? currentItem.value.condition_type : '',
    conditions: JSON.stringify(currentItem.value.conditions),
    actions: JSON.stringify(currentItem.value.actions),
    status: 'active',
    ...(props.currentInfo?.id && { id: props.currentInfo.id })
  };

  try {
    const endpoint = props.currentInfo ? `/rules/${props.currentInfo.id}?_method=PUT` : '/rules';
    const response = await $api(endpoint, { method: 'POST', body: payload });
    toast.success(response.message || 'Operation successful!');
    emit('update:isDrawerOpen', false);
    emit('submit', payload);
    resetForm();
    currentItem.value.rule = '';
    nextTick(() => {
      refForm.value?.reset();
      refForm.value?.resetValidation();
    });
  } catch (err) {
    toast.error(err._data?.message ?? "Submission failed.");
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  const response = await $api("/get-trigger-events");
  ruleList.value = response.data.rule_list.filter(item => item.status !== 'in-active');
});
</script>
