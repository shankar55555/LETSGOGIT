<?php

namespace Modules\FollowUp\Constants;

use App\Constants\CommonConst;

class FollowUpConst
{
    const FOLLOW_UP_TRIGGER_ACTION_LIST = [];
    const FOLLOW_UP_HEADER_LIST = [
        [
            'title' => CommonConst::MODULE_FOLLOW_UP,
            'slug' => 'follow-up',
            'table' => 'follow-up',
            'headers' => [
                ['title' => 'Lead Prospect', 'key' => 'lead_prospect', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Call Status', 'key' => 'call_status', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Call Summary', 'key' => 'call_summary', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];
    const FOLLOW_UP_PERMISSION_LIST = [
        # 3. Follow Up Permission
        [
            'name' => CommonConst::MODULE_FOLLOW_UP,
            'position' => 3,
            "icon" => 'tabler-hierarchy-2',
            "category" => [
                [
                    'name' => 'Follow Up',
                    "permission_list" => [
                        ["name" => 'View Follow Ups', "action" => "followUp", "slug" => 'view'],
                        ["name" => 'Create Follow Up', "action" => "followUp", "slug" => 'create'],
                        ["name" => 'Delete Follow Up', "action" => "followUp", "slug" => 'delete'],
                        ["name" => 'Edit Follow Up', "action" => "followUp", "slug" => 'edit'],
                        ["name" => 'View Activity Timeline', "action" => "followUp", "slug" => 'activity-timeline'],
                    ]
                ],
            ]
        ],
    ];
    const CALL_PICKED = "call-picked";
    const CALL_NOT_PICKED = "call-not-picked";
    const CALL_LATER = "call-later";
    # Follow Up page statuses 7
    const FOLLOW_UP_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_FOLLOW_UP,
            'position' => 2,
            'statuses' => [
                ["status_text" => "Call Picked", "slug" => self::CALL_PICKED, "status_color" => "#28a745", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Green
                ["status_text" => "Call Not Picked", "slug" => self::CALL_NOT_PICKED, "status_color" => "#dc3545", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Red
                ["status_text" => "Call Later", "slug" => self::CALL_LATER, "status_color" => "#ffc107", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null], // Yellow
            ]
        ],
    ];

    const HIGH = "High";
    const ENABLE = "Enable";
    const FOLLOW_UP_VARIABLE = ["company_name", 'call_status', 'lead_prospect', 'call_summary', 'created_by', 'last_updated_by', 'lead_id', 'client_id', 'next_call_datetime', 'need_site_visit', 'site_visit_datetime', 'site_visit_user_id', "created_at"];
    const RULE_FOLLOW_UP_CREATED = "followup-created"; # done
    const RULE_FOLLOW_UP_DUE = "follow-up-due"; # job done
    const RULE_FOLLOW_UP_OVERDUE = "follow-up-overdue"; # job done
    const FOLLOW_UP_EMAIL_TYPE_LIST = [self::RULE_FOLLOW_UP_CREATED, self::RULE_FOLLOW_UP_DUE, self::RULE_FOLLOW_UP_OVERDUE];
    const FOLLOW_UP_RULE_LIST = [
        [
            'module' => CommonConst::MODULE_FOLLOW_UP,
            'status' => 'active',
            'trigger_event' => [
                [
                    'name' => 'Followup Created',
                    'slug' => self::RULE_FOLLOW_UP_CREATED,
                    'allow_condition' => false,
                    'condition' => null,
                    'actionList' => [
                        [
                            'action' => 'Send Notification',
                            'slug' => CommonConst::ACTION_SEND_NOTIFICATION,
                            'recipient_list' => ['Admins', 'Created By', 'Assigned User', 'Client User', 'Lead User'],
                            'notification_methods' => ['Email',  'Bell Notification', 'WhatsApp'],
                            'interval' => ['Immediate'],
                            'priority' => ['Low', 'Medium', 'High']
                        ]
                    ]
                ],
                [
                    'name' => 'Follow-up Due',
                    'slug' => self::RULE_FOLLOW_UP_DUE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => "Before Due", 'value' => "<"],
                            ['title' => "Equals To", 'value' => "=="]
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],

                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Next Call Date", "value" => "next_call_datetime"],
                            ["title" => "Site Visit Date", "value" => "site_visit_datetime"],
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
                    'name' => 'Follow-up Overdue',
                    'slug' => self::RULE_FOLLOW_UP_OVERDUE,
                    'allow_condition' => true,
                    'condition' => [
                        'control' => [
                            ['title' => 'More than', 'value' => '>'],
                            ['title' => 'Equal to', 'value' => '=='],
                        ],
                        'datatype' => [
                            ['title' => "Days", 'value' => "date"]
                        ],
                        'fields' => [
                            ["title" => "Created At", "value" => "created_at"],
                            ["title" => "Updated At", "value" => "updated_at"],
                            ["title" => "Next Call Date", "value" => "next_call_datetime"],
                            ["title" => "Site Visit Date", "value" => "site_visit_datetime"],
                        ]
                    ],
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
                            ["title" => "Next Call Date", "value" => "next_call_datetime"],
                            ["title" => "Site Visit Date", "value" => "site_visit_datetime"],
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
                    ]
                ]
            ]
        ],
    ];

