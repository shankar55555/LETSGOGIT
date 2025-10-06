<?php

namespace Modules\Quotations\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Mail\MailSend;
use App\Models\AdminControlConfig;
use App\Models\ExportLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\Clients\Models\Client;
use Modules\Invoices\Services\InvoiceService;
use Modules\Quotations\Http\Requests\{QuotationStoreRequest, QuotationUpdateRequest};
use Modules\Quotations\Services\QuotationService;
use Modules\Quotations\Transformers\QuotationResource;
use Symfony\Component\HttpFoundation\Response;
use Modules\Leads\Models\Lead;
use Modules\Quotations\Models\Quotation;
use Nwidart\Modules\Facades\Module;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Services\WhatsAppService;
use Modules\Clients\Models\ClientAttachment;
use Modules\Invoices\Models\Invoice;
use Modules\Quotations\Constants\QuotationConst;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\LeadAttachment;
use Modules\Leads\Services\LeadService;

class QuotationController extends Controller
{
    const CONTROLLER_NAME = "Quotation Controller";
    protected $quotationService;
    protected $invoiceService;
    protected $leadService;
    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    public function index(Request $request): JsonResponse
    {
        $paginated = $this->quotationService->getPaginatedQuotations(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('status'),
            $request->input('client_id'),
            $request->input('lead_id'),
            $request->input('contract_id'),
            $request->input('created_by'),
            $request->input('last_updated_by'),
            $request->input('user_view_id'),
            $request->input('search')
        );

        return response()->json([
            'data' => QuotationResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Quotations retrieved successfully'
        ]);
    }

    public function store(QuotationStoreRequest $request): JsonResponse
    {
        $quotation = $this->quotationService->createQuotation(
            array_merge($request->validated(), ['created_by' => Auth::user()->uuid])
        );

        # Quotation Status created
        if ($quotation->status == QuotationConst::QUOTATION_CREATED) {
            NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_CREATED, quotationRuleNotification($quotation->id), null, loginUserId());
        }

