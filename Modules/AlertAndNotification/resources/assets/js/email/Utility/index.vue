<template>
  <section v-if="$can('email', 'view')">
    <!-- <VBreadcrumbs class="app-breadcrumbs" color="primary" density="compact" :items="bread" /> -->

    <VRow>
      <!-- Left Panel: Category List -->
      <VCol cols="12" md="5" lg="4">
        <VCard>
          <VCardTitle>
            <h3 class="mb-2">Mail Utilities</h3>
          </VCardTitle>
          <VCardText>
            <VExpansionPanels v-model="expandedIndex" multiple>
              <VExpansionPanel v-for="(item, index) in categoryList" :key="`category-${index}`">
                <VExpansionPanelTitle :class="{ 'link-active': category_id === item.id }">
                  <div class="d-flex justify-space-between align-center w-100">
                    <span>{{ item.category }}</span>
                    <IconBtn v-if="item.is_delete" @click.stop="openDeleteDialog(item, 'Category')"
                      v-tooltip="'Delete Category'">
                      <VIcon icon="tabler-trash" />
                    </IconBtn>
                  </div>
                </VExpansionPanelTitle>

                <VExpansionPanelText>
                  <VList dense>
                    <VListItem v-for="childItem in item.notification_types" :key="`type-${childItem.id}`"
                      @click="clickOnItem(childItem)" :class="{ 'link-active': notification_type_id === childItem.id }">
                      <div class="d-flex justify-space-between align-center w-100">
                        <VListItemTitle>{{ childItem.title }}</VListItemTitle>
                        <IconBtn v-if="childItem.is_delete"
                          @click.stop="openDeleteDialog(childItem, 'Notification Type')"
                          v-tooltip="'Delete Notification Type'">
                          <VIcon icon="tabler-trash" />
                        </IconBtn>
                      </div>
                    </VListItem>
                  </VList>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>

          </VCardText>
        </VCard>
      </VCol>

      <!-- Right Panel: Email Editor -->
      <VCol cols="12" md="7" lg="8">
        <VCard>
          <VCardTitle class="d-flex flex-wrap gap-4">
            <h3 class="page-title">Update Info</h3>
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VForm ref="formRef" v-model="formValid">
              <VRow>
                <VCol cols="12">
                  <VLabel>Type Title<span style="color: red;">*</span></VLabel>
                  <VTextField v-model="itemTypes" :rules="[requiredValidator]" placeholder="Type Title" outlined dense>
                  </VTextField>
                </VCol>

                <VCol cols="12">
                  <VLabel>description <span style="color: red;">*</span></VLabel>
                  <VTextField v-model="itemDescription" :rules="[requiredValidator]" placeholder="Description" outlined
                    dense>
                  </VTextField>
                </VCol>

                <VCol cols="12">
                  <VLabel>Title <span style="color: red;">*</span></VLabel>
                  <VTextField v-model="templateInfo.title" :rules="[requiredValidator]" placeholder="Title" outlined
                    dense>
                  </VTextField>
                </VCol>

                <VCol cols="12">
                  <VSwitch v-model="templateActive" label="Template Active" />
                </VCol>

                <VCol cols="12">
                  <VLabel>Subject <span style="color: red;">*</span></VLabel>
                  <VTextField v-model="templateInfo.email_subject" class="mb-4" :rules="[requiredValidator]"
                    placeholder="Subject of Email" outlined dense hide-details />
                </VCol>

                <VCol cols="12">
                  <VLabel>Hidden Pre Header <span style="color: red;">*</span></VLabel>
                  <VTextField v-model="templateInfo.hidden_pre_header" class="mb-4" :rules="[requiredValidator]"
                    placeholder="Pre Header Text" outlined dense hide-details />
                </VCol>

                <VCol cols="12">
                  <VChip v-for="template in notificationVariableList" :key="`template-${template.id}`" class="ma-2"
                    @click="emailDataGet(template)">
                    {{ template.variables }}
                  </VChip>
                </VCol>

                <VCol cols="12">
                  <VLabel>Email Body Content <span style="color: red;">*</span></VLabel>
                  <QuillEditor v-model:content="templateInfo.email_body" :rules="[requiredValidator]"
                    content-type="html" theme="snow" />
                </VCol>
              </VRow>
            </VForm>
          </VCardText>

          <VCardActions class="custom_top_margin mb-3">
            <VSpacer />
            <VBtn v-if="$can('email', 'preview')" variant="elevated" @click="preview" :loading="loadingPreview"
              :disabled="loadingPreview">
              Preview
            </VBtn>
            <VBtn v-if="$can('email', 'edit')" variant="elevated" @click="saveData" :loading="loadingSave"
              :disabled="loadingSave">
              Save Changes
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Preview Dialog -->
    <VDialog v-model="preview_dialog" max-width="900px" scrollable persistent>
      <VCard>
        <VCardTitle class="d-flex flex-wrap gap-4">
          <h3 class="page-title">Preview Mail</h3>
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-0">
          <div class="pa-4" v-if="previewMailData" v-html="previewMailData" />
          <div v-else class="pa-4 text-center">No Data Found</div>
        </VCardText>
        <VDivider />
        <VCardActions class="mt-10 mb-3">
          <VSpacer />
          <VBtn color="secondary" variant="tonal" @click="preview_dialog = false"> Cancel </VBtn>
          <VBtn v-if="$can('email', 'send-mail')" variant="elevated" @click="check_send_email"
            :loading="loadingSendMail" :disabled="loadingSendMail">Send Email </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Delete Dialog -->
    <DeleteDialog v-model:isDialogVisible="isDeleteDialogOpen" confirm-title="Delete!" :confirmation-question="title"
      :currentItem="currentInfo" @submit="getCategoryList" :action="'force_delete'"
      :endpoint="Type == 'Category' ? `/notification-category/delete/${currentInfo?.id}` : `/notification-type/delete/${currentInfo?.id}`"
      @close="isDeleteDialogOpen = false" />
  </section>
