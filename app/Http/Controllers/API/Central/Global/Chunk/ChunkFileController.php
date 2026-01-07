<?php

namespace App\Http\Controllers\API\Central\Global\Chunk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Global\Chunk\ChunkFileRequest;
use HasanHawary\MediaManager\Support\ChunkResolver;
use Illuminate\Http\JsonResponse;

class ChunkFileController extends Controller
{
    /**
     * @param ChunkFileRequest $request
     * @param ChunkResolver $chunkService
     * @return JsonResponse
     */
    public function __invoke(ChunkFileRequest $request, ChunkResolver $chunkService): JsonResponse
    {
        $path = $chunkService->upload($request->validated(), $request->path, (bool)$request->is_final);

        return successResponse(['path' => $path]);
    }
}
