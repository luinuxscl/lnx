<?php

namespace App\Livewire\Packages;

use App\Enums\Role;
use App\Support\LnxUiPackage;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('lnx-ui')]
class LnxUi extends Component
{
    public string $installedVersion = '';

    public ?string $latestVersion = null;

    public ?string $checkError = null;

    public ?string $checkedAt = null;

    public bool $updateAvailable = false;

    public function mount(LnxUiPackage $package): void
    {
        $this->authorizeRoot();

        $this->installedVersion = $package->installedVersion();
        $this->applyState($package->state(), $package);
    }

    public function checkForUpdates(LnxUiPackage $package): void
    {
        $this->authorizeRoot();

        $state = $package->refresh();
        $this->applyState($state, $package);

        match (true) {
            $state['error'] !== null => Flux::toast(variant: 'danger', text: __('Could not check for updates.')),
            $this->updateAvailable => Flux::toast(variant: 'warning', text: __('A newer version is available.')),
            default => Flux::toast(variant: 'success', text: __('The installed package is up to date.')),
        };
    }

    public function render(): View
    {
        $this->authorizeRoot();

        return view('livewire.packages.lnx-ui', [
            'packageName' => app(LnxUiPackage::class)->name(),
            'updateCommand' => app(LnxUiPackage::class)->updateCommand(),
            'galleryEnabled' => (bool) config('app.design_gallery_enabled'),
        ])->layout('layouts.app');
    }

    /**
     * @param  array{version: ?string, error: ?string, checked_at: ?string}  $state
     */
    private function applyState(array $state, LnxUiPackage $package): void
    {
        $this->latestVersion = $state['version'];
        $this->checkError = $state['error'];
        $this->checkedAt = $state['checked_at'];
        $this->updateAvailable = $package->isUpdateAvailable($state['version']);
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root), 403);
    }
}
