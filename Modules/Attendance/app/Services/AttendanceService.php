<?php

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\Attendance;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AttendanceService
{
    public function createAttendance(array $data): Attendance
    {
        // Manually override in your code (temporary debug)
        date_default_timezone_set('Asia/Kolkata');
        return Attendance::create([
            'user_id' => Auth::user()->uuid,
            'attendance_date' => now()->toDateString(),
            'session_token' => Str::uuid(),
            'login_time' => now()->format('h:i:s A'),
            'device_info' => $data['device_info'] ?? null,
        ]);
    }
    public function recordLogout(array $data = []): Attendance
    {
        date_default_timezone_set('Asia/Kolkata');
        $attendance = $this->getActiveSession();
        if (!$attendance) {
            throw new \Exception('No active session found');
        }
        return $this->updateAttendance($attendance->id, [
            'logout_time' => now()->format('h:i:s A'),
            'device_info' => $data['device_info'] ?? null,
        ]);
    }
    public function updateAttendance(string $id, array $data): Attendance
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update($data);
        return $attendance->fresh();
    }
    public function getActiveSession(): ?Attendance
    {
        return Attendance::where('user_id', Auth::user()->uuid)
            ->whereNull('logout_time')
            ->first();
    }
    public function getUserRecords(array $filters = [])
    {
        return Attendance::where('user_id', Auth::user()->uuid)
            ->when(isset($filters['date']), fn($q) => $q->whereDate('attendance_date', $filters['date']))
            ->orderBy('login_time', 'desc')
            // ->with(['user'])
            ->paginate(10);
    }
    public function getAllRecords(array $filters = [])
    {
        return Attendance::query()
            ->when(isset($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['date']), fn($q) => $q->whereDate('attendance_date', $filters['date']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('ip_address', 'like', "%{$filters['search']}%")
                        ->orWhere('user_agent', 'like', "%{$filters['search']}%")
                        ->orWhereHas('user', function ($userQuery) use ($filters) {
                            $userQuery->where('name', 'like', "%{$filters['search']}%");
                        });
                });
            })
            ->orderBy('login_time', 'desc')
            // ->with(['user'])
            ->paginate($filters['per_page'] ?? 15);
    }
    public function createManualAttendance(array $data): Attendance
    {
        // Validate required fields
        // if (!isset($data['user_id'])) {
        //     throw new \InvalidArgumentException('User ID is required');
        // }
        // Parse login date
        $loginDate = isset($data['login_time'])
            ? Carbon::parse($data['login_time'])->toDateString()
            : now()->toDateString();
        // Check for existing attendance for this user on the same date
        $existingAttendance = Attendance::where('user_id',  Auth::user()->uuid)
            ->whereDate('attendance_date', $loginDate)
            ->exists();
        if ($existingAttendance) {
            throw new \RuntimeException('Attendance already exists for this user on the selected date', Response::HTTP_CONFLICT);
        }
        // Determine status
        $status = isset($data['logout_time'])
            ? 'in-active'
            : ($data['status'] ?? 'active');
        // Determine color based on status
        $color = $data['color'] ?? match ($status) {
            'break' => '#f59e0b',
            'inactive' => '#ef4444',
            default => '#3b82f6'
        };
        // Create the attendance record
        return Attendance::create([
            'user_id' => $data['user_id'],
            'attendance_date' => $loginDate,
            'session_token' => Str::uuid(),
            'login_time' => $data['login_time'] ?? now()->toDateTimeString(),
            'logout_time' => $data['logout_time'] ?? null,
            'status' => $status,
            'color' => $color,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'is_manual' => true,
            'notes' => $data['notes'] ?? null,
            'device_info' => $data['device_info'] ?? null
        ]);
    }
    // public function createManualAttendance(array $data): Attendance
    // {
    //     // Default status is 'active', but if logout_time is present, status becomes 'in-active'
    //     $status = isset($data['logout_time']) ? 'in-active' : (isset($data['status']) ? $data['status'] : 'active');
    //     // Ensure color is determined correctly, based on the status or fallback to default color
    //     $color = $data['color'] ?? (isset($data['status']) ? ($data['status'] === 'break' ? '#f59e0b' : '#3b82f6') : '#3b82f6');
    //     // Create the attendance record
    //     return Attendance::create([
    //         'user_id' => Auth::id(), // Ensure the user is authenticated
    //         'attendance_date' => $data['login_time'] ? date('Y-m-d', strtotime($data['login_time'])) : now()->toDateString(),
    //         'session_token' => Str::uuid(),
    //         'login_time' => $data['login_time'],
    //         'logout_time' => $data['logout_time'] ?? null,
    //         'ip_address' => $data['ip_address'] ?? null,
    //         'user_agent' => $data['user_agent'] ?? null,
    //         'is_manual' => true,
    //         'notes' => $data['notes'] ?? null
    //     ]);
    // }
    public function deleteAttendance(int $id): bool
    {
        $attendance = Attendance::findOrFail($id);
        return $attendance->delete();
    }
}
