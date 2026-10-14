<?php

namespace Database\Seeders;

use App\Actions\ApplyIdentityDefaults;
use Illuminate\Database\Seeder;

/**
 * The kit's base identity seeder.
 *
 * It is registered with the lowest priority, so a customization package's
 * seeder runs first and wins at equal fields. Because its values mirror the
 * kit fallbacks already provided by config and the Setting accessors, it
 * persists nothing on its own: without a package the site keeps its current
 * identity and no settings row is created.
 */
class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        app(ApplyIdentityDefaults::class)->handle([
            'site_title' => (string) config('app.name'),
            'site_tagline' => (string) config('app.tagline'),
            'footer_text' => (string) config('app.footer_text'),
            'locale' => (string) config('app.locale'),
            'timezone' => (string) config('app.timezone'),
        ]);
    }
}
