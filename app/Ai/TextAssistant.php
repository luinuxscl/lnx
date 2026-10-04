<?php

namespace App\Ai;

use App\Ai\Agents\TextAssistantAgent;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Reusable text assistant: improve, summarize or translate a single text.
 *
 * It only ever receives the explicit source text, selected action and target
 * language. It never reads other form fields. Input size, action and language
 * are validated server-side, the output is capped by the agent and every call
 * goes through {@see AiGateway}, so the shared allowlist, quota and usage
 * recording apply unchanged. Failures are reduced to a secret-free category
 * and the original text is left untouched.
 */
class TextAssistant
{
    /**
     * Supported actions.
     *
     * @var list<string>
     */
    public const ACTIONS = ['improve', 'summarize', 'translate', 'support'];

    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiErrorCategorizer $categorizer,
    ) {}

    /**
     * The supported actions.
     *
     * @return list<string>
     */
    public function actions(): array
    {
        return self::ACTIONS;
    }

    /**
     * The allowed target languages, in a stable order.
     *
     * @return list<string>
     */
    public function languages(): array
    {
        $languages = config('lnx_ai.text_assistant.languages', ['es', 'en']);
        $languages = is_array($languages) ? $languages : [];

        $normalized = [];

        foreach ($languages as $language) {
            if (is_string($language) && trim($language) !== '') {
                $normalized[] = trim($language);
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * A human-readable name for a target language, used in the prompt.
     */
    public function languageLabel(string $language): string
    {
        return match ($language) {
            'es' => 'Spanish',
            'en' => 'English',
            default => $language,
        };
    }

    /**
     * Maximum accepted input length in characters.
     */
    public function maxInputChars(): int
    {
        return max(1, (int) config('lnx_ai.text_assistant.max_input_chars', 8000));
    }

    /**
     * Whether the assistant can run against the current configuration.
     */
    public function isReady(): bool
    {
        return $this->gateway->isReady();
    }

    /**
     * Server-side validation rules, reusable by any caller.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:'.$this->maxInputChars()],
            'action' => ['required', 'string', Rule::in($this->actions())],
            'language' => ['nullable', 'string', Rule::in($this->languages()), 'required_if:action,translate'],
        ];
    }

    /**
     * Run the requested action and return a secret-free result.
     *
     * @param  string  $action  one of {@see self::ACTIONS}
     * @return array{status: string, category: string, text: string|null}
     */
    public function run(string $text, string $action, ?string $language): array
    {
        if (! in_array($action, $this->actions(), true)) {
            throw new \InvalidArgumentException("Unsupported text assistant action [{$action}].");
        }

        $language = $action === 'translate' ? $language : null;

        if ($language !== null && ! in_array($language, $this->languages(), true)) {
            throw new \InvalidArgumentException("Unsupported text assistant language [{$language}].");
        }

        if (! $this->isReady()) {
            return $this->error(AiErrorCategorizer::NOT_CONFIGURED);
        }

        try {
            $agent = new TextAssistantAgent;

            $response = $this->gateway->prompt(
                $agent,
                $this->prompt($text, $action, $language),
                application: 'text_assistant',
                reservedTokens: $agent->maxTokens(),
                timeout: $agent->timeout(),
            );

            return [
                'status' => 'success',
                'category' => AiErrorCategorizer::OK,
                'text' => trim($response->text),
            ];
        } catch (Throwable $exception) {
            return $this->error($this->categorizer->categorize($exception));
        }
    }

    /**
     * Build the delimited prompt sent to the model.
     */
    protected function prompt(string $text, string $action, ?string $language): string
    {
        $instruction = match ($action) {
            'summarize' => 'Summarize the text concisely, preserving its key ideas.',
            'translate' => 'Translate the text into '.$this->languageLabel((string) $language).'.',
            'support' => 'Improve this support request and suggest a short title. Return exactly two lines: Title: <title> and Description: <improved text>. Preserve the original language. Do not invent facts, steps, causes or outcomes.',
            default => 'Rewrite the text to be clearer and more correct, preserving its meaning.',
        };

        return $instruction."\n\n<text>\n".$text."\n</text>";
    }

    /**
     * Build a failure result with no text.
     *
     * @return array{status: string, category: string, text: string|null}
     */
    protected function error(string $category): array
    {
        return [
            'status' => 'error',
            'category' => $category,
            'text' => null,
        ];
    }
}
