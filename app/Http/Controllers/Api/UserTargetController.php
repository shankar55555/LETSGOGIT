<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTargetController extends Controller
{
    public function index(Request $request)
    {
        $query = UserTarget::query();

        # Filter by search query
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('target_amount', 'ILIKE', "%{$search}%")
                    ->orWhere('incentive', 'ILIKE', "%{$search}%");
            });
        }

        $paginated = $query->where('user_id', $request->user_id ?? Auth::user()->uuid)
            ->with('user')
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Targets retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,uuid',
            'target_amount' => 'required|numeric|min:0',
            'incentive_percentage' => 'required|numeric|min:0',
            'month' => 'required',
            'achieved_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value > $request->target_amount) {
                        $fail('The achieved amount may not be greater than the target amount.');
                    }
                },
            ],
        ]);

        if (UserTarget::where('month', $request->month . '-01')->where('user_id', $request->user_id)->exists()) {
            return $this->actionFailure('This Month record already exist!');
        }


        $incentiveAmount = null;
        if ($request->filled('achieved_amount')) {
            $incentiveAmount = ($request->achieved_amount * $request->incentive_percentage) / 100;
        }
        $data = UserTarget::create([
            'user_id' => $request->user_id,
            'target_amount' => $request->target_amount,
            'incentive_percentage' => $request->incentive_percentage, // Admin-allowed percentage
            'achieved_amount' => $request->achieved_amount, // Calculated from achieved_amount
            'incentive' => $incentiveAmount, // Store the actual percentage
            'month' => $request->month . '-01', // Normalize to first day of the month
            'is_paid' => false,
        ]);

        return $this->actionSuccess('Target set successfully!', $data);
    }

    public function update(Request $request, UserTarget $userTarget)
    {
        $request->validate([
            'user_id' => 'required|exists:users,uuid',
            'target_amount' => 'required|numeric|min:0',
            'incentive_percentage' => 'required|numeric|min:0',
            'month' => 'required|date_format:Y-m',
            'achieved_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value > $request->target_amount) {
                        $fail('The incentive amount may not be greater than the target amount.');
                    }
                },
            ],
        ]);

        if (UserTarget::where('id', '!=', $request->id)->where('month', $request->month . '-01')->where('user_id', $request->user_id)->exists()) {
            return $this->actionFailure('This Month record already exist!');
        }

        $incentiveAmount = null;

        if ($request->filled('achieved_amount')) {
            $incentiveAmount = ($request->achieved_amount * $request->incentive_percentage) / 100;
        }

        $data = UserTarget::where('id', $request->id)->update([
            'target_amount' => $request->target_amount,
            'incentive_percentage' => $request->incentive_percentage, // Admin-allowed percentage
            'achieved_amount' => $request->achieved_amount, // Calculated from achieved_amount
            'incentive' => $incentiveAmount, // Store the actual percentage
            'month' => $request->month . '-01', // Normalize to first day of the month
        ]);

        return $this->actionSuccess('Target updated successfully!', $data);
    }

    public function updateIncentiveAmount(Request $request, UserTarget $userTarget)
    {
        $request->validate([
            'achieved_amount' => 'required|numeric|min:0',
        ]);

        $userTarget = UserTarget::findOrFail($request->id);
        // Get stored values from DB
        $targetAmount = $userTarget->target_amount;
        $applyPercentage = $userTarget->incentive_percentage;

        // Calculate applied amount
        $appliedAmount = ($targetAmount * $applyPercentage) / 100;

        // Update record
        $userTarget->update([
            'achieved_amount' => (int) $request->achieved_amount,
            'incentive_percentage' => (int) ($request->achieved_amount / $appliedAmount) * 100,
        ]);

        return $this->actionSuccess('Incentive percentage updated successfully!');
    }

    public function markAsPaid(Request $request)
    {
        $target = UserTarget::findOrFail($request->id);
        $target->update(['is_paid' => $request->is_paid]);
        return $this->actionSuccess('Incentive marked as' . $request->is_paid ? 'paid' : 'unpaid');
    }
}
