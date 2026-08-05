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
            'is_primary' => $this->boolean('is_primary'),
        ]);
    }

    public function rules(): array
    {
        $supportedExtensions = implode(',', (array) config('digital_twin.supported_extensions', []));

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
                'extensions:' . $supportedExtensions,
                'max:' . config('digital_twin.upload_max_kilobytes', 102400),
            ],
            'thumbnail_file' => ['nullable', 'image', 'max:10240'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
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

            if ($this->input('provider') === 'matterport' && !$hasExternalUrl && !$hasProviderModelId && !$hasSourceFile) {
                $validator->errors()->add('provider_model_id', 'Matterport capture sources need a Matterport model ID, hosted tour URL, or MatterPak ZIP upload.');
            }

            if ($hasSourceFile) {
                $file = $this->file('source_file');
                $extension = strtolower((string) $file->getClientOriginalExtension());
                $mimeType = strtolower((string) $file->getMimeType());
                $allowedMimeTypes = array_map('strtolower', (array) config("digital_twin.mime_types.{$extension}", []));

                if ($allowedMimeTypes !== [] && !in_array($mimeType, $allowedMimeTypes, true)) {
                    $validator->errors()->add(
                        'source_file',
                        'The selected capture source does not match the expected file type for .' . $extension . ' files.'
                    );
                }

                if ($extension === 'zip' && !class_exists(\ZipArchive::class)) {
                    $validator->errors()->add('source_file', 'ZIP twin uploads require the PHP ZipArchive extension.');
                }

                if ($extension === 'zip' && class_exists(\ZipArchive::class)) {
                    $zip = new \ZipArchive();
                    $containsObj = false;
                    $opened = false;

                    if ($zip->open($file->getRealPath()) === true) {
                        $opened = true;

                        for ($index = 0; $index < $zip->numFiles; $index++) {
                            $name = strtolower((string) $zip->getNameIndex($index));

                            if (str_ends_with($name, '.obj')) {
                                $containsObj = true;
                                break;
                            }
                        }

                        $zip->close();
                    }

                    if (!$opened) {
                        $validator->errors()->add('source_file', 'The ZIP twin upload could not be opened.');
                    }

                    if (!$containsObj) {
                        $validator->errors()->add('source_file', 'MatterPak or OBJ ZIP uploads must contain an OBJ mesh file.');
                    }
                }
            }

        });
    }

    public function messages(): array
    {
        return [
            'source_file.extensions' => 'Upload a supported twin source file: GLB, glTF, OBJ, MatterPak or OBJ ZIP bundle, E57, LAS, LAZ, JPG, JPEG, PNG, WEBP, or PDF.',
        ];
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
