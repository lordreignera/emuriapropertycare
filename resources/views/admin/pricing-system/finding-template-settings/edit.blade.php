@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Finding Template</h4>

                <form action="{{ route('admin.finding-template-settings.update', $findingTemplateSetting) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="task_question">Task / Question <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('task_question') is-invalid @enderror" id="task_question" name="task_question" value="{{ old('task_question', $findingTemplateSetting->task_question) }}" required>
                        @error('task_question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="category">Category</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $findingTemplateSetting->category) }}">
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="photo_reference">Photo Reference</label>
                            <input type="text" class="form-control @error('photo_reference') is-invalid @enderror" id="photo_reference" name="photo_reference" value="{{ old('photo_reference', $findingTemplateSetting->photo_reference) }}" placeholder="e.g., R01">
                            @error('photo_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="default_priority">Default Priority <span class="text-danger">*</span></label>
                            <select class="form-control @error('default_priority') is-invalid @enderror" id="default_priority" name="default_priority" required>
                                <option value="1" {{ old('default_priority', $findingTemplateSetting->default_priority) == '1' ? 'selected' : '' }}>1 - High</option>
                                <option value="2" {{ old('default_priority', $findingTemplateSetting->default_priority) == '2' ? 'selected' : '' }}>2 - Medium</option>
                                <option value="3" {{ old('default_priority', $findingTemplateSetting->default_priority) == '3' ? 'selected' : '' }}>3 - Low</option>
                            </select>
                            @error('default_priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="default_labour_hours">Default Labour Hours <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" min="0" class="form-control @error('default_labour_hours') is-invalid @enderror" id="default_labour_hours" name="default_labour_hours" value="{{ old('default_labour_hours', $findingTemplateSetting->default_labour_hours) }}" required>
                            @error('default_labour_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $findingTemplateSetting->sort_order) }}">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="default_notes">Default Notes</label>
                        <textarea class="form-control @error('default_notes') is-invalid @enderror" id="default_notes" name="default_notes" rows="3">{{ old('default_notes', $findingTemplateSetting->default_notes) }}</textarea>
                        @error('default_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label me-3">
                            <input type="checkbox" class="form-check-input" name="default_included" value="1" {{ old('default_included', $findingTemplateSetting->default_included) ? 'checked' : '' }}>
                            Included by default
                        </label>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $findingTemplateSetting->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Update Template</button>
                        <a href="{{ route('admin.finding-template-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