</template>
<script setup>
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import { onMounted, ref } from "vue";
import { toast } from 'vue3-toastify';
import { VDivider } from "vuetify/components";

const props = defineProps({
  listCall: { type: Boolean, required: false },
});

const formRef = ref(null)
const formValid = ref(true)
// States
const categoryList = ref([]);
const itemTypes = ref("");
const itemDescription = ref("");
const templateInfo = ref({});
const notificationVariableList = ref([]);
const expandedIndex = ref([]); // for multiple expands
const templateActive = ref(false);
const preview_dialog = ref(false);
const previewMailData = ref(null);
const notification_type_id = ref("");
const category_id = ref(null);

// Breadcrumbs
const bread = ref([
  { title: "Dashboard", disabled: false, to: "/dashboard/crm" },
  { title: "Mail Utility", disabled: true, to: "" },
])

const title = ref('');
const Type = ref('Category');
const currentInfo = ref(null);
const isDeleteDialogOpen = ref(false)
const openDeleteDialog = (item, type) => {
  switch (type) {
    case 'Category':
      title.value = 'Are you sure you want to delete this category? Note: This will also delete all associated Email, WhatsApp, and Bell notifications.';
      break;
    case 'Notification Type':
      title.value = 'Are you sure you want to delete this notification type? Note: This will remove the corresponding Email, WhatsApp, and Bell messages.';
      break;
    default:
      return;
  }

  Type.value = type;
  currentInfo.value = item;
  isDeleteDialogOpen.value = true;
};

watch(() => props.listCall, (val) => { if (val) getCategoryList() });
onMounted(() => { getCategoryList() })

// Functions
const toggleItem = (index) => {
  expandedIndex.value = expandedIndex.value.includes(index)
    ? expandedIndex.value.filter((i) => i !== index)
    : [...expandedIndex.value, index]
}

const emailDataGet = (template) => {
  let set_name = "[[**name**]]"
  let variable = template.variables

  if (variable.includes("copy_")) {
    variable = variable.replace("copy_", "")
  } else if (variable.includes("_link")) {
    set_name = "[[***name***]]"
  }

  templateInfo.value.email_body = `${templateInfo.value.email_body || ""} ${set_name.replace("name", variable)}`
}

