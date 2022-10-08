<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\EventListener;

use DateTime;
use Generator;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList as Segment;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BuilderSubscriberTest extends AbstractMauticTestCase
{
    // Custom preference center page
    public const CUSTOM_SEGMENT_SELECTOR           = '.pref-segmentlist';
    public const CUSTOM_CATEGORY_SELECTOR          = '.pref-categorylist';
    public const CUSTOM_PREFERRED_CHANNEL_SELECTOR = '.pref-preferredchannel';
    public const CUSTOM_CHANNEL_FREQ_SELECTOR      = '.pref-channelfrequency';
    public const CUSTOM_SAVE_BUTTON_TEXT           = 'Save preferences';

    // Default preference center page
    public const DEFAULT_SEGMENT_SELECTOR           = '#contact-segments';
    public const DEFAULT_CATEGORY_SELECTOR          = '#global-categories';
    public const DEFAULT_PREFERRED_CHANNEL_SELECTOR = '#preferred_channel';
    public const DEFAULT_CHANNEL_FREQ_SELECTOR      = '[data-contact-frequency="1"]';
    public const DEFAULT_PAUSE_DATES_SELECTOR       = '[data-contact-pause-dates="1"]';
    public const DEFAULT_SAVE_BUTTON_TEXT           = 'Save';

    // Common to both custom and default
    const TOKEN_SELECTOR = '#lead_contact_frequency_rules__token';
    const FORM_SELECTOR  = 'form[name="lead_contact_frequency_rules"]';

    /**
     * Tests both the default and custom preference center pages.
     *
     * @dataProvider frequencyFormRenderingDataProvider
     *
     * @param array<string,int> $configParams
     * @param array<string,int> $selectorsAndExpectedCounts
     */
    public function testUnsubscribeFormRendersPreferenceCenterPageCorrectly(array $configParams, array $selectorsAndExpectedCounts, bool $hasPreferenceCenter = false, bool $useTokens = false): void
    {
        $this->setUpSymfony(array_merge(['show_contact_preferences' => 1], $configParams));

        $emailStat = $this->createStat(
            $this->createEmail($configParams, $hasPreferenceCenter, $useTokens),
            $lead = $this->createLead()
        );

        $this->createSegment();
        $this->createCategory();

        $this->em->flush();

        $unsubscribeUrl = $this->router->generate('mautic_email_unsubscribe', [
            'idHash'     => $emailStat->getTrackingHash(),
            'urlEmail'   => $lead->getEmail(),
            'secretHash' => $this->container->get('mautic.helper.mailer_hash')->getEmailHash($lead->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $crawler = $this->client->request('GET', $unsubscribeUrl);
        $form    = $crawler->filter(static::FORM_SELECTOR);
        $html    = $form->html();

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
            $button = $form->selectButton(static::CUSTOM_SAVE_BUTTON_TEXT);
            Assert::assertCount(1, $button, sprintf('The following HTML does not contain the save button with text "%s". %s', static::CUSTOM_SAVE_BUTTON_TEXT, $html));
        } else {
            $button = $form->selectButton(static::DEFAULT_SAVE_BUTTON_TEXT);
            Assert::assertCount(1, $button, sprintf('The following HTML does not contain the save button with text "%s". %s', static::DEFAULT_SAVE_BUTTON_TEXT, $html));
        }

        if ($configParams['show_contact_frequency']) {
            $prefForm = $button->form();
            $checkbox = $prefForm->get('lead_contact_frequency_rules[lead_channels][subscribed_channels][0]');
            \assert($checkbox instanceof ChoiceFormField);
            $checkbox->untick();
            $this->client->submit($prefForm);

            Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

            $doNotContactRepository = $this->em->getRepository(DoNotContact::class);
            \assert($doNotContactRepository instanceof DoNotContactRepository);

            $dncRecords = $doNotContactRepository->getEntriesByLeadAndChannel($lead, 'email');
            Assert::assertCount(1, $dncRecords);
        }
    }

    public function frequencyFormRenderingDataProvider(): Generator
    {
        // Custom Preference Center: All preferences enabled with tokens instead of slots
        yield [
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
            true,
        ];

        // Custom Preference Center: All preferences enabled
        yield [
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

        // Custom Preference Center: Segments & Categories disabled
        yield [
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

        // Custom Preference Center: Preferred Channels & Frequency disabled
        yield [
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

        // Custom Preference Center: Frequency enabled & Pause Dates disabled
        yield [
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

        // Custom Preference Center: Frequency disabled & Pause Dates enabled
        yield [
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
                static::CUSTOM_CHANNEL_FREQ_SELECTOR      => 1, // determined by EITHER show_contact_frequency & show_contact_pause_dates
            ],
            true,
        ];

        // Custom Preference Center: All preferences disabled
        yield [
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

        // Default Preference Center: All preferences enabled
        yield [
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
        ];

        // Default Preference Center: Segments & Categories disabled
        yield [
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
        ];

        // Default Preference Center: Preferred Channels & Frequency disabled
        yield [
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
        ];

        // Default Preference Center: Frequency enabled & Pause Dates disabled
        yield [
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
        ];

        // Default Preference Center: Frequency disabled & Pause Dates enabled
        yield [
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
        ];

        // Default Preference Center: All preferences disabled
        yield [
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
        ];
    }

    private function createStat(Email $email, Lead $lead): Stat
    {
        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($lead);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setDateSent(new DateTime());
        $stat->setTrackingHash(uniqid());
        $this->em->persist($stat);

        return $stat;
    }

    /**
     * @param array<string,int> $configParams
     */
    private function createEmail(array $configParams, bool $hasPreferenceCenter = true, bool $useTokens): Email
    {
        $email = new Email();
        $email->setName('Example');

        if ($hasPreferenceCenter) {
            $content = $useTokens ? $this->getPageContentWithTokens($configParams) : $this->getPageContentWithSlots($configParams);
            $email->setPreferenceCenter($this->createPage($content));
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

    private function createPage(string $content): Page
    {
        $page = new Page();
        $page->setTitle('Preference Center');
        $page->setAlias('preference-center');
        $page->setIsPreferenceCenter(true);
        $page->setContent($content);
        $page->setCustomHtml($content);
        $page->setIsPublished(true);
        $this->em->persist($page);

        return $page;
    }

    /**
     * @param array<string,int> $configParams
     */
    private function getPageContentWithSlots(array $configParams): string
    {
        $slots = '';
        $slots .= $configParams['show_contact_segments'] ? '<div><div data-slot="segmentlist"></div></div>' : '';
        $slots .= $configParams['show_contact_categories'] ? '<div><div data-slot="categorylist"></div></div>' : '';
        $slots .= $configParams['show_contact_preferred_channels'] ? '<div><div data-slot="preferredchannel"></div></div>' : '';
        $slots .= $configParams['show_contact_frequency'] || $configParams['show_contact_pause_dates'] ? '<div><div data-slot="channelfrequency"></div></div>' : '';

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
        {$slots}
        <div><div data-slot="saveprefsbutton"></div></div>
    </div>
</body>
</html>
PAGE;
    }

    /**
     * @param array<string,int> $configParams
     */
    private function getPageContentWithTokens(array $configParams): string
    {
        $tokens = '';
        $tokens .= $configParams['show_contact_segments'] ? '<div>{segmentlist}</div>' : '';
        $tokens .= $configParams['show_contact_categories'] ? '<div>{categorylist}</div>' : '';
        $tokens .= $configParams['show_contact_preferred_channels'] ? '<div>{preferredchannel}</div>' : '';
        $tokens .= $configParams['show_contact_frequency'] || $configParams['show_contact_pause_dates'] ? '<div>{channelfrequency}</div>' : '';

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
        {$tokens}        
        <div>{saveprefsbutton}</div>
    </div>
</body>
</html>
PAGE;
    }
}
