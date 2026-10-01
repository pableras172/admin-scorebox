<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_user_password_via_arguments(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@scorebox.pro',
            'password' => 'oldpassword123',
        ]);

        $this->artisan('user:password', [
            'email' => 'admin@scorebox.pro',
            'password' => 'newsecretpass456',
        ])
            ->expectsOutputToContain('¡Contraseña de [admin@scorebox.pro] actualizada correctamente!')
            ->assertExitCode(0);

        $user->refresh();

        $this->assertTrue(Hash::check('newsecretpass456', $user->password));
    }

    public function test_it_fails_when_user_does_not_exist(): void
    {
        $this->artisan('user:password', [
            'email' => 'nonexistent@scorebox.pro',
            'password' => 'secret123',
        ])
            ->expectsOutputToContain('Usuario con email [nonexistent@scorebox.pro] no encontrado.')
            ->assertExitCode(1);
    }
}
