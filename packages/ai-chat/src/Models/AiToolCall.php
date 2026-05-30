<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiToolCallFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolCall extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiToolCallFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_tool_calls';

    protected $fillable = [
        'id',
        'conversation_id',
        'message_id',
        'tool_name',
        'arguments',
        'result',
        'status',
        'duration_ms',
        'error',
        'user_id',
        'tenant_id',
    ];

    protected $casts = [
        'arguments' => 'array',
        'result' => 'array',
        'duration_ms' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'message_id');
    }
}
