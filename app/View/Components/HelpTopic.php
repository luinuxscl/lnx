<?php

namespace App\View\Components;

use App\Models\User;
use App\Support\Help\HelpCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Inline link to a help center entry, meant for the screens a guide explains.
 *
 * The component degrades to nothing when the identity is not authenticated,
 * when the slug does not exist or when the entry is restricted: a screen never
 * breaks because its help link points to content the user cannot read.
 */
class HelpTopic extends Component
{
    public function __construct(
        public string $slug,
        public ?string $label = null,
    ) {}

    public function render(): View|string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return '';
        }

        $entry = app(HelpCatalog::class)->find($this->slug, $user);

        if ($entry === null) {
            return '';
        }

        return view('components.help-topic', [
            'slug' => $this->slug,
            'entryTitle' => (string) __($entry['title']),
            'ariaLabel' => $this->label ?? __('Read the guide: :title', ['title' => __($entry['title'])]),
        ]);
    }
}
