<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingCampaign>
 */
class MarketingCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
            'recipients_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'sent_at' => null,
        ];
    }

    /**
     * Indicate that the campaign is a test.
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => true,
        ]);
    }

    /**
     * Indicate that the campaign is sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MarketingCampaign::STATUS_SENT,
            'recipients_count' => 10,
            'sent_count' => 10,
            'failed_count' => 0,
            'sent_at' => now(),
        ]);
    }
}
