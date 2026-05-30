<?php

namespace AiChat\Http\Controllers;

use AiChat\Http\Resources\ToolResource;
use AiChat\MCP\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ToolController extends Controller
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function listTools(): JsonResponse
    {
        $tools = array_values($this->toolRegistry->all());

        return successResponse(ToolResource::collection(collect($tools)));
    }

    public function getToolSchema(string $name): JsonResponse
    {
        $tool = $this->toolRegistry->get($name);

        if (! $tool) {
            return failResponse('Tool not found.', [], 404);
        }

        return successResponse(new ToolResource($tool));
    }
}
