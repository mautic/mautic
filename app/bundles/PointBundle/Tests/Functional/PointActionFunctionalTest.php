<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\Point;

class PointActionFunctionalTest extends MauticMysqlTestCase
{
    public function testPointActionReadEmail(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::$container->get('mautic.lead.model.lead');

        $lead  = $this->createLead('john@doe.email');
        $email = $this->createEmail();

        $trackingHash = 'tracking_hash_123';
        $this->createEmailStat($lead, $email, $trackingHash);
        $pointAction = $this->createReadEmailAction(5);
        $this->client->request('GET', '/email/'.$trackingHash.'.gif');

        $lead = $leadModel->getEntity($lead->getId());

        $this->assertEquals($pointAction->getDelta(), $lead->getPoints());
    }

    public function testPointActionWithLeagueReadEmail(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::$container->get('mautic.lead.model.lead');

        $lead   = $this->createLead('john@doe.email');
        $email  = $this->createEmail();
        $league = $this->createLeague('League A');

        $trackingHash = 'tracking_hash_123';
        $this->createEmailStat($lead, $email, $trackingHash);
        $pointAction = $this->createReadEmailAction(5, $league);
        $this->client->request('GET', '/email/'.$trackingHash.'.gif');
        $this->em->clear(Lead::class);
        $lead        = $leadModel->getEntity($lead->getId());
        $leagueScore = $lead->getLeagueScores()->first();

        $this->assertEquals($pointAction->getDelta(), $lead->getPoints());
        $this->assertEquals($pointAction->getDelta(), $leagueScore->getScore());
    }

    private function createReadEmailAction(int $delta, League $league = null): Point
    {
        $pointAction = new Point();
        $pointAction->setName('Read email action');
        $pointAction->setDelta($delta);
        $pointAction->setType('email.open');
        if ($league) {
            $pointAction->setLeague($league);
        }
        $this->em->persist($pointAction);

        return $pointAction;
    }

    private function createEmailStat(
        Lead $lead,
        Email $email,
        string $trackingHash
    ): Stat {
        /** @var StatRepository $statRepository */
        $statRepository = self::$container->get('mautic.email.repository.stat');

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
        string $email
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

    private function createLeague(
        string $name
    ): League {
        $league = new League();
        $league->setName($name);
        $this->em->persist($league);

        return $league;
    }
}
