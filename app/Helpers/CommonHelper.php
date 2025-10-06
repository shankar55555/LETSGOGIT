<?php

use App\Constants\CommonConst;
use App\Models\ExceptionLog;
use App\Models\Setting;
use App\Models\TableHeaderManage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Models\Client;
use Modules\Contracts\Constants\ContractConst;
use Modules\Contracts\Models\Contract;
use Modules\FollowUp\Constants\FollowUpConst;
use Modules\FollowUp\Models\FollowUp;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\Lead;
use Modules\Product\Models\ProductService;
use Modules\Quotations\Constants\QuotationConst;
use Modules\Quotations\Models\Quotation;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\RolePermission\Models\Permission;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Models\SiteVisit;
use Nwidart\Modules\Facades\Module;

const COMMON_HELPER = 'Helper / Common Helper';

# i('This is an info message');
# er('Something went wrong');
# w('This is a warning message');
# dLog('Debug this variable: ' . json_encode($data));
# logWithContext('info', 'User logged in', ['user_id' => 123]);

/**
 * Log an informational message with highlight.
 *
 * @param string $msg
 * @return void
 */
function i($msg)
{
    Log::info('[INFO] ' . $msg);
}

/**
 * Log an error message with highlight.
 *
 * @param string $msg
 * @return void
 */
function er($msg)
{
    Log::error('[ERROR] ' . $msg);
}

/**
 * Log a warning message with highlight.
 *
 * @param string $msg
 * @return void
 */
function w($msg)
{
    Log::warning('[WARNING] ' . $msg);
}

/**
 * Log a debug message with highlight.
 *
 * @param string $msg
 * @return void
 */
function dLog($msg)
{
    Log::debug('[DEBUG] ' . $msg);
}

/**
 * Log a custom level message with context (file, line).
 *
 * @param string $level 'info', 'error', 'warning', etc.
 * @param string $msg
 * @param array $context
 * @return void
 */
function logWithContext($level, $msg, array $context = [])
{
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $location = $bt[0]['file'] . ':' . $bt[0]['line'];

    $context['location'] = $location;
    Log::log($level, strtoupper("[$level] ") . $msg, $context);
}

/**
 * Convert various ID formats into an array of IDs.
 *
 * Accepts null, array, comma-separated string, or single ID and converts it into an array format.
 *
 * @param mixed $ids ID(s) in various formats (null, string, array, or single int).
 *
 * @return array Array of IDs.
 */
function makeAnyIdArrayFormat($ids = null): array
{
    if (empty($ids)) return [];
    if (is_array($ids)) return $ids;
    if (is_string($ids) && str_contains($ids, ',')) return array_map('trim', explode(',', $ids));
    return [$ids];
}

/**
 * Format a date according to a “type” key.
 *
 * @param  \DateTime|string|null  $date
 * @param  string                 $type
 * @return string
 */
function formatAccordingDateTime($date, string $type): string
{
    if (empty($date)) $date = Carbon::now();

    # ensure we have a Carbon instance
    $dt = $date instanceof Carbon ? $date : Carbon::parse($date);

    switch ($type) {
        case 'time_ago':       # diffForHumans()
            return $dt->diffForHumans();

        case 'm-d-y':          # MM-DD-YYYY
            return $dt->format('m-d-Y');

        case 'd-m-y':          # DD-MM-YYYY
            return $dt->format('d-m-Y');

        case 'd-m-y-time':     # 12h with AM/PM
            return $dt->format('d-m-Y h:i A');

        case 'y-m-d':          # YYYY-MM-DD
            return $dt->format('Y-m-d');

        case 'd M, y':         # DD Mon, YYYY
            return $dt->format('d M, Y');

        case 'full_date':      # Friday, April 18th 2025
            return $dt->format('l, F jS Y');

        case 'full_date_1':    # Friday, April 18, 2025
            return $dt->format('l, F j, Y');

        case 'date_time':      # YYYY-MM-DD hh:mm AM/PM
            return $dt->format('Y-m-d h:i A');

        case 'time_only':      # 03:45 PM
            return $dt->format('h:i A');

        case 'month_year':     # April 2025
            return $dt->format('F Y');

        case 'iso':            # ISO 8601
            return $dt->toIso8601String();

        case 'custom_1':       # Apr 18, 2025
            return $dt->format('M j, Y');

        case 'custom_2':       # 18 Apr 2025, 3:45 PM
            return $dt->format('j M Y, g:i A');

        case 'd-m-y-his':      # 15-05-2025 15:26:42
            return $dt->format('d-m-Y H:i:s');

        default:
            # fallback: full Y-m-d H:i:s
            return $dt->format('Y-m-d H:i:s');
    }
}

/**
 * Get the client IP address.
 * Replaces localhost IPs with a predefined external IP for testing purposes.
 */
function getIpAddress()
{
    $ip = request()->ip();

    # Replace localhost IP with a test IP (optional)
    if (in_array($ip, ['127.0.0.1', '::1'])) {
        $ip = '103.233.24.1';
    }
    return $ip;
}

/**
 * Get the application base URL without a trailing slash.
 */
function getAppUrl()
{
    return rtrim(config('app.url'), '/');
}

/**
 * Get the current datetime formatted as Ymd_His plus milliseconds.
 * Example output: 20250617_12345689
 */
function formattedDateTime()
{
    # Get full date time + milliseconds
    $now = now(); # current time
    $micro = $now->format('u'); # microseconds like 893245
    $milliseconds = substr($micro, 0, 2); # first 2 digits = ms

    return $now->format('Ymd_His') . $milliseconds; # 20250426_15154289
}

