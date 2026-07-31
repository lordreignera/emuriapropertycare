<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyProcessInvoiceService
{
    private const TAX_RATE = 0.05;

    public function draftForProperty(Property $property): Invoice
    {
        return DB::transaction(function () use ($property) {
            $property->loadMissing([
                'user',
                'projects.invoices',
                'inspections.pharFindings',
                'spatialModels',
                'matterportModels',
            ]);

            $project = $this->resolveProject($property);
            $existing = $this->resolveExistingInvoice($property, $project);
            $paidAmount = round((float) ($existing->paid_amount ?? 0), 2);
            $lineItems = $this->buildLineItems($property, $project, $existing);
            $subtotal = round(collect($lineItems)->sum(fn ($item) => (float) ($item['amount'] ?? $item['total'] ?? 0)), 2);
            $tax = round($subtotal * self::TAX_RATE, 2);
            $total = round($subtotal + $tax, 2);
            $balance = max(0, round($total - $paidAmount, 2));

            $attributes = [
                'project_id' => $project->id,
                'user_id' => $property->user_id,
                'type' => 'additional',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $this->resolveStatus($existing, $balance, $paidAmount),
                'issue_date' => optional($existing?->issue_date)->toDateString() ?? now()->toDateString(),
                'due_date' => optional($existing?->due_date)->toDateString() ?? now()->addDays(14)->toDateString(),
                'line_items' => $lineItems,
                'notes' => 'Live ETOGO property-process invoice for ' . ($property->property_name ?? 'property') . '.',
            ];

            if ($attributes['status'] === 'paid') {
                $attributes['paid_at'] = optional($existing?->paid_at)->toDateString() ?? now()->toDateString();
            }

            if ($existing) {
                $existing->update($attributes);

                return $existing->fresh(['user', 'project.property']);
            }

            $attributes['invoice_number'] = $this->nextInvoiceNumber('ETG-' . now()->format('Ymd') . '-' . $property->id);

            return Invoice::create($attributes)->fresh(['user', 'project.property']);
        });
    }

    public function share(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'draft') {
            $invoice->update([
                'status' => (float) ($invoice->balance ?? 0) <= 0 && (float) ($invoice->total ?? 0) > 0 ? 'paid' : 'sent',
                'issue_date' => $invoice->issue_date ?: now()->toDateString(),
                'due_date' => $invoice->due_date ?: now()->addDays(14)->toDateString(),
            ]);
        }

        return $invoice->fresh(['user', 'project.property']);
    }

    private function resolveProject(Property $property): Project
    {
        return Project::firstOrCreate(
            ['property_id' => $property->id],
            [
                'title' => 'Property Process - ' . $property->property_name,
                'description' => 'Live property registry, documentation, diagnosis and remediation workflow for ' . $property->property_name,
                'status' => 'pending',
                'user_id' => $property->user_id,
                'managed_by' => $property->project_manager_id,
                'created_by' => Auth::id(),
                'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
            ]
        );
    }

    private function resolveExistingInvoice(Property $property, Project $project): ?Invoice
    {
        return Invoice::where('project_id', $project->id)
            ->where('user_id', $property->user_id)
            ->where('type', 'additional')
            ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
            ->orderByDesc('id')
            ->get()
            ->first(function (Invoice $invoice) {
                return collect($invoice->line_items ?? [])
                    ->contains(fn ($item) => in_array(($item['purpose'] ?? null), [
                        'property_process',
                        'property_facts',
                        'property_registry',
                        'property_documentation',
                        'property_twin',
                        'property_diagnosis',
                        'remediation_project',
                        'property_stewardship',
                    ], true));
            });
    }

    private function buildLineItems(Property $property, Project $project, ?Invoice $existing): array
    {
        $latestInspection = $property->inspections->sortByDesc('id')->first();
        $existingAmounts = collect($existing?->line_items ?? [])
            ->mapWithKeys(fn ($item) => [($item['purpose'] ?? '') => (float) ($item['amount'] ?? $item['total'] ?? 0)]);
        $registryAmount = $existingAmounts->get('property_registry', $existingAmounts->get('property_facts', 0));

        $diagnosisAmount = $existingAmounts->get('property_diagnosis')
            ?: (float) ($latestInspection?->inspection_fee_amount ?? 0);

        $remediationAmount = $existingAmounts->get('remediation_project')
            ?: $this->resolveRemediationAmount($latestInspection, $project);

        return collect([
            $this->line('01', 'Property Registry Creation', 'Property Registry (PR)', 'Property Registry Profile Creation', 'Establish the permanent Property Facts Registry profile and property identification structure.', 'property_registry', $registryAmount, true),
            $this->line('02', 'Property Documentation', 'Property Documentation (PD)', 'Historical Record Reconstruction', 'Collect, organize and reconstruct available plans, records, permits, warranties, invoices and property history.', 'property_documentation', $existingAmounts->get('property_documentation', 0), $this->hasDocumentation($property)),
            $this->line('03', 'Property Twin', 'Property Twin (PT)', 'Full Property Twin', 'Create or attach a navigable digital representation of the property, including relevant interior and exterior spaces.', 'property_twin', $existingAmounts->get('property_twin', 0), $this->hasPropertyTwin($property)),
            $this->line('04', 'Property Diagnosis', 'Diagnosis Findings (DF)', 'Findings Report', 'Collect, validate, organize and present the Findings Report. Individual findings are not separately billable.', 'property_diagnosis', $diagnosisAmount, (bool) $latestInspection),
            $this->line('05', 'Remediation Project', 'Remediation Deliverables (RD)', 'Approved Remediation Project', 'Complete the approved corrective work required to remedy acknowledged issues and deliver approved qualified deliverables.', 'remediation_project', $remediationAmount, $remediationAmount > 0 || $this->hasRemediationScope($latestInspection)),
            $this->line('06', 'Proactive Stewardship', 'Property Stewardship (PS)', 'Essential Stewardship', 'Ongoing property oversight, maintenance planning, records management and stewardship reporting.', 'property_stewardship', $existingAmounts->get('property_stewardship', 0), false),
        ])
            ->filter(fn ($item) => ($item['included'] ?? false) || (float) ($item['amount'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function line(string $item, string $service, string $deliverable, string $selectedDeliverable, string $description, string $purpose, float $amount, bool $included): array
    {
        $amount = round($amount, 2);

        return [
            'item' => $item,
            'service' => $service,
            'deliverable' => $deliverable,
            'selected_deliverable' => $selectedDeliverable,
            'description' => $description,
            'purpose' => $purpose,
            'quantity' => 1,
            'unit' => $purpose === 'property_stewardship' ? 'Month' : ($purpose === 'property_diagnosis' ? 'Diagnosis' : ($purpose === 'remediation_project' ? 'Project' : 'Service')),
            'unit_price' => $amount,
            'rate' => $amount,
            'tax_rate' => self::TAX_RATE,
            'amount' => $amount,
            'total' => $amount,
            'included' => $included,
        ];
    }

    private function hasDocumentation(Property $property): bool
    {
        return !empty($property->blueprint_file)
            || !empty($property->property_photos)
            || !empty($property->known_problem_images)
            || $property->verifiedPropertyFacts()->exists();
    }

    private function hasPropertyTwin(Property $property): bool
    {
        return $property->spatialModels->isNotEmpty() || $property->matterportModels->isNotEmpty();
    }

    private function hasRemediationScope(?Inspection $inspection): bool
    {
        return $inspection
            && in_array($inspection->status, ['client_committed', 'estimation_completed', 'quotation_shared', 'quotation_approved', 'completed'], true);
    }

    private function resolveRemediationAmount(?Inspection $inspection, Project $project): float
    {
        if ($inspection) {
            $amount = max(
                (float) ($inspection->trc_annual ?? 0),
                (float) ($inspection->arp_equivalent_final ?? 0),
                (float) ($inspection->scientific_final_annual ?? 0)
            );

            if ($amount > 0) {
                return round($amount, 2);
            }
        }

        return round((float) Invoice::where('project_id', $project->id)
            ->where('type', 'project')
            ->whereNotIn('status', ['cancelled'])
            ->sum('total'), 2);
    }

    private function resolveStatus(?Invoice $existing, float $balance, float $paidAmount): string
    {
        if ($balance <= 0 && $paidAmount > 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return in_array(($existing->status ?? null), ['sent', 'overdue'], true) ? $existing->status : 'draft';
    }

    private function nextInvoiceNumber(string $baseInvoiceNumber): string
    {
        $invoiceNumber = $baseInvoiceNumber;
        $counter = 1;

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = $baseInvoiceNumber . '-' . $counter;
            $counter++;
        }

        return $invoiceNumber;
    }
}