        return response()->json([
            'data' => new QuotationResource($quotation),
            'message' => 'Quotation created successfully'
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $quotation = $this->quotationService->getQuotationById($id);
        return response()->json([
            'data' => new QuotationResource($quotation),
            'message' => 'Quotation retrieved successfully'
        ]);
    }

    public function update(QuotationUpdateRequest $request, string $id): JsonResponse
    {
        $old_status = Quotation::where('id', $id)->pluck('status')->first();
        $quotation = $this->quotationService->updateQuotation(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        # Quotation Status created
        if ($quotation->status == QuotationConst::QUOTATION_CREATED && $quotation->status != $old_status) {
            NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_CREATED, quotationRuleNotification($quotation->id), null, loginUserId());
        }

        return response()->json([
            'data' => new QuotationResource($quotation),
            'message' => 'Quotation updated successfully'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->quotationService->deleteQuotation($id);
        return response()->json([
            'message' => 'Quotation deleted successfully'
        ]);
    }

    public function generateInvoices(Request $request)
    {

        $data = $request->all();
        $quotation = $this->quotationService->getQuotationById($request->quotation_id);
        if ($request->recurring_invoice == 'no') {
            $amount_due = 0;
        } else {
            $amount_due = $quotation->total;
        }
        $quotation = $this->quotationService->updateQuotationDueAmount(
            $request->quotation_id,
            [
                'amount_due' => $amount_due
            ]
        );

        $this->invoiceService = new InvoiceService();
        $this->invoiceService->generateScheduling($quotation, $data);


        # converting lead to client if quotation is made for lead

        $fetchLeadIfExist = Quotation::where('id', $request->quotation_id)
            ->whereNotNull('lead_id')
            ->whereNull('client_id')
            ->first();

        if ($fetchLeadIfExist) {
            $lead = Lead::where('id', $fetchLeadIfExist->lead_id)->first();
            if ($lead) {
                $lead->status = LeadConst::CONVERT_TO_CLIENT;
                $lead->save();
                $method = 'auto';
                $this->leadService = new LeadService();
                $this->leadService->convertToClient($lead->id, $method, loginUserId());
            }
            $leadAttachments = LeadAttachment::where('lead_id', $fetchLeadIfExist->lead_id)->get();
            if ($leadAttachments) {

                $invoiceID = Invoice::where('quotation_id', $request->quotation_id)->pluck('id')->first();
                $updatedQuotation = Quotation::where('id', $request->quotation_id)->first();

                foreach ($leadAttachments as $leadAttachment) {
                    $clientAttachment = new ClientAttachment();
                    $clientAttachment->client_id = $updatedQuotation->client_id;
                    $clientAttachment->quotation_id = $updatedQuotation->id;
                    $clientAttachment->invoice_id = $invoiceID;
                    $clientAttachment->file_name = $leadAttachment->file_name;
                    $clientAttachment->file_path = $leadAttachment->file_path;
                    $clientAttachment->mime_type = $leadAttachment->mime_type;
                    $clientAttachment->sent_via = $leadAttachment->sent_via;
                    $clientAttachment->uploaded_by = $leadAttachment->uploaded_by;
                    $clientAttachment->save();
                }
            }
        }

        return $this->actionSuccess('Invoices generated successfully');
    }

    public function optionLeadList()
    {
        try {
            $leads = Lead::select('id', 'name')->get();
            return $this->actionSuccess("option Lead List", $leads);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function optionClientList()
    {
        try {
            $clients = [];

            if (Module::has('Clients') && class_exists(Client::class)) {
                $clients = Client::select('id', 'name')
                    ->where('status', '!=', 'in-active')
                    ->get()
                    ->toArray();
            }

            return $this->actionSuccess("Option Client List", $clients);
        } catch (\Exception $e) {
            er("Quotation Controller : Failed to fetch Client List: " . $e->getMessage());
            return $this->actionFailure("Failed to fetch Client List");
        }
    }

    public function downloadPdf($quotationId)
    {
        $quotation = Quotation::with(['clientDetail', 'leadDetail'])
            ->findOrFail($quotationId);

        $settings = Setting::pluck('value', 'key') ?? [];

        $data = [
            'quotation' => $quotation,
            'settings' => $settings,
        ];

        $pdf = Pdf::loadView('pdf.quotationPdf', $data);

        return $pdf->download("quotation_{$quotation->quotation_number}.pdf");
    }

    public function updateDirectQuotationStatus(Request $request)
    {
        DB::beginTransaction();
        try {

            $quotation = Quotation::find($request->id);
            if (!$quotation) return $this->actionFailure('Record not Found');

            $old_status = $quotation->status;
            $quotation->status = $request->status;
            $quotation->save();
            $message = 'Quotation status updated successfully';
            if ($quotation->status != $old_status) {
                # Quotation Status created
                if ($quotation->status == QuotationConst::QUOTATION_CREATED) {
                    NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_CREATED, quotationRuleNotification($quotation->id), null, loginUserId());
                }

                # Quotation Status accepted
                elseif ($quotation->status == QuotationConst::QUOTATION_ACCEPTED) {
                    NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_ACCEPTED, quotationRuleNotification($quotation->id), null, loginUserId());
                }

                # Quotation Status rejected
                elseif ($quotation->status == QuotationConst::QUOTATION_REJECTED) {
                    NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_REJECTED, quotationRuleNotification($quotation->id), null, loginUserId());
                }

                # Quotation Status expired
                elseif ($quotation->status == QuotationConst::QUOTATION_EXPIRED) {
                    NotificationJob::dispatch(QuotationConst::RULE_QUOTATION_EXPIRED, quotationRuleNotification($quotation->id), null, loginUserId());
                }

                $RuleCheckHelper = new RuleCheckHelper();
                $RuleCheckHelper->onlyStatusChangeCheckRule(CommonConst::MODULE_QUOTATION, $quotation->status, [$quotation->id], $old_status);

                // $statusInfo = AdminControlConfig::where('status_for', CommonConst::MODULE_QUOTATION)->where('slug', $request->status)->select('id', 'trigger_action', 'send_plat_forms')->first();
                // if ($statusInfo && in_array(QuotationConst::TRIGGER_SEND_QUOTATION, makeAnyIdArrayFormat($statusInfo->trigger_action))) {
                //     $platforms = makeAnyIdArrayFormat($statusInfo->send_plat_forms);
                //     $responses = $this->statusTriggerActionSenFile($request, $platforms);

                //     // Check if all responses were successful
                //     $allSuccessful = collect($responses)->every(fn($res) => $res['status'] == 200);
                //     if ($allSuccessful) {
                //         $quotation->status = $request->status;
                //         $quotation->save();
                //     }
                // }
            }
            DB::commit();
            return $this->actionSuccess($message, $quotation);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->actionFailure(`Something went wrong while updating status $e`);
        }
    }

    protected function statusTriggerActionSenFile($request, ?array $platForms = [])
    {
        $quotation = Quotation::with(onlyQuotationUserRelation())->find($request->id);
        $info = $quotation->clientDetail ?? $quotation->leadDetail ?? null;
        $results = [];

        foreach ($platForms as $platform) {
            $data = [
                'module_name'         => CommonConst::MODULE_QUOTATION,
                'module_id'           => $quotation->id,
                'receiver_id'         => $info->id ?? '',
                'name'                => $info->name ?? 'No Name',
                'email'               => $info->email ?? '',
                'receiver_column'     => $quotation->clientDetail ? "client_id" : "lead_id",
                'phone'               => $info->phone ?? '',
                'socialPlatform'      => $platform,
                'sendAttachmentType'  => CommonConst::AUTO_SEND_FILE,
                'message'             => "Auto send file triggered by status update",
            ];

            # Create a fake POST request to the same controller method
            $internalRequest = Request::create('/quotation/send-message', 'POST', $data);

            # Resolve the currently authenticated user for internal request
            $internalRequest->setUserResolver(function () {
                return Auth::user();
            });

            # Call the method
            $response = $this->quotationSendMessage($internalRequest);

            # Decode the response content (JSON)
            $responseData = json_decode($response->getContent(), true);

            $results[] = [
                'status'   => $responseData['status'] ?? null,
                'message'  => $responseData['message'] ?? null,
                'id'       => $responseData['data']['id'] ?? null,
            ];
        }
    }

    public function quotationSendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module_name'         => "required|in:" . CommonConst::MODULE_QUOTATION,
            'module_id'           => 'required',
            'receiver_id'         => 'required',
            'name'                => 'required',
            'email'               => 'nullable|email',
            'phone'               => 'required',
            'socialPlatform'      => "required|in:" . implode(',', CommonConst::SEND_NOTIFICATION_PLAT_FORM),
            'sendAttachmentType'  => "required|in:" . implode(',', CommonConst::SEND_ATTACHMENT_TYPE_LIST),
            'message'             => 'required|string',
            'files'               => 'nullable|array',
            'files.*'             => 'file|mimes:pdf,doc,docx,jpeg,jpg,png',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        set_time_limit(0);

        $user = Auth::user();
        $sender_id = $user->uuid;
        $quotation = Quotation::with(onlyQuotationUserRelation())->find($request->module_id);
        $directory = "email_attachments";
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }

        if ($request->sendAttachmentType == CommonConst::SELECT_FILE && $request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = 'quotation_' . formattedDateTime() . '_' . $originalName;
        }

        $mainFileUrl = $mainFileCaption = $mainMime = $mainExtension = null;

        if ($request->sendAttachmentType == CommonConst::SELECT_FILE && $request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = 'quotation_' . now()->format('YmdHis') . '_' . $originalName;
                $path = $directory . '/' . $filename;
                Storage::disk('public')->putFileAs($directory, $file, $filename);

                $fileUrl = url('storage/' . $path);
                $fileCaption = $originalName;
                $mime = $file->getMimeType();
                $extension = Str::startsWith($mime, 'image/') ? 'Image' : 'Document';

                # Create attachment
                $this->createAttachment($request->receiver_id, $fileCaption, $path, $mime, $request->socialPlatform, $sender_id, $quotation->id);

                if (!isset($mainFileUrl)) {
                    $mainFileUrl     = $fileUrl;
                    $mainFileCaption = $fileCaption;
                    $mainMime        = $mime;
                    $mainExtension   = $extension;
                }
            }
        } elseif ($request->sendAttachmentType == CommonConst::AUTO_SEND_FILE) {
            $quotation = Quotation::with(onlyQuotationUserRelation())->find($request->module_id);

            $settings = Setting::pluck('value', 'key') ?? [];

            $pdf = Pdf::loadView('pdf.quotationPdf', ['quotation' => $quotation, 'settings' => $settings]);
            $filename = 'quotation_' . formattedDateTime() . '.pdf';
            $path = $directory . '/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            $mainFileUrl     = url('storage/' . $path);
            $mainFileCaption = $quotation && $quotation->quotation_number ? 'Quotation #' . $quotation->quotation_number : 'Quotation Attachment';
            $mainMime        = 'application/pdf';
            $mainExtension   = 'Document';

            # Create attachment
            $this->createAttachment($request->receiver_id, $mainFileCaption, $path, $mainMime, $request->socialPlatform, $sender_id, $quotation->id);
        }

