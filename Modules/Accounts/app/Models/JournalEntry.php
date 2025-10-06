<?php

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use Modules\Accounts\Models\Account;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'voucher_type',
        'description',
        'status',
        'total_debit',
        'total_credit',
        'debit_entries',
        'credit_entries'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit_entries' => 'array',
        'credit_entries' => 'array',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2'
    ];

    protected $dates = [
        'entry_date'
    ];

    protected $appends = [
        'debit_entries_with_accounts',
        'credit_entries_with_accounts'
    ];

    /**
     * Generate unique entry number
     */
    public static function generateEntryNumber(): string
    {
        $year = Carbon::now()->year;
        $lastEntry = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastEntry ?
            (int) substr($lastEntry->entry_number, -4) + 1 : 1;

        return 'JRNL-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total debit amount from debit entries
     */
    public function calculateTotalDebit(): float
    {
        if (!$this->debit_entries) return 0;

        return collect($this->debit_entries)->sum('amount');
    }

    /**
     * Calculate total credit amount from credit entries
     */
    public function calculateTotalCredit(): float
    {
        if (!$this->credit_entries) return 0;

        return collect($this->credit_entries)->sum('amount');
    }

    /**
     * Check if journal entry is balanced
     */
    public function isBalanced(): bool
    {
        return abs($this->calculateTotalDebit() - $this->calculateTotalCredit()) < 0.01;
    }

    /**
     * Get formatted entry date
     */
    public function getFormattedEntryDateAttribute(): string
    {
        return $this->entry_date->format('d-M-y');
    }

    /**
     * Get formatted total debit
     */
    public function getFormattedTotalDebitAttribute(): string
    {
        return '₹' . number_format($this->total_debit, 2);
    }

    /**
     * Get formatted total credit
     */
    public function getFormattedTotalCreditAttribute(): string
    {
        return '₹' . number_format($this->total_credit, 2);
    }

    /**
     * Get debit entries with account details
     */
    public function getDebitEntriesWithAccountsAttribute()
    {
        if (!$this->debit_entries) return [];

        return collect($this->debit_entries)->map(function ($entry) {
            $account = Account::find($entry['account_id']);
            return [
                'account_id' => $entry['account_id'],
                'amount' => $entry['amount'],
                'account' => $account ? [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'account_type' => $account->account_type
                ] : null
            ];
        })->toArray();
    }

    /**
     * Get credit entries with account details
     */
    public function getCreditEntriesWithAccountsAttribute()
    {
        if (!$this->credit_entries) return [];

        return collect($this->credit_entries)->map(function ($entry) {
            $account = Account::find($entry['account_id']);
            return [
                'account_id' => $entry['account_id'],
                'amount' => $entry['amount'],
                'account' => $account ? [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'account_type' => $account->account_type
                ] : null
            ];
        })->toArray();
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by voucher type
     */
    public function scopeByVoucherType($query, $voucherType)
    {
        return $query->where('voucher_type', $voucherType);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    /**
     * Boot method to auto-generate entry number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journalEntry) {
            if (!$journalEntry->entry_number) {
                $journalEntry->entry_number = self::generateEntryNumber();
            }

            // Auto-calculate totals
            $journalEntry->total_debit = $journalEntry->calculateTotalDebit();
            $journalEntry->total_credit = $journalEntry->calculateTotalCredit();
        });

        static::updating(function ($journalEntry) {
            // Auto-calculate totals on update
            $journalEntry->total_debit = $journalEntry->calculateTotalDebit();
            $journalEntry->total_credit = $journalEntry->calculateTotalCredit();
        });
    }
}
