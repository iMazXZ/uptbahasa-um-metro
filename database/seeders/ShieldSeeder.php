<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Membuat role 'super_admin' jika belum ada
        // Role ini secara default oleh Filament Shield akan memiliki semua hak akses
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        // Membuat user super admin jika belum ada
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // SANGAT PENTING: Ganti 'password' dengan password yang aman
            ]
        );

        // Memberikan role 'super_admin' ke user tersebut
        $user->assignRole($role);
    }
}