<?php

use App\Support\AppVersion;
use Symfony\Component\Process\Process;

it('prefers an explicit app version over the cache file', function () {
    $cache = tmpVersionCache('v9.9.9');

    config()->set('app.version', 'v1.2.3');

    expect(AppVersion::resolve($cache))->toBe('v1.2.3');
});

it('resolves the cached git version when no app version is set', function () {
    $cache = tmpVersionCache('v1.2.3-2-gf95ad6c');

    config()->set('app.version', AppVersion::FALLBACK);

    expect(AppVersion::resolve($cache))->toBe('v1.2.3-2-gf95ad6c');
});

it('falls back to the placeholder without a cache file', function () {
    config()->set('app.version', AppVersion::FALLBACK);

    expect(AppVersion::resolve(sys_get_temp_dir().'/app-version-inexistente.php'))->toBe(AppVersion::FALLBACK);
});

it('ignores a malformed cache file', function () {
    $cache = tempnam(sys_get_temp_dir(), 'app-version-');
    file_put_contents($cache, "<?php\n\nreturn ['no-un-string'];\n");

    config()->set('app.version', AppVersion::FALLBACK);

    expect(AppVersion::resolve($cache))->toBe(AppVersion::FALLBACK);
});

it('caches the real git version of the repository', function () {
    $path = AppVersion::cachePath();
    File::delete($path);

    $this->artisan('app:version')->assertSuccessful();

    expect(is_file($path))->toBeTrue();

    $cached = include $path;

    expect($cached)->toBe(expectedGitVersion());
});

it('removes the cached version with --forget', function () {
    $path = AppVersion::cachePath();
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "<?php\n\nreturn 'v0.0.0-test';\n");

    $this->artisan('app:version', ['--forget' => true])->assertSuccessful();

    expect(is_file($path))->toBeFalse();
});

it('exposes the resolved version through config for consumers', function () {
    // The provider wires AppVersion into config on register, so every
    // consumer reading config('app.version') sees the resolved value.
    expect(config('app.version'))->toBe(AppVersion::resolve());
});

/**
 * Write a temporary version cache file with the given contents.
 */
function tmpVersionCache(string $version): string
{
    $path = tempnam(sys_get_temp_dir(), 'app-version-');
    file_put_contents($path, "<?php\n\nreturn ".var_export($version, true).";\n");

    return $path;
}

/**
 * The version the repository currently resolves to, as the command sees it.
 */
function expectedGitVersion(): string
{
    $process = new Process(
        ['git', 'describe', '--tags', '--long', '--always', '--dirty'],
        base_path(),
    );
    $process->run();

    return AppVersion::normalize($process->getOutput());
}
