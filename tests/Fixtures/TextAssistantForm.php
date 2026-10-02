<?php

namespace Tests\Fixtures;

use Livewire\Component;

/**
 * Minimal parent form used to prove that the reusable assistant is two-way
 * bound to the host component through `wire:model`.
 */
class TextAssistantForm extends Component
{
    public string $description = 'Original text';

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <livewire:ai.text-assistant wire:model="description" />
            <p data-testid="value">{{ $description }}</p>
        </div>
        HTML;
    }
}
