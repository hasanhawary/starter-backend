<?php

namespace AiChat\Search;

use AiChat\Vector\VectorManager;

class VectorSearch
{
    public function __construct(
        protected VectorManager $vectorManager,
    ) {}

    public function search(array $embedding, int $limit = 10, float $threshold = 0.7): array
    {
        return $this->vectorManager->search($embedding, $limit, $threshold);
    }
}
