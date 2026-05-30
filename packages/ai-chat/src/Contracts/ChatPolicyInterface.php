<?php

namespace AiChat\Contracts;

use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyResult;

interface ChatPolicyInterface
{
    public function authorize(ChatContext $context): PolicyResult;
}
