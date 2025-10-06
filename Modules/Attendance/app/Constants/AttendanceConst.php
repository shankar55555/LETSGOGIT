<?php

namespace Modules\Attendance\Constants;

use App\Constants\CommonConst;

class AttendanceConst
{
    const ATTENDANCE_HEADER_LIST = [
        # Attendance List Sidebar Menu 
        [
            'title' => CommonConst::MODULE_ATTENDANCE,
            'slug' => 'attendance-list',
            'table' => 'attendance',
            'headers' => [
                ['title' => 'Date', 'key' => 'attendance_date', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Login Time', 'key' => 'login_time', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Logout Time', 'key' => 'logout_time', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Duration', 'key' => 'duration', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => 'User Attendance List',
            'slug' => 'user-attendance-list',
            'table' => 'user_attendance',
            'headers' => [
                ['title' => 'Date', 'key' => 'attendance_date', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Check In', 'key' => 'time_in', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Check Out', 'key' => 'time_out', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Time Duration', 'key' => 'total_duration', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Work', 'key' => 'work', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => false],
            ]
        ],
    ];

    const ATTENDANCE_PERMISSION_LIST = [
        # 3. Attendance Permission
        [
            'name' => CommonConst::MODULE_ATTENDANCE,
            'position' => 3,
            "icon" => 'tabler-hierarchy-2',
            "category" => [
                [
                    'name' => 'Attendance List',
                    "permission_list" => [
                        ["name" => 'View Attendance', "action" => "attendance", "slug" => 'view'],
                        ["name" => 'Export Attendance', "action" => "attendance", "slug" => 'export-list'],
                        ["name" => 'Delete Attendance', "action" => "attendance", "slug" => 'delete'],
                    ]
                ],
                [
                    'name' => 'User Attendance List',
                    "permission_list" => [
                        ["name" => 'View User Attendance', "action" => "userAttendance", "slug" => 'view'],
                        ["name" => 'Export User Attendance', "action" => "userAttendance", "slug" => 'export-list'],
                        ["name" => 'Edit User Attendance', "action" => "userAttendance", "slug" => 'edit'],
                        ["name" => 'Delete User Attendance', "action" => "userAttendance", "slug" => 'delete'],
                    ]
                ],
            ]

        ],
    ];

    # Attendance page statuses
    const ATTENDANCE_MODULE_STATUS_LIST = [
        [
            'page' => CommonConst::MODULE_ATTENDANCE,
            'position' => 5,
            'statuses' => [
                ["status_text" => "Present", "slug" => CommonConst::PRESENT, "status_color" => "#28a745", "position" => 1, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Half-Present", "slug" => CommonConst::HALF_PRESENT, "status_color" => "#6c757d", "position" => 2, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
                ["status_text" => "Absent", "slug" => CommonConst::ABSENT, "status_color" => "#6c757d", "position" => 3, "is_predefined" => 0, "invoice_footer_text" => null, "contract_footer_text" => null],
            ]
        ],
    ];

    const ATTENDANCE_RULE_LIST = [];
    const ATTENDANCE_RULE_ITEM_LIST = [];
    const ATTENDANCE_EMAIL_TEMPLATE_LIST = [];
}
