<?php

namespace Modules\Invoices\Constants;

use App\Constants\CommonConst;

class InvoiceConst
{
    const TRIGGER_SEND_INVOICE = "send-invoice";
    const INVOICE_TRIGGER_ACTION_LIST = [
        ['title' => "Send Invoice", 'value' => self::TRIGGER_SEND_INVOICE]
    ];

    const INVOICE_HEADER_LIST = [
        # Invoices List Sidebar Menu 
        [
            'title' => 'Invoice List',
            'slug' => 'invoice-list',
            'table' => 'invoices',
            'headers' => [
                ['title' => 'Name', 'key' => 'generated_name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Invoice Number', 'key' => 'invoice_number', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Quotation Number', 'key' => 'quotation_number', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Title', 'key' => 'title', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'GST', 'key' => 'tax', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Discount', 'key' => 'discount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Due Date', 'key' => 'due_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total', 'key' => 'total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => false],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];
    const INVOICE_PERMISSION_LIST = [
        # 7. Invoices Permission
        [
            'name' => CommonConst::MODULE_INVOICE,
            'position' => 7,
            "icon" => 'tabler-businessplan',
            "category" => [
                [
                    'name' => 'Invoice List',
                    "permission_list" => [
                        ["name" => 'View Invoices', "action" => "invoice", "slug" => 'view'],
                        ["name" => 'View Invoice Details', "action" => "invoice", "slug" => 'show'],
                        ["name" => 'Create Invoice', "action" => "invoice", "slug" => 'create'],
                        ["name" => 'Edit Invoice', "action" => "invoice", "slug" => 'edit'],
                        ["name" => 'Send Invoice Message', "action" => "invoice", "slug" => 'send-message'],
                        ["name" => 'Delete Invoice', "action" => "invoice", "slug" => 'delete'],
                        ["name" => 'Export Invoices', "action" => "invoice", "slug" => 'export-list'],
                    ]
                ],
            ]
        ],
    ];
    const DRAFT =  "draft";
    const CREATED =  "created";
    const SENT =  "sent";
    const PARTIAL =  "partial";
    const PAID =  "paid";
    const PAID_TO_CANCELLED =  "paid-to-cancelled";
    # Invoice page statuses
    const INVOICE_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_INVOICE,
            'position' => 4,
            'statuses' => [
                ["status_text" => "Draft", "slug" => self::DRAFT, "status_color" => "#ffc107", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Created", "slug" => self::CREATED, "status_color" => "#6c757d", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                // ["status_text" => "Partial", "slug" => self::PARTIAL, "status_color" => "#17a2b8", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Sent", "slug" => self::SENT, "status_color" => "#17a2b8", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Paid", "slug" => self::PAID, "status_color" => "#28a745", "position" => 4, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Paid To Cancelled", "slug" => self::PAID_TO_CANCELLED, "status_color" => "#17a2b8", "position" => 5, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const RULE_INVOICE_CREATED = "invoice-created"; # done
    const RULE_DAYS_BEFORE_DUE = "days-before-due"; # Job - pending
    const RULE_AFTER_DUE_DATE = "after-due-date"; # Job - pending
    const RULE_PARTIAL_PAYMENT = "partial-payment";
    const RULE_FULL_PAYMENT = "full-payment"; # done
    const INVOICE_EMAIL_TYPE_LIST = [self::RULE_INVOICE_CREATED, self::RULE_DAYS_BEFORE_DUE, self::RULE_AFTER_DUE_DATE, self::RULE_PARTIAL_PAYMENT, self::RULE_FULL_PAYMENT];

    const INVOICE_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_INVOICE,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Invoice Created',
                    'slug' => self::RULE_INVOICE_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'Before Due',
                    'slug' => self::RULE_DAYS_BEFORE_DUE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Before due", 'value' => "<"],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Due Date", "value" => "due_date"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'After Due',
                    'slug' => self::RULE_AFTER_DUE_DATE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "More than", 'value' => ">"]
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Due Date", "value" => "due_date"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                // [
                //     'name' => 'Partial Payment',
                //     'slug' => self::RULE_PARTIAL_PAYMENT,
                //     'allow_condition' => true,
                //     'condition' => [
                //         'control' => [
                //             ['title' => "Amount less than", 'value' => "<"],
                //             ['title' => "Amount greater than", 'value' => ">"],
                //             ['title' => "Percentage less than", 'value' => "%<"],
                //             ['title' => "Percentage greater than", 'value' => "%>"]
                //         ],
                //         'datatype' => [
                //             ['title' => "Numeric", 'value' => "numeric"],
                //             ['title' => "Percentage", 'value' => "percentage"]
                //         ],
                //                 'fields' => [
                //     ["title" => "Created At", "value" => "created_at"],
                //     ["title" => "Updated At", "value" => "updated_at"],
                //     ["title" => "Due Date", "value" => "due_date"],
                // ]
                //     ],
                //     'actionList' => [
                //         [
                //             'action' => 'Send Notification',
                //             'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                //             'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                //             'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                //             'interval' => ['Immediate'],
                //             'priority' => ['Low', 'Medium', 'High', 'Critical']
                //         ]
                //     ]
                // ],
                [
                    'name' => 'Full Payment',
                    'slug' => self::RULE_FULL_PAYMENT,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
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
                            ['title' => "Number", 'value' => 'numeric'],
                            ['title' => "Text", 'value' => 'string'],
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Due Date", "value" => "due_date"],
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
                            'action' => 'Send Invoice PDF',
                            'slug' => self::TRIGGER_SEND_INVOICE,
                            'recipient_list' => ['Client User'], // 'Admins', 'Created By', 'Assigned User',
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

    const INVOICE_RULE_ITEM_LIST = [
        [
            "rule" => "Invoice Created",
            "rule_slug" => self::RULE_INVOICE_CREATED,
            "condition_type" => null,
            "conditions" => '[{"module":"Invoices","trigger_event":"invoice-created","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Sms","Bell Notification","WhatsApp","App"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
            "status" => "active",
        ],
        [
            "rule" => "Invoice Days Before Due",
            "rule_slug" => self::RULE_DAYS_BEFORE_DUE,
            "condition_type" => null,
            "conditions" => '[{"module":"Invoices","trigger_event":"days-before-due","allow_condition":true,"operator":"<","datatype":"date","value":"3","field":"due_date"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Sms","Bell Notification","WhatsApp","App"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
            "status" => "active",
        ],
        [
            "rule" => "Invoice After Due Date",
            "rule_slug" => self::RULE_AFTER_DUE_DATE,
            "condition_type" => null,
            "conditions" => '[{"module":"Invoices","trigger_event":"after-due-date","allow_condition":true,"operator":">","datatype":"date","value":"7","field":"due_date"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Sms","Bell Notification","WhatsApp","App"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
            "status" => "active",
        ],
        // [
        //     "rule" => "Invoice Partial Payment",
        //     "rule_slug" => self::RULE_PARTIAL_PAYMENT,
        //     "condition_type" => null,
        //     "conditions" => '[{"module":"Invoices","trigger_event":"partial-payment","allow_condition":false,"operator":"","datatype":"","value":""}]',
        //     "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Sms","Bell Notification","WhatsApp","App"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Medium"}]',
        //     "status" => "active",
        // ],
        [
            "rule" => "Invoice Full Payment",
            "rule_slug" => self::RULE_FULL_PAYMENT,
            "condition_type" => null,
            "conditions" => '[{"module":"Invoices","trigger_event":"full-payment","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Sms","Bell Notification","WhatsApp","App"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Critical"}]',
            "status" => "active",
        ],
    ];

    const HIGH = "High";
    const ENABLE = "Enable";
    const INVOICE_VARIABLE = ["company_name", 'invoice_number', 'title', 'description', 'items', 'amount_paid', 'sub_total', 'tax', 'discount', 'total', 'status', 'due_date', 'client_id', 'contract_id', 'quotation_id', 'created_by', 'last_updated_by', 'created_at'];
    const INVOICE_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_INVOICE,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Invoice Created",
                    'type_key' => self::RULE_INVOICE_CREATED,
                    'description' => 'Triggered when a new invoice is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Invoice Created",
                        'email_body' => "<p>Your invoice [[**invoice_number**]] has been created with a total amount of [[**total**]].</p>",
                        'whats_app_message' => "Invoice [[**invoice_number**]] created. Total: [[**total**]].",
                        'sms_message' => "Invoice [[**invoice_number**]] created with total [[**total**]].",
                        'bell_notification_message' => "Invoice [[**invoice_number**]] created.",
                        'email_subject' => "Invoice [[**invoice_number**]] Created",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'New invoice notification from [[**company_name**]]',
                        'is_enable' => self::ENABLE,
                    ],
                    'variables' => self::INVOICE_VARIABLE,
                ],
                [
                    'title' => "Reminder: Upcoming Due Date",
                    'type_key' => self::RULE_DAYS_BEFORE_DUE,
                    'description' => 'Reminder sent days before the invoice due date.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Reminder: Upcoming Due Date",
                        'email_body' => "<p>Your invoice [[**invoice_number**]] is due on [[**due_date**]]. Please make your payment to avoid late fees.</p>",
                        'whats_app_message' => "Reminder: Invoice [[**invoice_number**]] is due on [[**due_date**]].",
                        'sms_message' => "Invoice [[**invoice_number**]] due on [[**due_date**]].",
                        'bell_notification_message' => "Upcoming due date for Invoice [[**invoice_number**]].",
                        'email_subject' => "Upcoming Invoice Due Date: [[**due_date**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Invoice due soon - [[**company_name**]]',
                        'is_enable' => self::ENABLE,
                    ],
                    'variables' => self::INVOICE_VARIABLE,
                ],
                [
                    'title' => "Invoice Overdue",
                    'type_key' => self::RULE_AFTER_DUE_DATE,
                    'description' => 'Triggered when an invoice has passed its due date.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Invoice Overdue",
                        'email_body' => "<p>Your invoice [[**invoice_number**]] is now overdue. Please settle the payment immediately.</p>",
                        'whats_app_message' => "Invoice [[**invoice_number**]] is overdue. Please pay now.",
                        'sms_message' => "Overdue Invoice [[**invoice_number**]]. Settle it immediately.",
                        'bell_notification_message' => "Invoice [[**invoice_number**]] is overdue.",
                        'email_subject' => "Overdue Invoice: [[**invoice_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Payment overdue for invoice [[**invoice_number**]]',
                        'is_enable' => self::ENABLE,
                    ],
                    'variables' => self::INVOICE_VARIABLE,
                ],
                [
                    'title' => "Partial Payment Received",
                    'type_key' => self::RULE_PARTIAL_PAYMENT,
                    'description' => 'Triggered when a partial payment is received for an invoice.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Partial Payment Received",
                        'email_body' => "<p>We have received a partial payment of [[**amount_paid**]] for invoice [[**invoice_number**]].</p>",
                        'whats_app_message' => "Partial payment of [[**amount_paid**]] received for Invoice [[**invoice_number**]].",
                        'sms_message' => "Partial payment received for Invoice [[**invoice_number**]]: [[**amount_paid**]].",
                        'bell_notification_message' => "Partial payment received for Invoice [[**invoice_number**]].",
                        'email_subject' => "Partial Payment Received: [[**invoice_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Thank you for your payment - [[**company_name**]]',
                        'is_enable' => self::ENABLE,
                    ],
                    'variables' => self::INVOICE_VARIABLE,
                ],
                [
                    'title' => "Full Payment Received",
                    'type_key' => self::RULE_FULL_PAYMENT,
                    'description' => 'Triggered when full payment is received for an invoice.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Full Payment Received",
                        'email_body' => "<p>Thank you! Full payment has been received for invoice [[**invoice_number**]].</p>",
                        'whats_app_message' => "Full payment received for Invoice [[**invoice_number**]].",
                        'sms_message' => "Full payment received for Invoice [[**invoice_number**]]. Thank you!",
                        'bell_notification_message' => "Full payment received for Invoice [[**invoice_number**]].",
                        'email_subject' => "Invoice Paid: [[**invoice_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Payment complete for your invoice',
                        'is_enable' => self::ENABLE,
                    ],
                    'variables' => self::INVOICE_VARIABLE,
                ]
            ]
        ]
    ];
}
