<?php

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class EmailGraphStatsControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testTemplateViewAction(): void
    {
        $email = $this->createAndPersistEmail('Email A');

        $this->client->request(Request::METHOD_GET, "/s/emails-graph-stats/{$email->getId()}/0/2022-08-21/2022-09-21");
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    public function testSegmentViewAction(): void
    {
        $segment = $this->createSegment('segment-B', []);
        $email   = $this->createAndPersistEmail('Email B', $segment);

        $this->client->request(Request::METHOD_GET, "/s/emails-graph-stats/{$email->getId()}/0/2022-08-21/2022-09-21");
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    private function createAndPersistEmail(string $name, ?LeadList $segment = null): Email
    {
        $email = $this->createEmail($name);
        if (null !== $segment) {
            $email->addList($segment);
        }
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
