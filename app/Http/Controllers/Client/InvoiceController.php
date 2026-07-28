<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Property;
use App\Services\InspectionInvoiceSyncService;
use App\Services\InvoicePaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InspectionInvoiceSyncService $inspectionInvoiceSyncService,
        private readonly InvoicePaymentService $invoicePaymentService,
    ) {
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

    public function payment(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

        if (($invoice->status ?? null) === 'paid' || (float) ($invoice->balance ?? 0) <= 0) {
            return redirect()->route('client.invoices.show', $invoice)
                ->with('info', 'This invoice is already fully paid.');
        }

        $plan = $request->query('plan', 'full');
        if (!in_array($plan, ['30', '50', 'full'], true)) {
            $plan = 'full';
        }

        $balance = round((float) ($invoice->balance ?? $invoice->total ?? 0), 2);
        $total = round((float) ($invoice->total ?? 0), 2);
        $chargeAmount = match ($plan) {
            '30' => round(min($balance, $total * 0.30), 2),
            '50' => round(min($balance, $total * 0.50), 2),
            default => $balance,
        };

        if ($chargeAmount <= 0) {
            return redirect()->route('client.invoices.show', $invoice)
                ->with('error', 'This invoice has no payable balance.');
        }

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($chargeAmount * 100),
            'currency' => strtolower((string) config('cashier.currency', 'usd')),
            'metadata' => [
                'payment_type' => 'invoice_payment',
                'invoice_id' => $invoice->id,
                'project_id' => $invoice->project_id,
                'property_id' => $invoice->project?->property_id,
                'client_user_id' => Auth::id(),
                'plan' => $plan,
            ],
        ]);

        return view('client.invoices.payment', [
            'invoice' => $invoice->loadMissing('project.property'),
            'plan' => $plan,
            'total' => $total,
            'balance' => $balance,
            'chargeAmount' => $chargeAmount,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    public function processPayment(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'plan' => ['required', 'in:30,50,full'],
        ]);

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if (($paymentIntent->status ?? null) !== 'succeeded') {
                throw new \RuntimeException('Payment not completed.');
            }

            if ((int) ($paymentIntent->metadata->invoice_id ?? 0) !== (int) $invoice->id) {
                throw new \RuntimeException('Payment reference does not match this invoice.');
            }

            $amount = round(((float) (($paymentIntent->amount_received ?? 0) ?: ($paymentIntent->amount ?? 0))) / 100, 2);
            $invoice = $this->invoicePaymentService->apply($invoice, $amount, $paymentIntent->id);

            return response()->json([
                'success' => true,
                'redirect' => route('client.invoices.show', $invoice),
                'message' => $invoice->status === 'paid'
                    ? 'Invoice paid successfully.'
                    : 'Partial payment received successfully.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again.',
            ], 400);
        }
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
            $hasFactsDiagnosisInvoice = Invoice::where('project_id', $inspection->project_id)
                ->where('user_id', $userId)
                ->where('type', 'additional')
                ->get()
                ->contains(function (Invoice $invoice) {
                    return collect($invoice->line_items ?? [])
                        ->contains(fn ($item) => in_array(($item['purpose'] ?? null), ['property_facts', 'property_diagnosis'], true));
                });

            if ($hasFactsDiagnosisInvoice) {
                continue;
            }

            $this->inspectionInvoiceSyncService->syncInspectionFeeInvoice($inspection);
        }
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        if ((int) $invoice->user_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized invoice access.');
        }

        $invoice->loadMissing(['project.property']);
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
