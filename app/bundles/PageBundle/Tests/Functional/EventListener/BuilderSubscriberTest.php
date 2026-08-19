<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\EventListener;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList as Segment;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class BuilderSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    // Custom preference center page
    public const string CUSTOM_SEGMENT_SELECTOR           = '.pref-segmentlist input';

    public const string CUSTOM_CATEGORY_SELECTOR          = '.pref-categorylist input';

    public const string CUSTOM_PREFERRED_CHANNEL_SELECTOR = '.pref-preferredchannel select';

    public const string CUSTOM_CHANNEL_FREQ_SELECTOR      = '.pref-channelfrequency div[data-contact-frequency="1"]';

    public const string CUSTOM_SAVE_BUTTON_SELECTOR       = '.prefs-saveprefs button.btn-save';

    // Default preference center page
    public const string DEFAULT_SEGMENT_SELECTOR           = '#contact-segments';

    public const string DEFAULT_CATEGORY_SELECTOR          = '#global-categories';

    public const string DEFAULT_PREFERRED_CHANNEL_SELECTOR = '#preferred_channel';

    public const string DEFAULT_CHANNEL_FREQ_SELECTOR      = '[data-contact-frequency="1"]';

    public const string DEFAULT_PAUSE_DATES_SELECTOR       = '[data-contact-pause-dates="1"]';

    public const string DEFAULT_SAVE_BUTTON_SELECTOR       = '#lead_contact_frequency_rules_buttons_save';

    // Common to both custom and default
    public const string TOKEN_SELECTOR = '#lead_contact_frequency_rules__token';

    public const string FORM_SELECTOR  = 'form[name="lead_contact_frequency_rules"]';

    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences'] = 1;
        $data                                           = $this->providedData();
        $this->configParams                             = array_merge($data[0], $this->configParams);

        parent::setUp();
    }

    /**
     * Tests both the default and custom preference center pages.
     *
     * @param mixed[]           $configParams
     * @param array<string,int> $selectorsAndExpectedCounts
     */
    #[DataProvider('frequencyFormRenderingDataProvider')]
    public function testUnsubscribeFormRendersPreferenceCenterPageCorrectly(array $configParams, array $selectorsAndExpectedCounts, bool $hasPreferenceCenter): void
    {
        $emailStat = $this->createStat(
            $this->createEmail($hasPreferenceCenter),
            $lead = $this->createLead()
        );

        $this->createSegment();
        $this->createCategory();

        $this->em->flush();

        $mailHashHelper = self::getContainer()->get(MailHashHelper::class);
        $this->assertInstanceOf(MailHashHelper::class, $mailHashHelper);

        $unsubscribeUrl = $this->router->generate('mautic_email_unsubscribe', [
            'idHash'     => $emailStat->getTrackingHash(),
            'urlEmail'   => $lead->getEmail(),
            'secretHash' => $mailHashHelper->getEmailHash($lead->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $crawler = $this->client->request(Request::METHOD_GET, $unsubscribeUrl);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), $this->client->getResponse()->getContent());

        $form = $crawler->filter(self::FORM_SELECTOR);
        $html = $form->html();

        foreach ($selectorsAndExpectedCounts as $selector => $expectedCount) {
            $message = sprintf(
                'The form HTML %s not contain the %s section. %s',
                0 === $expectedCount ? 'should' : 'does',
                $selector,
                $html
            );

            $this->assertCount($expectedCount, $form->filter($selector), $message);
        }

        // Ensure the token and save button are always included within the <form> tag
        $this->assertCount(1, $form->filter(self::TOKEN_SELECTOR), sprintf('The following HTML does not contain the _token. %s', $html));

        if ($hasPreferenceCenter) {
            $this->assertCount(1, $form->filter(self::CUSTOM_SAVE_BUTTON_SELECTOR), sprintf('The following HTML does not contain the save button. %s', $html));
        } else {
            $this->assertCount(1, $form->filter(self::DEFAULT_SAVE_BUTTON_SELECTOR), sprintf('The following HTML does not contain the save button. %s', $html));
        }
    }

    public static function frequencyFormRenderingDataProvider(): \Generator
    {
        yield 'Custom Preference Center: All preferences enabled' => [
            [
                'show_contact_segments'           => 1,
                'show_contact_categories'         => 1,
                'show_contact_preferred_channels' => 1,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        yield 'Custom Preference Center: Segments & Categories disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 1,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        yield 'Custom Preference Center: Preferred Channels & Frequency disabled' => [
            [
                'show_contact_segments'           => 1,
                'show_contact_categories'         => 1,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        yield 'Custom Preference Center: Frequency enabled & Pause Dates disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        yield 'Custom Preference Center: Frequency disabled & Pause Dates enabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency
                self::DEFAULT_PAUSE_DATES_SELECTOR      => 1, // determined by show_contact_pause_dates
            ],
            true,
        ];

        yield 'Custom Preference Center: All preferences disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        yield 'Default Preference Center: All preferences enabled' => [
            [
                'show_contact_segments'           => 1,
                'show_contact_categories'         => 1,
                'show_contact_preferred_channels' => 1,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 1, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];

        yield 'Default Preference Center: Segments & Categories disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 1,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 1, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];

        yield 'Default Preference Center: Preferred Channels & Frequency disabled' => [
            [
                'show_contact_segments'           => 1,
                'show_contact_categories'         => 1,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];

        yield 'Default Preference Center: Frequency enabled & Pause Dates disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 1,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];

        yield 'Default Preference Center: Frequency disabled & Pause Dates enabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 1,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];

        yield 'Default Preference Center: All preferences disabled' => [
            [
                'show_contact_segments'           => 0,
                'show_contact_categories'         => 0,
                'show_contact_preferred_channels' => 0,
                'show_contact_frequency'          => 0,
                'show_contact_pause_dates'        => 0,
            ],
            [
                self::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                self::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                self::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                self::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                self::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
            ],
            false,
        ];
    }

    private function createStat(Email $email, Lead $lead): Stat
    {
        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($lead);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setDateSent(new \DateTime());
        $stat->setTrackingHash(uniqid());
        $this->em->persist($stat);

        return $stat;
    }

    private function createEmail(bool $hasPreferenceCenter = true): Email
    {
        $email = new Email();
        $email->setName('Example');

        if ($hasPreferenceCenter) {
            $email->setPreferenceCenter($this->createPage());
        }

        $this->em->persist($email);

        return $email;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $this->em->persist($lead);

        return $lead;
    }

    private function createSegment(): Segment
    {
        $segment = new Segment();
        $segment->setName('My Segment');
        $segment->setPublicName('My Segment');
        $segment->setAlias('my-segment');
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);

        return $segment;
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setTitle('My Category');
        $category->setAlias('my-category');
        $category->setIsPublished(true);
        $category->setBundle('global');
        $this->em->persist($category);

        return $category;
    }

    private function createPage(): Page
    {
        $page = new Page();
        $page->setTitle('Preference Center');
        $page->setAlias('preference-center');
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml($this->getPageContent());
        $page->setIsPublished(true);
        $this->em->persist($page);

        return $page;
    }

    private function getPageContent(): string
    {
        return <<<PAGE
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <title>{pagetitle}</title>
    <meta name="description" content="{pagemetadescription}">
</head>
<body>
    <div>
        {langbar}
        {sharebuttons}
    </div>
    <div>
        {successmessage}
        <div>
            {segmentlist}
        </div>
        <div>
            {categorylist}
        </div>
        <div>
            {preferredchannel}
        </div>
        <div>
            {channelfrequency}
        </div>
        <div>
            {saveprefsbutton}
        </div>
    </div>
</body>
</html>
PAGE;
    }
}
