<?php

namespace App\Constants;

class CommonConst
{
    # Account Types

    const TESTING_EMAIL = 'surya@gmail.com';
    const TESTING_EMAIL_PASSWORD = 'admin@123';
    const ACCOUNT = 'Account';
    const ACCOUNT_LOGIN = 'account_login';
    const PASSWORD_RESET = 'password_reset';
    const FORGET_PASSWORD = 'forget_password';
    const REGISTER_USER = 'register_user';
    const UPDATE_PASSWORD = 'update_password';
    const EMAIL_VERIFY_SEND_OTP = 'email_verify_send_otp';

    const MODULE_ALERT_AND_NOTIFICATION = 'AlertAndNotification';
    const MODULE_ATTENDANCE = 'Attendance';
    const MODULE_CLIENT = 'Clients';
    const MODULE_USER = 'Users';
    const MODULE_CONTRACT = 'Contracts';
    const MODULE_DASHBOARD = 'Dashboard';
    const MODULE_FOLLOW_UP = 'FollowUp';
    const MODULE_INVOICE = 'Invoices';
    const MODULE_LEAD = 'Leads';
    const MODULE_PAYMENT = 'Payments';
    const MODULE_PRODUCT_SERVICE = 'ProductService';
    const MODULE_ACCOUNT = 'Accounts';
    const MODULE_QUOTATION = 'Quotations';
    const MODULE_ROLE_PERMISSION = 'RolePermission';
    const MODULE_SCHEDULING = 'Scheduling';
    const MODULE_SITE_VISIT = 'SiteVisit';
    const MODULE_TARGETS = 'Targets';
    const B_TO_B = 'B2B';
    const ROLES = 'Roles';
    const RULES = 'Rules';
    const EXPORT_LOG = 'Export Log';
    const NOTIFICATION_LOG = 'Notification Log';

    const PRESENT = 'present';
    const HALF_PRESENT = 'half-present';
    const ABSENT = 'absent';

    const EMAIL = 'Email';
    const SMS = 'Sms';
    const BELL_NOTIFICATION = 'Bell Notification';
    const WHATSAPP = 'WhatsApp';
    const APP = 'App';

    const SEND_NOTIFICATION_PLAT_FORM = [self::EMAIL, self::SMS, self::BELL_NOTIFICATION, self::WHATSAPP, self::APP];

    const SELECT_FILE = 'Select File';
    const AUTO_SEND_FILE = 'Auto Send File';
    const NO_ATTACHMENT = 'No Attachment';
    const SEND_ATTACHMENT_TYPE_LIST = [self::SELECT_FILE, self::AUTO_SEND_FILE, self::NO_ATTACHMENT];

    const CRITICAL = 'Critical';
    const LOW = 'Low';
    const MEDIUM = 'Medium';
    const HIGH = 'High';

    const PENDING = 'Pending';
    const SUCCESS = 'Success';
    const FAILED = 'Failed';

    const REQUIRED = 'Required';
    const DISABLE = 'Disable';
    const ENABLE = 'Enable';

    const SEND_VIA = 'Notification';

    const UN_READ = 0;
    const READ = 1;

    const ACTION_SEND_NOTIFICATION = 'Send Notification';
    const ACTION_CHANGE_STATUS = 'change-status';
    const ACTION_APPEND_NOTE = 'append-note';

    const RULE_STATUS_TRIGGER = "status-trigger";

    const ACTIVE = 'active';
    const IN_ACTIVE = 'in-active';

