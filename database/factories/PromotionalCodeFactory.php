<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PromotionalCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PromotionalCode>
 */
class PromotionalCodeFactory extends Factory
{
    protected $model = PromotionalCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(23)),
            'assigned_email' => null,
            'assigned_uid' => null,
            'marketing_campaign_id' => null,
            'assigned_at' => null,
        ];
    }

    /**
     * Indicate that the promotional code is assigned to a user.
     */
    public function assigned(?string $email = null, ?string $uid = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_email' => $email ?? fake()->unique()->safeEmail(),
            'assigned_uid' => $uid ?? Str::uuid()->toString(),
            'assigned_at' => now(),
        ]);
    }
}

