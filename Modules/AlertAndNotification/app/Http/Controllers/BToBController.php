<?php

namespace Modules\AlertAndNotification\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Jobs\ImportCsvFileInfoJob;
use App\Models\ExportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\NotificationLog;
use Modules\RolePermission\Constants\RolePermissionConst;
use League\Csv\Reader;
use League\Csv\Statement;
use Modules\AlertAndNotification\Models\BToBUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Constants\EmailConst;
use Modules\AlertAndNotification\Events\NotificationMessage;
use Modules\AlertAndNotification\Services\WhatsAppService;
use Illuminate\Validation\Rule;
use Modules\AlertAndNotification\Jobs\WhatsAppJob;

class BToBController extends Controller
{
  const CONTROLLER_NAME = "BToB Controller";

  protected $referer;
  protected $login_user;

  public function __construct()
  {
    $this->login_user = request()->user() ?? Auth::user() ?? null;
  }

  public function optionLeadList(Request $request)
  {
    try {
      $user = $this->login_user;
      $roleSlugs = $user->roles()->pluck('slug')->toArray();

      # Base query for email notifications
      $query = BToBUser::query();

      # If not admin/super admin, filter by receiver
      if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
        $query->where('created_by', $user->uuid);
      }

      $clients = $query->select('id', 'name')->get();
      return $this->actionSuccess("option Lead List", $clients);
    } catch (\Exception $e) {
      return $this->actionFailure($e->getMessage());
    }
  }

  public function b2bUserCount(Request $request)
  {
    try {
      $user = $this->login_user;
      $roleSlugs = $user->roles()->pluck('slug')->toArray();

      # Base query for email notifications
      $query = BToBUser::query();

      # If not admin/super admin, filter by receiver
      if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
        $query->where('created_by', $user->uuid);
      }

      $unreadCount = $query->count();

      return $this->actionSuccess("Unread email notification count retrieved successfully.", [
        'total' => $unreadCount,
        'active' => $unreadCount,
        'in_active' => $unreadCount
      ]);
    } catch (\Exception $e) {
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function b2bUserList(Request $request)
  {
    $user = $this->login_user;
    $roleSlugs = $user->roles()->pluck('slug')->toArray();

    try {
      $sort_key   = $request->input('sort_key') ?? null;
      $sort_order = $request->input('sort_order') ?? null;
      $search     = $request->input('search') ?? null;
      $startDate  = $request->input('start_date') ?? null;
      $endDate    = $request->input('end_date') ?? null;
      $status    = $request->input('status') ?? null;

      $query = BToBUser::query()->search($search);

      if (!array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN])) {
        $query->where('created_by', $user->uuid);
      }

      if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
      }

      if ($status) $query->where('status', strtolower($status));

      if ($sort_key && $sort_order) {
        $query->orderBy($sort_key, $sort_order);
      } else {
        $query->orderBy('created_at', 'desc');
      }

      $list = $query->with('creator:uuid,name', 'updater:uuid,name')->paginate($request->per_page ?? 50);

      return $this->actionSuccess('B To B User retrieved successfully.', customizingResponseData($list));
    } catch (\Exception $e) {
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function BToBUserImport(Request $request)
  {
    set_time_limit(0); # Prevent timeout for large files

    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:csv,txt',
    ]);

    if ($validator->fails()) {
      return $this->validationFailed(true, $validator->errors());
    }

    try {
      $file = $request->file('file');
      $csv = Reader::createFromPath($file->getRealPath(), 'r');
      $csv->setHeaderOffset(0);

      $records = Statement::create()->process($csv);
      $recordCount = iterator_count($records);

      if ($recordCount < 5001) {
        # Reset CSV reader (because iterator_count exhausted the records)
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->process($csv);

        $imported = $duplicates = $notCreated = 0;
        $notCreatedList = collect();
        $duplicateList = collect();

        foreach ($records as $record) {
          $name = trim($record['Name']);
          $email = strtolower(trim($record['Email']));
          $contactNo = trim($record['Contact No']);
          $status = trim($record['Status'] ?? 'active');

          $userExists = BToBUser::where([
            ['name', $name],
            ['email', $email],
            ['contact_no', $contactNo],
          ])->exists();

          if ($userExists) {
            $duplicates++;
            $duplicateList->push($this->formatImportError($name, $email, $contactNo, "Duplicate entry — already exists"));
            continue;
          }

          try {
            BToBUser::create([
              'name'         => $name,
              'company'      => $record['Company'] ?? null,
              'role'         => $record['Role'] ?? null,
              'country_code' => $record['Country Code'] ?? null,
              'contact_no'   => $contactNo,
              'email'        => $email,
              'status'       => strtolower($status),
              'address'      => $record['Address'] ?? null,
              'created_by'   => $this->login_user->uuid ?? null,
            ]);

            $imported++;
          } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            $notCreated++;
            $notCreatedList->push($this->formatImportError($name, $email, $contactNo, $e->getMessage()));
          }
        }

        return $this->actionSuccess('BToB user CSV import completed.', (object)[
          'imported_count'    => $imported,
          'duplicate_count'   => $duplicates,
          'duplicate_list'    => $duplicateList,
          'not_created_count' => $notCreated,
          'not_created_list'  => $notCreatedList,
        ]);
      } else {
        # For more than 5000 records, upload file and process with job
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = str_replace(' ', '_', $originalName);
        $extension = $file->getClientOriginalExtension();

        # Final filename
        $filename = formattedDateTime() . '_' . $originalName . '.' . $extension;
        $filePath = $file->storeAs('imports', $filename, 'local');

        $exportLog = ExportLog::create([
          'name'         => $filename,
          'table_name'   => 'b_to_b_users',
          'extension'    => $file->getClientOriginalExtension(),
          'file_path'    => $filePath,
          "status"       => CommonConst::PENDING,
          'created_by'   => $this->login_user->uuid ?? null,
        ]);

        ImportCsvFileInfoJob::dispatch($filePath, $exportLog->id, $this->login_user->uuid);

        return $this->actionSuccess("File uploaded. Import will be processed in the background.", $exportLog);
      }
    } catch (\Exception $e) {
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }


  private function formatImportError($name, $email, $contactNo, $message)
  {
    return [
      'name'        => $name,
      'email'       => $email,
      'contact_no'  => $contactNo,
      'message'     => $message,
    ];
  }

  public function b2bUserCreate(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'company' => 'required|string|max:255',
      'status' => 'required|in:active,in-active',
      'email' => ['nullable', 'email', 'max:255', Rule::unique('b_to_b_users', 'email')],
      'country_code' => 'required',
      'contact_no' => ['required', Rule::unique('b_to_b_users', 'contact_no')],
      'profile' => 'nullable|image|mimes:jpg,jpeg,png',
      'address' => 'nullable',
      'role' => 'nullable',
    ]);

    if ($validator->fails()) {
      return $this->validationFailed(true, $validator->errors());
    }

    DB::beginTransaction();
    try {
      $bTobUser = BToBUser::create([
        'name'         => $request->name,
        'company'      => $request->company,
        'role'         => $request->role,
        'country_code' => $request->country_code,
        'contact_no'   => $request->contact_no,
        'email'        => $request->email,
        'status'       => strtolower($request->status),
        'address'      => $request->address,
        'created_by'   => $this->login_user->uuid ?? null,
      ]);

      # Handle new image upload
      if ($request->hasFile('profile')) {
        $directory = "b2b_profiles";
        if (!Storage::disk('public')->exists($directory)) {
          Storage::disk('public')->makeDirectory($directory, 0755, true);
        }

        $path = $request->file('profile')->store('b2b_profiles', 'public');
        $bTobUser->avatar = url('storage/' . $path);
        $bTobUser->save();
      }

      DB::commit();
      return $this->actionSuccess('b2b User Create successfully.', $bTobUser);
    } catch (\Exception $e) {
      DB::rollBack();
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function b2bUserUpdate(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id'           => 'required|exists:b_to_b_users,id',
      'name'         => 'required|string|max:255',
      'company'      => 'required|string|max:255',
      'status'       => 'required|in:active,in-active',
      'email'        => ['nullable', 'sometimes', 'email', 'max:255', Rule::unique('b_to_b_users', 'email')->ignore($request->id)],
      'country_code' => 'required',
      'contact_no'   => ['required', Rule::unique('b_to_b_users', 'contact_no')->ignore($request->id)],
      'profile'      => 'nullable|image|mimes:jpg,jpeg,png',
      'address'      => 'nullable|string|nullable',
      'role'         => 'nullable|string|nullable',
    ]);

    if ($validator->fails()) return $this->validationFailed(true, $validator->errors()->first());

    DB::beginTransaction();

    try {
      $bTobUser = BToBUser::findOrFail($request->id);

      # Handle image deletion
      if ($request->boolean('image_delete') && $bTobUser->avatar) {
        # Extract the relative path from full URL
        $relativePath = Str::after($bTobUser->avatar, '/storage/'); # gives "b2b_profiles/filename.jpg"

        if (Storage::disk('public')->exists($relativePath)) {
          Storage::disk('public')->delete($relativePath);
        }

        $bTobUser->avatar = null;
      }

      # Handle new image upload
      if ($request->hasFile('profile')) {
        $directory = "b2b_profiles";
        # Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
          Storage::disk('public')->makeDirectory($directory, 0755, true);
        }
        $path = $request->file('profile')->store('b2b_profiles', 'public');

        # Ensure the image is saved with the full URL path
        $bTobUser->avatar = url('storage/' . $path);
      }

      # Save the updated user record
      $bTobUser->save();

      # Update other fields
      $bTobUser->fill([
        'name'         => $request->name,
        'company'      => $request->company,
        'role'         => $request->role,
        'country_code' => $request->country_code,
        'contact_no'   => $request->contact_no,
        'email'        => $request->email,
        'status'       => strtolower($request->status),
        'address'      => $request->address,
        'last_updated_by'      => $this->login_user->uuid ?? null,
      ])->save();

      DB::commit();
      return $this->actionSuccess('b2b User Updated successfully.', $bTobUser);
    } catch (\Exception $e) {
      DB::rollBack();
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function b2bStatusUpdate(Request $request)
  {
    $validator = Validator::make(
      ['b2b_id' => $request->b2b_id, 'status' => strtolower($request->status)],
      [
        'b2b_id' => 'required|exists:b_to_b_users,id',
        'status' => 'required|in:active,in-active',
      ]
    );

    if ($validator->fails()) {
      return $this->validationFailed(true, $validator->errors()->first());
    }

    DB::beginTransaction();
    try {
      $bTobUser = BToBUser::findOrFail($request->b2b_id);
      $bTobUser->status = strtolower($request->status);
      $bTobUser->save();

      DB::commit();
      return $this->actionSuccess('B2B User status updated successfully.', $bTobUser);
    } catch (\Exception $e) {
      DB::rollBack();
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function b2bUserDelete(Request $request)
  {
    $validator = Validator::make(['b2b_id' => $request->b2b_id], [
      'b2b_id' => 'required|exists:b_to_b_users,id',
    ]);

    if ($validator->fails()) {
      return $this->validationFailed(true, $validator->errors());
    }
    DB::beginTransaction();
    try {
      $bTobUser = BToBUser::findOrFail($request->b2b_id);
      $bTobUser->delete();
      DB::commit();
      return $this->actionSuccess('B2B User deleted successfully.', []);
    } catch (\Exception $e) {
      DB::rollBack();
      createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
      return $this->actionFailure($e->getMessage());
    }
  }

  public function reachoutSendMessage(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'b_to_b_user_ids' => 'required',
      'socialPlatform' => 'required|string',
      'message' => 'required|string',
      'type' => 'required|string|in:BToB-User',
      'file' => 'nullable|file|mimes:pdf,doc,docx,jpeg,jpg,png', # |max:5120 5MB limit
    ]);

    if ($validator->fails()) return $this->actionFailure($validator->errors()->first());
    set_time_limit(0);

    $sender_id = Auth::User()->uuid ?? adminUserId()[0];

    if (is_array($request->b_to_b_user_ids)) {
      $list = $request->b_to_b_user_ids;
    } else {
      $list = explode(',', $request->b_to_b_user_ids);
    }

    # If file exists, store and get extension
    $fileUrl = "";
    $extension = "";
    $fileCaption = "";

    if ($request->hasFile('file')) {
      $file = $request->file('file');
      $path = $file->store('reachout_files', 'public');
      $fileUrl = asset('storage/' . $path);
      $fileCaption = $file->getClientOriginalName();
      $mime = $file->getMimeType();

      if (Str::startsWith($mime, 'image/')) {
        $extension = 'Image';
      } elseif (Str::startsWith($mime, 'application/') || Str::startsWith($mime, 'text/')) {
        $extension = 'Document';
      } else {
        $extension = '';
      }
    }

    // $user_list = BToBUser::whereIn('id', $list)->select('id as uuid', 'name', 'email', 'contact_no as phone')->get()->toArray();
    // $additional_info = [
    //   'notification_type_id' => null,
    //   'sender_id' => $sender_id,
    //   "fileUrl" => $fileUrl,
    //   "fileCaption" => $fileCaption,
    //   "extension" => $extension,
    //   "is_notification" => false,
    //   "col_name" => 'b_to_b_user_id',
    //   'socialPlatform' => $request->socialPlatform,
    //   "type" => $request->type,
    //   'subject' => "Reach Out Send Message ",
    // ];

    // $log_id = null;
    // WhatsAppJob::dispatch($log_id, $user_list, ['message' => $request->message], $additional_info);

    foreach ($list as $item_id) {
      $info = BToBUser::find($item_id);
      $receiver_contact = $info->contact_no ?? null;

      if ($receiver_contact) {
        $col_name =  "b_to_b_user_id";
        $email_body = [
          $col_name => $item_id,
          'socialPlatform' => $request->socialPlatform,
          "type" => $request->type,
          'message' => $request->message,
        ];

        $additional_info = [
          'notification_type_id' => null,
          'user_name' => $info->name,
          'sender_id' => $sender_id,
          "fileUrl" => $fileUrl,
          "fileCaption" => $fileCaption,
          "extension" => $extension,
          "is_notification" => false,
          'b_to_b_user_id' => $item_id,
          'socialPlatform' => $request->socialPlatform,
          "type" => $request->type,
        ];

        $logData = [
          'receiver_contact' => $receiver_contact,
          'subject' => "Reach Out Send Message " . $info->name,
          'content' => $request->message,
          'priority' => CommonConst::HIGH,
          'status' => CommonConst::PENDING,
          'notification_type_id' => null,
          'receiver_id' => null,
          'section_type' => CommonConst::WHATSAPP,
          "is_notification" => false,
          'email_body' => json_encode($email_body),
          'additional_info' => json_encode($additional_info),
          'sender_id' => $sender_id ?? null,
          'module_id' => $item_id ?? null,
        ];

        $log = NotificationLog::create($logData);
        $userName = trim($info->name);
        $whatsAppMessage = str_replace(['<br>', '<br/>', '<br />'], "\n", $request->message);
        $response = (new WhatsAppService())->sendMediaMessage($userName, $receiver_contact, $whatsAppMessage, $fileUrl, $fileCaption, $extension);
        $log->status = $response->status ? CommonConst::SUCCESS : CommonConst::FAILED;
        $log->message = $response->message ?? null;
        $log->save();
        try {
          event(new NotificationMessage($log->subject, $sender_id, CommonConst::WHATSAPP));
        } catch (\Exception $e) {
          createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
        }
      }
    }

    return $this->actionSuccess('Message Sending please check log');
  }
}