    const ACCOUNT_EMAIL_LIST = [
        [
            'category' => CommonConst::ACCOUNT,
            'is_delete' => false,
            'type' => [
                [
                    'title' => "Account Login",
                    'type_key' => CommonConst::ACCOUNT_LOGIN,
                    'description' => 'Description of Account Login Email.',
                    'is_delete' => false,
                    'template' => [
                        "title" => "Account Login",
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'email_subject' => "Was This You? Recent Login Detected",
                        'email_body' => "<h2>[[**name**]] , Did You Login ?</h2><p>We noticed the login for your [[**company_name**]] account was recently. If this was you, you can safely disregard this email.</p><p>[[**request_device_info**]]</p>",
                        "whats_app_message" => "[[**name**]], was this your login attempt? [[**request_device_info**]]",
                        "sms_message" => "Hi [[**name**]], we noticed a login attempt. [[**request_device_info**]]",
                        "bell_notification_message" => "Login detected for [[**name**]]. [[**request_device_info**]]",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::REQUIRED
                    ],
                    'variables' => ["company_name", "name", "request_device_info"],
                ],
                [
                    'title' => "Password Reset",
                    'type_key' => CommonConst::PASSWORD_RESET,
                    'description' => 'Description of Password Reset.',
                    'is_delete' => false,
                    'template' => [
                        "title" => "Password Reset",
                        'hidden_pre_header' => 'Password change Notification',
                        'email_subject' => "Account Password Changed!",
                        'email_body' => "<center><h2> [[**name**]] , Did You change your password ?</h2></center><p>We noticed the password for your [[**company_name**]] account was recently changed.If this was you, you can safely disregard this email.</p><p>[[**request_device_info**]]</p>",
                        "whats_app_message" => "[[**name**]], your password was recently changed. [[**request_device_info**]]",
                        "sms_message" => "Hi [[**name**]], we detected a password change. [[**request_device_info**]]",
                        "bell_notification_message" => "Password update noticed for [[**name**]]. [[**request_device_info**]]",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::REQUIRED
                    ],
                    'variables' => ["company_name", "name", "email", "phone", "request_device_info"],
                ],
                [
                    'title' => "Forget Password",
                    'type_key' => CommonConst::FORGET_PASSWORD,
                    'description' => 'Description of Forget Password.',
                    'is_delete' => false,
                    'template' => [
                        "title" => "Forget Password",
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'email_subject' => "Forgot Password",
                        'email_body' => "<p>Trouble signing in [[**name**]]?</p><p>Resetting your password is easy.</p><p>Just click this [[***reset_link***]] and follow the instructions. We?ll have you up and running in no time.</p><p>If the link above don't work, please paste the below URL into your web browser.</p>[[**reset_link**]]<p>If you did not make this request then please ignore this email.</p><p>[[**request_device_info**]]</p>",
                        "whats_app_message" => "[[**name**]], click here to reset your password: [[**reset_link**]]",
                        "sms_message" => "Reset your [[**company_name**]] password: [[**reset_link**]]",
                        "bell_notification_message" => "Password reset link sent to [[**email**]]",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::REQUIRED
                    ],
                    'variables' => ["company_name", "name", "email", "reset_link", "copy_reset_link", "request_device_info"],
                ],
                [
                    'title' => "Register User Mail",
                    'type_key' => CommonConst::REGISTER_USER,
                    'description' => 'Description of User Register User mail',
                    'is_delete' => false,
                    'template' => [
                        "title" => "Register User Mail",
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'email_subject' => "Register Notification",
                        'email_body' => "<center> [[**name**]]</center><p>New User Register Notification </p><br>User Name is</strong> - [[**name**]] <br>Phone Ref is</strong> - [[**user_phone**]] <br>Email is</strong> - [[**user_email**]] <br>",
                        "whats_app_message" => "Welcome [[**name**]]! Your registration is complete.",
                        "sms_message" => "Hi [[**name**]], you’re registered with [[**company_name**]].",
                        "bell_notification_message" => "New user registered: [[**name**]]",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::ENABLE
                    ],
                    'variables' => ["company_name", "name", "user_phone", "user_email"],
                ],
                [
                    'title' => "Password Update Mail",
                    'type_key' => CommonConst::UPDATE_PASSWORD,
                    'description' => 'Description of User Password Update mail',
                    'is_delete' => false,
                    'template' => [
                        "title" => "Password Update Mail",
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'email_subject' => "Password Update Notification",
                        'email_body' => "<center> [[**name**]]</center><p>Password Update Notification </p> <br>Your password has been updated Successfully",
                        "whats_app_message" => "[[**name**]], your password has been updated successfully.",
                        "sms_message" => "Hi [[**name**]], your password was updated.",
                        "bell_notification_message" => "Password successfully updated for [[**name**]].",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::ENABLE
                    ],
                    'variables' => ["company_name", "name"],
                ],
                [
                    'title' => "User Email Verify Send Otp",
                    'type_key' => CommonConst::EMAIL_VERIFY_SEND_OTP,
                    'description' => 'Description of User Login Email verify Send Otp.',
                    'is_delete' => false,
                    'template' => [
                        "title" => "User Email Verify Send Otp",
                        'hidden_pre_header' => 'Greetings from [[**company_name**]]',
                        'email_subject' => "Email Verify Send Otp",
                        'email_body' => "<center>Hey [[**name**]]</center>, <br><p> Your Email verification otp is [[**otp**]]</p>",
                        "whats_app_message" => "Hi [[**name**]], your OTP is [[**otp**]].",
                        "sms_message" => "Your [[**company_name**]] OTP is [[**otp**]]",
                        "bell_notification_message" => "OTP sent to [[**name**]] for email verification.",
                        'priority' => CommonConst::HIGH,
                        'is_enable' => CommonConst::REQUIRED
                    ],
                    'variables' => ["company_name", "name", "otp"],
                ],
            ],
        ],
    ];

