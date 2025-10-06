<?php

namespace Modules\Leads\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Http\Requests\{LeadStoreRequest, LeadUpdateRequest};
use Modules\Leads\Models\Lead;
use Modules\Leads\Services\LeadService;
use Modules\Leads\Transformers\LeadResource;
use Modules\SiteVisit\Models\SiteVisit;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\Leads\Models\LeadAttachment;

class LeadController extends Controller
{
    protected $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function optionLeadList(Request $request)
    {
        try {
            if (class_exists(\Modules\SiteVisit\Models\SiteVisit::class) && $request->type == 'site-visit') {
                $siteVisitLeadIds = SiteVisit::where('status', SiteVisitConst::READY_FOR_SRM)->pluck('lead_id')->toArray();
                $clients = Lead::select('id', 'name', "country_code", "phone", "address")->whereIn('id', $siteVisitLeadIds)->whereNotIn('status', [LeadConst::NOT_INTERESTED, LeadConst::CONVERT_TO_CLIENT, CommonConst::IN_ACTIVE])->get();
            } else {
                $clients = Lead::select('id', 'name', "country_code", "phone", "address")->whereNotIn('status', [LeadConst::NOT_INTERESTED, LeadConst::CONVERT_TO_CLIENT, CommonConst::IN_ACTIVE])->get();
            }
            return $this->actionSuccess("option Lead List", $clients);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function index(Request $request): JsonResponse
    {
        $statusList = $request->has('status_list')
            ? (is_array($request->status_list) ? $request->status_list : explode(',', $request->status_list))
            : [];

        $paginated = $this->leadService->getPaginatedLeads(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('status'),
            $request->input('search'),
            $request->input('assigned_user'),
            $request->input('client_id'),
            $request->input('quotation_id'),
            $request->input('contract_id'),
            $request->input('invoice_id'),
            $request->input('created_by'),
            $request->input('last_updated_by'),
            $request->input('user_view_id'),
            $statusList,
        );

        return response()->json([
            'data' => LeadResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'message' => 'Leads retrieved successfully'
        ]);
    }

    public function dashboardLeadList(Request $request): JsonResponse
    {
        $statusList = $request->has('status_list')
            ? (is_array($request->status_list) ? $request->status_list : explode(',', $request->status_list))
            : [];

        $paginated = $this->leadService->getPaginatedDashboardLeads(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('status'),
            $request->input('search'),
            $request->input('start_date'),
            $request->input('end_date'),
            $statusList,
        );

        return response()->json([
            'data' => LeadResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'message' => 'Leads retrieved successfully'
        ]);
    }

    public function store(LeadStoreRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead(
            array_merge($request->validated(), ['created_by' => Auth::user()->uuid])
        );

        # Lead created
        NotificationJob::dispatch(LeadConst::RULE_LEAD_CREATED, leadRuleNotification($lead->id), null, loginUserId());

        # Lead assigned to user
        if ($lead->assigned_user) {
            NotificationJob::dispatch(LeadConst::RULE_ASSIGNED_TO_USER, leadRuleNotification($lead->id), null, loginUserId());
        }

        return response()->json(['message' => 'Lead created successfully', 'data' => new LeadResource($lead)], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $lead = $this->leadService->getLeadById($id);
        return response()->json([
            'data' => new LeadResource($lead),
            'message' => 'Lead retrieved successfully'
        ]);
    }

    // in this update method to checking the phone number is unique or not
    public function update(LeadUpdateRequest $request, string $id): JsonResponse
    {
        $assigned_user = Lead::where('id', $id)->pluck('assigned_user')->first();

        $lead = $this->leadService->updateLead(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        # Lead assigned to user
        if ($lead->assigned_user != $assigned_user) {
            NotificationJob::dispatch(LeadConst::RULE_ASSIGNED_TO_USER, leadRuleNotification($lead->id), null, loginUserId());
        }

        return response()->json(['data' => new LeadResource($lead), 'message' => 'Lead updated successfully']);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->leadService->deleteLead($id);
        return response()->json([
            'message' => 'Lead deleted successfully'
        ]);
    }

    public function updateDirectLeadStatus(Request $request)
    {
        DB::beginTransaction();
        try {
            $record = Lead::find($request->id);
            if (!$record) {
                return $this->actionFailure('Record not Found');
            }

            $oldStatus = $record->status;
            $record->status = $request->status;
            $record->save();

            $RuleCheckHelper = new RuleCheckHelper();
            $RuleCheckHelper->onlyStatusChangeCheckRule(CommonConst::MODULE_LEAD, $record->status, [$record->id], $oldStatus);

            DB::commit();
            return $this->actionSuccess('Lead status updated successfully', $record);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->actionFailure($e->getMessage());
        }
    }

    public function downloadSample()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define headers with standardized format
        $headers = [
            'Name',
            'Email',
            'Contact Person',
            'Contact Person Role',
            'Phone',
            'Secondary Phone',
            'Source',
            'Address',
            'City',
            'Date of Birth',
            'Anniversary Date',
            'Notes',
            'Assigned User',
        ];

        foreach (range('A', 'M') as $key => $column) {
            $sheet->setCellValue($column . '1', $headers[$key]);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        // Get a valid city name for the sample
        $sampleCity = City::first();
        $cityName = $sampleCity ? $sampleCity->name : '';

        // Add sample data
        $sampleData = [
            [
                'John Company',                    // name
                'john@example.com',               // email
                'John Doe',                       // contact_person
                'Manager',                        // contact_person_role
                '1234567890',                    // phone
                '0987654321, 1452365897',        // secondary_phone
                'Website',                        // source
                '123 Main St',                    // address
                $cityName,                        // city
                'YYYY-MM-DD',                    // date_of_birth
                'YYYY-MM-DD',                    // anniversary_date
                'Sample lead',                    // notes
                'user@example.com'                // user_email
            ]
        ];

        $sheet->fromArray($sampleData, null, 'A2');

        // Auto-size columns for better readability
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add notes/comments for important fields
        $sheet->getComment('A1')->getText()->createTextRun('Required field');
        $sheet->getComment('E1')->getText()->createTextRun('Required field');
        $sheet->getComment('F1')->getText()->createTextRun('Multiple numbers can be separated by commas');
        $sheet->getComment('I1')->getText()->createTextRun('If city does not exist, it will be created automatically');
        $sheet->getComment('J1')->getText()->createTextRun('Use YYYY-MM-DD format');
        $sheet->getComment('K1')->getText()->createTextRun('Use YYYY-MM-DD format');

        $writer = new Xlsx($spreadsheet);
        $filename = 'leads-sample.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('file');
            $skippedRecords = [];
            $importedCount = 0;

            if (!$file || !$file->isValid()) {
                $skippedRecords[] = "Invalid file format";
                return response()->json([
                    'message' => 'Import failed',
                    'show_dialog' => true,
                    'dialog_title' => 'Import Error',
                    'dialog_messages' => $skippedRecords,
                    'imported_count' => $importedCount,
                    'skipped_records' => $skippedRecords
                ], 400);
            }

            // Extract headers from the first row
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            // Get the header row and standardize column names
            // Extract headers from the first row
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            // Get the header row and standardize column names
            $headers = $worksheet->getRowIterator(1)->current();
            // $headerRow = [];
            // foreach ($headers->getCellIterator() as $cell) {
            //     $columnName = strtolower(trim($cell->getValue()));
            //     $columnName = preg_replace('/\s+/', '_', $columnName);
            //     $headerRow[] = $columnName;
            // }
            $headerRow = [];
            foreach ($headers->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if (is_null($value) || trim($value) === '') {
                    continue; // Skip empty headers
                }
                $columnName = strtolower(trim($value));
                $columnName = preg_replace('/\s+/', '_', $columnName);
                $headerRow[] = $columnName;
            }

            // Define allowed columns (in standardized format)
            $allowedColumns = [
                'name',
                'email',
                'contact_person',
                'contact_person_role',
                'phone',
                'secondary_phone',
                'source',
                'address',
                'city',
                'date_of_birth',
                'anniversary_date',
                'notes',
                'assigned_user'
            ];
            // Check for unknown columns in the Excel file
            $unknownColumns = array_diff($headerRow, $allowedColumns);

            if (!empty($unknownColumns)) {
                $skippedRecords[] = "Invalid columns: " . implode(', ', $unknownColumns);
                return response()->json([
                    'message' => 'Import failed',
                    'show_dialog' => true,
                    'dialog_title' => 'Invalid Columns',
                    'dialog_messages' => $skippedRecords,
                    'imported_count' => $importedCount,
                    'skipped_records' => $skippedRecords
                ], 400);
            }
            // Check for missing required columns
            $requiredColumns = ['name', 'phone'];
            $missingRequired = array_diff($requiredColumns, $headerRow);
            if (!empty($missingRequired)) {
                $skippedRecords[] = "Missing columns: " . implode(', ', $missingRequired);
                return response()->json([
                    'message' => 'Import failed',
                    'show_dialog' => true,
                    'dialog_title' => 'Missing Columns',
                    'dialog_messages' => $skippedRecords,
                    'imported_count' => $importedCount,
                    'skipped_records' => $skippedRecords
                ], 400);
            }

            // Create column mapping for valid columns only
            $columnMap = array_flip($headerRow);

            // Get all cities for efficient lookup with lowercase keys
            $cities = City::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
                return [strtolower($name) => $id];
            })->toArray();

            DB::beginTransaction();

            // Start from row 2 (after headers)
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = trim($cell->getValue());
                }

                // Skip completely empty rows
                if (collect($rowData)->filter()->isEmpty()) {
                    continue;
                }

                $rowNumber = $row->getRowIndex();
                $leadName = $rowData[$columnMap['name']] ?? 'Unknown';

                // Skip if required fields are empty
                if (empty($rowData[$columnMap['name']]) || empty($rowData[$columnMap['phone']])) {
                    $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Missing name or phone";
                    continue;
                }

                // Check for existing phone number
                $phone = trim($rowData[$columnMap['phone']]);
                $existingLead = Lead::where('phone', $phone)->first();

                if ($existingLead) {
                    $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Phone {$phone} already exists";
                    continue;
                }

                // Check for existing email
                if (isset($columnMap['email']) && !empty($rowData[$columnMap['email']])) {
                    $email = trim($rowData[$columnMap['email']]);
                    $existingLead = Lead::where('email', $email)->first();

                    if ($existingLead) {
                        $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Email {$email} already exists";
                        continue;
                    }
                }


                // Look up city ID by name, if city exists
                $cityId = null;
                if (isset($columnMap['city']) && !empty($rowData[$columnMap['city']])) {
                    $cityName = trim($rowData[$columnMap['city']]);
                    if (!empty($cityName)) {
                        try {
                            $cityId = $cities[strtolower($cityName)] ?? City::create([
                                'name' => $cityName,
                                'country_id' => Country::where('iso2', 'IN')->value('id'),
                                'state_id' => State::where('state_code', 'HP')->value('id'),
                                'created_by' => Auth::user()->uuid
                            ])->id;
                        } catch (\Exception $e) {
                            $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Invalid city {$cityName}";
                            continue;
                        }
                    }
                }

                // Create lead data array with only valid columns
                $leadData = [
                    'name' => $leadName,
                    'phone' => $phone,
                    'status' => '',
                    'city_id' => $cityId,
                    'created_by' => Auth::user()->uuid
                ];

                // Add optional fields only if they exist in the Excel file
                $optionalFields = [
                    'email' => 'email',
                    'contact_person' => 'contact_person',
                    'contact_person_role' => 'contact_person_role',
                    'secondary_phone' => 'secondary_phone',
                    'source' => 'source',
                    'referral_detail' => 'referral_detail',
                    'address' => 'address',
                    'notes' => 'notes'
                ];

                foreach ($optionalFields as $excelColumn => $dbColumn) {
                    if (isset($columnMap[$excelColumn])) {
                        $leadData[$dbColumn] = $rowData[$columnMap[$excelColumn]] ?? '';
                    }
                }

                // Handle date fields
                if (isset($columnMap['date_of_birth']) && !empty($rowData[$columnMap['date_of_birth']])) {
                    try {
                        $leadData['date_of_birth'] = Carbon::parse($rowData[$columnMap['date_of_birth']])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Invalid birth date format";
                        continue;
                    }
                }

                // Handle user email
                if (isset($columnMap['assigned_user']) && !empty($rowData[$columnMap['assigned_user']])) {
                    try {
                        $leadData['assigned_user'] = User::where('email', $rowData[$columnMap['assigned_user']])->value('uuid');
                    } catch (\Exception $e) {
                        $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Invalid user email";
                        continue;
                    }
                }

                if (isset($columnMap['anniversary_date']) && !empty($rowData[$columnMap['anniversary_date']])) {
                    try {
                        $leadData['anniversary_date'] = Carbon::parse($rowData[$columnMap['anniversary_date']])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Invalid anniversary date format";
                        continue;
                    }
                }

                $leadData['status'] = LeadConst::NO_ACTION;

                // Create the lead
                try {
                    Lead::create($leadData);
                    $importedCount++;
                } catch (\Exception $e) {
                    $skippedRecords[] = "Row {$rowNumber}: {$leadName}: Failed to create lead";
                    continue;
                }
            }

            DB::commit();

            $message = "Successfully imported {$importedCount} leads.";

            // Always return both success message and any skipped records
            return response()->json([
                'message' => $message,
                'show_dialog' => true,
                'dialog_title' => 'Import Results',
                'dialog_messages' => array_merge(
                    [$message],
                    count($skippedRecords) > 0 ? ['Skipped Records:'] : [],
                    $skippedRecords
                ),
                'imported_count' => $importedCount,
                'skipped_records' => $skippedRecords
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            er('Lead Controller : import error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Import failed',
                'show_dialog' => true,
                'dialog_title' => 'Import Error',
                'dialog_messages' => ['Import failed: ' . $e->getMessage()],
                'imported_count' => $importedCount,
                'skipped_records' => $skippedRecords
            ], 500);
        }
    }