/**
 * Retrieve application settings as key-value pairs.
 * Ensures the company_logo path is a full URL.
 */
function getSettingInfo()
{
    $setting = Setting::pluck('value', 'key') ?? [];
    # Ensure app URL has no trailing slash
    $appUrl = rtrim(config('app.url'), '/');
    # Handle company_logo path
    if (!empty($setting['company_logo'])) {
        $setting['company_logo'] = $appUrl . '/' . ltrim($setting['company_logo'], '/');
    }
    return $setting;
}

/* It creates an exception error
*
* @param exception The exception object
* @param type This is the type of exception.
*/
function createExceptionError($exception, $type, $function = null)
{
    er("createExceptionError : " . "$type : $function => " . $exception->getMessage(), ['exception' => $exception]);
    try {
        $error_message = "Function $function : " . $exception->getMessage();
        $exception_first = ExceptionLog::where('status', ExceptionLog::PENDING)
            ->where('type', $type)->where('error', $error_message)
            ->where('line_number', $exception->getLine())
            ->latest()
            ->first();

        if (!$exception_first) {
            $data = [
                'status' => ExceptionLog::PENDING,
                'type' => $type,
                'title' => get_class($exception),
                'error' => $error_message,
                'file_name' => $exception->getFile(),
                'line_number' => $exception->getLine(),
                'full_error' => $exception,
                'type_count' => 1,
            ];
            ExceptionLog::create($data);
        } else {
            $exception_first->type_count += 1;
            $exception_first->error = "Function $function : " . $exception->getMessage();
            $exception_first->save();
        }
        return true;
    } catch (\Exception $e) {
        er("createExceptionError : " . COMMON_HELPER . " : createExceptionError => " . $e->getMessage(), ['exception' => $e]);
        return false;
    }
}

/**
 * It takes a role, and assigns all permissions to it, except for the ones that are in the array of
 * permissions that are denied
 *
 * @param role The name of the role.
 */
function createNewRole($role)
{
    $permissions_ids = Permission::pluck('id')->toArray();

    # remove move old permission assign in $role  
    switch ($role->slug) {
        case RolePermissionConst::SLUG_SUPER_ADMIN:
            $permissions_ids = Permission::pluck('id')->toArray();
            break;
        case RolePermissionConst::SLUG_ADMIN:
            $permissions_ids = Permission::whereIn('permission', RolePermissionConst::ADMIN_PERMISSION)->pluck('id')->toArray();
            break;
        case RolePermissionConst::SLUG_EMPLOYEE:
            $permissions_ids = Permission::whereIn('permission', RolePermissionConst::EMPLOYEE_PERMISSION)->pluck('id')->toArray();
            break;
        default:
            $permissions_ids = [];
            break;
    }
    $role->permissions()->sync($permissions_ids);
    return $role;
}

/**
 * Format and structure pagination data for a standardized API response.
 * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $list
 */
function customizingResponseData($list)
{
    $data = [
        'data' => $list->items(),
        'current_page' => $list->currentPage(),
        'last_page' => $list->lastPage(),
        'per_page' => $list->perPage(),
        'total' => $list->total(),
        'from' => $list->firstItem(),
        'to' => $list->lastItem(),
    ];

    return $data;
}

/**
 * Get the display name for a given status code.
 *
 * @param string|null $status
 * @return string|null Human-readable status name or null if not found.
 */
function getStatusName($status = null)
{
    return match ($status) {
        'no_action' => 'No Action',
        'follow_up' => 'Follow up',
        'interested' => 'Interested',
        'not_interested' => 'Not Interested',
        'ready_for_srm' => 'Ready For SRM',
        'ready-for-quotation' => 'Ready For Quotation',
        "quotation-draft" => "Quotation Draft",
        "quotation-created" => "Quotation Created",
        "quotation-in-progress-25" => "Quotation in progress 25 %",
        "quotation-in-progress-50" => "Quotation in progress 50 %",
        "quotation-in-progress-75" => "Quotation in progress 75 %",
        "quotation-accepted" => "Quotation Accepted",
        "quotation-rejected" => "Quotation Rejected",
        "quotation-expired" => "Quotation Expired",
        default => null,
    };
}

/**
 * Read and merge constant lists defined across multiple modules.
 *
 * @param string $name The key segment used to build constant names.
 * @param array $list (optional) The base array to merge into.
 * @param bool $position (optional) Whether to sort the final list by 'position' key.
 * @return array The merged and optionally sorted list.
 */
