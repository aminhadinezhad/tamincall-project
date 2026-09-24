<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds nothing on purpose. Laravel's stock seeder created a "Test User" with the password
 * "password", which would be a way into the live panel if db:seed were ever run on the server.
 *
 * The first manager is created with: php artisan tamin:manager
 * Local demo data (local only):       php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
