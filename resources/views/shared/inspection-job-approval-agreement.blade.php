@php
    $agreementInspection = $inspection;
    $agreementProperty = $agreementInspection->property;
    $agreementClientName = $agreementInspection->owner_name
        ?? ($agreementProperty?->user?->name)
        ?? '______________________________';
    $agreementAddress = trim((string) (
        ($agreementProperty?->property_address ?? '')
        . ', '
        . ($agreementProperty?->city ?? '')
    ), ', ');
    $agreementAddress = $agreementAddress !== '' ? $agreementAddress : '______________________________';
    $agreementJobId = $agreementInspection->id ? ('JOB-' . str_pad((string) $agreementInspection->id, 6, '0', STR_PAD_LEFT)) : '______________________________';
    $agreementPharRef = $agreementProperty?->property_code
        ? ($agreementProperty->property_code . '-PHAR')
        : '______________________________';
    $agreementApprovalDate = $agreementInspection->completed_date
        ? $agreementInspection->completed_date->format('Y-m-d')
        : '______________________________';

    // Resolve findings scope. When an approved quotation exists, show only the
    // approved findings/tasks in the contract (ID-first, then key fallback).
    $agreementFindings = collect(is_array($agreementInspection->findings)
        ? $agreementInspection->findings
        : (json_decode($agreementInspection->getRawOriginal('findings') ?? '[]', true) ?? []))
        ->values();

    $agreementQuotation = null;
    if (!empty($agreementInspection->active_quotation_id)) {
        $agreementQuotation = \App\Models\InspectionQuotation::query()
            ->where('id', $agreementInspection->active_quotation_id)
            ->where('inspection_id', $agreementInspection->id)
            ->first();
    }

    if (($agreementQuotation->status ?? null) !== 'approved') {
        $latestApprovedQuotation = \App\Models\InspectionQuotation::query()
            ->where('inspection_id', $agreementInspection->id)
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->first();
        if ($latestApprovedQuotation) {
            $agreementQuotation = $latestApprovedQuotation;
        }
    }

    $makeAgreementFindingKey = function ($issueOrTask, $category) {
        $left = strtolower(trim((string) $issueOrTask));
        $right = strtolower(trim((string) $category));
        return $left . '|' . $right;
    };

    $agreementApprovedIds = collect($agreementQuotation->approved_finding_ids ?? [])
        ->map(fn($id) => (int) $id)
        ->filter(fn($id) => $id > 0)
        ->values();

    $showApprovedScopeOnly =
        !empty($agreementQuotation) &&
        (($agreementQuotation->status ?? null) === 'approved') &&
        $agreementApprovedIds->isNotEmpty();

    if ($showApprovedScopeOnly) {
        $approvedIdMap = $agreementApprovedIds->flip();
        $filteredById = $agreementFindings
            ->filter(fn($f) => $approvedIdMap->has((int) ($f['id'] ?? 0)))
            ->values();

        if ($filteredById->isNotEmpty()) {
            $agreementFindings = $filteredById;
        } else {
            $agreementSnapshot = collect($agreementQuotation->findings_snapshot ?? [])->values();
            $approvedScopeKeys = $agreementSnapshot
                ->filter(fn($f) => $agreementApprovedIds->contains((int) ($f['id'] ?? 0)))
                ->map(fn($f) => $makeAgreementFindingKey(
                    $f['task_question'] ?? ($f['issue'] ?? ''),
                    $f['category'] ?? ''
                ))
                ->filter(fn($k) => $k !== '|')
                ->unique()
                ->values();

            $agreementFindings = $agreementFindings
                ->filter(function ($f) use ($approvedScopeKeys, $makeAgreementFindingKey) {
                    $key = $makeAgreementFindingKey(
                        $f['task_question'] ?? ($f['issue'] ?? ''),
                        $f['phar_category'] ?? ($f['category'] ?? '')
                    );
                    return $approvedScopeKeys->contains($key);
                })
                ->values();
        }
    }

    // Backfill descriptive fields from approved quotation snapshot for legacy
    // findings where issue/recommendation descriptions are missing inline.
    $agreementSnapshotAll = collect($agreementQuotation->findings_snapshot ?? [])->values();
    $agreementSnapshotById = $agreementSnapshotAll
        ->filter(fn($f) => !empty($f['id']))
        ->mapWithKeys(fn($f) => [(int) $f['id'] => $f]);

    $agreementSnapshotByKey = $agreementSnapshotAll
        ->mapWithKeys(function ($f) use ($makeAgreementFindingKey) {
            $key = $makeAgreementFindingKey(
                $f['task_question'] ?? ($f['issue'] ?? ''),
                $f['category'] ?? ''
            );
            return $key !== '|' ? [$key => $f] : [];
        });

    $agreementFindings = $agreementFindings
        ->map(function ($finding) use ($agreementSnapshotById, $agreementSnapshotByKey, $makeAgreementFindingKey) {
            $snapshotFinding = null;
            $findingId = (int) ($finding['id'] ?? 0);

            if ($findingId > 0 && $agreementSnapshotById->has($findingId)) {
                $snapshotFinding = $agreementSnapshotById->get($findingId);
            } else {
                $key = $makeAgreementFindingKey(
                    $finding['task_question'] ?? ($finding['issue'] ?? ''),
                    $finding['phar_category'] ?? ($finding['category'] ?? '')
                );
                $snapshotFinding = $agreementSnapshotByKey->get($key);
            }

            if (!$snapshotFinding) {
                return $finding;
            }

            if (empty($finding['issue_description']) && !empty($snapshotFinding['issue_description'])) {
                $finding['issue_description'] = $snapshotFinding['issue_description'];
            }
            if (empty($finding['recommendation_details']) && !empty($snapshotFinding['recommendation_details'])) {
                $finding['recommendation_details'] = $snapshotFinding['recommendation_details'];
            }
            if (empty($finding['recommendations']) && !empty($snapshotFinding['recommendations']) && is_array($snapshotFinding['recommendations'])) {
                $finding['recommendations'] = $snapshotFinding['recommendations'];
            }

            return $finding;
        })
        ->values();

    $agreementLabourHours = $agreementFindings->sum(function ($finding) {
        return (float) ($finding['phar_labour_hours'] ?? 0);
    });

    $agreementMaterialsFromFindings = $agreementFindings->sum(function ($finding) {
        return collect($finding['phar_materials'] ?? [])->sum(function ($material) {
            return (float) ($material['line_total'] ?? 0);
        });
    });

    $agreementHourlyRate = (float) ($agreementInspection->labour_hourly_rate ?? 0);
    $agreementLabourFromFindings = $agreementHourlyRate > 0
        ? ($agreementLabourHours * $agreementHourlyRate)
        : 0;

    $agreementLabour = $agreementLabourFromFindings > 0
        ? $agreementLabourFromFindings
        : (float) ($agreementInspection->frlc_annual ?? 0);

    $agreementMaterials = $agreementMaterialsFromFindings > 0
        ? $agreementMaterialsFromFindings
        : (float) ($agreementInspection->fmc_annual ?? 0);

    $agreementTotal = round($agreementLabour + $agreementMaterials, 2);

    if ($agreementTotal <= 0) {
        $agreementTotal = round((float) ($agreementInspection->frlc_annual ?? 0) + (float) ($agreementInspection->fmc_annual ?? 0), 2);
    }

    if ($agreementTotal <= 0) {
        $agreementTotal = (float) ($agreementInspection->trc_annual ?? 0);
    }

    $agreementDeposit  = round($agreementTotal * 0.5,  2);
    $agreementProgress = round($agreementTotal * 0.25, 2);
    $agreementFinal    = max(round($agreementTotal - $agreementDeposit - $agreementProgress, 2), 0);
    $agreementPaymentPlan = (string) ($agreementInspection->payment_plan ?? 'full');
    $agreementWorkPaymentStatus = (string) ($agreementInspection->work_payment_status ?? 'pending');
    $agreementWorkPaymentAmount = (float) ($agreementInspection->work_payment_amount ?? 0);
    $agreementWorkPaymentPaidAt = $agreementInspection->work_payment_paid_at
        ? $agreementInspection->work_payment_paid_at->format('Y-m-d h:i A')
        : null;

    // Derive completion date and duration from work_schedule deliverable days
    // The last deliverable date across all visits is the real end date
    $agreementWorkSchedule = $agreementInspection->work_schedule ?? [];
    $allScheduledDates = [];
    foreach ($agreementWorkSchedule as $visit) {
        if (!empty($visit['date'])) {
            $allScheduledDates[] = $visit['date'];
        }
        foreach ($visit['deliverables'] ?? [] as $dl) {
            if (!empty($dl['date'])) {
                $allScheduledDates[] = $dl['date'];
            }
        }
    }
    $allScheduledDates = array_unique(array_filter($allScheduledDates));
    sort($allScheduledDates);

    $schedFirstDate = !empty($allScheduledDates) ? reset($allScheduledDates) : null;
    $schedLastDate  = !empty($allScheduledDates) ? end($allScheduledDates)   : null;

    // Start date: prefer first scheduled date, fallback to DB column
    if ($schedFirstDate) {
        $agreementStartDate = \Carbon\Carbon::parse($schedFirstDate)->format('Y-m-d');
    } elseif ($agreementInspection->planned_start_date) {
        $agreementStartDate = $agreementInspection->planned_start_date->format('Y-m-d');
    } else {
        $agreementStartDate = 'Pending stage completion';
    }

    if ($schedLastDate) {
        $agreementCompletionDate = \Carbon\Carbon::parse($schedLastDate)->format('Y-m-d');
    } elseif ($agreementInspection->target_completion_date) {
        $agreementCompletionDate = $agreementInspection->target_completion_date->format('Y-m-d');
    } else {
        $agreementCompletionDate = 'Pending stage completion';
    }

    if ($schedFirstDate && $schedLastDate && $schedFirstDate !== $schedLastDate) {
        $durationDays = \Carbon\Carbon::parse($schedFirstDate)->diffInDays(\Carbon\Carbon::parse($schedLastDate)) + 1;
        $agreementDuration = $durationDays . ' day(s)';
    } elseif ($agreementInspection->estimated_duration_days) {
        $agreementDuration = $agreementInspection->estimated_duration_days . ' day(s)';
    } elseif (count($allScheduledDates) === 1) {
        $agreementDuration = '1 day(s)';
    } else {
        $agreementDuration = 'To be calculated';
    }

    $assignedTools = $agreementInspection->toolAssignments()
        ->orderBy('tool_name')
        ->get();
