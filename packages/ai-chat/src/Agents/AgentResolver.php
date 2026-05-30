<?php

namespace AiChat\Agents;

use AiChat\Contracts\AgentInterface;
use Illuminate\Http\Request;

class AgentResolver
{
    public function __construct(
        protected AgentManager $manager,
    ) {}

    public function resolveFromRequest(Request $request): AgentInterface
    {
        $name = $request->header('X-AI-Agent')
            ?? $request->input('agent')
            ?? $request->query('agent');

        if (is_string($name) && $name !== '') {
            $agent = $this->manager->get($name);

            if ($agent !== null) {
                return $agent;
            }
        }

        return $this->manager->default();
    }
}
