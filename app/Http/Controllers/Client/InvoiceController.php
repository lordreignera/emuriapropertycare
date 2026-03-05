<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Property;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $propertyIds = Property::where('user_id', $user->id)->pluck('id');

        $this->syncInspectionFeeInvoices((int) $user->id, $propertyIds->all());
        $this->syncInvoicesFromCompletedInspections((int) $user->id, $propertyIds->all());

        $invoices = Invoice::with(['project.property'])
            ->where('user_id', $user->id)
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('client.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $user = Auth::user();

        if ((int) $invoice->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized invoice access.');
        }

        $invoice->load(['project.property']);

        $inspection = $this->resolveInspectionForInvoice($invoice);

        $bdcMonthly = (float) ($inspection->bdc_monthly ?? 0);
        $frlcMonthly = (float) ($inspection->frlc_monthly ?? 0);
        $fmcMonthly = (float) ($inspection->fmc_monthly ?? 0);
        $trcMonthly = (float) ($inspection->trc_monthly ?? 0);

        $scientificFinal = (float) ($inspection->scientific_final_monthly ?? 0);
        $arpEquivalentFinal = (float) ($inspection->arp_equivalent_final ?? 0);
        $basePackageFloor = (float) ($inspection->base_package_price_snapshot ?? 0);

        $invoiceTotal = (float) ($invoice->total ?? 0);
        $otherAdjustment = max(0, $invoiceTotal - $trcMonthly);

        return view('client.invoices.show', compact(
            'invoice',
            'inspection',
            'bdcMonthly',
            'frlcMonthly',
            'fmcMonthly',
            'trcMonthly',
            'scientificFinal',
            'arpEquivalentFinal',
            'basePackageFloor',
            'otherAdjustment',
            'invoiceTotal'
        ));
    }

    public function download(Invoice $invoice)
    {
        $user = Auth::user();

        if ((int) $invoice->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized invoice access.');
        }

        $invoice->load(['project.property']);
        $inspection = $this->resolveInspectionForInvoice($invoice);

        $bdcMonthly = (float) ($inspection->bdc_monthly ?? 0);
        $frlcMonthly = (float) ($inspection->frlc_monthly ?? 0);
        $fmcMonthly = (float) ($inspection->fmc_monthly ?? 0);
        $trcMonthly = (float) ($inspection->trc_monthly ?? 0);
        $invoiceTotal = (float) ($invoice->total ?? 0);
        $otherAdjustment = max(0, $invoiceTotal - $trcMonthly);

        $pdf = Pdf::loadView('client.invoices.pdf', compact(
            'invoice',
            'inspection',
            'bdcMonthly',
            'frlcMonthly',
            'fmcMonthly',
            'trcMonthly',
            'otherAdjustment',
            'invoiceTotal'
        ))
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10);

        $safeInvoiceNumber = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $invoice->invoice_number);
        $filename = 'Invoice_' . $safeInvoiceNumber . '.pdf';

        return $pdf->download($filename);
    }

    protected function syncInvoicesFromCompletedInspections(int $userId, array $propertyIds): void
    {
        if (empty($propertyIds)) {
            return;
        }

        $inspections = Inspection::with(['project', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->where('status', 'completed')
            ->whereNotNull('project_id')
            ->orderByDesc('completed_date')
            ->orderByDesc('id')
            ->get();

        foreach ($inspections as $inspection) {
            $projectId = (int) ($inspection->project_id ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $existingInvoice = Invoice::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('type', 'project')
                ->first();

            if ($existingInvoice) {
                continue;
            }

            $monthlyAmount = (float) max(
                (float) ($inspection->scientific_final_monthly ?? 0),
                (float) ($inspection->arp_equivalent_final ?? 0),
                (float) ($inspection->base_package_price_snapshot ?? 0),
                (float) ($inspection->trc_monthly ?? 0)
            );

            if ($monthlyAmount <= 0) {
                continue;
            }

            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . $inspection->id;
            $counter = 1;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . $inspection->id . '-' . $counter;
                $counter++;
            }

            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'project_id' => $projectId,
                'user_id' => $userId,
                'type' => 'project',
                'subtotal' => $monthlyAmount,
                'tax' => 0,
                'total' => $monthlyAmount,
                'paid_amount' => 0,
                'balance' => $monthlyAmount,
                'status' => 'sent',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'line_items' => [
                    [
                        'description' => 'Inspection Service - ' . ($inspection->property?->property_name ?? 'Property'),
                        'inspection_id' => $inspection->id,
                        'quantity' => 1,
                        'unit_price' => $monthlyAmount,
                        'total' => $monthlyAmount,
                    ],
                ],
                'notes' => 'Auto-generated from completed inspection #' . $inspection->id,
            ]);
        }
    }

    protected function syncInspectionFeeInvoices(int $userId, array $propertyIds): void
    {
        if (empty($propertyIds)) {
            return;
        }

        $inspections = Inspection::with(['project', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->whereNotNull('project_id')
            ->where('inspection_fee_amount', '>', 0)
            ->whereIn('inspection_fee_status', ['paid', 'pending'])
            ->orderByDesc('inspection_fee_paid_at')
            ->orderByDesc('id')
            ->get();

        foreach ($inspections as $inspection) {
            $projectId = (int) ($inspection->project_id ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $existingInvoice = Invoice::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('type', 'additional')
                ->get()
                ->first(function (Invoice $invoice) use ($inspection) {
                    return (int) data_get($invoice->line_items, '0.inspection_id') === (int) $inspection->id;
                });

            if ($existingInvoice) {
                continue;
            }

            $amount = (float) ($inspection->inspection_fee_amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $invoiceNumber = 'INV-INSP-' . now()->format('Ymd') . '-' . $inspection->id;
            $counter = 1;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-INSP-' . now()->format('Ymd') . '-' . $inspection->id . '-' . $counter;
                $counter++;
            }

            $isPaid = ($inspection->inspection_fee_status ?? 'pending') === 'paid';

            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'project_id' => $projectId,
                'user_id' => $userId,
                'type' => 'additional',
                'subtotal' => $amount,
                'tax' => 0,
                'total' => $amount,
                'paid_amount' => $isPaid ? $amount : 0,
                'balance' => $isPaid ? 0 : $amount,
                'status' => $isPaid ? 'paid' : 'sent',
                'issue_date' => optional($inspection->inspection_fee_paid_at)->toDateString() ?? now()->toDateString(),
                'due_date' => $isPaid
                    ? (optional($inspection->inspection_fee_paid_at)->toDateString() ?? now()->toDateString())
                    : now()->addDays(14)->toDateString(),
                'line_items' => [
                    [
                        'description' => 'Pre-Inspection Fee - ' . ($inspection->property?->property_name ?? 'Property'),
                        'inspection_id' => $inspection->id,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'total' => $amount,
                    ],
                ],
                'notes' => 'Auto-generated pre-inspection fee invoice for inspection #' . $inspection->id,
            ]);
        }
    }

    protected function resolveInspectionForInvoice(Invoice $invoice): ?Inspection
    {
        $inspectionId = data_get($invoice->line_items, '0.inspection_id');

        if ($inspectionId) {
            $inspection = Inspection::with(['property', 'project'])
                ->where('id', (int) $inspectionId)
                ->where('project_id', $invoice->project_id)
                ->first();

            if ($inspection) {
                return $inspection;
            }
        }

        return Inspection::with(['property', 'project'])
            ->where('project_id', $invoice->project_id)
            ->where('status', 'completed')
            ->orderByDesc('completed_date')
            ->orderByDesc('id')
            ->first();
    }
}
