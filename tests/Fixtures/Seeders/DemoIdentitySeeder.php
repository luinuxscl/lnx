<?php

namespace Tests\Fixtures\Seeders;

use App\Actions\ApplyIdentityDefaults;
use Illuminate\Database\Seeder;

/**
 * A stand-in for a customization package's identity seeder.
 */
class DemoIdentitySeeder extends Seeder
{
    /**
     * The identity values the fixture package contributes.
     *
     * @var array<string, string|null>
     */
    public static array $defaults = [
        'site_title' => 'Luz',
        'site_tagline' => 'Historial médico familiar',
        'footer_text' => 'Hecho por Luz',
        'locale' => 'en',
        'timezone' => 'Europe/Madrid',
    ];

    public function run(): void
    {
        app(ApplyIdentityDefaults::class)->handle(static::$defaults);
    }
}
