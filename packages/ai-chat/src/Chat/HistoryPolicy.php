<?php

namespace AiChat\Chat;

class HistoryPolicy
{
    public bool $useHistory = false;

    public string $mode = 'none';

    public ?string $query = null;

    public int $limit = 6;

    public static function none(): self
    {
        $policy = new self;
        $policy->useHistory = false;
        $policy->mode = 'none';
        $policy->query = null;
        $policy->limit = 0;

        return $policy;
    }

    public static function recent(int $limit = 6): self
    {
        $policy = new self;
        $policy->useHistory = true;
        $policy->mode = 'recent';
        $policy->query = null;
        $policy->limit = $limit;

        return $policy;
    }

    public static function relevant(string $query, int $limit = 6): self
    {
        $policy = new self;
        $policy->useHistory = true;
        $policy->mode = 'relevant';
        $policy->query = $query;
        $policy->limit = $limit;

        return $policy;
    }

    public static function summary(): self
    {
        $policy = new self;
        $policy->useHistory = true;
        $policy->mode = 'summary';
        $policy->query = null;
        $policy->limit = 20;

        return $policy;
    }

    public function toArray(): array
    {
        return [
            'use_history' => $this->useHistory,
            'mode' => $this->mode,
            'query' => $this->query,
            'limit' => $this->limit,
        ];
    }

    public static function fromArray(array $data): self
    {
        $mode = $data['mode'] ?? 'none';

        return match ($mode) {
            'recent' => self::recent($data['limit'] ?? 6),
            'relevant' => self::relevant($data['query'] ?? '', $data['limit'] ?? 6),
            'summary' => self::summary(),
            default => self::none(),
        };
    }
}
