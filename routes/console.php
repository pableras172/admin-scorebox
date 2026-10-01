<?php

use App\Jobs\SendMarketingCampaignJob;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

Artisan::command('marketing:send-scheduled', function () {
    $now = now();
    $campaigns = MarketingCampaign::where('status', MarketingCampaign::STATUS_SCHEDULED)
        ->where('scheduled_at', '<=', $now)
        ->get();

    if ($campaigns->isEmpty()) {
        $this->info('No hay campañas programadas pendientes de envío.');

        return 0;
    }

    foreach ($campaigns as $campaign) {
        $this->info("Despachando campaña programada ID [{$campaign->id}] - Asunto: {$campaign->subject}");
        $campaign->update(['status' => MarketingCampaign::STATUS_QUEUED]);
        SendMarketingCampaignJob::dispatch($campaign->id);
    }

    $this->info("Se han despachado {$campaigns->count()} campañas programadas.");

    return 0;
})->purpose('Despacha las campañas de marketing programadas');

Schedule::command('marketing:send-scheduled')->everyMinute();
