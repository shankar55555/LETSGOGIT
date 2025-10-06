// resources/js/utils/common.js
// import { $api } from '@/utils/api';
import { can } from '@layouts/plugins/casl';
import moment from "moment";

export const typeAccordingDateFormatChange = (date, type) => {
    if (type === "time_ago") {
        return moment(date).fromNow();
    } else if (type === "m-d-y") {
        return moment(date).format("MM-DD-YYYY"); // e.g. 04-18-2025
    } else if (type === "d-m-y") {
        return moment(date).format("DD-MM-YYYY"); // e.g. 18-04-2025
    } else if (type === "d-m-y-time") {
        return moment(date).format("DD-MM-YYYY hh:mm A"); // e.g. 18-04-2025 3:45 PM
    } else if (type === "y-m-d") {
        return moment(date).format("YYYY-MM-DD"); // e.g. 2025-04-18
    } else if (type === "d M, y") {
        return moment(date).format("DD MMM, YYYY"); // e.g. 18 Apr, 2025
    } else if (type === "full_date") {
        return moment(date).format("dddd, MMMM Do YYYY"); // e.g. Friday, April 18th 2025
    } else if (type === "full_date_1") {
        return moment(date).format("dddd, MMMM D, YYYY"); // e.g. Friday, April 18th 2025
    } else if (type === "date_time") {
        return moment(date).format("YYYY-MM-DD hh:mm A"); // e.g. 2025-04-18 03:45 PM
    } else if (type === "time_only") {
        return moment(date).format("hh:mm A"); // e.g. 03:45 PM
    } else if (type === "month_year") {
        return moment(date).format("MMMM YYYY"); // e.g. April 2025
    } else if (type === "iso") {
        return moment(date).toISOString(); // e.g. 2025-04-18T12:34:56.789Z
    } else if (type === "custom_1") {
        return moment(date).format("MMM D, YYYY"); // e.g. Apr 18, 2025
    } else if (type === "custom_2") {
        return moment(date).format("D MMM YYYY, h:mm A"); // e.g. 18 Apr 2025, 3:45 PM
    } else {
        return moment(date).format("YYYY-MM-DD");
    }
};

export const getStatusSlug = (status) => {
    const statusMap = {
        "No Action": "no_action",
        "Follow up": "follow_up",
        "Interested": "interested",
        "Not Interested": "not_interested",
        "Ready For SRM": "ready_for_srm",
        "Ready For Quotation": "ready-for-quotation",
        "Quotation Draft": "quotation-draft",
        "Quotation Created": "quotation-created",
        "Quotation in progress 25%": "quotation-in-progress-25",
        "Quotation in progress 50%": "quotation-in-progress-50",
        "Quotation in progress 75%": "quotation-in-progress-75",
        "Quotation Accepted": "quotation-accepted",
        "Quotation Rejected": "quotation-rejected",
        "Quotation Expired": "quotation-expired",
        "no_action": "No Action",
        "follow_up": "Follow up",
        "interested": "Interested",
        "not_interested": "Not Interested",
        "ready_for_srm": "Ready For SRM",
        'ready-for-quotation': 'Ready For Quotation',
        "quotation-draft": "Quotation Draft",
        "quotation-created": "Quotation Created",
        "quotation-in-progress-25": "Quotation in progress 25 %",
        "quotation-in-progress-50": "Quotation in progress 50 %",
        "quotation-in-progress-75": "Quotation in progress 75 %",
        "quotation-accepted": "Quotation Accepted",
        "quotation-rejected": "Quotation Rejected",
        "quotation-expired": "Quotation Expired",
    };
    return statusMap[status] || "All";
};

export const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'success':
            return 'success';
        case 'pending':
            return 'warning';
        case 'failed':
            return 'error';
        default:
            return 'secondary';
    }
};

export const selectDateRange = () => {
    return [
        {
            text: 'Last Hour',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 1)
                return [start, end]
            },
        },
        {
            text: 'Last 10 Hours',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 10)
                return [start, end]
            },
        },
        {
            text: 'Last 1 Day',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24)
                return [start, end]
            },
        },
        {
            text: 'Last 1 Week',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
                return [start, end]
            },
        },
        {
            text: 'Last 10 Weeks',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 7 * 10)
                return [start, end]
            },
        },
        {
            text: 'Last 1 Month',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
                return [start, end]
            },
        },
        {
            text: 'Last 10 Months',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 30 * 10)
                return [start, end]
            },
        },
    ];
};

// common.js
export const goToPage = (notification, type) => {

    if (type === 'b_to_b_user') {
        return `/admin/b_to_b_user/view/${notification.module_id}`;
    } else if (type === 'lead') {
        if (notification.module_type === "Lead Created in SRM" && can('leads', 'show-site-risk-management')) {
            return `/admin/site-risk-managment/view/${notification.module_id}`;
        } else if (notification.module_type === "Follow Up SRM" && can('leads', 'show-lead') && notification.lead) {
            return `/admin/leads/view/${notification.module_id}`;
        }
    } else if (type === 'client') {
        if (notification.module_type === "Create Client" && can('client', 'view-client')) {
            return `/admin/clients`;
        } else if (notification.module_type === "Client Visit Technician" && can('client', 'show-client')) {
            return `/admin/clients/view/${notification.module_id}`;
        }
    } else if (type === 'quotation') {
        if (notification.module_type === "Create Quotation" && can('quotation', 'view-quotation')) {
            return `/admin/quotations`;
        }
    } else if (type === 'contract') {
        if (notification.module_type === "Create Contract" && can('contract', 'view-contract')) {
            return `/admin/contracts`;
        }
    }

    return null;
};

export const resolveStatusVariant = (status, statusList = []) => {
    const list = Array.isArray(statusList) ? statusList : statusList.value || []
    const found = list.find(s => s.slug === status)
    if (found) {
        return {
            color: found.status_color || 'info',
            text: found.status_text || '—',
        }
    }
    return { color: 'info', text: status }
}

export const statusFilterPosition = (statusList = [], statusSlug = null) => {
    return statusList;
    if (!statusSlug) return statusList;
    const list = Array.isArray(statusList) ? statusList : (statusList.value || []);
    const current = list.find(s => s.slug === statusSlug);
    return list.filter(s => s.position >= current.position);
};

export const getInitials = (name) => {
    if (!name) return '';
    const words = name.trim().split(' ');
    return words.slice(0, 2).map(word => word.charAt(0).toUpperCase()).join('');
};

export function getFilteredTabs(tabs) {
    return tabs.filter(({ action, subject, extraPermissions = [] }) => {
        if (!action || !subject) return true;
        if (can(action, subject)) return true;
        return extraPermissions.some(({ action: extraAction, subject: extraSubject }) =>
            can(extraAction, extraSubject)
        );
    });
}
export function useFetchStatusList() {
    const statusList = ref([]);
    const fetchStatusList = async (moduleName) => {
        try {
            const response = await $api("/settings/status-list", { params: { type: moduleName } });
            const { data } = response.data;
            statusList.value = data ?? [];
        } catch (error) {
            console.error("Error fetching status list:", error);
            toast.error(error?.response?.data?.message || "Error fetching status list.");
        }
    };

    return { statusList, fetchStatusList };
}

