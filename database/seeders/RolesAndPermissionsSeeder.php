<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar cache de permisos (opcional)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        Permission::firstOrCreate(['name' => 'editar usuarios']);
        Permission::firstOrCreate(['name' => 'eliminar usuarios']);

        // Crear roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $guest = Role::firstOrCreate(['name' => 'invitado']);

        // Asignar permisos a roles
        $admin->givePermissionTo(['editar usuarios', 'eliminar usuarios']);
        $guest->givePermissionTo(['editar usuarios']);

        // Crear usuario admin si no existe
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
            ]
        );

        if ($user) {
            $user->assignRole($admin);
            $this->command->info('Usuario admin creado o encontrado y rol asignado.');
        } else {
            $this->command->error('No se pudo crear o encontrar el usuario admin.');
        }
    }
}
