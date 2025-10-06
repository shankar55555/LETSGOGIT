// D:\projects\modular-crm\resources\js\utils\constants.js
export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60

export const ACTIVE = 'active';
export const IN_ACTIVE = 'in-active';

export const SUPER_ADMIN = 'Super Admin';
export const ADMIN = "Admin";
export const EMPLOYEE = "Employee";
export const SLUG_SUPER_ADMIN = 'super-admin';
export const SLUG_ADMIN = "admin";
export const SLUG_EMPLOYEE = "employee";

export const MODULE_ALERT_AND_NOTIFICATION = "AlertAndNotification";
export const MODULE_ATTENDANCE = "Attendance";
export const MODULE_CLIENT = "Clients";
export const MODULE_USER = "Users";
export const MODULE_CONTRACT = "Contracts";
export const MODULE_DASHBOARD = "Dashboard";
export const MODULE_FOLLOW_UP = "FollowUp";
export const MODULE_INVOICE = "Invoices";
export const MODULE_LEAD = "Leads";
export const MODULE_PAYMENT = "Payments";
export const MODULE_PRODUCT_SERVICE = "ProductService";
export const MODULE_QUOTATION = "Quotations";
export const MODULE_ROLE_PERMISSION = "RolePermission";
export const MODULE_SCHEDULING = "Scheduling";
export const MODULE_SITE_VISIT = "SiteVisit";
export const MODULE_TARGETS = "Targets";

export const PRESENT = 'present';
export const HALF_PRESENT = 'half-present';
export const ABSENT = 'absent';
export const B_TO_B = 'B2B';
export const ROLES = 'Roles';
export const EXPORT_LOG = 'Export Log';
export const RULES = 'Rules';
export const NOTIFICATION_LOG = 'Notification Log';
export const USER_VIEW_ID = 'user-view-id';

export const QUOTATION_LEAD = "lead";
export const QUOTATION_CLIENT = "clients";

export const TYPE_MAP_NOTIFICATION_LIST = ['b_to_b_user', 'lead', 'client', 'srm', 'quotation', 'contract', 'follow_up']; // Relation name 

export const QUOTATION_DRAFT = "quotation-draft";
export const QUOTATION_CREATED = "quotation-created";
export const QUOTATION_SENT = "quotation-sent";
export const QUOTATION_ACCEPTED = "quotation-accepted";
export const QUOTATION_REJECTED = "quotation-rejected";
export const QUOTATION_EXPIRED = "quotation-expired";
export const QUOTATION_NOT_IN = [QUOTATION_DRAFT, QUOTATION_REJECTED, QUOTATION_EXPIRED];

export const EMAIL = "Email";
export const SMS = "Sms";
export const BELL_NOTIFICATION = "Bell Notification";
export const WHATSAPP = "WhatsApp";
export const APP = "App";

export const SEND_NOTIFICATION_LIST = [EMAIL, SMS, BELL_NOTIFICATION, WHATSAPP, APP];
export const SEND_NOTIFICATION_PLATFORM = [EMAIL, WHATSAPP];

export const SELECT_FILE = "Select File";
export const AUTO_SEND_FILE = "Auto Send File";
export const NO_ATTACHMENT = "No Attachment";
export const SEND_ATTACHMENT_TYPE_LIST = [SELECT_FILE, AUTO_SEND_FILE, NO_ATTACHMENT];
export const SETTING_KEYS = ["company_logo"];
export const NO_CALL = "no_call";
export const RULE_STATUS_TRIGGER = "status-trigger";

export const ACTION_SEND_NOTIFICATION = 'Send Notification';
export const ACTION_CHANGE_STATUS = 'change-status';
export const ACTION_APPEND_NOTE = 'append-note';
export const CONVERT_TO_LEAD = "convert_to_lead";
export const CONVERT_TO_CLIENT = "convert_to_client";

export const TRIGGER_SEND_INVOICE = "send-invoice";
export const TRIGGER_GENERATE_CHECKLIST_CHALLAN = "send-checklist-challan";
export const TRIGGER_SEND_QUOTATION = "send-quotation";
export const SEND_PLAT_FROM = [TRIGGER_SEND_INVOICE, TRIGGER_GENERATE_CHECKLIST_CHALLAN, TRIGGER_SEND_QUOTATION];

export const OTHER_ROUTE_PERMISSION_LIST = [
    { name: "apps-calendar", extra_permission: [{ action: 'calendar', subject: 'view' }] },
    { name: "profile-id", extra_permission: [{ action: 'profile', subject: 'view' }] },
    { name: "user-view-id", extra_permission: [{ action: 'user', subject: 'show' }] },
    { name: "role-edit", extra_permission: [{ action: 'role', subject: 'edit' }] },
    { name: "role-create", extra_permission: [{ action: 'role', subject: 'create' }] },
    { name: "lead-details-id", extra_permission: [{ action: 'leads', subject: 'show' }] },
    { name: "lead-site-risk-management", extra_permission: [{ action: 'siteVisit', subject: 'view' }] },
    { name: "client-details-id", extra_permission: [{ action: 'client', subject: 'show' }] },
    { name: "client-site-risk-management", extra_permission: [{ action: 'siteVisit', subject: 'view' }] },
    { name: "contract-details-id", extra_permission: [{ action: 'contract', subject: 'show' }] },
    { name: "contract-edit", extra_permission: [{ action: 'contract', subject: 'edit' }] },
    { name: "contract-create", extra_permission: [{ action: 'contract', subject: 'create' }] },
    { name: "quotation-details-id", extra_permission: [{ action: 'quotation', subject: 'show' }] },
    { name: "quotation-edit", extra_permission: [{ action: 'quotation', subject: 'edit' }] },
    { name: "quotation-create", extra_permission: [{ action: 'quotation', subject: 'create' }] },
    { name: "invoice-details-id", extra_permission: [{ action: 'invoice', subject: 'show' }] },
    { name: "invoice-edit", extra_permission: [{ action: 'invoice', subject: 'edit' }] },
    { name: "invoice-create", extra_permission: [{ action: 'invoice', subject: 'create' }] },
    { name: "product-service-create", extra_permission: [{ action: 'productService', subject: 'create' }] },
    { name: "product-service-edit", extra_permission: [{ action: 'productService', subject: 'edit' }] },
    { name: "product-service-details-id", extra_permission: [{ action: 'productService', subject: 'show' }] },
    { name: "notification-logs", extra_permission: [{ action: "emailLog", subject: "view" }, { action: "smsLog", subject: "view" }, { action: "whatsAppLog", subject: "view" }, { action: "bellNotificationLog", subject: "view" }, { action: "appLog", subject: "view" },] },
    { name: "notification-utilities", extra_permission: [{ action: "email", subject: "view" }, { action: "sms", subject: "view" }, { action: "whatsApp", subject: "view" }, { action: "bellNotification", subject: "view" }, { action: "appUtility", subject: "view" }] },
    { name: "rule-list", extra_permission: [{ action: "rule", subject: "view" }] },
    { name: "whatsapp-reachout-log", extra_permission: [{ action: "whatsAppLog", subject: "view" }] },
    { name: "whatsapp-new-reachout", extra_permission: [{ action: "client", subject: "view" }, { action: "leads", subject: "view" }, { action: "b2b", subject: "view" },] },
];
