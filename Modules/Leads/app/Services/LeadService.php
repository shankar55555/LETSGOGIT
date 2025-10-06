<?php

namespace Modules\Leads\Services;

use App\Constants\CommonConst;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Clients\Constants\ClientConst;
use Modules\Leads\Models\Lead;
use Modules\Clients\Models\Client;
use Modules\SiteVisit\Models\SiteVisit;
use Modules\FollowUp\Models\FollowUp;
use Modules\Invoices\Models\Invoice;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Models\SiteRiskManagement;
use Modules\SiteVisit\Models\SiteRiskMedia;

class LeadService
{
    public function getPaginatedLeads(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $status = null,
        ?string $search = null,
        ?string $assignedUser = null,
        ?string $clientId = null,
        ?string $quotationId = null,
        ?string $contractId = null,
        ?string $invoiceId = null,
        ?string $createdBy = null,
        ?string $lastUpdatedBy = null,
        ?string $user_view_id = null,
        ?array $status_list = [],
    ): LengthAwarePaginator {
        $query = Lead::query()->when($withTrashed, fn($q) => $q->withTrashed())->whereNot('status', 'convert_to_client');
        # ✅ Apply custom filtering from the helper
        $query = applyFilteringUser_new($query, ['created_by', 'assigned_user'], $user_view_id);
        return $query->when($status, fn($q) => $q->where('status', $status))
            ->when(!empty($status_list), fn($q) => $q->whereIn('status', $status_list))
            ->when($search, fn($q) => $q->search($search))
            ->when($assignedUser, fn($q) => $q->where('assigned_user', $assignedUser))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($quotationId, fn($q) => $q->where('quotation_id', $quotationId))
            ->when($contractId, fn($q) => $q->where('contract_id', $contractId))
            ->when($invoiceId, fn($q) => $q->where('invoice_id', $invoiceId))
            ->when($createdBy, fn($q) => $q->where('created_by', $createdBy))
            ->when($lastUpdatedBy, fn($q) => $q->where('last_updated_by', $lastUpdatedBy))
            ->with(['creator', 'updater', 'assignedUser', 'status_info', 'siteVisits', 'followups', 'quotations'])
            ->latest()
            ->paginate($perPage);
    }

    public function getPaginatedDashboardLeads(int $perPage = 15, bool $withTrashed = false, ?string $status = null, ?string $search = null, ?string $start_date = null, ?string $end_date = null, ?array $status_list = [],): LengthAwarePaginator
    {
        $query = Lead::query()->when($withTrashed, fn($q) => $q->withTrashed())->whereNot('status', 'convert_to_client');

        # ✅ Apply custom filtering from the helper
        $query = applyFilteringUser_new($query, ['created_by', 'assigned_user']);

        # ✅ Apply default date range if missing
        $start = $start_date ?? now()->toDateString();
        $end = $end_date ?? now()->toDateString();

        $query->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end);
        return $query->when($status, fn($q) => $q->where('status', $status))
            ->when(!empty($status_list), fn($q) => $q->whereIn('status', $status_list))
            ->when($search, fn($q) => $q->search($search))
            ->with(['creator', 'updater', 'assignedUser', 'status_info', 'siteVisits', 'followups', 'quotations'])
            ->latest()
            ->paginate($perPage);
    }

    public function getLeadById(string $id): Lead
    {
        return Lead::with(['assignedUser', 'creator', 'updater', 'siteVisits', 'followups', 'quotations'])
            ->where('client_id', null)
            ->where('id', $id)->first() ?? null;
    }

    public function createLead(array $data): Lead
    {
        return Lead::create($data);
    }

    public function updateLead(string $id, array $data): Lead
    {
        $lead = $this->getLeadById($id);
        $lead->update($data);
        return $lead->fresh();
    }

    public function deleteLead(string $id): void
    {
        $lead = $this->getLeadById($id);
        $lead->delete();
        $s_ids = SiteVisit::where('lead_id', $id)->pluck('id');
        $q_ids = Quotation::where('lead_id', $id)->pluck('id');
        FollowUp::where('lead_id', $id)->delete();
        SiteVisit::where('lead_id', $id)->delete();
        Quotation::where('lead_id', $id)->delete();
        Invoice::whereIn('quotation_id', $q_ids)->delete();
        SiteRiskManagement::whereIn('site_visit_id', $s_ids)->delete();
        $medias = SiteRiskMedia::whereIn('site_visit_id', $s_ids)->get();
        foreach ($medias as $key => $media) {
            // Find the media record
            $media = SiteRiskMedia::findOrFail($id);
            // Delete the file from storage
            if (Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
            // Delete the record from database
            $media->delete();
        }
    }

    public function convertToClient($leadId, $method, ?string $login_user_id = null)
    {
        $lead = $this->getLeadById($leadId);
        if ($lead) {
            $client = $this->createClientFromLead($lead, $method);
            $this->updateLeadConversionStatus($lead, $client, $login_user_id);
            $quotation = $this->updateRelatedRecords($leadId, $client);
            return [
                'client' => $client,
                'lead' => $lead->fresh(),
                'message' => "Lead convert to client Successfully"
            ];
        }
        return [
            'lead' => $lead,
            'message' => "Lead Already to convert to client"
        ];
    }

    protected function createClientFromLead(Lead $lead, $method): Client
    {
        $client = Client::create(
            $lead->only((new Client())->getFillable())
        );

        $client->update([
            'lead_id' => $lead->id,
            'status' => CommonConst::ACTIVE,
            'converted_by' => $method,
        ]);

        return $client;
    }

    protected function updateLeadConversionStatus(Lead $lead, Client $client, ?string $login_user_id = null): void
    {
        $lead->update([
            'client_id' => $client->id,
            'last_updated_by' => $login_user_id ?? loginUserId() ?? adminUserId()[0],
        ]);
    }

    protected function updateRelatedRecords($leadId, Client $client): void
    {
        $models = [
            SiteVisit::class,
            FollowUp::class,
            Quotation::class,
        ];

        foreach ($models as $model) {
            $model::where('lead_id', $leadId)->update(['client_id' => $client->id]);
        }
        try {
            $q_ids = Quotation::where('lead_id', $leadId)->pluck('id')->toArray();
            Invoice::whereIn('quotation_id', $q_ids)->update(['client_id' => $client->id]);
        } catch (\Throwable $th) {
        }
    }
}
