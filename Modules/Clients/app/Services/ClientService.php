<?php

namespace Modules\Clients\Services;

use App\Constants\CommonConst;
use Modules\Clients\Models\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\FollowUp\Models\FollowUp;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\Lead;
use Modules\Leads\Services\LeadService;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Models\SiteRiskManagement;
use Modules\SiteVisit\Models\SiteRiskMedia;
use Modules\SiteVisit\Models\SiteVisit;

class ClientService
{
    public function getActiveClientOptions()
    {
        return Client::select('id', 'name', 'email', "phone")
            ->where('status', '!=', CommonConst::IN_ACTIVE)
            ->get();
    }

    public function getClientById(string $id)
    {
        return Client::with(['creator', 'updater', 'assignedUser', 'siteVisits', 'followups', 'quotations'])->where('id', $id)->first() ?? null;
    }

    public function getPaginatedClients(
        bool $withTrashed = false,
        ?string $status = null,
        ?string $search = null,
        array $statusList = [],
        int $perPage = 15,
        ?string $user_view_id = null
    ): LengthAwarePaginator {
        $query = Client::query();
        $query = applyFilteringUser_new($query, ['created_by', 'assigned_user'], $user_view_id)
            ->when($withTrashed, fn($q) => $q->withTrashed())
            ->when($status, fn($q) => $q->filterByStatus($status))
            ->when($search, fn($q) => $q->search($search))
            ->when(count($statusList) > 0, fn($q) => $q->whereIn('status', $statusList))
            ->with(['creator', 'updater', 'assignedUser', 'siteVisits', 'followups', 'quotations'])
            ->latest();

        return $query->paginate($perPage);
    }

    public function createClient(array $data, string $createdBy): Client
    {
        return Client::createWithAttributes([
            ...$data,
            'created_by' => $createdBy
        ]);
    }

    public function loadClientRelations(Client $client): Client
    {
        return $client->loadRelations();
    }

    public function updateClient(Client $client, array $data, string $updatedBy): Client
    {
        $client->updateWithAttributes([
            ...$data,
            'last_updated_by' => $updatedBy
        ]);

        return $client;
    }

    public function softDeleteClient(Client $client): array
    {
        $id = $client->id;
        if ($client->trashed()) {
            throw new \Exception(__('Client is already deleted'), 409);
        }

        $client->delete();

        $s_ids = SiteVisit::where('client_id', $id)->pluck('id');
        $q_ids = Quotation::where('client_id', $id)->pluck('id');
        FollowUp::where('client_id', $id)->delete();
        SiteVisit::where('client_id', $id)->delete();
        Quotation::where('client_id', $id)->delete();
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

        return [
            'deleted_at' => $client->deleted_at
        ];
    }

    public function restoreClient($id): Client
    {
        $client = Client::withTrashed()->findOrFail($id);

        if (!$client->trashed()) {
            throw new \Exception(__('Client is not deleted'), 409);
        }

        $client->restore();

        return $client;
    }

    public function buildPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function updateClientStatus(Client $client, string $status, string $updatedBy): Client
    {
        $client->updateWithAttributes([
            'status' => $status,
            'last_updated_by' => $updatedBy
        ]);

        return $client->fresh();
    }

    public function convertToLead($clientId, $method, ?string $login_user_id = null)
    {
        # Fetch client including soft-deleted if needed
        $client = $this->getClientById($clientId);
        if (!$client) throw new \Exception("Client not found.");
        if ($client) {
            $updatedBy = $login_user_id ?? loginUserId() ?? adminUserId()[0];
            $leadId = $client->lead_id;
            $clientId = $client->id;

            # Update and soft-delete client
            $client->update(['lead_id' => null, 'converted_by' => $method, 'last_updated_by' => $updatedBy]);

            $lead = null;
            if ($leadId) {
                $lead = Lead::where('id', $leadId)->first();
                if ($lead) {
                    $lead->update([
                        'client_id' => null,
                        'last_updated_by' => $updatedBy,
                        'status' => LeadConst::NO_ACTION,
                    ]);
                }
            } else {
                $lead = Lead::create(
                    collect($client->only((new Lead())->getFillable()))
                        ->except(['client_id'])
                        ->toArray()
                );

                $lead->update([
                    'client_id' => null,
                    'last_updated_by' => $updatedBy,
                    'status' => LeadConst::NO_ACTION,
                ]);

                $leadId = $lead->id;
            }

            # Use previously stored $clientId to update related records
            $this->updateRelatedRecords($clientId, $leadId);

            $client->delete();
            return [
                'lead' => $lead,
                'client' => $client,
                'message' => "Client convert to Lead Successfully"
            ];
        }
    }

    protected function updateRelatedRecords($clientId, $leadId): void
    {
        foreach ([SiteVisit::class, FollowUp::class, Quotation::class] as $model) {
            $model::where('client_id', $clientId)->update(['lead_id' => $leadId]);
        }
    }
}
