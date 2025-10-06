<?php

namespace Modules\SiteVisit\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SiteVisit\Models\SiteVisit;

class SiteVisitResource extends JsonResource
{
    public function toArrayOld($request)
    {
        // Get status data for this contract
        $statusData = SiteVisit::getStatusList($this->status);

        // Get lead and client data
        $leadData = $this->lead();
        $clientData = $this->client();
        $siteRiskManagementData = $this->site_risk();

        return array_merge(parent::toArray($request), [
            'status_data' => $statusData,
            'client_data' => $clientData ? [
                'name' => $clientData->name,
                'phone' => $clientData->phone,
                'email' => $clientData->email,
                'address' => $clientData->address,
            ] : null,
            'lead_data' => $leadData ? [
                'name' => $leadData->name,
                'phone' => $leadData->phone,
                'email' => $leadData->email,
                'address' => $leadData->address,
            ] : null,
            'site_risk_management_data' => $siteRiskManagementData,
        ]);
    }

    public function toArray($request)
    {
        $statusData = SiteVisit::getStatusList($this->status);

        $leadData = $this->lead;
        $clientData = $this->client;
        $siteRiskManagementData = $this->site_risk;

        return array_merge(parent::toArray($request), [
            'status_data' => $statusData,
            'client_data' => $clientData ? [
                'name' => $clientData->name,
                'phone' => $clientData->phone,
                'email' => $clientData->email,
                'address' => $clientData->address,
            ] : null,
            'lead_data' => $leadData ? [
                'name' => $leadData->name,
                'phone' => $leadData->phone,
                'email' => $leadData->email,
                'address' => $leadData->address,
            ] : null,
            'site_risk_management_data' => $siteRiskManagementData,
        ]);
    }
}
