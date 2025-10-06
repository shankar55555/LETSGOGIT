<?php

namespace Modules\Leads\Constants;

use App\Constants\CommonConst;

class LeadConst
{
    const LEAD_TRIGGER_ACTION_LIST = [['title' => "Convert to Client", 'value' => "convert_to_client",]];

    const LEAD_HEADER_LIST = [
        [
            'title' => 'lead List',
            'slug' => 'lead-list',
            'table' => "leads",
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Contact Person', 'key' => 'contact_person', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Contact Person Role', 'key' => 'contact_person_role', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Secondary Phone', 'key' => 'secondary_phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Source', 'key' => 'source', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Referral Detail', 'key' => 'referral_detail', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Address', 'key' => 'address', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Assigned To', 'key' => 'assigned_user', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Lead Status', 'key' => 'status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Followup Status', 'key' => 'last_followup_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Site visit Status', 'key' => 'last_site_visit_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'City', 'key' => 'city_id', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Date of Birth', 'key' => 'date_of_birth', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Anniversary Date', 'key' => 'anniversary_date', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Quotation Status', 'key' => 'last_quotation_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => 'Dashboard Lead List',
            'slug' => 'dashboard-lead-list',
            'table' => "leads",
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Contact Person', 'key' => 'contact_person', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Contact Person Role', 'key' => 'contact_person_role', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Secondary Phone', 'key' => 'secondary_phone', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Source', 'key' => 'source', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Referral Detail', 'key' => 'referral_detail', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Address', 'key' => 'address', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Assigned To', 'key' => 'assigned_user', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Lead Status', 'key' => 'status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Followup Status', 'key' => 'last_followup_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Site visit Status', 'key' => 'last_site_visit_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'City', 'key' => 'city_id', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Date of Birth', 'key' => 'date_of_birth', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Anniversary Date', 'key' => 'anniversary_date', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Quotation Status', 'key' => 'last_quotation_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
            ]
        ],
        [
            'title' => 'Reachout Lead List',
            'slug' => 'reachout-lead-header-list',
            'table' => 'leads',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Lead Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Address', 'key' => 'address', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ]
    ];

    const LEAD_PERMISSION_LIST = [
        # 2. Lead Permission
        [
            'name' => CommonConst::MODULE_LEAD,
            'position' => 2,
            "icon" => 'tabler-antenna-bars-4',
            "category" => [
                [
                    'name' => CommonConst::MODULE_LEAD,
                    "permission_list" => [
                        ['name' => 'View Leads', 'action' => 'leads', 'slug' => 'view'],
                        ['name' => 'Create Lead', 'action' => 'leads', 'slug' => 'create'],
                        ['name' => 'Edit Lead', 'action' => 'leads', 'slug' => 'edit'],
                        ['name' => 'Export Leads', 'action' => 'leads', 'slug' => 'export-list'],
                        ['name' => 'Assign Lead', 'action' => 'leads', 'slug' => 'assign-to'],
                        ['name' => 'Update Lead Status', 'action' => 'leads', 'slug' => 'status-update'],
                        ['name' => 'Delete Lead', 'action' => 'leads', 'slug' => 'delete'],
                        ['name' => 'View Lead Details', 'action' => 'leads', 'slug' => 'show'],
                        ['name' => 'Convert to Client', 'action' => 'convert', 'slug' => 'show'],
                        ['name' => 'View Upcoming Birthdays/Anniversaries', 'action' => 'lead-upcoming-dates', 'slug' => 'view'],
                    ]
                ],
            ]
        ],
    ];

