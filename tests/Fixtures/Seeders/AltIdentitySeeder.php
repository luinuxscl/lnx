<?php

namespace Tests\Fixtures\Seeders;

use App\Actions\ApplyIdentityDefaults;
use Illuminate\Database\Seeder;

/**
 * A second stand-in identity seeder used to check priority precedence.
 */
class AltIdentitySeeder extends Seeder
{
    /**
     * @var array<string, string|null>
     */
    public static array $defaults = [
        'site_title' => 'Acme',
    ];

    public function run(): void
    {
        app(ApplyIdentityDefaults::class)->handle(static::$defaults);
    }
}
