<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_page_is_accessible(): void
    {
        $response = $this->get('/admin/password-reset/request');

        $response->assertSuccessful();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');

        $response->assertSuccessful();
    }

    public function test_authenticated_user_can_access_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/profile');

        $response->assertSuccessful();
    }
}
