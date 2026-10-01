<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailUnsubscribe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailUnsubscribe>
 */
class EmailUnsubscribeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'reason' => 'user_click',
            'source' => EmailUnsubscribe::SOURCE_LINK,
            'unsubscribed_at' => now(),
        ];
    }

    /**
     * Indicate that the unsubscribe was manual from admin panel.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => EmailUnsubscribe::SOURCE_MANUAL,
            'reason' => 'admin_request',
        ]);
    }
}