function readConstFileList(string $name, array $list = [], bool $position = false)
{
    # Define modules to check (class + constant name)
    $optionalModules = [
        ['class' => \Modules\AlertAndNotification\Constants\AlertNotificationConst::class, 'const' => "ALERT_AND_NOTIFICATION_" . $name . "_LIST"],
        ['class' => \Modules\Clients\Constants\ClientConst::class, 'const' => "CLIENT_" . $name . "_LIST"],
        ['class' => \Modules\Accounts\Constants\AccountConst::class, 'const' => "ACCOUNT_" . $name . "_LIST"],
        # ['class' => \Modules\Contracts\Constants\ContractConst::class, 'const' => "SCHEDULING_" . $name . "_LIST"],
        ['class' => \Modules\Dashboard\Constants\DashboardConst::class, 'const' => "LEAD_" . $name . "_LIST"],
        ['class' => \Modules\FollowUp\Constants\FollowUpConst::class, 'const' => "FOLLOW_UP_" . $name . "_LIST"],
        ['class' => \Modules\Invoices\Constants\InvoiceConst::class, 'const' => "INVOICE_" . $name . "_LIST"],
        ['class' => \Modules\Leads\Constants\LeadConst::class, 'const' => "LEAD_" . $name . "_LIST"],
        ['class' => \Modules\Product\Constants\ProductConst::class, 'const' => "PRODUCT_" . $name . "_LIST"],
        ['class' => \Modules\Quotations\Constants\QuotationConst::class, 'const' => "QUOTATION_" . $name . "_LIST"],
        ['class' => \Modules\SiteVisit\Constants\SiteVisitConst::class, 'const' => "SITE_VISIT_" . $name . "_LIST"],
        ['class' => \Modules\RolePermission\Constants\RolePermissionConst::class, 'const' => "ROLE_" . $name . "_LIST"],
        ['class' => \Modules\Attendance\Constants\AttendanceConst::class, 'const' => "ATTENDANCE_" . $name . "_LIST"],
        ['class' => \Modules\Targets\Constants\TargetConst::class, 'const' => "TARGET_" . $name . "_LIST"],
    ];

    foreach ($optionalModules as $module) {
        if (class_exists($module['class']) && defined($module['class'] . '::' . $module['const'])) {
            $list = array_merge($list, constant($module['class'] . '::' . $module['const']));
        }
    }

    # Sort by position
    if ($position) usort($list, function ($a, $b) {
        return $a['position'] <=> $b['position'];
    });

    return $list;
}

/**
 * Retrieve a constant list of header definitions.
 *
 * @return array Header configuration list
 */
function getConstHeaderList()
{
    $header_list = CommonConst::HEADER_MANAGE_LIST ?? [];

    $prams = ["name" => "HEADER", "list" => $header_list, "position" => false];
    return readConstFileList(...$prams);
}

/**
 * Create or update a table header configuration for the current user based on the provided slug.
 *
 * @param string $slug Unique identifier for the header configuration
 * @return \App\Models\TableHeaderManage|false The saved header model or false if not found in constant list
 */
function createTableHeaderManage(string $slug)
{
    $list = getConstHeaderList();
    $info = collect($list)->firstWhere(function ($item) use ($slug) {
        return is_array($item) && isset($item['slug']) && $item['slug'] == $slug;
    });

    if (!$info) return false;

    $header = TableHeaderManage::updateOrCreate(
        [
            'user_id' => Auth::user()->uuid,
            'slug' => $slug,
        ],
        [
            'title' => $info['title'],
            'table' => $info['table'],
            'headers' => $info['headers']
        ]
    );

    return $header;
}

/**
 * Update all users' header configurations for a specific slug using constant values.
 *
 * @param string $slug Unique identifier for the header configuration
 * @return int|false Number of records updated or false if not found
 */
function syncAllUserTableHeaderManage($slug)
{
    $list = getConstHeaderList();

    $info = collect($list)->firstWhere(fn($item) => $item['slug'] === $slug);

    if (!$info) return false;

    $header = TableHeaderManage::where('slug', $slug)->update(
        ['title' => $info['title'], 'table' => $info['table'], 'headers' => $info['headers']]
    );

    return $header;
}

/**
 * Create a storage directory if it doesn't exist, with appropriate permissions.
 *
 * @param string $file_path Relative path inside the storage directory
 * @return void
 */
function addStoragePermission($file_path)
{
    $storagePath = storage_path($file_path);

    # Ensure the directory exists with correct permissions
    if (!file_exists($storagePath)) {
        mkdir($storagePath, 0755, true); # Create the directory recursively
    }
}

/**
 * Retrieve UUID of logged-in user.
 */
function loginUserId()
{
    if (Auth::check()) {
        $uuid = Auth::user()->uuid;
        i("Common Helper: User is authenticated via Auth. Using UUID: {$uuid}");
        return $uuid;
    }

    if (request()->user()) {
        $uuid = request()->user()->uuid;
        i("Common Helper: User is authenticated via request. Using UUID: {$uuid}");
        return $uuid;
    }

    i("Common Helper: No authenticated user found. Returning null UUID.");
    return null;
}


/**
 * Retrieve UUIDs of all users with admin or super admin roles.
 *
 * @return array List of admin user UUIDs
 */
function adminUserId()
{
    return User::withTrashed()->whereHas('user_role', function ($qu) {
        $qu->whereHas('role', function ($q) {
            $q->whereIn('slug', [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN]);
        });
    })->pluck('uuid')->toArray();
}

/**
 * Apply user-based filtering to a query based on a single column type.
 *
 * @param Builder $query The query builder instance
 * @param string $type The column type to filter by (default: 'assigned_user')
 * @param string|null $user_view_id Optional user UUID from view context
 * @return Builder
 */
function applyFilteringUser($query, $type = "assigned_user",  $user_view_id = null)
{
    $referer = request()->header('referer');
    // $user = Auth::user();
    $user_id = Auth::user()->uuid;
    $user = User::withoutTrashed()->where('uuid', $user_id)->first();

    $columnMap = [
        'assigned_user' => 'assigned_user',
        'assign_user' => 'assign_user',
        'user_id' => 'user_id',
        'created_by' => 'created_by',
    ];
    $column = $columnMap[$type] ?? 'created_by';

    # Check if UUID is passed in URL
    if (!empty($user_view_id)) {
        return $query->where($column, $user_view_id);
    }

    # Restrict access for non-admin users
    $roleSlugs = $user->roles()->pluck('slug')->toArray();
    if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
        $query->where($column, $user->uuid);
    }
    return $query;
}

