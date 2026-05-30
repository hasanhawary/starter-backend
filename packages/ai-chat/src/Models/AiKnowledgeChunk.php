<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiKnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeChunk extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiKnowledgeChunkFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_knowledge_chunks';

    protected $fillable = [
        'id',
        'document_id',
        'content',
        'chunk_index',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeDocument::class, 'document_id');
    }
}
