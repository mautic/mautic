<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\Point;

class PointActionFunctionalTest extends MauticMysqlTestCase
{
    public function testPointActionReadEmail(): void
    {
        $this->logoutUser();

        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');

        $lead  = $this->createLead('john@doe.email');
        $email = $this->createEmail();

        $trackingHash = 'tracking_hash_123';
        $this->createEmailStat($lead, $email, $trackingHash);
        $pointAction = $this->createReadEmailAction(5);
        $this->client->request('GET', '/email/'.$trackingHash.'.gif');

        $lead = $leadModel->getEntity($lead->getId());

        $this->assertEquals($pointAction->getDelta(), $lead->getPoints());
    }

    public function testPointActionWithGroupReadEmail(): void
    {
        $this->logoutUser();

        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');

        $lead   = $this->createLead('john@doe.email');
        $email  = $this->createEmail();
        $group  = $this->createGroup('Group A');

        $trackingHash = 'tracking_hash_123';
        $this->createEmailStat($lead, $email, $trackingHash);
        $pointAction = $this->createReadEmailAction(5, $group);
        $this->client->request('GET', '/email/'.$trackingHash.'.gif');
        $this->em->clear(Lead::class);
        $lead        = $leadModel->getEntity($lead->getId());
        $groupScore  = $lead->getGroupScores()->first();

        $this->assertEquals($pointAction->getDelta(), $groupScore->getScore());
        // group point action shouldn't update main contact points
        $this->assertEquals(0, $lead->getPoints());
    }

    public function testPointActionEarlyReturnWhenNoPointsAvailable(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');

        $lead  = $this->createLead('jane@doe.email');
        $email = $this->createEmail();

        $trackingHash = 'tracking_hash_no_points_456';
        $this->createEmailStat($lead, $email, $trackingHash);
        // Note: No point actions created for email.open type

        $initialPoints = $lead->getPoints();
        $this->client->request('GET', '/email/'.$trackingHash.'.gif');

        $lead = $leadModel->getEntity($lead->getId());

        // Points should remain unchanged as no point actions are available
        $this->assertEquals($initialPoints, $lead->getPoints());
        $this->assertEquals(0, $lead->getPoints());
    }

    private function createReadEmailAction(int $delta, Group $group = null): Point
    {
        $pointAction = new Point();
        $pointAction->setName('Read email action');
        $pointAction->setDelta($delta);
        $pointAction->setType('email.open');
        if ($group) {
            $pointAction->setGroup($group);
        }
        $this->em->persist($pointAction);
        $this->em->flush();

        return $pointAction;
    }

    private function createEmailStat(
        Lead $lead,
        Email $email,
        string $trackingHash,
    ): Stat {
        /** @var StatRepository $statRepository */
        $statRepository = static::getContainer()->get('mautic.email.repository.stat');

        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $statRepository->saveEntity($stat);

        return $stat;
    }

    private function createLead(
        string $email,
    ): Lead {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Test email');
        $email->setSubject('Test email subject');
        $email->setEmailType('template');
        $email->setCustomHtml('<h1>Email content</h1><br>{signature}');
        $email->setIsPublished(true);
        $email->setFromAddress('from@api.test');
        $email->setFromName('API Test');
        $this->em->persist($email);

        return $email;
    }

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }
}
