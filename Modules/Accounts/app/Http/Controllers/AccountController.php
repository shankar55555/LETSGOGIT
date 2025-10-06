<?php

namespace Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounts\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts in hierarchical structure
     */
    public function index(): JsonResponse
    {
        $accounts = Account::with('descendants')
            ->roots()
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $this->formatAccountsForTree($accounts)
        ]);
    }

    /**
     * Get all accounts for parent selection dropdown
     */
    public function getParentOptions(): JsonResponse
    {
        $accounts = Account::groups()
            ->with('parent')
            ->orderBy('name')
            ->get();

        $options = $this->buildParentOptions($accounts);

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Get all ledger accounts for journal entries
     */
    public function getLedgers(): JsonResponse
    {
        $ledgers = Account::ledgers()
            ->with('parent')
            ->orderBy('name')
            ->get();

        $formattedLedgers = $ledgers->map(function ($ledger) {
            return [
                'id' => $ledger->id,
                'name' => $ledger->name,
                'type' => $ledger->type,
                'account_type' => $ledger->account_type,
                'parent_name' => $ledger->parent ? $ledger->parent->name : null,
                'full_name' => $ledger->parent ? $ledger->parent->name . ' > ' . $ledger->name : $ledger->name
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedLedgers
        ]);
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Balance Sheet,Profit & Loss',
            'account_type' => 'required|string|in:group,ledger',
            'parent_id' => 'nullable|exists:accounts,id',
            'position' => 'nullable|integer|min:1',
            'description' => 'nullable|string'
        ]);

        // If no position specified, get next available position
        if (!isset($validated['position']) && $validated['parent_id']) {
            $parent = Account::find($validated['parent_id']);
            $validated['position'] = $parent->getNextChildPosition();
        } elseif (!isset($validated['position'])) {
            $validated['position'] = Account::roots()->max('position') + 1;
        }

        // If parent is specified, inherit the type from parent
        if ($validated['parent_id']) {
            $parent = Account::find($validated['parent_id']);
            $validated['type'] = $parent->type;
        }

        $account = Account::create($validated);
        $account->load('parent', 'children');

        return response()->json([
            'success' => true,
            'message' => ucfirst($account->account_type) . ' created successfully',
            'data' => $account
        ], 201);
    }

    /**
     * Display the specified account
     */
    public function show(Account $account): JsonResponse
    {
        $account->load('parent', 'children');

        return response()->json([
            'success' => true,
            'data' => $account
        ]);
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:accounts,id',
                Rule::notIn([$account->id]) // Prevent self-reference
            ],
            'position' => 'nullable|integer|min:1',
            'description' => 'nullable|string'
        ]);

        // Prevent circular references
        if ($validated['parent_id'] && $this->wouldCreateCircularReference($account, $validated['parent_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move account: would create circular reference'
            ], 422);
        }

        // If parent changed, inherit type from new parent
        if (isset($validated['parent_id']) && $validated['parent_id'] !== $account->parent_id) {
            if ($validated['parent_id']) {
                $newParent = Account::find($validated['parent_id']);
                $validated['type'] = $newParent->type;
            }
        }

        $account->update($validated);
        $account->load('parent', 'children');

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    /**
     * Remove the specified account
     */
    public function destroy(Account $account): JsonResponse
    {
        // Check if account has children
        if ($account->hasChildren()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with child accounts. Please delete or move child accounts first.'
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get balance sheet data with calculated balances
     */
    public function getBalanceSheet()
    {
        try {
            // Get all Balance Sheet accounts with their hierarchy
            $accounts = Account::where('type', 'Balance Sheet')
                ->whereNull('parent_id')
                ->with(['descendants'])
                ->orderBy('position')
                ->get();

            $balanceSheetData = [
                'assets' => [],
                'liabilities' => []
            ];

            foreach ($accounts as $account) {
                $category = $account->getAccountCategory();
                $accountData = $this->buildAccountHierarchyWithBalances($account);

                if ($category === 'Assets') {
                    $balanceSheetData['assets'][] = $accountData;
                } elseif (in_array($category, ['Liabilities', 'Capital & Equity'])) {
                    $balanceSheetData['liabilities'][] = $accountData;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $balanceSheetData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance sheet data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build account hierarchy with calculated balances
     */
    private function buildAccountHierarchyWithBalances($account)
    {
        $currentBalance = $account->calculateBalance();
        
        // Calculate previous period balance (for demo, using 80% of current as previous)
        $previousBalance = $currentBalance * 0.8;
        
        // Calculate percentage change
        $changePercent = 0;
        if ($previousBalance != 0) {
            $changePercent = (($currentBalance - $previousBalance) / abs($previousBalance)) * 100;
        } elseif ($currentBalance != 0) {
            $changePercent = 100; // If previous was 0 and current is not, it's 100% increase
        }
        
        $changeFormatted = number_format($changePercent, 1) . '%';
        if ($changePercent > 0) {
            $changeFormatted = '+' . $changeFormatted;
        }

        $accountData = [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->account_type,
            'current' => $currentBalance,
            'currentFormatted' => '₹' . number_format($currentBalance, 2, '.', ','),
            'previous' => $previousBalance,
            'previousFormatted' => '₹' . number_format($previousBalance, 2, '.', ','),
            'change' => $changeFormatted,
            'changeFormatted' => $changeFormatted,
            'percent' => '0.0%', // TODO: Calculate percentage of total
            'children' => []
        ];

        // Recursively build children
        if ($account->children && $account->children->count() > 0) {
            foreach ($account->children as $child) {
                $accountData['children'][] = $this->buildAccountHierarchyWithBalances($child);
            }
        }

        return $accountData;
    }

    /**
     * Format accounts for tree structure
     */
    private function formatAccountsForTree($accounts)
    {
        return $accounts->map(function ($account) {
            return [
                'id' => (string) $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'account_type' => $account->account_type,
                'children' => $account->children->isNotEmpty()
                    ? $this->formatAccountsForTree($account->children)
                    : ($account->account_type === 'ledger' ? null : [])
            ];
        });
    }

    /**
     * Build parent options for dropdown
     */
    private function buildParentOptions($accounts, $level = 0)
    {
        $options = [];

        foreach ($accounts as $account) {
            $indent = str_repeat('— ', $level);
            $options[] = [
                'title' => $indent . $account->name,
                'value' => $account->id
            ];

            if ($account->children->isNotEmpty()) {
                $childOptions = $this->buildParentOptions($account->children, $level + 1);
                $options = array_merge($options, $childOptions);
            }
        }

        return $options;
    }

    /**
     * Check if moving an account would create a circular reference
     */
    private function wouldCreateCircularReference(Account $account, $newParentId)
    {
        if (!$newParentId) {
            return false;
        }

        $newParent = Account::find($newParentId);

        // Check if new parent is a descendant of the account being moved
        while ($newParent) {
            if ($newParent->id === $account->id) {
                return true;
            }
            $newParent = $newParent->parent;
        }

        return false;
    }

    /**
     * Get profit and loss data
     */
    public function getProfitAndLoss()
    {
        try {
            // Get all income and expense accounts with their hierarchical structure
            $accounts = Account::with(['children' => function ($query) {
                $query->orderBy('name');
            }])
                ->whereNull('parent_id')
                ->whereIn('name', ['Income', 'Expenses'])
                ->orderBy('name')
                ->get();

            $profitLossData = [
                'income' => [],
                'expenses' => []
            ];

            foreach ($accounts as $account) {
                $category = $account->getAccountCategory();
                $accountData = $this->buildAccountHierarchyWithBalances($account);

                if ($category === 'Income') {
                    $profitLossData['income'][] = $accountData;
                } elseif ($category === 'Expenses') {
                    $profitLossData['expenses'][] = $accountData;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $profitLossData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profit and loss data: ' . $e->getMessage()
            ], 500);
        }
    }
}