    const FOLLOW_UP_RULE_ITEM_LIST = [
        [
            "rule" => "Followup created",
            "rule_slug" => self::RULE_FOLLOW_UP_CREATED,
            "condition_type" => null,
            "conditions" => '[{"module":"FollowUp","trigger_event":"followup-created","allow_condition":false,"operator":"","datatype":"","value":""}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
        [
            "rule" => "Follow Up Due Date Rule",
            "rule_slug" => self::RULE_FOLLOW_UP_DUE,
            "condition_type" => null,
            "conditions" => '[{"module":"FollowUp","trigger_event":"follow-up-due","allow_condition":true,"operator":"==","datatype":"date","value":"1" ,"field":"site_visit_datetime"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
        [
            "rule" => "Follow Up Over Due Date Rule",
            "rule_slug" => self::RULE_FOLLOW_UP_OVERDUE,
            "condition_type" => null,
            "conditions" => '[{"module":"FollowUp","trigger_event":"follow-up-overdue","allow_condition":true,"operator":">","datatype":"date","value":"2","field":"site_visit_datetime"}]',
            "actions" => '[{"action_type":"Send Notification","notification_methods":["Email","Bell Notification","WhatsApp"],"recipients":["Created By","Admins","Assigned User"],"interval":"Immediate","priority":"Low"}]',
            "status" => "active",
        ],
    ];

    const FOLLOW_UP_EMAIL_TEMPLATE_LIST = [
        [
            'category' => CommonConst::MODULE_FOLLOW_UP,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "New Followup Created",
                    'type_key' => self::RULE_FOLLOW_UP_CREATED,
                    'description' => 'Triggered when a new lead is created.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "New Followup Created",
                        'email_body' => "<h2>New Followup Created</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Lead Prospect: [[**lead_prospect**]]</p>
                                       <p>Created By: [[**created_by**]]</p>
                                       <p>Status: [[**call_status**]]</p>",
                        "whats_app_message" => "New followup created for [[**company_name**]]. Prospect: [[**lead_prospect**]], Status: [[**call_status**]]",
                        "sms_message" => "New followup: [[**company_name**]], Status: [[**call_status**]]",
                        "bell_notification_message" => "New followup created: [[**company_name**]]",
                        'email_subject' => "New followup Created: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::FOLLOW_UP_VARIABLE,
                ],
                [
                    'title' => "Follow-Up Due",
                    'type_key' => self::RULE_FOLLOW_UP_DUE,
                    'description' => 'Triggered when a follow-up is due on a lead.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Follow-Up Due",
                        'email_body' => "<h2>Follow-Up Due</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Next Call Date: [[**next_call_datetime**]]</p>
                                       <p>Current Status: [[**call_status**]]</p>
                                       <p>Summary: [[**call_summary**]]</p>",
                        "whats_app_message" => "Follow-up due for [[**company_name**]] on [[**next_call_datetime**]]. Current status: [[**call_status**]]",
                        "sms_message" => "Follow-up due: [[**company_name**]] on [[**next_call_datetime**]]",
                        "bell_notification_message" => "Follow-up due for [[**company_name**]]",
                        'email_subject' => "Follow-Up Due: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::FOLLOW_UP_VARIABLE,
                ],
                [
                    'title' => "Follow-Up Overdue",
                    'type_key' => self::RULE_FOLLOW_UP_OVERDUE,
                    'description' => 'Triggered when a follow-up is overdue on a lead.',
                    'is_delete' => false,
                    'template' => [
                        'title' => "Follow-Up Overdue",
                        'email_body' => "<h2>Follow-Up Overdue</h2>
                                       <p>Company: [[**company_name**]]</p>
                                       <p>Overdue Since: [[**next_call_datetime**]]</p>
                                       <p>Current Status: [[**call_status**]]</p>
                                       <p>Last Updated By: [[**last_updated_by**]]</p>",
                        "whats_app_message" => "Alert: Follow-up overdue for [[**company_name**]] since [[**next_call_datetime**]]. Status: [[**call_status**]]",
                        "sms_message" => "Overdue follow-up: [[**company_name**]] since [[**next_call_datetime**]]",
                        "bell_notification_message" => "Follow-up overdue: [[**company_name**]]",
                        'email_subject' => "Follow-Up Overdue: [[**company_name**]]",
                        'priority' => self::HIGH,
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'is_enable' => self::ENABLE
                    ],
                    'variables' => self::FOLLOW_UP_VARIABLE,
                ]
            ],
        ],
    ];
}
