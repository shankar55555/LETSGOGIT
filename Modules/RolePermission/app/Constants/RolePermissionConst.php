<?php

namespace Modules\RolePermission\Constants;

class RolePermissionConst
{
    # Roles TODO: Make new Role add slug and make permission 
    const SUPER_ADMIN = 'Super Admin';
    const ADMIN = "Admin";
    const EMPLOYEE = "Employee";

    const SLUG_SUPER_ADMIN = 'super-admin';
    const SLUG_ADMIN = "admin";
    const SLUG_EMPLOYEE = "employee";

    const SUPER_ADMIN_MESSAGE = "Super Admin Role Permission Not Update!";
    const ADMIN_PERMISSION = [
        "dashboard_view",
        "leads_view",
        "leads_create",
        "leads_edit",
        "leads_export-list",
        "leads_assign-to",
        "leads_status-update",
        "leads_show",
        "targets_view",
        "targets_create",
        "targets_edit",
        "targets_export-list",
        "targets_assign-to",
        "targets_status-update",
        "targets_show",
        "client_view",
        "client_create",
        "client_edit",
        "client_assign-to",
        "client_export-list",
        "client_status-update",
        "client_show",
        "followUp_view",
        "followUp_create",
        "followUp_edit",
        "followUp_activity-timeline",
        "siteVisit_view",
        "siteVisit_create",
        "siteVisit_edit",
        "siteVisit_export-list",
        "siteVisit_assign-to",
        "siteVisit_show",
        "quotation_view",
        "quotation_show",
        "quotation_create",
        "quotation_edit",
        "quotation_export-list",

        "purchaseBill_view",
        "purchaseBill_show",
        "purchaseBill_create",
        "purchaseBill_edit",

        "contract_view",
        "contract_show",
        "contract_create",
        "contract_edit",
        "contract_export-list",
        "user_view",
        "user_create",
        "user_edit",
        "user_export-list",
        "user_update-password",
        "user_show",
        "role_create",
        "role_view",
        "role_edit",
        "rule_view",
        "rule_create",
        "rule_update",
        "rule_delete",
        "emailLog_view",
        "emailLog_delete",
        "email_view",
        "email_preview",
        "email_send-mail",
        "email_update",
        "smsLog_view",
        "smsLog_delete",
        "sms_view",
        "sms_preview",
        "sms_send-message",
        "sms_update",
        "sms_send-message",
        "whatsAppLog_view",
        "whatsAppLog_delete",
        "whatsApp_view",
        "whatsApp_preview",
        "whatsApp_send-message",
        "whatsApp_update",
        "whatsApp_send-message",
        "bellNotificationLog_view",
        "bellNotificationLog_delete",
        "bellNotification_view",
        "bellNotification_preview",
        "bellNotification_send-message",
        "bellNotification_update",
        "bellNotification_send-message",
        "appLog_view",
        "appLog_delete",
        "appUtility_view",
        "appUtility_preview",
        "appUtility_send-message",
        "appUtility_update",
        "appUtility_send-message",
        "reachout_send-message",
        "reachoutLog_view",
        "reachoutLog_delete",
        "b2b_view",
        "b2b_create",
        "b2b_update",
        "b2b_delete",
        "loginLog_view",
        "loginLog_delete",
        "invoice_view",
        "invoice_show",
        "invoice_create",
        "invoice_edit",
        "invoice_delete",
        "invoice_export-list",
        // "status_view",
        // "status_create",
        // "status_update",
        // "status_delete",
        "attendance_view",
        "attendance_export-list",
        "attendance_delete",
        "userAttendance_view",
        "userAttendance_export-list",
        "userAttendance_delete",
        "profile_view",
        "profile_update",
        "profile_change-password",
    ];

    const EMPLOYEE_PERMISSION = [
        "dashboard_view",
        "leads_view",
        "leads_create",
        "leads_edit",
        "leads_show",
        "client_view",
        "client_create",
        "client_show",
        "followUp_view",
        "followUp_create",
        "followUp_activity-timeline",
        "siteVisit_view",
        "siteVisit_create",
        "siteVisit_show",
        "quotation_view",
        "quotation_show",
        "contract_view",
        "contract_show",
        "contract_create",
        "emailLog_view",
        "emailLog_delete",
        "smsLog_view",
        "smsLog_delete",
        "whatsAppLog_view",
        "whatsAppLog_delete",
        "bellNotificationLog_view",
        "bellNotificationLog_delete",
        "appLog_view",
        "appLog_delete",
        "reachoutLog_view",
        "reachout_send-message",
        "loginLog_view",
        "invoice_view",
        "invoice_show",
        "attendance_view",
        "attendance_export-list",
        "userAttendance_view",
        "userAttendance_export-list",
        "userAttendance_update",
        "profile_view",
        "profile_update",
        "profile_change-password",
    ];

