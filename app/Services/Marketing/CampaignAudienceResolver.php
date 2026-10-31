<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\EmailUnsubscribe;
use App\Models\MarketingCampaign;
use App\Services\Firestore\FirestoreUserGateway;
use App\Services\Firestore\ScoreBoxUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CampaignAudienceResolver
{
    public function __construct(
        private readonly FirestoreUserGateway $userGateway,
    ) {}

    /**
     * Resolve valid recipients for a given marketing campaign segment, excluding unsubscribed users.
     * Supports segment, instrument, platform (Android/iOS), and trial/purchase status targeting.
     *
     * @return Collection<int, CampaignRecipient>
     */
    public function resolve(
        string $segment = MarketingCampaign::SEGMENT_ALL,
        bool $isTest = false,
        ?string $targetInstrument = null,
        string $targetPlatform = MarketingCampaign::PLATFORM_ALL,
        string $targetTrialStatus = MarketingCampaign::TRIAL_STATUS_ALL,
    ): Collection {
        if ($isTest) {
            $testEmails = (array) config('app.test_emails', [
                'pableras172@hotmail.com',
                'pableras172@gmail.com',
            ]);

            return collect($testEmails)
                ->filter(fn (mixed $email): bool => is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false)
                ->map(fn (string $email): CampaignRecipient => new CampaignRecipient(
                    email: strtolower(trim($email)),
                    name: 'Tester',
                    isPremium: false,
                ))
                ->values();
        }

        $result = $this->userGateway->all();

        if (! $result->isSuccess()) {
            Log::error('Failed to retrieve users from Firestore for campaign audience resolution.', [
                'segment' => $segment,
                'error' => $result->message(),
                'code' => $result->error(),
            ]);

            return collect();
        }

        $rawUsers = $result->data();
        if (! is_array($rawUsers) || empty($rawUsers)) {
            return collect();
        }

        // Retrieve suppression list of emails
        $suppressedEmails = EmailUnsubscribe::query()
            ->pluck('email')
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->flip()
            ->all();

        /** @var Collection<int, CampaignRecipient> $recipients */
        $recipients = collect();

        foreach ($rawUsers as $user) {
            $email = strtolower(trim((string) ($user['email'] ?? '')));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Exclude if unsubscribed
            if (isset($suppressedEmails[$email])) {
                continue;
            }

            $subscription = strtolower(trim((string) ($user['subscription'] ?? $user['subscrition'] ?? '')));
            $isPremium = (is_bool($user['isPremium'] ?? null) ? $user['isPremium'] : filter_var($user['isPremium'] ?? false, FILTER_VALIDATE_BOOLEAN))
                || in_array($subscription, ['pro', 'premium', 'activo', 'active'], true);

            // Double check segment filter in memory
            if ($segment === MarketingCampaign::SEGMENT_FREE && $isPremium) {
                continue;
            }
            if ($segment === MarketingCampaign::SEGMENT_PREMIUM && ! $isPremium) {
                continue;
            }

            // Target Instrument filter
            if (filled($targetInstrument)) {
                $userInstrument = strtolower(trim((string) ($user['mainInstrument'] ?? '')));
                if ($userInstrument !== strtolower(trim($targetInstrument))) {
                    continue;
                }
            }

            // Target Platform filter (android vs ios)
            if ($targetPlatform !== MarketingCampaign::PLATFORM_ALL) {
                $userPlatform = strtolower(trim((string) ($user['platform'] ?? 'android')));
                if ($userPlatform !== strtolower(trim($targetPlatform))) {
                    continue;
                }
            }

            // Target Trial / Purchase Status filter
            if ($targetTrialStatus !== MarketingCampaign::TRIAL_STATUS_ALL) {
                $userObj = ScoreBoxUser::fromArray($user);
                if ($userObj->getTrialStatus() !== $targetTrialStatus) {
                    continue;
                }
            }

            $displayName = trim((string) ($user['displayName'] ?? ''));
            $name = $displayName !== '' ? $displayName : 'músico';

            $uid = isset($user['uid']) && is_string($user['uid']) && $user['uid'] !== ''
                ? $user['uid']
                : (isset($user['id']) && is_string($user['id']) && $user['id'] !== '' ? $user['id'] : null);

            $recipients->push(new CampaignRecipient(
                email: $email,
                name: $name,
                isPremium: $isPremium,
                uid: $uid,
            ));
        }

        // Ensure recipients are unique by email
        return $recipients->unique('email')->values();
    }

    /**
     * Resolve audience directly from a MarketingCampaign model instance.
     *
     * @return Collection<int, CampaignRecipient>
     */
    public function resolveForCampaign(MarketingCampaign $campaign): Collection
    {
        return $this->resolve(
            segment: $campaign->target_segment ?? MarketingCampaign::SEGMENT_ALL,
            isTest: (bool) $campaign->is_test,
            targetInstrument: $campaign->target_instrument,
            targetPlatform: $campaign->target_platform ?? MarketingCampaign::PLATFORM_ALL,
            targetTrialStatus: $campaign->target_trial_status ?? MarketingCampaign::TRIAL_STATUS_ALL,
        );
    }

    /**
     * Count recipients for a given segment or test mode.
     */
    public function count(
        string $segment = MarketingCampaign::SEGMENT_ALL,
        bool $isTest = false,
        ?string $targetInstrument = null,
        string $targetPlatform = MarketingCampaign::PLATFORM_ALL,
        string $targetTrialStatus = MarketingCampaign::TRIAL_STATUS_ALL,
    ): int {
        return $this->resolve($segment, $isTest, $targetInstrument, $targetPlatform, $targetTrialStatus)->count();
    }
}
