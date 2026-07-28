@php
    $findingIndex = $findingIndex ?? null;
    $inspectionFindings = is_array($inspection->findings ?? null)
        ? $inspection->findings
        : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

    $legacyPaths = [];
    if ($findingIndex !== null && isset($inspectionFindings[$findingIndex]['finding_photos'])) {
        $legacyPaths = (array) $inspectionFindings[$findingIndex]['finding_photos'];
    }

    if (empty($legacyPaths)) {
        $legacyMatch = collect($inspectionFindings)->first(function ($row) use ($f) {
            return trim((string) ($row['finding'] ?? $row['task_question'] ?? '')) === trim((string) ($f->task_question ?? ''));
        });

        if (is_array($legacyMatch) && isset($legacyMatch['finding_photos'])) {
            $legacyPaths = (array) $legacyMatch['finding_photos'];
        }
    }

    $evidencePaths = collect(array_merge((array) ($f->photo_ids ?? []), $legacyPaths))
        ->filter(fn ($path) => is_string($path) && trim($path) !== '')
        ->map(fn ($path) => trim($path))
        ->unique()
        ->values();

    $isImageEvidence = function (string $path): bool {
        $cleanPath = strtolower(parse_url($path, PHP_URL_PATH) ?: $path);

        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|heic|heif)$/', $cleanPath);
    };

    $isVideoEvidence = function (string $path): bool {
        $cleanPath = strtolower(parse_url($path, PHP_URL_PATH) ?: $path);

        return (bool) preg_match('/\.(mp4|webm|mov|avi|mkv)$/', $cleanPath);
    };
@endphp

@if($evidencePaths->isNotEmpty())
    <div class="mt-3 p-2 px-3 rounded" style="background:#f8fbff;border-left:4px solid #0d6efd;">
        <div class="fw-bold small text-uppercase mb-2" style="color:#0d6efd;letter-spacing:.04em;">
            <i class="mdi mdi-image-multiple-outline me-1"></i>Evidence photos
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($evidencePaths as $index => $path)
                @php
                    $url = $inspection->getStorageUrl($path);
                    $label = 'Evidence ' . ($index + 1);
                @endphp

                @if($isImageEvidence($path))
                    <a href="{{ $url }}" target="_blank" class="d-inline-block" title="Open {{ strtolower($label) }}">
                        <img
                            src="{{ $url }}"
                            alt="{{ $label }}"
                            loading="lazy"
                            style="height:84px;width:84px;object-fit:cover;border-radius:6px;border:1px solid #d7e0ec;background:#fff;">
                    </a>
                @elseif($isVideoEvidence($path))
                    <video
                        src="{{ $url }}"
                        controls
                        preload="metadata"
                        style="height:84px;width:128px;object-fit:cover;border-radius:6px;border:1px solid #d7e0ec;background:#000;">
                    </video>
                @else
                    <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-light border">
                        <i class="mdi mdi-paperclip me-1"></i>{{ $label }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
