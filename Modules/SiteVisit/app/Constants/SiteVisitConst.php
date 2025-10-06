<?php

namespace Modules\SiteVisit\Constants;

use App\Constants\CommonConst;

class SiteVisitConst
{
    const TRIGGER_GENERATE_CHECKLIST_CHALLAN = "send-checklist-challan";
    const SITE_VISIT_TRIGGER_ACTION_LIST = [
        [
            'title' => "Send Visit Assign Checklist Challan",
            'value' => self::TRIGGER_GENERATE_CHECKLIST_CHALLAN,
        ]
    ];

    const SITE_VISIT_HEADER_LIST = [
        [
            'title' => CommonConst::MODULE_SITE_VISIT,
            'slug' => 'upcoming-site-visit',
            'table' => 'upcoming-site_visits',
            'headers' => [
                ['title' => 'Visit Type', 'key' => 'visit_type', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Items', 'key' => 'items', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Visit Date', 'key' => 'visit_time', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Upcoming Visit Date', 'key' => 'upcoming_time', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                // ['title' => 'assignee name', 'key' => 'visit_assignee_id', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'minWidth' => '200px', 'checked' => true],
                ['title' => 'Visit Notes', 'key' => 'visit_notes', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => CommonConst::MODULE_SITE_VISIT,
            'slug' => 'site-visit',
            'table' => 'site_visits',
            'headers' => [
                ['title' => 'Visit Type', 'key' => 'visit_type', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Visit Time', 'key' => 'visit_time', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'assignee name', 'key' => 'assignee_name', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'minWidth' => '200px', 'checked' => true],
                ['title' => 'Visit Notes', 'key' => 'visit_notes', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],


    ];

    const SITE_VISIT_PERMISSION_LIST = [
        # 3. Site Visit Permission
        [
            'name' => CommonConst::MODULE_SITE_VISIT,
            'position' => 3,
            "icon" => 'tabler-antenna-bars-4',
            "category" => [
                [
                    'name' => 'Site Visit List',
                    "permission_list" => [
                        ["name" => 'View Site Visits', "action" => "siteVisit", "slug" => 'view'],
                        ["name" => 'Create Site Visit', "action" => "siteVisit", "slug" => 'create'],
                        ["name" => 'Edit Site Visit', "action" => "siteVisit", "slug" => 'edit'],
                        ["name" => 'Export Site Visits', "action" => "siteVisit", "slug" => 'export-list'],
                        ["name" => 'Assign Site Visit', "action" => "siteVisit", "slug" => 'assign-to'],
                        ["name" => 'Delete Site Visit', "action" => "siteVisit", "slug" => 'delete'],
                        ["name" => 'View Site Visit Details', "action" => "siteVisit", "slug" => 'show'],
                    ]
                ],
            ]
        ],
    ];

    const READY_FOR_SRM = 'ready-for-srm';
    const SITE_VISIT_COMPLETED = 'srm-completed';
    const SITE_VISIT_CANCELLED = 'srm-cancelled';
    # Site Visit page statuses
    const SITE_VISIT_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_SITE_VISIT,
            'position' => 2,
            'statuses' => [
                ["status_text" => "Ready For SRM", "slug" => self::READY_FOR_SRM, "status_color" => "#007bff", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Blue
                ["status_text" => "Site Visit Completed", "slug" => self::SITE_VISIT_COMPLETED, "status_color" => "#007bff", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Blue
                ["status_text" => "Site Visit Cancelled", "slug" => self::SITE_VISIT_CANCELLED, "status_color" => "#007bff", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Blue
            ]
        ],
    ];

    const HIGH = "High";
    const ENABLE = "Enable";
    const SITE_VISIT_VARIABLE = ["company_name", "visit_time", "visit_type", "products", "visit_assignee", "visit_notes", "status", "lead_id", "client_id", "created_by", "last_updated_by", "created_at", "address", "building_type", "roof_type", "height_of_roof", "service", "visit_datetime", "solution_recommended"];
    const RULE_ASSIGNED_TO_USER = "assigned-to-user-site-visit"; # done
    const RULE_SITE_VISIT_DUE = "site-visit-due"; # Job done
    const RULE_SITE_VISIT_COMPLETED = "site-visit-complete"; # done
    const SITE_VISIT_EMAIL_TYPE_LIST = [self::RULE_ASSIGNED_TO_USER, self::RULE_SITE_VISIT_DUE, self::RULE_SITE_VISIT_COMPLETED];

    const SITE_VISIT_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_SITE_VISIT,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Assigned to User',
                    'slug' => self::RULE_ASSIGNED_TO_USER,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email',  'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Site Visit Due',
                    'slug' => self::RULE_SITE_VISIT_DUE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Before due", 'value' => "<"],
                            ['title' => "Equals To", 'value' => "=="],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Visit Date", "value" => "visit_time"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email',  'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'Site Visit Completed',
                    'slug' => self::RULE_SITE_VISIT_COMPLETED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email',  'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High']
                        ]
                    ]
                ],

                # New Status Trigger Rule
                [
                    'name' => 'Status Trigger',
                    'slug' => CommonConst::RULE_STATUS_TRIGGER,
                    'allow_condition' => true,
                    'status_list' => [],
                    "condition_list" => [["title" => "On Site", "value" => "on-site"], ["title" => "Condition", "value" => "condition"]], //  ["title" => "Custom Days", "slug" => "custom-days"]
                    'condition' => [
                        'control' => [
                            ['title' => "After Day (>)", 'value' => '>'],
                            ['title' => "On or After Day (>=)", 'value' => '>='],
                            ['title' => "Before Day (<)", 'value' => '<'],
                            ['title' => "On or Before Day (<=)", 'value' => '<='],
                            ['title' => "Equals To (==)", 'value' => '=='],
                            ['title' => "Not Equal (!=)", 'value' => '!='],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => 'date'],
                            # ['title' => "Number", 'value' => 'numeric'],
                            # ['title' => "Text", 'value' => 'string'],
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Visit Date", "value" => "visit_time"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'templates' => [],
                            'priority' => ['High']
                        ],
                        [
                            'action' => 'Change Status',
                            'slug' => CommonConst::ACTION_CHANGE_STATUS,
                            'status_list' => [],
                        ],
                        [
                            'action' => 'Send Visit Assign Challan Pdf',
                            'slug' => self::TRIGGER_GENERATE_CHECKLIST_CHALLAN,
                            'recipient_list' => ['Assigned User'], // 'Admins', 'Created By', 'Assigned User',
                            'notification_methods' => ['Email', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'templates' => [],
                            'priority' => ['High']
                        ]
                    ]
                ]
            ]
        ],
    ];

    const SITE_VISIT_RULE_ITEM_LIST = [
        [
            "rule" => "Site Visit created",
            "rule_slug" => self::RULE_ASSIGNED_TO_USER,
            "condition_type" => null,
            "conditions" => '[{"module":"SiteVisit","trigger_event":"assigned-to-user-site-visit","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
        [
            "rule" => "Site Visit Due Date Rule",
            "rule_slug" => self::RULE_SITE_VISIT_DUE,
            "condition_type" => null,
            "conditions" => '[{"module":"SiteVisit","trigger_event":"site-visit-due","allow_condition":true,"operator":"<","datatype":"date","value":"1","field":"updated_at"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
        [
            "rule" => "Site Visit Complete",
            "rule_slug" => self::RULE_SITE_VISIT_COMPLETED,
            "condition_type" => null,
            "conditions" => '[{"module":"SiteVisit","trigger_event":"site-visit-complete","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
    ];

    const SITE_VISIT_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_SITE_VISIT,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Site Visit Assigned To User",
                    'type_key' => self::RULE_ASSIGNED_TO_USER,
                    'description' => 'Triggered when a site visit is assigned to a user.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Site Visit Assigned To User",
                        'email_body' => "<h2>Site Visit Assigned</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Assigned To: [[**visit_assignee**]]</p>
                                       <p>Visit Time: [[**visit_time**]]</p>
                                       <p>Visit Type: [[**visit_type**]]</p>
                                       <p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Site visit for [[**company_name**]] has been assigned to [[**visit_assignee**]] for [[**visit_time**]].",
                        "sms_message" => "Site visit assigned: [[**company_name**]] to [[**visit_assignee**]]",
                        "bell_notification_message" => "Site visit assigned: [[**company_name**]] to [[**visit_assignee**]]",
                        'email_subject' => "Site Visit Assigned: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SITE_VISIT_VARIABLE,
                ],
                [
                    'title' => "Site visit Due",
                    'type_key' => self::RULE_SITE_VISIT_DUE,
                    'description' => 'Triggered when a site visit is due.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Site visit Due",
                        'email_body' => "<h2>Site Visit Due</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Visit Time: [[**visit_time**]]</p>
                                       <p>Visit Type: [[**visit_type**]]</p>
                                       <p>Assigned To: [[**visit_assignee**]]</p>
                                       <p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Site visit for [[**company_name**]] is due at [[**visit_time**]]. Assigned to: [[**visit_assignee**]].",
                        "sms_message" => "Site visit due: [[**company_name**]] at [[**visit_time**]]",
                        "bell_notification_message" => "Site visit due: [[**company_name**]] at [[**visit_time**]]",
                        'email_subject' => "Site Visit Due: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SITE_VISIT_VARIABLE,
                ],
                [
                    'title' => "Site Visit Completed",
                    'type_key' => self::RULE_SITE_VISIT_COMPLETED,
                    'description' => 'Triggered when a site visit is completed.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Site Visit Completed",
                        'email_body' => "<h2>Site Visit Completed</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Visit Time: [[**visit_time**]]</p>
                                       <p>Visit Type: [[**visit_type**]]</p>
                                       <p>Completed By: [[**visit_assignee**]]</p>
                                       <p>Notes: [[**visit_notes**]]</p>
                                       <p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Site visit for [[**company_name**]] completed by [[**visit_assignee**]]. Notes: [[**visit_notes**]]",
                        "sms_message" => "Site visit completed: [[**company_name**]] by [[**visit_assignee**]]",
                        "bell_notification_message" => "Site visit completed: [[**company_name**]]",
                        'email_subject' => "Site Visit Completed: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SITE_VISIT_VARIABLE,
                ],
            ],
        ],
    ];
}
