<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PointBundle\Entity\Point;

class PointSubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var LeadRepository
     */
    private $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactRepository  = $this->em->getRepository(Lead::class);
    }

    public function testPointsForAnyEmailReadRepeatable(): void
    {
        $emails = [
            $this->createEmail('Email sample 1'),
            $this->createEmail('Email sample 2'),
        ];

        $lead = $this->createLead('john@doe.email');

        $this->createPointAction([
            'emails'      => [],
            'categories'  => [],
            'triggerMode' => '',
        ], 1, true);

        $stats = [
            $this->createStat($emails[0], $lead),
            $this->createStat($emails[1], $lead),
        ];

        $this->client->request('GET', '/email/'.$stats[0]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[1]->getTrackingHash().'.gif');

        $updatedLead = $this->contactRepository->getEntity($lead->getId());

        $this->assertEquals(2, $updatedLead->getPoints());
    }

    public function testPointsForCategoryEmailReadRepeatable(): void
    {
        $category = $this->createCategory('Samples');
        $emails   = [
            $this->createEmail('Email sample 1', $category),
            $this->createEmail('Email sample 2', $category),
            $this->createEmail('Without category email'),
        ];

        $lead = $this->createLead('john@doe.email');

        $this->createPointAction([
            'emails'      => [],
            'categories'  => [
                (string) $category->getId(),
            ],
            'triggerMode' => '',
        ], 1, true);

        $stats = [
            $this->createStat($emails[0], $lead),
            $this->createStat($emails[1], $lead),
            $this->createStat($emails[2], $lead),
        ];

        $this->client->request('GET', '/email/'.$stats[0]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[1]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[2]->getTrackingHash().'.gif');

        $updatedLead = $this->contactRepository->getEntity($lead->getId());

        $this->assertEquals(2, $updatedLead->getPoints());
    }

    public function testPointsOnceForReadSelectedEmail(): void
    {
        $emails = [
            $this->createEmail('Email sample 1'),
            $this->createEmail('Email sample 2'),
        ];

        $lead = $this->createLead('john@doe.email');

        $this->createPointAction([
            'emails' => [
                (string) $emails[0]->getId(),
                (string) $emails[1]->getId(),
            ],
            'categories'  => [],
        ], 1);

        $stats = [
            $this->createStat($emails[0], $lead),
            $this->createStat($emails[1], $lead),
        ];

        $this->client->request('GET', '/email/'.$stats[0]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[1]->getTrackingHash().'.gif');

        $updatedLead = $this->contactRepository->getEntity($lead->getId());

        $this->assertEquals(1, $updatedLead->getPoints());
    }

    public function testPointsForReadEachSelectedEmail(): void
    {
        $emails = [
            $this->createEmail('Email sample 1'),
            $this->createEmail('Email sample 2'),
        ];

        $lead = $this->createLead('john@doe.email');

        $this->createPointAction([
            'emails' => [
                (string) $emails[0]->getId(),
                (string) $emails[1]->getId(),
            ],
            'categories'  => [],
            'triggerMode' => 'internalId',
        ], 1);

        $stats = [
            $this->createStat($emails[0], $lead),
            $this->createStat($emails[1], $lead),
        ];

        $this->client->request('GET', '/email/'.$stats[0]->getTrackingHash().'.gif');
        $updatedLead = $this->contactRepository->getEntity($lead->getId());
        $this->assertEquals(1, $updatedLead->getPoints());

        $this->client->request('GET', '/email/'.$stats[1]->getTrackingHash().'.gif');
        $updatedLead = $this->contactRepository->getEntity($lead->getId());
        $this->assertEquals(2, $updatedLead->getPoints());
    }

    public function testPointsForReadEachEmailFromCategory(): void
    {
        $category = $this->createCategory('Samples');
        $emails   = [
            $this->createEmail('Email sample 1', $category),
            $this->createEmail('Email sample 2', $category),
            $this->createEmail('Without category email'),
        ];

        $lead = $this->createLead('john@doe.email');

        $this->createPointAction([
            'emails'      => [],
            'categories'  => [
                (string) $category->getId(),
            ],
            'triggerMode' => 'internalId',
        ], 1);

        $stats = [
            $this->createStat($emails[0], $lead),
            $this->createStat($emails[1], $lead),
            $this->createStat($emails[2], $lead),
        ];

        $this->client->request('GET', '/email/'.$stats[0]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[1]->getTrackingHash().'.gif');
        $this->client->request('GET', '/email/'.$stats[2]->getTrackingHash().'.gif');

        $updatedLead = $this->contactRepository->getEntity($lead->getId());

        $this->assertEquals(2, $updatedLead->getPoints());
    }

    protected function createPointAction(array $properties, int $delta, bool $isRepeatable = false): Point
    {
        $point = new Point();
        $point->setType('email.open');
        $point->setRepeatable($isRepeatable);
        $point->setName('read for each email');
        $point->setIsPublished(true);
        $point->setDelta($delta);
        $point->setProperties($properties);
        $this->em->persist($point);
        $this->em->flush();

        return $point;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createEmail(string $name, Category $category = null): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('template');
        if ($category) {
            $email->setCategory($category);
        }
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createStat(Email $email, Lead $lead): Stat
    {
        $trackingHash = md5(uniqid((string) rand()));

        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setLead($lead);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createLead(string $emailAddress): Lead
    {
        $lead = new Lead();
        $lead->setEmail($emailAddress);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createCategory(string $name): Category
    {
        $category = new Category();
        $category->setTitle($name);
        $category->setAlias($name);
        $category->setBundle('email');
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }
}
