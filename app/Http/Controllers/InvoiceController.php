<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['user', 'project.property'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();

            if ($status === 'pending') {
                $query->pending();
            } elseif ($status === 'paid') {
                $query->paid();
            } elseif ($status === 'partial') {
                $query->where('status', 'partial');
            } elseif ($status === 'overdue') {
                $query->where('status', 'overdue');
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project.property', function ($propertyQuery) use ($search) {
                        $propertyQuery->where('property_name', 'like', "%{$search}%")
                            ->orWhere('property_code', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->paginate(15)->withQueryString();

        $summary = [
            'total' => Invoice::count(),
            'paid' => Invoice::paid()->count(),
            'partial' => Invoice::where('status', 'partial')->count(),
            'pending' => Invoice::pending()->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'summary'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'project.property', 'project.manager']);

        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
