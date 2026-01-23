<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Stewardship Loss Reduction Entry') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.stewardship-loss.update', $stewardshipLoss) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="cpi_band" class="block text-gray-700 text-sm font-bold mb-2">CPI Band *</label>
                            <input type="text" name="cpi_band" id="cpi_band" value="{{ old('cpi_band', $stewardshipLoss->cpi_band) }}" placeholder="e.g., CPI-0, CPI-1, CPI-2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('cpi_band') border-red-500 @enderror" required>
                            @error('cpi_band')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="loss_reduction" class="block text-gray-700 text-sm font-bold mb-2">Loss Reduction (0.00 - 1.00) *</label>
                            <input type="number" step="0.01" min="0" max="1" name="loss_reduction" id="loss_reduction" value="{{ old('loss_reduction', $stewardshipLoss->loss_reduction) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('loss_reduction') border-red-500 @enderror" required>
                            <p class="text-gray-600 text-xs italic mt-1">Enter as decimal (e.g., 0.20 for 20%)</p>
                            @error('loss_reduction')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror">{{ old('description', $stewardshipLoss->description) }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="sort_order" class="block text-gray-700 text-sm font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $stewardshipLoss->sort_order) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('sort_order') border-red-500 @enderror">
                            @error('sort_order')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update
                            </button>
                            <a href="{{ route('admin.stewardship-loss.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
