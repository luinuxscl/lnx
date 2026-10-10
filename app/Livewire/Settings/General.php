<?php

namespace App\Livewire\Settings;

use App\Actions\RecordAdministrativeActivity;
use App\Enums\AdministrativeAction;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Title('General settings')]
class General extends Component
{
    use WithFileUploads;

    public string $site_title = '';

    public string $site_tagline = '';

    public string $meta_description = '';

    public string $admin_email = '';

    public string $footer_text = '';

    public string $analytics_id = '';

    public bool $maintenance_mode = false;

    public string $registration_mode = 'open';

    public string $maintenance_message = '';

    public mixed $logo = null;

    public mixed $logo_dark = null;

    public mixed $favicon = null;

    public mixed $apple_touch_icon = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('update', Setting::current());

        $settings = Setting::current();

        $this->site_title = $settings->title();
        $this->site_tagline = $settings->tagline() ?? '';
        $this->meta_description = $settings->meta_description ?? '';
        $this->admin_email = $settings->admin_email ?? '';
        $this->footer_text = $settings->footer() ?? '';
        $this->analytics_id = $settings->analytics_id ?? '';
        $this->maintenance_mode = (bool) $settings->maintenance_mode;
        $this->registration_mode = $settings->registration_mode ?? 'open';
        $this->maintenance_message = $settings->maintenance_message ?? '';
    }

    /**
     * Persist the site settings.
     */
    public function save(): void
    {
        $this->authorize('update', Setting::current());

        $validated = $this->validate();

        $values = [
            'site_title' => $validated['site_title'],
            'site_tagline' => $validated['site_tagline'] ?: null,
            'meta_description' => $validated['meta_description'] ?: null,
            'admin_email' => $validated['admin_email'] ?: null,
            'footer_text' => $validated['footer_text'] ?: null,
            'analytics_id' => $validated['analytics_id'] ?: null,
            'maintenance_mode' => $validated['maintenance_mode'],
            'registration_mode' => $validated['registration_mode'],
            'maintenance_message' => $validated['maintenance_message'] ?: null,
        ];
        $uploaded = [];
        try {
            foreach ($this->fileFields() as $property => $column) {
                if ($this->{$property} !== null) {
                    $uploaded[$column] = $this->{$property}->store('branding', 'public');
                }
            }
            DB::transaction(function () use ($values, $uploaded): void {
                if ($uploaded !== []) {
                    DB::afterRollBack(function () use ($uploaded): void {
                        foreach ($uploaded as $path) {
                            Storage::disk('public')->delete($path);
                        }
                    });
                }
                $settings = Setting::query()->first() ?? new Setting;
                $this->authorize('update', $settings);
                $fields = array_merge(array_keys($values), array_values($this->fileFields()));
                $before = $settings->only($fields);
                $oldFiles = [];
                foreach ($uploaded as $column => $path) {
                    $oldFiles[] = $settings->{$column};
                }
                $settings->fill([...$values, ...$uploaded]);
                if (! $settings->isDirty()) {
                    return;
                }
                $settings->save();
                app(RecordAdministrativeActivity::class)->handle(auth()->user(), AdministrativeAction::SettingsUpdated, $settings, $before, $settings->only($fields));
                DB::afterCommit(function () use ($oldFiles): void {
                    foreach ($oldFiles as $path) {
                        $this->deleteStoredFile($path);
                    }
                    Setting::forgetCache();
                });
            });
        } catch (Throwable $exception) {
            foreach ($uploaded as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        $this->reset(['logo', 'logo_dark', 'favicon', 'apple_touch_icon']);

        Flux::toast(variant: 'success', text: __('Settings saved.'));
    }

    /**
     * Remove a previously uploaded branding file.
     */
    public function removeFile(string $field): void
    {
        $this->authorize('update', Setting::current());

        $fileFields = $this->fileFields();

        abort_unless(array_key_exists($field, $fileFields), 404);

        DB::transaction(function () use ($field, $fileFields): void {
            $settings = Setting::query()->firstOrFail();
            $this->authorize('update', $settings);
            $column = $fileFields[$field];
            $oldPath = $settings->{$column};
            if ($oldPath === null) {
                return;
            }
            $settings->{$column} = null;
            $settings->save();
            app(RecordAdministrativeActivity::class)->handle(auth()->user(), AdministrativeAction::SettingsUpdated, $settings, [$column => $oldPath], [$column => null]);
            DB::afterCommit(function () use ($oldPath): void {
                $this->deleteStoredFile($oldPath);
                Setting::forgetCache();
            });
        });

        Flux::toast(variant: 'success', text: __('File removed.'));
    }

    /**
     * Get the validation rules for the settings form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'analytics_id' => ['nullable', 'string', 'max:50'],
            'maintenance_mode' => ['boolean'],
            'registration_mode' => ['required', 'in:open,invitation_only'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:1024'],
            'apple_touch_icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * Map the upload properties to their settings columns.
     *
     * @return array<string, string>
     */
    protected function fileFields(): array
    {
        return [
            'logo' => 'logo_path',
            'logo_dark' => 'logo_dark_path',
            'favicon' => 'favicon_path',
            'apple_touch_icon' => 'apple_touch_icon_path',
        ];
    }

    /**
     * Delete a stored branding file from the public disk.
     */
    protected function deleteStoredFile(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    public function render(): View
    {
        return view('livewire.settings.general', [
            'settings' => Setting::current(),
        ])->layout('layouts.app');
    }
}