/**
 * Apply user-based filtering on multiple columns for a query.
 *
 * @param Builder $query The query builder instance
 * @param array $types Array of column type keys to apply filtering on
 * @param string|null $user_view_id Optional user UUID for filtering
 * @return Builder
 */
function applyFilteringUser_new($query, $types = ['created_by'], $user_view_id = null)
{
    // $user = Auth::user();
    $user_id = Auth::user()->uuid;
    $user = User::withoutTrashed()->where('uuid', $user_id)->first();
    $columnMap = [
        'assigned_user' => 'assigned_user',
        'assign_user' => 'assign_user',
        'user_id' => 'user_id',
        'created_by' => 'created_by',
        'visit_assignee' => 'visit_assignee',
    ];

    # Resolve columns from provided types
    $columns = collect($types)->map(fn($type) => $columnMap[$type] ?? null)->filter()->unique();

    # Handle /user/view UUID from referer
    if (!empty($user_view_id)) {
        $query->where(function ($q) use ($columns, $user_view_id) {
            foreach ($columns as $column) {
                $q->orWhere($column, $user_view_id);
            }
        });
        return $query;
    }

    # Restrict for non-admins
    $roleSlugs = $user->roles()->pluck('slug')->toArray();
    if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
        $query->where(function ($q) use ($columns, $user) {
            foreach ($columns as $column) {
                $q->orWhere($column, $user->uuid);
            }
        });
    }

    return $query;
}

/**
 * Generate recurring or advance invoices based on payment data.
 *
 * @param array $data Payment details including amount, tax, discounts, invoice/quotation ID, etc.
 * @return void
 */
function makeSchedulingPayment($data)
{
    try {
        $invoicePrefix = Setting::where('key', 'invoicePrefix')->value('value') ?? 'INV';
        $months = (int) filter_var($data['payment_duration'], FILTER_SANITIZE_NUMBER_INT);
        $months = max(1, $months); # Prevent division by zero
        # Defaults to 0 if not set
        $sub_total = $data['subTotal'] ?? 0;
        $balance = $data['balance'] ?? 0;
        $tax = $data['total_tax_amount'] ?? 0;
        $discount = $data['discount_amount'] ?? 0;
        $total = $data['total'] ?? 0;
        $amountReceived = $data['amount_receive'] ?? 0;

        # Amounts per installment
        $baseAmount = floor($sub_total / $months);
        $taxAmount = round($tax / $months, 2);
        $discountAmount = round($discount / $months, 2);
        $totalAmount = round($total / $months, 2) - $amountReceived;

        # Module fetch logic
        $quotation_id = $data['quotation_id'] ?? null;
        $invoice_id = $data['invoice_id'] ?? null;

        $module_data = null;
        if ($quotation_id) {
            $module_data = Quotation::findOrFail($quotation_id);
        } elseif ($invoice_id) {
            $module_data = Invoice::findOrFail($invoice_id);
        }

        # Case 1: Partial or advance payment
        if ($amountReceived > 0 && $amountReceived < $total) {
            invoiceCreating($module_data, $data, $baseAmount, $taxAmount, $discountAmount, $totalAmount);
        }

        # Case 2: Full payment
        if ($data['recurring_invoice'] == 'no') {
            invoiceCreating($module_data, $data, $balance, $tax, $discount, $total);
            return; # No need to create drafts
        }

        # Case 3: Remaining to be scheduled
        $startDate = now();
        for ($i = 1; $i <= $months; $i++) {
            $scheduleDate = $startDate->copy()->addMonths($i);

            $invoice = Invoice::create([
                'invoice_number' => "{$invoicePrefix}-Draft",
                'title' => $module_data->title ?? '',
                'description' => $module_data->description ?? '',
                'items' => $module_data->items ?? [],
                'amount_paid' => $amountReceived,
                'sub_total' => $baseAmount,
                'tax' => $taxAmount,
                'discount' => $discountAmount,
                'total' => $totalAmount,
                'status' => InvoiceConst::CREATED,
                'due_date' => $scheduleDate,
                'quotation_id' => $quotation_id,
                'created_by' => Auth::user()?->uuid ?? 'unknown',
            ]);

            # Send Notification:
            $data = invoiceRuleNotification($invoice->id);
            $notificationHelper = new NotificationHelper();
            $notificationHelper->handle(InvoiceConst::RULE_INVOICE_CREATED, $data, null, loginUserId());
        }
    } catch (\Exception $e) {
        er('Payment creation failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $data,
        ]);

        createExceptionError($e, COMMON_HELPER, __FUNCTION__);
    }
}

/**
 * Creates a new invoice (paid or recurring) and sends a notification.
 *
 * @param mixed $module Module model instance (e.g., Quotation or Invoice)
 * @param array $data Input data including payment and recurring information
 * @param float $baseAmount Subtotal amount without tax and discount
 * @param float $taxAmount Tax amount to apply
 * @param float $discountAmount Discount amount to apply
 * @param float $totalAmount Final total amount (subtotal + tax - discount)
 * @return void
 */
