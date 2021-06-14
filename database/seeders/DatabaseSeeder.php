<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        for ($i=3; $i <= 110; $i++) { 
            \App\Models\User::factory(1)->create([
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@user.com'
            ]);
        }
    }
}
