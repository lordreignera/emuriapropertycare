<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Job Approval & Service Agreement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.45;
            margin: 0;
        }
        .header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .muted {
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0 0 4px 0;">Client Job Approval & Service Agreement</h2>
        <div class="muted">
            Inspection #{{ $inspection->id }} | Generated {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    @include('shared.inspection-job-approval-agreement', ['inspection' => $inspection, 'pdfMode' => true])
</body>
</html>
