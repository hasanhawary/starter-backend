<?php

namespace AiChat\RAG;

class DocumentChunker
{
    public function chunk(string $content, int $chunkSize = 500, int $overlap = 50): array
    {
        if (trim($content) === '') {
            return [];
        }

        $paragraphs = $this->splitByParagraphs($content);
        $chunks = [];
        $currentIndex = 0;
        $buffer = '';
        $overlapBuffer = '';

        foreach ($paragraphs as $paragraph) {
            if (mb_strlen($buffer) + mb_strlen($paragraph) + 1 <= $chunkSize) {
                $buffer .= ($buffer !== '' ? "\n\n" : '').$paragraph;

                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $this->makeChunk($buffer, $currentIndex, $overlapBuffer);
                $overlapBuffer = $this->extractOverlap($buffer, $overlap);
                $currentIndex++;
                $buffer = '';
            }

            $sentences = $this->splitBySentences($paragraph);

            foreach ($sentences as $sentence) {
                if (mb_strlen($buffer) + mb_strlen($sentence) + 1 > $chunkSize && $buffer !== '') {
                    $chunks[] = $this->makeChunk($buffer, $currentIndex, $overlapBuffer);
                    $overlapBuffer = $this->extractOverlap($buffer, $overlap);
                    $currentIndex++;
                    $buffer = '';
                }

                if (mb_strlen($sentence) > $chunkSize) {
                    $subChunks = $this->splitBySize($sentence, $chunkSize);

                    foreach ($subChunks as $subChunk) {
                        if ($buffer !== '') {
                            $buffer .= ' '.$subChunk;

                            if (mb_strlen($buffer) > $chunkSize) {
                                $chunks[] = $this->makeChunk($buffer, $currentIndex, $overlapBuffer);
                                $overlapBuffer = $this->extractOverlap($buffer, $overlap);
                                $currentIndex++;
                                $buffer = '';
                            }
                        } else {
                            $buffer = $subChunk;
                        }
                    }
                } else {
                    $buffer .= ($buffer !== '' ? ' ' : '').$sentence;
                }
            }
        }

        if ($buffer !== '') {
            $chunks[] = $this->makeChunk($buffer, $currentIndex, $overlapBuffer);
        }

        return $chunks;
    }

    protected function splitByParagraphs(string $content): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $content);

        return array_values(array_filter(array_map('trim', $paragraphs), fn ($p) => $p !== ''));
    }

    protected function splitBySentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);

        return array_values(array_filter(array_map('trim', $sentences), fn ($s) => $s !== ''));
    }

    protected function splitBySize(string $text, int $size): array
    {
        $chunks = [];
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i += $size) {
            $chunks[] = mb_substr($text, $i, $size);
        }

        return $chunks;
    }

    protected function extractOverlap(string $buffer, int $overlapSize): string
    {
        if ($overlapSize <= 0) {
            return '';
        }

        $words = explode(' ', $buffer);
        $overlapWords = array_slice($words, -(int) ceil($overlapSize / 5));

        return implode(' ', $overlapWords);
    }

    protected function makeChunk(string $content, int $index, string $overlap = ''): array
    {
        return [
            'content' => $content,
            'chunk_index' => $index,
            'metadata' => [
                'overlap' => $overlap,
                'char_count' => mb_strlen($content),
                'word_count' => str_word_count($content),
            ],
        ];
    }
}
