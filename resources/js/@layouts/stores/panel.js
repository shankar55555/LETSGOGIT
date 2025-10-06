// D:\projects\modular-crm\resources\js\@layouts\stores\panel.js

import { $api } from "@/utils/api";
import { defineStore } from "pinia";
import { toast } from "vue3-toastify";

export const panelDetails = defineStore('panelDetails', () => {
  const userDetails = ref(null);
  const permissionList = ref([]);

  // Log out the user and clear relevant data
  const logoutUser = async () => {
    const userId = useCookie("userData").value?.uuid || localStorage.getItem("user_id");

    if (userId) {
      try {
        await $api("/log-unauthenticated-access", {
          method: "POST",
          body: { user_id: userId },
        });
      } catch (error) {
        console.error("Failed to log unauthenticated event:", error);
      }
    } else {
      console.warn("No user ID found for logout logging.");
    }

    // Clear cookies, session, and localStorage
    useCookie("userAbilityRules").value = null;
    useCookie("userData").value = null;
    useCookie("accessToken").value = null;

    sessionStorage.clear();
    localStorage.clear();
    window.location.href = "/login";
  };

  // Fetch user details
  const getUserDetails = async () => {
    const accessToken = useCookie("accessToken").value;
    if (!accessToken) return toast.error("Access token missing. Please log in again.");

    try {
      const response = await $api('/profile', {
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      });

      if (response?.data) {
        userDetails.value = response.data;
      } else {
        toast.error("Session expired. Please log in again.");
        await logoutUser();
      }
    } catch (error) {
      console.error("Failed to fetch user details:", error);
      toast.error("Unable to fetch user details. Please try again.");
    }
  };

  // Fetch user permissions
  const getUserPermission = async () => {
    try {
      const accessToken = useCookie("accessToken").value;
      const response = await $api("/role/user-permission", {
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      });
      permissionList.value = response.data ?? [];

      const abilityList = permissionList.value.map((p) => ({
        action: p.action,
        subject: p.slug,
      }));

      localStorage.setItem("permission_list", JSON.stringify(abilityList));
    } catch (error) {
      console.error("Failed to fetch user permissions:", error);
      toast.error("Unable to fetch permissions. Please try again.");
    }
  };

  return { userDetails, getUserDetails, permissionList, getUserPermission };
});
