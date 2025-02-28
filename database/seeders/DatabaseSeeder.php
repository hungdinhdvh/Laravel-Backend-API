<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

       User::factory()->create([
            'name' => 'Đinh Văn Hùng',
            'email' => 'manhhung.dvh@gmail.com',
            'password' => Hash::make('password'),
            'address' => '11 Street, Tam Binh Ward, Thu Duc City, Ho Chi Minh City, Vietnam'
        ]);
    }
}
