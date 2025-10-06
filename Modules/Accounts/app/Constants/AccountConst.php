<?php

namespace Modules\Accounts\Constants;

use App\Constants\CommonConst;

class AccountConst
{
    const ACCOUNT_HEADER_LIST = [
        # Purchase Bill List Sidebar Menu 
        [
            'title' => 'Purchase Bill List',
            'slug' => 'account-pages-PurchaseBills',
            'table' => 'purchase_bills',
            'headers' => [
                ['title' => 'Bill Number', 'key' => 'bill_number', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Vendor Name', 'key' => 'vendor_name', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Bill Date', 'key' => 'bill_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Due Date', 'key' => 'due_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Tax Amount', 'key' => 'tax_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total Amount', 'key' => 'total_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];

    const PURCHASE_BILL_HEADER_LIST = [
        # Purchase Bill List Sidebar Menu 
        [
            'title' => 'Purchase Bill List',
            'slug' => 'account-pages-PurchaseBills',
            'table' => 'purchase_bills',
            'headers' => [
                ['title' => 'Bill Number', 'key' => 'bill_number', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Vendor', 'key' => 'vendor_name', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Bill Date', 'key' => 'bill_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Due Date', 'key' => 'due_date', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Sub Total', 'key' => 'sub_total', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Tax Amount', 'key' => 'tax_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Total Amount', 'key' => 'total_amount', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Updated At', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'last_updated_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];
    const PRODUCT_SERVICE_PERMISSION_LIST = [
        [
            'name' => CommonConst::MODULE_ACCOUNT,
            'position' => 3, // Adjust position as needed
            "icon" => 'tabler-shopping-cart',
            "category" => [
                [
                    'name' => CommonConst::MODULE_ACCOUNT,
                    "permission_list" => [
                        ["name" => 'View Purchase Bill List', "action" => "purchaseBill", "slug" => 'view'],
                        ["name" => 'Create new Purchase Bill', "action" => "purchaseBill", "slug" => 'create'],
                        ["name" => 'Edit Purchase Bill', "action" => "purchaseBill", "slug" => 'edit'],
                        ["name" => 'Export Purchase Bill list', "action" => "purchaseBill", "slug" => 'export-list'],
                        ["name" => 'Delete Purchase Bill', "action" => "purchaseBill", "slug" => 'delete'],
                        ["name" => 'View Purchase Bill Details', "action" => "purchaseBill", "slug" => 'show'],
                    ]
                ],
            ]
        ],
    ];

    const PRODUCT_SERVICE_MODULE_STATUS_LIST = [];
    const PRODUCT_SERVICE_RULE_LIST = [];
    const PRODUCT_SERVICE_RULE_ITEM_LIST = [];
    const PRODUCT_SERVICE_EMAIL_TEMPLATE_LIST = [];
}
