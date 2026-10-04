<?php

namespace App\Livewire\Support;

use App\Ai\TextAssistant;
use App\Support\TicketService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Panel extends Component
{
    use WithFileUploads;

    public bool $showPanel = false;

    public string $type = 'problem';

    public string $title = '';

    public string $description = '';

    public ?bool $blocking = null;

    public ?string $screen = null;

    public ?string $routeName = null;

    public ?string $openedAt = null;

    public ?int $windowWidth = null;

    public ?int $windowHeight = null;

    public ?string $locale = null;

    public ?string $theme = null;

    public ?string $appVersion = null;

    public ?TemporaryUploadedFile $image = null;

    public ?string $proposalTitle = null;

    public ?string $proposalDescription = null;

    public ?string $aiError = null;

    public string $requestKey = '';

    /** @param array<string, mixed> $context */
    public function open(array $context = []): void
    {
        $this->requestKey = (string) Str::uuid();
        $this->screen = Str::limit(strip_tags((string) ($context['screen'] ?? '')), 160, '');
        $this->routeName = Str::limit((string) ($context['route_name'] ?? ''), 160, '');
        $this->openedAt = now()->toIso8601String();
        $this->windowWidth = isset($context['width']) ? min(65535, max(0, (int) $context['width'])) : null;
        $this->windowHeight = isset($context['height']) ? min(65535, max(0, (int) $context['height'])) : null;
        $this->locale = app()->getLocale();
        $this->theme = in_array(($context['theme'] ?? null), ['light', 'dark'], true) ? $context['theme'] : null;
        $this->appVersion = config('app.version');
        $this->showPanel = true;
    }

    public function suggest(TextAssistant $assistant): void
    {
        abort_unless(Gate::allows('use-support-assistant'), 403);
        $this->validate(['description' => ['required', 'string', 'max:'.$assistant->maxInputChars()]]);
        $this->aiError = null;
        $this->proposalTitle = null;
        $this->proposalDescription = null;
        $original = $this->description;
        $result = $assistant->run($original, 'support', null);
        if ($result['status'] !== 'success' || ! is_string($result['text'])) {
            $this->aiError = __('The assistant is unavailable. You can still send your request.');
        } elseif ($original !== $this->description || ! preg_match('/^Title:\s*(.+)\RDescription:\s*([\s\S]+)$/u', $result['text'], $matches)) {
            $this->aiError = __('The assistant response could not be used. Your text is unchanged.');
        } else {
            $this->proposalTitle = Str::limit(trim($matches[1]), 160, '');
            $this->proposalDescription = trim($matches[2]);
        }

        Flux::modal('support-panel')->show();
    }

    public function acceptProposal(): void
    {
        if ($this->proposalTitle !== null && $this->proposalDescription !== null) {
            $this->title = $this->proposalTitle;
            $this->description = $this->proposalDescription;
        }
        $this->discardProposal();
    }

    public function discardProposal(): void
    {
        $this->proposalTitle = null;
        $this->proposalDescription = null;
    }

    public function submit(TicketService $service): void
    {
        $data = $this->validate([
            'type' => ['required', 'in:problem,idea,question'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:8000'],
            'blocking' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);
        $ticket = $service->create(auth()->user(), [
            ...$data,
            'screen' => $this->screen,
            'route_name' => $this->routeName,
            'opened_at' => $this->openedAt,
            'window_width' => $this->windowWidth,
            'window_height' => $this->windowHeight,
            'locale' => $this->locale,
            'theme' => $this->theme,
            'app_version' => $this->appVersion,
        ], $this->image, $this->requestKey);
        $this->reset(['showPanel', 'title', 'description', 'blocking', 'image', 'proposalTitle', 'proposalDescription']);
        $this->redirectRoute('support.show', $ticket, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.support.panel');
    }
}
