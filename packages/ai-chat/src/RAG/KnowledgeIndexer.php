<?php

namespace AiChat\RAG;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiKnowledgeDocument;
use AiChat\Models\AiProjectMap;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KnowledgeIndexer
{
    public function __construct(
        protected DocumentLoader $loader,
        protected DocumentChunker $chunker,
        protected EmbeddingGenerator $embeddings,
    ) {}

    public function indexDocument(array $document): AiKnowledgeDocument
    {
        return DB::transaction(function () use ($document) {
            $contentHash = md5($document['content']);

            $existing = AiKnowledgeDocument::where('source_path', $document['source_path'])
                ->where('content_hash', $contentHash)
                ->first();

            if ($existing) {
                return $existing;
            }

            AiKnowledgeDocument::where('source_path', $document['source_path'])->delete();

            $doc = AiKnowledgeDocument::create([
                'id' => Str::uuid()->toString(),
                'title' => $document['title'],
                'source_path' => $document['source_path'],
                'source_type' => $document['source_type'],
                'content_hash' => $contentHash,
                'chunk_count' => 0,
                'metadata' => $document['metadata'] ?? [],
                'indexed_at' => now(),
            ]);

            $chunks = $this->chunker->chunk($document['content']);
            $chunkModels = [];

            foreach ($chunks as $chunk) {
                $embedding = $this->embeddings->generate($chunk['content']);

                $chunkModel = AiKnowledgeChunk::create([
                    'id' => Str::uuid()->toString(),
                    'document_id' => $doc->id,
                    'content' => $chunk['content'],
                    'chunk_index' => $chunk['chunk_index'],
                    'embedding' => $embedding,
                    'metadata' => $chunk['metadata'],
                ]);

                $chunkModels[] = $chunkModel;
            }

            $doc->update(['chunk_count' => count($chunkModels)]);

            return $doc;
        });
    }

    public function indexDirectory(string $path): int
    {
        $documents = $this->loader->loadDirectory($path);
        $count = 0;

        foreach ($documents as $document) {
            $this->indexDocument($document);
            $count++;
        }

        return $count;
    }

    public function indexProjectMap(): void
    {
        $projectMap = AiProjectMap::latest()->first();

        if (! $projectMap) {
            return;
        }

        $content = json_encode($projectMap->project_map, JSON_PRETTY_PRINT);

        $this->indexDocument([
            'title' => 'Project Map - '.$projectMap->scan_hash,
            'content' => $content,
            'source_path' => 'project_map://'.$projectMap->scan_hash,
            'source_type' => 'project_map',
            'metadata' => [
                'scan_hash' => $projectMap->scan_hash,
                'models_count' => $projectMap->models_count,
                'routes_count' => $projectMap->routes_count,
                'controllers_count' => $projectMap->controllers_count,
                'services_count' => $projectMap->services_count,
                'policies_count' => $projectMap->policies_count,
            ],
        ]);
    }

    public function removeStale(array $currentPaths): int
    {
        $stale = AiKnowledgeDocument::whereNotIn('source_path', $currentPaths)
            ->whereNot('source_type', 'project_map')
            ->get();

        $removed = 0;

        DB::transaction(function () use ($stale, &$removed) {
            foreach ($stale as $document) {
                $document->chunks()->delete();
                $document->delete();
                $removed++;
            }
        });

        return $removed;
    }
}
