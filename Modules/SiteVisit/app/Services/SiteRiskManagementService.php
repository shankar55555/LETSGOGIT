<?php

namespace Modules\SiteVisit\Services;

use Modules\SiteVisit\Models\SiteRiskManagement;
use Modules\SiteVisit\Models\SiteVisit;

class SiteRiskManagementService
{

    public function createOrUpdate(array $data, string $siteVisitId): SiteRiskManagement
    {
        return SiteRiskManagement::updateOrCreate(
            ['site_visit_id' => $siteVisitId],
            $data
        );
    }

    public function create(array $data, string $siteVisitId): SiteRiskManagement
    {
        $riskManagement = $this->createOrUpdate($data, $siteVisitId);
        SiteVisit::where('id', $siteVisitId)->update(['status' => $data['status']]);
        return  $riskManagement;
    }

    public function show(string $siteVisitId): array
    {
        return SiteRiskManagement::getBySiteVisitId($siteVisitId);
    }
}
