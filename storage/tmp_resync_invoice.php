<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$inspection = App\Models\Inspection::with(['property', 'project'])->find(1);
if (!$inspection) {
    echo "inspection_not_found\n";
    exit(1);
}

$service = app(App\Services\InspectionInvoiceSyncService::class);
$invoice = $service->syncProjectInvoice($inspection);

if (!$invoice) {
    echo "invoice_sync_failed\n";
    exit(1);
}

echo 'invoice_id=' . $invoice->id . PHP_EOL;
echo 'total=' . $invoice->total . PHP_EOL;
echo 'paid_amount=' . $invoice->paid_amount . PHP_EOL;
echo 'balance=' . $invoice->balance . PHP_EOL;
echo 'status=' . $invoice->status . PHP_EOL;
