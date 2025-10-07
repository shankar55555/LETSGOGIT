<?php

namespace App\Http\Controllers\Api;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Mail\ForgetPassword;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\RolePermission\Constants\RolePermissionConst;
use Stevebauman\Location\Facades\Location;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Mail;


class AuthController extends Controller
{
    const CONTROLLER_NAME = "Auth Controller";

    public function signIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'remember_me' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        try {
            # Attempt to find the user by email, username, or phone, excluding inactive users
            $user = User::where(function ($query) use ($request) {
                $query->where('email', $request->email)->orWhere('user_name', $request->email)->orWhere('phone', $request->email);
            })->where('status', '!=', CommonConst::IN_ACTIVE)->first();

            # Validate password and user existence
            if (!$user) {
                return $this->actionFailure('User not found or account is inactive.');
            }

            if (!Hash::check($request->password, $user->password)) {
                return $this->actionFailure('Incorrect password. Please try again.');
            }

            # Log in the user (not required for Sanctum token issuing, but optional)
            $token = $user->createToken('API Token')->plainTextToken;

            $credentials = ['email' => $user->email, 'user_name' => $user->user_name, 'password' => $request->password];

            if ($request->remember_me) {
                auth('web')->attempt($credentials, true);
            } else {
                auth('web')->attempt($credentials);
            }

            $info = adminAddLoginUserLog($user, $request);

            $response = [
                'access_token' => $token,
                'permissions' => $user->getPermissionsViaRoles(),
                'user' => $user->makeHidden('roles'),
                'status' => true
            ];

            return $this->actionSuccess("Login Successfully", $response);
        } catch (\Exception $e) {
            # createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->user_name = $request->user_name ?? (explode('@', $request->email)[0] ?? $request->name);
            $user->password = Hash::make($request->password);
            $user->status = CommonConst::ACTIVE;
            $user->save();

            $token = $user->createToken('API Token')->plainTextToken;

            $info = adminAddLoginUserLog($user, $request);

            $response = [
                'access_token' => $token,
                'permissions' => $user->getPermissionsViaRoles(),
                'user' => $user->makeHidden('roles'),
                'status' => true
            ];

            return $this->actionSuccess('Registration successful', $response);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user_id = Auth::id();
            $user = User::where('id', $user_id)->first();

            if (!$user) {
                return $this->actionFailure('User not found.');
            }

            if (Hash::check($request->old_password, $user->password)) {
                $user->password = $request->password;
                $user->save();

                return $this->actionSuccess('Successfully changed password.');
            }

            return $this->actionFailure('Old password does not match.');
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    /**
     * send reset password link to user email
     *
     */
    public function forgotPassword(Request $request)
    {
        try {
            $user = User::whereEmail($request->email)->first();

            if (!$user) {
                return $this->actionFailure('User email not found.');
            }
            $token = Str::random(40);
            $datetime = Carbon::now()->format('Y-m-d H:i:s');
            $expiresAt = Carbon::now()->addMinutes(2);


            PasswordReset::updateOrCreate(
                ['email' => $request->email],
                ['email' => $request->email, 'token' => $token, 'created_at' => $datetime]
            );

            User::where('email', $request->email)->update([
                'expire_at' => $expiresAt,
            ]);

            Mail::to($user->email)->send(new ForgetPassword($user, $token));

            return $this->actionSuccess('Password reset link has been sent.');
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function ResetPasswordView(Request $request)
    {
        $resetData = PasswordReset::where('token', $request->token)->where('email', $request->email)->first();
        if (isset($resetData)) {
            // Redirect to the Vue.js reset password page with query parameters
            $redirectUrl = url('/reset-password') . '?' . http_build_query([
                'token' => $request->token,
                'email' => $request->email
            ]);

            return redirect($redirectUrl);
        }

        // Redirect to login page with error message or return error response
        return redirect('/login')->with('error', 'Invalid or expired reset link.');
    }

    /**
     * Check if password reset token is valid and not expired
     */
    public function validateResetToken(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required',
            ]);

            $resetRecord = PasswordReset::where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'error' => 'Invalid reset token.',
                    'expired' => true,
                    'status' => false
                ], 400);
            }

            // Check if the token has expired
            $user = User::where('email', $request->email)->first();
            if (!$user || Carbon::now()->greaterThan($user->expire_at)) {
                return response()->json([
                    'error' => 'This password reset link has expired.',
                    'expired' => true,
                    'status' => false
                ], 400);
            }

            return response()->json([
                'message' => 'Token is valid.',
                'expired' => false,
                'status' => true
            ]);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return response()->json([
                'error' => 'An error occurred while validating the token.',
                'expired' => true,
                'status' => false
            ], 500);
        }
    }

    /**
     * reset password
     *
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetRecord = PasswordReset::where('email', $request->email)
            ->where('token', $request->token)
            ->first();
        if (!$resetRecord) {
            return response()->json(['error' => 'Invalid token.'], 400);
        }

        // Check if the token has expired
        $user = User::where('email', $request->email)->first();
        if (Carbon::now()->greaterThan($user->expire_at)) {
            return response()->json(['error' => 'This password reset link has expired.'], 400);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->expire_at = null; // Clear the expiration time
        $user->save();

        // Optionally, you can clear the token from the password_reset_tokens table
        PasswordReset::where('token', $request->token)->delete();
        return $this->actionSuccess('Password reset successfully', $user);
    }

    public function getProfile()
    {
        try {
            $user_id = request()->user()->uuid;
            $user = User::where('uuid', $user_id)->with('roles')->first();
            $roleSlugs = $user->roles->pluck('slug')->toArray();
            $isSuperAdmin = in_array(RolePermissionConst::SLUG_SUPER_ADMIN, $roleSlugs);
            $isAdmin = in_array(RolePermissionConst::SLUG_ADMIN, $roleSlugs);
            $user->isAdmin = $isSuperAdmin || $isAdmin;
            $user->user_type = $isSuperAdmin ? RolePermissionConst::SUPER_ADMIN : ($isAdmin ? RolePermissionConst::ADMIN : RolePermissionConst::EMPLOYEE);
            return $this->actionSuccess('Profile retrieved successfully.', $user);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = User::findOrFail(Auth::user()->id);

            if (empty($user)) {
                return $this->actionFailure('User not found.');
            }

            if (isset($request['image']) && !empty($request['image'])) {
                $user->clearMediaCollection(User::USER_IMAGE);
                $user->addMedia($request['image'])->toMediaCollection(User::USER_IMAGE, config('app.media_disc'));
            }

            $user->update($request->all());

            return $this->actionSuccess('Profile updated successfully.', $user);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function fetchSettings()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->actionFailure('User not authenticated.');
            }

            # Retrieve the settings for the authenticated user
            $settings = null;
            // $settings = Setting::where('user_id', $user->uuid)->pluck('value', 'key')->toArray();

            return response()->json([
                'success' => true,
                'message' => 'General settings retrieved successfully.',
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function updateSettings(Request $request)
    {
        try {

            $user = User::findOrFail(Auth::user()->id);

            if (empty($user)) {
                return $this->actionFailure('User not found.');
            }

            $file = $request->file('logo');

            if ($file) {
                # Get original file name and extension
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                # Create unique file name
                $uniqueFileName = $originalFileName . '.' . $extension;

                # Store file
                $path = $file->storeAs('uploads', $uniqueFileName, 'public');
                $url = Storage::url($path);

                // # Update logo-related settings
                // Setting::updateOrCreate(
                //     ['key' => 'site_logo', 'user_id' => $user->uuid],
                //     ['value' => $uniqueFileName]
                // );

                // Setting::updateOrCreate(
                //     ['key' => 'logo_url', 'user_id' => $user->uuid],
                //     ['value' => $url]
                // );
            }

            # Update other settings
            $settings = [
                'site_name' => $request->input('name'),
                'site_email' => $request->input('email'),
            ];

            foreach ($settings as $key => $value) {
                // Setting::updateOrCreate(
                //     ['key' => $key, 'user_id' => $user->uuid],
                //     ['value' => $value]
                // );
            }

            # Retrieve all updated settings
            $updatedSettings = [];
            // $updatedSettings = Setting::where('user_id', $user->uuid)->pluck('value', 'key')->toArray();

            # Return response with success message and updated settings
            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully.',
                'settings' => $updatedSettings,
            ]);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->actionFailure('No active session found.');
            }
            $exist = UserLoginLog::where('user_id', $user->uuid)->latest('logged_at')->first();
            if (!$exist || ($exist && $exist->event == "login")) {
                $ip = getIpAddress();
                $location = Location::get($ip);

                # Log the logout before revoking token
                UserLoginLog::create([
                    'user_id'    => $user->uuid,
                    'ip_address' => $ip,
                    'user_agent' => request()->header('User-Agent'),
                    'event'      => 'logout',
                    'success'    => true,
                    'logged_at'  => now(),
                    'country'    => $location?->countryName ?? '',
                    'state'      => $location?->regionName ?? '',
                    'city'       => $location?->cityName ?? '',
                ]);
            }
            # Passport	
            # $user->token()->revoke();
            # request()->user()->token()->revoke();

            # Sanctum	
            request()->user()->currentAccessToken()->delete();

            #Web (Session)	
            # Auth::logout();
            # request()->session()->invalidate();
            # request()->session()->regenerateToken();

            return $this->actionSuccess('User logged out successfully.');
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    # User Login Log
    public function getUserLoginLogs(Request $request)
    {
        try {
            $user_view_id = null;
            $query = UserLoginLog::query();
            # Apply filtering if the user is NOT a Super Admin
            $query  = applyFilteringUser($query, "user_id", $user_view_id);
            # Filter by search query
            if ($search = $request->input('search')) {
                $query->where(function ($qur) use ($search) {
                    $qur->where('user_agent', 'ILIKE', "%{$search}%")
                        ->orWhere('country', 'ILIKE', "%{$search}%")
                        ->orWhere('state', 'ILIKE', "%{$search}%")
                        ->orWhereHas('user', function ($qu) use ($search) {
                            $qu->where('name', 'ILIKE', "%{$search}%")
                                ->orWhere('email', 'ILIKE', "%{$search}%")
                                ->orWhere('phone', 'ILIKE', "%{$search}%");
                        });
                });
            }

            # Sort results
            if ($sortKey = $request->input('sort_key')) {
                $sortOrder = $request->input('sort_order', 'asc');
                $query->orderBy($sortKey, $sortOrder);
            }

            # Pagination
            $perPage = $request->input('per_page', 10);
            $list = $query->with('user')->orderBy('logged_at', 'desc')->paginate($perPage);

            return $this->actionSuccess('User logs retrieved successfully.', customizingResponseData($list));
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function deleteLoginLog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,restore,force_delete',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $action = $request->action;
            $login_log_id = $request->login_log_id;

            $UserLoginLog = UserLoginLog::findOrFail($login_log_id);
            if ($action  == 'force_delete') {
                if (strtolower(trim($request->delete_text)) !== 'delete') {
                    return $this->actionFailure('Your Delete input value is wrong. If you are permanently deleting the file, please type "delete" to confirm!');
                }
                $UserLoginLog->Delete();
                $message = 'User Login Log permanently deleted successfully.';
            } else {
                return $this->actionFailure('Invalid action.');
            }

            DB::commit();
            return $this->actionSuccess($message);
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }

    public function logUnauthenticatedAccess(Request $request)
    {
        try {
            if (!User::where('uuid', $request->user_id)->exists()) {
                return $this->actionFailure("User is not exists");
            }
            $log = null;
            $exist = UserLoginLog::where('user_id', $request->user_id)->latest('logged_at')->first();
            if (!$exist || ($exist && $exist->event == "login")) {
                $ip = getIpAddress();
                $location = Location::get($ip);

                $log = UserLoginLog::create([
                    'user_id'    => $request->user_id,
                    'ip_address' => $ip,
                    'user_agent' => $request->header('User-Agent'),
                    'event'      => 'Unauthenticated',
                    'success'    => false,
                    'logged_at'  => now(),
                    'country'    => $location?->countryName ?? '',
                    'state'      => $location?->regionName ?? '',
                    'city'       => $location?->cityName ?? '',
                ]);
            }
            return $this->actionSuccess('Unauthenticated access logged successfully', $log);
        } catch (\Exception $e) {
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
