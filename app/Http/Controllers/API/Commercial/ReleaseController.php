<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\CreateReleaseRequest;
use App\Models\Release;
use App\Services\Commercial\ReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends BaseController
{
    public function __construct(private readonly ReleaseService $releaseService)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $releases = Release::query()
            ->when($request->filled('channel'), fn ($query) => $query->where('channel', $request->string('channel')->upper()))
            ->latest('created_at')
            ->get()
            ->map(fn (Release $release): array => $this->data($release));

        return successResponse($releases);
    }

    public function store(CreateReleaseRequest $request): JsonResponse
    {
        $release = $this->releaseService->create($request->validated(), $request->user());

        return successResponse($this->data($release), 'Release created successfully.', 201);
    }

    public function publish(Request $request, Release $release): JsonResponse
    {
        $release = $this->releaseService->publish($release, $request->user());

        return successResponse($this->data($release), 'Release published successfully.');
    }

    public function pause(Request $request, Release $release): JsonResponse
    {
        $release = $this->releaseService->pause($release, $request->user());

        return successResponse($this->data($release), 'Release rollout paused.');
    }

    public function revoke(Request $request, Release $release): JsonResponse
    {
        $release = $this->releaseService->revoke($release, $request->user());

        return successResponse($this->data($release), 'Release revoked successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Release $release): array
    {
        return [
            'id' => $release->getKey(),
            'release_id' => $release->release_id,
            'version' => $release->product_version,
            'channel' => $release->channel,
            'status' => $release->status,
            'published_at' => $release->published_at?->toISOString(),
            'desktop_version' => $release->desktop_version,
            'edge_version' => $release->edge_version,
            'print_agent_minimum_version' => $release->print_agent_minimum_version,
            'schema_version' => $release->schema_version,
            'package_size' => $release->package_size,
            'package_sha256' => $release->package_sha256,
            'signing_key_id' => $release->signing_key_id,
            'minimum_current_version' => $release->minimum_current_version,
            'minimum_supported_version' => $release->minimum_supported_version,
            'mandatory' => $release->mandatory,
            'mandatory_deadline' => $release->mandatory_deadline?->toISOString(),
            'rollout' => $release->rollout,
            'release_notes' => $release->release_notes,
        ];
    }
}
