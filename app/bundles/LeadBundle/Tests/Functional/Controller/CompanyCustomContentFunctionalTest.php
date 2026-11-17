<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

final class CompanyCustomContentFunctionalTest extends MauticMysqlTestCase
{
    private string $csvFile;

    private bool $injectContent = true;

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(EventDispatcherInterface::class)->addListener(CoreEvents::VIEW_INJECT_CUSTOM_CONTENT, function (CustomContentEvent $event): void {
            if (!$this->injectContent) {
                return;
            }

            $event->addContent(sprintf('<div class="custom-content-marker">%s|%s</div>', $event->getViewName(), $event->getContext()));
        });
    }

    protected function beforeTearDown(): void
    {
        if (isset($this->csvFile) && file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function companyViewContextProvider(): iterable
    {
        yield 'engagement before' => ['company.engagement.before'];
        yield 'engagement after' => ['company.engagement.after'];
        yield 'contacts after' => ['company.contacts.after'];
        yield 'tabs' => ['company.tabs'];
        yield 'tabs content' => ['company.tabs.content'];
        yield 'sidebar top' => ['company.sidebar.top'];
        yield 'sidebar bottom' => ['company.sidebar.bottom'];
    }

    #[DataProvider('companyViewContextProvider')]
    public function testCompanyViewRendersCustomContent(string $context): void
    {
        $this->client->request(Request::METHOD_GET, '/s/companies/view/'.$this->createCompany()->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            '@MauticLead/Company/company.html.twig|'.$context,
            (string) $this->client->getResponse()->getContent()
        );
        $this->assertSelectorExists('#company-contacts-container #contacts-table');
    }

    public function testCompanyViewWithoutCustomTabsRendersNoTabs(): void
    {
        $this->injectContent = false;

        $this->client->request(Request::METHOD_GET, '/s/companies/view/'.$this->createCompany()->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#contacts-table');
        $this->assertSelectorNotExists('#company-contacts-container');
    }

    public function testCompanyFormRendersCustomContentInSidebar(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/companies/new');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            '@MauticLead/Company/form_standalone.html.twig|company.form.sidebar',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testImportMappingFormRendersCustomContentInDefaults(): void
    {
        $this->csvFile = sys_get_temp_dir().'/'.uniqid('mautic_import_test_', true).'.csv';
        file_put_contents($this->csvFile, "companyname,companyemail\nAcme,info@acme.test\n");

        $crawler = $this->client->request(Request::METHOD_GET, '/s/companies/import/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Upload')->form();
        $form->setValues([
            'lead_import[file]'       => $this->csvFile,
            'lead_import[batchlimit]' => 100,
            'lead_import[delimiter]'  => ',',
            'lead_import[enclosure]'  => '"',
            'lead_import[escape]'     => '\\',
        ]);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            '@MauticLead/Import/_mapping_form.html.twig|import.defaults',
            (string) $this->client->getResponse()->getContent()
        );
    }

    private function createCompany(): Company
    {
        $company = new Company();
        $company->setName('Custom content company');
        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }
}
