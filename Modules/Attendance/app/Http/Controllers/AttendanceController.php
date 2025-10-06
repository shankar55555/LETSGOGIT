<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Routing\Controller;
use Modules\Attendance\Http\Resources\AttendanceResource;
use Modules\Attendance\Services\AttendanceService;
use Symfony\Component\HttpFoundation\Response;
use Jenssegers\Agent\Agent;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }
    public function index(Request $request): JsonResponse
    {
        $records = $this->attendanceService->getAllRecords([
            'date' => $request->date,
            'user_id' => $request->user_id,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 15)
        ]);
        return response()->json([
            'data' => AttendanceResource::collection($records),
            'meta' => $this->buildPaginationMeta($records),
            'status' => Response::HTTP_OK
        ]);
    }
    public function records(Request $request): JsonResponse
    {
        $records = $this->attendanceService->getUserRecords([
            'date' => $request->date,
            'per_page' => $request->integer('per_page', 10)
        ]);
        return response()->json([
            'data' => AttendanceResource::collection($records),
            'meta' => $this->buildPaginationMeta($records),
            'status' => Response::HTTP_OK
        ]);
    }
    public function login(Request $request): JsonResponse
    {
        try {
            $agent = new Agent();
            $device_info = [
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location_coordinates' => $request->location_coordinates,
            ];
            // Check if user already has an active session
            if ($this->attendanceService->getActiveSession()) {
                return response()->json([
                    'message' => __('You already have an active session'),
                    'status' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }
            // Create new attendance record
            $attendance = $this->attendanceService->createAttendance([
                'device_info' => $device_info
            ]);
            return response()->json([
                'message' => __('Login recorded successfully'),
                'data' => new AttendanceResource($attendance),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Failed to record login'),
                'error' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function logout(Request $request): JsonResponse
    {
        try {
            $agent = new Agent();
            $device_info = [
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location_coordinates' => $request->location_coordinates,
            ];
            $attendance = $this->attendanceService->recordLogout([
                'device_info' => $device_info,
            ]);
            return response()->json([
                'message' => __('Logout recorded successfully'),
                'data' => new AttendanceResource($attendance),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Failed to record logout'),
                'error' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                // 'user_id' => 'required|exists:users,id',
                'login_time' => 'required|date',
                'logout_time' => 'nullable|date|after:login_time',
                'color' => 'nullable|string'
            ]);

            $attendance = $this->attendanceService->createManualAttendance(array_merge($validated, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_manual' => true
            ]));
            return response()->json([
                'message' => __('Attendance recorded successfully'),
                'data' => new AttendanceResource($attendance),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('Validation failed'),
                'errors' => $e->errors(),
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Failed to record attendance'),
                'error' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'login_time' => 'sometimes|date',
                'logout_time' => 'sometimes|date|after:login_time',
                'status' => 'sometimes|in:active,in-active,break',
            ]);
            $attendance = $this->attendanceService->updateAttendance($id, $validated);
            return response()->json([
                'message' => __('Attendance updated successfully'),
                'data' => new AttendanceResource($attendance),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Failed to update attendance'),
                'error' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id): JsonResponse
    {
        try {
            $this->attendanceService->deleteAttendance($id);
            return response()->json([
                'message' => __('Attendance deleted successfully'),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Failed to delete attendance'),
                'error' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    protected function buildPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
