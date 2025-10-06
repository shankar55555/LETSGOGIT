<?php

namespace Modules\SiteVisit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Services\SiteRiskManagementService;
use Modules\SiteVisit\Http\Requests\SiteRiskManagementRequest;
use Modules\SiteVisit\Models\SiteRiskMedia;
use Modules\SiteVisit\Transformers\SiteRiskMediaResource;

class SiteRiskManagementController extends Controller
{
    protected SiteRiskManagementService $siteRiskManagementService;

    public function __construct(SiteRiskManagementService $siteRiskManagementService)
    {
        $this->siteRiskManagementService = $siteRiskManagementService;
    }

    /**
     * Store or update site risk management data
     */
    public function store(SiteRiskManagementRequest $request, string $siteVisitId)
    {
        $riskManagement = $this->siteRiskManagementService->create(
            array_merge($request->validated(), ['created_by' => Auth::user()->uuid]),
            $siteVisitId
        );

        NotificationJob::dispatch(SiteVisitConst::RULE_SITE_VISIT_COMPLETED, SiteVisitRuleNotification($riskManagement->site_visit_id), null, loginUserId());

        return $this->actionSuccess(
            'Data saved successfully',
            $riskManagement,
        );
    }

    /**
     * Get a specific site risk management record
     */
    public function show(string $siteVisitId)
    {
        $riskManagement = $this->siteRiskManagementService->show($siteVisitId);
        return $this->actionSuccess(
            'Data fetch successfully',
            $riskManagement,
        );
    }

    /**
     * Get a specific site risk management record
     */
    public function getSiteRiskMedia(string $siteVisitId)
    {
        return $this->actionSuccess(
            'Data fetch successfully',
            SiteRiskMediaResource::collection(SiteRiskMedia::where('site_visit_id', $siteVisitId)->get())
        );
    }

    public function uploadSiteRiskMedia(Request $request, string $siteVisitId)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480', // 20MB max
        ]);
        // $savedFiles = [];
        foreach ($request->file('files') as $file) {
            $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
            $directory = $siteVisitId . "/" . $type;
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }
            $path = $file->store($directory, 'public'); // Store in storage/app/$directory
            SiteRiskMedia::create([
                'site_visit_id' => $siteVisitId,
                'type' => $type,
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
            ]);
        }

        return $this->actionSuccess("File Uploaded Successfully", SiteRiskMediaResource::collection(SiteRiskMedia::where('site_visit_id', $siteVisitId)->get()));
    }

    public function deleteSiteRiskMedia(string $siteVisitId, string $id)
    {
        // Find the media record
        $media = SiteRiskMedia::findOrFail($id);

        // Delete the file from storage
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        // Delete the record from database
        $media->delete();
        return $this->actionSuccess("File deleted successfully", SiteRiskMediaResource::collection(SiteRiskMedia::where('site_visit_id', $siteVisitId)->get()));
    }
}
