<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
