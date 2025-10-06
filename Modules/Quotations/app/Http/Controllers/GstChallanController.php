<?php

namespace Modules\Quotations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Quotations\Models\Quotation;
use Modules\Quotations\Services\GstChallanService;
use NumberToWords\NumberToWords;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class GstChallanController extends Controller
{
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'challanDate' => 'required|date',
            'gstin' => 'required',
            'quotation_id' => 'required|exists:quotations,id',
            'stateType' => 'required|in:intra,inter',
        ]);

        $quotation = Quotation::with(['clientDetail', 'leadDetail'])
            ->findOrFail($validated['quotation_id']);

        $challanData = $this->prepareChallanData($validated, $quotation);
        $pdf = $this->generatePdf($challanData, $quotation);

        return $pdf->download($challanData['challan_number'] . '.pdf');
    }

    protected function prepareChallanData(array $validated, Quotation $quotation): array
    {
        $challanNumber = 'GST-' . $quotation->quotation_number;
        $challanDate = Carbon::parse($validated['challanDate'])->format('d-F-Y');

        $itemsData = $this->processQuotationItems(
            $quotation->items,
            $validated['stateType'],
            $challanNumber
        );

        return [
            'items' => $itemsData['items'],
            'grand_total' => $itemsData['grandTotal'],
            'grand_subtotal' => $itemsData['grandSubtotal'],
            'challan_number' => $challanNumber,
            'challan_date' => $challanDate,
            'gstNumber' => $validated['gstin'],
            'amount_in_words' => $this->getAmountInWords($itemsData['grandTotal']),
            'settings' => Setting::pluck('value', 'key')->all(),
        ];
    }

    protected function processQuotationItems(array $items, string $stateType, string $challanNumber): array
    {
        $grandTotal = 0;
        $grandSubtotal = 0;

        $processedItems = collect($items)->map(function ($item, $key) use ($stateType, $challanNumber, &$grandTotal, &$grandSubtotal) {
            $subtotal = (float) $item['subtotal'];
            $discountAmount = (float) $item['discount_amount'];
            $taxableAmount = $subtotal - $discountAmount;

            $taxes = $this->calculateTaxes(
                $taxableAmount,
                (float) $item['tax_rate'],
                $stateType
            );

            $total = $taxableAmount + $taxes['totalTax'];
            $grandTotal += $total;
            $grandSubtotal += $subtotal;

            return [
                'ser_no' => $key,
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_rate' => $item['discount_rate'],
                'state_type' => $stateType,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'taxable_amount' => $taxableAmount,
                'challan_number' => $challanNumber,
                'hsn_code' => $item['hsn_code'] ?? 'N/A',
                'cgst' => $taxes['cgst'],
                'sgst' => $taxes['sgst'],
                'igst' => $taxes['igst'],
                'tax_rate' => (float) $item['tax_rate'],
                'total_tax' => $taxes['totalTax'],
                'total' => $total,
            ];
        });

        return [
            'items' => $processedItems->toArray(),
            'grandTotal' => $grandTotal,
            'grandSubtotal' => $grandSubtotal,
        ];
    }

    protected function generatePdf(array $challanData, Quotation $quotation)
    {


        return Pdf::loadView('pdf.gst_challan', [
            'challan' => $challanData,
            'settings' => $challanData['settings'],
            'quotation' => $quotation,
        ]);
    }

    protected function getAmountInWords(float $amount): string
    {
        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer('en');

        return ucwords($transformer->toWords($amount) . ' Rupees Only');
    }

    protected function calculateTaxes(float $taxableAmount, float $taxRate, string $stateType): array
    {
        $cgst = $sgst = $igst = $totalTax = 0;

        if ($stateType === 'intra') {
            $halfRate = $taxRate / 2;
            $cgst = $sgst = ($taxableAmount * $halfRate) / 100;
            $totalTax = $cgst + $sgst;
        } else {
            $igst = ($taxableAmount * $taxRate) / 100;
            $totalTax = $igst;
        }

        return compact('cgst', 'sgst', 'igst', 'totalTax');
    }
}
