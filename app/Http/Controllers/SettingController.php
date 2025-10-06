<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AdminControlConfig;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    const CONTROLLER_NAME = 'Setting Controller';

    # Setting List and Save Function

    public function index(Request $request)
    {
        try {
            if ($request->keys) {
                $settings = Setting::whereIn('key', $request->keys)->pluck('value', 'key') ?? [];
            } else {
                $settings = Setting::pluck('value', 'key') ?? [];
            }
            return $this->actionSuccess('Setting list get successfully',  $settings);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function termsUpdate(Request $request)
    {
        try {
            $settings = $request->all(); // Keep everything user sends
            $userId = Auth::user()->uuid;

            if (empty($settings)) {
                return response()->json(['message' => 'No settings provided'], 400);
            }

            foreach ($settings as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'updated_by' => $userId,
                        'created_by' => $userId
                    ]
                );
            }

            return response()->json(['message' => 'Settings updated successfully'], 200);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $is_delete = $request->boolean('is_delete');
            $settings = $request->except(['image', 'is_delete', '_method', 'pwa_logo_192', 'pwa_logo_512']);
            $userId = Auth::user()->uuid;

            foreach ($settings as $key => $value) {
                $existingSetting = Setting::where('key', $key)->first();

                if ($existingSetting) {
                    $existingSetting->value = $value;
                    $existingSetting->updated_by = $userId;
                    $existingSetting->save();
                } else {
                    Setting::create([
                        'key' => $key,
                        'value' => $value,
                        'created_by' => $userId,
                    ]);
                }
            }

            // Delete logos if requested
            if ($is_delete) {
                $logos = ['company_logo', 'pwa_logo_192', 'pwa_logo_512'];
                foreach ($logos as $logoKey) {
                    $existLogo = Setting::where('key', $logoKey)->first();

                    if ($existLogo && $existLogo->value) {
                        $relativePath = Str::after($existLogo->value, '/storage/');
                        if (Storage::disk('public')->exists($relativePath)) {
                            Storage::disk('public')->delete($relativePath);
                        }
                        $existLogo->value = null;
                        $existLogo->updated_by = $userId;
                        $existLogo->save();
                    }
                }
            }

            // Handle file upload for company logo
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $directory = 'companyLogs';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0755, true);
                }

                $path = $request->file('image')->store($directory, 'public');
                $image_url = url('storage/' . $path);

                $logoKey = 'company_logo';
                $existingLogo = Setting::where('key', $logoKey)->first();

                if ($existingLogo) {
                    $existingLogo->value = $image_url;
                    $existingLogo->updated_by = $userId;
                    $existingLogo->save();
                } else {
                    Setting::create([
                        'key' => $logoKey,
                        'value' => $image_url,
                        'created_by' => $userId,
                    ]);
                }
            }

            // Handle file upload for PWA logo 192x192
            if ($request->hasFile('pwa_logo_192') && $request->file('pwa_logo_192')->isValid()) {
                $file = $request->file('pwa_logo_192');
                // Validate file type
                if ($file->getMimeType() !== 'image/png') {
                    throw new \Exception('PWA logo (192x192) must be a PNG image.');
                }
                // Validate dimensions
                $imageInfo = getimagesize($file->getRealPath());
                if (!$imageInfo || $imageInfo[0] !== 192 || $imageInfo[1] !== 192) {
                    throw new \Exception('PWA logo (192x192) must be 192x192 pixels.');
                }

                $directory = 'pwaLogos';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0755, true);
                }

                $path = $file->store($directory, 'public');
                $image_url = url('storage/' . $path);

                $logoKey = 'pwa_logo_192';
                $existingLogo = Setting::where('key', $logoKey)->first();

                if ($existingLogo) {
                    $existingLogo->value = $image_url;
                    $existingLogo->updated_by = $userId;
                    $existingLogo->save();
                } else {
                    Setting::create([
                        'key' => $logoKey,
                        'value' => $image_url,
                        'created_by' => $userId,
                    ]);
                }
            }

            // Handle file upload for PWA logo 512x512
            if ($request->hasFile('pwa_logo_512') && $request->file('pwa_logo_512')->isValid()) {
                $file = $request->file('pwa_logo_512');
                // Validate file type
                if ($file->getMimeType() !== 'image/png') {
                    throw new \Exception('PWA logo (512x512) must be a PNG image.');
                }
                // Validate dimensions
                $imageInfo = getimagesize($file->getRealPath());
                if (!$imageInfo || $imageInfo[0] !== 512 || $imageInfo[1] !== 512) {
                    throw new \Exception('PWA logo (512x512) must be 512x512 pixels.');
                }

                $directory = 'pwaLogos';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0755, true);
                }

                $path = $file->store($directory, 'public');
                $image_url = url('storage/' . $path);

                $logoKey = 'pwa_logo_512';
                $existingLogo = Setting::where('key', $logoKey)->first();

                if ($existingLogo) {
                    $existingLogo->value = $image_url;
                    $existingLogo->updated_by = $userId;
                    $existingLogo->save();
                } else {
                    Setting::create([
                        'key' => $logoKey,
                        'value' => $image_url,
                        'created_by' => $userId,
                    ]);
                }
            }

            DB::commit();
            return $this->actionSuccess('Settings updated successfully!', Setting::pluck('value', 'key'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->actionFailure($e->getMessage());
        }
    }

    // public function update( Request $request )
    // {
    //     DB::beginTransaction();

    //     try {
    //         $is_delete = $request->boolean( 'is_delete' );
    //         $settings = $request->except( [ 'image', 'is_delete', '_method' ] );
    //         $userId = Auth::user()->uuid;

    //         foreach ( $settings as $key => $value ) {
    //             $existingSetting = Setting::where( 'key', $key )->first();

    //             if ( $existingSetting ) {
    //                 $existingSetting->value = $value;
    //                 $existingSetting->updated_by = $userId;
    //                 $existingSetting->save();
    //             } else {
    //                 Setting::create( [
    //                     'key' => $key,
    //                     'value' => $value,
    //                     'created_by' => $userId,
    // ] );
    //             }
    //         }

    //         // Delete logo if requested
    //         if ( $is_delete ) {
    //             $existLogo = Setting::where( 'key', 'company_logo' )->first();

    //             if ( $existLogo && $existLogo->value ) {
    //                 $relativePath = Str::after( $existLogo->value, '/storage/' );
    //                 if ( Storage::disk( 'public' )->exists( $relativePath ) ) {
    //                     Storage::disk( 'public' )->delete( $relativePath );
    //                 }
    //                 $existLogo->value = null;
    //                 $existLogo->updated_by = $userId;
    //                 $existLogo->save();
    //             }
    //         }

    //         // Handle file upload
    //         if ( $request->hasFile( 'image' ) && $request->file( 'image' )->isValid() ) {
    //             $directory = 'companyLogs';
    //             if ( !Storage::disk( 'public' )->exists( $directory ) ) {
    //                 Storage::disk( 'public' )->makeDirectory( $directory, 0755, true );
    //             }

    //             $path = $request->file( 'image' )->store( $directory, 'public' );
    //             $image_url = url( 'storage/' . $path );

    //             $logoKey = 'company_logo';
    //             $existingLogo = Setting::where( 'key', $logoKey )->first();

    //             if ( $existingLogo ) {
    //                 $existingLogo->value = $image_url;
    //                 $existingLogo->updated_by = $userId;
    //                 $existingLogo->save();
    //             } else {
    //                 Setting::create( [
    //                     'key' => $logoKey,
    //                     'value' => $image_url,
    //                     'created_by' => $userId,
    // ] );
    //             }
    //         }

    //         DB::commit();
    //         return $this->actionSuccess( 'Settings updated successfully!', Setting::pluck( 'value', 'key' ) );
    //     } catch ( \Exception $e ) {
    //         DB::rollBack();
    //         return $this->actionFailure( $e->getMessage() );
    //     }
    // }

    # Status list Function

    public function pageList(Request $request)
    {
        try {
            $pages = AdminControlConfig::pluck('status_for')->unique()->values()->toArray();

            // Map 'status_for' values to their corresponding constant class
            $classMap = [
                'Leads'      => ['class' => \Modules\Leads\Constants\LeadConst::class,      'const_name' => 'LEAD_TRIGGER_ACTION_LIST'],
                // 'Clients'    => ['class' => \Modules\Clients\Constants\ClientConst::class,  'const_name' => 'CLIENT_TRIGGER_ACTION_LIST'],
                'FollowUp'   => ['class' => \Modules\FollowUp\Constants\FollowUpConst::class, 'const_name' => 'FOLLOW_UP_TRIGGER_ACTION_LIST'],
                'SiteVisit'  => ['class' => \Modules\SiteVisit\Constants\SiteVisitConst::class, 'const_name' => 'SITE_VISIT_TRIGGER_ACTION_LIST'],
                'Quotations' => ['class' => \Modules\Quotations\Constants\QuotationConst::class, 'const_name' => 'QUOTATION_TRIGGER_ACTION_LIST'],
                'Invoices'   => ['class' => \Modules\Invoices\Constants\InvoiceConst::class, 'const_name' => 'INVOICE_TRIGGER_ACTION_LIST'],
            ];

            $triggerActions = [];

            foreach ($pages as $page) {
                if (isset($classMap[$page])) {
                    $classInfo = $classMap[$page];
                    $className = $classInfo['class'];
                    $constName = $classInfo['const_name'];

                    if (defined("$className::$constName")) {
                        $triggerActions[$page] = constant("$className::$constName");
                    } else {
                        $triggerActions[$page] = [];
                    }
                } else {
                    $triggerActions[$page] = [];
                }
            }

            $data = ['data' => $pages, 'triggers' => $triggerActions];

            return $this->actionSuccess('Page list and triggers fetched successfully', $data);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }

        // try {
        //     $pages = AdminControlConfig::pluck( 'status_for' )->unique()->values()->toArray();
        //     return $this->actionSuccess( 'Page list get successfully',  $pages );
        // } catch ( \Exception $e ) {
        //     createExceptionError( $e, self::CONTROLLER_NAME, __FUNCTION__ );
        //     return $this->actionFailure( $e->getMessage() );
        // }
    }

    public function pageStatusList(Request $request)
    {
        try {
            $type = $request->type ? $request->type : 'All';
            $query = AdminControlConfig::query();

            if ($type != 'All') $query->where('status_for', $type);

            # Sort results
            $query->orderBy('position', 'asc');

            # Pagination
            $perPage = $request->input('per_page', 25);
            $list = $query->paginate($perPage);

            return $this->actionSuccess('Status list get successfully',  $list);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function pageStatusCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status_text'         => 'required|string|max:255',
            'status_color'        => 'required|string',
            'position'            => 'required|integer|min:0',
            'status'              => 'required|boolean',
            'status_for'          => 'required|array|min:1',
            'status_for.*'        => 'string|distinct',
            'invoice_footer_text' => 'nullable|string',
            'contract_footer_text' => 'nullable|string',
            'trigger_action' => 'nullable|array',
            'send_plat_forms' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        // Check uniqueness of status_text for each status_for value
        foreach ($request->status_for as $statusFor) {
            $exists = AdminControlConfig::where('status_text', $request->status_text)->where('status_for', $statusFor)->exists();

            if ($exists) {
                return $this->actionFailure("Status '{$request->status_text}' already exists for '{$statusFor}'.");
            }
        }

        DB::beginTransaction();
        try {

            foreach ($request->status_for as $statusFor) {
                # Get all records that need to shift their position
                $this->updatePositionNumber($statusFor, $request->position);

                $slugBase = Str::slug($request->status_text);
                $slug = $slugBase;
                $counter = 1;

                # Ensure the slug is unique
                while (AdminControlConfig::where('status_for', $statusFor)->where('slug', $slug)->exists()) {
                    $slug = $slugBase . '-' . $counter++;
                }

                $config = new AdminControlConfig([
                    'status_text'         => $request->status_text,
                    'slug'                => $slug,
                    'status_color'        => $request->status_color,
                    'position'            => $request->position,
                    'is_predefined'       => $request->status,
                    'status_for'          => $statusFor,
                    'invoice_footer_text' => $request->invoice_footer_text,
                    'contract_footer_text' => $request->contract_footer_text,
                    'trigger_action' => makeAnyIdArrayFormat($request->trigger_action) ?? null,
                    'send_plat_forms' => makeAnyIdArrayFormat($request->send_plat_forms) ?? null,
                ]);

                $config->save();
            }

            DB::commit();
            return $this->actionSuccess('Status Create successfully',  $config);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function pageStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                   => 'required|exists:admin_control_configs,id',
            'status_text'          => 'required|string|max:255',
            'status_color'         => 'required|string',
            'position'             => 'required|integer|min:0',
            'status'               => 'required|boolean',
            'status_for'           => 'required|array|min:1',
            'status_for.*'         => 'string|distinct',
            'invoice_footer_text'  => 'nullable|string',
            'contract_footer_text' => 'nullable|string',
            'trigger_action'       => 'nullable|array',
            'send_plat_forms'      => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        # Check uniqueness of status_text for each status_for
        foreach ($request->status_for as $statusFor) {
            $exists = AdminControlConfig::where('id', '!=', $request->id)->where('status_text', $request->status_text)->where('status_for', $statusFor)->exists();
            if ($exists) {
                return $this->actionFailure("Status '{$request->status_text}' already exists for '{$statusFor}'.");
            }
        }

        DB::beginTransaction();
        try {
            $updatedConfigs = [];

            foreach ($request->status_for as $statusFor) {
                # Get all records that need to shift their position
                $this->updatePositionNumber($statusFor, $request->position);
                $update = [
                    'status_text'          => $request->status_text,
                    'status_color'         => $request->status_color,
                    'position'             => $request->position,
                    'invoice_footer_text'  => $request->invoice_footer_text,
                    'contract_footer_text' => $request->contract_footer_text,
                    'trigger_action' => makeAnyIdArrayFormat($request->trigger_action) ?? null,
                    'send_plat_forms' => makeAnyIdArrayFormat($request->send_plat_forms) ?? null,
                ];

                if ($request->is_predefined == true || $request->is_predefined == 1) {
                    $update['is_predefined']        = $request->is_predefined;
                    $update['status_for']           = $statusFor;
                }

                # Update the target record
                $config = AdminControlConfig::where('id', $request->id)->update($update);

                $updatedConfigs[] = $config;
            }

            DB::commit();
            return $this->actionSuccess('Status updated successfully', $updatedConfigs);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'         => 'required',
            'status_for'         => 'required',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $update = AdminControlConfig::where('id', $request->status_id)->first();
            if (!$update) return $this->actionFailure('Status info not Found!');
            $update->position = (int) $request->status;
            $update->save();

            # Get all records that need to shift their position
            $this->updatePositionNumber($update->status_for, 0);

            DB::commit();
            return $this->actionSuccess('Status Update successfully',  $update);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function updatePositionNumber($status_for, $position)
    {
        // Get all records with a position >= 1, ordered ascending
        $position_list = AdminControlConfig::where('status_for', $status_for)
            ->where('position', '>=', 1)
            ->orderBy('position', 'asc')
            ->get();

        $newPosition = 1;
        foreach ($position_list as $item) {
            // Skip the slot where the new item will be inserted
            if ($newPosition == $position) {
                $newPosition++;
            }

            // Assign the new position
            $item->position = $newPosition;
            $item->save();

            $newPosition++;
        }
    }

    public function changeColorStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status_color'         => 'required',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $update = AdminControlConfig::where('id', $request->status_id)->first();
            if (!$update) return $this->actionFailure('Status info not Found!');
            $update->status_color = $request->status_color;
            $update->save();
            DB::commit();
            return $this->actionSuccess('Change Color Status successfully',  $update);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function pageStatusDelete(Request $request)
    {
        DB::beginTransaction();
        try {
            $info = AdminControlConfig::where('id', $request->status_id)->first();
            if (!$info) return $this->actionFailure('Unable to delete: Status information not found.');

            $modelShortNamePlural = $info->status_for;
            $modelShortName = Str::singular($modelShortNamePlural);
            $makeDelte = 0;
            if ($modelShortName === 'Rule') {
                $modelClass = 'Modules\\AlertAndNotification\\Models\\Rule';
            } else {

                $modelClass = "Modules\\$modelShortNamePlural\\Models\\$modelShortName";
                if (!class_exists($modelClass)) {

                    $mainModelClass = "App\\Models\\$modelShortName";
                    if (class_exists($mainModelClass)) {
                        $modelClass = $mainModelClass;
                    } else {
                        $makeDelte = 1;
                        // return $this->actionFailure( "Unable to delete: The model for status type '{$modelShortNamePlural}' could not be found in either the module or main app." );
                    }
                }
            }

            // Now check if any record uses this status
            if ($makeDelte == 0) {

                if ($modelShortName == 'FollowUp') {
                    $relatedCount = $modelClass::where('call_status', $info->slug)->count();
                } else {
                    $relatedCount = $modelClass::where('status', $info->slug)->count();
                }
                if ($relatedCount > 0) {
                    return $this->actionFailure("Unable to delete: This status is currently in use in {$modelShortNamePlural}. You can only deactivate it, not delete it.");
                }
            }

            $status_for =  $info->status_for;
            $info->delete();

            # Get all records that need to shift their position
            $this->updatePositionNumber($status_for, 0);

            DB::commit();
            return $this->actionSuccess('Status deleted successfully.',  $info);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure('An error occurred while deleting the status: ' . $e->getMessage());
        }
    }
}
