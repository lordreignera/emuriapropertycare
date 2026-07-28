<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatterportModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Administrator'])) {
            return true;
        }

        $inspection = $this->route('inspection');
        if (!$inspection instanceof \App\Models\Inspection) {
            return false;
        }

        $canAttach = $this->userCanAttachMatterport()
            || $user->hasRole('Project Manager');

        return $canAttach && $this->isAssignedStaff($inspection, (int) $user->id);
    }

    protected function prepareForValidation(): void
    {
        $sid = trim((string) $this->input('model_sid', ''));

        if (str_contains($sid, 'matterport.com')) {
            $query = parse_url($sid, PHP_URL_QUERY);
            $params = [];

            if ($query) {
                parse_str($query, $params);
            }

            $sid = trim((string) ($params['m'] ?? $sid));
        }

        $this->merge([
            'model_sid' => $sid,
            'status' => $this->input('status', 'active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'model_sid' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]{6,80}$/'],
            'model_name' => ['nullable', 'string', 'max:255'],
            'model_url' => ['nullable', 'url', 'max:255'],
            'thumbnail_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'scanned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function userCanAttachMatterport(): bool
    {
        try {
            return (bool) $this->user()?->can('attach matterport models');
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAssignedStaff(\App\Models\Inspection $inspection, int $userId): bool
    {
        $inspection->loadMissing(['property', 'project']);
        $property = $inspection->property;
        $project = $inspection->project;

        return (int) ($inspection->inspector_id ?? 0) === $userId
            || (int) ($property?->inspector_id ?? 0) === $userId
            || (int) ($property?->project_manager_id ?? 0) === $userId
            || (int) ($project?->managed_by ?? 0) === $userId;
    }
}
