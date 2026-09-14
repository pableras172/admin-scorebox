<?php

declare(strict_types=1);

namespace App\Services\Marketing;

final readonly class CampaignRecipient
{
    public function __construct(
        public string $email,
        public string $name,
        public bool $isPremium,
        public ?string $uid = null,
    ) {}
}
