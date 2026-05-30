<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiKnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiKnowledgeDocument extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiKnowledgeDocumentFactory::new();
    }

    public static function generateId(): string
    {
        return Str::uuid()->toString();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_knowledge_documents';

    protected $fillable = [
        'id',
        'title',
        'source_path',
        'source_type',
        'content_hash',
        'chunk_count',
        'metadata',
        'indexed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'chunk_count' => 'integer',
        'indexed_at' => 'datetime',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }
}
