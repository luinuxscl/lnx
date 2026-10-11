<?php

namespace App\Livewire\Packages;

use App\Enums\Role;
use App\Support\Packages\PackageRegistry;
use App\Support\Packages\PackageVersion;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Packages')]
class Index extends Component
{
    /**
     * @var list<array{name: string, label: string, description: ?string, repository: string, url: ?string, installed_version: string, latest_version: ?string, check_error: ?string, checked_at: ?string, update_available: bool, update_command: string}>
     */
    public array $packages = [];

    public function mount(PackageRegistry $registry): void
    {
        $this->authorizeRoot();

        $this->packages = $this->buildPackages($registry);
    }

    public function checkForUpdates(PackageRegistry $registry): void
    {
        $this->authorizeRoot();

        $failures = 0;

        foreach ($registry->all() as $package) {
            if ($this->version($package['name'], $package['repository'])->refresh()['error'] !== null) {
                $failures++;
            }
        }

        $this->packages = $this->buildPackages($registry);

        match (true) {
            $failures > 0 => Flux::toast(variant: 'danger', text: __('Could not check for updates.')),
            $this->hasUpdateAvailable() => Flux::toast(variant: 'warning', text: __('A newer version is available.')),
            default => Flux::toast(variant: 'success', text: __('Every installed package is up to date.')),
        };
    }

    public function render(): View
    {
        $this->authorizeRoot();

        return view('livewire.packages.index')->layout('layouts.app');
    }

    /**
     * @return list<array{name: string, label: string, description: ?string, repository: string, url: ?string, installed_version: string, latest_version: ?string, check_error: ?string, checked_at: ?string, update_available: bool, update_command: string}>
     */
    private function buildPackages(PackageRegistry $registry): array
    {
        $packages = [];

        foreach ($registry->all() as $package) {
            $version = $this->version($package['name'], $package['repository']);
            $state = $version->state();

            $packages[] = [
                'name' => $package['name'],
                'label' => $package['label'],
                'description' => $package['description'],
                'repository' => $package['repository'],
                'url' => $package['url'],
                'installed_version' => $version->installedVersion(),
                'latest_version' => $state['version'],
                'check_error' => $state['error'],
                'checked_at' => $state['checked_at'],
                'update_available' => $version->isUpdateAvailable($state['version']),
                'update_command' => $package['update_command'],
            ];
        }

        return $packages;
    }

    private function version(string $name, string $repository): PackageVersion
    {
        return new PackageVersion($name, $repository);
    }

    private function hasUpdateAvailable(): bool
    {
        foreach ($this->packages as $package) {
            if ($package['update_available']) {
                return true;
            }
        }

        return false;
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root), 403);
    }
}
