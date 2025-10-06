<?php

namespace Modules\Contracts\Constants;

use App\Constants\CommonConst;
use Modules\AlertAndNotification\Constants\EmailConst;

class ContractConst
{
    const CONTRACT_HEADER_LIST = [
        # Contract List Sidebar Menu 
        [
            'title' => 'Contract List',
            'slug' => 'contract-list',
            'table' => 'contracts',
            'headers' => [
                ['title' => 'Title', 'key' => 'title', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Start Date', 'key' => 'start_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'End Date', 'key' => 'end_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Discount', 'key' => 'discount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Tax', 'key' => 'tax', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total', 'key' => 'total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];
    const CONTRACT_PERMISSION_LIST = [
        # 5. Contracts Permission
        [
            'name' => CommonConst::MODULE_CONTRACT,
            'position' => 5,
            "icon" => 'tabler-businessplan',
            "category" => [
                [
                    'name' => 'Contracts List',
                    "permission_list" => [
                        ["name" => 'View Contract', "action" => "contract", "slug" => 'view'],
                        ["name" => 'View Contract List Item Info', "action" => "contract", "slug" => 'show'],
                        ["name" => 'Create Contract', "action" => "contract", "slug" => 'create'],
                        ["name" => 'Edit Contract', "action" => "contract", "slug" => 'edit'],
                        ["name" => 'Delete Contract', "action" => "contract", "slug" => 'delete'],
                        ["name" => 'Contract Export list', "action" => "contract", "slug" => 'export-list'],
                    ]
                ],
            ]
        ],
    ];

    const EXPIRED = "expired";
    const RENEW = "renew";
    # Contracts page statuses
    const CONTRACT_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_CONTRACT,
            'position' => 6,
            'statuses' => [
                ["status_text" => "Active", "slug" => CommonConst::ACTIVE, "status_color" => "#28a745", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "In Active", "slug" => CommonConst::IN_ACTIVE, "status_color" => "#6c757d", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Expired", "slug" => self::EXPIRED, "status_color" => "#dc3545", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Renew", "slug" => self::RENEW, "status_color" => "#007bff", "position" => 4, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const RULE_CONTRACT_CREATED = "contract-created";
    const RULE_SENT_TO_CLIENT = "sent-to-client";
    const RULE_CLIENT_SIGNED = "client-signed";
    const RULE_ISSUE_RAISED = "issue-raised";
    const RULE_CONTRACT_END_DATE_PASSED = "contract-end-date-passed";
    const RULE_DAYS_BEFORE_EXPIRY = "days-before-expiry";
    const RULE_RENEWAL_PAID = "renewal-paid";
    const RULE_TERMINATED  = "terminated";

    const CONTRACT_EMAIL_TYPE_LIST = [
        self::RULE_CONTRACT_CREATED,
        self::RULE_SENT_TO_CLIENT,
        self::RULE_CLIENT_SIGNED,
        self::RULE_ISSUE_RAISED,
        self::RULE_CONTRACT_END_DATE_PASSED,
        self::RULE_DAYS_BEFORE_EXPIRY,
        self::RULE_RENEWAL_PAID,
        self::RULE_TERMINATED,
    ];

    const CONTRACT_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_CONTRACT,
            'trigger_event' => [
                [
                    'name' => 'Contract Created',
                    'slug' => self::RULE_CONTRACT_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Sent to Client',
                    'slug' => self::RULE_SENT_TO_CLIENT,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Client Signed',
                    'slug' => self::RULE_CLIENT_SIGNED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Issue Raised',
                    'slug' => self::RULE_ISSUE_RAISED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Contract End Date Passed',
                    'slug' => self::RULE_CONTRACT_END_DATE_PASSED,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Days before end", 'value' => "<"],
                            ['title' => "Days after end", 'value' => ">"]
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Days Before Expiry',
                    'slug' => self::RULE_DAYS_BEFORE_EXPIRY,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Days before expiry", 'value' => "<"],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                        ]
                    ],
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Renewal Paid',
                    'slug' => self::RULE_RENEWAL_PAID,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ],
                [
                    'name' => 'Terminated',
                    'slug' => self::RULE_TERMINATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'type' => 'notification',
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Last Updated By']
                        ]
                    ]
                ]
            ]
        ],
    ];
    const CONTRACT_RULE_ITEM_LIST = [];
    const HIGH = "High";
    const ENABLE = "Enable";
    const CONTRACT_VARIABLE = ["company_name", "contract_name", 'title', 'description', 'items', 'start_date', 'end_date', 'sub_total', 'discount', 'tax', 'total', 'status', 'client_id', 'quotation_id', 'invoice_id', 'created_by', 'last_updated_by', "created_at"];
    const CONTRACT_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_CONTRACT,
            'status' => 'In-active',
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Contract Created",
                    'type_key' => self::RULE_CONTRACT_CREATED,
                    'description' => 'Triggered when a new contract is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Created",
                        'email_body' => "<h2>New Contract: [[**contract_name**]]</h2><p>Description: [[**description**]]</p><p>Start Date: [[**start_date**]], End Date: [[**end_date**]]</p>",
                        "whats_app_message" => "New Contract Created: [[**contract_name**]], Start: [[**start_date**]], End: [[**end_date**]]",
                        "sms_message" => "New Contract: [[**contract_name**]] (Start: [[**start_date**]], End: [[**end_date**]])",
                        "bell_notification_message" => "Contract [[**contract_name**]] has been created.",
                        'email_subject' => "[[**contract_name**]] has been created",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Contract Sent to Client",
                    'type_key' => self::RULE_SENT_TO_CLIENT,
                    'description' => 'Triggered when a contract is sent to the client.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Sent to Client",
                        'email_body' => "<h2>[[**contract_name**]] Sent</h2><p>Client ID: [[**client_id**]]</p><p>Contract Total: [[**total**]]</p>",
                        "whats_app_message" => "Contract [[**contract_name**]] sent to client. Total: [[**total**]]",
                        "sms_message" => "Contract [[**contract_name**]] sent. Total: [[**total**]]",
                        "bell_notification_message" => "Contract [[**contract_name**]] was sent to the client.",
                        'email_subject' => "Contract [[**contract_name**]] sent to client",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Contract Signed by Client",
                    'type_key' => self::RULE_CLIENT_SIGNED,
                    'description' => 'Triggered when a client signs the contract.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Signed by Client",
                        'email_body' => "<h2>Client Signed: [[**contract_name**]]</h2><p>Status: [[**status**]]</p><p>Signed by Client ID: [[**client_id**]]</p>",
                        "whats_app_message" => "Client signed contract: [[**contract_name**]]",
                        "sms_message" => "Contract [[**contract_name**]] signed by client.",
                        "bell_notification_message" => "Client signed [[**contract_name**]].",
                        'email_subject' => "Contract [[**contract_name**]] signed by client",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Issue Raised",
                    'type_key' => self::RULE_ISSUE_RAISED,
                    'description' => 'Triggered when an issue is raised for a contract.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Issue raised",
                        'email_body' => "<h2>Issue Raised for: [[**contract_name**]]</h2><p>Description: [[**description**]]</p><p>Raised by: [[**last_updated_by**]]</p>",
                        "whats_app_message" => "Issue raised for [[**contract_name**]] by [[**last_updated_by**]].",
                        "sms_message" => "Issue with [[**contract_name**]] reported.",
                        "bell_notification_message" => "Issue raised on [[**contract_name**]].",
                        'email_subject' => "Issue raised for [[**contract_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Contract End Date Passed",
                    'type_key' => self::RULE_CONTRACT_END_DATE_PASSED,
                    'description' => 'Triggered when the contract end date has passed.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Expired",
                        'email_body' => "<h2>Contract Expired: [[**contract_name**]]</h2><p>End Date: [[**end_date**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Contract [[**contract_name**]] has expired on [[**end_date**]].",
                        "sms_message" => "Contract [[**contract_name**]] expired.",
                        "bell_notification_message" => "Contract [[**contract_name**]] has passed the end date.",
                        'email_subject' => "Contract [[**contract_name**]] expired",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Days Before Expiry Reminder",
                    'type_key' => self::RULE_DAYS_BEFORE_EXPIRY,
                    'description' => 'Reminder before contract expiry date.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Reminder Expire soon",
                        'email_body' => "<h2>Reminder: [[**contract_name**]] is expiring soon</h2><p>End Date: [[**end_date**]]</p><p>Total Amount: [[**total**]]</p>",
                        "whats_app_message" => "Reminder: [[**contract_name**]] is expiring on [[**end_date**]].",
                        "sms_message" => "Reminder: [[**contract_name**]] expiring soon.",
                        "bell_notification_message" => "Reminder: [[**contract_name**]] will expire soon.",
                        'email_subject' => "Reminder: [[**contract_name**]] will expire soon",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Renewal Paid",
                    'type_key' => self::RULE_RENEWAL_PAID,
                    'description' => 'Triggered when a contract is renewed and payment is received.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Renewed",
                        'email_body' => "<h2>Contract Renewed: [[**contract_name**]]</h2><p>Renewal Paid by: [[**client_id**]]</p><p>Amount Paid: [[**total**]]</p>",
                        "whats_app_message" => "Renewal payment received for [[**contract_name**]].",
                        "sms_message" => "Contract [[**contract_name**]] renewed successfully.",
                        "bell_notification_message" => "Renewal payment received for [[**contract_name**]].",
                        'email_subject' => "Renewal Payment Received for [[**contract_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
                [
                    'title' => "Contract Terminated",
                    'type_key' => self::RULE_TERMINATED,
                    'description' => 'Triggered when a contract is terminated.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Contract Terminated",
                        'email_body' => "<h2>Contract Terminated : [[**contract_name**]]</h2><p>Terminated by: [[**last_updated_by**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Contract [[**contract_name**]] has been terminated.",
                        "sms_message" => "Contract [[**contract_name**]] was terminated.",
                        "bell_notification_message" => "Contract [[**contract_name**]] has been terminated.",
                        'email_subject' => "Contract [[**contract_name**]] has been terminated",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::CONTRACT_VARIABLE,
                ],
            ],
        ],
    ];

    # Scheduling Module Rule And template 
    const SCHEDULING_HEADER_LIST = [];
    const SCHEDULING_PERMISSION_LIST = [];
    const SCHEDULING_MODULE_STATUS_LIST = [];

    const RULE_JOB_SCHEDULED = "job-scheduled";
    const RULE_JOB_STARTED = "job-started";
    const RULE_JOB_COMPLETED = "job-completed";
    const RULE_JOB_FAILED = "job-failed";
    const SCHEDULING_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_SCHEDULING,
            'status' => 'In-active',
            'trigger_event' => [
                [
                    'name' => 'Job Scheduled',
                    'slug' => self::RULE_JOB_SCHEDULED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'Job Started',
                    'slug' => self::RULE_JOB_STARTED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'Job Completed',
                    'slug' => self::RULE_JOB_COMPLETED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ],
                [
                    'name' => 'Job Failed',
                    'slug' => self::RULE_JOB_FAILED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User'],
                            'notification_methods' => ['Email', 'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High', 'Critical']
                        ]
                    ]
                ]
            ]
        ],
    ];

    // const HIGH = "High";
    // const ENABLE = "Enable";
    const SCHEDULING_VARIABLE = ["company_name", 'contract_id', 'contract_number', 'client_id', 'service_id', 'assigned_user', 'schedule_data', 'frequency', 'status'];
    const SCHEDULING_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_SCHEDULING,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Job Scheduled",
                    'type_key' => self::RULE_JOB_SCHEDULED,
                    'description' => 'Triggered when a job is scheduled.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Job Scheduled",
                        'email_body' => "<h2>Job Scheduled</h2><p>Contract ID: [[**contract_id**]]</p><p>Assigned To: [[**assigned_user**]]</p><p>Schedule: [[**schedule_data**]]</p><p>Frequency: [[**frequency**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Job Scheduled\nContract ID: [[**contract_id**]]\nAssigned To: [[**assigned_user**]]\nSchedule: [[**schedule_data**]]\nFrequency: [[**frequency**]]\nStatus: [[**status**]]",
                        "sms_message" => "Job Scheduled - Contract: [[**contract_id**]], Assigned To: [[**assigned_user**]], Schedule: [[**schedule_data**]], Frequency: [[**frequency**]], Status: [[**status**]]",
                        "bell_notification_message" => "Job Scheduled for Contract [[**contract_id**]] - Assigned to [[**assigned_user**]]",
                        'email_subject' => "Job Scheduled for Contract [[**contract_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SCHEDULING_VARIABLE,
                ],
                [
                    'title' => "Job Rescheduled",
                    'type_key' => self::RULE_JOB_FAILED,
                    'description' => 'Triggered when a scheduled job is rescheduled.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Job Rescheduled",
                        'email_body' => "<h2>Job Rescheduled</h2><p>Contract ID: [[**contract_id**]]</p><p>New Schedule: [[**schedule_data**]]</p><p>Updated By: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Job Rescheduled\nContract ID: [[**contract_id**]]\nNew Schedule: [[**schedule_data**]]\nUpdated By: [[**assigned_user**]]\nStatus: [[**status**]]",
                        "sms_message" => "Job Rescheduled - Contract: [[**contract_id**]], New Schedule: [[**schedule_data**]], Updated By: [[**assigned_user**]], Status: [[**status**]]",
                        "bell_notification_message" => "Job Rescheduled for Contract [[**contract_id**]] - Updated by [[**assigned_user**]]",
                        'email_subject' => "Job Rescheduled for Contract [[**contract_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SCHEDULING_VARIABLE,
                ],
                [
                    'title' => "Job Started",
                    'type_key' => self::RULE_JOB_STARTED,
                    'description' => 'Triggered when a job has started.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Job Started",
                        'email_body' => "<h2>Job Started</h2><p>Contract ID: [[**contract_id**]]</p><p>Started By: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Job Started\nContract ID: [[**contract_id**]]\nStarted By: [[**assigned_user**]]\nStatus: [[**status**]]",
                        "sms_message" => "Job Started - Contract: [[**contract_id**]], Started By: [[**assigned_user**]], Status: [[**status**]]",
                        "bell_notification_message" => "Job Started for Contract [[**contract_id**]] - By [[**assigned_user**]]",
                        'email_subject' => "Job Started for Contract [[**contract_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SCHEDULING_VARIABLE,
                ],
                [
                    'title' => "Job Completed",
                    'type_key' => self::RULE_JOB_COMPLETED,
                    'description' => 'Triggered when a job has been completed.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Job Completed",
                        'email_body' => "<h2>Job Completed</h2><p>Contract ID: [[**contract_id**]]</p><p>Completed By: [[**assigned_user**]]</p><p>Status: [[**status**]]</p>",
                        "whats_app_message" => "Job Completed\nContract ID: [[**contract_id**]]\nCompleted By: [[**assigned_user**]]\nStatus: [[**status**]]",
                        "sms_message" => "Job Completed - Contract: [[**contract_id**]], Completed By: [[**assigned_user**]], Status: [[**status**]]",
                        "bell_notification_message" => "Job Completed for Contract [[**contract_id**]] - By [[**assigned_user**]]",
                        'email_subject' => "Job Completed for Contract [[**contract_number**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::SCHEDULING_VARIABLE,
                ]
            ],
        ],
    ];
}
