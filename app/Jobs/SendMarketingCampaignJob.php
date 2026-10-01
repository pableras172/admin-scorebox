<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\MarketingCampaignMailable;
use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use App\Services\Marketing\CampaignAudienceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SendMarketingCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public int $timeout = 600;

    public function __construct(
        public int $campaignId,
    ) {}

    public function handle(CampaignAudienceResolver $resolver): void
    {
        $campaign = MarketingCampaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning('SendMarketingCampaignJob aborted: campaign not found.', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        // Prevent duplicate execution if already sending or sent
        if (! in_array($campaign->status, [
            MarketingCampaign::STATUS_DRAFT,
            MarketingCampaign::STATUS_SCHEDULED,
            MarketingCampaign::STATUS_QUEUED,
            MarketingCampaign::STATUS_FAILED,
            MarketingCampaign::STATUS_TEST_SENT,
        ], true)) {
            Log::warning('SendMarketingCampaignJob aborted: invalid campaign status.', [
                'campaign_id' => $this->campaignId,
                'status' => $campaign->status,
            ]);

            return;
        }

        $campaign->update([
            'status' => MarketingCampaign::STATUS_SENDING,
        ]);

        try {
            $recipients = $campaign->target_instrument !== null
                ? $resolver->resolve($campaign->target_segment, (bool) $campaign->is_test, $campaign->target_instrument)
                : $resolver->resolve($campaign->target_segment, (bool) $campaign->is_test);

            // Filter recipients and validate stock for promotional code campaigns in production
            if ($campaign->isPromotionalCode() && ! $campaign->is_test) {
                $previousAssignedCodes = PromotionalCode::query()
                    ->whereNotNull('assigned_email')
                    ->get(['id', 'code', 'assigned_email'])
                    ->keyBy(fn (PromotionalCode $c): string => strtolower(trim((string) $c->assigned_email)));

                if ($campaign->exclude_previous_promo_recipients) {
                    $recipients = $recipients->reject(
                        fn ($recipient): bool => $previousAssignedCodes->has(strtolower($recipient->email))
                    )->values();
                } else {
                    $recipients = $recipients->reject(function ($recipient) use ($previousAssignedCodes): bool {
                        $hasCode = $previousAssignedCodes->has(strtolower($recipient->email));

                        return $hasCode && $recipient->isPremium;
                    })->values();
                }

                $neededFreshCodes = $recipients->filter(
                    fn ($recipient): bool => ! $previousAssignedCodes->has(strtolower($recipient->email))
                )->count();

                $availableCodesCount = PromotionalCode::available()->count();

                if ($availableCodesCount < $neededFreshCodes) {
                    Log::warning('SendMarketingCampaignJob aborted: insufficient promotional codes available.', [
                        'campaign_id' => $campaign->id,
                        'needed' => $neededFreshCodes,
                        'available' => $availableCodesCount,
                    ]);

                    $campaign->update([
                        'status' => MarketingCampaign::STATUS_FAILED,
                    ]);

                    return;
                }
            }

            $campaign->update([
                'recipients_count' => $recipients->count(),
            ]);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $unsubscribeUrl = URL::signedRoute('marketing.unsubscribe', [
                        'email' => $recipient->email,
                    ]);

                    $search = ['{{name}}'];
                    $subjectReplace = [$recipient->name];
                    $contentReplace = [e($recipient->name)];

                    if ($campaign->isPromotionalCode()) {
                        if ($campaign->is_test) {
                            $promoCode = 'PROMO-TEST-'.strtoupper(Str::random(8));
                        } else {
                            $existingPromoCode = PromotionalCode::where('assigned_email', $recipient->email)->first();

                            if ($existingPromoCode) {
                                $promoCode = $existingPromoCode->code;
                            } else {
                                $assignedRecord = DB::transaction(function () use ($recipient, $campaign) {
                                    $code = PromotionalCode::available()
                                        ->lockForUpdate()
                                        ->first();

                                    if (! $code) {
                                        return null;
                                    }

                                    $code->update([
                                        'assigned_email' => $recipient->email,
                                        'assigned_uid' => $recipient->uid,
                                        'marketing_campaign_id' => $campaign->id,
                                        'assigned_at' => now(),
                                    ]);

                                    return $code;
                                });

                                if (! $assignedRecord) {
                                    Log::warning('SendMarketingCampaignJob ran out of promo codes during execution.', [
                                        'campaign_id' => $campaign->id,
                                        'recipient' => $recipient->email,
                                    ]);
                                    $failedCount++;

                                    continue;
                                }

                                $promoCode = $assignedRecord->code;
                            }
                        }

                        $search[] = '{{promotioncode}}';
                        $subjectReplace[] = $promoCode;
                        $contentReplace[] = $promoCode;
                    }

                    $personalizedSubject = str_replace($search, $subjectReplace, $campaign->subject);
                    $personalizedContent = str_replace($search, $contentReplace, $campaign->getHtmlContent());

                    Mail::to($recipient->email)->send(
                        new MarketingCampaignMailable(
                            campaignSubject: $personalizedSubject,
                            campaignContent: $personalizedContent,
                            unsubscribeUrl: $unsubscribeUrl,
                        )
                    );

                    $sentCount++;
                } catch (Throwable $recipientException) {
                    $failedCount++;
                    Log::warning('Failed sending marketing campaign email to recipient.', [
                        'campaign_id' => $campaign->id,
                        'recipient' => $recipient->email,
                        'error' => $recipientException->getMessage(),
                    ]);
                }
            }

            $finalStatus = $campaign->is_test ? MarketingCampaign::STATUS_TEST_SENT : MarketingCampaign::STATUS_SENT;

            $campaign->update([
                'status' => $finalStatus,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'sent_at' => now(),
            ]);

            Log::info('Marketing campaign dispatch completed successfully.', [
                'campaign_id' => $campaign->id,
                'recipients_count' => $recipients->count(),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);
        } catch (Throwable $e) {
            $campaign->update([
                'status' => MarketingCampaign::STATUS_FAILED,
            ]);

            Log::error('Marketing campaign dispatch failed critically.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
