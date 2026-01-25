<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Evita duplicar el admin si corres el seeder más de una vez
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'     => 'Administrador',
                'username' => 'admin',
                'password' => Hash::make('123456'),
                'role'     => 'admin',
            ]
        );
    }
}
