<?php

namespace Modules\Clients\Constants;

use App\Constants\CommonConst;

class ClientConst
{
    const CONVERT_TO_LEAD = "convert_to_lead";
    const CLIENT_TRIGGER_ACTION_LIST = [
        ['title' => "Convert to Lead", 'value' =>  self::CONVERT_TO_LEAD]
    ];

    const CLIENT_HEADER_LIST = [
        # Client List Sidebar Menu 
        [
            'title' => 'Client List',
            'slug' => 'client-list',
            'table' => 'clients',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Contact Person', 'key' => 'contact_person', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'GST', 'key' => 'gst', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Secondary Phone', 'key' => 'secondary_phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Followup Status', 'key' => 'last_followup_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Quotation Status', 'key' => 'last_quotation_status', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'City', 'key' => 'city_id', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Date of Birth', 'key' => 'date_of_birth', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Anniversary Date', 'key' => 'anniversary_date', 'minWidth' => '200px', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => 'Reachout Client List',
            'slug' => 'reachout-client-header-list',
            'table' => 'clients',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Address', 'key' => 'address', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];

    const CLIENT_PERMISSION_LIST = [
        # 3. Clients Permission
        [
            'name' => CommonConst::MODULE_CLIENT,
            'position' => 3,
            "icon" => 'tabler-hierarchy-2',
            "category" => [
                [
                    'name' => 'Clients',
                    "permission_list" => [
                        ["name" => 'View Clients', "action" => "client", "slug" => 'view'],
                        ["name" => 'Create Client', "action" => "client", "slug" => 'create'],
                        ["name" => 'Edit Client', "action" => "client", "slug" => 'edit'],
                        ["name" => 'Assign Client', "action" => "client", "slug" => 'assign-to'],
                        ["name" => 'Export Clients', "action" => "client", "slug" => 'export-list'],
                        ["name" => 'Update Client Status', "action" => "client", "slug" => 'status-update'],
                        ["name" => 'Delete Client', "action" => "client", "slug" => 'delete'],
                        ["name" => 'View Client Details', "action" => "client", "slug" => 'show'],
                        ["name" => 'View Upcoming Birthdays/Anniversaries', "action" => "client-upcoming-dates", "slug" => 'view'],
                    ]

                ],
            ]
        ],
    ];

    const PENDING = "pending";
    const SUCCESS = "success";
    const FAILED = "failed";
    # Clients page statuses
    const CLIENT_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_CLIENT,
            'position' => 5,
            'statuses' => [
                ["status_text" => "Active", "slug" =>  CommonConst::ACTIVE, "status_color" => "#28a745", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "In Active", "slug" => CommonConst::IN_ACTIVE, "status_color" => "#6c757d", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const HIGH = "High";
    const ENABLE = "Enable";
    const CLIENT_VARIABLE = ["company_name", "name", "contact_person", "contact_person_role", "email", "phone", "status", "assigned_user", "created_by", "last_updated_by", "lead_id", 'created_at'];
    const RULE_CLIENT_CREATED = "client-created"; # done
    const RULE_ASSIGNED_TO_USER = "assigned-to-user-client"; # done
    const RULE_CLIENT_INACTIVE = "client-inactive"; # Job done
    const CLIENT_EMAIL_TYPE_LIST = [self::RULE_CLIENT_CREATED, self::RULE_ASSIGNED_TO_USER, self::RULE_CLIENT_INACTIVE];

    const CLIENT_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_CLIENT,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Client Created',
                    'slug' => self::RULE_CLIENT_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ]
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
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Client inactive',
                    'slug' => self::RULE_CLIENT_INACTIVE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "More than", 'value' => ">"],
                            ['title' => "Equal to", 'value' => "=="],
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
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User' . 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Medium', 'High', 'Critical']
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
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
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
                            'action' => 'Convert To Lead',
                            'slug' => self::CONVERT_TO_LEAD,
                        ]
                    ]
                ]
            ]
        ],
    ];

    const CLIENT_RULE_ITEM_LIST = [
        [
            'rule' => 'Client Create',
            'rule_slug' => self::RULE_CLIENT_CREATED,
            'condition_type' => null,
            'conditions' => '[{"module":"Clients","trigger_event":"client-created","allow_condition":false,"operator":"","datatype":"","value":""}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins"],"interval":"Immediate","priority":"Medium"}]',
            'status' => 'active',
        ],
        [
            'rule' => 'Client In-Active Rule',
            'rule_slug' => self::RULE_CLIENT_INACTIVE,
            'condition_type' => null,
            'conditions' => '[{"module":"Clients","trigger_event":"client-inactive","allow_condition":true,"operator":">","datatype":"date","value":"7","value":"7" ,"field":"updated_at"}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins"],"interval":"Immediate","priority":"High"}]',
            'status' => 'active',
        ],
        [
            'rule' => 'Client Assign to User',
            'rule_slug' => self::RULE_ASSIGNED_TO_USER,
            'condition_type' => null,
            'conditions' => '[{"module":"Clients","trigger_event":"assigned-to-user-client","allow_condition":false,"operator":"","datatype":"","value":""}]',
            'actions' => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
            'status' => 'active',
        ],
    ];

    const CLIENT_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_CLIENT,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "New Client Created",
                    'type_key' => self::RULE_CLIENT_CREATED,
                    'description' => 'Triggered when a new client is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "New Client Created",
                        'email_body' => "<h2>New Client Created</h2><p>Client Name: [[**name**]]</p><p>Contact Person: [[**contact_person**]]</p><p>Phone: [[**phone**]]</p><p>Email: [[**email**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "A new client named [[**name**]] has been created. Contact: [[**contact_person**]], Phone: [[**phone**]].",
                        "sms_message" => "New client [[**name**]] created. Contact: [[**contact_person**]], Phone: [[**phone**]].",
                        "bell_notification_message" => "New client created: [[**name**]]",
                        'email_subject' => "New Client Created: [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CLIENT_VARIABLE,
                ],
                [
                    'title' => "Client Assigned",
                    'type_key' => self::RULE_ASSIGNED_TO_USER,
                    'description' => 'Triggered when a client is assigned to a user.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Client Assigned",
                        'email_body' => "<h2>Client Assigned</h2><p>Client Name: [[**name**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Client [[**name**]] has been assigned to [[**assigned_user**]].",
                        "sms_message" => "Client [[**name**]] assigned to [[**assigned_user**]].",
                        "bell_notification_message" => "Client assigned: [[**name**]] to [[**assigned_user**]]",
                        'email_subject' => "Client Assigned: [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CLIENT_VARIABLE,
                ],
                [
                    'title' => "Client Inactive",
                    'type_key' => self::RULE_CLIENT_INACTIVE,
                    'description' => 'Triggered when a client/client becomes inactive.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Client Marked Inactive",
                        'email_body' => "<h2>Client Inactive</h2><p>Client Name: [[**name**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Client [[**name**]] is now marked as inactive.",
                        "sms_message" => "Client inactive: [[**name**]].",
                        "bell_notification_message" => "Client marked inactive: [[**name**]]",
                        'email_subject' => "Client Marked Inactive: [[**name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CLIENT_VARIABLE,
                ],
            ],
        ],
    ];
}
