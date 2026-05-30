<?php

namespace AiChat\Console\Commands;

use AiChat\Contracts\VectorStoreInterface;
use AiChat\Models\AiKnowledgeDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IndexKnowledgeCommand extends Command
{
    protected $signature = 'ai-chat:index-knowledge {--path=app/AI/Knowledge : Knowledge path} {--reindex : Reindex all documents}';

    protected $description = 'Index knowledge documents for RAG';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! File::isDirectory($path)) {
            $this->components->error("Knowledge path [{$path}] does not exist.");

            return self::FAILURE;
        }

        if ($this->option('reindex')) {
            $this->components->info('Reindexing all documents...');
            AiKnowledgeDocument::query()->delete();

            $vectorStore = app(VectorStoreInterface::class);
            $vectorStore->deleteAll();
        }

        $extensions = ['md', 'txt', 'html', 'json'];
        $files = collect(File::allFiles($path))
            ->filter(fn ($file) => in_array($file->getExtension(), $extensions));

        if ($files->isEmpty()) {
            $this->components->warn('No knowledge documents found.');

            return self::SUCCESS;
        }

        $indexed = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $relativePath = str_replace(base_path().'/', '', $file->getRealPath());

            $contentHash = md5_file($file->getRealPath());

            if (! $this->option('reindex')) {
                $exists = AiKnowledgeDocument::where('source_path', $relativePath)
                    ->where('content_hash', $contentHash)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $this->components->twoColumnDetail($relativePath, '<fg=yellow>Unchanged</>');

                    continue;
                }
            }

            $content = File::get($file->getRealPath());

            AiKnowledgeDocument::updateOrCreate(
                ['source_path' => $relativePath],
                [
                    'id' => AiKnowledgeDocument::generateId(),
                    'title' => $file->getBasename('.'.$file->getExtension()),
                    'source_type' => $file->getExtension(),
                    'content_hash' => $contentHash,
                    'chunk_count' => 0,
                    'metadata' => [
                        'size' => $file->getSize(),
                        'mime_type' => File::mimeType($file->getRealPath()),
                    ],
                    'indexed_at' => now(),
                ],
            );

            $this->components->task("Indexed {$relativePath}", fn () => true);
            $indexed++;
        }

        $this->newLine();
        $this->components->info("Indexed: {$indexed}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
