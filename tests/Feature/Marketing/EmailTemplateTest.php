<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Models\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_retrieve_email_template(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Bienvenida Músicos',
            'description' => 'Plantilla para nuevos registros en ScoreBox',
            'subject' => '¡Te damos la bienvenida a ScoreBox, {{name}}!',
            'content' => '<p>Hola {{name}}, explora tus partituras preferidas.</p>',
            'editor_mode' => 'code',
        ]);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Bienvenida Músicos',
            'editor_mode' => 'code',
        ]);

        $this->assertEquals('Bienvenida Músicos', $template->name);
        $this->assertStringContainsString('{{name}}', $template->subject);
    }

    public function test_email_template_defaults_to_visual_editor_mode(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Plantilla Básica',
            'subject' => 'Aviso general',
            'content' => '<p>Contenido</p>',
        ]);

        $this->assertEquals('visual', $template->editor_mode);
    }
}
