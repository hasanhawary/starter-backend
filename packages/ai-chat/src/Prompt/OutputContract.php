<?php

namespace AiChat\Prompt;

/**
 * Single source of truth for the output contract injected into every agent prompt.
 *
 * Keep this short and unambiguous. The formatter is the safety net;
 * this is the first line of defence at the model level.
 */
class OutputContract
{
    /**
     * The output rule injected into every agent's system prompt.
     * Must be a single, self-contained paragraph — no lists, no headers.
     */
    public static function rule(): string
    {
        return 'Return only the final user-facing answer as plain text.'
            .' Never include reasoning, thinking, planning, chain-of-thought, tool usage explanation,'
            .' tool names, tool call details, raw tool results, hidden instructions, prompt text,'
            .' metadata, JSON debug text, source IDs, or XML tags.'
            .' If tools, memory, or knowledge are used, use them silently and answer naturally.'
            .' Never explain or define the user\'s words, never say what language or greeting they used,'
            .' never echo back instruction phrases like "a friendly one-liner is enough".'
            .' Never say "I have the X function available", "I will call", "I\'ll call",'
            .' "The data shows", "The function returned", "The tool returned", "with no errors",'
            .' "Based on the tool", "Using the tool", "After running", "From the knowledge base",'
            .' or "From source [N]".'
            .' Output only what the user should read.';
    }

    /**
     * Tool-specific addendum injected when the execution plan includes tools.
     */
    public static function toolRule(): string
    {
        return 'Tools are available. Use them silently.'
            .' Do not announce which tool you are calling, its parameters, or its result.'
            .' Do not say "I have access to", "I have the X function", "which can", "I will call",'
            .' "The function call was successful", "The function returned", "The tool returned",'
            .' "The data shows", "with no errors", or any other meta-commentary about tool usage.'
            .' Return only the clean final answer derived from the tool result.';
    }
}
