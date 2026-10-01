<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Mail\UserSupportMailable;
use App\Models\PromotionalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_support_mailable_renders_content_and_promo_code(): void
    {
        $mailable = new UserSupportMailable(
            supportSubject: 'Soporte ScoreBox',
            supportMessage: 'Gracias por ponerte en contacto.',
            userName: 'Carlos',
            promoCode: 'PLAY-STORE-CODE-1234',
        );

        $mailable->assertHasSubject('Soporte ScoreBox');

        $rendered = $mailable->render();
        $this->assertStringContainsString('Carlos', $rendered);
        $this->assertStringContainsString('Gracias por ponerte en contacto.', $rendered);
        $this->assertStringContainsString('PLAY-STORE-CODE-1234', $rendered);
        $this->assertStringContainsString('Canjear', $rendered);
    }

    public function test_can_assign_available_promo_code_to_user_email(): void
    {
        $promo = PromotionalCode::create([
            'code' => 'PROMO-TEST-ABC',
        ]);

        $this->assertNull($promo->assigned_email);
        $this->assertNull($promo->assigned_at);

        $userEmail = 'musician@example.com';

        $available = PromotionalCode::available()->first();
        $this->assertNotNull($available);

        $available->update([
            'assigned_email' => strtolower($userEmail),
            'assigned_at' => now(),
        ]);

        $this->assertDatabaseHas('promotional_codes', [
            'id' => $promo->id,
            'assigned_email' => 'musician@example.com',
        ]);
        $this->assertTrue($promo->fresh()->isAssigned());
        $this->assertNotNull($promo->fresh()->assigned_at);
    }
}
