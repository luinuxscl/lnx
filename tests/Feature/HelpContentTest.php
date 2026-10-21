<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('declares the minimum guides and faqs', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    $entries = app(HelpRegistry::class)->all($root);
    $guides = array_values(array_filter($entries, fn (array $entry): bool => $entry['type'] === 'article'));
    $faqs = array_values(array_filter($entries, fn (array $entry): bool => $entry['type'] === 'faq'));

    expect($guides)->toHaveCount(15)
        ->and($faqs)->toHaveCount(15);

    $guideSlugs = array_column($guides, 'slug');

    foreach ([
        'lnx.getting-started',
        'lnx.dashboard',
        'lnx.profile-security',
        'lnx.notifications',
        'lnx.users-invitations',
        'lnx.feedback-support',
        'lnx.chat',
        'lnx.knowledge',
        'lnx.documents',
        'lnx.emails',
        'lnx.packages',
        'lnx.activity',
        'lnx.setup',
        'lnx.preferences',
        'lnx.general-settings',
    ] as $slug) {
        expect($guideSlugs)->toContain($slug);
    }

    $faqSlugs = array_column($faqs, 'slug');

    foreach ([
        'lnx.faq.reset-password',
        'lnx.faq.contact-support',
        'lnx.faq.ai-disabled',
        'lnx.faq.install-package',
        'lnx.faq.invite-user',
    ] as $slug) {
        expect($faqSlugs)->toContain($slug);
    }
});

it('resolves every title, summary, group and body in both languages', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);
    $entries = app(HelpRegistry::class)->all($root);

    foreach (['es', 'en'] as $locale) {
        app()->setLocale($locale);

        foreach ($entries as $entry) {
            expect(__($entry['title']))->not->toBe($entry['title'], "[{$locale}] title {$entry['slug']}")
                ->and(__($entry['summary']))->not->toBe($entry['summary'], "[{$locale}] summary {$entry['slug']}")
                ->and(__($entry['body']))->not->toBe($entry['body'], "[{$locale}] body {$entry['slug']}")
                ->and(__($entry['group']))->not->toBe($entry['group'], "[{$locale}] group {$entry['slug']}")
                ->and(mb_strlen((string) __($entry['body'])))->toBeGreaterThan(40);
        }
    }
});

it('points every declared route to an existing named route', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    foreach (app(HelpRegistry::class)->all($root) as $entry) {
        if ($entry['route'] !== null) {
            expect(Route::has($entry['route']))->toBeTrue("[{$entry['slug']}] points to {$entry['route']}");
        }
    }
});

it('does not expose root-only guides to standard users or admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $registry = app(HelpRegistry::class);
    $userSlugs = array_column($registry->all($user), 'slug');
    $adminSlugs = array_column($registry->all($admin), 'slug');

    expect($userSlugs)->toContain('lnx.feedback-support')
        ->and($userSlugs)->not->toContain('lnx.general-settings')
        ->and($userSlugs)->not->toContain('lnx.activity')
        ->and($adminSlugs)->toContain('lnx.users-invitations')
        ->and($adminSlugs)->not->toContain('lnx.chat');
});
