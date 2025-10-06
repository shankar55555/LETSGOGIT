<?php

namespace App\Http\Controllers\Api;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use App\Models\UserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\UserTarget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class UserAttendanceController extends Controller
{
    const CONTROLLER_NAME = "User Attendance Controller";
    public function index(Request $request)
    {
        $validator = Validator::make(["start_date" => $request->start_date], [
            'start_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        $query = UserAttendance::query();

        # Filter by search query
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('attendance_date', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%");
            });
        }

        # Filter by user_id if provided
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        # Filter by date range if BOTH dates are provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('attendance_date', [
                $request->input('start_date'),
                $request->input('end_date')  != 'undefined' ? $request->input('end_date') : $request->input('start_date'),
            ]);
        }

        # Apply sorting
        if ($sortKey = $request->input('sort_key')) {
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortKey, $sortOrder);
        } else {
            $query->orderBy('attendance_date', 'desc');
        }

        $paginated = $query->with('user')->paginate($request->input('per_page', 10));

        $data = collect($paginated->items())->map(function ($attendance) {
            if (!empty($attendance->time_in) && !empty($attendance->time_out)) {
                try {
                    $checkIn = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_in);
                    $checkOut = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_out);


                    $totalSeconds = $checkIn->diffInSeconds($checkOut);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = round(($totalSeconds % 3600) / 60); // round instead of floor

                    if ($hours > 0 && $minutes > 0) {
                        $attendance->total_duration = "{$hours} hours {$minutes} minutes";
                    } elseif ($hours > 0) {
                        $attendance->total_duration = "{$hours} hours";
                    } elseif ($minutes > 0) {
                        $attendance->total_duration = "{$minutes} minutes";
                    } else {
                        $attendance->total_duration = "0 minutes";
                    }
                } catch (\Exception $e) {
                    $attendance->total_duration = "0 minutes";
                }
            } else {
                $attendance->total_duration = "0 minutes";
            }

            return $attendance;
        });



        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Attendance records retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $statusTypes = implode(',', [CommonConst::PRESENT, CommonConst::HALF_PRESENT, CommonConst::ABSENT]);
        $validator = Validator::make($request->only('attendance_date', 'status'), [
            'attendance_date' => 'required|date',
            'status' => "required|in:$statusTypes",
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        $userId = $request->user_id ?? Auth::user()->uuid;
        $attendanceDate = Carbon::parse($request->attendance_date)->format('Y-m-d');

        # Check if record already exists for this date and user
        if (UserAttendance::where('attendance_date', $attendanceDate)
            ->where('user_id', $userId)
            ->exists()
        ) {
            return $this->actionFailure('This date record already exists!');
        }

        # Fill missing days from 1st of current month up to (but not including) attendanceDate
        $this->markMissingDaysAsAbsent($userId, $attendanceDate);

        # Create attendance record
        $data = UserAttendance::create([
            'user_id' => $userId,
            'attendance_date' => $attendanceDate,
            'status' => $request->status,
            'time_in' => $request->timeDuration == 'morningTime' ? now()->setTimezone('Asia/Kolkata')->format('H:i:s') : null,
            'work' => $request->work,
        ]);

        return $this->actionSuccess('Attendance set successfully!', $data);
    }

    /**
     * Mark missing days as absent between 1st of month and attendanceDate - 1
     */
    protected function markMissingDaysAsAbsent($userId, $attendanceDate)
    {
        $attendanceDate = Carbon::parse($attendanceDate);
        $startDate = $attendanceDate->copy()->startOfMonth();
        $endDate = $attendanceDate->copy()->subDay();

        if ($endDate->lessThan($startDate)) {
            return; # No missing range (e.g. adding on April 1)
        }

        $missingDates = $this->getMissingDates($userId, $startDate, $endDate);

        foreach ($missingDates as $date) {
            UserAttendance::create([
                'user_id' => $userId,
                'attendance_date' => $date,
                'status' => CommonConst::ABSENT,
            ]);
        }
    }

    /**
     * Get all missing dates without attendance between two dates
     */
    protected function getMissingDates($userId, $startDate, $endDate)
    {
        $existingDates = UserAttendance::where('user_id', $userId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->pluck('attendance_date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $allDates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $allDates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return array_diff($allDates, $existingDates);
    }


    public function checkTodaysAttendance()
    {

        $today = now()->format('Y-m-d');
        $userId = Auth::user()->uuid;

        $attendanceExists = false;
        $hasClosedAttendance = false;

        if (Auth::user()->mark_attendance) {
            // If the user is allowed to mark attendance, just check if it's already marked
            $attendanceExists = UserAttendance::where('attendance_date', $today)
                ->where('user_id', $userId)
                ->exists();

            $hasClosedAttendance = UserAttendance::where('attendance_date', $today)
                ->where('user_id', $userId)
                ->whereNotNull('time_out')
                ->exists();
        } else {
            // If not allowed to mark, assume attendance is already marked and check if it's closed
            $attendanceExists = true;

            // This probably means: has attendance record and shift is closed (e.g., time_out is set)
            $hasClosedAttendance = UserAttendance::where('attendance_date', $today)
                ->where('user_id', $userId)
                ->whereNotNull('time_out')
                ->exists();
        }

        return response()->json([
            'has_marked_attendance' => $attendanceExists,
            'has_closed_attendance' => $hasClosedAttendance,
            'today' => $today,
        ]);
    }


    public function update(Request $request, UserAttendance $userAttendance)
    {
        $validator = Validator::make($request->only('user_id', 'attendance_date', 'status'), [
            'user_id' => 'required|exists:users,uuid',
            'attendance_date' => 'required|date',
            'status' => 'required|in:' . implode(',', [CommonConst::PRESENT, CommonConst::HALF_PRESENT, CommonConst::ABSENT]),
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        # Check if the month is properly formatted (YYYY-MM)
        if ($request->has('month') && !preg_match('/^\d{4}-\d{2}$/', $request->month)) {
            return $this->actionFailure('Invalid month format. Please use YYYY-MM format.');
        }

        # Check for existing record with proper date format
        if (UserTarget::where('id', '!=', $request->id)
            ->where('month', $request->month ? $request->month . '-01' : null)
            ->where('user_id', $request->user_id ?? Auth::user()->uuid)
            ->exists()
        ) {
            return $this->actionFailure('This Month record already exists!');
        }

        $data = UserAttendance::where('id', $userAttendance->id)->update([
            'attendance_date' => $request->attendance_date,
            'status' => $request->status,
        ]);

        return $this->actionSuccess('Attendance updated successfully!', $data);
    }

    public function updateShiftOutTime(Request $request)
    {

        $validator = Validator::make($request->only('user_id', 'attendance_date'), [
            'user_id' => 'required|exists:users,uuid',
            'attendance_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->actionFailure($validator->errors()->first());
        }

        # Checking for the user's attendance on that specific date
        $attendance = UserAttendance::where('user_id', $request->user_id)
            ->where('attendance_date', $request->attendance_date)
            ->first();

        if ($attendance) {

            # Old code 
            // $attendance->update([
            //     'time_out' => now()->setTimezone('Asia/Kolkata')->format('H:i:s'),
            //     'work' => $request->work,
            // ]);

            # New Code 
            $now = now()->setTimezone('Asia/Kolkata');

            $timeIn = Carbon::createFromFormat('H:i:s', $attendance->time_in, 'Asia/Kolkata')
                ->setDate($now->year, $now->month, $now->day);

            $hours = round($timeIn->diffInMinutes($now) / 60, 2);

            $status = $hours < 4 ? CommonConst::ABSENT : ($hours < 8 ? CommonConst::HALF_PRESENT : CommonConst::PRESENT);

            $attendance->update([
                'time_out' => $now->format('H:i:s'),
                'work' => $request->work,
                'status' => $status,
            ]);

            return $this->actionSuccess('Attendance updated successfully!', $attendance);
        }

        return $this->actionFailure('Attendance record not found for the user on the given date.');
    }

    public function calculate(Request $request)
    {
        $userId = 3;
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        // 1. Get user with attendance in the selected month/year
        $user = User::with(['attendances' => function ($query) use ($month, $year) {
            $query->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year);
        }])->find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $grossSalary = $user->salary;
        $attendances = $user->attendances;

        // 2. Calculate total working days
        $totalWorkingDays = $attendances->unique('attendance_date')->count();

        if ($totalWorkingDays === 0) {
            return response()->json(['message' => 'No working days found'], 404);
        }

        // 3. Count present days
        $presentDays = $attendances->where('status', 'present')->count();

        // 4. Salary calculations
        $perDaySalary = $grossSalary / $totalWorkingDays;
        $earnedSalary = $perDaySalary * $presentDays;

        // 5. Get or create incentive
        $incentiveAmount = 0;

        $incentive = UserTarget::where([
            'user_id' => $user->uuid,
            'month' => Carbon::createFromDate($year, $month, 1)->format('Y-m-d'),
        ])->first();


        return response()->json([
            'user_id'            => $user->id,
            'name'               => $user->name,
            'gross_salary'       => round($grossSalary, 2),
            'total_working_days' => $totalWorkingDays,
            'present_days'       => $presentDays,
            'per_day_salary'     => round($perDaySalary, 2),
            'earned_salary'      => round($earnedSalary, 2),
            'total_payable'      => round($earnedSalary + $incentiveAmount, 2),
            'incentive'          => $incentive,
            2,
            'attendances'          => $attendances,
            2,
        ]);
    }





    //     public function calculate(Request $request)
    // {
    //     $userId = 3; // User ID, can be dynamic from the request as well
    //     $month = $request->input('month', now()->month);
    //     $year  = $request->input('year', now()->year);

    //     // 1. Get user with attendance in the selected month/year
    //     $user = User::with(['attendances' => function ($query) use ($month, $year) {
    //         $query->whereMonth('attendance_date', $month)
    //               ->whereYear('attendance_date', $year);
    //     }])->find($userId);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $grossSalary = $user->salary; // Gross salary

    //     // 2. Get attendances for the user
    //     $attendances = $user->attendances;

    //     // 3. Calculate total working days (excluding 'holiday')
    //     $totalWorkingDays = $attendances
    //         ->unique('attendance_date') // Ensure no duplicate dates
    //         ->count();

    //     if ($totalWorkingDays === 0) {
    //         return response()->json(['message' => 'No working days found'], 404);
    //     }

    //     // 4. Count present days
    //     $presentDays = $attendances->where('status', 'present')->count();

    //     // 5. Calculate salary
    //     $perDaySalary = $grossSalary / $totalWorkingDays;
    //     $earnedSalary = $perDaySalary * $presentDays;

    //     // 6. Calculate Incentives (You can change the logic based on your conditions)
    //     // Example: Incentive based on the number of present days
    //     $incentiveRate = 100; // Incentive per day for being present (can be dynamic)
    //     $incentive = $incentiveRate * $presentDays;

    //     // You can also add performance-based incentives if you have such logic:
    //     // Example: If user has more than 25 present days, give a bonus of 500.
    //     if ($presentDays > 25) {
    //         $incentive += 500; // Performance bonus
    //     }

    //     // 7. Final Salary (Gross + Earned Salary + Incentive)
    //     $finalSalary = $earnedSalary + $incentive;

    //     return response()->json([
    //         'user_id'            => $user->id,
    //         'name'               => $user->name,
    //         'gross_salary'       => round($grossSalary, 2),
    //         'total_working_days' => $totalWorkingDays,
    //         'present_days'       => $presentDays,
    //         'per_day_salary'     => round($perDaySalary, 2),
    //         'earned_salary'      => round($earnedSalary, 2),
    //         'incentive'          => round($incentive, 2),
    //         'final_salary'       => round($finalSalary, 2),
    //     ]);
    // }

    public function userAttendanceExportList(Request $request)
    {
        return;
        try {
            DB::beginTransaction();

            $type = $request->type ?? 'User-Attendance';
            $extension = $request->export_type ?? 'xlsx';
            $fileName = "{$type}_export_" . now()->format('Y-m-d_H-i-s') . ".{$extension}";
            $folderPath = "exports/{$type}";
            $filePath = "{$folderPath}/{$fileName}";

            addStoragePermission("app/public/{$folderPath}");
            // $list = $this->userAttendanceExportList($request);
            $params = [
                'list' => $list,
                'file_Path' => ($extension === 'csv') ? storage_path("app/public/{$filePath}") : $filePath,
                'type' => ucwords(str_replace('-', ' ', $type)),
            ];

            // $success = ($extension === 'csv') ? $this->generateCSV(...$params) : $this->generateXLSX(...$params);

            if (!$success) {
                DB::rollBack();
                return $this->actionFailure("Failed to generate the export file.");
            }

            ExportLog::create([
                'name' => "{$type} File Export",
                'table_name' => str_replace('-', '_', $type) . "s",
                'extension' => $extension,
                'body_params' => json_encode($request->all()),
                'file_path' => $filePath,
                'created_by' => Auth::user()->uuid,
            ]);

            DB::commit();
            return $this->actionSuccess(
                "{$type} export file created successfully.",
                [
                    "file_name" => $fileName,
                    "url" => asset("storage/{$filePath}"),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            createExceptionError($e, self::CONTROLLER_NAME, __FUNCTION__);
            return $this->actionFailure($e->getMessage());
        }
    }
}
