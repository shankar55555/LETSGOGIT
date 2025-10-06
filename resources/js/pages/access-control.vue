<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant';
import pages403 from '@images/pages/403-error-access-forbidden.avif';
import miscMaskDark from '@images/pages/misc-mask-dark.png';
import miscMaskLight from '@images/pages/misc-mask-light.png';
import { useRouter } from 'vue-router';

const router = useRouter();

// Define page alias and meta information
definePage({
  alias: '/pages/misc/access-control',
  meta: {
    layout: 'blank',
    public: true,
  },
});

// Generate the background mask based on the theme
const authThemeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark);

// Go back to the previous page
const goBack = () => {
  router.back(); // or router.go(-1)
};
</script>

<template>
  <div class="misc-wrapper text-center px-4">
    <!-- Header Section Forbidden -->
         <!-- Image -->
    <div class="misc-avatar w-100 mb-10">
      <VImg
        :src="pages403"
        alt="Permission Denied"
        :max-height="$vuetify.display.smAndDown ? 350 : 500"
        class="mx-auto"
      />
    </div>

    <!-- Footer Mask -->
    <img
      class="misc-footer-img d-none d-md-block"
      :src="authThemeMask"
      alt="Background Mask"
      height="320"
    />
    <ErrorHeader
      title="You are Permission Denied! 🔐"
      description="You don’t have permission to access this page. Please contact the administrator or go back home."
    />


        <!-- Back Button -->
        <VBtn
      class="mb-8"
      @click="goBack"
      color="primary"
      rounded
    >
      Back To Home
    </VBtn>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/misc";
</style>
