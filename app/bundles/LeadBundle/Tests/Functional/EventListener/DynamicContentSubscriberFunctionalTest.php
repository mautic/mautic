<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class DynamicContentSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    protected $useCleanupRollback = false;

    public function testLeadSeesContentWhenPrimaryCompanyIsInSegment(): void
    {
        $companySegment = $this->createCompanySegment('VIP Companies', 'vip-companies');

        $company = $this->createCompany('ACME Corp');
        $this->addCompanyToCompanySegment($company, $companySegment);

        $lead = $this->createLead('john@acme.com', 'John');
        $this->addLeadToCompany($lead, $company, true);

        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => [$companySegment->getId()],
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'VIP Content');

        $page = $this->createPage($dynamicContent);

        // Logout so that DynamicContentSubscriber::decodeTokens uses ContactTracker
        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('VIP Content', $response->getContent());
    }

    public function testLeadDoesNotSeeContentWhenPrimaryCompanyIsNotInSegment(): void
    {
        $companySegment = $this->createCompanySegment('VIP Companies', 'vip-companies');

        $company = $this->createCompany('Regular Corp');

        $lead = $this->createLead('jane@regular.com', 'Jane');
        $this->addLeadToCompany($lead, $company, true);

        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => [$companySegment->getId()],
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'VIP Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('VIP Content', $response->getContent());
    }

    public function testLeadSeesContentWithNotInOperator(): void
    {
        $companySegment = $this->createCompanySegment('VIP Companies', 'vip-companies');

        $company = $this->createCompany('Regular Corp');

        $lead = $this->createLead('jane@regular.com', 'Jane');
        $this->addLeadToCompany($lead, $company, true);

        // Set lead as system contact for this request
        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => [$companySegment->getId()],
                'display'  => null,
                'operator' => '!in',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'Non-VIP Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Non-VIP Content', $response->getContent());
    }

    public function testLeadWithoutPrimaryCompanySeesContentWithEmptyOperator(): void
    {
        $lead = $this->createLead('solo@example.com', 'Solo');

        // Set lead as system contact for this request
        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => null,
                'display'  => null,
                'operator' => 'empty',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'No Company Segment Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No Company Segment Content', $response->getContent());
    }

    public function testLeadWithCompanyInSegmentDoesNotSeeContentWithEmptyOperator(): void
    {
        $companySegment = $this->createCompanySegment('VIP Companies', 'vip-companies');

        $company = $this->createCompany('ACME Corp');
        $this->addCompanyToCompanySegment($company, $companySegment);

        $lead = $this->createLead('john@acme.com', 'John');
        $this->addLeadToCompany($lead, $company, true);

        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => null,
                'display'  => null,
                'operator' => 'empty',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'No Company Segment Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('No Company Segment Content', $response->getContent());
    }

    public function testLeadWithCompanyInSegmentSeesContentWithNotEmptyOperator(): void
    {
        $companySegment = $this->createCompanySegment('VIP Companies', 'vip-companies');

        $company = $this->createCompany('ACME Corp');
        $this->addCompanyToCompanySegment($company, $companySegment);

        $lead = $this->createLead('john@acme.com', 'John');
        $this->addLeadToCompany($lead, $company, true);

        // Set lead as system contact for this request
        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'Has Company Segment Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Has Company Segment Content', $response->getContent());
    }

    public function testLeadWithoutPrimaryCompanyDoesNotSeeContentWithNotEmptyOperator(): void
    {
        $lead = $this->createLead('solo@example.com', 'Solo');

        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'Has Company Segment Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Has Company Segment Content', $response->getContent());
    }

    public function testLeadWithCompanyInMultipleSegments(): void
    {
        $segment1 = $this->createCompanySegment('VIP Companies', 'vip-companies');
        $segment2 = $this->createCompanySegment('Gold Companies', 'gold-companies');

        $company = $this->createCompany('Premium Corp');
        $this->addCompanyToCompanySegment($company, $segment1);
        $this->addCompanyToCompanySegment($company, $segment2);

        $lead = $this->createLead('premium@corp.com', 'Premium');
        $this->addLeadToCompany($lead, $company, true);

        $contactTracker = self::getContainer()->get('mautic.tracker.contact');
        $contactTracker->setSystemContact($lead);

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'company_segments',
                'object'   => 'company_segments',
                'type'     => 'company_segments',
                'filter'   => [$segment1->getId(), $segment2->getId()],
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithFilter($filters, 'Premium Content');

        $page = $this->createPage($dynamicContent);

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, sprintf('/%s?contactId=%d', $page->getAlias(), $lead->getId()));

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Premium Content', $response->getContent());
    }

    /**
     * Helper: Create DynamicContent with company_segments filters.
     *
     * @param array<array<mixed>> $filters
     */
    private function createDynamicContentWithFilter(array $filters, string $content): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Test DWC: '.$content);
        $dynamicContent->setDescription('Test Dynamic Web Content');
        $dynamicContent->setFilters($filters);
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName('test_slot_'.uniqid());
        $dynamicContent->setContent($content);
        $dynamicContent->setIsPublished(true);
        $this->em->persist($dynamicContent);
        $this->em->flush();

        return $dynamicContent;
    }

    private function createPage(DynamicContent $dynamicContent): Page
    {
        $dwcToken = sprintf('{dwc=%s}', $dynamicContent->getSlotName());

        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('Test Page with DWC');
        $page->setAlias('test-page-dwc-'.uniqid());
        $page->setTemplate('Blank');
        $page->setCustomHtml('<html><body>'.$dwcToken.'</body></html>');
        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    /**
     * Wrapper for CreateTestEntitiesTrait::createCompany to match original signature.
     */
    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);
        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    /**
     * Wrapper for CreateTestEntitiesTrait::createLead to match original signature.
     */
    private function createLead(string $email, string $firstName): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setFirstname($firstName);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * Add lead to company (wraps relationship creation).
     */
    private function addLeadToCompany(Lead $lead, Company $company, bool $isPrimary): void
    {
        $this->createCompanyLead($company, $lead, $isPrimary);
    }
}
