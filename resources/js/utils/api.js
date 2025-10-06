// D:\projects\modular-crm\resources\js\utils\api.js
import { ofetch } from "ofetch";
import { toast } from "vue3-toastify";

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "/api",

  async onRequest({ options }) {
    const accessToken = useCookie("accessToken").value;
    if (accessToken) {
      options.headers = {
        ...options.headers,
        Accept: "application/json",
        Authorization: `Bearer ${accessToken}`,
      };
    }

    // Don't set Content-Type for FormData
    if (options.body instanceof FormData) {
      delete options.headers['Content-Type'];
    }
  },

  async onResponseError({ response }) {
    const errorMessage = response?._data?.message || null;
    if (typeof errorMessage === "string" && errorMessage.toLowerCase().includes("unauthenticated")) {
      toast.error("Session expired. Please log in again.");
      const userId = useCookie("userData").value?.uuid || localStorage.getItem("user_id");
      if (userId) {
        try {
          await ofetch("/api/log-unauthenticated-access", {
            method: "POST",
            body: { user_id: userId },
          });
        } catch (error) {
          console.error("Failed to log unauthenticated event:", error);
        }
      }
      useCookie("userAbilityRules").value = null;
      useCookie("userData").value = null;
      useCookie("accessToken").value = null;

      sessionStorage.clear();
      localStorage.clear();
      window.location.href = "/login";
    }

    return Promise.reject(response);
  },
});
