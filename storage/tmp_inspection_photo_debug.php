<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$inspection = App\Models\Inspection::find(1);
if (!$inspection) {
    echo "inspection_not_found\n";
    exit(0);
}

$findings = is_array($inspection->findings)
    ? $inspection->findings
    : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

echo "inspection_findings=" . count($findings) . "\n";
foreach ($findings as $i => $f) {
    $issue = $f['issue'] ?? ($f['task_question'] ?? '');
    $paths = $f['finding_photos'] ?? [];
    if (is_string($paths)) {
        $decoded = json_decode($paths, true);
        $paths = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($paths)) {
        $paths = [];
    }
    echo "F[$i] issue=" . substr((string)$issue, 0, 40) . " photos=" . count($paths) . "\n";
}

$phar = App\Models\PHARFinding::where('inspection_id', 1)->orderBy('id')->get();
echo "phar_findings=" . $phar->count() . "\n";
foreach ($phar as $i => $f) {
    $ids = $f->photo_ids;
    if (is_string($ids)) {
        $decoded = json_decode($ids, true);
        $ids = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($ids)) {
        $ids = [];
    }
    echo "P[$i] task=" . substr((string)$f->task_question, 0, 40) . " photos=" . count($ids) . "\n";
}
