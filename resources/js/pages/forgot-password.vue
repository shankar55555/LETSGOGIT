<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { ref } from 'vue'
import { toast } from "vue3-toastify"

const email = ref('')
const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const loading = ref(false)
const isDialogActive = ref(false);


definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const sendResetLink = async () => {
  loading.value = true;
  if (email.value) {
    try {
      const response = await $api('/forgot-password', {
        method: 'POST',
        body: {
          email: email.value
        },
      });
      if (response?.status == 200) {
        toast.success(response?.message);
        isDialogActive.value = true;
        email.value = '';
        loading.value = false;
      } else {
        toast.error(response?.message);
        loading.value = false;
      }
    } catch (error) {
      toast.error(error?._data?.message);
      loading.value = false;
    }
  } else {
    toast.error('Email is required');
    loading.value = false;
  }
}
</script>

<template>

  <v-dialog transition="dialog-top-transition" width="700px" v-model="isDialogActive">
    <template v-slot:activator="{ props: activatorProps }">
    </template>
    <template v-slot:default="{ isActive }">
      <v-card>
        <!-- <v-toolbar title=""></v-toolbar> -->

        <v-card-text class="text-h5 pa-5">
          <p>We have sent you a verification email. Please click the link in the email to reset your password.</p>
        </v-card-text>

        <v-card-actions class="justify-end">
          <v-btn text="OK" @click="isActive.value = false"></v-btn>
        </v-card-actions>
      </v-card>
    </template>
  </v-dialog>


  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow class="auth-wrapper bg-surface" no-gutters>
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 150px;">
          <VImg max-width="468" :src="authThemeImg" class="auth-illustration mt-16 mb-2" />
        </div>

        <img class="auth-footer-mask" :src="authThemeMask" alt="auth-footer-mask" height="280" width="100">
      </div>
    </VCol>

    <VCol cols="12" md="4" class="d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-4">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Forgot Password? 🔒
          </h4>
          <p class="mb-0">
            Enter your email and we'll send you an email for instructions to reset your password
          </p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="sendResetLink">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField v-model="email" autofocus label="Email" type="email" placeholder="johndoe@email.com" />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn block type="submit" v-if="!loading">
                  Send Reset Link
                </VBtn>
                <VBtn block v-else>
                  <v-progress-circular color="light" :width="4" :size="20" indeterminate
                    class="mr-2"></v-progress-circular>Sending...
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink class="d-flex align-center justify-center" :to="{ name: 'login' }">
                  <VIcon icon="tabler-chevron-left" size="20" class="me-1 flip-in-rtl" />
                  <span>Back to login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
