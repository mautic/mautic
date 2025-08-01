<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicContentReportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private DynamicContent $dynamicContent;
    private Page $page;
    private Email $email;
    private Lead $contact;
    private string $dwcSlotName  = 'dwc_slot';
    private string $dwcPlacement = 'Body';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEntities();
    }

    /**
     * @param mixed[] $reportData
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('reportDataProvider')]
    public function testDynamicContentReportFunctionality(array $reportData, bool $isPage): void
    {
        // Create the report
        $reportId = $this->createDwcReport($reportData);

        // Verify report via API
        $this->client->request(Request::METHOD_GET, "/api/reports/{$reportId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $report = json_decode($clientResponse->getContent(), true);

        // Validate report basics
        $this->assertSame($reportData['name'], $report['report']['name']);
        $this->assertSame($reportData['source'], $report['report']['source']);

        // Verify content-specific data in the report
        $this->verifyReportContent($report, $isPage);
    }

    /**
     * Verify the correct content exists in the generated report.
     *
     * @param mixed[] $report
     */
    private function verifyReportContent(array $report, bool $isPage): void
    {
        $matchingRows = array_filter($report['data'], function (array $row) use ($isPage) {
            return (
                $isPage ?
                    isset($row['page_id']) && (int) $row['page_id'] === $this->page->getId() :
                    isset($row['email_id']) && (int) $row['email_id'] === $this->email->getId()
            )
                && isset($row['dwc_id']) && (int) $row['dwc_id'] === $this->dynamicContent->getId()
                && isset($row['dwc_slot_name']) && $row['dwc_slot_name'] === $this->dynamicContent->getSlotName()
                && isset($row['target']) && $row['target'] === $this->dwcPlacement;
        });

        Assert::assertNotEmpty($matchingRows, 'Expected Dynamic Web Content in report data not found');
    }

    /**
     * Test specifically that the Dynamic Web Content option is visible in the Data Source dropdown.
     */
    public function testDWCOptionIsVisibleInDataSource(): void
    {
        $crawler= $this->client->request('GET', '/s/reports/new');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $options = $crawler->filterXPath('//select[@name="report[source]"]')->html();
        $this->assertStringContainsString('Dynamic Web Content', $options);
    }

    /**
     * Data provider for Dynamic Web Content report tests.
     *
     * @return iterable<string, mixed[]>
     */
    public static function reportDataProvider(): iterable
    {
        yield 'Standard DWC Page Report' => [
            [
                'name'    => 'Page DWC Report Standard',
                'source'  => 'dwc',
                'columns' => [
                    'dwc.id',
                    'dwc.slot_name',
                    'dwc_stat.token_placement',
                    'p.id',
                    'p.title',
                ],
            ],
            true,
        ];

        yield 'Standard DWC Email Report' => [
            [
                'name'    => 'Email DWC Report Limited',
                'source'  => 'dwc',
                'columns' => [
                    'dwc.id',
                    'dwc.slot_name',
                    'dwc_stat.token_placement',
                    'e.id',
                    'e.name',
                    'e.subject',
                ],
            ],
            false,
        ];
    }

    /**
     * Setup test entities required for the test.
     */
    private function setupTestEntities(): void
    {
        $this->setupDynamicContent();
        $this->setupPageAndEmail();
        $this->createStats();
    }

    /**
     * Setup dynamic content and contact.
     */
    private function setupDynamicContent(): void
    {
        $this->dynamicContent = new DynamicContent();
        $this->dynamicContent->setName('test');
        $this->dynamicContent->setIsCampaignBased(false);
        $this->dynamicContent->setSlotName($this->dwcSlotName);

        $this->em->persist($this->dynamicContent);
    }

    /**
     * Setup page and email with dynamic content.
     */
    private function setupPageAndEmail(): void
    {
        $dwcContent = sprintf('{dwc=%s}Default{/dwc}', $this->dynamicContent->getSlotName());

        $this->page = new Page();
        $this->page->setTitle('DWC Page');
        $this->page->setAlias('this-is-a-test-page');
        $this->page->setCustomHtml($dwcContent);

        $this->email = new Email();
        $this->email->setDateAdded(new \DateTime());
        $this->email->setName('DWC Email');
        $this->email->setSubject('DWC Email');
        $this->email->setTemplate('Blank');
        $this->email->setCustomHtml($dwcContent);

        $this->em->persist($this->page);
        $this->em->persist($this->email);
    }

    /**
     * Create page and email stat entries.
     */
    private function createStats(): void
    {
        $this->contact = new Lead();
        $this->em->persist($this->contact);
        $this->em->flush();

        $model = $this->getContainer()->get('mautic.dynamicContent.model.dynamicContent');
        \assert($model instanceof DynamicContentModel);

        // Create page stat
        $pageEvent = new PageDisplayEvent('text', $this->page);
        $model->createStatEntry($this->dynamicContent, $this->contact, $pageEvent);

        // Create email stat
        $emailEvent = new EmailSendEvent(
            null,
            [
                'email' => $this->email,
                'lead'  => ['id' => $this->contact->getId(), 'email' => $this->contact->getEmail()],
            ],
        );
        $model->createStatEntry($this->dynamicContent, $this->contact, $emailEvent);
    }

    /**
     * Create a Dynamic Web Content report with the specified parameters.
     *
     * @param mixed[] $reportData
     */
    private function createDwcReport(array $reportData): int
    {
        // Navigate to new report page
        $report        = $this->client->request(Request::METHOD_GET, '/s/reports/new');
        $buttonCrawler = $report->selectButton('Save & Close');
        $form          = $buttonCrawler->form();

        // Set initial values
        $form->setValues([
            'report[name]'   => $reportData['name'],
            'report[source]' => $reportData['source'],
        ]);

        // Submit the form
        $report   = $this->client->submit($form);
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());

        // Get the ID of the new report
        $viewUrl  = $report->getUri();
        $reportId = (int) basename(parse_url($viewUrl, PHP_URL_PATH));

        // Edit the report to add columns
        $report        = $this->client->request(Request::METHOD_GET, '/s/reports/edit/'.$reportId);
        $buttonCrawler = $report->selectButton('Save & Close');
        $form          = $buttonCrawler->form();

        // Set columns
        $form->setValues([
            'report[columns]' => $reportData['columns'],
        ]);

        // Submit the edit form
        $report   = $this->client->submit($form);
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());

        return $reportId;
    }
}