        # Export log (only if attachment is present)
        // if ($request->sendAttachmentType != CommonConst::NO_ATTACHMENT && $fileUrl) {
        //     ExportLog::create([
        //         'name'       => $filename,
        //         'table_name' => 'invoices',
        //         'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
        //         'file_path'  => $fileUrl,
        //         'status'     => CommonConst::SUCCESS,
        //         'created_by' => $sender_id,
        //     ]);
        // }

        if ($mainFileUrl) {
            ExportLog::create([
                'name'       => $mainFileCaption,
                'table_name' => 'quotations',
                'extension'  => pathinfo($mainFileCaption, PATHINFO_EXTENSION),
                'file_path'  => $mainFileUrl,
                'status'     => CommonConst::SUCCESS,
                'created_by' => $sender_id,
            ]);
        }

        $receiver_column = $request->receiver_column ?? 'receiver_id';

        $email_body = [
            'module_name'        => $request->module_name,
            'module_id'          => $request->module_id,
            $receiver_column     => $request->receiver_id,
            'name'               => $request->name,
            'email'              => $request->email,
            'phone'              => $request->phone,
            'socialPlatform'     => $request->socialPlatform,
            'sendAttachmentType' => $request->sendAttachmentType,
            'message'            => $request->message,
        ];

        $additional_info = [];
        if ($mainFileUrl) {
            $additional_info = [
                'fileUrl'     => $mainFileUrl,
                'extension'   => $mainExtension,
                'fileCaption' => $mainFileCaption,
            ];
        }

