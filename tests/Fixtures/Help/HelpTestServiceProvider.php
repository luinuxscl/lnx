<?php

namespace Tests\Fixtures\Help;

use App\Support\Help\HelpRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * A package-like provider that declares help entries during boot, proving the
 * registry is fed by installed modules and not by the kit itself.
 */
class HelpTestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(HelpRegistry::class)->register('demo', [
            'demo.users' => [
                'type' => 'article',
                'group' => 'help.test.groups.administration',
                'title' => 'help.test.users.title',
                'summary' => 'help.test.users.summary',
                'body' => 'help.test.users.body',
                'roles' => ['admin'],
                'order' => 2,
            ],
            'demo.getting-started' => [
                'type' => 'article',
                'group' => 'help.test.groups.getting_started',
                'title' => 'help.test.getting_started.title',
                'summary' => 'help.test.getting_started.summary',
                'body' => 'help.test.getting_started.body',
                'order' => 1,
                'route' => 'dashboard',
            ],
            'demo.faq' => [
                'type' => 'faq',
                'group' => 'help.test.groups.faq',
                'title' => 'help.test.faq.title',
                'summary' => 'help.test.faq.summary',
                'body' => 'help.test.faq.body',
                'order' => 3,
            ],
        ]);
    }
}
