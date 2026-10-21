<?php

namespace Database\Seeders;

use App\Actions\SeedIdentity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(NavigationSeeder::class);
        $this->call(HelpSeeder::class);
        $this->call(KnowledgeSeeder::class);

        app(SeedIdentity::class)->handle($this->command);

        if (! app()->isProduction()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
