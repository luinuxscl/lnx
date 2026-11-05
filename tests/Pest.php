<?php

use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamTurn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Fixtures\Sam\FakeProposeTool;
use Tests\Fixtures\Sam\FakeReadTool;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Configure a ready AI provider for the Sam runtime.
 */
function configureSam(string $model = 'test/sam-model'): void
{
    config()->set('lnx_sam.enabled', true);
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', $model);
    config()->set('lnx_ai.allowed_models', [
        $model => [
            'tools' => true,
            'structured' => false,
            'streaming' => true,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
}

/**
 * Grant the surface permission to every identity for the current test.
 */
function grantSamPermission(): void
{
    Gate::define('sam.use', fn (User $user): bool => true);
}

/**
 * Reset the Sam registry and the shared fixture state between cases.
 */
function resetSamFixtures(): void
{
    FakeReadTool::$denies = false;
    FakeReadTool::$calls = 0;
    FakeProposeTool::$calls = 0;
    FakeSamContributor::$withPresenter = false;
    FakeSamContributor::$withPlainTool = false;
    FakeSamContributor::$knowledge = 'Dominio falso con contactos y empresas.';
    FakeSamContributor::$context = null;
    FakeSamContributor::$rowContext = null;
    FakeSamContributor::$signals = [];

    app(SamRegistry::class)->flush();
    SamTurn::clear();
}