function invoiceCreating($module, $data, $baseAmount, $taxAmount, $discountAmount, $totalAmount)
{
    $invoicePrefix = Setting::where('key', 'invoicePrefix')->value('value') ?? 'INV';

    $lastInvoice = Invoice::where('invoice_number', 'not like', '%Draft')
        ->whereRaw("invoice_number ~ '[0-9]'")
        ->orderByRaw("CAST(REGEXP_REPLACE(invoice_number, '[^0-9]', '', 'g') AS INTEGER) DESC")
        ->first();

    $lastNumber = 0;
    if ($lastInvoice && preg_match('/^([A-Z]+)-(\d+)$/', $lastInvoice->invoice_number, $matches)) {
        $invoicePrefix = $matches[1];
        $lastNumber = (int) $matches[2];
    }

    $newInvoiceNo = "{$invoicePrefix}-" . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

    $invoice = Invoice::create([
        'invoice_number' => $newInvoiceNo,
        'title' => $module->title ?? '',
        'description' => $module->description ?? '',
        'items' => $module->items ?? [],
        'amount_paid' => $data['recurring_invoice'] == 'no' ? $totalAmount : $data['amount_receive'] ?? 0,
        'sub_total' => $baseAmount,
        'tax' => $taxAmount,
        'discount' => $discountAmount,
        'total' => $totalAmount,
        'status' => InvoiceConst::PAID,
        'due_date' => now(),
        'quotation_id' => $module->id ?? null,
        'created_by' => Auth::user()?->uuid ?? 'unknown',
    ]);

    # Send Notification:
    $data = invoiceRuleNotification($invoice->id);
    $notificationHelper = new NotificationHelper();
    $notificationHelper->handle(InvoiceConst::RULE_INVOICE_CREATED, $data, null, loginUserId());
}

/**
 * Returns eager loading relationships for Quotation-related user data.
 *
 * @return array List of relationship strings
 */
function onlyQuotationUserRelation()
{
    $with = [];
    if (Module::has(CommonConst::MODULE_LEAD)) {
        $with[] = 'leadDetail:id,name,email,country_code,phone';
    }
    if (Module::has(CommonConst::MODULE_CLIENT)) {
        $with[] = 'clientDetail:id,name,avatar,email,country_code,phone';
    }
    return $with;
}

/**
 * Returns eager loading relationships for Invoice-related user data.
 *
 * @return array List of relationship strings
 */
function onlyInvoiceUserRelation()
{
    $with = [];
    if (Module::has(CommonConst::MODULE_QUOTATION)) {
        $with[] = 'quotation';
        if (Module::has(CommonConst::MODULE_LEAD)) {
            $with[] = 'quotation.leadDetail:id,name,email,country_code,phone';
        }
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            $with[] = 'quotation.clientDetail:id,name,email,country_code,phone';
        }
    }
    if (Module::has(CommonConst::MODULE_CLIENT)) {
        $with[] = 'client:id,name,avatar,email,country_code,phone';
    }
    return $with;
}

/**
 * Retrieves rule-based notification data for a specific module.
 *
 * @param string $module The module slug (e.g., 'lead', 'invoice', etc.)
 * @param string|null $type Optional type ('email', etc.) for formatting request info
 * @return array Notification data
 */
function getSendDataList($module, $type = null)
{
    $data = [];
    if ($module == CommonConst::ACCOUNT) {
        $user = request()->user();
        $request_device_info = $type == CommonConst::EMAIL ? addEmailDeviceInfo(request()) : addMessageDeviceInfo(request());
        $data = ['name' => $user->name, 'email' => $user->email, 'request_device_info' => $request_device_info];
    } else if (Module::has(CommonConst::MODULE_LEAD) && $module == CommonConst::MODULE_LEAD) {
        $id = Lead::pluck('id')->first();
        $data = $id ? leadRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_CLIENT) && $module == CommonConst::MODULE_CLIENT) {
        $id = Client::pluck('id')->first();
        $data = $id ?  clientRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_QUOTATION) && $module == CommonConst::MODULE_QUOTATION) {
        $id = Quotation::pluck('id')->first();
        $data = $id ? quotationRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_INVOICE) && $module == CommonConst::MODULE_INVOICE) {
        $id = Invoice::pluck('id')->first();
        $data = $id ? invoiceRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_FOLLOW_UP) && $module == CommonConst::MODULE_FOLLOW_UP) {
        $id = FollowUp::pluck('id')->first();
        $data = $id ? followUpRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_SITE_VISIT) && $module == CommonConst::MODULE_SITE_VISIT) {
        $id = SiteVisit::pluck('id')->first();
        $data = $id ? siteVisitRuleNotification($id) : [];
    } else if (Module::has(CommonConst::MODULE_CONTRACT) && $module == CommonConst::MODULE_CONTRACT) {
        $id = Contract::pluck('id')->first();
        $data = $id ? invoiceRuleNotification($id) : [];
    }
    return $data;
}

/**
 * Prepares a notification-ready data array for a given lead.
 *
 * This function loads all related models (creator, updater, client, quotations, etc.)
 * and formats them into a standard data structure for use in rule-based notifications.
 *
 * @param int|string $lead_id Lead ID (primary key)
 * @return array Filtered data array with lead info and related entities
 */
