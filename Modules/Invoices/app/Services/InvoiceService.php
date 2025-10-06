<?php

namespace Modules\Invoices\Services;

use App\Constants\CommonConst;
use App\Mail\MailSend;
use App\Models\AdminControlConfig;
use App\Models\ExportLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Modules\Quotations\Models\Quotation;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\AlertAndNotification\Services\WhatsAppService;

class InvoiceService
{
    public function getPaginatedInvoices(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $status = null,
        ?string $clientId = null,
        ?string $contractId = null,
        ?string $quotationId = null,
        ?string $createdBy = null,
        ?string $lastUpdatedBy = null,
        ?string $user_view_id = null,
        ?string $search = null,
        ?string $leadId = null,
    ): LengthAwarePaginator {
        $query = Invoice::query()->when($withTrashed, fn($q) => $q->withTrashed());
        # ✅ Apply custom filtering from the helper
        if (!$clientId && !$quotationId && !$leadId) {
            $query = applyFilteringUser($query, 'created_by', $user_view_id);
        } elseif ($leadId) {
            $qids = Quotation::where('lead_id', $leadId)->pluck('id')->toArray();
            $query->whereIn('quotation_id', $qids);
        }

        return $query->when($status, fn($q) => $q->where('status', $status))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($contractId, fn($q) => $q->where('contract_id', $contractId))
            ->when($quotationId, fn($q) => $q->where('quotation_id', $quotationId))
            ->when($createdBy, fn($q) => $q->where('created_by', $createdBy))
            ->when($lastUpdatedBy, fn($q) => $q->where('last_updated_by', $lastUpdatedBy))
            ->when($search, fn($q) => $q->search($search))
            ->with(onlyInvoiceUserRelation())
            ->with(['creator', 'updater', 'quotationNumber:id,quotation_number', 'contractNumber:id, contract_number'])
            ->latest()
            ->paginate($perPage);
    }

    public function getInvoiceById(string $id): Invoice
    {
        $with = ['creator', 'updater',];
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            $with[] = 'client:id,name,avatar,email,country_code,phone';
        }

        if (Module::has(CommonConst::MODULE_QUOTATION)) {
            $with[] = 'quotation';
            if (Module::has(CommonConst::MODULE_LEAD)) {
                $with[] = 'quotation.leadDetail:id,name,email,country_code,phone';
            }
            if (Module::has(CommonConst::MODULE_CLIENT)) {
                $with[] = 'quotation.clientDetail:id,name,email,country_code,phone';
            }
        }

