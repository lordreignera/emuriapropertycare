<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionToolAssignment;
use App\Models\ToolSetting;
use Illuminate\Support\Carbon;

class AgreementScheduleService
{
    public function refresh(Inspection $inspection): Inspection
    {
        $plan = $this->build($inspection);

        $updates = [
            'estimated_duration_days' => $plan['estimated_duration_days'],
            'schedule_blocked_reason' => $plan['schedule_blocked_reason'],
        ];

        // Only write auto-calculated dates if the admin has NOT already set
        // a real work_schedule (visit dates). Once the admin schedules visits,
        // planned_start_date and target_completion_date come from those dates
        // and must not be overwritten by the auto-calculation.
        $hasManualSchedule = !empty($inspection->work_schedule);
        if (!$hasManualSchedule) {
            $updates['planned_start_date']    = $plan['planned_start_date'];
            $updates['target_completion_date'] = $plan['target_completion_date'];
        }

        $inspection->update($updates);

        return $inspection->fresh();
    }

    public function build(Inspection $inspection): array
    {
        $findings = is_array($inspection->findings)
            ? $inspection->findings
            : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

        $requiredTools = $this->resolveRequiredTools($findings);
        $this->syncToolAssignments($inspection, $requiredTools, $findings);

        $labourHours = collect($findings)->sum(static fn(array $f) => (float) ($f['phar_labour_hours'] ?? 0));

        // Mon–Sat, 7:00 AM – 6:00 PM = 11 hours/day per technician, 2 technicians, 85% efficiency
        $effectiveHoursPerDay = 2 * 11 * 0.85;
        $estimatedDays = max(1, (int) ceil($labourHours / max($effectiveHoursPerDay, 1)));

        $blockers = [];
        if (!$inspection->approved_by_client) {
            $blockers[] = 'Awaiting client signature.';
        }
        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            $blockers[] = 'Awaiting deposit confirmation.';
        }

        $unavailableTools = $this->findUnavailableTools($requiredTools);
        if (!empty($unavailableTools)) {
            $blockers[] = 'Required tools not available: ' . implode(', ', $unavailableTools) . '.';
        }

        $startDate = null;
        $completionDate = null;

        if (empty($blockers)) {
            $startDate = $this->nextBusinessDate(now()->toDateString());
            $completionDate = $this->addWorkingDays(clone $startDate, max($estimatedDays - 1, 0));
        }

        return [
            'estimated_duration_days' => $estimatedDays,
            'planned_start_date' => $startDate?->toDateString(),
            'target_completion_date' => $completionDate?->toDateString(),
            'schedule_blocked_reason' => empty($blockers) ? null : implode(' ', $blockers),
        ];
    }

    private function findUnavailableTools($requiredTools): array
    {
        return collect($requiredTools)
            ->filter(static fn(ToolSetting $tool) => ($tool->availability_status ?? null) === 'non_available')
            ->pluck('tool_name')
            ->map(static fn($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveRequiredTools(array $findings)
    {
        $findingsCollection = collect($findings);
        $systemIds = $findingsCollection->pluck('system_id')->filter()->unique()->values();
        $subsystemIds = $findingsCollection->pluck('subsystem_id')->filter()->unique()->values();

        $tools = ToolSetting::query()
            ->where('is_active', true)
            ->get();

        if ($tools->isEmpty()) {
            return collect();
        }

        return $tools->filter(function (ToolSetting $tool) use ($systemIds, $subsystemIds) {
            if (is_null($tool->system_id) && is_null($tool->subsystem_id)) {
                return true;
            }

            if (!is_null($tool->subsystem_id)) {
                return $subsystemIds->contains((int) $tool->subsystem_id);
            }

            return !is_null($tool->system_id)
                ? $systemIds->contains((int) $tool->system_id)
                : false;
        })->values();
    }

    private function syncToolAssignments(Inspection $inspection, $requiredTools, array $findings): void
    {
        $requiredToolRows = collect($requiredTools)->map(function (ToolSetting $tool) use ($inspection, $findings) {
            $matchingCount = collect($findings)->filter(function (array $finding) use ($tool) {
                if (!is_null($tool->subsystem_id)) {
                    return (int) ($finding['subsystem_id'] ?? 0) === (int) $tool->subsystem_id;
                }

                if (!is_null($tool->system_id)) {
                    return (int) ($finding['system_id'] ?? 0) === (int) $tool->system_id;
                }

                return true;
            })->count();

            return [
                'inspection_id'       => $inspection->id,
                'property_id'         => $inspection->property_id,
                'tool_setting_id'     => $tool->id,
                'system_id'           => $tool->system_id,
                'subsystem_id'        => $tool->subsystem_id,
                'tool_name'           => trim((string) $tool->tool_name),
                'ownership_status'    => $tool->ownership_status,
                'availability_status' => $tool->availability_status,
                'finding_count'       => $matchingCount,
            ];
        })->filter(static fn(array $row) => $row['tool_name'] !== '')->values();

        $validToolNames = $requiredToolRows->pluck('tool_name')->all();

        InspectionToolAssignment::query()
            ->where('inspection_id', $inspection->id)
            ->where('quantity', 0)          // never auto-delete a tool that was manually deployed
            ->when(!empty($validToolNames), function ($query) use ($validToolNames) {
                $query->whereNotIn('tool_name', $validToolNames);
            }, function ($query) {
                $query->whereRaw('1=1');
            })
            ->delete();

        foreach ($requiredToolRows as $row) {
            $existing = InspectionToolAssignment::where([
                'inspection_id' => $row['inspection_id'],
                'tool_name'     => $row['tool_name'],
            ])->first();

            if ($existing) {
                // Preserve admin-set quantity — only refresh metadata
                $existing->update(array_diff_key($row, array_flip(['inspection_id', 'tool_name', 'quantity'])));
            } else {
                // New record: quantity defaults to 0 until admin/PM manually assigns
                InspectionToolAssignment::create(array_merge($row, ['quantity' => 0]));
            }
        }
    }

    private function nextBusinessDate(string $fromDate): Carbon
    {
        $date = Carbon::parse($fromDate)->addDay();
        // Working week is Mon–Sat; only skip Sunday
        while ($date->dayOfWeek === Carbon::SUNDAY) {
            $date->addDay();
        }

        return $date;
    }

    private function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $added = 0;
        while ($added < $days) {
            $date->addDay();
            // Mon–Sat are working days; skip Sunday only
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $added++;
            }
        }

        return $date;
    }
}
