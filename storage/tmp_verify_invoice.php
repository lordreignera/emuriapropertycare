<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoice = App\Models\Invoice::find(2);
if (!$invoice) {
    echo "invoice_not_found\n";
    exit(1);
}

echo 'invoice_total=' . $invoice->total . PHP_EOL;
echo 'invoice_paid=' . $invoice->paid_amount . PHP_EOL;
echo 'invoice_balance=' . $invoice->balance . PHP_EOL;
echo 'invoice_status=' . $invoice->status . PHP_EOL;
