<?php

namespace AiChat\RAG;

use Illuminate\Support\Facades\File;

class DocumentLoader
{
    protected array $supportedExtensions = ['md', 'txt', 'json'];

    public function loadFile(string $path): ?array
    {
        if (! File::exists($path)) {
            return null;
        }

        $extension = File::extension($path);

        if (! in_array($extension, $this->supportedExtensions)) {
            return null;
        }

        $content = File::get($path);

        if ($extension === 'json') {
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $content = is_array($decoded) ? $this->flattenJsonContent($decoded) : (string) $decoded;
        }

        return [
            'title' => File::name($path),
            'content' => $content,
            'source_path' => $path,
            'source_type' => $extension,
        ];
    }

    public function loadDirectory(string $path): array
    {
        if (! File::isDirectory($path)) {
            return [];
        }

        $documents = [];

        $files = File::allFiles($path);

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), $this->supportedExtensions)) {
                continue;
            }

            $document = $this->loadFile($file->getPathname());

            if ($document !== null) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    protected function flattenJsonContent(array $data, string $prefix = ''): string
    {
        $lines = [];

        foreach ($data as $key => $value) {
            $label = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $lines[] = $this->flattenJsonContent($value, $label);
            } else {
                $lines[] = "{$label}: ".(string) $value;
            }
        }

        return implode("\n", $lines);
    }
}