function leadRuleNotification($lead_id)
{
    # ["name", "contact_person", "contact_person_role", "email", "phone", "address", "status", "source", "assigned_user", "note", "created_by", "last_updated_by", "client_id", "quotation_id"];
    $variable_list = array_merge(LeadConst::LEAD_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'assigned_user_uuid', 'lead_uuid', 'rule_module']);
    $lead = Lead::with(['creator', 'updater', 'assignedUser', 'client', 'quotations', 'status_info'])->findOrFail($lead_id);
    $data = $lead->toArray();

    # UUIDs of related users
    $data['created_by_uuid'] = $lead->created_by ?? ''; # UUID of creator
    $data['last_updated_by_uuid'] = $lead->last_updated_by ?? ''; # UUID of last updater
    $data['assigned_user_uuid'] = $lead->assigned_user ?? ''; # UUID of assigned user

    # Human-readable names from relations
    $data['created_by'] = $lead->creator?->name ?? ''; # Name of creator (User relation)
    $data['last_updated_by'] = $lead->updater?->name ?? ''; # Name of last updater (User relation)
    $data['assigned_user'] = $lead->assignedUser?->name ?? ''; # Name of assigned user (User relation)

    # Status text from AdminControlConfig
    $data['status'] = $lead->status_info?->status_text ?? $lead->status ?? '';

    # Client name from Client model
    $data['client_id'] = $lead->client?->name ?? ''; # Name of client
    $data['lead_uuid'] = $lead->id ?? ''; # Lead_id
    $data['rule_module'] = CommonConst::MODULE_LEAD;

    # Quotation numbers as comma-separated string
    $data['quotation_id'] = $lead->quotations->count() > 0 ? implode(',', $lead->quotations->pluck('quotation_number')->toArray()) : ''; # Quotation numbers
    $data['created_at'] = formatAccordingDateTime($lead->created_at, 'full_date_1');

    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

/**
 * Prepares a notification-ready data array for a given client.
 *
 * Loads all related data (creator, updater, quotations, leads, etc.)
 * and formats it for use in notification templates or rule engines.
 *
 * @param int|string $client_id Client ID (primary key)
 * @return array Filtered data array with client info and related entities
 */