    # TODO: this list Slug Plz make Unique And Change slug to plz Check Vue or js file in change slug
    const HEADER_MANAGE_LIST = [
        # Login Log List Header
        [
            'title' => 'Login Log List',
            'slug' => 'login-log-list',
            'table' => 'user_login_logs',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Ip Address', 'key' => 'ip_address', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'User Agent', 'key' => 'user_agent', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Country', 'key' => 'country', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'State', 'key' => 'state', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'City', 'key' => 'city', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Event', 'key' => 'event', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Date', 'key' => 'logged_at', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'actions', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # User List Sidebar Menu
        [
            'title' => 'User List',
            'slug' => 'user-list',
            'table' => 'users',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Salary', 'key' => 'salary', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'User Name', 'key' => 'user_name', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Date of Birth', 'key' => 'date_of_birth', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Anniversary Date', 'key' => 'anniversary_date', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Role', 'key' => 'role', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Actions', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        # User List Sidebar Menu
        [
            'title' => 'User target List',
            'slug' => 'targets_and_incentives',
            'table' => 'targets_and_incentives',
            'headers' => [
                ['title' => 'Month', 'key' => 'month', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Target Amount', 'key' => 'target_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Achieved Amount', 'key' => 'achieved_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Incentive Percentage', 'key' => 'incentive_percentage', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Incentive', 'key' => 'incentive', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Is Paid', 'key' => 'is_paid', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        # Setting Status List Header
        [
            'title' => 'Setting Status List',
            'slug' => 'setting-status-list',
            'table' => 'admin_control_configs',
            'headers' => [
                ['title' => 'Page Name', 'key' => 'status_for', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Status Name', 'key' => 'status_text', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Color', 'key' => 'status_color', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Position', 'key' => 'position', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Invoice Footer Text', 'key' => 'invoice_footer_text', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Contract Footer Text', 'key' => 'contract_footer_text', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Trigger Action', 'key' => 'trigger_action', 'sortable' => false, 'align' => 'left', 'checked' => false],
                // ['title' => 'Send PlatForms', 'key' => 'send_plat_forms', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Actions', 'key' => 'actions', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],

        # Export Logs List Header
        [
            'title' => 'Export Log List',
            'slug' => 'export-log-list',
            'table' => 'export_logs',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Table Name', 'key' => 'table_name', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'File Path', 'key' => 'file_path', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Status', 'key' => 'status', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Extension', 'key' => 'extension', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Json', 'key' => 'body_params', 'sortable' => false, 'align' => 'left', 'checked' => false],
                ['title' => 'Actions', 'key' => 'actions', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];

    # Clients page statuses
    const USER_MODULE_STATUS_LIST = [
        [
            'page' => self::MODULE_USER,
            'position' => 5,
            'statuses' => [
                ['status_text' => 'Active', 'slug' =>  self::ACTIVE, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'In Active', 'slug' => self::IN_ACTIVE, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
            ]
        ],
        [
            'page' => self::B_TO_B,
            'position' => 5,
            'statuses' => [
                ['status_text' => 'Active', 'slug' =>  self::ACTIVE, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'In Active', 'slug' => self::IN_ACTIVE, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
            ]
        ],
        [
            'page' => self::ROLES,
            'position' => 5,
            'statuses' => [
                ['status_text' => 'Active', 'slug' =>  self::ACTIVE, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'In Active', 'slug' => self::IN_ACTIVE, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
            ]
        ],
        // [
        //   'page' => self::RULES,
        //   'position' => 5,
        //   'statuses' => [
        //     [ 'status_text' => 'Active', 'slug' =>  self::ACTIVE, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null ],
        //     [ 'status_text' => 'In Active', 'slug' => self::IN_ACTIVE, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null ],
        // ]
        // ],
        [
            'page' => self::NOTIFICATION_LOG,
            'position' => 5,
            'statuses' => [
                ['status_text' => 'Pending', 'slug' =>  self::PENDING, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'Success', 'slug' => self::SUCCESS, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'Failed', 'slug' => self::FAILED, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
            ]
        ],
        [
            'page' => self::EXPORT_LOG,
            'position' => 5,
            'statuses' => [
                ['status_text' => 'Pending', 'slug' =>  self::PENDING, 'status_color' => '#28a745', 'position' => 1, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'Success', 'slug' => self::SUCCESS, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
                ['status_text' => 'Failed', 'slug' => self::FAILED, 'status_color' => '#6c757d', 'position' => 2, 'is_predefined' => 0, 'invoice_footer_text' => null, 'contract_footer_text' => null],
            ]
        ],
    ];
}
