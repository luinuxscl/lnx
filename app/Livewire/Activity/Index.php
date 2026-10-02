<?php

namespace App\Livewire\Activity;

use App\Actions\ListAdministrativeActivity;
use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Administrative activity')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'actor')]
    public string $actor = '';

    #[Url(as: 'action')]
    public string $action = '';

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    public function updatingActor(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', AdministrativeActivity::class);

        try {
            $activities = app(ListAdministrativeActivity::class)->handle([
                'actor' => $this->actor,
                'action' => $this->action,
                'from' => $this->from,
                'to' => $this->to,
            ]);
            $this->resetValidation();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            $activities = new LengthAwarePaginator([], 0, 10, $this->getPage());
        }

        return view('livewire.activity.index', ['activities' => $activities, 'actions' => AdministrativeAction::cases()])
            ->layout('layouts.app', ['breadcrumbs' => [['label' => __('Administrative activity')]]]);
    }
}
