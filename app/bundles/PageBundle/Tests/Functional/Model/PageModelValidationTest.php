<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class PageModelValidationTest extends MauticMysqlTestCase
{
    private HitRepository $pageHitRepository;

    protected function setUp(): void
    {
        $this->configParams['validate_page_hit_required_data'] = true;
        parent::setUp();
        $this->pageHitRepository = self::getContainer()->get('mautic.page.repository.hit');
    }

    public function testPageHitWhenRequiredValuesValidationPass(): void
    {
        $companyEmail   = 'company@domain.tld';
        $lead           = $this->createLead($companyEmail);
        $dynamicContent = $this->createDynamicContent($lead);
        $page           = $this->createPage($dynamicContent);
        $stat           = $this->createStat($lead->getEmail());

        $this->em->flush();
        $this->em->clear();

        $ct           = $this->getEncodedClickThroughValue($stat->getTrackingHash(), (int) $lead->getId());
        $requestParam = '?ct='.$ct.'&page_title='.$page->getTitle();

        // hitPage() tracks only anonymous requests.
        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/'.$page->getAlias().$requestParam, [], []);
        $this->assertResponseIsSuccessful();

        $pageHit = $this->pageHitRepository->findOneBy([]);
        Assert::assertNotEmpty($pageHit, 'page hit should not be empty');
    }

    public function testPageHitWhenRequiredValuesValidationFails(): void
    {
        $lead = $this->createLeadForValidation();
        $stat = $this->createStat($lead->getEmail());

        $this->em->flush();
        $this->em->clear();

        $ct = $this->getEncodedClickThroughValue($stat->getTrackingHash(), (int) $lead->getId());
        // Use tracking pixel endpoint without page_title to trigger validation failure
        // This will have no page, no redirect, and no urlTitle (since page_title is missing)
        $requestParam = '?ct='.$ct;

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/mtracking.gif'.$requestParam, [], []);

        $this->assertResponseIsSuccessful();

        // Verify no Hit was persisted due to validation failure
        $pageHit = $this->pageHitRepository->findOneBy([]);
        Assert::assertNull($pageHit, 'page hit should not be persisted when validation fails');
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function createPage(DynamicContent $dynamicContent): Page
    {
        $page = new Page();
        $page->setTitle('PageTitle');
        $page->setAlias('page');
        $page->setCustomHtml('<html><body><div data-slot="dwc" data-param-slot-name="'.$dynamicContent->getSlotName().'"><span>Default content</span></div></body></html>');
        $this->em->persist($page);

        return $page;
    }

    private function createDynamicContent(Lead $lead): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Dynamic content');
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName('slot-name');
        $dynamicContent->setContent('<p>Contact email: {contactfield=email}, company email: {contactfield=companyemail}</p>');
        $dynamicContent->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => $lead->getEmail(),
                'display'  => null,
                'operator' => '=',
            ],
        ]);
        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    private function createLead(string $companyEmail): Lead
    {
        $lead = new Lead();
        $lead->setEmail('testemail@domain.tld');
        $this->em->persist($lead);

        $company = new Company();
        $company->setEmail($companyEmail);
        $this->em->persist($company);

        $companyLead = new CompanyLead();
        $companyLead->setLead($lead);
        $companyLead->setCompany($company);
        $companyLead->setPrimary(true);
        $companyLead->setDateAdded(new \DateTime());
        $this->em->persist($companyLead);

        return $lead;
    }

    private function createLeadForValidation(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('validation-test@domain.tld');
        $this->em->persist($lead);

        return $lead;
    }

    private function createStat(string $emailAddress): Stat
    {
        $stat = new Stat();
        $stat->setTrackingHash('62970e83798e0668813916');
        $stat->setDateSent(new \DateTime());
        $stat->setEmailAddress($emailAddress);
        $this->em->persist($stat);

        return $stat;
    }

    private function getEncodedClickThroughValue(string $trackingHash, int $leadId): string
    {
        return ClickthroughHelper::encodeArrayForUrl(
            [
                'source' => [],
                'email'  => null,
                'stat'   => $trackingHash,
                'lead'   => $leadId,
            ]
        );
    }
}
