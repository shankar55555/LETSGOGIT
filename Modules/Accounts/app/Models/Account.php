<?php

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'account_type',
        'parent_id',
        'position',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer'
    ];

    /**
     * Get the parent account
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope to get only groups
     */
    public function scopeGroups($query)
    {
        return $query->where('account_type', 'group');
    }

    /**
     * Scope to get only ledgers
     */
    public function scopeLedgers($query)
    {
        return $query->where('account_type', 'ledger');
    }

    /**
     * Scope to get root accounts (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full hierarchical path
     */
    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }

    /**
     * Check if this account has children
     */
    public function hasChildren()
    {
        return $this->children()->exists();
    }

    /**
     * Get next position for new child
     */
    public function getNextChildPosition()
    {
        return $this->children()->max('position') + 1;
    }

    /**
     * Calculate account balance from journal entries
     */
    public function calculateBalance()
    {
        // Get all journal entries and calculate totals
        $journalEntries = \Modules\Accounts\Models\JournalEntry::all();

        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($journalEntries as $entry) {
            // Calculate debit total for this account
            if ($entry->debit_entries) {
                $debitEntries = collect($entry->debit_entries);
                $debitTotal += $debitEntries->where('account_id', $this->id)->sum('amount');
            }

            // Calculate credit total for this account
            if ($entry->credit_entries) {
                $creditEntries = collect($entry->credit_entries);
                $creditTotal += $creditEntries->where('account_id', $this->id)->sum('amount');
            }
        }

        // For assets: Debit increases balance (Debit - Credit)
        // For liabilities and equity: Credit increases balance (Credit - Debit)
        $category = $this->getAccountCategory();

        if ($category === 'Assets') {
            return $debitTotal - $creditTotal;
        } else {
            return $creditTotal - $debitTotal;
        }
    }

    /**
     * Get account category based on account hierarchy
     */
    public function getAccountCategory()
    {
        // Get root parent to determine category
        $root = $this;
        while ($root->parent) {
            $root = $root->parent;
        }

        // Map root account names to categories
        $categoryMap = [
            'Assets' => 'Assets',
            'Liabilities' => 'Liabilities',
            'Capital & Equity' => 'Liabilities', // Equity is treated as liability for balance calculation
            'Income' => 'Income',
            'Expenses' => 'Expenses'
        ];

        return $categoryMap[$root->name] ?? 'Unknown';
    }
}
