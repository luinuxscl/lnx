<?php

namespace App\Ai\Agents;

use App\Ai\LnxAgent;

/**
 * Agent behind the reusable text assistant.
 *
 * It only transforms the text it is given: it never invents content, wraps the
 * answer in commentary or returns HTML. Output and timeout are capped from the
 * text_assistant configuration so a single request stays bounded.
 */
class TextAssistantAgent extends LnxAgent
{
    public function instructions(): string
    {
        return <<<'PROMPT'
        You edit text on behalf of a user. Follow only the requested action and
        return exclusively the resulting text, with no preamble, explanation,
        quotes or Markdown fences. Never return HTML. Preserve the original
        language unless the action is to translate. Keep the user's meaning and
        do not add facts that are not present in the source text.
        PROMPT;
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('lnx_ai.text_assistant.max_output_tokens', 1200));
    }

    public function timeout(): int
    {
        return max(1, (int) config('lnx_ai.text_assistant.timeout', 30));
    }
}
