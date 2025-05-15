<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;


class UserTest  extends TestCase
{
    use RefreshDatabase;

    /** @test registro */
    public function test_create_user_successfully()
    {
        // Crea un usuario autenticado para hacer la solicitud
        $authUser = \App\Models\User::factory()->create();

        // Simula autenticación con Sanctum
        $this->actingAs($authUser, 'sanctum');

        $payload = [
            'name' => 'Juan Pérez',
            'email' => 'juanperez@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Usuario creado exitosamente',
                'user' => [
                    'name' => 'Juan Pérez',
                    'email' => 'juanperez@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juanperez@example.com',
        ]);
    }

    /** @test actualizar */
    public function test_update_user_successfully()
    {
        Role::firstOrCreate(['name' => 'admin']);
        // Crear usuario autenticado
        $authUser = \App\Models\User::factory()->create();
        $authUser->assignRole('admin'); // O el rol que tu sistema requiere


        // Autenticar como $authUser
        $this->actingAs($authUser, 'sanctum');

        $payload = [
            'name' => 'Nombre Actualizado',
            'email' => 'nuevoemail@example.com',
            'password' => 'newpassword123',
        ];

        // $authUser actualiza SU propio registro
        $response = $this->putJson("/api/users/{$authUser->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuario actualizado exitosamente',
                'user' => [
                    'name' => 'Nombre Actualizado',
                    'email' => 'nuevoemail@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $authUser->id,
            'name' => 'Nombre Actualizado',
            'email' => 'nuevoemail@example.com',
        ]);
    }

    /** @test eliminar */
    public function test_delete_user_with_admin_role()
    {
        // Crear el rol admin si no existe
        Role::firstOrCreate(['name' => 'admin']);
        // Crear usuario con rol admin para autenticación
        $authUser = \App\Models\User::factory()->create();
        $authUser->assignRole('admin'); // O asigna el rol según tu implementación

        $this->actingAs($authUser, 'sanctum');

        // Usuario que será eliminado
        $userToDelete = \App\Models\User::factory()->create();

        $response = $this->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuario eliminado exitosamente',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }
}
