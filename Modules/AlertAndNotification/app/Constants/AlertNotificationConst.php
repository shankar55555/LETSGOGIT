<?php

namespace Modules\AlertAndNotification\Constants;

use App\Constants\CommonConst;

class AlertNotificationConst
{
    const ALERT_AND_NOTIFICATION_HEADER_LIST = [
        [
            'title' => 'BToB User List',
            'slug' => 'b-to-b-user-header-list',
            'table' => 'b_to_b_users',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Company', 'key' => 'company', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Phone', 'key' => 'contact_no', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Role', 'key' => 'role', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Address', 'key' => 'address', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'created By', 'key' => 'created_by', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        # email-log-header-list
        [
            'title' => 'Email Log List',
            'slug' => 'email-log-header-list',
            'table' => 'notification_logs',
            'headers' => [
                ['title' => 'Notification Type', 'key' => 'notification_type_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Subject', 'key' => 'subject', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver Email', 'key' => 'receiver_contact', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Send Date', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Sender User', 'key' => 'sender_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver User', 'key' => 'receiver_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Email', 'key' => 'content', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Other Info', 'key' => 'other_info', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Section Type', 'key' => 'section_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Module Name', 'key' => 'module_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # Whats-App-log-header-list
        [
            'title' => 'Whats App Log List',
            'slug' => 'whats-app-log-header-list',
            'table' => 'notification_logs',
            'headers' => [
                ['title' => 'Notification Type', 'key' => 'notification_type_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Subject', 'key' => 'subject', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver Whats App', 'key' => 'receiver_contact', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Send Date', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Sender User', 'key' => 'sender_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver User', 'key' => 'receiver_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Whats App', 'key' => 'content', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Other Info', 'key' => 'other_info', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Section Type', 'key' => 'section_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Module Name', 'key' => 'module_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # sms-log-header-list
        [
            'title' => 'Sms Log List',
            'slug' => 'sms-log-header-list',
            'table' => 'notification_logs',
            'headers' => [
                ['title' => 'Notification Type', 'key' => 'notification_type_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Subject', 'key' => 'subject', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver Sms', 'key' => 'receiver_contact', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Send Date', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Sender User', 'key' => 'sender_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver User', 'key' => 'receiver_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Whats App', 'key' => 'content', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Other Info', 'key' => 'other_info', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Section Type', 'key' => 'section_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Module Name', 'key' => 'module_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # app-log-header-list
        [
            'title' => 'App Log List',
            'slug' => 'app-log-header-list',
            'table' => 'notification_logs',
            'headers' => [
                ['title' => 'Notification Type', 'key' => 'notification_type_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Subject', 'key' => 'subject', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver Way', 'key' => 'receiver_contact', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Send Date', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Sender User', 'key' => 'sender_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver User', 'key' => 'receiver_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'App Message', 'key' => 'content', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Other Info', 'key' => 'other_info', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Section Type', 'key' => 'section_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Module Name', 'key' => 'module_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        # Bell-notification-log-header-list
        [
            'title' => 'Bell Notification Log List',
            'slug' => 'bell-notification-log-header-list',
            'table' => 'notification_logs',
            'headers' => [
                ['title' => 'Notification Type', 'key' => 'notification_type_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Subject', 'key' => 'subject', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver Way', 'key' => 'receiver_contact', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Send Date', 'key' => 'created_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Sender User', 'key' => 'sender_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Receiver User', 'key' => 'receiver_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Bell Message', 'key' => 'content', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Other Info', 'key' => 'other_info', 'sortable' => false, 'align' => 'center', 'checked' => true],
                ['title' => 'Section Type', 'key' => 'section_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Module Name', 'key' => 'module_id', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # Setting Status List Header 
        [
            'title' => 'Rule List',
            'slug' => 'header-rule-list',
            'table' => 'rules',
            'headers' => [
                ['title' => 'Rule', 'key' => 'rule', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Condition Type', 'key' => 'condition_type', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Condition', 'key' => 'conditions', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'actions', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Updated By', 'key' => 'last_updated_by', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];

    const ALERT_AND_NOTIFICATION_PERMISSION_LIST = [
        # 7. Rule Permission
        [
            'name' => 'Rules',
            'position' => 6,
            "icon" => 'tabler-bell-dollar',
            "category" => [
                [
                    'name' => 'Rule List',
                    "permission_list" => [
                        ["name" => 'View Rules', "action" => "rule", "slug" => 'view'],
                        ["name" => 'Create Rule', "action" => "rule", "slug" => 'create'],
                        ["name" => 'Update Rule', "action" => "rule", "slug" => 'edit'],
                        ["name" => 'Delete Rule', "action" => "rule", "slug" => 'delete'],
                    ]
                ],
            ]
        ],
        # 7. Alert & Notifications Permission
        [
            'name' => 'Alert & Notifications',
            'position' => 6,
            "icon" => 'tabler-bell-dollar',
            "category" => [
                # Email 
                [
                    'name' => 'Email log',
                    "permission_list" => [
                        ["name" => 'View Email log list', "action" => "emailLog", "slug" => 'view'],
                        ["name" => 'Status Update Email log', "action" => "emailLog", "slug" => 'edit'],
                        ["name" => 'Email Soft Delate', "action" => "emailLog", "slug" => 'delete'],
                    ]
                ],
                [
                    'name' => 'Email Utility',
                    "permission_list" => [
                        ["name" => 'Email Utility', "action" => "email", "slug" => 'view'],
                        ["name" => 'Preview Mail', "action" => "email", "slug" => 'preview'],
                        ["name" => 'Send Mail', "action" => "email", "slug" => 'send-mail'],
                        ["name" => 'Email Update', "action" => "email", "slug" => 'edit'],
                    ]
                ],

                # Sms 
                // [
                //     'name' => 'Sms log',
                //     "permission_list" => [
                //         ["name" => 'View Sms log list', "action" => "smsLog", "slug" => 'view'],
                //         ["name" => 'Status Update Sms log', "action" => "smsLog", "slug" => 'edit'],
                //         ["name" => 'Sms Soft Delate', "action" => "smsLog", "slug" => 'delete'],
                //     ]
                // ],
                // [
                //     'name' => 'Sms Utility',
                //     "permission_list" => [
                //         ["name" => 'Sms Utility', "action" => "sms", "slug" => 'view'],
                //         ["name" => 'preview Sms', "action" => "sms", "slug" => 'preview'],
                //         ["name" => 'Send Sms Message', "action" => "sms", "slug" => 'send-message'],
                //         ["name" => 'Sms Update', "action" => "sms", "slug" => 'edit'],
                //     ]
                // ],

                # Whats App  
                [
                    'name' => 'Whats Log',
                    "permission_list" => [
                        ["name" => 'View Whats App log list', "action" => "whatsAppLog", "slug" => 'view'],
                        ["name" => 'Status Update Whats App log', "action" => "whatsAppLog", "slug" => 'edit'],
                        ["name" => 'Whats App Soft Delate', "action" => "whatsAppLog", "slug" => 'delete'],
                    ]
                ],
                [
                    'name' => 'Whats App Utility',
                    "permission_list" => [
                        ["name" => 'Whats App Utility', "action" => "whatsApp", "slug" => 'view'],
                        ["name" => 'preview Whats App', "action" => "whatsApp", "slug" => 'preview'],
                        ["name" => 'Send Whats App Message', "action" => "whatsApp", "slug" => 'send-message'],
                        ["name" => 'Whats App Update', "action" => "whatsApp", "slug" => 'edit'],
                    ]
                ],

                # Bell Notification  
                [
                    'name' => 'Bell Notification Log',
                    "permission_list" => [
                        ["name" => 'View Bell Notification log list', "action" => "bellNotificationLog", "slug" => 'view'],
                        ["name" => 'Status Update Bell Notification log', "action" => "bellNotificationLog", "slug" => 'edit'],
                        ["name" => 'Bell Notification Soft Delate', "action" => "bellNotificationLog", "slug" => 'delete'],
                    ]
                ],
                [
                    'name' => 'Bell Notification Utility',
                    "permission_list" => [
                        ["name" => 'Bell Notification Utility', "action" => "bellNotification", "slug" => 'view'],
                        ["name" => 'preview Bell Notification', "action" => "bellNotification", "slug" => 'preview'],
                        ["name" => 'Send Bell Message', "action" => "bellNotification", "slug" => 'send-message'],
                        ["name" => 'Bell Notification Update', "action" => "bellNotification", "slug" => 'edit'],
                    ]
                ],

                # App
                // [
                //     'name' => 'App Log',
                //     "permission_list" => [
                //         ["name" => 'View App log list', "action" => "appLog", "slug" => 'view'],
                //         ["name" => 'Status Update App log', "action" => "appLog", "slug" => 'edit'],
                //         ["name" => 'App Soft Delate', "action" => "appLog", "slug" => 'delete'],
                //     ]
                // ],
                // [
                //     'name' => 'App Utility',
                //     "permission_list" => [
                //         ["name" => 'App Utility', "action" => "appUtility", "slug" => 'view'],
                //         ["name" => 'preview App', "action" => "appUtility", "slug" => 'preview'],
                //         ["name" => 'Send App Message', "action" => "appUtility", "slug" => 'send-message'],
                //         ["name" => 'App Update', "action" => "appUtility", "slug" => 'edit'],
                //     ]
                // ],
            ]
        ],
        # WhatsApp Campaign Permission
        [
            'name' => 'WhatsApp Campaign',
            'position' => 6,
            "icon" => 'tabler-bell-dollar',
            "category" => [
                [
                    'name' => 'Reachout',
                    "permission_list" => [
                        ["name" => 'Send Message Reachout', "action" => "reachout", "slug" => 'send-message'],
                    ]
                ],
                [
                    'name' => 'Reachout log',
                    "permission_list" => [
                        ["name" => 'Reachout log View', "action" => "reachoutLog", "slug" => 'view'],
                        ["name" => 'Delete Reachout log', "action" => "reachoutLog", "slug" => 'delete'],
                    ]
                ],
                [
                    'name' => 'B2B User',
                    "permission_list" => [
                        ["name" => 'B2B User View', "action" => "b2b", "slug" => 'view'],
                        ["name" => 'Create B2B User', "action" => "b2b", "slug" => 'create'],
                        ["name" => 'Update B2B User', "action" => "b2b", "slug" => 'edit'],
                        ["name" => 'Delete B2B User', "action" => "b2b", "slug" => 'delete'],
                    ]
                ],
            ]
        ],
    ];

    const DRAFT = "draft";
    # Alert and Notification Module Status
    const ALERT_AND_NOTIFICATION_MODULE_STATUS_LIST = [
        [
            'page' => 'Rule',
            'position' => 101,
            'statuses' => [
                ["status_text" => "Active", "slug" => CommonConst::ACTIVE, "status_color" => "#28a745", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "In Active", "slug" => CommonConst::IN_ACTIVE, "status_color" => "#6c757d", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Draft", "slug" => self::DRAFT, "status_color" => "#6c757d", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const ALERT_AND_NOTIFICATION_RULE_LIST = [];
    const ALERT_AND_NOTIFICATION_RULE_ITEM_LIST = [];
    const ALERT_AND_NOTIFICATION_EMAIL_TEMPLATE_LIST = [];
}
