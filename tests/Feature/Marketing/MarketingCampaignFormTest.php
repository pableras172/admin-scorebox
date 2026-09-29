<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Filament\Resources\MarketingCampaigns\Pages\CreateMarketingCampaign;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class MarketingCampaignFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_campaign_with_promotional_code_type_and_exclusion_setting(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Código de regalo para {{name}}',
            'content' => '<p>Usa este código: {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_FREE,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
        ]);

        $this->assertTrue($campaign->isPromotionalCode());
        $this->assertTrue($campaign->exclude_previous_promo_recipients);
        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
        ]);
    }

    public function test_it_safely_persists_campaign_when_content_is_provided_as_tiptap_array(): void
    {
        $tiptapContent = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Gracias por instalar ScoreBox',
                        ],
                    ],
                ],
            ],
        ];

        $campaign = MarketingCampaign::create([
            'subject' => 'Gracias por instalar ScoreBox',
            'content' => $tiptapContent,
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_STANDARD,
            'is_test' => true,
        ]);

        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'subject' => 'Gracias por instalar ScoreBox',
        ]);
        $this->assertIsString($campaign->content);
        $this->assertStringContainsString('Gracias por instalar ScoreBox', $campaign->content);
        $this->assertStringContainsString('<p>', $campaign->content);
    }

    public function test_it_handles_array_content_without_error_in_promo_code_validation(): void
    {
        // TipTap rich editor structure as array
        $tiptapContent = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Canjea tu código {{promotioncode}} aquí.',
                        ],
                    ],
                ],
            ],
        ];

        $failed = false;
        $failCallback = function (string $message) use (&$failed) {
            $failed = true;
        };

        // Replicate rule closure with array value
        $subject = 'Hola músico';
        $content = is_array($tiptapContent)
            ? (json_encode($tiptapContent, JSON_UNESCAPED_UNICODE) ?: '')
            : (string) ($tiptapContent ?? '');

        if (! str_contains($subject, '{{promotioncode}}') && ! str_contains($content, '{{promotioncode}}')) {
            $failCallback('Falta comodín');
        }

        $this->assertFalse($failed);
    }

    public function test_it_fails_when_array_content_lacks_promotioncode_placeholder(): void
    {
        $tiptapContentWithoutPromo = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Hola músico, bienvenido.',
                        ],
                    ],
                ],
            ],
        ];

        $failed = false;
        $failCallback = function (string $message) use (&$failed) {
            $failed = true;
        };

        $subject = 'Hola músico';
        $content = is_array($tiptapContentWithoutPromo)
            ? (json_encode($tiptapContentWithoutPromo, JSON_UNESCAPED_UNICODE) ?: '')
            : (string) ($tiptapContentWithoutPromo ?? '');

        if (! str_contains($subject, '{{promotioncode}}') && ! str_contains($content, '{{promotioncode}}')) {
            $failCallback('Falta comodín');
        }

        $this->assertTrue($failed);
    }

    public function test_it_validates_raw_html_content_with_promotioncode(): void
    {
        $htmlContent = '<div class="promo-box"><h2>Hola {{name}}</h2><p>Código: {{promotioncode}}</p><a href="https://play.google.com/redeem?code={{promotioncode}}">Canjear</a></div>';

        $failed = false;
        $failCallback = function (string $message) use (&$failed) {
            $failed = true;
        };

        $subject = 'Tu regalo ScoreBox';
        $content = is_array($htmlContent)
            ? (json_encode($htmlContent, JSON_UNESCAPED_UNICODE) ?: '')
            : (string) ($htmlContent ?? '');

        if (! str_contains($subject, '{{promotioncode}}') && ! str_contains($content, '{{promotioncode}}')) {
            $failCallback('Falta comodín');
        }

        $this->assertFalse($failed);
    }

    public function test_marketing_campaign_form_schema_configures_editor_mode_and_textarea(): void
    {
        Livewire::test(CreateMarketingCampaign::class)
            ->assertFormFieldExists('editor_mode')
            ->assertFormFieldExists('content')
            ->fillForm([
                'subject' => 'Oferta {{promotioncode}}',
                'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
                'editor_mode' => 'code',
                'content' => '<p>Tu código es {{promotioncode}}</p>',
            ])
            ->assertHasNoFormErrors();
    }

    public function test_get_html_content_returns_html_from_both_raw_html_and_tiptap_json(): void
    {
        // 1. Raw HTML campaign
        $rawHtmlCampaign = new MarketingCampaign([
            'content' => '<p>Hola músico</p>',
        ]);
        $this->assertSame('<p>Hola músico</p>', $rawHtmlCampaign->getHtmlContent());

        // 2. TipTap JSON campaign
        $jsonCampaign = new MarketingCampaign([
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hola músico desde TipTap'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertStringContainsString('Hola músico desde TipTap', $jsonCampaign->getHtmlContent());
        $this->assertStringStartsWith('<p>', $jsonCampaign->getHtmlContent());

        // 3. TipTap JSON with image node
        $jsonWithImage = new MarketingCampaign([
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => '/storage/marketing-campaigns/test.gif',
                            'alt' => 'Test GIF',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertStringContainsString('test.gif', $jsonWithImage->getHtmlContent());
    }

    public function test_process_content_for_email_preserves_external_urls(): void
    {
        $input = '<p>Mira este GIF: <img src="https://media.giphy.com/media/sample/giphy.gif" alt="Animated"></p>';
        $output = MarketingCampaign::processContentForEmail($input);

        $this->assertStringContainsString('src="https://media.giphy.com/media/sample/giphy.gif"', $output);
    }

    public function test_process_content_for_email_embeds_local_images_with_message(): void
    {
        $input = '<p>Logo: <img src="/images/headerMail.png" alt="Header"></p>';

        $fakeMessage = new class
        {
            public function embed(string $file): string
            {
                return 'cid:embedded-header-id';
            }
        };

        $output = MarketingCampaign::processContentForEmail($input, $fakeMessage);

        $this->assertStringContainsString('src="cid:embedded-header-id"', $output);
    }

    public function test_process_content_for_email_normalizes_relative_url_without_message(): void
    {
        $input = '<p>Foto: <img src="/images/headerMail.png" alt="Header"></p>';
        $output = MarketingCampaign::processContentForEmail($input, null);

        $this->assertStringContainsString('http', $output);
        $this->assertStringContainsString('headerMail.png', $output);
    }
}
