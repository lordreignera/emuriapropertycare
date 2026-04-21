<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Property;
use App\Services\InspectionInvoiceSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(private readonly InspectionInvoiceSyncService $inspectionInvoiceSyncService)
    {
    }

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

        $bdcAnnual = (float) ($inspection->bdc_annual ?? 0);
        $frlcAnnual = (float) ($inspection->frlc_annual ?? 0);
        $fmcAnnual = (float) ($inspection->fmc_annual ?? 0);
        $trcAnnual = (float) ($inspection->trc_annual ?? 0);

        $scientificFinal = (float) ($inspection->scientific_final_monthly ?? 0);
        $arpEquivalentFinal = (float) ($inspection->arp_equivalent_final ?? 0);
        $basePackageFloor = (float) ($inspection->base_package_price_snapshot ?? 0);

        $invoiceTotal = (float) ($invoice->total ?? 0);
        $otherAdjustment = max(0, $invoiceTotal - $trcAnnual);

        return view('client.invoices.show', compact(
            'invoice',
            'inspection',
            'bdcAnnual',
            'frlcAnnual',
            'fmcAnnual',
            'trcAnnual',
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

        $bdcAnnual = (float) ($inspection->bdc_annual ?? 0);
        $frlcAnnual = (float) ($inspection->frlc_annual ?? 0);
        $fmcAnnual = (float) ($inspection->fmc_annual ?? 0);
        $trcAnnual = (float) ($inspection->trc_annual ?? 0);
        $invoiceTotal = (float) ($invoice->total ?? 0);
        $otherAdjustment = max(0, $invoiceTotal - $trcAnnual);

        $pdf = Pdf::loadView('client.invoices.pdf', compact(
            'invoice',
            'inspection',
            'bdcAnnual',
            'frlcAnnual',
            'fmcAnnual',
            'trcAnnual',
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
            $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection);
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
            $this->inspectionInvoiceSyncService->syncInspectionFeeInvoice($inspection);
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
