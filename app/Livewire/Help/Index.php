<?php

namespace App\Livewire\Help;

use App\Models\User;
use App\Support\Help\HelpRegistry;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Help center')]
class Index extends Component
{
    public const TABS = ['guides', 'faq', 'support'];

    #[Url]
    public string $tab = 'guides';

    #[Url]
    public string $topic = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'guides';
        }

        $this->search = trim($this->search);
        $this->topic = trim($this->topic);
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->topic = '';
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->topic = '';
        }
    }

    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        $registry = app(HelpRegistry::class);
        $all = $registry->all($user);

        $selected = $this->topic === '' ? null : $registry->find($this->topic, $user);

        abort_if($this->topic !== '' && $selected === null, 404);

        $entries = $selected === null ? $registry->search($this->search, $user) : [];

        if ($selected === null && $this->search === '' && in_array($this->tab, ['guides', 'faq'], true)) {
            $type = $this->tab === 'faq' ? 'faq' : 'article';
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry): bool => $entry['type'] === $type,
            ));
        }

        $groups = [];
        $entryBodies = [];

        foreach ($entries as $entry) {
            $groups[$entry['group']][] = $entry;
            $entryBodies[$entry['slug']] = $registry->bodyHtml($entry);
        }

        return view('livewire.help.index', [
            'selected' => $selected,
            'selectedHtml' => $selected === null ? null : $registry->bodyHtml($selected),
            'entryBodies' => $entryBodies,
            'selectedRoute' => $selected === null ? null : $registry->routeUrl($selected),
            'groups' => $groups,
            'total' => count($all),
            'articleCount' => count(array_filter($all, fn (array $entry): bool => $entry['type'] === 'article')),
            'faqCount' => count(array_filter($all, fn (array $entry): bool => $entry['type'] === 'faq')),
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Help center')]],
        ]);
    }
}
