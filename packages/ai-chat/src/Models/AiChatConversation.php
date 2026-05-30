<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiChatConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiChatConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_conversations';

    protected static function newFactory()
    {
        return AiChatConversationFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'session_id',
        'ip_address',
        'title',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id');
    }
}