    const NO_ACTION = "no_action";
    const FOLLOW_UP = "follow_up";
    const INTERESTED = "interested";
    const NOT_INTERESTED = "not_interested";
    const CONVERT_TO_CLIENT = "convert_to_client";
    const LEAD_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_LEAD,
            'position' => 1,
            'statuses' => [
                ["status_text" => "No Action", "slug" => self::NO_ACTION, "status_color" => "#6c757d", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Gray
                ["status_text" => "Follow up", "slug" => self::FOLLOW_UP, "status_color" => "#ffc107", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Yellow
                ["status_text" => "Interested", "slug" =>  self::INTERESTED, "status_color" => "#17a2b8", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Cyan
                ["status_text" => "Not Interested", "slug" => self::NOT_INTERESTED, "status_color" => "#dc3545", "position" => 4, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Red
                ["status_text" => "Convert-To-Client", "slug" => self::CONVERT_TO_CLIENT, "status_color" => "#dc3545", "position" => 5, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Red
                ["status_text" => "In-Active", "slug" => CommonConst::IN_ACTIVE, "status_color" => "#dc3545", "position" => 6, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Red
            ]
        ],
    ];

    const RULE_NO_ACTION = self::NO_ACTION; # Job done
    const RULE_LEAD_CREATED = "lead-created"; # done
    const RULE_ASSIGNED_TO_USER = "assigned-to-user-lead"; # done

    const HIGH = "High";
    const ENABLE = "Enable";
    const LEAD_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_LEAD,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Lead Created',
                    'slug' => self::RULE_LEAD_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'templates' => [],
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ],
                    ]
                ],
                [
                    'name' => 'Assigned to User',
                    'slug' => self::RULE_ASSIGNED_TO_USER,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'templates' => [],
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'No action',
                    'slug' => self::RULE_NO_ACTION,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "More then", 'value' => ">"],
                            ['title' => "Equals To", 'value' => "=="],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            # ["title" => "Date of Birth", "value" => "date_of_birth"],
                            # ["title" => "Anniversary Date", "value" => "anniversary_date"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'templates' => [],
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['High', 'Critical']
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
                            # ["title" => "Date of Birth", "value" => "date_of_birth"],
                            # ["title" => "Anniversary Date", "value" => "anniversary_date"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Lead User'],
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
                            'action' => 'Append Note',
                            'slug' => CommonConst::ACTION_APPEND_NOTE,
                        ],
                        [
                            'action' => 'Convert To Client',
                            'slug' => self::CONVERT_TO_CLIENT,
                        ]
                    ]
                ]
            ]
        ],
    ];

    const LEAD_RULE_ITEM_LIST = [
        [
            'rule' => 'Lead create',
            'rule_slug' => self::RULE_LEAD_CREATED,
            'condition_type' => null,
            'conditions' => '[{"module":"Leads","trigger_event":"lead-created","allow_condition":false,"operator":"","datatype":"","value":""}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins"],"interval":"Immediate","priority":"Medium"}]',
            'status' => 'active',
        ],
        [
            'rule' => 'No Action Lead Rule',
            'rule_slug' => self::RULE_NO_ACTION,
            'condition_type' => null,
            'conditions' => '[{"module":"Leads","trigger_event":"no_action","allow_condition":true,"operator":">","datatype":"date","value":"5","field":"updated_at"}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins"],"interval":"Immediate","priority":"High"}]',
            'status' => 'active',
        ],
        [
            'rule' => 'Lead Assign to User',
            'rule_slug' => self::RULE_ASSIGNED_TO_USER,
            'condition_type' => null,
            'conditions' => '[{"module":"Leads","trigger_event":"assigned-to-user-lead","allow_condition":false,"operator":"","datatype":"","value":""}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
            'status' => 'active',
        ],
    ];

    const LEAD_VARIABLE = ["company_name", "name", "contact_person", "contact_person_role", "email", "phone", "address", "status", "source", "assigned_user", "note", "created_by", "last_updated_by", "client_id", "quotation_id", "created_at"]; // "contract_id", "invoice_id"

    const LEAD_EMAIL_TYPE_LIST = [self::RULE_LEAD_CREATED, self::RULE_ASSIGNED_TO_USER, self::NO_ACTION];
    const LEAD_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_LEAD,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "New Lead Created",
                    'type_key' => self::RULE_LEAD_CREATED,
                    'description' => 'Triggered when a new lead is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "New Lead Created",
                        'email_body' => "<h2>New Lead Created</h2><p>Lead Name: [[**name**]]</p><p>Contact Person: [[**contact_person**]]</p><p>Phone: [[**phone**]]</p><p>Email: [[**email**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "A new lead named [[**name**]] has been created. Contact: [[**contact_person**]], Phone: [[**phone**]].",
                        "sms_message" => "New lead [[**name**]] created. Contact: [[**contact_person**]], Phone: [[**phone**]].",
                        "bell_notification_message" => "New lead created: [[**name**]]",
                        'email_subject' => "New Lead Created: [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::LEAD_VARIABLE,
                ],
                [
                    'title' => "Lead Assigned",
                    'type_key' => self::RULE_ASSIGNED_TO_USER,
                    'description' => 'Triggered when a lead is assigned to a user.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Lead Assigned",
                        'email_body' => "<h2>Lead Assigned</h2><p>Lead Name: [[**name**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Lead [[**name**]] has been assigned to [[**assigned_user**]].",
                        "sms_message" => "Lead [[**name**]] assigned to [[**assigned_user**]].",
                        "bell_notification_message" => "Lead assigned: [[**name**]] to [[**assigned_user**]]",
                        'email_subject' => "Lead Assigned: [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::LEAD_VARIABLE,
                ],
                [
                    'title' => "Lead Status No Action",
                    'type_key' => self::NO_ACTION,
                    'description' => 'Triggered when a lead is Status No Action.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Lead Status No Action",
                        'email_body' => "<h2>Lead Status No Action</h2><p>Lead Name: [[**name**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Lead [[**name**]] has been Status No Action",
                        "sms_message" => "Lead [[**name**]] Status No Action.",
                        "bell_notification_message" => "Lead Status No Action : [[**name**]] to [[**status**]]",
                        'email_subject' => "Lead Status No Action : [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::LEAD_VARIABLE,
                ],
                [
                    'title' => "Lead Change Status",
                    'type_key' => CommonConst::ACTION_CHANGE_STATUS,
                    'description' => 'Triggered when a lead is Status Change.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Lead Change Status",
                        'email_body' => "<h2>Lead Status Change </h2><p>Lead Name: [[**name**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Lead [[**name**]] has been Status No Action",
                        "sms_message" => "Lead [[**name**]] Status No Action.",
                        "bell_notification_message" => "Lead Status No Action : [[**name**]] to [[**status**]]",
                        'email_subject' => "Lead Status Change : [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::LEAD_VARIABLE,
                ],
                [
                    'title' => "Lead Append Note",
                    'type_key' => CommonConst::ACTION_APPEND_NOTE,
                    'description' => 'Triggered when a lead is Append Note.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Lead Append Note",
                        'email_body' => "<h2>Lead Append Note </h2><p>Lead Name: [[**name**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Note : [[**note**]]</p>",
                        "whats_app_message" => "Lead [[**name**]] has Append Note [[**note**]]",
                        "sms_message" => "Lead [[**name**]] Append Note : [[**note**]]",
                        "bell_notification_message" => "Lead Append Note : [[**name**]] to [[**note**]]",
                        'email_subject' => "Lead Append Note : [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::LEAD_VARIABLE,
                ],

            ],
        ],
    ];
}