        return Invoice::with($with)->findOrFail($id);
    }

    public function createInvoice(array $data): Invoice
    {
        // Retrieve invoice prefix from settings, defaulting to 'INV'
        $invoicePrefix = Setting::where('key', 'invoicePrefix')->value('value') ?? 'INV';

        // Generate next invoice number
        $lastInvoice = Invoice::withTrashed()->latest('created_at')->first();
        $lastNumber = 0;

        // Match against the prefix dynamically
        if ($lastInvoice && preg_match("/{$invoicePrefix}-(\d+)/", $lastInvoice->invoice_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        // $data['invoice_number'] = "{$invoicePrefix}-{$nextNumber}";
        $data['status'] = InvoiceConst::DRAFT;
        $data['invoice_number'] = "{$invoicePrefix}-Draft";
        if ($data['items']) {
            $data['status'] = InvoiceConst::CREATED;
        }
        // Calculate totals before creation
        $totals = $this->calculateTotals($data['items'] ?? []);
        $data = array_merge($data, $totals);

        $invoice = Invoice::create($data);
        $this->existTriggerActionSendFile($invoice->id, $invoice->status);
        return $invoice;
    }

    public function updateInvoice(string $id, array $data): Invoice
    {
        $invoice = $this->getInvoiceById($id);
        // Calculate totals before creation
        $totals = $this->calculateTotals($data['items'] ?? []);
        $data = array_merge($data, $totals);
        $oldStatus = $invoice->status;
        $invoice->update($data);
        $this->existTriggerActionSendFile($invoice->id, $invoice->status, $oldStatus);
        return $invoice->fresh();
    }

    public function deleteInvoice(string $id): void
    {
        $invoice = $this->getInvoiceById($id);
        $invoice->delete();
    }

    public function calculateTotals(array $items): array
    {
        $subTotal = collect($items)->sum('subtotal');
        $total = collect($items)->sum('total');
        $discount = collect($items)->sum('discount_amount');
        $tax = collect($items)->sum('tax_amount');


        return [
            'sub_total' => $subTotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total
        ];
    }

    public function updateInvoiceStatus(string $id, array $data): Invoice
    {
        $invoice = $this->getInvoiceById($id);
        $oldStatus = $invoice->status;
        $invoice->update($data);
        $this->existTriggerActionSendFile($invoice->id, $invoice->status, $oldStatus);
        return $invoice->fresh();
    }

    public function existTriggerActionSendFile(string $invoiceId, string $status, ?string $oldStatus = null)
    {
        if ($oldStatus != $status) {
            $RuleCheckHelper = new RuleCheckHelper();
            $RuleCheckHelper->onlyStatusChangeCheckRule(CommonConst::MODULE_INVOICE, $status, [$invoiceId], $oldStatus);
            // $statusInfo = AdminControlConfig::where('status_for', CommonConst::MODULE_INVOICE)->where('slug', $status)->select('id', 'trigger_action', 'send_plat_forms')->first();
            // if ($statusInfo && in_array(InvoiceConst::TRIGGER_SEND_INVOICE, makeAnyIdArrayFormat($statusInfo->trigger_action))) {
            //     $platforms = makeAnyIdArrayFormat($statusInfo->send_plat_forms);
            //     return $this->statusTriggerActionSenFile($invoiceId, $platforms);
            // }
        }
    }

    public function statusTriggerActionSenFile(string $invoiceId, ?array $platForms = []): array
    {
        # Fetch site visit with its assignee
        $invoice = Invoice::with('client')->findOrFail($invoiceId);
        $client = $invoice->client ?? null;

        # If no assignee found, skip sending
        if (!$client) {
            return [['status' => 409, 'message' => 'Client not found.']];
        }

        $results = [];

        foreach ($platForms as $platform) {
            $info = (object)[
                'module_name'        => CommonConst::MODULE_INVOICE,
                'module_id'          => $invoiceId,
                'receiver_id'        => $client->id,
                'name'               => $client->name ?? 'No Name',
                'email'              => $client->email ?? '',
                'receiver_column'    => "invoice_id",
                'phone'              => $client->phone ?? '',
                'socialPlatform'     => $platform,
                'sendAttachmentType' => CommonConst::AUTO_SEND_FILE,
                'message'            => "Auto send file triggered by status update",
                'sender_id'          => Auth::user()->uuid ?? null,
            ];

            $results[] = $this->invoiceSendMessage($info);
        }

        return $results;
    }

    public function invoiceSendMessage($info)
    {
        $directory = 'invoices';
        Storage::disk('public')->makeDirectory($directory, 0755, true);
        $fileUrl = $fileCaption = $extension = $filename = $path = '';
        # Attachment processing
        if ($info->sendAttachmentType == CommonConst::SELECT_FILE && $info->hasFile('file')) {
            $file = $info->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = 'invoice_' . formattedDateTime() . '_' . $originalName;
            $path = "$directory/$filename";
            Storage::disk('public')->putFileAs($directory, $file, $filename);

            $fileUrl = url("storage/$path");
            $fileCaption = $originalName;
            $mime = $file->getMimeType();
            $extension = Str::startsWith($mime, 'image/') ? 'Image' : 'Document';
        } elseif ($info->sendAttachmentType == CommonConst::AUTO_SEND_FILE) {
            $invoice = Invoice::with(onlyInvoiceUserRelation())->findOrFail($info->module_id);
            $settings = Setting::pluck('value', 'key') ?? [];
            $data = ['invoice' => $invoice, 'settings' => $settings];
            $pdf = Pdf::loadView('pdf.invoicePdf', $data);
            $filename = 'invoice_' . formattedDateTime() . '.pdf';
            $path = "$directory/$filename";
            Storage::disk('public')->put($path, $pdf->output());

            $fileUrl = url("storage/$path");
            $fileCaption = 'Send Invoice Pdf';
            $extension = 'Document';
        }

        # Log the exported file
        if ($info->sendAttachmentType != CommonConst::NO_ATTACHMENT && $fileUrl) {
            ExportLog::create([
                'name'       => $filename,
                'table_name' => 'invoices',
                'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
                'file_path'  => $fileUrl,
                'status'     => CommonConst::SUCCESS,
                'created_by' => $info->sender_id,
            ]);
        }

        # Build email body and additional info
        $receiverColumn = $info->receiver_column ?? 'receiver_id';

        $emailBody = [
            'module_name'        => $info->module_name,
            'module_id'          => $info->module_id,
            $receiverColumn      => $info->receiver_id,
            'name'               => $info->name,
            'email'              => $info->email,
            'phone'              => $info->phone,
            'socialPlatform'     => $info->socialPlatform,
            'sendAttachmentType' => $info->sendAttachmentType,
            'message'            => $info->message,
        ];

        $additionalInfo = $fileUrl ? ['fileUrl' => $fileUrl, 'extension' => $extension, 'fileCaption' => $fileCaption] : [];

        $receiverContact = $info->socialPlatform === CommonConst::WHATSAPP ? $info->phone : $info->email;

        # Notification log
        $log = NotificationLog::create([
            'receiver_contact'    => $receiverContact,
            'subject'             => "Invoice Sent Info User " . $info->name,
            'content'             => $info->message,
            'priority'            => CommonConst::HIGH,
            'status'              => CommonConst::PENDING,
            'notification_type_id' => null,
            'receiver_id'         => null,
            'section_type'        => $info->socialPlatform,
            'is_notification'     => false,
            'email_body'          => json_encode($emailBody),
            'additional_info'     => json_encode($additionalInfo),
            'sender_id'           => $info->sender_id,
            'module_id'           => $info->module_id,
        ]);

        $message = '';

        # Sending message logic
        if ($info->socialPlatform === CommonConst::WHATSAPP) {
            $plainText = str_replace(['<br>', '<br/>', '<br />'], "\n", $info->message);
            $response = (new WhatsAppService())->sendMediaMessage(
                trim($info->name),
                $receiverContact,
                $plainText,
                $fileUrl,
                $fileCaption,
                $extension
            );
            $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
            $log->message = $response->message ?? '';
            $message = $response->status ? 'WhatsApp message sent successfully.' : ($response->message ?? 'Failed to send WhatsApp message.');
        } elseif ($info->socialPlatform === CommonConst::EMAIL) {
            try {
                $mailData = [
                    'subject'                => "Invoice Sent Info User " . $info->name,
                    'receiver_contact'       => $receiverContact,
                    'mail_log_id'            => $log->id,
                    'attachment_path'         => $fileUrl ? "public/$path" : null,
                    'attachment_original_name' => $filename ?: null,
                    'hidden_pre_header'      => "This is your Invoice summary. Please check the attachment for full details.",
                    'content'                => $info->message,
                ];
                Mail::to($receiverContact)->send(new MailSend($mailData));
                $log->status = CommonConst::SUCCESS;
                $message = 'Email sent successfully.';
            } catch (\Exception $e) {
                createExceptionError($e, "InvoiceService", __FUNCTION__);
                $log->status = CommonConst::FAILED;
                $log->message = $e->getMessage();
                $message = $e->getMessage();
            }
        }

        $log->save();

        # Fire notification event
        try {
            event(new NotificationMessage($log->subject, $info->sender_id, $info->socialPlatform));
        } catch (\Exception $e) {
            createExceptionError($e, "InvoiceService", __FUNCTION__);
        }

        return [
            "status"             => $log->status == CommonConst::SUCCESS ? 200 : 409,
            "message"            => $message,
            "notification_log_id" => $log->id,
        ];
    }

    // C:\Projects\nobal-solar\Modules\Invoices\app\Services\InvoiceService.php
    function generateScheduling($quotation, $data)
    {
        Invoice::where('quotation_id', $quotation->id)->whereNot('status', InvoiceConst::PAID_TO_CANCELLED)->delete();
        $invoicePrefix = Setting::where('key', 'invoicePrefix')->value('value') ?? 'INV';
        $recurring = $data['recurring_invoice'] ?? 'yes';
        // // Case 1: Full payment
        if ($recurring === 'no') {
            $invoice =  Invoice::create([
                'invoice_number' => "{$invoicePrefix}-Draft",
                'title' => $quotation->title ?? '',
                'description' => $data['note'] ?? $quotation?->description ?? '',
                'items' => $quotation->items ?? [],
                'amount_paid' => 0,
                'sub_total' => $quotation->sub_total,
                'tax' => $quotation->tax,
                'discount' => $quotation->discount,
                'total' => $quotation->total,
                'status' => InvoiceConst::CREATED,
                'due_date' => now(),
                'quotation_id' => $quotation->id,
                'client_id' => $quotation->client_id,
                'created_by' => Auth::user()->uuid,
            ]);
            # Send Notification:

            # Invoice created
            NotificationJob::dispatch(InvoiceConst::RULE_INVOICE_CREATED, invoiceRuleNotification($invoice->id), null, loginUserId());
            return;
        }

        // Sanitize and prepare values
        $months = max(1, (int) filter_var($data['payment_duration'], FILTER_SANITIZE_NUMBER_INT));
        // Use values from the quotation model
        $subTotal = $quotation->sub_total ?? 0;
        $tax = $quotation->tax ?? 0;
        $discount = $quotation->discount ?? 0;
        $total = $quotation->total ?? 0;

        // Base installment values
        $baseAmount = floor($subTotal / $months);
        $taxPerInstallment = round($tax / $months, 2);
        $discountPerInstallment = round($discount / $months, 2);
        $installmentTotal = round($total / $months, 2);

        // // Case 2: Partial payment
        // if ($amountReceived > 0 && $amountReceived < $total) {
        //     invoiceCreating($quotation, $data, $baseAmount, $taxPerInstallment, $discountPerInstallment, $installmentTotal);
        // }

        // Case 3: Create scheduled invoices
        $startDate = now();
        Log::info('Start Date: ' . $startDate);

        for ($i = 1; $i <= $months; $i++) {
            $dueDate = (clone $startDate)->addMonthsNoOverflow($i);

            Log::info("Installment {$i} due date: " . $dueDate->format('d-m-Y'));

            $invoice = Invoice::create([
                'invoice_number' => "{$invoicePrefix}-Draft",
                'title' => $quotation->title ?? '',
                'description' => $data['note'] ?? $quotation->description ?? '',
                'items' => $quotation->items ?? [],
                'amount_paid' => 0,
                'sub_total' => $baseAmount,
                'tax' => $taxPerInstallment,
                'discount' => $discountPerInstallment,
                'total' => $installmentTotal,
                'status' => InvoiceConst::CREATED,
                'due_date' => $dueDate->format('Y-m-d'),
                'quotation_id' => $quotation->id,
                'client_id' => $quotation->client_id,
                'created_by' => Auth::user()->uuid,
            ]);

            NotificationJob::dispatch(
                InvoiceConst::RULE_INVOICE_CREATED,
                invoiceRuleNotification($invoice->id),
                null,
                loginUserId()
            );
        }
    }
}
