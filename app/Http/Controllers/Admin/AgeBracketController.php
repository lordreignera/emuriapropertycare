<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgeBracket;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class AgeBracketController extends Controller
{
    public function index()
    {
        $ageBrackets = AgeBracket::orderBy('sort_order')->orderBy('min_age')->get();
        return view('admin.pricing-system.age-brackets.index', compact('ageBrackets'));
    }

    public function create()
    {
        $domains = CpiDomain::active()->orderBy('domain_number')->get();
        return view('admin.pricing-system.age-brackets.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bracket_name' => 'required|string|max:255',
            'cpi_domain_id' => 'required|exists:cpi_domains,id',
            'min_age' => 'required|integer|min:0',
            'max_age' => 'nullable|integer|min:0',
            'score_value' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        AgeBracket::create($validated);

        return redirect()->route('admin.age-brackets.index')
            ->with('success', 'Age bracket created successfully.');
    }

    public function edit(AgeBracket $ageBracket)
    {
        return view('admin.pricing-system.age-brackets.edit', compact('ageBracket'));
    }

    public function update(Request $request, AgeBracket $ageBracket)
    {
        $validated = $request->validate([
            'bracket_name' => 'required|string|max:50',
            'min_age' => 'required|integer|min:0',
            'max_age' => 'nullable|integer|min:0',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $ageBracket->update($validated);

        return redirect()->route('admin.age-brackets.index')
            ->with('success', 'Age bracket updated successfully.');
    }

    public function destroy(AgeBracket $ageBracket)
    {
        $ageBracket->delete();

        return redirect()->route('admin.age-brackets.index')
            ->with('success', 'Age bracket deleted successfully.');
    }
}
