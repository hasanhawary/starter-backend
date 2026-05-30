<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiMemoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMemory extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiMemoryFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_memories';

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'content',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
