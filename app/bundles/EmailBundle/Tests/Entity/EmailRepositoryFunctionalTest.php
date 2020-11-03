<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Entity;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class EmailRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testGetDoNotEmailListEmpty(): void
    {
        $result = $this->em->getRepository(Email::class)->getDoNotEmailList();

        Assert::assertSame([], $result);
    }

    public function testGetDoNotEmailListNotEmpty(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new DateTime());
        $doNotContact->setChannel('email');
        $this->em->persist($doNotContact);

        $this->em->flush();
        $emailRepository = $this->em->getRepository(Email::class);

        // no $leadIds
        $result = $emailRepository->getDoNotEmailList();
        Assert::assertSame([$lead->getId() => $lead->getEmail()], $result);

        // matching $leadIds
        $result = $emailRepository->getDoNotEmailList([$lead->getId()]);
        Assert::assertSame([$lead->getId() => $lead->getEmail()], $result);

        // mismatching $leadIds
        $result = $emailRepository->getDoNotEmailList([-1]);
        Assert::assertSame([], $result);
    }

    public function testCheckDoNotEmailNonExistent(): void
    {
        $result = $this->em->getRepository(Email::class)->checkDoNotEmail('name@domain.tld');

        Assert::assertFalse($result);
    }

    public function testCheckDoNotEmailExistent(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new DateTime());
        $doNotContact->setChannel('email');
        $doNotContact->setReason(1);
        $doNotContact->setComments('Some comment');
        $this->em->persist($doNotContact);

        $this->em->flush();

        $result = $this->em->getRepository(Email::class)->checkDoNotEmail('name@domain.tld');

        Assert::assertSame([
            'id'           => (string) $doNotContact->getId(),
            'unsubscribed' => true,
            'bounced'      => false,
            'manual'       => false,
            'comments'     => $doNotContact->getComments(),
        ], $result);
    }
}
