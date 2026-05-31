<?php

namespace AiChat\Planning\ToolSearch;

use AiChat\Support\ArabicTextNormalizer;

class ArrayToolSearchIndex implements ToolSearchIndex
{
    protected array $indexed = [];

    protected const STOP_WORDS = [
        'returns', 'return', 'get', 'gets', 'data', 'list', 'show', 'summary',
        'information', 'system', 'project', 'records', 'items', 'details',
        'the', 'and', 'or', 'for', 'with', 'is', 'a', 'an', 'of', 'to',
        'in', 'from', 'by', 'on', 'at', 'that', 'this', 'it',
        'عرض', 'ارجاع', 'يرجع', 'بيانات', 'معلومات', 'النظام', 'المشروع',
        'كل', 'من', 'في', 'على', 'منه', 'عنه', 'بها', 'لها',
    ];

    protected const SCORE_EXACT_NAME = 1.00;

    protected const SCORE_KEYWORD_PHRASE = 0.90;

    protected const SCORE_NORMALIZED_KEYWORD = 0.85;

    protected const SCORE_MODEL_ACTION = 0.80;

    protected const SCORE_STRONG_TAG = 0.70;

    protected const SCORE_DESCRIPTION = 0.70;

    public function index(array $tools): void
    {
        $this->indexed = [];

        foreach ($tools as $tool) {
            $name = method_exists($tool, 'name') ? $tool->name() : (string) $tool;
            $description = method_exists($tool, 'description') ? $tool->description() : '';
            $keywords = method_exists($tool, 'keywords') ? $tool->keywords() : [];
            $tags = method_exists($tool, 'tags') ? $tool->tags() : [];

            $this->indexed[$name] = [
                'name' => $name,
                'description' => $description,
                'keywords' => $keywords,
                'tags' => $tags,
                'name_normalized' => ArabicTextNormalizer::normalize($name),
                'description_normalized' => ArabicTextNormalizer::normalize($description),
                'keywords_normalized' => array_map(
                    fn (string $kw) => ArabicTextNormalizer::normalize($kw),
                    $keywords,
                ),
            ];
        }
    }

    public function search(string $query, int $limit = 5): array
    {
        $normalizedQuery = ArabicTextNormalizer::normalize($query);
        $queryWords = ArabicTextNormalizer::words($query);
        $results = [];

        foreach ($this->indexed as $name => $entry) {
            $score = $this->scoreTool($entry, $normalizedQuery, $queryWords);

            if ($score > 0) {
                $results[] = [
                    'name' => $name,
                    'score' => $score,
                ];
            }
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    protected function scoreTool(array $entry, string $normalizedQuery, array $queryWords): float
    {
        if ($normalizedQuery === $entry['name_normalized'] || $normalizedQuery === $entry['name']) {
            return self::SCORE_EXACT_NAME;
        }

        foreach ($entry['keywords_normalized'] as $kwNormalized) {
            if ($kwNormalized !== '' && $normalizedQuery === $kwNormalized) {
                return self::SCORE_KEYWORD_PHRASE;
            }
        }

        foreach ($entry['keywords_normalized'] as $kwNormalized) {
            if ($kwNormalized !== '' && str_contains($normalizedQuery, $kwNormalized)) {
                return self::SCORE_NORMALIZED_KEYWORD;
            }
        }

        $parts = explode('_', $entry['name_normalized']);
        $modelName = $parts[0] ?? '';
        $actionWord = $parts[1] ?? '';

        if ($modelName !== '' && $actionWord !== '') {
            $modelSynonyms = $this->getModelSynonyms($modelName);
            $modelMatched = false;

            foreach ($modelSynonyms as $synonym) {
                $normSynonym = ArabicTextNormalizer::normalize($synonym);
                if (str_contains($normalizedQuery, $normSynonym)) {
                    $modelMatched = true;

                    break;
                }
            }

            if ($modelMatched) {
                $actionSynonyms = $this->getActionSynonyms($actionWord);

                foreach ($actionSynonyms as $synonym) {
                    if (str_contains($normalizedQuery, $synonym)) {
                        return self::SCORE_MODEL_ACTION;
                    }
                }
            }
        }

        $score = $this->scoreTags($entry['tags'], $queryWords);

        if ($score > 0) {
            return $score;
        }

        return $this->scoreDescription($entry['description_normalized'], $queryWords);
    }

    protected function scoreTags(array $tags, array $queryWords): float
    {
        $matches = 0;

        foreach ($tags as $tag) {
            $tagWords = explode('_', str_replace('-', '_', ArabicTextNormalizer::normalize($tag)));

            foreach ($tagWords as $word) {
                if (mb_strlen($word) > 3 && in_array($word, $queryWords)) {
                    $matches++;
                }
            }
        }

        if ($matches >= 2) {
            return self::SCORE_STRONG_TAG;
        }

        if ($matches === 1) {
            return self::SCORE_STRONG_TAG;
        }

        return 0.0;
    }

    protected function scoreDescription(string $description, array $queryWords): float
    {
        $descWords = explode(' ', $description);
        $matches = 0;

        foreach ($queryWords as $qw) {
            if (in_array($qw, self::STOP_WORDS) || mb_strlen($qw) < 3) {
                continue;
            }

            foreach ($descWords as $dw) {
                if (mb_strlen($dw) > 5 && str_contains($dw, $qw)) {
                    $matches++;

                    break;
                }
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        $ratio = $matches / count(array_filter($queryWords, fn (string $w) => ! in_array($w, self::STOP_WORDS) && mb_strlen($w) >= 3));

        if ($ratio >= 0.6) {
            return self::SCORE_DESCRIPTION;
        }

        return 0.0;
    }

    protected function getModelSynonyms(string $model): array
    {
        $dialectSynonyms = config('ai-chat-dialects.model_synonyms', []);

        if (isset($dialectSynonyms[$model])) {
            return $dialectSynonyms[$model];
        }

        $builtin = [
            'user' => ['user', 'users', 'مستخدم', 'المستخدمين', 'مستخدمين'],
            'role' => ['role', 'roles', 'دور', 'الادوار', 'ادوار', 'رول'],
            'permission' => ['permission', 'permissions', 'صلاحية', 'الصلاحيات', 'صلاحيات'],
            'setting' => ['setting', 'settings', 'اعداد', 'الاعدادات', 'اعدادات'],
            'notification' => ['notification', 'notifications', 'اشعار', 'الاشعارات', 'اشعارات'],
            'country' => ['country', 'countries', 'دولة', 'بلاد', 'دول'],
            'order' => ['order', 'orders', 'طلب', 'الطلبات', 'طلبات'],
        ];

        return $builtin[$model] ?? [$model];
    }

    protected function getActionSynonyms(string $action): array
    {
        $dialectSynonyms = config('ai-chat-dialects.action_synonyms', []);

        if (isset($dialectSynonyms[$action])) {
            return $dialectSynonyms[$action];
        }

        return [];
    }
}
