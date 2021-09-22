<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testUnsubscribeAction(): void
    {
        $client  = $this->client;
        $segment = $this->createSegment();
        $email   = $this->createEmail();
        $email->addList($segment);

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->flush();

        $client->request('GET', '/email/unsubscribe/'.$email->getId());
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    private function createSegment(string $suffix = 'A'): LeadList
    {
        $segment = new LeadList();
        $segment->setName("Segment $suffix");
        $segment->setPublicName("Segment $suffix");
        $segment->setAlias("segment-$suffix");

        return $segment;
    }

    private function createEmail(string $suffix = 'A', string $emailType = 'list'): Email
    {
        $email = new Email();
        $email->setName("Email $suffix");
        $email->setSubject("Email $suffix Subject");
        $email->setEmailType($emailType);

        return $email;
    }
}