function clientRuleNotification($client_id)
{
    # [ "name", "contact_person", "contact_person_role", "email", "phone", "status", "assigned_user", "created_by", "last_updated_by", "lead_id"];
    $variable_list = array_merge(ClientConst::CLIENT_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'assigned_user_uuid', 'client_uuid', 'lead_uuid', 'rule_module']);

    $client = Client::with(['creator', 'updater', 'status_info', 'assignedUser', 'leads', 'quotations',])->findOrFail($client_id);
    $data = $client->toArray();

    # UUIDs of related users
    $data['created_by_uuid'] = $client->created_by ?? ''; # UUID of creator
    $data['last_updated_by_uuid'] = $client->last_updated_by ?? ''; # UUID of last updater
    $data['assigned_user_uuid'] = $client->assigned_user ?? ''; # UUID of assigned user

    # Human-readable names from relations
    $data['created_by'] = $client->creator?->name ?? ''; # Name of creator (User relation)
    $data['last_updated_by'] = $client->updater?->name ?? ''; # Name of last updater (User relation)
    $data['assigned_user'] = $client->assignedUser?->name ?? ''; # Name of assigned user (User relation)

    # Status text from AdminControlConfig
    $data['status'] = $client->status_info?->status_text ?? $client->status ?? '';

    $data['client_uuid'] = $client->id ?? ''; # Client Id
    $data['lead_uuid'] = $client->lead_id ?? ''; # Lead Id
    $data['rule_module'] = CommonConst::MODULE_CLIENT;

    # Quotation numbers as comma-separated string
    $data['quotation_id'] = $client->quotations->isNotEmpty() ? $client->quotations->pluck('quotation_number')->implode(',') : ''; # quotation numbers
    $data['lead_id'] = $client->leads->count() > 0 ? implode(',', $client->leads->pluck('name')->toArray()) : ''; # Lead name
    $data['created_at'] = formatAccordingDateTime($client->created_at, 'full_date_1');

    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function quotationRuleNotification($quotation_id)
{
    # Load quotation with all related models we need
    # ["company_name", "quotation_number", "valid_uptil", "quotation_type", "title", "sub_total", "discount", "tax", "total", "status", "items", "custom_header_text", "payment_terms", "terms_conditions", "lead_id", "client_id", "created_by", "last_updated_by", 'created_at']
    $variable_list = array_merge(QuotationConst::QUOTATION_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'lead_uuid', 'client_uuid', 'rule_module']);

    $quotation = Quotation::with(['creator', 'updater', 'status_info', 'clientDetail', 'leadDetail'])->findOrFail($quotation_id);
    $data = $quotation->toArray();

    $data['created_by_uuid']      = $quotation->created_by ?? '';       # Creator UUID
    $data['last_updated_by_uuid'] = $quotation->last_updated_by ?? '';  # Last updater UUID

    $data['created_by']      = $quotation->creator?->name ?? '';
    $data['last_updated_by'] = $quotation->updater?->name ?? '';

    $data['client_id'] = $quotation->clientDetail?->name ?? '';  # Client name
    $data['lead_id']   = $quotation->leadDetail?->name ?? '';  # Lead name

    $data['client_uuid'] = $quotation->client_id ?? ''; # Client Id
    $data['lead_uuid'] = $quotation->lead_id ?? ''; # Lead Id
    $data['rule_module'] = CommonConst::MODULE_QUOTATION;

    $data['valid_uptil'] = $quotation->valid_uptil ? formatAccordingDateTime($quotation->valid_uptil, 'd-m-Y') : '';
    $data['created_at']  = formatAccordingDateTime($quotation->created_at, 'full_date_1');

    # Status text from AdminControlConfig
    $data['status'] = $quotation->status_info?->status_text ?? $quotation->status ?? '';

    # Original 'items' is an array of objects; we re-map it to just names
    $data['items'] = collect($quotation->items)->pluck('name')->all() ?? '';
    $data['items'] = $data['items'] ? implode(', ', $data['items']) : '';


    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function invoiceRuleNotification($invoice_id)
{
    # Load quotation with all related models we need
    # ["company_name", 'invoice_number', 'title', 'description', 'items', 'amount_paid', 'sub_total', 'tax', 'discount', 'total', 'status', 'due_date', 'client_id', 'contract_id', 'quotation_id', 'created_by', 'last_updated_by']
    $variable_list = array_merge(InvoiceConst::INVOICE_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'client_uuid', 'rule_module']);

    $invoice = Invoice::with(['creator', 'updater', 'status_info', 'quotation.clientDetail', 'quotation.leadDetail', 'client'])->findOrFail($invoice_id);
    $data = $invoice->toArray();

    $data['created_by_uuid'] = $invoice->created_by ?? ''; # Creator UUID
    $data['last_updated_by_uuid'] = $invoice->last_updated_by ?? ''; # Last updater UUID

    $data['created_by'] = $invoice->creator?->name ?? '';
    $data['last_updated_by'] = $invoice->updater?->name ?? '';
    $data['rule_module'] = CommonConst::MODULE_INVOICE;
    $data['client_id'] = $invoice->client?->name ?? $invoice->quotation?->client?->name ?? $invoice->quotation?->leadDetail?->name ?? ''; # Client name
    $data['client_uuid'] = $invoice->client_id ?? ''; # Client Id
    // $data['lead_uuid'] = $invoice->lead_id ?? ''; # Lead Id

    $data['due_date'] = formatAccordingDateTime($invoice->due_date, 'd-m-Y');
    $data['created_at'] = formatAccordingDateTime($invoice->created_at, 'full_date_1');

    # Status text from AdminControlConfig
    $data['status'] = $invoice->status_info?->status_text ?? $invoice->status ?? '';

    # Cast returns an array of item‑objects; we pluck only the `name` field.
    $data['items'] = collect($invoice->items)->pluck('name')->all() ?? '';
    $data['items'] = $data['items'] ? implode(', ', $data['items']) : '';

    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function followUpRuleNotification($follow_up_id)
{
    # Load Follow Up with all related models we need
    # ['call_status','lead_prospect','call_summary','created_by','last_updated_by','lead_id','client_id','next_call_datetime','need_site_visit','site_visit_datetime','site_visit_user_id' ];
    $variable_list = array_merge(FollowUpConst::FOLLOW_UP_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'lead_uuid', 'client_uuid', 'rule_module']);

    $followUp = FollowUp::with(['creator', 'updater', 'status_info', 'client', 'lead', 'site_visit_user'])->findOrFail($follow_up_id);
    $data = $followUp->toArray();

    $data['created_by_uuid'] = $followUp->created_by ?? ''; # Creator UUID
    $data['last_updated_by_uuid'] = $followUp->last_updated_by ?? ''; # Last updater UUID
    $data['rule_module'] = CommonConst::MODULE_FOLLOW_UP;
    $data['created_by'] = $followUp->creator?->name ?? '';
    $data['last_updated_by'] = $followUp->updater?->name ?? '';

    $data['client_id'] = $followUp->client?->name ?? '';
    $data['lead_id'] = $followUp->lead?->name ?? '';
    $data['site_visit_user_id'] = $followUp->site_visit_user?->name ?? '';

    $data['client_uuid'] = $followUp->client_id ?? ''; # Client Id
    $data['lead_uuid'] = $followUp->lead_id ?? ''; # Lead Id

    $data['next_call_datetime'] = $followUp->next_call_datetime ? formatAccordingDateTime($followUp->next_call_datetime, 'full_date_1') : '';
    $data['site_visit_datetime'] = $followUp->site_visit_datetime ? formatAccordingDateTime($followUp->site_visit_datetime, 'full_date_1') : '';
    $data['created_at'] = formatAccordingDateTime($followUp->created_at, 'full_date_1');

    # Status text from AdminControlConfig
    $data['call_status'] = $followUp->status_info?->status_text ?? $followUp->call_status ?? '';
    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function SiteVisitRuleNotification($site_visit_id)
{
    # Load Site Visit with all related models we need
    $variable_list = array_merge(SiteVisitConst::SITE_VISIT_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'assigned_user_uuid', 'lead_uuid', 'client_uuid', 'rule_module']);

    $siteVisit = SiteVisit::with(['creator', 'updater', 'status_info', 'assignee', 'client', 'lead', 'site_risk'])->findOrFail($site_visit_id);
    $data = $siteVisit->toArray();

    $data['created_by_uuid'] = $siteVisit->created_by ?? ''; # Creator UUID
    $data['last_updated_by_uuid'] = $siteVisit->last_updated_by ?? ''; # Last updater UUID
    $data['assigned_user_uuid'] = $siteVisit->visit_assignee ?? ''; # UUID of assigned user
    $data['rule_module'] = CommonConst::MODULE_SITE_VISIT;
    $data['created_by'] = $siteVisit->creator?->name ?? '';
    $data['last_updated_by'] = $siteVisit->updater?->name ?? '';

    $data['client_id'] = $siteVisit->client?->name ?? '';
    $data['lead_id'] = $siteVisit->lead?->name ?? '';
    $data['visit_assignee'] = $siteVisit->assignee?->name ?? '';

    $data['client_uuid'] = $siteVisit->client_id ?? ''; # Client Id
    $data['lead_uuid'] = $siteVisit->lead_id ?? ''; # Lead Id

    $data['products'] = $siteVisit->products ? implode(', ', ProductService::whereIn('id', $siteVisit->products)->pluck('name')->toArray()) : '';

    # site_risk info
    $data["address"] = $siteVisit->site_risk?->address ?? '';
    $data["building_type"] = $siteVisit->site_risk?->building_type ?? '';
    $data["roof_type"] = $siteVisit->site_risk?->roof_type ?? '';
    $data["height_of_roof"] = $siteVisit->site_risk?->height_of_roof ?? '';
    $data["service"] = $siteVisit->site_risk?->service ?? '';
    $data["visit_datetime"] = $siteVisit->site_risk?->visit_datetime && $siteVisit->site_risk?->visit_datetime ? formatAccordingDateTime($siteVisit->site_risk?->visit_datetime, 'full_date_1') : '';
    $data["solution_recommended"] = $siteVisit->site_risk?->solution_recommended ?? '';

    $data['visit_time'] = $siteVisit->visit_time ? formatAccordingDateTime($siteVisit->visit_time, 'full_date_1') : '';
    $data['created_at'] = formatAccordingDateTime($siteVisit->created_at, 'full_date_1');

    # Status text from AdminControlConfig
    $data['status'] = $siteVisit->status_info?->status_text ?? $siteVisit->status ?? '';
    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function contractRuleNotification($contract_id)
{
    # ['title', 'description', 'items', 'start_date', 'end_date', 'sub_total', 'discount', 'tax', 'total', 'status', 'client_id', 'quotation_id', 'invoice_id', 'created_by', 'last_updated_by'];
    $variable_list = array_merge(ContractConst::CONTRACT_VARIABLE, ['id', 'created_by_uuid', 'last_updated_by_uuid', 'lead_uuid', 'client_uuid', 'rule_module']);

    $contract = Contract::with(['creator', 'updater', 'status_info'])->findOrFail($contract_id);
    $data = $contract->toArray();

    $data['created_by_uuid'] = $contract->created_by  ?? ''; # Creator UUID
    $data['last_updated_by_uuid'] = $contract->last_updated_by ?? ''; # Last updater UUID
    $data['rule_module'] = CommonConst::MODULE_CONTRACT;
    $data['created_by'] = $contract->creator?->name ?? '';
    $data['last_updated_by'] = $contract->updater?->name ?? '';
    $data['client_id'] = $contract->client?->name ?? '';
    $data['quotation_id'] = $contract->quotation?->quotation_number ?? '';

    $data['start_date'] = formatAccordingDateTime($contract->due_date, 'd-m-Y');
    $data['end_date'] = formatAccordingDateTime($contract->due_date, 'd-m-Y');
    $data['created_at'] = formatAccordingDateTime($contract->created_at, 'full_date_1');

    # Status text from AdminControlConfig
    $data['status'] = $contract->status_info?->status_text ?? $contract->status ?? '';

    # $data['client_uuid'] = $siteVisit->client_id ?? ''; # Client Id
    # $data['lead_uuid'] = $siteVisit->lead_id ?? ''; # Lead Id

    # Cast returns an array of item‑objects; we pluck only the `name` field.
    $data['items'] = collect($contract->items)->pluck('name')->all() ?? '';
    $data['items'] = $data['items'] ? implode(', ', $data['items']) : '';

    $data['invoice_id'] = $contract->invoices->pluck('invoice_number')->implode(', ');
    # Filter only required fields from $data
    $filteredData = [];
    foreach ($variable_list as $key) {
        $filteredData[$key] = $data[$key] ?? null;
    }

    return $filteredData;
}

