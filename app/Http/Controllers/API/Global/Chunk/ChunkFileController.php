<?php

namespace App\Http\Controllers\API\Global\Chunk;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Chunk\ChunkFileRequest;
use HasanHawary\MediaManager\Support\ChunkResolver;
use Illuminate\Http\JsonResponse;

class ChunkFileController extends BaseController
{
    public function __invoke(ChunkFileRequest $request, ChunkResolver $chunkService): JsonResponse
    {
        $path = $chunkService->upload($request->validated(), $request->path, (bool) $request->is_final);

        return successResponse(['path' => $path]);
    }
}
