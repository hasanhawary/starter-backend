<?php

namespace App\AI\Policies;

use AiChat\Contracts\ChatPolicyInterface;
use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyResult;

class {{CLASS_NAME}} implements ChatPolicyInterface
{
    public function authorize(ChatContext $context): PolicyResult
    {
        {{POLICY_LOGIC}}
    }
}
