<?php

namespace App\Livewire\Packages;

use App\Enums\Role;
use App\Support\Packages\PackageVersion;
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

    public function mount(): void
    {
        $this->authorizeRoot();

        $package = $this->version();
        $this->installedVersion = $package->installedVersion();
        $this->applyState($package->state(), $package);
    }

    public function checkForUpdates(): void
    {
        $this->authorizeRoot();

        $package = $this->version();
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
            'packageName' => $this->version()->name(),
            'updateCommand' => $this->version()->updateCommand(),
            'galleryEnabled' => (bool) config('app.design_gallery_enabled'),
        ])->layout('layouts.app');
    }

    /**
     * The lnx-ui package is not part of the generic registry: it keeps its own
     * page with the design gallery, fed by the shared version resolver.
     */
    private function version(): PackageVersion
    {
        return new PackageVersion((string) config('lnx_ui.repository', 'luinuxscl/lnx-ui'));
    }

    /**
     * @param  array{version: ?string, error: ?string, checked_at: ?string}  $state
     */
    private function applyState(array $state, PackageVersion $package): void
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
