<?php

namespace Modules\Quotations\Constants;

use App\Constants\CommonConst;

class QuotationConst
{
    const TRIGGER_SEND_QUOTATION = "send-quotation";
    const QUOTATION_TRIGGER_ACTION_LIST = [
        ['title' => "Send Quotation", 'value' => self::TRIGGER_SEND_QUOTATION]
    ];
    const QUOTATION_HEADER_LIST = [
        # Quotation List Sidebar Menu 
        [
            'title' => 'Quotation List',
            'slug' => 'quotation-list',
            'table' => 'quotations',
            'headers' => [
                ['title' => 'Quotation Number', 'key' => 'quotation_number', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Valid Up Till', 'key' => 'valid_uptil', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Quotation Type', 'key' => 'quotation_type', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Title', 'key' => 'title', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Discount', 'key' => 'discount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'GST', 'key' => 'tax', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total', 'key' => 'total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Amount Due', 'key' => 'amount_due', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'minWidth' => '200px', 'checked' => true],
                ['title' => 'Custom Header Text', 'key' => 'custom_header_text', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Payment Terms', 'key' => 'payment_terms', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Terms & Conditions', 'key' => 'terms_conditions', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => 'Quotation Page List',
            'slug' => 'quotation-page-list',
            'table' => 'quotations',
            'headers' => [
                ['title' => 'Name', 'key' => 'generated_name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Quotation Number', 'key' => 'quotation_number', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Valid Up Till', 'key' => 'valid_uptil', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Quotation Type', 'key' => 'quotation_type', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Title', 'key' => 'title', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Discount', 'key' => 'discount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'GST', 'key' => 'tax', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total', 'key' => 'total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Amount Due', 'key' => 'amount_due', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'minWidth' => '200px', 'checked' => true],
                ['title' => 'Custom Header Text', 'key' => 'custom_header_text', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Payment Terms', 'key' => 'payment_terms', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Terms & Conditions', 'key' => 'terms_conditions', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];


    const QUOTATION_PERMISSION_LIST = [
        # 4. Quotations Permission
        [
            'name' => CommonConst::MODULE_QUOTATION,
            'position' => 4,
            "icon" => 'tabler-users',
            "category" => [
                [
                    'name' => 'Quotation list',
                    "permission_list" => [
                        ["name" => 'View Quotations', "action" => "quotation", "slug" => 'view'],
                        ["name" => 'View Quotation Details', "action" => "quotation", "slug" => 'show'],
                        ["name" => 'Create Quotation', "action" => "quotation", "slug" => 'create'],
                        ["name" => 'Edit Quotation', "action" => "quotation", "slug" => 'edit'],
                        ["name" => 'Send Quotation', "action" => "quotation", "slug" => 'send-message'],
                        ["name" => 'Delete Quotation', "action" => "quotation", "slug" => 'delete'],
                        ["name" => 'Export Quotations', "action" => "quotation", "slug" => 'export-list'],
                    ]

                ],
            ]
        ],
    ];

    // const READY_FOR_QUOTATION = "ready-for-quotation";
    const QUOTATION_DRAFT = "quotation-draft";
    const QUOTATION_CREATED = "quotation-created";
    const QUOTATION_SENT = "quotation-sent";
    // const QUOTATION_IN_PROGRESS_25 = "quotation-in-progress-25";
    // const QUOTATION_IN_PROGRESS_50 = "quotation-in-progress-50";
    // const QUOTATION_IN_PROGRESS_75 = "quotation-in-progress-75";
    # TODO: this const use to dashboard service change to plz change service in 
    const QUOTATION_ACCEPTED = "quotation-accepted";
    const QUOTATION_REJECTED = "quotation-rejected";
    const QUOTATION_EXPIRED = "quotation-expired";

    # Quotations page statuses
    const QUOTATION_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_QUOTATION,
            'position' => 3,
            'statuses' => [
                // ["status_text" => "Ready For Quotation", "slug" => self::READY_FOR_QUOTATION, "status_color" => "#007bff", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Quotation Draft", "slug" => self::QUOTATION_DRAFT, "status_color" => "#17a2b8", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Quotation Created", "slug" => self::QUOTATION_CREATED, "status_color" => "#17a2b8", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Quotation Sent", "slug" => self::QUOTATION_SENT, "status_color" => "#17a2b8", "position" => 4, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                // ["status_text" => "Quotation in progress 25 %", "slug" => self::QUOTATION_IN_PROGRESS_25, "status_color" => "#ffc107", "position" => 5, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                // ["status_text" => "Quotation in progress 50 %", "slug" => self::QUOTATION_IN_PROGRESS_50, "status_color" => "#ffc107", "position" => 6, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                // ["status_text" => "Quotation in progress 75 %", "slug" => self::QUOTATION_IN_PROGRESS_75, "status_color" => "#ffc107", "position" => 7, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Quotation Accepted", "slug" => self::QUOTATION_ACCEPTED, "status_color" => "#28a745", "position" => 8, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Green
                ["status_text" => "Quotation Rejected", "slug" => self::QUOTATION_REJECTED, "status_color" => "#dc3545", "position" => 9, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Quotation Expired", "slug" => self::QUOTATION_EXPIRED, "status_color" => "#dc3545", "position" => 10, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const RULE_QUOTATION_CREATED = "quotation-created"; # done
    const RULE_QUOTATION_ACCEPTED = "quotation-accepted"; # done
    const RULE_QUOTATION_REJECTED = "quotation-rejected"; # done
    const RULE_QUOTATION_EXPIRED = "quotation-expired"; # Job done
    const QUOTATION_EMAIL_TYPE_LIST = [self::RULE_QUOTATION_CREATED, self::RULE_QUOTATION_ACCEPTED, self::RULE_QUOTATION_REJECTED, self::RULE_QUOTATION_EXPIRED];

    const QUOTATION_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_QUOTATION,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Quotation Created',
                    'slug' => self::QUOTATION_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Quotation Accepted',
                    'slug' => self::QUOTATION_ACCEPTED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Quotation Rejected',
                    'slug' => self::RULE_QUOTATION_REJECTED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Quotation Expired',
                    'slug' => self::RULE_QUOTATION_EXPIRED,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Days before expiry", 'value' => "<"],
                            ['title' => "Equals To", 'value' => "=="],

                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Valid Uptil", "value" => "valid_uptil"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
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
                            ["title" => "Valid Uptil", "value" => "valid_uptil"],
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
                            'action' => 'Send Quotation PDF',
                            'slug' => self::TRIGGER_SEND_QUOTATION,
                            'recipient_list' => ['Client User', 'Lead User'], // 'Admins', 'Created By', 'Assigned User',
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

    const QUOTATION_RULE_ITEM_LIST = [
        [
            "rule" => "Quotation Create",
            "rule_slug" => self::RULE_QUOTATION_CREATED,
            "condition_type" => null,
            "conditions" => '[{"module":"Quotations","trigger_event":"quotation-created","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["App","WhatsApp","Sms","Email","Bell Notification"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"High"}]',
            "status" => "active",
        ],
        [
            "rule" => "Quotation Accepted",
            "rule_slug" => self::RULE_QUOTATION_ACCEPTED,
            "condition_type" => null,
            "conditions" => '[{"module":"Quotations","trigger_event":"quotation-accepted","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["App","WhatsApp","Bell Notification","Email","Sms"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"High"}]',
            "status" => "active",
        ],
        [
            "rule" => "Quotation Rejected",
            "rule_slug" => self::RULE_QUOTATION_REJECTED,
            "condition_type" => null,
            "conditions" => '[{"module":"Quotations","trigger_event":"quotation-rejected","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["App","Bell Notification","Sms","Email","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"High"}]',
            "status" => "active",
        ],
        [
            "rule" => "Quotation Expired",
            "rule_slug" => self::RULE_QUOTATION_EXPIRED,
            "condition_type" => null,
            "conditions" => '[{"module":"Quotations","trigger_event":"quotation-expired","allow_condition":true,"operator":"<","datatype":"date","value":"2","field":"updated_at"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["App","WhatsApp","Sms","Email","Bell Notification"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"High"}]',
            "status" => "active",
        ],
    ];

    const HIGH = "High";
    const ENABLE = "Enable";
    const QUOTATION_VARIABLE = ["company_name", "quotation_number", "valid_uptil", "quotation_type", "title", "sub_total", "discount", "tax", "total", "status", "items", "custom_header_text", "payment_terms", "terms_conditions", "lead_id", "client_id", "created_by", "last_updated_by", 'created_at']; # "contract_id",
    const QUOTATION_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_QUOTATION,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Quotation Created",
                    'type_key' => self::RULE_QUOTATION_CREATED,
                    'description' => 'Triggered when a new quotation is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Quotation Created",
                        'email_body' => "<h2>New Quotation: [[**quotation_number**]]</h2><p>Description: [[**description**]]</p><p>Valid Until: [[**valid_uptil**]]</p>",
                        'whats_app_message' => "A new quotation [[**quotation_number**]] has been created. Valid until [[**valid_uptil**]].",
                        'sms_message' => "Quotation [[**quotation_number**]] created. Valid until [[**valid_uptil**]].",
                        'bell_notification_message' => "Quotation [[**quotation_number**]] has been created successfully.",
                        'email_subject' => "[[**quotation_number**]] has been created",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::QUOTATION_VARIABLE,
                ],
                [
                    'title' => "Quotation Accepted",
                    'type_key' => self::RULE_QUOTATION_ACCEPTED,
                    'description' => 'Triggered when the client accepts the quotation.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Quotation Accepted",
                        'email_body' => "<h2>Quotation Accepted: [[**quotation_number**]]</h2><p>Accepted by Client ID: [[**client_id**]]</p><p>Status: [[**status**]]</p>",
                        'whats_app_message' => "Client [[**client_id**]] has accepted quotation [[**quotation_number**]].",
                        'sms_message' => "Quotation [[**quotation_number**]] has been accepted.",
                        'bell_notification_message' => "Quotation [[**quotation_number**]] accepted by client.",
                        'email_subject' => "Quotation [[**quotation_number**]] accepted",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::QUOTATION_VARIABLE,
                ],
                [
                    'title' => "Quotation Rejected",
                    'type_key' => self::RULE_QUOTATION_REJECTED,
                    'description' => 'Triggered when the client rejects the quotation.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Quotation Rejected",
                        'email_body' => "<h2>Quotation Rejected: [[**quotation_number**]]</h2><p>Status: [[**status**]]</p>",
                        'whats_app_message' => "Client has rejected quotation [[**quotation_number**]].",
                        'sms_message' => "Quotation [[**quotation_number**]] was rejected.",
                        'bell_notification_message' => "Quotation [[**quotation_number**]] rejected by client.",
                        'email_subject' => "Quotation [[**quotation_number**]] rejected",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::QUOTATION_VARIABLE,
                ],
                [
                    'title' => "Quotation Expired",
                    'type_key' => self::RULE_QUOTATION_EXPIRED,
                    'description' => 'Triggered when a quotation has expired.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Quotation Expired",
                        'email_body' => "<h2>Quotation Expired: [[**quotation_number**]]</h2><p>Valid Until: [[**valid_uptil**]]</p><p>Status: [[**status**]]</p>",
                        'whats_app_message' => "Quotation [[**quotation_number**]] has expired. Valid until [[**valid_uptil**]].",
                        'sms_message' => "Quotation [[**quotation_number**]] expired.",
                        'bell_notification_message' => "Quotation [[**quotation_number**]] has expired.",
                        'email_subject' => "Quotation [[**quotation_number**]] has expired",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::QUOTATION_VARIABLE,
                ],
            ],
        ],
    ];
}
