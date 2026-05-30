<?php

namespace AiChat\Console\Commands;

use AiChat\RAG\KnowledgeIndexer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IndexKnowledgeCommand extends Command
{
    protected $signature = 'ai-chat:index-knowledge {--path=app/AI/Knowledge : Knowledge path} {--reindex : Reindex all documents}';

    protected $description = 'Index knowledge documents for RAG';

    public function handle(KnowledgeIndexer $indexer): int
    {
        $path = base_path($this->option('path'));

        if (! File::isDirectory($path)) {
            $this->components->error("Knowledge path [{$path}] does not exist.");

            return self::FAILURE;
        }

        if ($this->option('reindex')) {
            $this->components->info('Reindexing all documents...');

            $indexer->removeStale([]);
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
            $content = File::get($file->getRealPath());

            try {
                $doc = $indexer->indexDocument([
                    'title' => $file->getBasename('.'.$file->getExtension()),
                    'content' => $content,
                    'source_path' => $relativePath,
                    'source_type' => $file->getExtension(),
                    'metadata' => [
                        'size' => $file->getSize(),
                        'mime_type' => File::mimeType($file->getRealPath()),
                    ],
                ]);

                if ($doc->wasRecentlyCreated) {
                    $this->components->task("Indexed {$relativePath}", fn () => true);
                    $indexed++;
                } else {
                    $this->components->twoColumnDetail($relativePath, '<fg=yellow>Unchanged</>');
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $this->components->error("Failed to index {$relativePath}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->components->info("Indexed: {$indexed}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
