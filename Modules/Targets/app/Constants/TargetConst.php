<?php

namespace Modules\Targets\Constants;

use App\Constants\CommonConst;

class TargetConst
{
    const TARGET_HEADER_LIST = [
        [
            'title' => 'target List',
            'slug' => 'target-list',
            'table' => 'targets',
            'headers' => [
                ['title' => 'Title', 'key' => 'title', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Type', 'key' => 'target_type', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Targets', 'key' => 'target_value', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Target Amount', 'key' => 'target_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Incentive percent', 'key' => 'incentive_percent', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Start Date', 'key' => 'start_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'End Date', 'key' => 'end_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ]
    ];


    const TARGET_PERMISSION_LIST = [
        # 2. Target Permission
        [
            'name' => CommonConst::MODULE_TARGETS,
            'position' => 2,
            "icon" => 'tabler-antenna-bars-4',
            "category" => [
                [
                    'name' => 'Targets',
                    "permission_list" => [
                        ["name" => 'View Targets', "action" => "targets", "slug" => 'view'],
                        ["name" => 'Create Target', "action" => "targets", "slug" => 'create'],
                        ["name" => 'Edit Target', "action" => "targets", "slug" => 'edit'],
                        ["name" => 'Export Targets', "action" => "targets", "slug" => 'export-list'],
                        ["name" => 'Assign Target', "action" => "targets", "slug" => 'assign-to'],
                        ["name" => 'Update Target Status', "action" => "targets", "slug" => 'status-update'],
                        ["name" => 'Delete Target', "action" => "targets", "slug" => 'delete'],
                        ["name" => 'View Target Details', "action" => "targets", "slug" => 'show'],
                    ]
                ],
            ]
        ],
    ];

    const TARGET_MODULE_STATUS_LIST = [];
    const TARGET_RULE_LIST = [];
    const TARGET_RULE_ITEM_LIST = [];
    const TARGET_EMAIL_TEMPLATE_LIST = [];
}
