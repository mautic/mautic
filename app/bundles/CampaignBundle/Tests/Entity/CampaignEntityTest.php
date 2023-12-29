<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;

class CampaignEntityTest extends MauticMysqlTestCase
{
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetEmailSendEvents(): void
    {
        $campaign = $this->createCampaignWithEmail();

        $this->assertTrue($campaign->isEmailCampaign());
        $this->assertCount(1, $campaign->getEmailSendEvents());
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithEmail(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        // Create email event
        $event = new Event();
        $event->setName('Send email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setChannel('email');
        $event->setChannelId($email->getId());
        $event->setCampaign($campaign);
        $this->em->persist($event);
        $this->em->flush();

        // Add event to campaign
        $campaign->addEvent(0, $event);

        return $campaign;
    }
}