        $receiver_contact = $request->socialPlatform === CommonConst::WHATSAPP ? $request->phone : $request->email;

        $log = NotificationLog::create([
            'receiver_contact'     => $receiver_contact,
            'subject'              => "Quotation has been successfully created and sent to the user: " . $request->name,
            'content'              => $request->message,
            'priority'             => CommonConst::HIGH,
            'status'               => CommonConst::PENDING,
            'notification_type_id' => null,
            'receiver_id'          => null,
            'section_type'         => $request->socialPlatform,
            'is_notification'      => false,
            'email_body'           => json_encode($email_body),
            'additional_info'      => json_encode($additional_info),
            'sender_id'            => $sender_id,
            'module_id'            => $request->module_id,
        ]);

        $message = '';

        if ($request->socialPlatform == CommonConst::WHATSAPP) {
            $plainTextMessage = str_replace(['<br>', '<br/>', '<br />'], "\n", $request->message);
            $fileUrl = $mainFileUrl ?? '';
            $fileCaption = $mainFileCaption ?? '';
            $extension = $mainExtension ?? '';
            $userName = trim($request->name);

            $response = (new WhatsAppService())->sendMediaMessage(
                $userName,
                $receiver_contact,
                $plainTextMessage,
                $fileUrl,
                $fileCaption,
                $extension
            );

            $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
            $log->message = $response->message ?? '';
            $log->save();
            $message = $response->status ? 'WhatsApp message sent successfully.' : $response->message;
            $message = 'WhatsApp message sent successfully.';
        } else if ($request->socialPlatform == CommonConst::EMAIL) {
            try {
                $mailData = [
                    'subject'                => "Quotation has been successfully created and sent to the user: " . $request->name,
                    'receiver_contact'       => $receiver_contact,
                    'mail_log_id'            => $log->id,
                    'attachment_file_url'    => $fileUrl,
                    'attachment_path'         => $fileUrl != "" ? 'public/' . $path : null,
                    'attachment_original_name' => $filename != "" ? $filename : null,
                    'hidden_pre_header'      => "This is your quotation summary. Please check the attachment for full details.",
                    'content'                => $request->message,
                ];

                Mail::to($receiver_contact)->send(new MailSend($mailData));

                $log->status = CommonConst::SUCCESS;
                $message = 'Email sent successfully.';
            } catch (\Exception $e) {
                createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
                $log->status = CommonConst::FAILED;
                $log->message = $e->getMessage();
                $message = $e->getMessage();
            }
            $log->save();
        }

        if ($log->status == CommonConst::SUCCESS && $quotation->status == QuotationConst::QUOTATION_CREATED) {
            $quotation->status = QuotationConst::QUOTATION_SENT;
            $quotation->save();
        }

        try {
            event(new NotificationMessage($log->subject, $sender_id, $request->socialPlatform));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
        }

        return $log->status == CommonConst::SUCCESS ? $this->actionSuccess($message, $log) : $this->actionFailure($message, $log);
    }

    private function createAttachment($receiver_id, $fileCaption, $path, $mime, $socialPlatform, $senderId, $quotationId)
    {
        $userType  = Client::where('id', $receiver_id)->first();
        if ($userType) {
            ClientAttachment::create([
                'client_id'    => $receiver_id,
                'quotation_id' => $quotationId,
                'file_name'    => $fileCaption,
                'file_path'    => $path,
                'mime_type'    => $mime,
                'sent_via'     => $socialPlatform,
                'uploaded_by'  => $senderId,
            ]);
        } else {
            LeadAttachment::create([
                'lead_id'      => $receiver_id,
                'quotation_id' => $quotationId,
                'file_name'    => $fileCaption,
                'file_path'    => $path,
                'mime_type'    => $mime,
                'sent_via'     => $socialPlatform,
                'uploaded_by'  => $senderId,
            ]);
        }
    }
}
