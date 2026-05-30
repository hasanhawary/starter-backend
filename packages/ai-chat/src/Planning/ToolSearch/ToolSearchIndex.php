<?php

namespace AiChat\Planning\ToolSearch;

interface ToolSearchIndex
{
    public function index(array $tools): void;

    public function search(string $query, int $limit = 5): array;
}