@endphp

<div class="job-approval-agreement" style="border:1px solid #d8dbe2; padding:14px; border-radius:6px; margin-top:14px; {{ !empty($pdfMode) ? 'font-size:11px;' : '' }}">
    <h3 style="margin:0 0 6px 0; font-size:{{ !empty($pdfMode) ? '15px' : '19px' }};">CLIENT JOB APPROVAL &amp; SERVICE AGREEMENT</h3>
    <div style="margin-bottom:10px;"><strong>ETOGO | Proactive Property &amp; People Stewardship&trade;</strong></div>
    <p style="margin-top:0;">This Client Job Approval &amp; Service Agreement (the "Agreement") is entered into as of the Date of Approval set forth below, by and between Etogo ("Etogo") and the undersigned client (the "Client").</p>

    <h4 style="margin-bottom:4px;">1. PROJECT IDENTIFICATION</h4>
    <div>• Client Name: {{ $agreementClientName }}</div>
    <div>• Property Address: {{ $agreementAddress }}</div>
    <div>• Job ID: {{ $agreementJobId }}</div>
    <div>• PHAR Reference (if applicable): {{ $agreementPharRef }}</div>
    <div>• Date of Approval: {{ $agreementApprovalDate }}</div>

    <h4 style="margin:10px 0 4px 0;">2. SCOPE OF WORK (LOCKED SCOPE)</h4>
    <p style="margin:0 0 6px 0;">The Scope of Work is strictly defined by the line items generated through the Etogo system and is based upon:</p>
    <div>• Property Health Assessment Report (PHAR)</div>
    <div>• Client-requested specific scope</div>
    <div>• Site inspection findings</div>
    <div style="margin-top:6px;">Included Services: findings → remediation actions (as listed in this report).</div>
    
    @php
        $scopeFindings = $agreementFindings->filter(function ($f) {
            return !empty($f['issue'] ?? null) || !empty($f['task_question'] ?? null);
        })->values();
    @endphp
    
    @if($scopeFindings->isNotEmpty())
        <div style="margin-top:8px;"><strong>Detailed Findings List:</strong></div>
        <ol style="margin:4px 0 6px 16px; font-size:{{ !empty($pdfMode) ? '10px' : '13px' }};">
        @foreach($scopeFindings as $idx => $finding)
            <li style="margin-bottom:3px;">
                <strong>{{ $finding['issue'] ?? ($finding['task_question'] ?? 'Finding') }}</strong>
                @if(!empty($finding['system']))
                    <span style="color:#666;">({{ $finding['system'] }}{{ !empty($finding['subsystem']) ? ' › '.$finding['subsystem'] : '' }})</span>
                @endif
                @if(!empty($finding['phar_labour_hours']))
                    <br><span style="color:#666; font-size:0.9em;">Labour: {{ number_format((float)$finding['phar_labour_hours'], 1) }} hrs @ ${{ number_format((float) ($agreementInspection->labour_hourly_rate ?? 165), 2) }}/hr = ${{ number_format(((float)$finding['phar_labour_hours']) * (float)($agreementInspection->labour_hourly_rate ?? 165), 2) }}</span>
                @endif
                @php
                    $findingMats = collect($finding['phar_materials'] ?? [])->filter(fn($m) => !empty($m['material_name']))->values();
                    $findingMatCost = collect($finding['phar_materials'] ?? [])->sum(fn($m) => (float)($m['line_total'] ?? 0));
                @endphp
                @if($findingMatCost > 0)
                    <br><span style="color:#666; font-size:0.9em;">Materials: ${{ number_format($findingMatCost, 2) }}</span>
                @endif
                @if($findingMats->isNotEmpty())
                    <ul style="margin:4px 0 0 14px; padding:0; color:#555; font-size:0.88em;">
                        @foreach($findingMats as $mat)
                            <li>{{ $mat['material_name'] ?? 'Material' }}: {{ number_format((float) ($mat['quantity'] ?? 0), 2) }} {{ $mat['unit'] ?? 'ea' }} @ ${{ number_format((float) ($mat['unit_cost'] ?? 0), 2) }} = ${{ number_format((float) ($mat['line_total'] ?? 0), 2) }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($finding['issue_description']))
                    <div style="color:#444; font-size:0.88em; margin-top:2px;"><em>Issue detail:</em> {{ $finding['issue_description'] }}</div>
                @endif
                @if(!empty($finding['recommendations']) && is_array($finding['recommendations']))
                    <div style="color:#666; font-size:0.88em; margin-top:2px;"><em>Recommendation:</em> {{ implode('; ', array_filter($finding['recommendations'])) }}</div>
                @endif
                @if(!empty($finding['recommendation_details']))
                    <div style="color:#444; font-size:0.88em; margin-top:2px;"><em>Recommendation detail:</em> {{ $finding['recommendation_details'] }}</div>
                @endif
                @if(!empty($finding['phar_notes']) || !empty($finding['notes']))
                    <div style="color:#666; font-size:0.88em; margin-top:2px;"><em>Notes:</em> {{ $finding['phar_notes'] ?? $finding['notes'] }}</div>
                @endif
            </li>
        @endforeach
        </ol>
    @endif
    
    <p style="margin-top:6px;">Locked Scope Clause: The scope of work is fixed upon execution of this Agreement. Etogo is only responsible for the tasks explicitly listed above. Any additional work, discovery of hidden defects, or requested modifications must follow the formal Change Order Process (Section 5).</p>

    <h4 style="margin:10px 0 4px 0;">3. PROJECT TIMELINE &amp; WORK SCHEDULE</h4>
    <div>• Start Date: {{ $agreementStartDate }}</div>
    <div>• Completion Target: {{ $agreementCompletionDate }}</div>
    <div>• Estimated Duration: {{ $agreementDuration }}</div>
    <div>• Working Hours: Monday – Saturday, 7:00 AM – 6:00 PM (no work on Sundays or statutory holidays)</div>
    <div>• Total Scheduled Visits: {{ count($agreementInspection->work_schedule ?? []) ?: (int)($agreementInspection->bdc_visits_per_year ?? 0) ?: 'To be confirmed' }}</div>
    @php
        $agreementScheduledVisits = collect($agreementInspection->work_schedule ?? [])
            ->sortBy('date')
            ->values();

        // Count total individual work days across all deliverables
        $totalWorkDays = 0;
        foreach ($agreementScheduledVisits as $v) {
            $dlCount = count($v['deliverables'] ?? []);
            $totalWorkDays += $dlCount > 0 ? $dlCount : 1;
        }
    @endphp
    @if($totalWorkDays > 0)
        <div>• Total Scheduled Work Days: {{ $totalWorkDays }}</div>
    @endif
    @if($agreementScheduledVisits->isNotEmpty())
        <div style="margin-top:8px;"><strong>Confirmed Work Schedule:</strong></div>
        <table style="width:100%; border-collapse:collapse; margin:4px 0 6px 0; font-size:{{ !empty($pdfMode) ? '10px' : '13px' }};">
            <tr style="background:#334155; color:#fff;">
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Visit</th>
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Day #</th>
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Date</th>
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Weekday</th>
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Hours</th>
                <th style="padding:4px 8px; text-align:left; border:1px solid #ddd;">Planned Tasks</th>
            </tr>
            @foreach($agreementScheduledVisits as $vIdx => $visit)
                @php
                    $deliverables = $visit['deliverables'] ?? [];
                    $visitLabel = 'Visit ' . ($vIdx + 1);
                    $visitDate = $visit['date'] ?? '';
                @endphp
                @if(count($deliverables) > 0)
                    @foreach($deliverables as $dIdx => $dl)
                        @php
                            $dlDate  = $dl['date'] ?? $visitDate;
                            $dlDay   = $dl['day']  ?? ($dIdx + 1);
                            $dlTasks = $dl['tasks'] ?? [];
                            // backward compat: old records used plain string 'planned_work'
                            if (empty($dlTasks) && !empty($dl['planned_work'])) {
                                $dlTasks = [$dl['planned_work']];
                            }
                            $rowBg = $dIdx === 0 ? '#eff6ff' : '#fff';
                        @endphp
                        <tr style="background:{{ $rowBg }};">
                            <td style="padding:3px 8px; border:1px solid #ddd; font-weight:{{ $dIdx === 0 ? '600' : 'normal' }}; color:{{ $dIdx === 0 ? '#1e40af' : '#444' }};">
                                {{ $dIdx === 0 ? $visitLabel : '' }}
                            </td>
                            <td style="padding:3px 8px; border:1px solid #ddd; text-align:center;">Day {{ $dlDay }}</td>
                            <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">
                                @if($dlDate){{ \Carbon\Carbon::parse($dlDate)->format('M d, Y') }}@else—@endif
                            </td>
                            <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">
                                @if($dlDate){{ \Carbon\Carbon::parse($dlDate)->format('l') }}@else—@endif
                            </td>
                            <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">7:00 AM – 6:00 PM</td>
                            <td style="padding:3px 8px; border:1px solid #ddd;">
                                @if(count($dlTasks) > 0)
                                    <ul style="margin:0; padding-left:14px;">
                                        @foreach($dlTasks as $task)
                                            @if(trim((string)$task) !== '')
                                                <li>{{ $task }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @else
                                    <span style="color:#999;">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    {{-- Visit with no deliverable breakdown — show single row --}}
                    <tr style="background:#eff6ff;">
                        <td style="padding:3px 8px; border:1px solid #ddd; font-weight:600; color:#1e40af;">{{ $visitLabel }}</td>
                        <td style="padding:3px 8px; border:1px solid #ddd; text-align:center;">Day 1</td>
                        <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">
                            @if($visitDate){{ \Carbon\Carbon::parse($visitDate)->format('M d, Y') }}@else—@endif
                        </td>
                        <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">
                            @if($visitDate){{ \Carbon\Carbon::parse($visitDate)->format('l') }}@else—@endif
                        </td>
                        <td style="padding:3px 8px; border:1px solid #ddd; white-space:nowrap;">7:00 AM – 6:00 PM</td>
                        <td style="padding:3px 8px; border:1px solid #ddd;"><span style="color:#999;">—</span></td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif
    @if(!empty($agreementInspection->schedule_blocked_reason))
        <div style="margin-top:6px;color:#9a3412;"><strong>Scheduling Note:</strong> {{ $agreementInspection->schedule_blocked_reason }}</div>
    @endif
    <p style="margin-top:6px;">Timeline Clause: The timeline is an estimate based on the current scope, material availability, and site conditions. Etogo shall not be held liable for delays caused by Client-initiated changes, restricted site access, permit delays, or force majeure events (e.g., extreme weather).</p>

    <h4 style="margin:10px 0 4px 0;">4. PRICING &amp; PAYMENT TERMS</h4>
    <div><strong>A. Project Cost Breakdown:</strong></div>
    <div>• Labour Cost: ${{ number_format($agreementLabour, 2) }}</div>
    <div>• Material Cost: ${{ number_format($agreementMaterials, 2) }}</div>
    <div>• TOTAL PROJECT COST (Labour + Materials): ${{ number_format($agreementTotal, 2) }}</div>
    <div style="margin-top:6px;"><strong>B. Payment Structure:</strong></div>
    <div>1. Deposit (50%): ${{ number_format($agreementDeposit, 2) }} (Required to schedule and mobilize)</div>
    <div>2. Progress Payment: ${{ number_format($agreementProgress, 2) }} (Due at agreed milestone or before final close-out)</div>
    <div>3. Final Payment: ${{ number_format($agreementFinal, 2) }} (Due immediately upon completion)</div>
    @if($agreementPaymentPlan === 'installment' && $agreementWorkPaymentStatus === 'paid')
        <div style="margin-top:6px; color:#166534;"><strong>Deposit Status:</strong> 50% deposit paid by client
            @if($agreementWorkPaymentAmount > 0)
                (Received: ${{ number_format($agreementWorkPaymentAmount, 2) }})
            @endif
            @if(!empty($agreementWorkPaymentPaidAt))
                on {{ $agreementWorkPaymentPaidAt }}
            @endif
            .</div>
    @elseif($agreementWorkPaymentStatus === 'paid')
        <div style="margin-top:6px; color:#166534;"><strong>Payment Status:</strong> Client payment confirmed
            @if($agreementWorkPaymentAmount > 0)
                (Received: ${{ number_format($agreementWorkPaymentAmount, 2) }})
            @endif
            @if(!empty($agreementWorkPaymentPaidAt))
                on {{ $agreementWorkPaymentPaidAt }}
            @endif
            .</div>
    @endif
    <p style="margin:6px 0 0 0;"><em>Single-visit jobs may also use a two-part structure: 50% deposit before mobilization and 50% balance payment after service completion.</em></p>
    <p style="margin-top:6px;">Payment Clause: Work will not commence, and the project will not be placed on the Etogo master schedule, until the required deposit is received in cleared funds. Etogo reserves the right to withhold final deliverables, reports, or warranties until full payment is settled.</p>

    <h4 style="margin:10px 0 4px 0;">5. CHANGE ORDER PROCESS</h4>
    <p style="margin:0 0 6px 0;">Etogo maintains a zero-tolerance policy for undocumented scope creep to ensure project profitability and timeline integrity.</p>
    <div>Change Order Requirements: No additional work will be performed without a digital or written Change Order including:</div>
    <div>1. Detailed description of the additional work.</div>
    <div>2. Fixed costing for the change.</div>
    <div>3. Revised timeline impact.</div>
    <div>4. Client signature/authorization.</div>
    <p style="margin-top:6px;">Clause: Verbal approvals are not legally binding. All changes must be processed through the Etogo operational system.</p>

    <h4 style="margin:10px 0 4px 0;">6. MATERIALS &amp; PROCUREMENT</h4>
    <div>Materials Provision:</div>
    <div>• Supplied by Etogo (standard procurement)</div>
    <div>• Supplied by Client (subject to Etogo approval)</div>
    <p style="margin-top:6px;">Clause: Etogo reserves the right to select appropriate materials and substitute equivalent materials where supply chain constraints exist. Etogo is not responsible for delays, quality defects, or installation failures arising from client-supplied materials.</p>

    <h4 style="margin:10px 0 4px 0;">7. TOOL &amp; EXECUTION STANDARDS</h4>
    <p style="margin:0 0 6px 0;">Etogo operates under a proprietary controlled execution system. The Client acknowledges that services are delivered using:</p>
    <div>• Digital tool tracking and operator accountability.</div>
    <div>• Measured output delivery (e.g., turnover standards).</div>
    <div>• Defined material allocation protocols.</div>
    @if($assignedTools->isNotEmpty())
        <div style="margin-top:6px;"><strong>Assigned Tool Set for This Property:</strong></div>
        @foreach($assignedTools as $tool)
            <div>
                • {{ $tool->tool_name }} &times;{{ $tool->quantity ?? 1 }}
                @if($tool->ownership_status)
                    ({{ ucfirst(str_replace('_', ' ', $tool->ownership_status)) }}
                @endif
                @if($tool->availability_status)
                    {{ $tool->ownership_status ? ', ' : '(' }}{{ ucfirst(str_replace('_', ' ', $tool->availability_status)) }})
                @elseif($tool->ownership_status)
                    )
                @endif
            </div>
        @endforeach
    @else
        <div style="margin-top:6px;">• Assigned tool set will populate automatically once assessment scope is synchronized.</div>
    @endif
    <p style="margin-top:6px;">This system ensures that the project is executed to Etogo’s professional quality standards and "Proactive Property Stewardship" benchmarks.</p>

    <h4 style="margin:10px 0 4px 0;">8. SITE ACCESS &amp; RESPONSIBILITIES</h4>
    <div>The Client agrees to provide:</div>
    <div>1. Unobstructed access to the property during agreed-upon work hours.</div>
    <div>2. Functioning utilities (water, electricity, climate control).</div>
    <div>3. A safe working environment free of hazards.</div>
    <p style="margin-top:6px;">Clause: Delays or aborted visits due to lack of access or unsafe conditions will result in additional "Service Interruption" fees.</p>

    <h4 style="margin:10px 0 4px 0;">9. QUALITY ASSURANCE &amp; COMPLETION</h4>
    <p style="margin:0 0 6px 0;">Completion Definition: Work is deemed complete when all scope items are delivered, the site is restored to a professional condition, and a final walkthrough (digital or physical) is recorded.</p>
    <p style="margin:0 0 6px 0;">Client Review Period: The Client has 48 hours following notice of completion to raise concerns. After this period, the work is deemed accepted and final payment is due.</p>

    <h4 style="margin:10px 0 4px 0;">10. LIMITED WORKMANSHIP WARRANTY</h4>
    <p style="margin:0 0 6px 0;">Etogo provides a 90-day warranty on workmanship related specifically to the items in the Approved Scope.</p>
    <p style="margin:0 0 6px 0;">Exclusions: This warranty does not cover normal wear and tear, pre-existing structural issues, new damage caused by third parties/tenants, or external environmental factors.</p>

    <h4 style="margin:10px 0 4px 0;">11. LIABILITY &amp; RISK</h4>
    <p style="margin:0 0 6px 0;">Clause: Etogo is not liable for pre-existing conditions, latent defects not visible during the PHAR/Assessment, or consequential damages arising from the property’s overall condition. Our liability is limited to the total value of this specific Job Agreement.</p>

    <h4 style="margin:10px 0 4px 0;">12. INTEGRATION WITH ETOGO STEWARDSHIP SYSTEM</h4>
    <p style="margin:0 0 6px 0;">This Agreement is a component of the Etogo operational ecosystem. All project records, material logs, and progress tracking within the Etogo system are incorporated herein by reference. In the event of a conflict between this Job Approval and the Master Property Stewardship &amp; Management Agreement, the Master Agreement shall prevail.</p>

    <h4 style="margin:10px 0 4px 0;">13. GOVERNING LAW &amp; JURISDICTION</h4>
    <p style="margin:0 0 6px 0;">This Agreement shall be governed by and construed in accordance with the laws of the Province of British Columbia and the federal laws of Canada applicable therein. The parties irrevocably attorn to the exclusive jurisdiction of the courts of the Province of British Columbia, sitting in Vancouver, British Columbia, in respect of any dispute arising under or in connection with this Agreement.</p>

    <h4 style="margin:10px 0 4px 0;">14. AUTHORIZATION</h4>
    <p style="margin:0 0 6px 0;"><strong>Execution Process — Three Required Steps:</strong></p>
    <div style="margin-bottom:6px;">
        <strong>Step 1 — Client Signature:</strong> The Client reviews and signs this Agreement below, confirming approval of the Scope of Work and acceptance of all terms.<br>
        <strong>Step 2 — Work Payment / Deposit:</strong> The Client confirms the first work payment (full amount or first visit payment), which mobilizes the project.<br>
        <strong>Step 3 — Etogo Countersignature:</strong> An authorized Etogo representative countersigns to fully execute this Agreement. Work scheduling commences upon completion of all three steps.
    </div>
    <p style="margin:0 0 10px 0;">This Agreement is not binding until all three steps above are completed.</p>

    <table style="width:100%; border-collapse:collapse; margin-top:6px;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:14px; border-top:2px solid #333; padding-top:8px;">
                <strong>STEP 1 — CLIENT SIGNATURE</strong><br><br>
                @if(!empty($agreementInspection->approved_by_client) && !empty($agreementInspection->client_full_name))
                    Signature: <em style="font-style:italic;">Digitally Signed</em><br>
                    @if(!empty($agreementInspection->client_signature_image_path))
                        @php
                            $disk = config('filesystems.default', 'public');
                            $clientSigUrl = $pdfMode ?? false
                                ? storage_path('app/public/' . $agreementInspection->client_signature_image_path)
                                : \Illuminate\Support\Facades\Storage::disk($disk)->url($agreementInspection->client_signature_image_path);
                        @endphp
                        <img src="{{ $clientSigUrl }}" alt="Client Signature"
                             style="max-height:55px;max-width:180px;object-fit:contain;display:block;margin:4px 0;"><br>
                    @endif
                    Date: {{ optional($agreementInspection->client_approved_at)->format('Y-m-d h:i A') ?: 'N/A' }}<br>
                    Name (Print): {{ $agreementInspection->client_full_name }}
                @else
                    Signature: _________________________________<br>
                    Date: ____________________<br>
                    Name (Print): ______________________________<br>
                    <small style="color:#888;">(Client signs first)</small>
                @endif
            </td>
            <td style="width:50%; vertical-align:top; padding-left:14px; border-top:2px solid #333; padding-top:8px;">
                <strong>STEP 3 — ETOGO COUNTERSIGNATURE</strong><br><br>
                @if(!empty($agreementInspection->etogo_signed_at))
                    Signature: <em style="font-style:italic;">Digitally Signed</em><br>
                    @if(!empty($agreementInspection->etogo_signature_image_path))
                        @php
                            $disk = config('filesystems.default', 'public');
                            $etogoSigUrl = $pdfMode ?? false
                                ? storage_path('app/public/' . $agreementInspection->etogo_signature_image_path)
                                : \Illuminate\Support\Facades\Storage::disk($disk)->url($agreementInspection->etogo_signature_image_path);
                        @endphp
                        <img src="{{ $etogoSigUrl }}" alt="Etogo Signature"
                             style="max-height:55px;max-width:180px;object-fit:contain;display:block;margin:4px 0;"><br>
                    @endif
                    Date: {{ optional($agreementInspection->etogo_signed_at)->format('Y-m-d h:i A') ?: 'N/A' }}<br>
                    Name (Print): {{ $agreementInspection->etogoRepresentative?->name ?? 'Etogo Representative' }}
                @else
                    Signature: _________________________________<br>
                    Date: ____________________<br>
                    Name (Print): ______________________________<br>
                    <small style="color:#888;">(Etogo signs after client + payment confirmed)</small>
                @endif
            </td>
        </tr>
    </table>
</div>
