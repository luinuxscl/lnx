<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('a user can upload and replace their avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('avatar', UploadedFile::fake()->image('first.png'))
        ->call('saveAvatar')
        ->assertHasNoErrors();

    $firstPath = $user->refresh()->avatar_path;

    expect($firstPath)->toStartWith("avatars/{$user->id}/");
    Storage::disk('public')->assertExists($firstPath);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('avatar', UploadedFile::fake()->image('second.jpg'))
        ->call('saveAvatar')
        ->assertHasNoErrors();

    $secondPath = $user->refresh()->avatar_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('the save avatar button remains usable and an empty submission shows a validation error', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test(Profile::class);

    preg_match('/<form[^>]*wire:submit="saveAvatar".*?<button([^>]*)>/s', $component->html(), $button);

    expect($button)->toHaveCount(2)
        ->and(preg_match('/(?:^|\s)disabled(?:\s|=|$)/', $button[1]))->toBe(0);

    $component->call('saveAvatar')->assertHasErrors(['avatar' => 'required']);

    $component->set('avatar', UploadedFile::fake()->image('avatar.png'));

    preg_match('/<form[^>]*wire:submit="saveAvatar".*?<button([^>]*)>/s', $component->html(), $button);

    expect($button)->toHaveCount(2)
        ->and(preg_match('/(?:^|\s)disabled(?:\s|=|$)/', $button[1]))->toBe(0);
});

test('invalid and oversized avatars are rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('avatar', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->call('saveAvatar')
        ->assertHasErrors(['avatar']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('avatar', UploadedFile::fake()->image('large.png')->size(2049))
        ->call('saveAvatar')
        ->assertHasErrors(['avatar']);

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty("avatars/{$user->id}");
});

test('a user can remove their avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('avatar.png')->store("avatars/{$user->id}", 'public');
    $user->forceFill(['avatar_path' => $path])->save();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->call('removeAvatar')
        ->assertHasNoErrors();

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('avatar URLs prefer the uploaded image and use the current email for Gravatar', function () {
    Storage::fake('public');

    $user = User::factory()->create(['email' => 'person@example.com']);
    $expectedHash = hash('sha256', 'person@example.com');

    expect($user->uploadedAvatarUrl())->toBeNull()
        ->and($user->gravatarUrl())->toBe("https://gravatar.com/avatar/{$expectedHash}?s=160&r=g&d=404");

    $user->email = ' Person@Example.com ';

    expect($user->gravatarUrl())->toBe("https://gravatar.com/avatar/{$expectedHash}?s=160&r=g&d=404");

    $user->email = 'person@example.com';

    $path = UploadedFile::fake()->image('avatar.png')->store("avatars/{$user->id}", 'public');
    $user->forceFill(['avatar_path' => $path])->save();

    expect($user->uploadedAvatarUrl())->toBe(Storage::disk('public')->url($path));

    $user->email = 'another@example.com';

    expect($user->gravatarUrl())->toContain(hash('sha256', 'another@example.com'));
});

test('the avatar file is deleted with the account', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('avatar.png')->store("avatars/{$user->id}", 'public');
    $user->forceFill(['avatar_path' => $path])->save();

    Livewire::actingAs($user)
        ->test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($path);
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
