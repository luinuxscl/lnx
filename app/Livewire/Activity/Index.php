<?php

namespace App\Livewire\Activity;

use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make([
            'action' => $this->action, 'from' => $this->from, 'to' => $this->to,
        ], [
            'action' => ['nullable', 'in:'.implode(',', array_column(AdministrativeAction::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $validator->setAttributeNames(['action' => __('Action'), 'from' => __('From'), 'to' => __('To')]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            $activities = new LengthAwarePaginator([], 0, 10, $this->getPage());
        } else {
            $this->resetValidation();
            $timezone = (string) config('app.timezone');
            $activities = AdministrativeActivity::query()
                ->when($this->actor !== '', function ($query): void {
                    $term = '%'.addcslashes($this->actor, '%_\\').'%';
                    $query->where(function ($query) use ($term): void {
                        $query->where('actor_name', 'like', $term)->orWhere('actor_email', 'like', $term)
                            ->orWhereRaw('CAST(actor_id AS CHAR) LIKE ?', [$term]);
                    });
                })
                ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
                ->when($this->from !== '', fn ($query) => $query->where('occurred_at', '>=', Carbon::createFromFormat('!Y-m-d', $this->from, $timezone)->startOfDay()))
                ->when($this->to !== '', fn ($query) => $query->where('occurred_at', '<=', Carbon::createFromFormat('!Y-m-d', $this->to, $timezone)->endOfDay()))
                ->orderByDesc('occurred_at')->orderByDesc('id')->paginate(10);
        }

        return view('livewire.activity.index', ['activities' => $activities, 'actions' => AdministrativeAction::cases()])
            ->layout('layouts.app', ['breadcrumbs' => [['label' => __('Administrative activity')]]]);
    }
}
