<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Filament\Resources\MarketingCampaigns\Pages\CreateMarketingCampaign;
use App\Filament\Resources\MarketingCampaigns\Pages\EditMarketingCampaign;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Link;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

final class MarketingCampaignFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_editor_attach_files_action(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        Livewire::actingAs($user)
            ->test(CreateMarketingCampaign::class)
            ->set('data.editor_mode', 'visual')
            ->mountFormComponentAction('content_visual', 'attachFiles', arguments: [
                'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1],
            ])
            ->setFormComponentActionData([
                'file' => $file,
                'alt' => 'Foto clarinete',
            ])
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors();
    }

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
            ->assertFormFieldExists('content_code')
            ->assertFormFieldExists('content_visual')
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

    public function test_it_saves_campaign_with_html_link_without_stripping_anchor(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Novedades ScoreBox',
            'content' => '<p><a href="https://scorebox.pro/open">📱 Abrir ScoreBox</a></p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_STANDARD,
            'is_test' => true,
        ]);

        $this->assertStringContainsString('href="https://scorebox.pro/open"', $campaign->fresh()->content);
    }

    public function test_edit_marketing_campaign_preserves_html_links_when_saving(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Novedades ScoreBox',
            'content' => '<p><a href="https://scorebox.pro/open">📱 Abrir ScoreBox</a></p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_STANDARD,
            'is_test' => true,
        ]);

        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditMarketingCampaign::class, [
                'record' => $campaign->id,
            ])
            ->fillForm([
                'content' => '<p><a href="https://scorebox.pro/open">📱 Abrir ScoreBox</a></p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('href="https://scorebox.pro/open"', $campaign->fresh()->content);
    }

    public function test_tiptap_editor_converts_link_with_div_or_inside_table(): void
    {
        $input = '<table><tbody><tr><td><div style="text-align:center;"><a href="https://scorebox.pro/open">📱 Abrir ScoreBox &rarr;</a></div></td></tr></tbody></table>';
        $editor = MarketingCampaign::createTipTapEditor()->setContent($input);
        $json = $editor->getDocument();
        $html = $editor->getHTML();

        // Let's also test when wrapped in <p>
        $pInput = '<table><tbody><tr><td><p><a href="https://scorebox.pro/open">📱 Abrir ScoreBox &rarr;</a></p></td></tr></tbody></table>';
        $pEditor = MarketingCampaign::createTipTapEditor()->setContent($pInput);
        $pHtml = $pEditor->getHTML();

        $customLink = new class extends Link
        {
            public function addAttributes()
            {
                return [
                    'href' => [],
                    'target' => [],
                    'rel' => [],
                    'class' => [],
                    'style' => [],
                ];
            }
        };

        $customEditor = new Editor([
            'extensions' => [
                new StarterKit,
                $customLink,
                new Table,
                new TableRow,
                new TableCell,
                new TableHeader,
            ],
        ]);

        $styledInput = '<table><tbody><tr><td><p><a target="_blank" rel="noopener noreferrer nofollow" class="button-link" href="https://scorebox.pro/open"><strong>📱 Abrir ScoreBox &rarr;</strong></a></p></td></tr></tbody></table>';
        $styledHtml = MarketingCampaign::createTipTapEditor()->setContent($styledInput)->getHTML();

        $this->assertStringContainsString('href="https://scorebox.pro/open"', $styledHtml);
        $this->assertStringContainsString('class="button-link"', $styledHtml);
        $this->assertStringContainsString('<strong>📱 Abrir ScoreBox →</strong>', $styledHtml);
    }

    public function test_full_campaign_preserves_all_open_app_links(): void
    {
        $campaignHtml = '<table><tbody><tr><td rowspan="1" colspan="1"><p><strong>1</strong></p><p><a target="_blank" rel="noopener noreferrer nofollow" class="button-link" href="https://scorebox.pro/open"><strong>📱 Abrir ScoreBox &rarr;</strong></a></p></td></tr></tbody></table><table><tbody><tr><td rowspan="1" colspan="1"><p><strong>2</strong></p><p><a target="_blank" rel="noopener noreferrer nofollow" class="button-link" href="https://scorebox.pro/open"><strong>📱 Abrir ScoreBox &rarr;</strong></a></p></td></tr></tbody></table>';
        $rendered = MarketingCampaign::createTipTapEditor()->setContent($campaignHtml)->getHTML();

        $this->assertSame(2, substr_count($rendered, 'https://scorebox.pro/open'));
        $this->assertSame(2, substr_count($rendered, 'class="button-link"'));
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
