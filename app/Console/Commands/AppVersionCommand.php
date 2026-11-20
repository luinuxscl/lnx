<?php

namespace App\Console\Commands;

use App\Support\AppVersion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

#[Signature('app:version {--forget : Remove the cached version instead of refreshing it}')]
#[Description('Cache the application version resolved from Git tags (runs on composer install).')]
class AppVersionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = AppVersion::cachePath();

        if ($this->option('forget')) {
            File::delete($path);

            $this->info('Versión cacheada eliminada; la app usará el fallback o APP_VERSION.');

            return self::SUCCESS;
        }

        $version = $this->describe();

        if ($version === null) {
            // Deploy targets without Git metadata must not break composer install.
            File::delete($path);

            $this->warn('Git no está disponible o el árbol no tiene commits; no se cachea versión. La app usará el fallback o APP_VERSION.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "<?php\n\nreturn ".var_export($version, true).";\n");

        $this->info('Versión cacheada: '.$version);

        return self::SUCCESS;
    }

    /**
     * Run git describe in the project root, normalized for display.
     */
    protected function describe(): ?string
    {
        $process = new Process(
            ['git', 'describe', '--tags', '--long', '--always', '--dirty'],
            base_path(),
        );

        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $describe = AppVersion::normalize($process->getOutput());

        return filled($describe) ? $describe : null;
    }
}
