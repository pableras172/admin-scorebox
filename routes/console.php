<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:password {email? : El email del usuario} {password? : La nueva contraseña}', function () {
    $email = (string) ($this->argument('email') ?? '');
    if ($email === '' && $this->input->isInteractive()) {
        $email = (string) $this->ask('Introduce el email del usuario');
    }

    $password = (string) ($this->argument('password') ?? '');
    if ($password === '' && $this->input->isInteractive()) {
        $password = (string) $this->secret('Introduce la nueva contraseña');
    }

    if (blank($email)) {
        $this->error('El email es obligatorio.');

        return 1;
    }

    if (blank($password)) {
        $this->error('La contraseña no puede estar vacía.');

        return 1;
    }

    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Usuario con email [{$email}] no encontrado.");

        return 1;
    }

    $user->password = $password;
    $user->save();

    $this->info("¡Contraseña de [{$email}] actualizada correctamente!");

    return 0;
})->purpose('Actualiza la contraseña de un usuario');