    const ROLE_HEADER_LIST = [];

    const ROLE_LIST = [
        ['name' => RolePermissionConst::SUPER_ADMIN, "slug" => RolePermissionConst::SLUG_SUPER_ADMIN, 'description' => 'Full access to all system features and settings.', "position" => 0],
        ['name' => RolePermissionConst::ADMIN, "slug" => RolePermissionConst::SLUG_ADMIN, 'description' => 'Manage most system settings and data.', "position" => 1],
        ['name' => RolePermissionConst::EMPLOYEE, "slug" => RolePermissionConst::SLUG_EMPLOYEE, 'description' => 'Manage most system settings and data.', "position" => 2],
    ];

    const ROLE_PERMISSION_LIST = [
        # 1. Dashboard Permission
        [
            'name' => 'Dashboard',
            'position' => 1,
            "icon" => 'tabler-dashboard',
            "category" => [
                [
                    'name' => 'Dashboard',
                    "permission_list" => [
                        ["name" => "Dashboard", "action" => "dashboard", "slug" => 'view'],
                    ],
                ]
            ],
        ],
        [
            'name' => 'Users',
            'position' => 5,
            "icon" => 'tabler-users',
            "category" => [
                [
                    'name' => 'Users',
                    "permission_list" => [
                        ["name" => 'View Users', "action" => "user", "slug" => 'view'],
                        ["name" => 'Create User', "action" => "user", "slug" => 'create'],
                        ["name" => 'Edit User', "action" => "user", "slug" => 'edit'],
                        ["name" => 'Export Users', "action" => "user", "slug" => 'export-list'],
                        ["name" => 'Restore User', "action" => "user", "slug" => 'restore'],
                        ["name" => 'Update User Password', "action" => "user", "slug" => 'update-password'],
                        ["name" => 'Delete User', "action" => "user", "slug" => 'delete'],
                        ["name" => 'View User Details', "action" => "user", "slug" => 'show'],
                        ["name" => 'View Upcoming Birthdays/Anniversaries', "action" => "user-upcoming-dates", "slug" => 'view'],
                    ]
                ],
                [
                    'name' => 'Manage Roles',
                    "permission_list" => [
                        ["name" => 'Add Role', "action" => "role", "slug" => 'create'],
                        ["name" => 'View Role', "action" => "role", "slug" => 'view'],
                        ["name" => 'Edit Role', "action" => "role", "slug" => 'edit'],
                        ["name" => 'Delete Role', "action" => "role", "slug" => 'delete'],
                    ]
                ]
            ]
        ],

        # 7. Calender Permission
        [
            'name' => 'Calender',
            'position' => 6,
            "icon" => 'tabler-calendar',
            "category" => [
                [
                    'name' => 'Calendar',
                    "permission_list" => [
                        ["name" => 'View Calendar', "action" => "calendar", "slug" => 'view'],
                    ]
                ],
            ]
        ],

        # 7. Profiles Permission
        [
            'name' => 'Profile',
            'position' => 6,
            "icon" => 'tabler-bell-dollar',
            "category" => [
                [
                    'name' => 'Profile',
                    "permission_list" => [
                        ["name" => 'View Profile', "action" => "profile", "slug" => 'view'],
                        ["name" => 'Update info', "action" => "profile", "slug" => 'edit'],
                        ["name" => 'Change Password', "action" => "profile", "slug" => 'change-password'],
                    ]
                ],
                [
                    'name' => 'Login Log',
                    "permission_list" => [
                        ["name" => 'View Login Log List', "action" => "loginLog", "slug" => 'view'],
                        ["name" => 'Delete Login Log', "action" => "loginLog", "slug" => 'delete'],
                    ]
                ],
            ]
        ],

        # 8. Settings Permission
        [
            'name' => 'Settings',
            'position' => 7,
            "icon" => 'tabler-settings',
            "category" => [
                [
                    'name' => 'General Settings',
                    "permission_list" => [
                        ["name" => 'View General Setting', "action" => "generalSetting", "slug" => 'view'],
                        ["name" => 'Save General Setting', "action" => "generalSetting", "slug" => 'save'],
                    ]
                ],
                [
                    'name' => 'Status',
                    "permission_list" => [
                        ["name" => 'View Status', "action" => "status", "slug" => 'view'],
                        ["name" => 'Create Status', "action" => "status", "slug" => 'create'],
                        ["name" => 'Update Status', "action" => "status", "slug" => 'edit'],
                        ["name" => 'Delete Status', "action" => "status", "slug" => 'delete'],
                    ]
                ],
            ]
        ],
    ];
    const ROLE_MODULE_STATUS_LIST = [];
    const ROLE_RULE_LIST = [];
    const ROLE_RULE_ITEM_LIST = [];
    const ROLE_EMAIL_TEMPLATE_LIST = [];
}
