<?php

namespace App\Http\Controllers;

use App\Models\InspectionSystem;
use App\Models\TradeApplication;
use Illuminate\Http\Request;

class TradeApplicationController extends Controller
{
    public function create()
    {
        $systems = InspectionSystem::with(['subsystems' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
        }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('trade-applications.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'service_area' => 'required|string|max:255',
            'years_in_business' => 'nullable|integer|min:0|max:150',
            'technicians_count' => 'nullable|integer|min:0|max:1000',
            'company_description' => 'nullable|string|max:3000',
            'system_ids' => 'required|array|min:1',
            'system_ids.*' => 'integer|exists:systems,id',
            'subsystem_ids' => 'required|array|min:1',
            'subsystem_ids.*' => 'integer|exists:subsystems,id',
            'system_pricing' => 'nullable|array',
            'system_pricing.*.units' => 'nullable|array',
            'system_pricing.*.units.*' => 'nullable|in:sf,lf,ea,hr,day,ls,ton',
            'system_pricing.*.typical_rate' => 'nullable|numeric|min:0|max:999999.99',
            'system_pricing.*.rate_unit' => 'nullable|in:sf,lf,ea,hr,day,ls,ton',
            'system_pricing.*.minimum_charge' => 'nullable|numeric|min:0|max:999999.99',
            'system_pricing.*.notes' => 'nullable|string|max:1000',
            'availability' => 'nullable|array',
            'availability.*' => 'nullable|string|max:100',
            'minimum_service_charge' => 'nullable|numeric|min:0|max:999999.99',
            'emergency_premium' => 'nullable|string|max:255',
            'travel_charge_policy' => 'nullable|string|max:255',
            'travel_policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'material_policy' => 'nullable|string|max:255',
            'material_policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'equipment_policy' => 'nullable|string|max:255',
            'equipment_policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'disposal_policy' => 'nullable|string|max:255',
            'disposal_policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'standard_warranty' => 'nullable|string|max:255',
            'warranty_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'pricing_notes' => 'nullable|string|max:3000',
            'pricing_policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'business_licence_status' => 'required|in:yes,no,pending,not_applicable',
            'business_licence_number' => 'nullable|string|max:255',
            'business_licence_expiry' => 'nullable|date',
            'business_licence_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'liability_insurance_status' => 'required|in:yes,no,pending,not_applicable',
            'liability_insurance_provider' => 'nullable|string|max:255',
            'liability_insurance_policy_number' => 'nullable|string|max:255',
            'liability_insurance_expiry' => 'nullable|date',
            'liability_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'worksafebc_status' => 'required|in:yes,no,pending,not_applicable',
            'worksafebc_number' => 'nullable|string|max:255',
            'worksafebc_expiry' => 'nullable|date',
            'worksafebc_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'gst_status' => 'required|in:yes,no,pending,not_applicable',
            'gst_number' => 'nullable|string|max:255',
            'gst_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'references' => 'nullable|array',
            'references.*.name' => 'nullable|string|max:255',
            'references.*.phone' => 'nullable|string|max:50',
            'references.*.email' => 'nullable|email|max:255',
            'additional_documents' => 'nullable|array|max:5',
            'additional_documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'terms_accepted' => 'required|accepted',
        ]);

        $validated['system_ids'] = array_values(array_unique(array_map('intval', $validated['system_ids'] ?? [])));
        $validated['subsystem_ids'] = array_values(array_unique(array_map('intval', $validated['subsystem_ids'] ?? [])));
        $validated['system_pricing'] = collect($validated['system_pricing'] ?? [])
            ->only(array_map('strval', $validated['system_ids']))
            ->map(function ($pricing) {
                return [
                    'units' => array_values(array_unique(array_filter($pricing['units'] ?? []))),
                    'typical_rate' => isset($pricing['typical_rate']) && $pricing['typical_rate'] !== '' ? (float) $pricing['typical_rate'] : null,
                    'rate_unit' => $pricing['rate_unit'] ?? null,
                    'minimum_charge' => isset($pricing['minimum_charge']) && $pricing['minimum_charge'] !== '' ? (float) $pricing['minimum_charge'] : null,
                    'notes' => trim((string) ($pricing['notes'] ?? '')),
                ];
            })
            ->filter(fn ($pricing) => !empty($pricing['units']) || $pricing['typical_rate'] !== null || $pricing['minimum_charge'] !== null || $pricing['notes'] !== '')
            ->all();
        $validated['availability'] = array_values($validated['availability'] ?? []);
        $validated['references'] = collect($validated['references'] ?? [])
            ->filter(fn ($reference) => !empty($reference['name']) || !empty($reference['phone']) || !empty($reference['email']))
            ->values()
            ->all();

        $disk = config('filesystems.default', 's3');

        foreach ([
            'business_licence_document',
            'liability_insurance_document',
            'worksafebc_document',
            'gst_document',
            'travel_policy_document',
            'material_policy_document',
            'equipment_policy_document',
            'disposal_policy_document',
            'warranty_document',
            'pricing_policy_document',
        ] as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('trade-applications/documents', $disk);
            }
        }

        $validated['additional_documents'] = [];
        foreach ((array) $request->file('additional_documents', []) as $document) {
            if ($document && $document->isValid()) {
                $validated['additional_documents'][] = $document->store('trade-applications/documents', $disk);
            }
        }

        $requiredStatuses = [
            $validated['business_licence_status'],
            $validated['liability_insurance_status'],
            $validated['worksafebc_status'],
            $validated['gst_status'],
        ];

        $validated['status'] = collect($requiredStatuses)->every(fn ($status) => in_array($status, ['yes', 'not_applicable'], true))
            ? TradeApplication::STATUS_READY_FOR_REVIEW
            : TradeApplication::STATUS_NEEDS_MORE_INFORMATION;
        $validated['submitted_at'] = now();

        $application = TradeApplication::create($validated);

        return redirect()->route('trade-applications.thank-you', $application)
            ->with('success', 'Your trade application has been submitted.');
    }

    public function thankYou(TradeApplication $tradeApplication)
    {
        return view('trade-applications.thank-you', ['application' => $tradeApplication]);
    }
}
