<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;

/**
 * Test the new source URI tracking functionality for DWC from 3rd party websites.
 */
final class DynamicContentSourceUriTrackingTest extends MauticMysqlTestCase
{
    private Lead $testLead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testLead = $this->createTestLead();
    }

    private function createTestLead(string $email = 'test@example.com'): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createTestDwc(string $name, string $slotName): DynamicContent
    {
        $dwc = new DynamicContent();
        $dwc->setName($name)
            ->setSlotName($slotName)
            ->setContent('<p>Test content</p>')
            ->setIsCampaignBased(false)
            ->setIsPublished(true)
            ->setFilters([])
            ->setDisplayOrder(1);

        $this->getDwcModel()->saveEntity($dwc);
        $this->em->flush();

        return $dwc;
    }

    private function getDwcModel(): object
    {
        return $this->getContainer()->get(DynamicContentModel::class);
    }

    private function createEmailEvent(bool $isSubject = false): EmailSendEvent
    {
        $email = new Email();
        $email->setName('Test Email');
        $email->setSubject('Test Subject');
        $email->setEmailType('template');
        $email->setCustomHtml('<p>Test email content</p>');

        $this->em->persist($email);
        $this->em->flush();

        $args = [
            'email'   => $email,
            'content' => '<p>Test email content</p>',
            'subject' => 'Test Subject',
        ];

        $emailEvent = new EmailSendEvent(null, $args, false);
        if ($isSubject) {
            $emailEvent->setIsSubject(true);
        }

        return $emailEvent;
    }

    private function createPageEvent(): PageDisplayEvent
    {
        $page = new Page();
        $page->setTitle('Test Page');
        $page->setAlias('test-page');
        $page->setTemplate('blank');
        $page->setCustomHtml('<p>Test page content</p>');
        $page->setIsPublished(true);

        $this->em->persist($page);
        $this->em->flush();

        return new PageDisplayEvent('<p>Test page content</p>', $page);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('uriTrackingProvider')]
    public function testThirdPartyWebsiteUriTracking(string $uri, string $expectedUri): void
    {
        $dwc = $this->createTestDwc('Test DWC for URI Tracking', 'test-slot');

        $sourceData = ['uri' => $uri];
        $stat       = $this->getDwcModel()->createStatEntry($dwc, $this->testLead, null, $sourceData);

        $this->assertNotNull($stat);
        $this->assertNotEmpty($stat->getSourceData());
        $this->assertEquals($expectedUri, $stat->getSourceData()['uri']);
        $this->assertEquals('body', $stat->getTokenPlacement());
    }

    /**
     * @return \Iterator<string, array<int, string>>
     */
    public static function uriTrackingProvider(): \Iterator
    {
        yield 'simple URL' => [
            'https://example.com/page1',
            'https://example.com/page1',
        ];
        yield 'URL with query params' => [
            'https://example.com/page?utm_source=google&utm_medium=cpc',
            'https://example.com/page?utm_source=google&utm_medium=cpc',
        ];
        yield 'URL with fragment' => [
            'https://example.com/page#section1',
            'https://example.com/page#section1',
        ];
        yield 'complex URL' => [
            'https://subdomain.example.com:8080/path/to/page?param=value&another=test#anchor',
            'https://subdomain.example.com:8080/path/to/page?param=value&another=test#anchor',
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sourceDataNullScenariosProvider')]
    public function testSourceDataIsNull(
        string $testName,
        ?string $eventType,
        ?string $expectedSource,
        string $expectedTokenPlacement = 'body',
    ): void {
        $dwc = $this->createTestDwc("Test DWC for {$testName}", "test-slot-{$testName}");

        $event = match ($eventType) {
            'page'  => $this->createPageEvent(),
            'email' => $this->createEmailEvent(),
            default => null,
        };

        $stat = $this->getDwcModel()->createStatEntry($dwc, $this->testLead, $event);

        $this->assertNotNull($stat);
        $this->assertEquals($expectedSource, $stat->getSource());
        if ($expectedSource) {
            $this->assertNotNull($stat->getSourceId());
        } else {
            $this->assertNull($stat->getSourceId());
        }
        $this->assertEmpty($stat->getSourceData());
        $this->assertEquals($expectedTokenPlacement, $stat->getTokenPlacement());
    }

    /**
     * @return \Iterator<string, array<int, (string | null)>>
     */
    public static function sourceDataNullScenariosProvider(): \Iterator
    {
        yield 'page requests' => [
            'page',
            'page',
            'page',
            'body',
        ];
        yield 'email requests' => [
            'email',
            'email',
            'email',
            'body',
        ];
        yield 'direct model calls' => [
            'direct',
            null,
            null,
            'body',
        ];
    }
}
