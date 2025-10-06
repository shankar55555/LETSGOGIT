<?php

namespace Modules\Invoices\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Mail\MailSend;
use App\Models\ExportLog;
use App\Models\Setting;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\Invoices\Http\Requests\{InvoiceStoreRequest, InvoiceUpdateRequest};
use Modules\Invoices\Models\Invoice;
use Modules\Invoices\Services\InvoiceService;
use Modules\Invoices\Transformers\InvoiceResource;
use Modules\Quotations\Services\QuotationService;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Invoices\Constants\InvoiceConst;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Services\WhatsAppService;
use Modules\Clients\Models\Client;
use Modules\Clients\Models\ClientAttachment;
use Modules\Leads\Models\LeadAttachment;

class InvoiceController extends Controller
{
    const CONTROLLER_NAME = "Invoice Controller";
    protected $invoiceService;
    protected $quotationService;
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request): JsonResponse
    {
        $paginated = $this->invoiceService->getPaginatedInvoices(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('status'),
            $request->input('client_id'),
            $request->input('contract_id'),
            $request->input('quotation_id'),
            $request->input('created_by'),
            $request->input('last_updated_by'),
            $request->input('user_view_id'),
            $request->input('search'),
            $request->input('lead_id')
        );
        return response()->json([
            'data' => InvoiceResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    public function store(InvoiceStoreRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice(
            array_merge($request->validated(), [
                'created_by' => Auth::user()->uuid,
                'amount_paid' => 0,
                'due_date' => now(),
            ])
        );

        # Invoice created
        NotificationJob::dispatch(InvoiceConst::RULE_INVOICE_CREATED, invoiceRuleNotification($invoice->id), null, loginUserId());
        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice created successfully'
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoiceById($id);
        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function update(InvoiceUpdateRequest $request, string $id): JsonResponse
    {
        $invoice = $this->invoiceService->updateInvoice(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice updated successfully'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->invoiceService->deleteInvoice($id);
        return response()->json([
            'message' => 'Invoice deleted successfully'
        ]);
    }

    public function cancelInvoice(Request $request, $id)
    {
        $this->invoiceService->updateInvoiceStatus(
            $id,
            [
                'status' => InvoiceConst::PAID_TO_CANCELLED,
                'last_updated_by' => Auth::user()->uuid
            ]
        );
        return response()->json([
            'message' => 'Invoice cancelled successfully'
        ]);
    }
    public function payInvoice(Request $request): JsonResponse
    {
        $data = $request->all();

        $invoicePrefix = Setting::where('key', 'invoicePrefix')->value('value') ?? 'INV';

        $lastInvoice = Invoice::where('invoice_number', 'not like', '%Draft')
            ->whereRaw("invoice_number ~ '[0-9]'")
            ->orderByRaw("CAST(REGEXP_REPLACE(invoice_number, '[^0-9]', '', 'g') AS INTEGER) DESC")
            ->first();

        $lastNumber = 0;
        if ($lastInvoice && preg_match('/^([A-Z]+)-(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $lastNumber = (int) $matches[2];
        }

        $data['status'] = InvoiceConst::PAID;
        $data['invoice_number'] = "{$invoicePrefix}-" . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        // Update invoice status using the service
        $this->invoiceService->updateInvoiceStatus($request->invoice_id, $data);

        // Retrieve the paid invoice
        $invoice = $this->invoiceService->getInvoiceById($request->invoice_id);

        // Initialize quotation service if not already injected (could be optimized via dependency injection)
        if ($invoice->quotation_id) {
            $this->quotationService = new QuotationService();

            $quotation = $this->quotationService->getQuotationById($invoice->quotation_id);

            // Calculate new amount due after payment
            $newAmountDue = $quotation->amount_due - $invoice->total;

            // Determine new status for the quotation based on remaining due
            // $newStatus = ($quotation->status === 'scheduled' && $newAmountDue <= 0) ? 'Paid' : $quotation->status;

            // Update quotation with new amount_due and status
            $this->quotationService->updateQuotationDueAmount($quotation->id, [
                'amount_due' => $newAmountDue,
            ]);
        }
        NotificationJob::dispatch(InvoiceConst::RULE_FULL_PAYMENT, invoiceRuleNotification($invoice->id), null, loginUserId());

        // Return success response
        return response()->json([
            'message' => 'Invoice paid successfully',
        ]);
    }

    public function downloadPdf($invoiceId)
    {
        $invoice = Invoice::with(onlyInvoiceUserRelation())->findOrFail($invoiceId);
        $settings = Setting::pluck('value', 'key') ?? [];
        $data = [
            'invoice' => $invoice,
            'settings' => $settings,
        ];

        $pdf = Pdf::loadView('pdf.invoicePdf', $data);

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }

    public function invoiceStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::where('id', $request->invoice_id)->first();
            if (!$invoice) return $this->actionFailure('invoice not Found!');
            $oldStatus = $invoice->status;
            $invoice->status = $request->status;
            $invoice->save();
            $this->invoiceService->existTriggerActionSendFile($invoice->id, $request->status, $oldStatus);
            DB::commit();
            return $this->actionSuccess('invoice Status updated successfully.', $invoice);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function invoiceSendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module_name'         => "required|in:" . CommonConst::MODULE_INVOICE,
            'module_id'           => 'required',
            'receiver_id'         => 'required',
            'name'                => 'required',
            'email'               => 'nullable|email',
            'phone'               => 'required',
            'socialPlatform'      => "required|in:" . implode(',', CommonConst::SEND_NOTIFICATION_PLAT_FORM),
            'sendAttachmentType'  => "required|in:" . implode(',', CommonConst::SEND_ATTACHMENT_TYPE_LIST),
            'message'             => 'required|string',
            'file'                => 'nullable|file|mimes:pdf,doc,docx,jpeg,jpg,png',
        ]);

        if ($validator->fails()) {
            return $this->validationFailed(true, $validator->errors());
        }

        set_time_limit(0);

        $user = Auth::user();
        $sender_id = $user->uuid;

        $invoice = Invoice::with(onlyInvoiceUserRelation())->find($request->module_id);

        # Handle attachments
        $directory = "email_attachments";
        $fileUrl = $extension = $fileCaption = $filename = "";

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }
        if ($request->sendAttachmentType === CommonConst::SELECT_FILE && $request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = 'Invoice_' . formattedDateTime() . '_' . $originalName;

                // custom path
                $path = $directory . '/' . $filename;

                // Save file to storage/app/public/{directory}
                Storage::disk('public')->putFileAs($directory, $file, $filename);

                // Generate full URL
                $fileUrl = url('storage/' . $path);

                // Determine type for preview
                $mime = $file->getMimeType();
                $fileCaption = $originalName;
                $extension = Str::startsWith($mime, 'image/') ? 'Image' : 'Document';

                // Call your method to store/send attachment
                $this->createAttachment(
                    $request->receiver_id,
                    $fileCaption,
                    $path,
                    $mime,
                    $request->socialPlatform,
                    $sender_id,
                    $invoice->id
                );
            }
        } elseif ($request->sendAttachmentType == CommonConst::AUTO_SEND_FILE) {
            $settings = Setting::pluck('value', 'key') ?? [];
            $data = [
                'invoice' => $invoice,
                'settings' => $settings,
            ];
            $pdf = Pdf::loadView('pdf.invoicePdf', $data);
            $filename = 'Invoice_' . formattedDateTime() . '.pdf';
            $path = $directory . '/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            $fileUrl = url('storage/' . $path);
            $fileCaption = $invoice && $invoice->invoice_number ? 'Invoice #' . $invoice->invoice_number  : 'Invoice Attachment';
            $mime        = 'application/pdf';
            $extension = 'Document';

            # Create attachment
            $this->createAttachment($request->receiver_id, $fileCaption, $path, $mime, $request->socialPlatform, $sender_id, $invoice->id);
        }

        # Export log (only if attachment is present)
        if ($request->sendAttachmentType != CommonConst::NO_ATTACHMENT && $fileUrl) {
            ExportLog::create([
                'name'       => $filename,
                'table_name' => 'invoices',
                'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
                'file_path'  => $fileUrl,
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
        if ($extension != "") {
            $additional_info = [
                'fileUrl'            => $fileUrl,
                'extension'          => $extension,
                'fileCaption'        => $fileCaption,
            ];
        }

        $receiver_contact = $request->socialPlatform === CommonConst::WHATSAPP ? $request->phone : $request->email;

        # Create initial log
        $log = NotificationLog::create([
            'receiver_contact'    => $receiver_contact,
            'subject'             => "Quotation has been successfully created and sent to the user: " . $request->name,
            'content'             => $request->message,
            'priority'            => CommonConst::HIGH,
            'status'              => CommonConst::PENDING,
            'notification_type_id' => null,
            'receiver_id'         => null,
            'section_type'        => $request->socialPlatform,
            'is_notification'     => false,
            'email_body'          => json_encode($email_body),
            'additional_info'     => json_encode($additional_info),
            'sender_id'           => $sender_id,
            'module_id'           => $request->module_id,
        ]);

        $message = '';
        if ($request->socialPlatform == CommonConst::WHATSAPP) {
            $plainTextMessage = str_replace(['<br>', '<br/>', '<br />'], "\n", $request->message);
            $fileUrl = $fileUrl != '' ? asset('storage/' . $path) : '';
            if ($fileUrl != '') {
                $fileUrl1 = asset('storage/' . ltrim(str_replace(url('storage'), '', $fileUrl), '/'));
            }
            $userName = trim($request->name);
            $response = (new WhatsAppService())->sendMediaMessage($userName, $receiver_contact, $plainTextMessage, $fileUrl, $fileCaption, $extension);
            $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
            $log->message = $response->message ?? '';
            $log->save();
            $message = $response->status ? 'WhatsApp message sent successfully.' : $response->message;
        } else if ($request->socialPlatform == CommonConst::EMAIL) {
            try {
                # Mail content
                $mailData = [
                    'subject'                => "Invoice Created Sent Info User " . $request->name,
                    'receiver_contact'       => $receiver_contact,
                    'mail_log_id'            => $log->id,
                    'attachment_file_url'    => $fileUrl,
                    'attachment_path'         => $fileUrl != "" ? 'public/' . $path : null,
                    'attachment_original_name' => $filename != "" ? $filename : null,
                    'hidden_pre_header'      => "This is your Invoice summary. Please check the attachment for full details.",
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


        if ($log->status == CommonConst::SUCCESS) {
            if ($invoice->status == InvoiceConst::CREATED) {
                $invoice->status =  InvoiceConst::SENT;
                $invoice->save();
            }
        }

        # Fire notification event
        try {
            event(new NotificationMessage($log->subject, $sender_id, $request->socialPlatform));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
        }

        return $log->status == CommonConst::SUCCESS ? $this->actionSuccess($message, $log) : $this->actionFailure($message, $log);
    }

    private function createAttachment($receiver_id, $fileCaption, $path, $mime, $socialPlatform, $senderId, $invoiceId)
    {
        $userType  = Client::where('id', $receiver_id)->first();
        if ($userType) {
            ClientAttachment::create([
                'client_id'    => $receiver_id,
                'invoice_id'   => $invoiceId,
                'file_name'    => $fileCaption,
                'file_path'    => $path,
                'mime_type'    => $mime,
                'sent_via'     => $socialPlatform,
                'uploaded_by'  => $senderId,
            ]);
        } else {
            LeadAttachment::create([
                'lead_id'      => $receiver_id,
                'invoice_id'   => $invoiceId,
                'file_name'    => $fileCaption,
                'file_path'    => $path,
                'mime_type'    => $mime,
                'sent_via'     => $socialPlatform,
                'uploaded_by'  => $senderId,
            ]);
        }
    }
}
