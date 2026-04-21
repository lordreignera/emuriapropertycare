<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoice = App\Models\Invoice::with('project.inspections')->find(2);
if (!$invoice) { echo "invoice_not_found\n"; exit(0); }

echo 'invoice_total=' . $invoice->total . PHP_EOL;
echo 'invoice_paid=' . $invoice->paid_amount . PHP_EOL;
echo 'invoice_balance=' . $invoice->balance . PHP_EOL;
echo 'project_id=' . $invoice->project_id . PHP_EOL;

$inspection = $invoice->project?->inspections?->sortByDesc('id')->first();
if (!$inspection) { echo "inspection_not_found\n"; exit(0); }

echo 'inspection_id=' . $inspection->id . PHP_EOL;
$fields = [
    'work_payment_amount', 'work_payment_status', 'work_payment_cadence', 'payment_plan',
    'trc_annual', 'trc_per_visit', 'trc_monthly', 'scientific_final_monthly',
    'scientific_final_annual', 'arp_total_locked', 'arp_equivalent_final',
    'base_package_price_snapshot', 'installment_amount', 'installment_months', 'installments_paid', 'arp_fully_paid_at'
];
foreach ($fields as $field) {
    $value = $inspection->{$field};
    if ($value instanceof DateTimeInterface) {
        $value = $value->format('Y-m-d H:i:s');
    }
    echo $field . '=' . var_export($value, true) . PHP_EOL;
}
