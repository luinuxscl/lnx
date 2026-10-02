<?php

namespace App\Actions;

use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use App\Support\Appearance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ListAdministrativeActivity
{
    /**
     * Validation rules shared by the Livewire page and the MCP tool.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'action' => ['nullable', 'in:'.implode(',', array_column(AdministrativeAction::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * Human readable attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return ['action' => __('Action'), 'from' => __('From'), 'to' => __('To')];
    }

    /**
     * Filter and paginate the administrative activity history.
     *
     * @param  array{actor?: string, action?: string, from?: string, to?: string}  $filters
     * @return LengthAwarePaginator<int, AdministrativeActivity>
     *
     * @throws ValidationException
     */
    public function handle(array $filters, int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        Validator::make($filters, self::rules(), [], self::attributeNames())->validate();

        $actor = (string) ($filters['actor'] ?? '');
        $action = (string) ($filters['action'] ?? '');
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        $timezone = Appearance::currentTimezone();

        return AdministrativeActivity::query()
            ->when($actor !== '', function ($query) use ($actor): void {
                $term = '%'.addcslashes($actor, '%_\\').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('actor_name', 'like', $term)->orWhere('actor_email', 'like', $term)
                        ->orWhereRaw('CAST(actor_id AS CHAR) LIKE ?', [$term]);
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($from !== '', fn ($query) => $query->where('occurred_at', '>=', Carbon::createFromFormat('!Y-m-d', $from, $timezone)->startOfDay()->setTimezone((string) config('app.timezone'))))
            ->when($to !== '', fn ($query) => $query->where('occurred_at', '<=', Carbon::createFromFormat('!Y-m-d', $to, $timezone)->endOfDay()->setTimezone((string) config('app.timezone'))))
            ->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }
}
