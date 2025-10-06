<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounts\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create root level accounts
        $assets = Account::create([
            'name' => 'Assets',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'position' => 1
        ]);

        $liabilities = Account::create([
            'name' => 'Liabilities',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'position' => 2
        ]);

        $capitalEquity = Account::create([
            'name' => 'Capital & Equity',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'position' => 3
        ]);

        $income = Account::create([
            'name' => 'Income',
            'type' => 'Profit & Loss',
            'account_type' => 'group',
            'position' => 4
        ]);

        $expenses = Account::create([
            'name' => 'Expenses',
            'type' => 'Profit & Loss',
            'account_type' => 'group',
            'position' => 5
        ]);

        // Assets groups
        $currentAssets = Account::create([
            'name' => 'Current Assets',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'parent_id' => $assets->id,
            'position' => 1
        ]);

        $fixedAssets = Account::create([
            'name' => 'Fixed Assets',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'parent_id' => $assets->id,
            'position' => 2
        ]);

        // Current Assets Sub-groups
        $cash = Account::create([
            'name' => 'Cash',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $currentAssets->id,
            'position' => 1
        ]);

        $bankAccounts = Account::create([
            'name' => 'Bank Accounts',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $currentAssets->id,
            'position' => 2
        ]);

        $accountsReceivable = Account::create([
            'name' => 'Accounts Receivable',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $currentAssets->id,
            'position' => 3
        ]);

        // Cash ledgers
        Account::create([
            'name' => 'Cash in Hand',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $cash->id,
            'position' => 1
        ]);

        // Bank Accounts ledgers
        Account::create([
            'name' => 'PNB Bank A/c',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $bankAccounts->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'ICICI Bank A/c',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $bankAccounts->id,
            'position' => 2
        ]);

        // Accounts Receivable ledgers
        Account::create([
            'name' => 'Sundry Debtors',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $accountsReceivable->id,
            'position' => 1
        ]);

        // Fixed Assets sub-groups
        $propertyEquipment = Account::create([
            'name' => 'Property & Equipment',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $fixedAssets->id,
            'position' => 1
        ]);

        $vehicles = Account::create([
            'name' => 'Vehicles',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $fixedAssets->id,
            'position' => 2
        ]);

        // Property & Equipment ledgers
        Account::create([
            'name' => 'Computers & Peripherals',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $propertyEquipment->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Furniture & Fixtures',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $propertyEquipment->id,
            'position' => 2
        ]);

        // Vehicles ledgers
        Account::create([
            'name' => 'Company Car',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $vehicles->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Delivery Van',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $vehicles->id,
            'position' => 2
        ]);

        // Liabilities sub-groups
        $currentLiabilities = Account::create([
            'name' => 'Current Liabilities',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'parent_id' => $liabilities->id,
            'position' => 1
        ]);

        $longTermLiabilities = Account::create([
            'name' => 'Long-Term Liabilities',
            'type' => 'Balance Sheet',
            'account_type' => 'group',
            'parent_id' => $liabilities->id,
            'position' => 2
        ]);

        // Current Liabilities sub-groups
        $accountsPayable = Account::create([
            'name' => 'Accounts Payable',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $currentLiabilities->id,
            'position' => 1
        ]);

        $creditCardPayable = Account::create([
            'name' => 'Credit Card Payable',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $currentLiabilities->id,
            'position' => 2
        ]);

        // Accounts Payable ledgers
        Account::create([
            'name' => 'Sundry Creditors',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $accountsPayable->id,
            'position' => 1
        ]);

        // Credit Card Payable ledgers
        Account::create([
            'name' => 'HDFC Credit Card',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $creditCardPayable->id,
            'position' => 1
        ]);

        // Long-Term Liabilities sub-groups
        $bankLoan = Account::create([
            'name' => 'Bank Loan',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $longTermLiabilities->id,
            'position' => 1
        ]);

        // Bank Loan ledgers
        Account::create([
            'name' => 'SBI Term Loan',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $bankLoan->id,
            'position' => 1
        ]);

        // Capital & Equity sub-groups
        $capital = Account::create([
            'name' => 'Capital',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $capitalEquity->id,
            'position' => 1
        ]);

        $equity = Account::create([
            'name' => 'Equity',
            'type' => 'Balance Sheet',
            'account_type' => 'sub-group',
            'parent_id' => $capitalEquity->id,
            'position' => 2
        ]);

        // Capital ledgers
        Account::create([
            'name' => "Owner's Capital A/c",
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $capital->id,
            'position' => 1
        ]);

        // Equity ledgers
        Account::create([
            'name' => "Owner's Equity",
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $equity->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Retained Earnings',
            'type' => 'Balance Sheet',
            'account_type' => 'ledger',
            'parent_id' => $equity->id,
            'position' => 2
        ]);

        // Income sub-groups
        $salesRevenue = Account::create([
            'name' => 'Sales Revenue',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $income->id,
            'position' => 1
        ]);

        $otherIncome = Account::create([
            'name' => 'Other Income',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $income->id,
            'position' => 2
        ]);

        // Sales Revenue ledgers
        Account::create([
            'name' => 'Hoodie Sales',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $salesRevenue->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Jacket Sales',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $salesRevenue->id,
            'position' => 2
        ]);

        // Other Income ledgers
        Account::create([
            'name' => 'Interest Income',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $otherIncome->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Discount Received',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $otherIncome->id,
            'position' => 2
        ]);

        // Expenses sub-groups
        $costOfGoodsSold = Account::create([
            'name' => 'Cost of Goods Sold (COGS)',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $expenses->id,
            'position' => 1
        ]);

        $operatingExpenses = Account::create([
            'name' => 'Operating Expenses',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $expenses->id,
            'position' => 2
        ]);

        // Cost of Goods Sold ledgers
        Account::create([
            'name' => 'Purchases – Hoodies',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $costOfGoodsSold->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Purchases – Jackets',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $costOfGoodsSold->id,
            'position' => 2
        ]);

        Account::create([
            'name' => 'Freight & Import Duty',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $costOfGoodsSold->id,
            'position' => 3
        ]);

        // Operating Expenses sub-groups
        $rentExpense = Account::create([
            'name' => 'Rent Expense',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $operatingExpenses->id,
            'position' => 1
        ]);

        $salariesWages = Account::create([
            'name' => 'Salaries & Wages',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $operatingExpenses->id,
            'position' => 2
        ]);

        $utilitiesExpense = Account::create([
            'name' => 'Utilities Expense',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $operatingExpenses->id,
            'position' => 3
        ]);

        $marketingPromotion = Account::create([
            'name' => 'Marketing & Promotion',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $operatingExpenses->id,
            'position' => 4
        ]);

        $generalExpenses = Account::create([
            'name' => 'General Expenses',
            'type' => 'Profit & Loss',
            'account_type' => 'sub-group',
            'parent_id' => $operatingExpenses->id,
            'position' => 5
        ]);

        // Rent Expense ledgers
        Account::create([
            'name' => 'Office Rent',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $rentExpense->id,
            'position' => 1
        ]);

        // Salaries & Wages ledgers
        Account::create([
            'name' => 'Staff Salary',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $salariesWages->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Worker Wages',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $salariesWages->id,
            'position' => 2
        ]);

        // Utilities Expense ledgers
        Account::create([
            'name' => 'Electricity Bill',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $utilitiesExpense->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Internet Bill',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $utilitiesExpense->id,
            'position' => 2
        ]);

        // Marketing & Promotion ledgers
        Account::create([
            'name' => 'Facebook Ads',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $marketingPromotion->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Influencer Payments',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $marketingPromotion->id,
            'position' => 2
        ]);

        // General Expenses ledgers
        Account::create([
            'name' => 'Office Supplies',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $generalExpenses->id,
            'position' => 1
        ]);

        Account::create([
            'name' => 'Travel Expense',
            'type' => 'Profit & Loss',
            'account_type' => 'ledger',
            'parent_id' => $generalExpenses->id,
            'position' => 2
        ]);
    }
}
