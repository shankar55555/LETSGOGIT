<?php

namespace Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Accounts\Models\JournalEntry;
use Modules\Accounts\Http\Requests\StoreJournalEntryRequest;
use Modules\Accounts\Http\Requests\UpdateJournalEntryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = JournalEntry::query();

            // Apply filters - use filled() instead of has() for better empty value handling
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->filled('voucher_type')) {
                $query->byVoucherType($request->voucher_type);
            }

            // Date range - check if both dates are filled
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('entry_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('voucher_type', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'entry_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $journalEntries = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $journalEntries,
                'message' => 'Journal entries retrieved successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal entries.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        try {
            $journalEntry = JournalEntry::create($request->validated());
            // Refresh to get the appended attributes
            $journalEntry = $journalEntry->fresh();

            return response()->json([
                'success' => true,
                'data' => $journalEntry,
                'message' => 'Journal entry created successfully.'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal entry.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified journal entry.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $journalEntry = JournalEntry::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $journalEntry,
                'message' => 'Journal entry retrieved successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal entry.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified journal entry.
     */
    public function update(UpdateJournalEntryRequest $request, string $id): JsonResponse
    {
        try {
            $journalEntry = JournalEntry::findOrFail($id);

            // Check if entry is approved and prevent modification
            if ($journalEntry->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify approved journal entries.'
                ], 422);
            }

            $journalEntry->update($request->validated());
            // Refresh to get the updated data with appended attributes
            $journalEntry = $journalEntry->fresh();

            return response()->json([
                'success' => true,
                'data' => $journalEntry,
                'message' => 'Journal entry updated successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal entry.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified journal entry.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $journalEntry = JournalEntry::findOrFail($id);

            // Check if entry is approved and prevent deletion
            if ($journalEntry->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete approved journal entries.'
                ], 422);
            }

            $journalEntry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal entry deleted successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete journal entry.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update journal entry status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        try {
            $journalEntry = JournalEntry::findOrFail($id);
            $journalEntry->update(['status' => $request->status]);
            // Refresh to get the updated data with appended attributes
            $journalEntry = $journalEntry->fresh();

            return response()->json([
                'success' => true,
                'data' => $journalEntry,
                'message' => 'Journal entry status updated successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal entry status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get journal entry statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_entries' => JournalEntry::count(),
                'pending_entries' => JournalEntry::byStatus('pending')->count(),
                'approved_entries' => JournalEntry::byStatus('approved')->count(),
                'rejected_entries' => JournalEntry::byStatus('rejected')->count(),
                'total_debit_amount' => JournalEntry::sum('total_debit'),
                'total_credit_amount' => JournalEntry::sum('total_credit'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}