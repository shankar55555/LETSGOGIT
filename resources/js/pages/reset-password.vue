<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { onMounted, ref } from 'vue'
import { toast } from "vue3-toastify"

const router = useRouter()

const refVForm = ref()
const loading = ref(false)
const isPasswordVisible = ref(false)
const tokenExpired = ref(false)
const validatingToken = ref(false)
const showExpirationDialog = ref(false)

const formData = ref({
  email: '',
  password: '',
  password_confirmation: '',
  token: '',
});

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const validateToken = async () => {
  if (!formData.value.email || !formData.value.token) {
    tokenExpired.value = true
    showExpirationDialog.value = true
    return
  }

  try {
    validatingToken.value = true

    const response = await $api('/validate-reset-token', {
      method: 'POST',
      body: {
        email: formData.value.email,
        token: formData.value.token,
      },
    })

    if (response?.status && !response.expired) {
      tokenExpired.value = false
    } else {
      tokenExpired.value = true
      showExpirationDialog.value = true
    }
  } catch (error) {
    console.error('Token validation error:', error)
    tokenExpired.value = true
    showExpirationDialog.value = true
  } finally {
    validatingToken.value = false
  }
}

const reset = async () => {
  try {
    // Check if token is expired before proceeding
    if (tokenExpired.value) {
      showExpirationDialog.value = true
      return
    }

    // Validate the form before submitting
    const { valid: isValid } = await refVForm.value?.validate();

    if (isValid) {
      loading.value = true;  // Show loading indicator

      // Make API request to reset the password
      const response = await $api('/reset-password', {
        method: 'POST',
        body: {
          email: formData.value.email,
          password: formData.value.password,
          password_confirmation: formData.value.password_confirmation,
          token: formData.value.token,
        },
      });

      if (response?.status) {
        toast.success(response?.message || 'Password reset successfully!');
        router.replace('/login');
      } else {
        toast.error(response?.error || 'Failed to reset password');
      }
    } else {
      // If validation fails, show error message
      toast.error('Please fill out all fields correctly.');
    }
  } catch (error) {
    // Handle error response
    console.error('Reset password error:', error);

    // Check if it's a token expiration error
    if (error?.data?.expired || error?.response?.data?.expired) {
      tokenExpired.value = true
      showExpirationDialog.value = true
    } else {
      toast.error(error?.data?.message || error?.response?.data?.message || 'An error occurred while resetting password');
    }
  } finally {
    loading.value = false;  // Hide loading indicator after completion
  }
}

const handleGoToLogin = () => {
  showExpirationDialog.value = false
  router.replace('/login')
}

const getQueryParams = () => {
  const params = new URLSearchParams(window.location.search);
  formData.value.email = params.get('email') || '';
  formData.value.token = params.get('token') || '';
};

onMounted(() => {
  getQueryParams();
  // Validate token on mount
  validateToken();
});
</script>

<template>
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
            Reset Password? 🔒
          </h4>
          <p class="mb-0">
            Enter New password and confirm the new password to reset your account access!
          </p>
        </VCardText>

        <VCardText>
          <!-- Token Validation Loading -->
          <div v-if="validatingToken" class="text-center mb-4">
            <VProgressCircular indeterminate color="primary" />
            <p class="mt-2 text-body-2">Validating reset link...</p>
          </div>

          <!-- Token Expired Warning -->
          <VAlert v-if="tokenExpired && !validatingToken" type="error" variant="tonal" class="mb-4">
            <VAlertTitle>Link Expired</VAlertTitle>
            <div>Your password reset link has expired. Please request a new one.</div>
          </VAlert>

          <VForm ref="refVForm" @submit.prevent="reset">
            <VRow>
              <!-- Password -->
              <VCol cols="12">
                <AppTextField v-model="formData.password" :rules="[requiredValidator]" label="Password"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" :disabled="tokenExpired"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="formData.password_confirmation"
                  :rules="[requiredValidator, confirmedValidator(formData.password_confirmation, formData.password)]"
                  label="Confirm Password" placeholder="············" :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" :disabled="tokenExpired"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn block type="submit" :loading="loading" :disabled="tokenExpired || validatingToken">
                  Set Password
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

  <!-- Token Expiration Dialog -->
  <VDialog v-model="showExpirationDialog" max-width="450" persistent>
    <VCard>
      <VCardTitle class="text-h5 pa-6">
        <div class="d-flex align-center gap-3">
          <VIcon icon="tabler-alert-circle" color="error" size="24" />
          Reset Link Expired
        </div>
      </VCardTitle>

      <VCardText class="pa-6 pt-0">
        <p class="text-body-1 mb-4">
          Your password reset link has expired. For security reasons, password reset links are only valid for 2 minutes.
        </p>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Please go back to the login page and request a new password reset link.
        </p>
      </VCardText>

      <VCardActions class="pa-6 pt-0">
        <VSpacer />
        <VBtn color="primary" variant="elevated" @click="handleGoToLogin">
          Go to Login
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
