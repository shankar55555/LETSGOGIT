<?php

namespace Modules\Quotations\Services;

use App\Constants\CommonConst;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Invoices\Models\Invoice;
use Modules\Quotations\Models\Quotation;
use Nwidart\Modules\Facades\Module;

class QuotationService
{
    public function getPaginatedQuotations(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $status = null,
        ?string $clientId = null,
        ?string $leadId = null,
        ?string $contractId = null,
        ?string $createdBy = null,
        ?string $lastUpdatedBy = null,
        ?string $user_view_id = null,
        ?string $search = null,
    ): LengthAwarePaginator {

        $query = Quotation::query()->when($withTrashed, fn($q) => $q->withTrashed());

        # ✅ Apply custom filtering from the helper
        if (!$clientId && !$leadId) {
            $query = applyFilteringUser($query, 'created_by', $user_view_id);
        }

        # Get the relationships using the helper
        $with = onlyQuotationUserRelation();
        $query->with($with);

        return $query->when($status, fn($q) => $q->where('status', $status))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($leadId, fn($q) => $q->where('lead_id', $leadId))
            ->when($contractId, fn($q) => $q->where('contract_id', $contractId))
            ->when($createdBy, fn($q) => $q->where('created_by', $createdBy))
            ->when($lastUpdatedBy, fn($q) => $q->where('last_updated_by', $lastUpdatedBy))
            ->when($search, fn($q) => $q->search($search))
            ->with(['creator', 'updater'])
            ->latest()
            ->paginate($perPage);
    }


    public function getQuotationById(string $id): Quotation
    {
        $with = ['creator', 'updater',];
        if (Module::has(CommonConst::MODULE_LEAD)) {
            $with[] = 'leadDetail';
        }
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            $with[] = 'clientDetail';
        }
        if (Module::has(CommonConst::MODULE_INVOICE)) {
            $with[] = 'invoices';
        }
        return Quotation::with($with)->findOrFail($id);
    }

    public function createQuotation(array $data): Quotation
    {
        // Generate next quotation number
        $lastQuotation = Quotation::withTrashed()->latest('created_at')->first();
        $lastNumber = 0;
        if ($lastQuotation && preg_match('/QUO-(\d+)/', $lastQuotation->quotation_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }
        $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        $data['quotation_number'] = "QUO-{$nextNumber}";
        $totals = $this->calculateTotals($data['items'] ?? []);
        $data = array_merge($data, $totals);
        return Quotation::create($data);
    }

    public function updateQuotation(string $id, array $data): Quotation
    {
        $quotation = $this->getQuotationById($id);
        $totals = $this->calculateTotals($data['items'] ?? []);
        $data = array_merge($data, $totals);
        $quotation->update($data);
        return $quotation->fresh();
    }

    public function updateQuotationDueAmount(string $id, array $data): Quotation
    {
        $quotation = $this->getQuotationById($id);
        $quotation->update($data);
        return $quotation->fresh();
    }

    public function deleteQuotation(string $id): void
    {
        $quotation = $this->getQuotationById($id);
        $quotation->delete();
        Invoice::where('quotation_id', $id)->delete();
    }

    public function calculateTotals(array $items): array
    {
        $subTotal = collect($items)->sum('subtotal');
        $total = collect($items)->sum('total');
        $discount = collect($items)->sum('discount_amount');
        $tax = collect($items)->sum('tax_amount');


        return [
            'sub_total' => $subTotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total
        ];
    }
}
