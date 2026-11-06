<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Entity\TrackableRepository;

final class TrackingDwcTokenLinkTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://mautic.test';

        parent::setUp();
    }

    public function testDwcTokenLinkRedirectTrackingInEmail(): void
    {
        $page  = $this->createLandingPage('Trackable Link Page');
        $dwc   = $this->createDynamicContentWithTrackableLink('Email DWC', 'email-dwc', 0, $page);
        $email = $this->createSegmentEmail('Email', $dwc->getSlotName());

        /** @var EmailModel $emailModel */
        $emailModel    = self::getContainer()->get(EmailModel::class);
        [$sentCount]   = $emailModel->sendEmailToLists($email);
        $this->assertEquals(2, $sentCount);

        /** @var TrackableRepository $trackableRepository */
        $trackableRepository = $this->em->getRepository(Trackable::class);
        $redirects           = $trackableRepository->findByChannel('email', $email->getId());
        $this->assertCount(1, $redirects);
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment for '.$name);
        $segment->setPublicName($name);
        $segment->setAlias('segment-for-'.$name);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createLead(string $firstname, string $email, string $city): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($firstname);
        $lead->setEmail($email);
        $lead->setCity($city);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function addContactToSegment(Lead $contact, LeadList $segment, bool $manuallyAdded = false): void
    {
        $leadList = new ListLead();
        $leadList->setList($segment);
        $leadList->setLead($contact);
        $leadList->setManuallyAdded($manuallyAdded);
        $leadList->setDateAdded(new \DateTime());
        $this->em->persist($leadList);
        $this->em->flush();
    }

    private function createSegmentEmail(string $name, ?string $slotName = null): Email
    {
        $segment = $this->createSegment($name);

        $lead = $this->createLead('Abc', 'test1@test.com', 'Pune');
        $this->addContactToSegment($lead, $segment, true);

        $lead1 = $this->createLead('Pqr', 'test2@test.com', 'Pune');
        $this->addContactToSegment($lead1, $segment, true);

        $email = new Email();
        $email->setIsPublished(true);
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('list');
        if ($slotName) {
            $email->setCustomHtml(
                '<html><body><div>{dwc='.$slotName.'}Default content{/dwc}</div></body></html>'
            );
        } else {
            $email->setCustomHtml('<html><body><div>Email Content</div></body></html>');
        }
        $email->addList($segment);

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createDynamicContentWithTrackableLink(
        string $name,
        string $slotName,
        int $order,
        Page $page,
    ): DynamicContent {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'city',
                'object'   => 'lead',
                'type'     => 'text',
                'filter'   => 'Pune',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $dwc = new DynamicContent();
        $dwc->setIsPublished(true)
            ->setName($name)
            ->setContent("<p><a href='{pagelink={$page->getId()}}' title='Page Link'>Test LP</a></p>")
            ->setIsCampaignBased(false)
            ->setFilters($filters)
            ->setSlotName($slotName)
            ->setDisplayOrder($order);

        $this->em->persist($dwc);
        $this->em->flush();

        return $dwc;
    }

    private function createLandingPage(string $title, ?string $slotName = null): Page
    {
        $pageAlias = strtolower(str_replace(' ', '-', $title));

        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle($title);
        $page->setAlias($pageAlias);
        $page->setRevision(1);
        $page->setLanguage('en');

        if ($slotName) {
            $page->setCustomHtml('<html><body><div>{dwc='.$slotName.'}Default content{/dwc}</div></body></html>');
        } else {
            $page->setCustomHtml('<p>Landing page content</p>');
        }

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }
}
