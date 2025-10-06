// resources/js/stores/companyStore.js
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useCompanyStore = defineStore('company', () => {
    const companyDetails = ref(null);
    const loading = ref(false);

    const fetchSettingList = async (keys = []) => {
        if (companyDetails.value) return;
        loading.value = true;
        try {
            const response = await $api(`/setting-list`, {
                method: 'POST',
                body: { keys: keys }
            });
            companyDetails.value = response.data ?? null;
        } catch (error) {
            console.error('Failed to fetch company details:', error);
        } finally {
            loading.value = false;
        }
    };

    return {
        companyDetails,
        loading,
        fetchSettingList
    };
});
