<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Submitted | {{ config('app.name', 'EMURIA') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4">
        <div class="max-w-xl rounded bg-white p-8 text-center shadow-sm">
            <h1 class="text-2xl font-bold">Trade application submitted</h1>
            <p class="mt-3 text-gray-600">Thank you. Your application number is <strong>{{ $application->application_number }}</strong>.</p>
            <p class="mt-2 text-gray-600">Our admin team will review your systems, subsystems, and compliance documents before approving your company for work orders.</p>
            <a href="/home/index.html" class="mt-6 inline-flex rounded bg-indigo-700 px-5 py-3 font-semibold text-white">Return to Home</a>
        </div>
    </main>
</body>
</html>
