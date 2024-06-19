<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;

final class CampaignSubscriberActionEmailToContactFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testOnCampaignTriggerActionSendEmailToContact(): void
    {
        $leadA = $this->createLead('Lead A', 'A', 'lead-a@test.com');
        $leadB = $this->createLead('Lead B');
        $leadC = $this->createLead('Lead C', 'D', 'lead-c@test.com');

        $campaign = $this->createCampaign('Campaign');

        $segment  = $this->createSegment('Segment A', [['object' => 'lead', 'glue' => 'and', 'field' => 'firstname', 'type' => 'text', 'operator' => 'startsWith', 'properties' => ['filter' => 'Lead']]]);

        $campaign->addList($segment);

        $category = $this->createCategory('CategoryA', 'category-a');

        $this->createLeadCategory($leadA, $category, true);
        $this->createLeadCategory($leadB, $category, true);
        $this->createLeadCategory($leadC, $category, false);

        $email      = $this->createEmailWithCategory('Email', $category);
        $property   = ['email' => $email->getId()];
        $this->createEvent('Event 1', $campaign, 'email.send', 'action', $property);

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:segments:update', ['--list-id' => $segment->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        /** @var LeadEventLogRepository $logRepo */
        $logRepo  = static::getContainer()->get('mautic.campaign.repository.lead_event_log');
        $metaData = [];
        foreach ($logRepo->getLeadLogs() as $leadLog) {
            if ($leadLog['metadata']) {
                $metaData[$leadLog['lead_id']] = $leadLog['metadata']['reason'];
            }
        }

        $translator = static::getContainer()->get('translator');
        $noEmailLog = $translator->trans(
            'mautic.email.contact_has_no_email',
            ['%contact%' => $leadB->getPrimaryIdentifier()]
        );
        $this->assertSame($noEmailLog, $metaData[$leadB->getId()], 'here');

        $unsubscribedLog = $translator->trans(
            'mautic.email.contact_has_unsubscribed_from_category',
            ['%contact%' => $leadC->getPrimaryIdentifier(), '%category%' => $category->getId()]
        );
        $this->assertSame($unsubscribedLog, $metaData[$leadC->getId()], 'here 2');
    }

    private function createEmailWithCategory(string $name, Category $category): Email
    {
        $email = $this->createEmail($name);
        $email->setCategory($category);

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
