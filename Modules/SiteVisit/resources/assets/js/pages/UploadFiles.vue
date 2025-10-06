<script setup>
import {
  useDropZone,
  useFileDialog,
  useObjectUrl,
} from '@vueuse/core';
import { ref } from 'vue';
import { useRoute } from "vue-router";
import { toast } from "vue3-toastify";

const instance = getCurrentInstance();
const $can = instance?.proxy?.$can;

const dropZoneRef = ref()
const fileData = ref([])
const isUploading = ref(false)
const uploadProgress = ref(0)
const route = useRoute();

const { open, onChange } = useFileDialog({
  accept: 'image/*,video/*',
  multiple: true
})

function onDrop(DroppedFiles) {
  if (!DroppedFiles) return

  DroppedFiles.forEach(file => {
    const fileType = file.type.slice(0, 5)

    if (fileType !== 'image' && fileType !== 'video') {
      toast.error("Only image and video files are allowed");
      return
    }

    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
      type: fileType,
      uploadStatus: 'pending' // 'pending', 'uploading', 'success', 'error'
    })
  })
}

onChange(selectedFiles => {
  if (!selectedFiles) return

  for (const file of selectedFiles) {
    const fileType = file.type.slice(0, 5)
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
      type: fileType,
      uploadStatus: 'pending'
    })
  }
})

async function uploadFiles() {
  if (fileData.value.length === 0) {
    toast.error("No files to upload");

    return
  }

  isUploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()

  // Add all files to FormData
  fileData.value.forEach((item, index) => {
    formData.append(`files[${index}]`, item.file)
    item.uploadStatus = 'uploading'
  })

  try {
    const response = await $api(`/api/site-visit/${route.params.id}/site-risk-media`,
      { method: 'POST', body: formData },
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );
    mediaList.value = response.data;
    toast.success(response.message);
    fileData.value = []
  } catch (error) {
    console.error('Upload failed:', error)
    fileData.value.forEach(item => {
      item.uploadStatus = 'error'
    })
  } finally {
    isUploading.value = false
  }
}

useDropZone(dropZoneRef, onDrop)
const mediaList = ref([]);
try {
  const response = await $api(`/api/site-visit/${route.params.id}/site-risk-media`);
  mediaList.value = response.data;
} catch (error) {
  console.error('Upload failed:', error)
} 

const deleteLoading = ref(false);
async function deleteMedia(id) {
  if(deleteLoading.value) return ;
  try {
    deleteLoading.value = true ;
    const response = await $api(`/api/site-visit/${route.params.id}/site-risk-media/${id}`,
    { method: 'DELETE' },
  );
    mediaList.value = response.data;
    toast.success(response.message);
  } catch (error) {
    console.error('Delete failed:', error)
  } finally {
    deleteLoading.value = false ;
  } 
}
</script>

<template>
  <div class="flex" v-if="mediaList.length">
    <div class="w-full h-auto relative">
      <div ref="dropZoneRef" class="cursor-pointer">
        <div class="d-flex justify-center align-center gap-3 pa-8 drop-zone flex-wrap">
          <VRow class="match-height w-100">
            <template v-for="(item, i) in mediaList" :key="i">
              <VCol cols="12" sm="4">
                <VCard :ripple="false">
                  <VCardText class="d-flex flex-column" @click.stop>
                    <!-- Show video element for video files -->
                    <video v-if="item.type === 'video'" :src="item.path" width="200px" height="150px"
                      class="w-100 mx-auto" controls />
                    <!-- Show image for image files -->
                    <VImg v-else :src="item.path" width="200px" height="150px" class="w-100 mx-auto" />
                    <div class="mt-2">
                      <span class="clamp-text text-wrap">
                        {{ item.filename }}
                      </span>
                      <span>
                        {{ (item.file_size / 1000).toFixed(2) }}KB
                      </span>
                    </div>
                  </VCardText>
                  <VCardActions>
                    <!-- fileData.splice(index, 1) -->
                    <VBtn variant="text" block @click.stop="deleteMedia(item.id)" :disabled="deleteLoading">
                      Delete
                    </VBtn>
                  </VCardActions>
                </VCard>
              </VCol>
            </template>
          </VRow>
        </div>
      </div>
    </div>
  </div>

  <div class="flex" v-if="$can('siteVisit', 'create')">
    <div class="w-full h-auto relative">
      <div ref="dropZoneRef" class="cursor-pointer">
        <div v-if="fileData.length === 0"
          class="d-flex flex-column justify-center align-center gap-y-2 pa-12 drop-zone rounded"
           @click="() => open()"
          >
          <IconBtn variant="tonal" class="rounded-sm">
            <VIcon icon="tabler-upload" />
          </IconBtn>
          <h4 class="text-h4">
            Drag and drop your image or video here.
          </h4>
          <span class="text-disabled">or</span>

          <VBtn variant="tonal" size="small">
            Browse Files
          </VBtn>
        </div>

        <div v-else class="d-flex justify-center align-center gap-3 pa-8 drop-zone flex-wrap">
          <VRow class="match-height w-100">
            <template v-for="(item, index) in fileData" :key="index">
              <VCol cols="12" lg="4" md="4" sm="12">
                <VCard :ripple="false">
                  <VCardText class="d-flex flex-column" @click.stop>
                    <!-- Show video element for video files -->
                    <video v-if="item.type === 'video'" :src="item.url" width="200px" height="150px"
                      class="w-100 mx-auto" controls />
                    <!-- Show image for image files -->
                    <VImg v-else :src="item.url" width="200px" height="150px" class="w-100 mx-auto" />
                    <div class="mt-2">
                      <span class="clamp-text text-wrap">
                        {{ item.file.name }}
                      </span>
                      <span>
                        {{ (item.file.size / 1000).toFixed(2) }} KB
                      </span>
                    </div>
                    <!-- Upload status indicator -->
                    <div v-if="item.uploadStatus !== 'pending'" class="mt-2">
                      <VChip :color="item.uploadStatus === 'success' ? 'success' :
                        item.uploadStatus === 'error' ? 'error' : 'primary'" size="small">
                        {{ item.uploadStatus }}
                      </VChip>
                    </div>
                  </VCardText>
                  <VCardActions>
                    <VBtn variant="text" block @click.stop="fileData.splice(index, 1)" v-if="$can('siteVisit', 'create')">
                      Remove File
                    </VBtn>
                  </VCardActions>
                </VCard>
              </VCol>
            </template>

            <VCol cols="12" lg="4" md="4" sm="12">
              <div class="placeholder_div"
              @click="() => open()"
              v-if="$can('siteVisit', 'create')"
              >
                <VIcon icon="tabler-plus" color="secondary" size="x-large"/>
              </div>
              </VCol>
          </VRow>

          <!-- Upload button and progress -->
          <div class="mt-4" >
            <VBtn color="primary" :loading="isUploading" :disabled="isUploading" block @click="uploadFiles">
              Upload {{ fileData.length }} Files
            </VBtn>

            <VProgressLinear v-if="isUploading" v-model="uploadProgress" color="primary" height="8" class="mt-2" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.placeholder_div {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed rgb(var(--v-theme-secondary));
  border-radius: 6px;
  background: rgb(var(--v-theme-background));
  block-size: 100%;
  min-block-size: 300px;
}
</style>
