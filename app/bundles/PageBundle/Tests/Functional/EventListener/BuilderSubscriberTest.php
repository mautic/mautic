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
use PHPUnit\Framework\Assert;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class BuilderSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    // Custom preference center page
    public const CUSTOM_SEGMENT_SELECTOR           = '.pref-segmentlist input';
    public const CUSTOM_CATEGORY_SELECTOR          = '.pref-categorylist input';
    public const CUSTOM_PREFERRED_CHANNEL_SELECTOR = '.pref-preferredchannel select';
    public const CUSTOM_CHANNEL_FREQ_SELECTOR      = '.pref-channelfrequency div[data-contact-frequency="1"]';
    public const CUSTOM_SAVE_BUTTON_SELECTOR       = '.prefs-saveprefs a.btn-save';

    // Default preference center page
    public const DEFAULT_SEGMENT_SELECTOR           = '#contact-segments';
    public const DEFAULT_CATEGORY_SELECTOR          = '#global-categories';
    public const DEFAULT_PREFERRED_CHANNEL_SELECTOR = '#preferred_channel';
    public const DEFAULT_CHANNEL_FREQ_SELECTOR      = '[data-contact-frequency="1"]';
    public const DEFAULT_PAUSE_DATES_SELECTOR       = '[data-contact-pause-dates="1"]';
    public const DEFAULT_SAVE_BUTTON_SELECTOR       = '#lead_contact_frequency_rules_buttons_save';

    // Common to both custom and default
    public const TOKEN_SELECTOR = '#lead_contact_frequency_rules__token';
    public const FORM_SELECTOR  = 'form[name="lead_contact_frequency_rules"]';

    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences'] = 1;
        $data                                           = $this->getProvidedData();
        $this->configParams                             = array_merge($data[0], $this->configParams);

        parent::setUp();
    }

    /**
     * Tests both the default and custom preference center pages.
     *
     * @param mixed[]           $configParams
     * @param array<string,int> $selectorsAndExpectedCounts
     *
     * @dataProvider frequencyFormRenderingDataProvider
     */
    public function testUnsubscribeFormRendersPreferenceCenterPageCorrectly(array $configParams, array $selectorsAndExpectedCounts, bool $hasPreferenceCenter): void
    {
        $emailStat = $this->createStat(
            $this->createEmail($hasPreferenceCenter),
            $lead = $this->createLead()
        );

        $this->createSegment();
        $this->createCategory();

        $this->em->flush();

        $mailHashHelper = static::getContainer()->get(MailHashHelper::class);
        \assert($mailHashHelper instanceof MailHashHelper);

        $unsubscribeUrl = $this->router->generate('mautic_email_unsubscribe', [
            'idHash'     => $emailStat->getTrackingHash(),
            'urlEmail'   => $lead->getEmail(),
            'secretHash' => $mailHashHelper->getEmailHash($lead->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $crawler = $this->client->request('GET', $unsubscribeUrl);

        self::assertTrue($this->client->getResponse()->isSuccessful(), $this->client->getResponse()->getContent());

        $form = $crawler->filter(static::FORM_SELECTOR);
        $html = $form->html();

        foreach ($selectorsAndExpectedCounts as $selector => $expectedCount) {
            $message = sprintf(
                'The form HTML %s not contain the %s section. %s',
                0 === $expectedCount ? 'should' : 'does',
                $selector,
                $html
            );

            Assert::assertCount(
                $expectedCount,
                $form->filter($selector),
                $message
            );
        }

        // Ensure the token and save button are always included within the <form> tag
        Assert::assertCount(1, $form->filter(static::TOKEN_SELECTOR), sprintf('The following HTML does not contain the _token. %s', $html));

        if ($hasPreferenceCenter) {
            Assert::assertCount(1, $form->filter(static::CUSTOM_SAVE_BUTTON_SELECTOR), sprintf('The following HTML does not contain the save button. %s', $html));
        } else {
            Assert::assertCount(1, $form->filter(static::DEFAULT_SAVE_BUTTON_SELECTOR), sprintf('The following HTML does not contain the save button. %s', $html));
        }
    }

    public function frequencyFormRenderingDataProvider(): \Generator
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
                static::CUSTOM_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
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
                static::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
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
                static::CUSTOM_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by EITHER show_contact_frequency & show_contact_pause_dates
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
                static::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
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
                static::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency
                static::DEFAULT_PAUSE_DATES_SELECTOR      => 1, // determined by show_contact_pause_dates
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
                static::CUSTOM_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::CUSTOM_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::CUSTOM_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 0, // determined by EITHER show_contact_frequency & show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 1, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 1, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 1, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 1, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 1, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 1, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
                static::DEFAULT_SEGMENT_SELECTOR           => 0, // determined by show_contact_segments
                static::DEFAULT_CATEGORY_SELECTOR          => 0, // determined by show_contact_categories
                static::DEFAULT_PREFERRED_CHANNEL_SELECTOR => 0, // determined by show_contact_preferred_channels
                static::DEFAULT_CHANNEL_FREQ_SELECTOR      => 0, // determined by show_contact_frequency. This differs from a custom page.
                static::DEFAULT_PAUSE_DATES_SELECTOR       => 0, // determined FIRST by show_contact_frequency, then by show_contact_pause_dates
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