const clickOnItem = (item) => {
  notification_type_id.value = item.id
  category_id.value = item.category_id;
  itemTypes.value = item.title
  itemDescription.value = item.description
  templateInfo.value = item.notification_template_section || {}
  notificationVariableList.value = item.notification_variables || []
  templateActive.value = item.notification_template_section?.is_enable == 'Enable' ? true : false
}

const loading = ref(false);
const loadingPreview = ref(false);
const loadingSave = ref(false);
const loadingSendMail = ref(false);

const getCategoryList = async () => {
  loading.value = true;

  try {
    const response = await $api("/email/category-list");
    categoryList.value = response.data || [];

    const firstItem = categoryList.value[0];
    const firstType = firstItem?.notification_types?.[0];

    if (firstType) {
      category_id.value = firstItem.id;
      notification_type_id.value = firstType.id;
      itemTypes.value = firstType.title;
      itemDescription.value = firstType.description;
      templateInfo.value = firstType.notification_template_section || {};
      notificationVariableList.value = firstType.notification_variables || [];
      templateActive.value = firstType.notification_template_section?.is_enable == 'Enable' ? true : false
    }

  } catch (error) {
    handleApiError(error);
  } finally {
    loading.value = false;
  }
};

const preview = async () => {
  if (!templateInfo.value || loadingPreview.value) return;

  loadingPreview.value = true;
  previewMailData.value = null;

  try {
    const response = await $api("/email/preview", {
      method: "POST",
      body: { notification_type_id: templateInfo.value.notification_type_id },
    });

    previewMailData.value = response.data?.content || "";
    preview_dialog.value = true;

  } catch (error) {
    handleApiError(error);
  } finally {
    loadingPreview.value = false;
  }
};

const saveData = async () => {
  if (!templateInfo.value || loadingSave.value) return;
  const isValid = await formRef.value.validate()
  if (!isValid) {
    toast.error('Please fill all required fields.')
    return
  }

  if (!itemTypes.value) {
    toast.error('Please fill Type title fields.')
    return
  }

  if (!itemDescription.value) {
    toast.error('Please fill Description fields.')
    return
  }

  loadingSave.value = true;

  try {
    const obj = {
      id: templateInfo.value.id,
      title: templateInfo.value.title,
      email_body: templateInfo.value.email_body,
      email_subject: templateInfo.value.email_subject,
      hidden_pre_header: templateInfo.value.hidden_pre_header,
      is_enable: templateActive.value === false ? 'Disable' : 'Enable',
      notification_type_id: templateInfo.value.notification_type_id,
      type_title: itemTypes.value,
      description: itemDescription.value,
    };
    const response = await $api(`/email/create-update-template/${templateInfo.value.id}`, { method: "PUT", body: obj });
    toast.success(response.message);
  } catch (error) {
    handleApiError(error);
  } finally {
    loadingSave.value = false;
  }
};

const check_send_email = async () => {
  if (!templateInfo.value || loadingSendMail.value) return;

  loadingSendMail.value = true;

  try {
    const response = await $api("/email/send-notification", {
      method: "POST",
      body: { notification_type_id: templateInfo.value.notification_type_id },
    });

    preview_dialog.value = false;
    toast.success(response.message);
  } catch (error) {
    handleApiError(error);
  } finally {
    loadingSendMail.value = false;
  }
};

const handleApiError = (error, defaultMessage = "Error occurred while processing the request.") => {
  const errorMessage = error?._data?.errors ?? error._data?.message ?? error.message ?? defaultMessage;
  toast.error(errorMessage);
};

</script>
<style scoped>
.link-active {
  border-radius: 4px;
  background: linear-gradient(270deg, rgba(var(--v-global-theme-primary), 0.7) 0%, rgb(var(--v-global-theme-primary)) 100%) !important;

  /* Light blue or whatever fits your theme */
  font-weight: bold;
}
</style>