    public function export()
    {
        $leads = Lead::with(['city', 'creator'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $headers = [
            'Name',
            'Email',
            'Contact Person',
            'Contact Person Role',
            'Phone',
            'Secondary Phone',
            'Source',
            'Address',
            'Status',
            'City',
            'Date of Birth',
            'Anniversary Date',
            'Notes',
            'Created By',
            'Created At'
        ];

        foreach (range('A', 'O') as $key => $column) {
            $sheet->setCellValue($column . '1', $headers[$key]);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($leads as $lead) {
            // Convert array fields to string
            $secondaryPhone = is_array($lead->secondary_phone)
                ? implode(', ', $lead->secondary_phone)
                : $lead->secondary_phone;

            $sheet->fromArray([
                $lead->name,
                $lead->email,
                $lead->contact_person,
                $lead->contact_person_role,
                $lead->phone,
                $secondaryPhone,
                $lead->source,
                $lead->address,
                $lead->status,
                $lead->city?->name ?? '',
                $lead->date_of_birth ? date('Y-m-d', strtotime($lead->date_of_birth)) : '',
                $lead->anniversary_date ? date('Y-m-d', strtotime($lead->anniversary_date)) : '',
                $lead->notes,
                $lead->creator?->name ?? '',
                $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : ''
            ], null, 'A' . $row);
            $row++;
        }

        // Auto-size columns for better readability
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'leads-export-' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function leadAttachments($id)
    {
        $leads = LeadAttachment::where('lead_id', $id)->get();

        return response()->json([
            'message' => 'Lead attachments fetched successfully',
            'data' => $leads
        ]);
    }
}