function filterNotificationQuery($query, $module_id, $module_log_type)
{
    $ids = [];

    switch ($module_log_type) {
        case CommonConst::MODULE_LEAD:
            $c_ids = array_merge(
                FollowUp::where('lead_id', $module_id)->pluck('id')->toArray(),
                SiteVisit::where('lead_id', $module_id)->pluck('id')->toArray(),
                // $q_ids = Quotation::where('lead_id', $module_id)->pluck('id')->toArray(),
                // Invoice::whereIn('quotation_id', $q_ids)->pluck('id')->toArray(),
                [$module_id]
            );
            break;

        case CommonConst::MODULE_CLIENT:
            $c_ids = array_unique(array_merge(
                FollowUp::where('client_id', $module_id)->pluck('id')->toArray(),
                SiteVisit::where('client_id', $module_id)->pluck('id')->toArray(),
                // $q_ids = Quotation::where('client_id', $module_id)->pluck('id')->toArray(),
                // Invoice::whereIn('quotation_id', $q_ids)->pluck('id')->toArray(),
                // Invoice::where('client_id', $module_id)->pluck('id')->toArray(),
                [$module_id]
            ));
            break;

        case CommonConst::MODULE_QUOTATION:
            $c_ids = array_merge(
                // Invoice::where('quotation_id', $module_id)->pluck('id')->toArray(),
                [$module_id]
            );
            break;

        case CommonConst::MODULE_INVOICE:
            $c_ids = [$module_id];
            break;

        case CommonConst::MODULE_USER:
            $c_ids = [$module_id];
            break;

        default:
            return $query;
    }

    $ids = array_unique(array_merge(
        NotificationLog::whereIn('receiver_id', $c_ids)->pluck('id')->toArray(),
        NotificationLog::whereIn('module_id', $c_ids)->pluck('id')->toArray()
    ));

    return $query->whereIn('id', $ids);
}
