<?php

namespace AiChat\MCP;

use AiChat\Contracts\ToolInterface;
use AiChat\Policies\ChatContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool as LaravelTool;
use Laravel\Ai\Tools\Request;

class ToolAdapter implements LaravelTool
{
    public function __construct(
        protected ToolInterface $tool,
        protected array $contextPayload = [],
    ) {}

    public function name(): string
    {
        return $this->tool->name();
    }

    public function description(): string
    {
        return $this->tool->description();
    }

    public function handle(Request $request): string
    {
        $context = new ChatContext(
            action: 'read',
            payload: array_merge($this->contextPayload, [
                'arguments' => $request->all(),
            ]),
        );

        $result = app(ToolExecutor::class)->execute($this->tool->name(), $request->all(), $context);

        return json_encode($result->toArray());
    }

    public function schema(JsonSchema $schema): array
    {
        $toolSchema = $this->tool->schema();

        if (isset($toolSchema['properties'])) {
            $result = [];
            foreach ($toolSchema['properties'] as $name => $prop) {
                $field = $schema->string();
                if (isset($prop['description'])) {
                    $field = $field->description($prop['description']);
                }
                $required = in_array($name, $toolSchema['required'] ?? []);
                if ($required) {
                    $field = $field->required();
                }
                $result[$name] = $field;
            }

            return $result;
        }

        return $toolSchema;
    }

    public function getUnderlyingTool(): ToolInterface
    {
        return $this->tool;
    }
}
