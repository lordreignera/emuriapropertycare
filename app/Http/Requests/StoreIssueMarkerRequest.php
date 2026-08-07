<?php

namespace App\Http\Requests;

use App\Models\CaptureSession;
use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueMarkerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $decoded = [];

        foreach (['camera_position', 'camera_target', 'provenance'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) !== '') {
                $json = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $decoded[$field] = $json;
                }
            }
        }

        if ($decoded !== []) {
            $this->merge($decoded);
        }

        $this->merge([
            'create_phar_finding' => $this->boolean('create_phar_finding'),
        ]);
    }

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
        if (!$inspection instanceof Inspection) {
            return false;
        }

        $canCreate = $this->userCanCreateMarkers()
            || $user->hasAnyRole(['Project Manager', 'Inspector']);

        return $canCreate && $this->isAssignedStaff($inspection, (int) $user->id);
    }

    public function rules(): array
    {
        return [
            'spatial_model_id' => ['nullable', 'integer', 'exists:spatial_models,id'],
            'capture_session_id' => ['nullable', 'integer', 'exists:capture_sessions,id'],
            'phar_finding_id' => ['nullable', 'integer', 'exists:phar_findings,id'],
            'create_phar_finding' => ['nullable', 'boolean'],
            'building_system_id' => ['nullable', 'integer', 'exists:building_systems,id'],
            'building_subsystem_id' => ['nullable', 'integer', 'exists:building_subsystems,id'],
            'building_component_id' => ['nullable', 'integer', 'exists:building_components,id'],
            'source_provider' => ['required', Rule::in(array_merge(array_keys(CaptureSession::PROVIDERS), ['manual']))],
            'marker_type' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['required', Rule::in(['open', 'monitoring', 'quoted', 'in_progress', 'resolved', 'closed'])],
            'position_x' => ['nullable', 'numeric'],
            'position_y' => ['nullable', 'numeric'],
            'position_z' => ['nullable', 'numeric'],
            'normal_x' => ['nullable', 'numeric'],
            'normal_y' => ['nullable', 'numeric'],
            'normal_z' => ['nullable', 'numeric'],
            'camera_position' => ['nullable', 'array'],
            'camera_position.x' => ['nullable', 'numeric'],
            'camera_position.y' => ['nullable', 'numeric'],
            'camera_position.z' => ['nullable', 'numeric'],
            'camera_target' => ['nullable', 'array'],
            'camera_target.x' => ['nullable', 'numeric'],
            'camera_target.y' => ['nullable', 'numeric'],
            'camera_target.z' => ['nullable', 'numeric'],
            'object_uuid' => ['nullable', 'string', 'max:191'],
            'provenance' => ['nullable', 'array'],
            'room_name' => ['nullable', 'string', 'max:255'],
            'surface_label' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'between:0,100'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function userCanCreateMarkers(): bool
    {
        try {
            return (bool) ($this->user()?->can('create digital twin issue markers')
                || $this->user()?->can('create inspection findings'));
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAssignedStaff(Inspection $inspection, int $userId): bool
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
