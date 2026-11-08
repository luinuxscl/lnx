<?php

namespace App\Livewire\Sam;

use App\Actions\UpdateSamModeCeiling;
use App\Enums\Role;
use App\Models\SamModeCeiling;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Luinuxscl\LnxSam\SamMode;
use Luinuxscl\LnxSam\SamModeResolver;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

#[Title('Sam')]
class Index extends Component
{
    /**
     * Selected ceiling per role id. `inherit` clears the row.
     *
     * @var array<int, string>
     */
    public array $ceilings = [];

    public function mount(): void
    {
        $this->authorizeRoot();
        $this->loadCeilings();
    }

    public function save(int $roleId, UpdateSamModeCeiling $update): void
    {
        $this->authorizeRoot();

        $role = SpatieRole::query()->find($roleId);

        if (! $role instanceof SpatieRole) {
            $this->addError('ceilings', __('That role does not exist.'));

            return;
        }

        $this->validate([
            'ceilings.'.$roleId => ['required', 'in:'.implode(',', $this->allowedValues())],
        ]);

        try {
            $changed = $update->handle(auth()->user(), $role->name, $this->ceilings[$roleId]);
        } catch (InvalidArgumentException) {
            $this->addError('ceilings', __('That role does not exist.'));

            return;
        }

        if ($changed) {
            Flux::toast(variant: 'success', text: __('The role ceiling was saved.'));
        }
    }

    public function render(SamModeResolver $modes): View
    {
        $this->authorizeRoot();

        $global = $modes->global();

        return view('livewire.sam.index', [
            'rows' => $this->rows($global),
            'global' => $global,
            'options' => $this->options(),
        ])->layout('layouts.app');
    }

    /**
     * @return Collection<int, array{id: int, label: string, exceeds: bool}>
     */
    private function rows(SamMode $global): Collection
    {
        return SpatieRole::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (SpatieRole $role) use ($global): array {
                $id = $this->roleId($role);
                $value = $this->ceilings[$id] ?? UpdateSamModeCeiling::INHERIT;
                $selected = SamMode::tryFrom($value);

                return [
                    'id' => $id,
                    'label' => $this->roleLabel($role->name),
                    'exceeds' => $selected instanceof SamMode && $selected->rank() > $global->rank(),
                ];
            });
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function options(): array
    {
        $options = [[
            'value' => UpdateSamModeCeiling::INHERIT,
            'label' => __('Inherit'),
        ]];

        foreach (SamMode::cases() as $mode) {
            $options[] = [
                'value' => $mode->value,
                'label' => __($mode->label()),
            ];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function allowedValues(): array
    {
        return array_map(
            fn (array $option): string => $option['value'],
            $this->options(),
        );
    }

    private function loadCeilings(): void
    {
        $stored = SamModeCeiling::query()
            ->get(['role', 'mode'])
            ->mapWithKeys(fn (SamModeCeiling $ceiling): array => [$ceiling->role => $ceiling->mode->value]);

        $ceilings = [];

        foreach (SpatieRole::query()->orderBy('name')->get(['id', 'name']) as $role) {
            $ceilings[$this->roleId($role)] = $stored->get($role->name, UpdateSamModeCeiling::INHERIT);
        }

        $this->ceilings = $ceilings;
    }

    private function roleId(SpatieRole $role): int
    {
        $id = $role->getKey();

        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        throw new RuntimeException('The role id must be an integer.');
    }

    private function roleLabel(string $name): string
    {
        $role = Role::tryFrom($name);

        return $role instanceof Role ? __($role->label()) : $name;
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root), 403);
    }
}
