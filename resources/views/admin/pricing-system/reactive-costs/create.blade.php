<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Reactive Cost Assumption') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.reactive-costs.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="severity_level" class="block text-gray-700 text-sm font-bold mb-2">Severity Level *</label>
                            <select name="severity_level" id="severity_level" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('severity_level') border-red-500 @enderror" required>
                                <option value="">Select Severity Level</option>
                                <option value="LOW" {{ old('severity_level') === 'LOW' ? 'selected' : '' }}>LOW</option>
                                <option value="MODERATE" {{ old('severity_level') === 'MODERATE' ? 'selected' : '' }}>MODERATE</option>
                                <option value="HIGH" {{ old('severity_level') === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                                <option value="CRITICAL" {{ old('severity_level') === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                            </select>
                            @error('severity_level')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="typical_cost" class="block text-gray-700 text-sm font-bold mb-2">Typical Cost ($) *</label>
                            <input type="number" step="0.01" name="typical_cost" id="typical_cost" value="{{ old('typical_cost') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('typical_cost') border-red-500 @enderror" required>
                            @error('typical_cost')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="annual_probability" class="block text-gray-700 text-sm font-bold mb-2">Annual Probability (0.00 - 1.00) *</label>
                            <input type="number" step="0.01" min="0" max="1" name="annual_probability" id="annual_probability" value="{{ old('annual_probability') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('annual_probability') border-red-500 @enderror" required>
                            @error('annual_probability')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="claimable_fraction" class="block text-gray-700 text-sm font-bold mb-2">Claimable Fraction (0.00 - 1.00) *</label>
                            <input type="number" step="0.01" min="0" max="1" name="claimable_fraction" id="claimable_fraction" value="{{ old('claimable_fraction') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('claimable_fraction') border-red-500 @enderror" required>
                            @error('claimable_fraction')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="sort_order" class="block text-gray-700 text-sm font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('sort_order') border-red-500 @enderror">
                            @error('sort_order')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Create
                            </button>
                            <a href="{{ route('admin.reactive-costs.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
