<?php

namespace App\Http\Requests;

use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\SpatialModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSpatialModelRequest extends FormRequest
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
        if (!$inspection instanceof Inspection) {
            return false;
        }

        $canManage = $this->userCanManageDigitalTwin()
            || $user->hasRole('Project Manager');

        return $canManage && $this->isAssignedStaff($inspection, (int) $user->id);
    }

    protected function prepareForValidation(): void
    {
        $providerModelId = trim((string) $this->input('provider_model_id', ''));
        $externalUrl = trim((string) $this->input('external_url', ''));

        if (!$providerModelId && str_contains($externalUrl, 'matterport.com')) {
            $query = parse_url($externalUrl, PHP_URL_QUERY);
            $params = [];

            if ($query) {
                parse_str($query, $params);
            }

            $providerModelId = trim((string) ($params['m'] ?? ''));
        }

        $this->merge([
            'provider_model_id' => $providerModelId ?: null,
            'status' => $this->input('status', 'active'),
            'processing_status' => $this->input('processing_status', 'ready'),
            'is_primary' => $this->boolean('is_primary'),
        ]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(array_keys(CaptureSession::PROVIDERS))],
            'capture_type' => ['required', Rule::in(array_keys(CaptureSession::CAPTURE_TYPES))],
            'source_type' => ['required', Rule::in(array_keys(SpatialModel::SOURCE_TYPES))],
            'display_name' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_serial' => ['nullable', 'string', 'max:255'],
            'runtime_format' => ['nullable', 'string', 'max:40'],
            'original_format' => ['nullable', 'string', 'max:40'],
            'provider_model_id' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:255'],
            'source_file' => [
                'nullable',
                'file',
                'extensions:glb,gltf,obj,fbx,dae,ply,e57,las,laz,pts,ptx,xyz,zip,jpg,jpeg,png,webp,pdf,heic,heif',
                'max:' . config('digital_twin.upload_max_kilobytes', 102400),
            ],
            'thumbnail_file' => ['nullable', 'image', 'max:10240'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'processing_status' => ['required', Rule::in(['queued', 'processing', 'ready', 'failed'])],
            'is_primary' => ['boolean'],
            'accuracy_class' => ['nullable', 'string', 'max:80'],
            'captured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasSourceFile = $this->hasFile('source_file');
            $hasExternalUrl = filled($this->input('external_url'));
            $hasProviderModelId = filled($this->input('provider_model_id'));

            if (!$hasSourceFile && !$hasExternalUrl && !$hasProviderModelId) {
                $validator->errors()->add('source_file', 'Attach a file, external URL, or provider model ID for this capture source.');
            }

            if ($this->input('provider') === 'matterport' && !$hasExternalUrl && !$hasProviderModelId) {
                $validator->errors()->add('provider_model_id', 'Matterport capture sources need a Matterport model ID or hosted tour URL.');
            }

            if ($this->input('source_type') === 'master_point_cloud' && !$hasSourceFile && $this->input('processing_status') === 'queued') {
                $validator->errors()->add('source_file', 'Upload the point-cloud file before queuing conversion. External point-cloud links can be stored as references, but cannot be converted by the local worker.');
            }
        });
    }

    private function userCanManageDigitalTwin(): bool
    {
        try {
            return (bool) ($this->user()?->can('manage digital twin models')
                || $this->user()?->can('attach matterport models'));
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
