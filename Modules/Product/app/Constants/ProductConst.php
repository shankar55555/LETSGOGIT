<?php

namespace Modules\Product\Constants;

use App\Constants\CommonConst;

class ProductConst
{
    const PRODUCT_HEADER_LIST = [
        # Product/Service List Sidebar Menu 
        [
            'title' => 'Product List',
            'slug' => 'product-list',
            'table' => 'products',
            'headers' => [
                ['title' => 'Name', 'key' => 'name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                // ['title' => 'Price', 'key' => 'price', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Category', 'key' => 'category', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'SKU', 'key' => 'sku', 'sortable' => true, 'align' => 'left', 'checked' => true],
                // ['title' => 'Quantity', 'key' => 'stock_quantity', 'sortable' => false, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Status', 'key' => 'status', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Last Updated By', 'key' => 'updated_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
        [
            'title' => 'Vendor List',
            'slug' => 'vendor-list',
            'table' => 'vendors',
            'headers' => [
                ['title' => 'First Name', 'key' => 'first_name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Last Name', 'key' => 'last_name', 'sortable' => true, 'align' => 'left', 'minWidth' => '140px', 'checked' => true],
                ['title' => 'Company Name', 'key' => 'company_name', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Email', 'key' => 'email', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Phone', 'key' => 'phone', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Address', 'key' => 'address', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'City', 'key' => 'city', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'State', 'key' => 'state', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Zip Code', 'key' => 'zip_code', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'GSTIN', 'key' => 'gstin', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Created By', 'key' => 'created_by', 'sortable' => true, 'align' => 'left', 'checked' => true],
                ['title' => 'Action', 'key' => 'action', 'sortable' => false, 'align' => 'center', 'checked' => true],
            ]
        ],
    ];

    const PRODUCT_SERVICE_PERMISSION_LIST = [
        [
            'name' => CommonConst::MODULE_PRODUCT_SERVICE,
            'position' => 3, // Adjust position as needed
            "icon" => 'tabler-shopping-cart',
            "category" => [
                [
                    'name' => CommonConst::MODULE_PRODUCT_SERVICE,
                    "permission_list" => [
                        ["name" => 'View Product/Service List', "action" => "product", "slug" => 'view'],
                        ["name" => 'Create new Product/Service', "action" => "product", "slug" => 'create'],
                        ["name" => 'Edit Product/Service', "action" => "product", "slug" => 'edit'],
                        ["name" => 'Export Product/Service list', "action" => "product", "slug" => 'export-list'],
                        ["name" => 'Delete Product/Service', "action" => "product", "slug" => 'delete'],
                        ["name" => 'View Product/Service Details', "action" => "productService", "slug" => 'show'],
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
