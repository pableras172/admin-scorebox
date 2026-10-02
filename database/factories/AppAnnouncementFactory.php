<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppAnnouncement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppAnnouncement>
 */
final class AppAnnouncementFactory extends Factory
{
    protected $model = AppAnnouncement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(1),
            'type' => fake()->randomElement([
                AppAnnouncement::TYPE_INFO,
                AppAnnouncement::TYPE_WARNING,
                AppAnnouncement::TYPE_SUCCESS,
                AppAnnouncement::TYPE_PROMO,
                AppAnnouncement::TYPE_AD,
            ]),
            'image_url' => fake()->imageUrl(),
            'action_text' => 'Ver más',
            'action_url' => fake()->url(),
            'hide_for_pro' => false,
            'is_active' => false,
            'synced_to_firestore_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'synced_to_firestore_at' => now(),
        ]);
    }

    public function ad(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AppAnnouncement::TYPE_AD,
            'hide_for_pro' => true,
        ]);
    }
}
