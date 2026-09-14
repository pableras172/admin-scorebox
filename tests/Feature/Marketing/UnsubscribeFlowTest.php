<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Models\EmailUnsubscribe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class UnsubscribeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_unsubscribe_without_signature_is_forbidden(): void
    {
        $response = $this->get('/marketing/unsubscribe?email=test@example.com');
        $response->assertForbidden();
    }

    public function test_get_unsubscribe_with_valid_signature_unsubscribes_and_renders_view(): void
    {
        $email = 'pianist@example.com';
        $signedUrl = URL::signedRoute('marketing.unsubscribe', ['email' => $email]);

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee('Te has dado de baja correctamente');
        $response->assertSee($email);
        $response->assertSee('Volver a suscribirme');

        $this->assertDatabaseHas('email_unsubscribes', [
            'email' => $email,
            'source' => EmailUnsubscribe::SOURCE_LINK,
        ]);
        $this->assertTrue(EmailUnsubscribe::isUnsubscribed($email));

        // Test idempotency: second visit does not fail
        $secondResponse = $this->get($signedUrl);
        $secondResponse->assertOk();
        $this->assertSame(1, EmailUnsubscribe::where('email', $email)->count());
    }

    public function test_rfc8058_post_unsubscribe_with_valid_signature_records_header_source(): void
    {
        $email = 'violinist@example.com';
        $signedUrl = URL::signedRoute('marketing.unsubscribe.post', ['email' => $email]);

        $response = $this->post($signedUrl, [
            'List-Unsubscribe' => 'One-Click',
        ]);

        $response->assertOk();
        $response->assertSeeText('Unsubscribed successfully');

        $this->assertDatabaseHas('email_unsubscribes', [
            'email' => $email,
            'source' => EmailUnsubscribe::SOURCE_HEADER,
            'reason' => 'rfc8058_one_click',
        ]);
    }

    public function test_resubscribe_flow_removes_email_from_suppression_list(): void
    {
        $email = 'cellist@example.com';
        EmailUnsubscribe::unsubscribe($email, EmailUnsubscribe::SOURCE_LINK);
        $this->assertTrue(EmailUnsubscribe::isUnsubscribed($email));

        $signedResubscribeUrl = URL::signedRoute('marketing.resubscribe', ['email' => $email]);

        $response = $this->post($signedResubscribeUrl, ['email' => $email]);

        $response->assertOk();
        $response->assertSee('¡Suscripción reactivada!');
        $response->assertSee($email);

        $this->assertDatabaseMissing('email_unsubscribes', [
            'email' => $email,
        ]);
        $this->assertFalse(EmailUnsubscribe::isUnsubscribed($email));
    }
}

