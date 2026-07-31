<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Property;
use App\Models\Inspection;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('uploads:migrate-private-to-public {--keep : Keep original files on local disk}', function () {
    $local = Storage::disk('local');
    $public = Storage::disk('public');
    $keep = (bool) $this->option('keep');

    $moved = 0;
    $skipped = 0;
    $missing = 0;

    $moveFile = function (?string $path) use ($local, $public, $keep, &$moved, &$skipped, &$missing): void {
        if (!$path) {
            return;
        }

        if ($public->exists($path)) {
            $skipped++;
            return;
        }

        if (!$local->exists($path)) {
            $missing++;
            return;
        }

        $readStream = $local->readStream($path);
        if ($readStream === false) {
            $missing++;
            return;
        }

        $public->writeStream($path, $readStream);

        if (is_resource($readStream)) {
            fclose($readStream);
        }

        if (!$keep) {
            $local->delete($path);
        }

        $moved++;
    };

    Property::query()->chunkById(200, function ($properties) use ($moveFile) {
        foreach ($properties as $property) {
            if (is_array($property->property_photos)) {
                foreach ($property->property_photos as $photo) {
                    $moveFile($photo);
                }
            }

            if (is_string($property->blueprint_file)) {
                $moveFile($property->blueprint_file);
            }
        }
    });

    Inspection::query()->chunkById(200, function ($inspections) use ($moveFile) {
        foreach ($inspections as $inspection) {
            if (is_array($inspection->photos)) {
                foreach ($inspection->photos as $photo) {
                    $moveFile($photo);
                }
            }

            if (is_string($inspection->report_file)) {
                $moveFile($inspection->report_file);
            }
        }
    });

    $this->info("Moved: {$moved}, Skipped: {$skipped}, Missing: {$missing}");
})->purpose('Move uploaded files from local private disk to public disk');

Artisan::command('inspections:dedupe-diagnosis
        {--write : Delete stale duplicate diagnosis rows}
        {--property-id=* : Limit cleanup to one or more property IDs}
        {--force : Delete duplicates even when they have downstream workflow links}',
    function () {
        $write = (bool) $this->option('write');
        $force = (bool) $this->option('force');
        $propertyIds = collect((array) $this->option('property-id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $diagnosisStatuses = [
            'scheduled',
            'in_progress',
            'findings_captured',
            'findings_shared',
        ];

        $protectedRelations = [
            'quotations',
            'clientDecisions',
            'remediationRoadmaps',
            'remediationWorkOrders',
            'tradePricingItems',
            'toolAssignments',
            'maintenanceVisitLogs',
            'captureSessions',
            'spatialModels',
            'matterportModels',
            'issueMarkers',
            'scopeOfWorks',
            'findingEvidence',
        ];

        $query = Inspection::query()
            ->whereNotNull('property_id')
            ->whereIn('status', $diagnosisStatuses)
            ->withCount($protectedRelations)
            ->orderBy('property_id')
            ->orderByDesc('id');

        if ($propertyIds->isNotEmpty()) {
            $query->whereIn('property_id', $propertyIds);
        }

        $duplicateGroups = $query->get()
            ->groupBy('property_id')
            ->filter(fn ($group) => $group->count() > 1);

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate diagnosis rows found.');
            return self::SUCCESS;
        }

        $deleteIds = [];
        $reportRows = [];

        foreach ($duplicateGroups as $propertyId => $group) {
            $retained = $group->first();

            foreach ($group->skip(1) as $inspection) {
                $signals = collect($protectedRelations)
                    ->filter(fn ($relation) => (int) ($inspection->{$relation . '_count'} ?? 0) > 0)
                    ->map(fn ($relation) => "{$relation}=" . (int) $inspection->{$relation . '_count'})
                    ->values();

                foreach ([
                    'findings_report_shared_at',
                    'client_committed_at',
                    'estimation_started_at',
                    'estimation_completed_at',
                    'assessment_finalised_at',
                    'etogo_signed_at',
                    'active_quotation_id',
                    'client_signature',
                ] as $field) {
                    if (!empty($inspection->{$field})) {
                        $signals->push($field);
                    }
                }

                $protected = $signals->isNotEmpty();
                $action = (!$protected || $force) ? ($write ? 'DELETED' : 'DELETE dry-run') : 'SKIP protected';

                if (!$protected || $force) {
                    $deleteIds[] = (int) $inspection->id;
                }

                $reportRows[] = [
                    $propertyId,
                    $retained->id,
                    $inspection->id,
                    $inspection->status,
                    $action,
                    $signals->implode(', ') ?: '-',
                ];
            }
        }

        $this->table(
            ['Property', 'Keep ID', 'Duplicate ID', 'Status', 'Action', 'Signals'],
            $reportRows
        );

        if (!$write) {
            $this->warn('Dry-run only. Re-run with --write to delete rows marked "DELETE dry-run".');
            return self::SUCCESS;
        }

        if (empty($deleteIds)) {
            $this->warn('No deletable duplicate rows found.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($deleteIds) {
            Inspection::query()->whereIn('id', $deleteIds)->delete();
        });

        $this->info('Deleted ' . count($deleteIds) . ' duplicate diagnosis row(s).');
        return self::SUCCESS;
    }
)->purpose('Remove stale duplicate diagnosis rows while keeping the latest inspection per property');
